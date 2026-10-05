<?php
/**
 * Gabochie Anti-Spam — storage.
 *
 * Custome table (prefixed, e.g. wpor_gabochie_antispam on live) holding a
 * reviewable log of guarded submissions + verdict + evidence.
 *
 * @package Gabochie_Antispam
 */

defined( 'ABSPATH' ) || exit;

final class Gabochie_Antispam_DB {

	const DB_VERSION = '1.0.0';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'maybe_upgrade' ), 1 );
	}

	public function maybe_upgrade() {
		if ( is_admin() || ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) ) {
			$this->maybe_install();
		}
	}

	public static function activate() {
		self::install();
		if ( false === get_option( 'gabochie_antispam_settings' ) ) {
			update_option( 'gabochie_antispam_settings', self::default_settings() );
		}
	}

	public static function default_settings() {
		return array(
			'sensitivity'    => 'normal',
			'ai_enabled'     => 1,
			'ai_cap_hour'    => 30,
			'ai_cap_hour_ip' => 5,
			'honeypot'       => 1,
			'retention'      => 30,
			'log_cap_hour'   => 20,
		);
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . GABOCHIE_ANTISPAM_TABLE;
	}

	private function maybe_install() {
		$installed = get_option( 'gabochie_antispam_db_version' );
		if ( $installed !== self::DB_VERSION ) {
			self::install();
		}
	}

	private static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                source VARCHAR(40) NOT NULL DEFAULT 'unknown',
                name VARCHAR(255) NOT NULL DEFAULT '',
                email VARCHAR(191) NOT NULL DEFAULT '',
                ip VARCHAR(64) NOT NULL DEFAULT '',
                content LONGTEXT NULL,
                score SMALLINT NOT NULL DEFAULT 0,
                verdict VARCHAR(20) NOT NULL DEFAULT 'ham',
                reasons TEXT NULL,
                ai TINYINT(1) NOT NULL DEFAULT 0,
                created DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY source (source),
                KEY verdict (verdict),
                KEY created (created),
                KEY email (email)
            ) {$charset};"
		);
		update_option( 'gabochie_antispam_db_version', self::DB_VERSION );
	}

	/**
	 * @param array $entry source,name,email,ip,content,score,verdict,reasons(array)
	 * @return int row id
	 */
	public function log( $entry = array() ) {
		global $wpdb;
		if ( ! $this->table_exists() ) {
			self::install();
		}
		$wpdb->insert(
			self::table(),
			array(
				'source'  => sanitize_key( (string) ( $entry['source'] ?? 'unknown' ) ),
				'name'    => sanitize_text_field( (string) ( $entry['name'] ?? '' ) ),
				'email'   => sanitize_email( (string) ( $entry['email'] ?? '' ) ),
				'ip'      => sanitize_text_field( (string) ( $entry['ip'] ?? '' ) ),
				'content' => wp_kses_post( (string) ( $entry['content'] ?? '' ) ),
				'score'   => (int) ( $entry['score'] ?? 0 ),
				'verdict' => sanitize_key( (string) ( $entry['verdict'] ?? 'ham' ) ),
				'reasons' => wp_json_encode( (array) ( $entry['reasons'] ?? array() ) ),
				'ai'      => empty( $entry['ai'] ) ? 0 : 1,
				'created' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	public function update_verdict( $id, $verdict ) {
		global $wpdb;
		return $wpdb->update(
			self::table(),
			array( 'verdict' => sanitize_key( $verdict ) ),
			array( 'id' => (int) $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) );
	}

	/**
	 * Count rows logged from one IP inside a time window.
	 *
	 * Used as a storage-abuse guard: these rows are written from
	 * unauthenticated endpoints, so the volume per IP has to be bounded.
	 *
	 * @param string $ip IP address.
	 * @param int $hours Window size in hours.
	 * @return int
	 */
	public function count_recent_by_ip( $ip, $hours = 1 ) {
		global $wpdb;

		$ip = (string) $ip;
		if ( '' === $ip ) {
			return 0;
		}

		$table = self::table();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE ip = %s AND created >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$ip,
				gmdate( 'Y-m-d H:i:s', time() - (int) $hours * HOUR_IN_SECONDS )
			)
		);
	}

	/**
	 * Delete log rows older than the configured retention window.
	 *
	 * Invoked from WP-Cron (gabochie_antispam_prune, daily) as a static
	 * callable, so it must stay static: registering a non-static method that
	 * way is a TypeError on PHP 8, which fatals the cron request and leaves
	 * the log growing forever with submitters' emails and IPs in it.
	 *
	 * @return int|false Rows deleted, or false if the table is not installed.
	 */
	public static function prune() {
		global $wpdb;

		if ( ! self::instance()->table_exists() ) {
			return false;
		}

		$settings = gabochie_antispam_settings();
		$days     = max( 7, (int) ( $settings['retention'] ?? 30 ) );
		$table    = self::table();

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS )
			)
		);
	}

	public function recent( $limit = 100, $verdict = '' ) {
		global $wpdb;
		$table = self::table();
		$sql   = $wpdb->prepare(
			'SELECT id, source, name, email, ip, score, verdict, reasons, ai, created, content, LENGTH(content) AS c_len
                FROM %i',
			$table
		);
		if ( $verdict ) {
			$sql .= $wpdb->prepare( ' WHERE verdict = %s', $verdict );
		}
		$sql .= $wpdb->prepare( ' ORDER BY id DESC LIMIT %d', (int) $limit );
		// $sql is fully prepared above; table via %i, every value via prepare().
		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as $row ) {
			$decoded      = json_decode( (string) $row->reasons, true );
			$row->reasons = is_array( $decoded ) ? $decoded : array();
		}
		return $rows;
	}

	public function count_verdict( $verdict, $hours = null ) {
		global $wpdb;
		$table = self::table();
		if ( $hours ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE verdict = %s AND created >= %s',
					$table,
					$verdict,
					gmdate( 'Y-m-d H:i:s', time() - (int) $hours * HOUR_IN_SECONDS )
				)
			);
		}
		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE verdict = %s', $table, $verdict )
		);
	}

	public function stats( $hours = 24 ) {
		global $wpdb;
		$table = self::table();
		return array(
			'spam_24h'       => $this->count_verdict( 'spam', $hours ),
			'borderline_24h' => $this->count_verdict( 'borderline', $hours ),
			'ham_24h'        => $this->count_verdict( 'ham', $hours ),
			'ai_24h'         => (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE ai = 1 AND created >= %s',
					$table,
					gmdate( 'Y-m-d H:i:s', time() - (int) $hours * HOUR_IN_SECONDS )
				)
			),
			'total'          => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) ),
			'by_source'      => (array) $wpdb->get_results(
				$wpdb->prepare( 'SELECT source, COUNT(*) AS n FROM %i GROUP BY source ORDER BY n DESC', $table )
			),
		);
	}

	public function table_exists() {
		global $wpdb;
		$table = self::table();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}
}

Gabochie_Antispam_DB::instance();
