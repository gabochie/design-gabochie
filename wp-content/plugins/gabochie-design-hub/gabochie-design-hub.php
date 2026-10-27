<?php
/**
 * Plugin Name:  Gabochie Design Hub
 * Plugin URI:   https://design.gabochie.com
 * Description:  Command center for the Gabochie Design platform build. Smart launch checklist that auto-detects what is configured and what still needs a human hand.
 * Version:      1.0.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://design.gabochie.com
 * Text Domain:  gabochie-design-hub
 */

defined('ABSPATH') || exit;

define('GDH_VERSION', '1.0.0');
define('GDH_PATH', plugin_dir_path(__FILE__));
define('GDH_URL', plugin_dir_url(__FILE__));

require_once GDH_PATH . 'includes/class-hub.php';

register_activation_hook(__FILE__, function () {
    if (!get_option('gdh_overrides')) {
        add_option('gdh_overrides', array(), '', false);
    }
});
