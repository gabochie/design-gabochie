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
        if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
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
            'sensitivity' => 'normal',
            'ai_enabled'  => 1,
            'ai_cap_hour' => 30,
            'honeypot'    => 1,
            'retention'   => 30,
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
        $table = self::table();
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

    public function prune() {
        global $wpdb;
        $settings = gabochie_antispam_settings();
        $days = max( 7, (int) ( $settings['retention'] ?? 30 ) );
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}gabochie_antispam WHERE created < %s",
                gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS )
            )
        );
    }

    public function recent( $limit = 100, $verdict = '' ) {
        global $wpdb;
        $table = self::table();
        $sql = "SELECT id, source, name, email, ip, score, verdict, reasons, ai, created, content, LENGTH(content) AS c_len
                FROM {$table}";
        if ( $verdict ) {
            $sql .= $wpdb->prepare( ' WHERE verdict = %s', $verdict );
        }
        $sql .= $wpdb->prepare( ' ORDER BY id DESC LIMIT %d', (int) $limit );
        $rows = $wpdb->get_results( $sql );
        foreach ( $rows as $row ) {
            $row->reasons = json_decode( (string) $row->reasons, true ) ?: array();
        }
        return $rows;
    }

    public function count_verdict( $verdict, $hours = null ) {
        global $wpdb;
        $table = self::table();
        $where = $wpdb->prepare( 'WHERE verdict = %s', $verdict );
        if ( $hours ) {
            $where .= $wpdb->prepare( ' AND created >= %s', gmdate( 'Y-m-d H:i:s', time() - (int) $hours * HOUR_IN_SECONDS ) );
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
    }

    public function stats( $hours = 24 ) {
        global $wpdb;
        $table = self::table();
        return array(
            'spam_24h'        => $this->count_verdict( 'spam', $hours ),
            'borderline_24h'  => $this->count_verdict( 'borderline', $hours ),
            'ham_24h'         => $this->count_verdict( 'ham', $hours ),
            'ai_24h'          => (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE ai = 1 AND created >= %s", gmdate( 'Y-m-d H:i:s', time() - $hours * HOUR_IN_SECONDS ) )
            ),
            'total'           => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
            'by_source'       => (array) $wpdb->get_results(
                "SELECT source, COUNT(*) AS n FROM {$table} GROUP BY source ORDER BY n DESC"
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