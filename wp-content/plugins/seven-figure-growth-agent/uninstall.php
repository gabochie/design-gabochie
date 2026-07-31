<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (!current_user_can('activate_plugins')) {
    exit;
}

global $wpdb;

delete_option('growth_agent_settings');
delete_option('growth_agent_db_version');
delete_option('growth_agent_active_profile');

$settings = get_option('growth_agent_settings', array());
if (!empty($settings['clean_db_on_uninstall'])) {
    $tables = array(
        $wpdb->prefix . 'growth_ai_scans',
        $wpdb->prefix . 'growth_ai_reports',
        $wpdb->prefix . 'growth_ai_tasks',
        $wpdb->prefix . 'growth_ai_recommendations',
        $wpdb->prefix . 'growth_ai_memory'
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS " . esc_sql($table));
    }
}

wp_clear_scheduled_hook('growth_agent_daily_cron');
wp_clear_scheduled_hook('growth_agent_weekly_cron');
