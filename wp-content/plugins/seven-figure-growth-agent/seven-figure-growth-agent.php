<?php
/**
 * Plugin Name:       7-Figure Growth Intelligence Agent
 * Plugin URI:        https://growthagent.ai/wordpress
 * Description:       Autonomous AI-powered business growth operating system that audits, plans, prioritizes, and executes website improvements to achieve 7-figure revenue targets.
 * Version:           1.0.0
 * Author:            7-Figure Growth AI Inc.
 * Author URI:        https://growthagent.ai
 * License:           GPL-2.0+
 * Text Domain:       seven-figure-growth-agent
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GROWTH_AGENT_VERSION', '1.0.0');
define('GROWTH_AGENT_DB_VERSION', '1.0.0');
define('GROWTH_AGENT_PATH', plugin_dir_path(__FILE__));
define('GROWTH_AGENT_URL', plugin_dir_url(__FILE__));
define('GROWTH_AGENT_BASENAME', plugin_basename(__FILE__));

require_once GROWTH_AGENT_PATH . 'includes/class-growth-agent-core.php';

function run_seven_figure_growth_agent() {
    $plugin = Growth_Agent_Core::get_instance();
    $plugin->run();
}

register_activation_hook(__FILE__, array('Growth_Agent_Core', 'activate'));
register_deactivation_hook(__FILE__, array('Growth_Agent_Core', 'deactivate'));

run_seven_figure_growth_agent();
