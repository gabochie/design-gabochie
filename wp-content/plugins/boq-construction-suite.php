<?php
/**
 * Plugin Name: BuildSuite Pro - Construction & BOQ Suite
 * Plugin URI:  https://example.com/buildsuite-pro
 * Description: An all-in-one WordPress-native suite for Construction BOQ Estimating, Architect/QS Invoicing, Real Estate JV Modeling, and Rate History Search.
 * Version:     1.0.0
 * Author:      Antigravity Deepmind & Quantity Surveying Solutions
 * Text Domain: boq-construction-suite
 * Domain Path: /languages
 * License:     GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('BOQ_SUITE_VERSION', '1.0.0');
define('BOQ_SUITE_FILE', __FILE__);
define('BOQ_SUITE_PATH', plugin_dir_path(__FILE__));
define('BOQ_SUITE_URL', plugin_dir_url(__FILE__));

// Require Core Loader
require_once BOQ_SUITE_PATH . 'includes/class-boq-suite-loader.php';
require_once BOQ_SUITE_PATH . 'includes/class-boq-suite-cpt.php';
require_once BOQ_SUITE_PATH . 'includes/class-boq-suite-rest-api.php';
require_once BOQ_SUITE_PATH . 'includes/class-boq-suite-pdf.php';

// Require Modules
require_once BOQ_SUITE_PATH . 'includes/modules/class-module-boq-estimator.php';
require_once BOQ_SUITE_PATH . 'includes/modules/class-module-qs-invoicing.php';
require_once BOQ_SUITE_PATH . 'includes/modules/class-module-jv-calculator.php';
require_once BOQ_SUITE_PATH . 'includes/modules/class-module-rate-search.php';
require_once BOQ_SUITE_PATH . 'includes/modules/class-module-boq-importer.php';

// Require Admin & Public Interfaces
require_once BOQ_SUITE_PATH . 'admin/class-boq-suite-admin.php';
require_once BOQ_SUITE_PATH . 'public/class-boq-suite-public.php';

/**
 * Activation Hook: Seed default data and set default options.
 */
function boq_suite_activate() {
    BOQ_Suite_CPT::register_cpts();
    BOQ_Module_BOQ_Estimator::seed_default_rates();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'boq_suite_activate');

/**
 * Deactivation Hook.
 */
function boq_suite_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'boq_suite_deactivate');

/**
 * Run the Plugin.
 */
function run_boq_construction_suite() {
    $plugin = new BOQ_Suite_Loader();
    $plugin->run();
}
run_boq_construction_suite();
