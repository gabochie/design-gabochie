<?php
/**
 * Plugin Name: Ghana Building Code Assistant
 * Plugin URI: https://buildingcode.gh
 * Description: AI-powered assistant for GS 1207:2018 Ghana Building Code. Compliance checks, exam prep, and small building auto-checks.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * Author: GhBC
 * Text Domain: ghbc
 */

defined('ABSPATH') || exit;

define('GHBC_VERSION', '0.1.1');
define('GHBC_PATH', plugin_dir_path(__FILE__));
define('GHBC_URL', plugin_dir_url(__FILE__));

// Autoload includes
$includes = [
    'class-core.php',
    'class-llm.php',
    'class-search.php',
    'class-tools.php',
    'class-assistant.php',
    'class-billing.php',
    'class-shortcodes.php',
    'class-ajax.php',
];
foreach ($includes as $file) {
    require_once GHBC_PATH . 'includes/' . $file;
}

register_activation_hook(__FILE__, ['GHBC_Core', 'activate']);
register_deactivation_hook(__FILE__, ['GHBC_Core', 'deactivate']);

// WP-CLI command
if (defined('WP_CLI') && WP_CLI) {
    require_once GHBC_PATH . 'includes/class-wpcli.php';
    WP_CLI::add_command('ghbc', 'GHBC_WPCLI');
}

add_action('plugins_loaded', function () {
    GHBC_Core::instance();
    GHBC_LLM::instance();
    GHBC_Search::instance();
    GHBC_Tools::instance();
    GHBC_Assistant::instance();
    GHBC_Billing::instance();
    GHBC_Shortcodes::instance();
    GHBC_Ajax::instance();
});
