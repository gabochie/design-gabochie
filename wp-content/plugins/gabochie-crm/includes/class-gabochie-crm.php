<?php
/**
 * Gabochie CRM core.
 *
 * Owns {prefix}gab_contacts — the single subscriber/lead store behind the
 * newsletter, lead hub, cold outreach and the command-center funnel. Replaces
 * FluentCRM as the audience source with a tiny first-party table, kept in sync
 * by every capture point via `gabochie_crm_upsert()`.
 *
 * @package Gabochie_Crm
 */

defined( 'ABSPATH' ) || exit;

/**
 * In-house contact store for Gabochie Design.
 */
class Gabochie_Crm {


	/**
 * @var string
*/
	const TABLE = 'gab_contacts';

	/**
 * @var string
*/
	const STATUS_SUBSCRIBED   = 'subscribed';
	const STATUS_UNSUBSCRIBED = 'unsubscribed';
	const STATUS_PENDING      = 'pending';

	/**
	 * Fully-qualified table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Does the contact table exist?
	 *
	 * @return bool
	 */
	public static function ready() {
		global $wpdb;
		$table = self::table();
     // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	/**
	 * Create the table when missing (idempotent).
	 */
	public static function ensure_schema() {
		global $wpdb;
		if ( self::ready() ) {
			return;
		}
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$collate = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			email VARCHAR(190) NOT NULL,
			first_name VARCHAR(100) NOT NULL DEFAULT '',
			last_name VARCHAR(100) NOT NULL DEFAULT '',
			phone VARCHAR(40) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'subscribed',
			contact_type VARCHAR(40) NOT NULL DEFAULT 'subscriber',
			list_key VARCHAR(80) NOT NULL DEFAULT '',
			source VARCHAR(60) NOT NULL DEFAULT '',
			tags VARCHAR(255) NOT NULL DEFAULT '',
			utm_source VARCHAR(120) NOT NULL DEFAULT '',
			utm_medium VARCHAR(120) NOT NULL DEFAULT '',
			utm_campaign VARCHAR(120) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			last_engaged_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_email (email),
			KEY idx_status (status),
			KEY idx_list (list_key),
			KEY idx_source (source),
			KEY idx_created (created_at)
		) {$collate};";
		dbDelta( $sql );
	}

	/**
	 * Insert or update one contact; returns the row id (0 when invalid).
	 *
	 * @param  string $email Sanitized candidate address.
	 * @param  array  $data  Contact context: first_name, last_name, phone,
	 *                       status, contact_type, list_key, source, tags (array
	 *                       or CSV), utm_source|medium|campaign, last_engaged_at.
	 * @return int Row id on success, 0 on failure.
	 */
	public static function upsert_contact( $email, array $data = array() ) {
		global $wpdb;
		$email = strtolower( trim( sanitize_email( (string) $email ) ) );
		if ( '' === $email || ! is_email( $email ) ) {
			return 0;
		}
		self::ensure_schema();

		$now = current_time( 'mysql' );
		$val = array(
			'first_name'      => isset( $data['first_name'] ) ? sanitize_text_field( (string) $data['first_name'] ) : null,
			'last_name'       => isset( $data['last_name'] ) ? sanitize_text_field( (string) $data['last_name'] ) : null,
			'phone'           => isset( $data['phone'] ) ? sanitize_text_field( (string) $data['phone'] ) : null,
			'status'          => isset( $data['status'] ) && self::is_status( $data['status'] ) ? $data['status'] : null,
			'contact_type'    => isset( $data['contact_type'] ) ? sanitize_key( $data['contact_type'] ) : null,
			'list_key'        => isset( $data['list_key'] ) ? sanitize_key( $data['list_key'] ) : null,
			'source'          => isset( $data['source'] ) ? sanitize_key( $data['source'] ) : null,
			'tags'            => isset( $data['tags'] ) ? self::normalize_tags( $data['tags'] ) : null,
			'utm_source'      => isset( $data['utm_source'] ) ? sanitize_key( $data['utm_source'] ) : null,
			'utm_medium'      => isset( $data['utm_medium'] ) ? sanitize_key( $data['utm_medium'] ) : null,
			'utm_campaign'    => isset( $data['utm_campaign'] ) ? sanitize_key( $data['utm_campaign'] ) : null,
			'last_engaged_at' => isset( $data['last_engaged_at'] ) && is_string( $data['last_engaged_at'] ) && '' !== trim( $data['last_engaged_at'] )
			? sanitize_text_field( trim( $data['last_engaged_at'] ) )
			: null,
		);

		$table    = self::table();
		$existing = $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM %i WHERE email = %s', $table, $email )
		);

