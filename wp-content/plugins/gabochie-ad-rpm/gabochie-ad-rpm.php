<?php
/**
 * Plugin Name:  Gabochie Ad RPM Optimizer
 * Plugin URI:   https://design.gabochie.com
 * Description:  A/B tests ad unit placement, count, and lazy-load timing against RPM and bounce-rate data.
 * Version:      1.0.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://design.gabochie.com
 * Text Domain:  gabochie-ad-rpm
 */

defined('ABSPATH') || exit;

define('GAR_VERSION', '1.0.0');
define('GAR_PATH', plugin_dir_path(__FILE__));
define('GAR_URL', plugin_dir_url(__FILE__));

require_once GAR_PATH . 'includes/class-tracker.php';
require_once GAR_PATH . 'includes/class-admin.php';

register_activation_hook(__FILE__, function () {
    if (!get_option('gar_placement_config')) {
        update_option('gar_placement_config', array(
            'positions' => array('after_title', 'after_content', 'sidebar'),
            'count' => 3,
            'lazy_load' => true,
        ), false);
    }
});
