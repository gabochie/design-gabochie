<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\Core;

final class Installer {
    public static function activate(): void {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        // Create Encrypted Audit Log Table
        $auditTable = $wpdb->prefix . 'ai_recovery_audit_logs';
        $sqlAudit = "CREATE TABLE IF NOT EXISTS {$auditTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL,
            details TEXT NOT NULL,
            metadata LONGTEXT NULL,
            PRIMARY KEY  (id),
            KEY category_idx (category),
            KEY status_idx (status)
        ) {$charsetCollate};";

        // Create Snapshots Metadata Table
        $snapshotTable = $wpdb->prefix . 'ai_recovery_snapshots';
        $sqlSnapshot = "CREATE TABLE IF NOT EXISTS {$snapshotTable} (
            id VARCHAR(64) NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            php_version VARCHAR(20) NOT NULL,
            wp_version VARCHAR(20) NOT NULL,
            snapshot_data LONGTEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'VALID' NOT NULL,
            PRIMARY KEY  (id)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sqlAudit);
        dbDelta($sqlSnapshot);

        update_option('wp_ai_recovery_db_version', WP_AI_RECOVERY_INCIDENT_DB_VERSION);
    }

    public static function deactivate(): void {
        // Safe cleanup options if configured
    }
}
