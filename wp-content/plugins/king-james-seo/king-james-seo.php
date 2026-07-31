<?php
/**
 * Plugin Name:       King James SEO
 * Plugin URI:        https://kingjamesseo.com
 * Description:       AI-powered modular SEO ecosystem — content generation, optimization, quality assurance, and workflow orchestration for the King James SEO methodology.
 * Version:           1.2.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            King James SEO
 * Author URI:        https://kingjamesseo.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       king-james-seo
 * Domain Path:       /languages
 */

defined('ABSPATH') || exit;

define('KJS_VERSION', '1.2.0');
define('KJS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KJS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KJS_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once KJS_PLUGIN_DIR . 'core/class-autoloader.php';

KingJamesSEO\Core\Autoloader::register();

register_activation_hook(__FILE__, [KingJamesSEO\Core\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [KingJamesSEO\Core\Plugin::class, 'deactivate']);

KingJamesSEO\Core\Plugin::instance();
