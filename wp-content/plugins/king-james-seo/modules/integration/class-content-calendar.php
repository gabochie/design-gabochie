<?php

namespace KingJamesSEO\Modules\Integration;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Content_Calendar implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'content-calendar';
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'add_calendar_page'], 25);
    }

    public function add_calendar_page(): void {
        add_submenu_page(
            'king-james-seo',
            __('Content Calendar', 'king-james-seo'),
            __('Calendar', 'king-james-seo'),
            'manage_options',
            'kjs-calendar',
            [$this, 'render_calendar']
        );
    }

    public function render_calendar(): void {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_content_calendar');
        $items = $wpdb->get_results("SELECT * FROM {$table} ORDER BY scheduled_date ASC LIMIT 100");
        include KJS_PLUGIN_DIR . 'admin/views/calendar.php';
    }

    public function schedule_item(array $data): int {
        global $wpdb;
        $wpdb->insert(
            Plugin::instance()->database()->table('kjs_content_calendar'),
            [
                'title'          => $data['title'],
                'module'         => $data['module'] ?? '',
                'post_id'        => $data['post_id'] ?? 0,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'status'         => $data['status'] ?? 'draft',
                'cluster'        => $data['cluster'] ?? '',
            ]
        );
        return (int) $wpdb->insert_id;
    }

    public function update_status(int $item_id, string $status): void {
        global $wpdb;
        $wpdb->update(
            Plugin::instance()->database()->table('kjs_content_calendar'),
            ['status' => $status],
            ['id' => $item_id]
        );
    }
}
