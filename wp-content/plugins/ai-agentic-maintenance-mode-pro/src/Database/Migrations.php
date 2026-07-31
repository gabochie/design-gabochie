<?php
namespace AIAgenticMaintenancePro\Database;

class Migrations {
    public static function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $table_leads = $wpdb->prefix . 'aimmp_leads';
        $sql_leads = "CREATE TABLE $table_leads (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(100) DEFAULT '',
            company varchar(255) DEFAULT '',
            budget varchar(100) DEFAULT '',
            service varchar(255) DEFAULT '',
            score int(11) DEFAULT 50,
            status varchar(50) DEFAULT 'new',
            captured_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_leads );
    }

    public static function deactivate() {
        // Option cleanups if requested
    }
}
