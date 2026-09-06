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

	/** @var string */
	const TABLE = 'gab_contacts';

	/** @var string */
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
		$table    = self::table();
		$collate  = $wpdb->get_charset_collate();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$table} (
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
				UNIQUE KEY uq_email (email),
				KEY idx_status (status),
				KEY idx_list (list_key),
				KEY idx_source (source),
				KEY idx_created (created_at)
			) {$collate};"
		);
	}

	/**
	 * Insert or update one contact; returns the row id (0 when invalid).
	 *
	 * @param string $email Sanitized candidate address.
	 * @param array  $data  Contact context: first_name, last_name, phone,
	 *                      status, contact_type, list_key, source, tags (array
	 *                      or CSV), utm_source|medium|campaign, last_engaged_at.
	 * @return int Row id on success, 0 on failure.
	 */
	public static function upsert_contact( $email, array $data = array() ) {
		global $wpdb;
		$email = strtolower( trim( sanitize_email( (string) $email ) ) );
		if ( '' === $email || ! is_email( $email ) ) {
			return 0;
		}
		self::ensure_schema();

		$now   = current_time( 'mysql' );
		$tags  = self::normalize_tags( $data['tags'] ?? array() );
		$set   = array(
			'first_name'    => isset( $data['first_name'] ) ? sanitize_text_field( (string) $data['first_name'] ) : '',
			'last_name'     => isset( $data['last_name'] ) ? sanitize_text_field( (string) $data['last_name'] ) : '',
			'phone'         => isset( $data['phone'] ) ? sanitize_text_field( (string) $data['phone'] ) : '',
			'status'        => self::is_status( $data['status'] ?? '' ) ? $data['status'] : self::STATUS_SUBSCRIBED,
			'contact_type'  => isset( $data['contact_type'] ) ? sanitize_key( $data['contact_type'] ) : 'subscriber',
			'list_key'      => isset( $data['list_key'] ) ? sanitize_key( $data['list_key'] ) : '',
			'source'        => isset( $data['source'] ) ? sanitize_key( $data['source'] ) : '',
			'tags'          => $tags,
			'utm_source'    => isset( $data['utm_source'] ) ? sanitize_key( $data['utm_source'] ) : '',
			'utm_medium'    => isset( $data['utm_medium'] ) ? sanitize_key( $data['utm_medium'] ) : '',
			'utm_campaign'  => isset( $data['utm_campaign'] ) ? sanitize_key( $data['utm_campaign'] ) : '',
			'updated_at'    => $now,
			'last_engaged_at' => isset( $data['last_engaged_at'] ) && is_string( $data['last_engaged_at'] ) && '' !== trim( $data['last_engaged_at'] )
				? sanitize_text_field( trim( $data['last_engaged_at'] ) )
				: null,
		);

		$table   = self::table();
		$existing = $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM %i WHERE email = %s', $table, $email )
		);

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$res = $wpdb->update(
				$table,
				$set,
				array( 'id' => (int) $existing ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
			return false === $res ? 0 : (int) $existing;
		}

		$set['created_at'] = isset( $data['created_at'] ) && is_string( $data['created_at'] ) && '' !== trim( $data['created_at'] )
			? sanitize_text_field( trim( $data['created_at'] ) )
			: $now;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$res = $wpdb->insert( $table, $set );
		return false === $res ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Flip one contact's status (e.g. to unsubscribed).
	 *
	 * @param string $email Contact email.
	 * @param string $status New status.
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
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
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
			return array( 'source' => 0, 'migrated' => 0, 'skipped' => 0 );
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT email, first_name, last_name, phone, status, created_at FROM %i ORDER BY id ASC',
				$fc
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array( 'source' => 0, 'migrated' => 0, 'skipped' => 0 );
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
		return array( 'source' => $source, 'migrated' => $migrated, 'skipped' => $skipped );
	}

	/**
	 * Normalize tags input (array or CSV) to a canonical comma CSV.
	 *
	 * @param mixed $tags Tags input.
	 * @return string
	 */
	private static function normalize_tags( $tags ) {
		if ( is_string( $tags ) ) {
			$tags = explode( ',', $tags );
		}
		if ( ! is_array( $tags ) ) {
			return '';
		}
		$clean = array();
		foreach ( $tags as $t ) {
			$t = sanitize_key( (string) $t );
			if ( '' !== $t && ! in_array( $t, $clean, true ) ) {
				$clean[] = $t;
			}
		}
		return implode( ',', $clean );
	}

	/**
	 * Whether a status value is one we manage.
	 *
	 * @param string $status
	 * @return bool
	 */
	private static function is_status( $status ) {
		return in_array( (string) $status, array( self::STATUS_SUBSCRIBED, self::STATUS_UNSUBSCRIBED, self::STATUS_PENDING ), true );
	}
}