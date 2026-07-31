<?php

namespace KingJamesSEO\Admin;

use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu'], 5);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('plugin_action_links_' . KJS_PLUGIN_BASENAME, [$this, 'action_links']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings(): void {
        register_setting('kjs_settings', 'kjs_ai_settings', [
            'type' => 'array',
            'sanitize_callback' => function($input) {
                if (!is_array($input)) return [];
                $allowed = ['provider', 'openai_api_key', 'openai_model', 'anthropic_api_key', 'anthropic_model', 'gemini_api_key', 'gemini_model'];
                $sanitized = [];
                foreach ($allowed as $key) {
                    if (isset($input[$key])) {
                        $sanitized[$key] = sanitize_text_field($input[$key]);
                    }
                }
                return $sanitized;
            },
            'default' => [],
        ]);
    }

    public function add_menu(): void {
        add_menu_page(
            esc_html__('King James SEO', 'king-james-seo'),
            esc_html__('King James SEO', 'king-james-seo'),
            'manage_options',
            'king-james-seo',
            [$this, 'render_dashboard'],
            'dashicons-admin-generic',
            2
        );

        add_submenu_page(
            'king-james-seo',
            esc_html__('Dashboard', 'king-james-seo'),
            esc_html__('Dashboard', 'king-james-seo'),
            'manage_options',
            'king-james-seo',
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'king-james-seo',
            esc_html__('Pipelines', 'king-james-seo'),
            esc_html__('Pipelines', 'king-james-seo'),
            'manage_options',
            'king-james-seo-pipelines',
            [$this, 'render_pipelines']
        );

        add_submenu_page(
            'king-james-seo',
            esc_html__('Settings', 'king-james-seo'),
            esc_html__('Settings', 'king-james-seo'),
            'manage_options',
            'king-james-seo-settings',
            [$this, 'render_settings']
        );

        add_submenu_page(
            'king-james-seo',
            esc_html__('Audit Log', 'king-james-seo'),
            esc_html__('Audit Log', 'king-james-seo'),
            'manage_options',
            'king-james-seo-log',
            [$this, 'render_log']
        );

        add_submenu_page(
            'king-james-seo',
            esc_html__('Scaled Content Compliance', 'king-james-seo'),
            esc_html__('Scaled Content', 'king-james-seo'),
            'manage_options',
            'king-james-seo-gscc',
            [$this, 'render_gscc']
        );
    }

    public function enqueue_assets(string $hook): void {
        if (strpos($hook, 'king-james-seo') === false) {
            return;
        }
        wp_enqueue_style('kjs-admin', KJS_PLUGIN_URL . 'assets/css/admin.css', [], KJS_VERSION);
        wp_enqueue_script('kjs-admin', KJS_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], KJS_VERSION, true);
        wp_localize_script('kjs-admin', 'kjsAdmin', [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('kjs_admin_nonce'),
        ]);
    }

    public function action_links(array $links): array {
        $links[] = '<a href="' . admin_url('admin.php?page=king-james-seo') . '">' . esc_html__('Dashboard', 'king-james-seo') . '</a>';
        $links[] = '<a href="' . admin_url('admin.php?page=king-james-seo-settings') . '">' . esc_html__('Settings', 'king-james-seo') . '</a>';
        return $links;
    }

    public function render_dashboard(): void {
        $module_manager = Plugin::instance()->module_manager();
        $categories = $module_manager->categories();
        $guardrails = Plugin::instance()->guardrails();
        $pipeline = Plugin::instance()->pipeline();
        $active_runs = $pipeline->get_runs(['status' => 'in_progress']);
        $recent_logs = Plugin::instance()->logger()->get_logs(['limit' => 10]);
        include KJS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function render_pipelines(): void {
        $pipeline = Plugin::instance()->pipeline();
        $runs = $pipeline->get_runs(['limit' => 50]);
        include KJS_PLUGIN_DIR . 'admin/views/pipelines.php';
    }

    public function render_settings(): void {
        $module_manager = Plugin::instance()->module_manager();
        $guardrails = Plugin::instance()->guardrails();
        include KJS_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public function render_log(): void {
        $logs = Plugin::instance()->logger()->get_logs(['limit' => 100]);
        include KJS_PLUGIN_DIR . 'admin/views/log.php';
    }

    public function render_gscc(): void {
        $module = \KingJamesSEO\Modules\Quality\Google_Scaled_Content_Compliance::instance();
        $audit_action = sanitize_text_field($_GET['gscc_action'] ?? '');

        if ('run_audit' === $audit_action && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'kjs_gscc_run_audit')) {
            $results = $module->run_bulk_audit();
            $audit_complete = true;
        }

        global $wpdb;
        $audits = $wpdb->get_results("
            SELECT a.*, p.post_title, p.post_type
            FROM {$wpdb->prefix}kjs_gscc_audits a
            LEFT JOIN {$wpdb->posts} p ON a.post_id = p.ID
            ORDER BY a.compliance_score ASC
        ");

        $recommendations = $wpdb->get_results("
            SELECT r.*, p.post_title
            FROM {$wpdb->prefix}kjs_gscc_recommendations r
            LEFT JOIN {$wpdb->posts} p ON r.post_id = p.ID
            ORDER BY FIELD(r.severity, 'critical', 'warning') ASC, r.created_at DESC
            LIMIT 200
        ");

        $stats = [
            'total_audited' => count($audits),
            'avg_score'     => $audits ? array_sum(array_column($audits, 'compliance_score')) / count($audits) : 0,
            'critical'      => count(array_filter($audits, fn($a) => $a->compliance_score < 40)),
            'warning'       => count(array_filter($audits, fn($a) => $a->compliance_score >= 40 && $a->compliance_score < 70)),
            'passing'       => count(array_filter($audits, fn($a) => $a->compliance_score >= 70)),
        ];

        $total_recommendations = $recommendations ? count($recommendations) : 0;

        include KJS_PLUGIN_DIR . 'admin/views/scaled-content-compliance.php';
    }
}
