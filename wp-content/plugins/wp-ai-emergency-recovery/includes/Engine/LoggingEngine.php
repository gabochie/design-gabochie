<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\Engine;

final class LoggingEngine {
    public function log(string $category, string $action, string $details, array $metadata = []): void {
        global $wpdb;

        $table = $wpdb->prefix . 'ai_recovery_audit_logs';

        $wpdb->insert(
            $table,
            [
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'action' => sanitize_text_field($action),
                'category' => sanitize_text_field($category),
                'status' => 'SUCCESS',
                'details' => sanitize_textarea_field($details),
                'metadata' => wp_json_encode($metadata)
            ]
        );
    }
}
