<?php
/**
 * Plugin Name:  Gabochie Authority Builder
 * Plugin URI:   https://gabochiedesign.com
 * Description:  Generates and maintains author-bio pages, credential blocks, and real project case studies tied to your real architecture background (BSc Architecture, KNUST).
 * Version:      1.0.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://gabochiedesign.com
 * Text Domain:  gabochie-authority-builder
 */

defined('ABSPATH') || exit;

define('GABUILD_VERSION', '1.0.0');
define('GABUILD_PATH', plugin_dir_path(__FILE__));
define('GABUILD_URL', plugin_dir_url(__FILE__));

require_once GABUILD_PATH . 'includes/class-author.php';
require_once GABUILD_PATH . 'includes/class-case-studies.php';
require_once GABUILD_PATH . 'includes/class-admin.php';

register_activation_hook(__FILE__, function () {
    GAB_Author::instance()->create_default_author();
    GAB_Case_Studies::instance()->seed_example();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
