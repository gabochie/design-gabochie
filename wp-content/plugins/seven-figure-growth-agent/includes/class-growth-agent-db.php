<?php
class Growth_Agent_DB {
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_scans = $wpdb->prefix . 'growth_ai_scans';
        $sql_scans = "CREATE TABLE $table_scans (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            website_url varchar(255) NOT NULL,
            overall_score int(3) NOT NULL,
            scores_json longtext NOT NULL,
            audit_json longtext NOT NULL,
            scanned_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_scans);

        $table_reports = $wpdb->prefix . 'growth_ai_reports';
        $sql_reports = "CREATE TABLE $table_reports (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            report_type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            content_json longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_reports);

        $table_tasks = $wpdb->prefix . 'growth_ai_tasks';
        $sql_tasks = "CREATE TABLE $table_tasks (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            category varchar(50) NOT NULL,
            reasoning text NOT NULL,
            ice_score float NOT NULL,
            priority varchar(10) NOT NULL,
            status varchar(20) DEFAULT 'PENDING' NOT NULL,
            action_steps_json text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_tasks);

        $table_recs = $wpdb->prefix . 'growth_ai_recommendations';
        $sql_recs = "CREATE TABLE $table_recs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            problem text NOT NULL,
            revenue_impact varchar(50) NOT NULL,
            difficulty varchar(50) NOT NULL,
            roi_estimate varchar(50) NOT NULL,
            implementation_steps longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_recs);

        $table_memory = $wpdb->prefix . 'growth_ai_memory';
        $sql_memory = "CREATE TABLE $table_memory (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            memory_key varchar(100) NOT NULL,
            memory_value longtext NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY memory_key (memory_key)
        ) $charset_collate;";
        dbDelta($sql_memory);
    }
}
