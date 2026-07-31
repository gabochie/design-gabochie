<?php

namespace KingJamesSEO\Core;

defined('ABSPATH') || exit;

class Shared_Memory {
    public static function set(string $namespace, string $key, $value): void {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_shared_memory');

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE namespace = %s AND mem_key = %s",
            $namespace,
            $key
        ));

        if ($existing) {
            $wpdb->update(
                $table,
                ['mem_value' => maybe_serialize($value)],
                ['id' => $existing]
            );
        } else {
            $wpdb->insert(
                $table,
                [
                    'namespace' => $namespace,
                    'mem_key'   => $key,
                    'mem_value' => maybe_serialize($value),
                ]
            );
        }
    }

    public static function get(string $namespace, string $key, $default = null) {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_shared_memory');

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT mem_value FROM $table WHERE namespace = %s AND mem_key = %s",
            $namespace,
            $key
        ));

        return null !== $value ? maybe_unserialize($value) : $default;
    }

    public static function get_namespace(string $namespace): array {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_shared_memory');

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT mem_key, mem_value FROM $table WHERE namespace = %s",
            $namespace
        ), ARRAY_A);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['mem_key']] = maybe_unserialize($row['mem_value']);
        }
        return $result;
    }

    public static function delete(string $namespace, string $key): void {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_shared_memory');
        $wpdb->delete($table, ['namespace' => $namespace, 'mem_key' => $key]);
    }

    public static function clear_namespace(string $namespace): void {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_shared_memory');
        $wpdb->delete($table, ['namespace' => $namespace]);
    }
}
