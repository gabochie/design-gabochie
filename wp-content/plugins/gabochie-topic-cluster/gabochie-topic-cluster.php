<?php
/**
 * Plugin Name:  Gabochie Topic Cluster Map
 * Plugin URI:   https://design.gabochie.com
 * Description:  Internal-linking automation built around topical-authority silos. Maps every published page against AEC/property/tiling/ecosystem segments, flags orphaned pages, and suggests contextual internal links.
 * Version:      1.0.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://design.gabochie.com
 * Text Domain:  gabochie-topic-cluster
 */

defined('ABSPATH') || exit;

define('GTC_VERSION', '1.0.0');
define('GTC_PATH', plugin_dir_path(__FILE__));
define('GTC_URL', plugin_dir_url(__FILE__));

require_once GTC_PATH . 'includes/class-segments.php';
require_once GTC_PATH . 'includes/class-linker.php';
require_once GTC_PATH . 'includes/class-audit.php';
require_once GTC_PATH . 'includes/class-admin.php';

register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
