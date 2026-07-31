<?php
/**
 * Plugin Name:  Gabochie Agentic SEO
 * Plugin URI:   https://gabochiedesign.com
 * Description:  AI-powered SEO plugin for Ghana AEC industry — content analysis, schema markup, keyphrase optimization, OG tags, and XML sitemaps. Integrates with Gabochie AI Engine for intelligent suggestions.
 * Version:      1.0.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://gabochiedesign.com
 * Text Domain:  gabochie-seo
 */

defined('ABSPATH') || exit;

define('GSEO_VERSION', '1.0.0');
define('GSEO_PATH', plugin_dir_path(__FILE__));
define('GSEO_URL', plugin_dir_url(__FILE__));

require_once GSEO_PATH . 'includes/class-meta.php';
require_once GSEO_PATH . 'includes/class-schema.php';
require_once GSEO_PATH . 'includes/class-analysis.php';
require_once GSEO_PATH . 'includes/class-admin.php';
