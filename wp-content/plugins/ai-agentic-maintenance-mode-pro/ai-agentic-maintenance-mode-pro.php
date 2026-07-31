<?php
/**
 * Plugin Name:       AI Agentic Maintenance Mode Pro
 * Description:       Enterprise-grade, AI-powered digital receptionist & maintenance mode suite for WordPress.
 * Version:           1.0.0
 * Author:            Apex Digital Solutions
 * Author URI:        https://apexdigital.example
 * Text Domain:       ai-agentic-maintenance-mode-pro
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AIMMP_VERSION', '1.0.0' );
define( 'AIMMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIMMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// PSR-4 Autoloader
spl_autoload_register( function( $class ) {
    $prefix = 'AIAgenticMaintenancePro\\';
    $base_dir = AIMMP_PLUGIN_DIR . 'src/';
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

register_activation_hook( __FILE__, array( 'AIAgenticMaintenancePro\\Database\\Migrations', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AIAgenticMaintenancePro\\Database\\Migrations', 'deactivate' ) );

add_action( 'plugins_loaded', function() {
    \AIAgenticMaintenancePro\Core\Plugin::get_instance()->run();
} );
