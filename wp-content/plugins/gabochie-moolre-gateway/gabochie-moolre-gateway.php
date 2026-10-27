<?php
/**
 * Plugin Name: Gabochie Moolre Gateway for PMPro
 * Plugin URI:  https://design.gabochie.com
 * Description: PMPro payment gateway for Moolre (MTN MoMo). Redirects to Moolre's hosted checkout for mobile money payments, verifies via callback.
 * Version:     1.1.0
 * Author:      Gabochie Design
 * License:     GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: gabochie-moolre
 */

defined( 'ABSPATH' ) || exit;

define( 'GMG_VERSION', '1.1.0' );
define( 'GMG_PATH', plugin_dir_path( __FILE__ ) );
define( 'GMG_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load gateway class only after PMPro is available.
 */
add_action( 'plugins_loaded', 'gmg_load_gateway', 5 );
function gmg_load_gateway() {
    if ( ! class_exists( 'PMProGateway' ) ) {
        return;
    }
    require_once GMG_PATH . 'includes/class-moolre-pmpro-gateway.php';
}

/**
 * Register the Moolre gateway with PMPro.
 */
add_filter( 'pmpro_gateways', 'gmg_register_gateway' );
function gmg_register_gateway( $gateways ) {
    $gateways['moolre'] = 'Moolre — MTN MoMo';
    return $gateways;
}

/**
 * Register REST API callback route for Moolre webhook.
 * REST API bypasses Cloudflare JS challenge (unlike admin-ajax.php on staging).
 */
add_action( 'rest_api_init', 'gmg_register_rest_routes' );
function gmg_register_rest_routes() {
    register_rest_route( 'gmg/v1', '/callback', array(
        'methods'             => 'POST,GET',
        'callback'            => 'gmg_rest_callback',
        'permission_callback' => '__return_true',
    ) );
}

function gmg_rest_callback( $request ) {
    if ( ! function_exists( 'pmpro_doing_webhook' ) ) {
        return new WP_Error( 'pmpro_missing', 'PMPro not loaded.', array( 'status' => 500 ) );
    }

    if ( ! class_exists( 'PMProGateway_moolre' ) ) {
        require_once GMG_PATH . 'includes/class-moolre-pmpro-gateway.php';
    }

    $reference = $request->get_param( 'reference' );
    if ( empty( $reference ) ) {
        return new WP_Error( 'missing_reference', 'Missing reference.', array( 'status' => 400 ) );
    }

    // Make reference available via $_REQUEST for the webhook handler.
    $_REQUEST['reference'] = $reference;

    $gateway = new PMProGateway_moolre( 'moolre' );
    $gateway->webhook();
    exit;
}

/**
 * Webhook endpoint (legacy AJAX fallback — also registered for completeness).
 */
add_action( 'wp_ajax_nopriv_gmg_callback', 'gmg_webhook_handler' );
add_action( 'wp_ajax_gmg_callback', 'gmg_webhook_handler' );
function gmg_webhook_handler() {
    // Moolre sends reference as GET or POST param.
    $reference = isset( $_REQUEST['reference'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['reference'] ) ) : '';
    if ( empty( $reference ) ) {
        status_header( 400 );
        echo 'Invalid request.';
        exit;
    }

    // PMPro must be active (this is a PMPro gateway).
    if ( ! function_exists( 'pmpro_doing_webhook' ) ) {
        status_header( 500 );
        echo 'PMPro not loaded.';
        exit;
    }

    // Load gateway class if not already loaded.
    if ( ! class_exists( 'PMProGateway_moolre' ) ) {
        require_once GMG_PATH . 'includes/class-moolre-pmpro-gateway.php';
    }

    $gateway = new PMProGateway_moolre( 'moolre' );
    $gateway->webhook();
}

/**
 * Set Moolre as default gateway on activation.
 */
register_activation_hook( __FILE__, 'gmg_activate' );
function gmg_activate() {
    if ( ! class_exists( 'PMProMembershipLevel' ) ) {
        return;
    }
    $current = get_option( 'pmpro_gateway', '' );
    if ( ! $current || $current === 'wordpress' ) {
        update_option( 'pmpro_gateway', 'moolre' );
    }
    if ( ! get_option( 'pmpro_currency' ) ) {
        update_option( 'pmpro_currency', 'GHS' );
    }
}

/**
 * Admin notices for Moolre gateway configuration.
 */
add_action( 'admin_notices', 'gmg_admin_notices' );
function gmg_admin_notices() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || 'memberships_page_pmpro-settings' !== $screen->id ) {
        return;
    }

    $settings = get_option( 'gmg_settings', array() );
    if ( empty( $settings['api_key'] ) || empty( $settings['account_number'] ) || empty( $settings['api_user'] ) ) {
        echo '<div class="notice notice-warning"><p><strong>Gabochie Moolre Gateway:</strong> Enter your Moolre API key, username and account number in the Payment Gateway settings below.</p></div>';
    }

    $webhook_url = rest_url( 'gmg/v1/callback' );
    echo '<div class="notice notice-info"><p><strong>Moolre webhook URL:</strong> <code>' . esc_html( $webhook_url ) . '</code> — set this in your <a href="https://dashboard.moolre.com" target="_blank" rel="noopener">Moolre dashboard</a>.</p></div>';
}
