<?php
/**
 * Gabochie Cold Outreach — database.
 *
 * Outbound job state tables, separate from FluentCRM (the CRM/audience).
 * `cold_outreach` tracks each imported contact through the send pipeline;
 * `cold_outreach_optout` is the do-not-contact registry checked before any send.
 *
 * @package Gabochie_Cold_Outreach
 */

defined( 'ABSPATH' ) || exit;

/**
 * Schema + safe queries for the outreach tracking tables.
 */
class Gabochie_Co_Db {

	/** @var string */
	const TABLE = 'gabochie_co_contacts';

	/** @var string */
	const TABLE_OPTOUT = 'gabochie_co_optout';

	/** @var string */
	const TABLE_LOG = 'gabochie_co_sendlog';

	/**
	 * Row status lifecycle.
	 */
	const STATUS = array(
		'imported'  => 'Imported (pending)',
		'queued'    => 'Queued',
		'sent'      => 'Sent',
		'failed'    => 'Failed',
		'opted_out' => 'Opted out',
	);

	/**
	 * Install the tables.
	 */
	public static function install() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$contacts = self::table( self::TABLE );
		$optout   = self::table( self::TABLE_OPTOUT );
		$log      = self::table( self::TABLE_LOG );

		dbDelta(
			"CREATE TABLE $contacts (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				email varchar(190) NOT NULL DEFAULT '',
				phone varchar(40) NOT NULL DEFAULT '',
				biz varchar(190) NOT NULL DEFAULT '',
				category varchar(120) NOT NULL DEFAULT '',
				region varchar(60) NOT NULL DEFAULT '',
				country varchar(60) NOT NULL DEFAULT '',
				channel varchar(20) NOT NULL DEFAULT '',
				segment varchar(40) NOT NULL DEFAULT '',
				status varchar(20) NOT NULL DEFAULT 'imported',
				attempts int unsigned NOT NULL DEFAULT 0,
				last_attempt datetime NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY email (email),
				KEY phone (phone),
				KEY status (status),
				KEY channel (channel)
			) $charset;"
		);

		dbDelta(
			"CREATE TABLE $optout (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				contact varchar(190) NOT NULL DEFAULT '',
				type varchar(8) NOT NULL DEFAULT 'email',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY contact (contact)
			) $charset;"
		);

		dbDelta(
			"CREATE TABLE $log (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
				channel varchar(20) NOT NULL DEFAULT '',
				kind varchar(20) NOT NULL DEFAULT '',
				status varchar(20) NOT NULL DEFAULT 'dry-run',
				message_text text NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY contact_id (contact_id),
				KEY created_at (created_at)
			) $charset;"
		);
	}

	/**
	 * Wire activation-hook-adjacent init (idempotent).
	 */
	public static function init() {
		// Ensure tables exist on load when running a CLI command.
		if ( defined( 'WP_CLI' ) ) {
			self::install();
		}
	}

	/**
	 * Prefix a base table name.
	 *
	 * @param string $base Base table name (one of self::*).
	 * @return string
	 */
	public static function table( $base ) {
		global $wpdb;
		return $wpdb->prefix . $base;
	}

	/**
	 * Insert a contact row (dedupe by normalized email or phone).
	 *
	 * @param array $data Contact data.
	 * @return int Contact id or 0.
	 */
	public static function upsert_contact( array $data ) {
		global $wpdb;
		$table = self::table( self::TABLE );

		$email = sanitize_email( $data['email'] ?? '' );
		$phone = sanitize_text_field( $data['phone'] ?? '' );

		if ( '' === $email && '' === $phone ) {
			return 0;
		}

		$args  = array();
		$where = array();
		if ( '' !== $email ) {
			$where[] = $wpdb->prepare( 'email = %s', $email );
		}
		if ( '' !== $phone ) {
			$where[] = $wpdb->prepare( 'phone = %s', $phone );
		}
		$row = null;
		if ( ! empty( $where ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
			$row = $wpdb->get_row( 'SELECT * FROM ' . $table . ' WHERE ' . implode( ' OR ', $where ) . ' LIMIT 1', ARRAY_A );
		}

		if ( is_array( $row ) && ! empty( $row['id'] ) ) {
			// Enrich existing row with any newly-provided fields.
			$update = array();
			foreach ( array( 'biz', 'category', 'region', 'country', 'segment' ) as $k ) {
				if ( isset( $data[ $k ] ) && '' !== $data[ $k ] && '' === $row[ $k ] ) {
					$update[ $k ] = sanitize_text_field( $data[ $k ] );
				}
			}
			// Refill email/phone gaps.
			if ( '' === $row['email'] && '' !== $email ) {
				$update['email'] = $email;
			}
			if ( '' === $row['phone'] && '' !== $phone ) {
				$update['phone'] = $phone;
			}
			if ( ! empty( $update ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->update( $table, $update, array( 'id' => (int) $row['id'] ) );
			}
			return (int) $row['id'];
		}

		// Insert new.
		$insert = array(
			'email'      => $email,
			'phone'      => $phone,
			'biz'        => sanitize_text_field( $data['biz'] ?? '' ),
			'category'   => sanitize_text_field( $data['category'] ?? '' ),
			'region'     => sanitize_text_field( $data['region'] ?? '' ),
			'country'    => sanitize_text_field( $data['country'] ?? '' ),
			'channel'    => sanitize_key( $data['channel'] ?? 'wa' ),
			'segment'    => sanitize_key( $data['segment'] ?? 'general' ),
			'status'     => 'imported',
			'created_at' => current_time( 'mysql' ),
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = $wpdb->insert( $table, $insert );
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Fetch contacts by status/channel with a limit for a send pass.
	 *
	 * @param string $status Status filter.
	 * @param int    $limit  Max rows.
	 * @return array Rows.
	 */
	public static function contacts_by_status( $status, $limit = 25 ) {
		global $wpdb;
		$table = self::table( self::TABLE );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM %i WHERE status = %s ORDER BY id ASC LIMIT %d', $table, $status, max( 1, (int) $limit ) ),
			ARRAY_A
		);
	}

	/**
	 * Set a contact's status (and increment attempts when moving to queued/sent).
	 *
	 * @param int    $id     Contact id.
	 * @param string $status New status.
	 */
	public static function set_status( $id, $status ) {
		global $wpdb;
		$table = self::table( self::TABLE );
		$data  = array(
			'status'       => in_array( $status, array_keys( self::STATUS ), true ) ? $status : 'failed',
			'last_attempt' => current_time( 'mysql' ),
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $table, $data, array( 'id' => (int) $id ) );
	}

	/**
	 * Helpers for counts on the admin dashboard.
	 *
	 * @param array $where Optional raw where SQL (no values).
	 * @return int
	 */
	public static function count( array $where ) {
		global $wpdb;
		$table = self::table( self::TABLE );
		$sql   = 'SELECT COUNT(*) FROM ' . $table;
		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Opt-out registry.
	 *
	 * @param string $contact Email or phone.
	 * @param string $type    email|wa.
	 * @return bool True if recorded.
	 */
	public static function opt_out( $contact, $type = 'email' ) {
		global $wpdb;
		$table   = self::table( self::TABLE_OPTOUT );
		$contact = sanitize_text_field( $contact );
		if ( '' === $contact ) {
			return false;
		}
		$exists = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE contact = %s AND type = %s', $table, $contact, $type )
		);
		if ( $exists ) {
			return true;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$table,
			array(
				'contact'    => $contact,
				'type'       => $type,
				'created_at' => current_time( 'mysql' ),
			)
		);
		return true;
	}

	/**
	 * Check opt-out for a contact across email or WhatsApp identifiers.
	 *
	 * @param string $email Email address (may be empty).
	 * @param string $phone Phone (may be empty).
	 * @return bool True if opted out.
	 */
	public static function is_opted_out( $email, $phone ) {
		global $wpdb;
		$table = self::table( self::TABLE_OPTOUT );
		if ( '' === $email && '' === $phone ) {
			return false;
		}
		$found = false;
		if ( '' !== $email ) {
			$n = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE contact = %s AND type = %s', $table, $email, 'email' )
			);
			if ( $n > 0 ) {
				$found = true;
			}
		}
		if ( ! $found && '' !== $phone ) {
			$n = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE contact = %s AND type = %s', $table, $phone, 'wa' )
			);
			if ( $n > 0 ) {
				$found = true;
			}
		}
		return $found;
	}

	/**
	 * Append a send-log entry.
	 *
	 * @param int    $contact_id Contact id.
	 * @param string $channel    Channel.
	 * @param string $kind       Message kind.
	 * @param string $status     dry-run|sent|failed|opted_out.
	 * @param string $message    Rendered message body.
	 */
	public static function log_send( $contact_id, $channel, $kind, $status, $message ) {
		global $wpdb;
		$table = self::table( self::TABLE_LOG );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$table,
			array(
				'contact_id'   => (int) $contact_id,
				'channel'      => $channel,
				'kind'         => $kind,
				'status'       => $status,
				'message_text' => substr( (string) $message, 0, 4000 ),
				'created_at'   => current_time( 'mysql' ),
			)
		);
	}
}
