<?php

namespace KingJamesSEO\Core;

defined('ABSPATH') || exit;

class Logger {
    public function log(string $module, string $action, string $summary = '', string $reasoning = '', array $meta = []): void {
        global $wpdb;

        $wpdb->insert(
            Plugin::instance()->database()->table('kjs_audit_log'),
            [
                'module'    => $module,
                'action'    => $action,
                'summary'   => $summary,
                'reasoning' => $reasoning,
                'user_id'   => get_current_user_id(),
                'post_id'   => $meta['post_id'] ?? 0,
                'meta'      => !empty($meta) ? maybe_serialize($meta) : '',
            ]
        );
    }

    public function get_logs(array $args = []): array {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_audit_log');
        $where = ['1=1'];
        $params = [];

        if (!empty($args['module'])) {
            $where[] = 'module = %s';
            $params[] = $args['module'];
        }
        if (!empty($args['action'])) {
            $where[] = 'action = %s';
            $params[] = $args['action'];
        }
        if (!empty($args['post_id'])) {
            $where[] = 'post_id = %d';
            $params[] = (int) $args['post_id'];
        }
        if (!empty($args['since'])) {
            $where[] = 'created_at >= %s';
            $params[] = $args['since'];
        }

        $limit = min(abs($args['limit'] ?? 50), 500);
        $offset = max(0, (int) ($args['offset'] ?? 0));

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        return (array) $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }
}
