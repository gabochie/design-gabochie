<?php
/**
 * Plugin Name:  Gabochie Content Refresh
 * Plugin URI:   https://gabochiedesign.com
 * Description:  Watches published pages for ranking or traffic decay and queues them for an update pass before they fall further.
 * Version:      1.0.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://gabochiedesign.com
 * Text Domain:  gabochie-content-refresh
 */

defined('ABSPATH') || exit;

define('GCR_VERSION', '1.0.0');
define('GCR_PATH', plugin_dir_path(__FILE__));
define('GCR_URL', plugin_dir_url(__FILE__));

require_once GCR_PATH . 'includes/class-monitor.php';
require_once GCR_PATH . 'includes/class-admin.php';

register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('gcr_weekly_scan')) {
        wp_schedule_event(time(), 'gcr_weekly', 'gcr_weekly_scan');
    }
});

register_deactivation_hook(__FILE__, function () {
    $timestamp = wp_next_scheduled('gcr_weekly_scan');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'gcr_weekly_scan');
    }
});
