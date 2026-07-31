<?php
/**
 * Plugin Name:  Gabochie Content Pipeline
 * Plugin URI:   https://gabochiedesign.com
 * Description:  AI-assisted drafting pipeline with mandatory human editorial gate. Takes topics/keywords and produces structured first drafts using Ghana AEC data. Every draft lands in a "Needs Review" queue — nothing auto-publishes.
 * Version:      1.0.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://gabochiedesign.com
 * Text Domain:  gabochie-content-pipeline
 */

defined('ABSPATH') || exit;

define('GCP_VERSION', '1.0.0');
define('GCP_PATH', plugin_dir_path(__FILE__));
define('GCP_URL', plugin_dir_url(__FILE__));

require_once GCP_PATH . 'includes/class-data.php';
require_once GCP_PATH . 'includes/class-generator.php';
require_once GCP_PATH . 'includes/class-queue.php';
require_once GCP_PATH . 'includes/class-admin.php';

register_activation_hook(__FILE__, function () {
    GCP_Queue::register_status();
    flush_rewrite_rules();
    if (!get_option('gcp_ae_data')) {
        update_option('gcp_ae_data', GCP_Data::get_defaults(), false);
    }
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
