<?php
/**
 * Plugin Name: Enterprise AI WordPress Emergency Recovery
 * Plugin URI:  https://github.com/enterprise/wp-ai-emergency-recovery
 * Description: Mission-critical autonomous AI-powered WordPress emergency recovery plugin. Detects frontend crashes, analyzes root causes, produces evidence, and safely repairs broken sites with full rollback capabilities.
 * Version:     1.0.0
 * Author:      Enterprise SRE & AI Engineering Team
 * Author URI:  https://ai.studio/build
 * License:     GPL-2.0-or-later
 * Text Domain: wp-ai-emergency-recovery
 * Requires PHP: 8.3
 * Requires at least: 6.2
 */

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('WP_AI_RECOVERY_VERSION', '1.0.0');
define('WP_AI_RECOVERY_PATH', plugin_dir_path(__FILE__));
define('WP_AI_RECOVERY_URL', plugin_dir_url(__FILE__));
define('WP_AI_RECOVERY_INCIDENT_DB_VERSION', '1.0');

// Autoload composer dependencies if present
if (file_exists(WP_AI_RECOVERY_PATH . 'vendor/autoload.php')) {
    require_once WP_AI_RECOVERY_PATH . 'vendor/autoload.php';
} else {
    // Custom PSR-4 Fallback Autoloader
    spl_autoload_register(function (string $class): void {
        $prefix = 'WP_AI_Emergency_Recovery\\';
        $baseDir = WP_AI_RECOVERY_PATH . 'includes/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

/**
 * Register Activation & Deactivation Hooks
 */
register_activation_hook(__FILE__, [Core\Installer::class, 'activate']);
register_deactivation_hook(__FILE__, [Core\Installer::class, 'deactivate']);

/**
 * Initialize Plugin Core Instance
 */
add_action('plugins_loaded', function(): void {
    Core\Plugin::getInstance()->boot();
}, 0);
