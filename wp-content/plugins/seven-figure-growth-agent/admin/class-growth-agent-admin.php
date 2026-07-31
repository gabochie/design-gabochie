<?php
class Growth_Agent_Admin {
    public function add_plugin_admin_menu() {
        add_menu_page(
            '7-Figure Growth AI',
            'AI Growth Agent',
            'manage_options',
            'growth-agent-dashboard',
            array($this, 'display_dashboard_page'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page('growth-agent-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'growth-agent-dashboard', array($this, 'display_dashboard_page'));
        add_submenu_page('growth-agent-dashboard', 'Website Intelligence', 'Website Scan', 'manage_options', 'growth-agent-scan', array($this, 'display_scan_page'));
        add_submenu_page('growth-agent-dashboard', 'Revenue Blueprint', 'Revenue Blueprint', 'manage_options', 'growth-agent-blueprint', array($this, 'display_blueprint_page'));
        add_submenu_page('growth-agent-dashboard', 'Daily Actions', 'Daily Actions', 'manage_options', 'growth-agent-actions', array($this, 'display_actions_page'));
        add_submenu_page('growth-agent-dashboard', 'Reports', 'Reports', 'manage_options', 'growth-agent-reports', array($this, 'display_reports_page'));
        add_submenu_page('growth-agent-dashboard', 'Settings', 'Settings', 'manage_options', 'growth-agent-settings', array($this, 'display_settings_page'));
    }

    public function enqueue_styles_and_scripts($hook) {
        if (strpos($hook, 'growth-agent') === false) return;

        wp_enqueue_style('growth-agent-admin-css', GROWTH_AGENT_URL . 'admin/css/admin.css', array(), GROWTH_AGENT_VERSION);
        wp_enqueue_script('growth-agent-admin-js', GROWTH_AGENT_URL . 'admin/js/admin.js', array('jquery'), GROWTH_AGENT_VERSION, true);

        wp_localize_script('growth-agent-admin-js', 'growthAgentData', array(
            'apiUrl' => esc_url_raw(rest_url('growth-agent/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'siteUrl' => get_site_url()
        ));
    }

    public function display_dashboard_page() {
        include_once GROWTH_AGENT_PATH . 'admin/views/dashboard.php';
    }

    public function display_scan_page() {
        include_once GROWTH_AGENT_PATH . 'admin/views/scan.php';
    }

    public function display_blueprint_page() {
        include_once GROWTH_AGENT_PATH . 'admin/views/blueprint.php';
    }

    public function display_actions_page() {
        include_once GROWTH_AGENT_PATH . 'admin/views/actions.php';
    }

    public function display_reports_page() {
        include_once GROWTH_AGENT_PATH . 'admin/views/reports.php';
    }

    public function display_settings_page() {
        include_once GROWTH_AGENT_PATH . 'admin/views/settings.php';
    }
}