		if ( $existing ) {
			$update = array( 'updated_at' => $now );
			foreach ( $val as $col => $v ) {
				if ( null !== $v ) {
					$update[ $col ] = $v;
				}
			}
         // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$res = $wpdb->update(
				$table,
				$update,
				array( 'id' => (int) $existing ),
				array_fill( 0, count( $update ), '%s' ),
				array( '%d' )
			);
			return false === $res ? 0 : (int) $existing;
		}

		$insert               = array(
			'email'      => $email,
			'updated_at' => $now,
		);
		$insert['created_at'] = ( isset( $data['created_at'] ) && is_string( $data['created_at'] ) && '' !== trim( $data['created_at'] ) )
		? sanitize_text_field( trim( $data['created_at'] ) )
		: $now;
		foreach ( $val as $col => $v ) {
			$insert[ $col ] = null === $v ? '' : $v;
		}
		if ( '' === $insert['status'] ) {
			$insert['status'] = self::STATUS_SUBSCRIBED;
		}

     // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$res = $wpdb->insert( $table, $insert );
		return false === $res ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Flip one contact's status (e.g. to unsubscribed).
	 *
	 * @param  string $email  Contact email.
	 * @param  string $status New status.
	 * @return bool
	 */
	public static function set_status( $email, $status ) {
		global $wpdb;
		$email = strtolower( trim( sanitize_email( (string) $email ) ) );
		if ( '' === $email || ! is_email( $email ) || ! self::is_status( $status ) ) {
			return false;
		}
		self::ensure_schema();
     // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$res = $wpdb->update(
			self::table(),
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'email' => $email ),
			array( '%s', '%s' ),
			array( '%s' )
		);
		return false !== $res;
	}

	/**
	 * Record that a contact just engaged (newsletter open/click, login, ...).
	 * Feeds the Command Center engaged_30d KPI via last_engaged_at.
	 *
	 * @param  string $email Contact email.
	 * @return bool
	 */
	public static function stamp_engagement( $email ) {
		global $wpdb;
		$email = strtolower( trim( sanitize_email( (string) $email ) ) );
		if ( '' === $email || ! is_email( $email ) ) {
			return false;
		}
		self::ensure_schema();
		if ( ! self::ready() ) {
			return false;
		}
		$now = current_time( 'mysql' );
     // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$res = $wpdb->update(
			self::table(),
			array(
				'last_engaged_at' => $now,
				'updated_at'      => $now,
			),
			array( 'email' => $email ),
			array( '%s', '%s' ),
			array( '%s' )
		);
		return false !== $res;
	}

	/**
	 * All subscribed contacts, oldest first.
	 *
	 * @return array<int,array{email:string,first_name:string,last_name:string,phone:string}>
	 */
	public static function subscribers() {
		global $wpdb;
		if ( ! self::ready() ) {
			return array();
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT email, first_name, last_name, phone FROM %i WHERE status = %s ORDER BY id ASC',
				self::table(),
				self::STATUS_SUBSCRIBED
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			$email = strtolower( trim( sanitize_email( (string) $row['email'] ) ) );
			if ( '' === $email || ! is_email( $email ) ) {
				continue;
			}
			$out[] = array(
				'email'      => $email,
				'first_name' => empty( $row['first_name'] ) ? '' : (string) $row['first_name'],
				'last_name'  => empty( $row['last_name'] ) ? '' : (string) $row['last_name'],
				'phone'      => empty( $row['phone'] ) ? '' : (string) $row['phone'],
			);
		}
		return $out;
	}

	/**
	 * One-time (idempotent) copy of FluentCRM `fc_subscribers` into gab_contacts.
	 * Existing emails are kept as-is (upsert merges, does not overwrite).
	 *
	 * @return array{source:int,migrated:int,skipped:int}
	 */
	public static function migrate_from_fluentcrm() {
		global $wpdb;
		$fc = $wpdb->prefix . 'fc_subscribers';
     // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $fc ) ) !== $fc ) {
			return array(
				'source'   => 0,
				'migrated' => 0,
				'skipped'  => 0,
			);
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT email, first_name, last_name, phone, status, created_at FROM %i ORDER BY id ASC',
				$fc
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array(
				'source'   => 0,
				'migrated' => 0,
				'skipped'  => 0,
			);
		}

		$source   = count( $rows );
		$migrated = 0;
		$skipped  = 0;
		foreach ( $rows as $row ) {
			$email = strtolower( trim( sanitize_email( (string) $row['email'] ) ) );
			if ( '' === $email || ! is_email( $email ) ) {
				++$skipped;
				continue;
			}
			$status = self::is_status( $row['status'] ?? '' ) ? $row['status'] : self::STATUS_SUBSCRIBED;
			$id     = self::upsert_contact(
				$email,
				array(
					'first_name' => (string) $row['first_name'],
					'last_name'  => (string) $row['last_name'],
					'phone'      => (string) $row['phone'],
					'status'     => $status,
					'list_key'   => 'leadhub-migrated',
					'source'     => 'fluentcrm-migrated',
					'created_at' => (string) $row['created_at'],
				)
			);
			if ( $id > 0 ) {
				++$migrated;
			} else {
				++$skipped;
			}
		}
		return array(
			'source'   => $source,
			'migrated' => $migrated,
			'skipped'  => $skipped,
		);
	}

	/**
	 * Normalize tags input (array or CSV) to a canonical comma CSV.
	 *
	 * @param  mixed $tags Tags input.
	 * @return string
	 */
	public static function normalize_tags( $tags ) {
		if ( is_string( $tags ) ) {
			$tags = explode( ',', $tags );
		}
		if ( ! is_array( $tags ) ) {
			return '';
		}
		$clean = array();
		foreach ( $tags as $t ) {
			// Colons are significant (source:voice-agent) — sanitize_key would drop them.
			$t = strtolower( trim( (string) $t ) );
			$t = preg_replace( '/[^a-z0-9_\-:]/', '', $t );
			if ( '' !== $t && ! in_array( $t, $clean, true ) ) {
				$clean[] = $t;
			}
		}
		return implode( ',', $clean );
	}

	/**
	 * Fetch a single contact row by id.
	 *
	 * @param  int $id Contact row id.
	 * @return array|null Associative row, or null when missing.
	 */
	public static function get_contact_by_id( $id ) {
		global $wpdb;
		if ( ! self::ready() ) {
			return null;
		}
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), (int) $id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Fetch a single contact row by email.
	 *
	 * Purchase capture needs this to read the existing tag set before merging
	 * (upsert_contact is merge-only, so tags must be passed complete) and to
	 * tell a brand new contact from an existing one.
	 *
	 * @param  string $email Contact email.
	 * @return array|null Associative row, or null when missing.
	 */
	public static function get_contact_by_email( $email ) {
		global $wpdb;
		$email = strtolower( trim( sanitize_email( (string) $email ) ) );
		if ( '' === $email || ! is_email( $email ) || ! self::ready() ) {
			return null;
		}
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE email = %s', self::table(), $email ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Search / list contacts with filters and pagination.
	 *
	 * @param array $args {
	 *                    Optional query arguments.
	 *
	 * @type   string $search   Free-text against email / first_name / last_name / phone.
	 * @type   string $status   subscribed|unsubscribed|pending (omit = any).
	 * @type   string $source   Source key (exact).
	 * @type   string $list_key List key (exact).
	 * @type   string $tag      A single tag (CSV substring match).
	 * @type   int    $page     Page number (1-based).
	 * @type   int    $per_page Rows per page.
	 * @type   string $orderby  Column to order by (id, email, created_at, last_engaged_at).
	 * @type   string $order    ASC|DESC.
	 * }
	 * @return array{items:array, total:int}
	 */
	public static function search_contacts( array $args = array() ) {
		global $wpdb;
		if ( ! self::ready() ) {
			return array(
				'items' => array(),
				'total' => 0,
			);
		}

		$where   = array( '1=1' );
		$prepare = array();

		$search = isset( $args['search'] ) ? trim( (string) $args['search'] ) : '';
		if ( '' !== $search ) {
			$where[]   = '(email LIKE %s OR first_name LIKE %s OR last_name LIKE %s OR phone LIKE %s)';
			$like      = '%' . $wpdb->esc_like( $search ) . '%';
			$prepare[] = $like;
			$prepare[] = $like;
			$prepare[] = $like;
			$prepare[] = $like;
		}

		if ( ! empty( $args['status'] ) && self::is_status( $args['status'] ) ) {
			$where[]   = 'status = %s';
			$prepare[] = $args['status'];
		}
		if ( ! empty( $args['source'] ) ) {
			$where[]   = 'source = %s';
			$prepare[] = sanitize_key( (string) $args['source'] );
		}
		if ( ! empty( $args['list_key'] ) ) {
			$where[]   = 'list_key = %s';
			$prepare[] = sanitize_key( (string) $args['list_key'] );
		}
		if ( ! empty( $args['tag'] ) ) {
			$tag = preg_replace( '/[^a-z0-9_\-:]/', '', strtolower( trim( (string) $args['tag'] ) ) );
			if ( '' !== $tag ) {
				$where[]   = 'tags LIKE %s';
				$prepare[] = '%' . $wpdb->esc_like( $tag ) . '%';
			}
		}

		$where_sql = implode( ' AND ', $where );
		$table     = self::table();

		if ( empty( $prepare ) ) {
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		} else {
			$total = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}", $prepare ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			);
		}

		$per_page = isset( $args['per_page'] ) ? max( 1, min( 500, (int) $args['per_page'] ) ) : 25;
		$page     = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$offset   = ( $page - 1 ) * $per_page;

		$orderby = 'id';
		if ( isset( $args['orderby'] ) && in_array( $args['orderby'], array( 'email', 'created_at', 'last_engaged_at', 'status', 'source' ), true ) ) {
			$orderby = $args['orderby'];
		}
		$order = ( isset( $args['order'] ) && 'ASC' === strtoupper( (string) $args['order'] ) ) ? 'ASC' : 'DESC';

		$items = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", array_merge( $prepare, array( $per_page, $offset ) ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			ARRAY_A
		);

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Count contacts matching a status (any if omitted).
	 *
	 * @param  string $status Optional status filter.
	 * @return int
	 */
	public static function count_contacts( $status = '' ) {
		global $wpdb;
		if ( ! self::ready() ) {
			return 0;
		}
		if ( '' !== $status && self::is_status( $status ) ) {
         // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', self::table(), $status ) );
		}
     // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', self::table() ) );
	}

	/**
	 * Hard delete a contact row by id (GDPR erasure).
	 *
	 * @param  int $id Contact row id.
	 * @return bool
	 */
	public static function delete_contact( $id ) {
		global $wpdb;
		if ( ! self::ready() ) {
			return false;
		}
     // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$res = $wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) );
		return false !== $res;
	}

	/**
	 * Flip the status of many contact ids at once.
	 *
	 * @param  array  $ids    Contact row ids.
	 * @param  string $status Target status.
	 * @return int Number of rows updated.
	 */
	public static function bulk_set_status( array $ids, $status ) {
		if ( ! self::is_status( $status ) ) {
			return 0;
		}
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( empty( $ids ) ) {
			return 0;
		}
		global $wpdb;
		if ( ! self::ready() ) {
			return 0;
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
     // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$res = $wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET status = %s, updated_at = %s WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				self::table(),
				$status,
				current_time( 'mysql' ),
				...$ids
			)
		);
		return false === $res ? 0 : (int) $res;
	}

	/**
	 * Append tags to many contacts.
	 *
	 * @param  array        $ids  Contact row ids.
	 * @param  array|string $tags Tags to add.
	 * @return int Number of rows updated.
	 */
	public static function bulk_add_tags( array $ids, $tags ) {
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( empty( $ids ) ) {
			return 0;
		}
		$extra = self::normalize_tags( $tags );
		global $wpdb;
		if ( ! self::ready() || '' === $extra ) {
			return 0;
		}
		$updated = 0;
		foreach ( $ids as $id ) {
			$row = self::get_contact_by_id( $id );
			if ( ! $row ) {
				continue;
			}
			$merged = self::normalize_tags( array_merge( explode( ',', $row['tags'] ), explode( ',', $extra ) ) );
			if ( $merged === $row['tags'] ) {
				continue;
			}
         // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$res = $wpdb->update(
				self::table(),
				array(
					'tags'       => $merged,
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
			if ( false !== $res ) {
				++$updated;
			}
		}
		return $updated;
	}

	/**
	 * Remove tags from many contacts.
	 *
	 * @param  array        $ids  Contact row ids.
	 * @param  array|string $tags Tags to remove.
	 * @return int Number of rows updated.
	 */
	public static function bulk_remove_tags( array $ids, $tags ) {
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( empty( $ids ) ) {
			return 0;
		}
		$remove = self::normalize_tags( $tags );
		$remove = '' === $remove ? array() : explode( ',', $remove );
		if ( empty( $remove ) ) {
			return 0;
		}
		global $wpdb;
		if ( ! self::ready() ) {
			return 0;
		}
		$updated = 0;
		foreach ( $ids as $id ) {
			$row = self::get_contact_by_id( $id );
			if ( ! $row ) {
				continue;
			}
			$keep = array_diff( explode( ',', $row['tags'] ), $remove );
			$keep = array_values(
				array_filter(
					$keep,
					static function ( $t ) {
						return '' !== $t;
					}
				)
			);
			if ( implode( ',', $keep ) === $row['tags'] ) {
				continue;
			}
         // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$res = $wpdb->update(
				self::table(),
				array(
					'tags'       => self::normalize_tags( $keep ),
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
			if ( false !== $res ) {
				++$updated;
			}
		}
		return $updated;
	}

	/**
	 * Distinct tags across all contacts (for a filter dropdown).
	 *
	 * @return array<string> Sorted, de-duplicated tag list.
	 */
	public static function get_unique_tags() {
		global $wpdb;
		if ( ! self::ready() ) {
			return array();
		}
		$rows   = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT tags FROM %i WHERE tags <> %s', self::table(), '' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$tagset = array();
		foreach ( $rows as $csv ) {
			foreach ( explode( ',', (string) $csv ) as $t ) {
				$t = trim( $t );
				if ( '' !== $t ) {
					$tagset[ $t ] = $t;
				}
			}
		}
		$tagset = array_values( $tagset );
		sort( $tagset, SORT_STRING );
		return $tagset;
	}

	/**
	 * Distinct source keys across all contacts.
	 *
	 * @return array<string> Sorted source list.
	 */
	public static function get_unique_sources() {
		return self::get_distinct_col( 'source' );
	}

	/**
	 * Distinct list keys across all contacts.
	 *
	 * @return array<string> Sorted list-key list.
	 */
	public static function get_unique_list_keys() {
		return self::get_distinct_col( 'list_key' );
	}

	/**
	 * Distinct non-empty values for one column.
	 *
	 * @param  string $col Column name.
	 * @return array<string>
	 */
	private static function get_distinct_col( $col ) {
		global $wpdb;
		if ( ! self::ready() ) {
			return array();
		}
		$col = sanitize_key( $col );
     // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT {$col} FROM %i WHERE {$col} <> ''", self::table() ) );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array_values( array_unique( array_map( 'strval', $rows ) ) );
		sort( $out, SORT_STRING );
		return $out;
	}

	/**
	 * Merge-update an existing contact row by id.
	 *
	 * Only the provided fields are written; omitted fields keep their stored
	 * value. updated_at is always bumped. Returns false if the row does not
	 * exist or nothing changed.
	 *
	 * @param  int   $id   Contact row id.
	 * @param  array $data Optional fields.
	 * @return bool
	 */
	public static function update_contact( $id, array $data = array() ) {
		global $wpdb;
		$id = (int) $id;
		if ( $id < 1 || ! self::ready() ) {
			return false;
		}
		$existing = self::get_contact_by_id( $id );
		if ( ! $existing ) {
			return false;
		}

		$fields = array(
			'first_name'   => isset( $data['first_name'] ) ? sanitize_text_field( (string) $data['first_name'] ) : null,
			'last_name'    => isset( $data['last_name'] ) ? sanitize_text_field( (string) $data['last_name'] ) : null,
			'phone'        => isset( $data['phone'] ) ? sanitize_text_field( (string) $data['phone'] ) : null,
			'status'       => isset( $data['status'] ) && self::is_status( $data['status'] ) ? $data['status'] : null,
			'contact_type' => isset( $data['contact_type'] ) ? sanitize_key( $data['contact_type'] ) : null,
			'list_key'     => isset( $data['list_key'] ) ? sanitize_key( $data['list_key'] ) : null,
			'source'       => isset( $data['source'] ) ? sanitize_key( $data['source'] ) : null,
			'tags'         => isset( $data['tags'] ) ? self::normalize_tags( $data['tags'] ) : null,
		);

		$update = array( 'updated_at' => current_time( 'mysql' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		foreach ( $fields as $col => $v ) {
			if ( null !== $v ) {
				$update[ $col ] = $v;
			}
		}

     // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$res = $wpdb->update(
			self::table(),
			$update,
			array( 'id' => $id ),
			array_fill( 0, count( $update ), '%s' ),
			array( '%d' )
		);
		return false !== $res;
	}

	/**
	 * Whether a status value is one we manage.
	 *
	 * @param  string $status
	 * @return bool
	 */
	private static function is_status( $status ) {
		return in_array( (string) $status, array( self::STATUS_SUBSCRIBED, self::STATUS_UNSUBSCRIBED, self::STATUS_PENDING ), true );
	}
}
