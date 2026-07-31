<?php

namespace KingJamesSEO\Core;

defined('ABSPATH') || exit;

class Database {
    private const TABLES = [
        'kjs_audit_log' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            module varchar(64) NOT NULL DEFAULT '',
            action varchar(64) NOT NULL DEFAULT '',
            summary text,
            reasoning text,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            post_id bigint(20) unsigned NOT NULL DEFAULT 0,
            meta longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY module (module),
            KEY action (action),
            KEY created_at (created_at)
        ",
        'kjs_pipeline_runs' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL DEFAULT '',
            status varchar(32) NOT NULL DEFAULT 'pending',
            current_phase varchar(64) NOT NULL DEFAULT '',
            module_config longtext,
            checkpoint_data longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status)
        ",
        'kjs_shared_memory' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            namespace varchar(64) NOT NULL DEFAULT '',
            mem_key varchar(255) NOT NULL DEFAULT '',
            mem_value longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY ns_key (namespace, mem_key)
        ",
        'kjs_content_scores' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            total_score decimal(8,2) NOT NULL DEFAULT 0.00,
            category_scores longtext,
            passed_threshold tinyint(1) NOT NULL DEFAULT 0,
            scored_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY scored_at (scored_at)
        ",
        'kjs_schema_data' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            schema_type varchar(64) NOT NULL DEFAULT '',
            schema_json longtext NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY schema_type (schema_type)
        ",
        'kjs_seo_titles' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            seo_title varchar(255) NOT NULL DEFAULT '',
            h1_title varchar(255) NOT NULL DEFAULT '',
            slug varchar(255) NOT NULL DEFAULT '',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ",
        'kjs_meta_descriptions' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            meta_description varchar(255) NOT NULL DEFAULT '',
            cta varchar(64) NOT NULL DEFAULT '',
            variant varchar(32) NOT NULL DEFAULT 'a',
            impressions bigint(20) unsigned NOT NULL DEFAULT 0,
            clicks bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ",
        'kjs_ymyl_audits' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            topic_type varchar(64) NOT NULL DEFAULT '',
            grade varchar(8) NOT NULL DEFAULT '',
            findings longtext,
            passed tinyint(1) NOT NULL DEFAULT 0,
            reviewed_by bigint(20) unsigned NOT NULL DEFAULT 0,
            reviewed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY passed (passed)
        ",
        'kjs_content_calendar' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL DEFAULT '',
            module varchar(64) NOT NULL DEFAULT '',
            post_id bigint(20) unsigned NOT NULL DEFAULT 0,
            scheduled_date datetime DEFAULT NULL,
            status varchar(32) NOT NULL DEFAULT 'draft',
            cluster varchar(128) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY scheduled_date (scheduled_date),
            KEY status (status),
            KEY cluster (cluster)
        ",
        'kjs_api_keys' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(128) NOT NULL DEFAULT '',
            api_key varchar(64) NOT NULL,
            permissions longtext,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            last_used datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY api_key (api_key)
        ",
        'kjs_gscc_audits' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            compliance_score int(4) NOT NULL DEFAULT 0,
            issues_found int(4) NOT NULL DEFAULT 0,
            word_count int(8) NOT NULL DEFAULT 0,
            check_results longtext,
            audited_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            KEY compliance_score (compliance_score),
            KEY audited_at (audited_at)
        ",
        'kjs_gscc_recommendations' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            issue_type varchar(64) NOT NULL DEFAULT '',
            severity varchar(16) NOT NULL DEFAULT 'warning',
            description text,
            recommendation text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY issue_type (issue_type),
            KEY severity (severity)
        ",
        'kjs_rewrite_queue' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL DEFAULT 0,
            priority tinyint(4) NOT NULL DEFAULT 3,
            instructions text,
            result_content longtext,
            result_log text,
            status varchar(32) NOT NULL DEFAULT 'queued',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY priority (priority)
        ",
        'kjs_autoblog_log' => "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            action varchar(64) NOT NULL DEFAULT '',
            post_id bigint(20) unsigned NOT NULL DEFAULT 0,
            topic varchar(255) NOT NULL DEFAULT '',
            provider varchar(32) NOT NULL DEFAULT '',
            model varchar(64) NOT NULL DEFAULT '',
            tokens_in int(11) NOT NULL DEFAULT 0,
            tokens_out int(11) NOT NULL DEFAULT 0,
            duration_ms int(11) NOT NULL DEFAULT 0,
            status varchar(32) NOT NULL DEFAULT 'success',
            message text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY action (action),
            KEY status (status),
            KEY created_at (created_at)
        ",
    ];

    public function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach (self::TABLES as $table_name => $columns) {
            $full_name = $wpdb->prefix . $table_name;
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $full_name ($columns) $charset_collate;";
            dbDelta($sql);
        }
    }

    public function table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . $name;
    }
}
