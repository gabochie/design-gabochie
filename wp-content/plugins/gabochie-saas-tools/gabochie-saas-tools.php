<?php
/**
 * Plugin Name:  Gabochie SaaS Tools
 * Plugin URI:   https://tools.gabochie.com
 * Description:  Production AI engine + SaaS platform — real LLM integration (Claude/GPT-4o), AI document generation, document vault, team management, subscription billing, usage tracking. Powers the tools ecosystem.
 * Version:      2.0.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://design.gabochie.com
 * Text Domain:  gabochie-saas-tools
 */

defined('ABSPATH') || exit;

define('GST_VERSION', '2.0.0');
define('GST_PATH', plugin_dir_path(__FILE__));
define('GST_URL', plugin_dir_url(__FILE__));

require_once GST_PATH . 'includes/class-ai-engine.php';
require_once GST_PATH . 'includes/tools.php';
require_once GST_PATH . 'includes/ajax.php';
require_once GST_PATH . 'includes/documents.php';
require_once GST_PATH . 'includes/team.php';
require_once GST_PATH . 'includes/billing.php';
require_once GST_PATH . 'includes/usage.php';
require_once GST_PATH . 'includes/payments.php';
require_once GST_PATH . 'includes/upgrades.php';
require_once GST_PATH . 'includes/cpt.php';
require_once GST_PATH . 'includes/admin.php';

// FluentCRM integration loads only when the plugin is present (dormant tail).
if (defined('FLUENTCRM') && function_exists('FluentCrmApi')) {
	require_once GST_PATH . 'includes/fluentcrm.php';
	require_once GST_PATH . 'includes/gs-fluent-sms.php';
}

function gst_activate() {
    gs_register_tool_cpt();
    gs_register_role();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'gst_activate');

function gst_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'gst_deactivate');
