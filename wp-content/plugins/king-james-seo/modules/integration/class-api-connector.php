<?php

namespace KingJamesSEO\Modules\Integration;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class API_Connector implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'api-connector';
    }

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('king-james-seo/v1', '/modules', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_modules'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);

        register_rest_route('king-james-seo/v1', '/pipeline', [
            'methods'             => 'POST',
            'callback'            => [$this, 'create_pipeline'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);

        register_rest_route('king-james-seo/v1', '/scores/(?P<post_id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_scores'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);
    }

    public function check_api_key(\WP_REST_Request $request): bool {
        $key = $request->get_header('X-KJS-API-Key');
        if (!$key) {
            return current_user_can('manage_options');
        }

        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_api_keys');
        $stored = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE api_key = %s AND is_active = 1",
            $key
        ));

        if ($stored) {
            $wpdb->update($table, ['last_used' => current_time('mysql')], ['id' => $stored]);
            return true;
        }

        return false;
    }

    public function get_modules(): array {
        return Plugin::instance()->module_manager()->all();
    }

    public function create_pipeline(\WP_REST_Request $request): array {
        $name = $request->get_param('name') ?: 'API Pipeline';
        $config = $request->get_param('config') ?: [];
        $run_id = Plugin::instance()->pipeline()->create_run($name, $config);
        return ['run_id' => $run_id, 'status' => 'created'];
    }

    public function get_scores(\WP_REST_Request $request): array {
        $post_id = (int) $request->get_param('post_id');
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_content_scores');
        return (array) $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d ORDER BY scored_at DESC", $post_id),
            ARRAY_A
        );
    }
}
