<?php
/**
 * Plugin Name:  Gabochie Auto-Blog
 * Plugin URI:   https://gabochiedesign.com
 * Description:  AI-powered scheduled content generation for Ghana AEC industry. Auto-creates posts via AI Engine with customizable templates, categories, and schedules.
 * Version:      1.0.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://gabochiedesign.com
 * Text Domain:  gabochie-auto-blog
 */

defined('ABSPATH') || exit;

define('GAB_VERSION', '1.0.0');
define('GAB_PATH', plugin_dir_path(__FILE__));
define('GAB_URL', plugin_dir_url(__FILE__));

require_once GAB_PATH . 'includes/class-generator.php';
require_once GAB_PATH . 'includes/class-scheduler.php';
require_once GAB_PATH . 'includes/class-admin.php';

register_activation_hook(__FILE__, function () {
    $defaults = array(
        'gab_frequency' => 'daily',
        'gab_time'      => '06:00',
        'gab_category'  => 0,
        'gab_author'    => 1,
        'gab_status'    => 'draft',
        'gab_topics'    => "Ghana architecture trends\nSustainable building in Ghana\nDiaspora building tips\nGhana construction costs\nInterior design Ghana\nSmart home technology Ghana\nGhana real estate development\nBuilding regulations Ghana",
        'gab_enabled'   => '0',
    );
    foreach ($defaults as $key => $val) {
        if (!get_option($key)) {
            update_option($key, $val);
        }
    }
    if (!wp_next_scheduled('gab_generate_content')) {
        wp_schedule_event(strtotime('06:00:00'), 'daily', 'gab_generate_content');
    }
});

register_deactivation_hook(__FILE__, function () {
    $timestamp = wp_next_scheduled('gab_generate_content');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'gab_generate_content');
    }
});
