<?php

namespace KingJamesSEO\Modules\Quality;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Content_Scoring_Dashboard implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'content-scoring-dashboard';
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'add_dashboard_page'], 20);
    }

    public function add_dashboard_page(): void {
        add_submenu_page(
            'king-james-seo',
            __('Content Scores', 'king-james-seo'),
            __('Content Scores', 'king-james-seo'),
            'manage_options',
            'kjs-content-scores',
            [$this, 'render_dashboard']
        );
    }

    public function render_dashboard(): void {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_content_scores');

        $scores = $wpdb->get_results(
            "SELECT cs.*, p.post_title, p.post_date
             FROM {$table} cs
             LEFT JOIN {$wpdb->posts} p ON cs.post_id = p.ID
             ORDER BY cs.scored_at DESC
             LIMIT 50"
        );

        include KJS_PLUGIN_DIR . 'admin/views/scores.php';
    }

    public function get_scores_for_post(int $post_id): array {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_content_scores');
        return (array) $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d ORDER BY scored_at DESC", $post_id),
            ARRAY_A
        );
    }
}
