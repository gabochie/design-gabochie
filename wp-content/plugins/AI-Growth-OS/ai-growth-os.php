<?php
/**
 * AI Growth OS — Autonomous AI Business Operating System for WordPress
 *
 * @wordpress-plugin
 * Plugin Name: AI Growth OS
 * Plugin URI:  https://ai-growt.os
 * Description: Convert any WordPress website into a self-improving AI business capable of continuously analysing, learning, planning and recommending growth actions.
 * Version:     0.1.0
 * Author:      AI Growth OS
 * Text Domain: ai-growth-os
 * Domain Path: /languages
 * Requires PHP: 8.2
 * Requires at least: 6.5
 */

declare(strict_types=1);

namespace AIGrowthOS;

use AIGrowthOS\Infrastructure\Security\PluginSecurity;
use AIGrowthOS\Infrastructure\Logging\Logger;
use AIGrowthOS\Presentation\Admin\DashboardController;

// Prevent direct access
defined('ABSPATH') || exit('Direct access not permitted.');

// Load autoloader
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>AI Growth OS requires Composer dependencies. Please run <code>composer install</code>.</p></div>';
    });
    return;
}
require_once $autoload;

// Define plugin constants
define('AI_GROWTH_OS_VERSION', '0.1.0');
define('AI_GROWTH_OS_FILE', __FILE__);
define('AI_GROWTH_OS_DIR', __DIR__);
define('AI_GROWTH_OS_URL', plugin_dir_url(__FILE__));

// Bootstrap the plugin
add_action('plugins_loaded', function () {
    Plugin::getInstance()->init();
});

register_activation_hook(__FILE__, [Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Deactivator::class, 'deactivate']);
