<?php
/**
 * Plugin Name: Gabochie ExpressPay Gateway for PMPro
 * Plugin URI:  https://gabochiedesign.com
 * Description: PMPro payment gateway for ExpressPay Ghana. Supports Visa, Mastercard, Mobile Money, GhanaPay, and more via ExpressPay's hosted checkout.
 * Version:     1.0.0
 * Author:      Gabochie Design
 * License:     GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: gabochie-expresspay
 */

defined( 'ABSPATH' ) || exit;

define( 'GEG_VERSION', '1.0.0' );
define( 'GEG_PATH', plugin_dir_path( __FILE__ ) );
define( 'GEG_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load gateway class only after PMPro is available.
 */
add_action( 'plugins_loaded', 'geg_load_gateway', 5 );
function geg_load_gateway() {
    if ( ! class_exists( 'PMProGateway' ) ) {
        return;
    }
    require_once GEG_PATH . 'includes/class-expresspay-pmpro-gateway.php';
}

/**
 * Register ExpressPay gateway with PMPro.
 */
add_filter( 'pmpro_gateways', 'geg_register_gateway' );
function geg_register_gateway( $gateways ) {
    $gateways['expresspay'] = 'ExpressPay — Card, MoMo & GhanaPay';
    return $gateways;
}

/**
 * Hide credit card fields on checkout for ExpressPay (offsite redirect gateway).
 * Must run on every page load, not just in the constructor.
 */
add_action( 'init', 'geg_hide_cc_fields' );
function geg_hide_cc_fields() {
    $current_gateway = get_option( 'pmpro_gateway', '' );
    if ( 'expresspay' === $current_gateway ) {
        add_filter( 'pmpro_include_billing_address_fields', '__return_false' );
        add_filter( 'pmpro_hide_payment_information_fields', '__return_true' );
    }
}

/**
 * Register REST API callback route (bypasses Cloudflare).
 */
add_action( 'rest_api_init', 'geg_register_rest_routes' );
function geg_register_rest_routes() {
    register_rest_route( 'geg/v1', '/callback', array(
        'methods'             => 'POST,GET',
        'callback'            => 'geg_rest_callback',
        'permission_callback' => '__return_true',
    ) );
}

function geg_rest_callback( $request ) {
    if ( ! function_exists( 'pmpro_doing_webhook' ) ) {
        return new WP_Error( 'pmpro_missing', 'PMPro not loaded.', array( 'status' => 500 ) );
    }

    if ( ! class_exists( 'PMProGateway_expresspay' ) ) {
        require_once GEG_PATH . 'includes/class-expresspay-pmpro-gateway.php';
    }

    $token    = $request->get_param( 'token' );
    $order_id = $request->get_param( 'order-id' );

    if ( empty( $token ) || empty( $order_id ) ) {
        return new WP_Error( 'missing_params', 'Missing token or order-id.', array( 'status' => 400 ) );
    }

    // Make params available via $_REQUEST for the callback handler.
    $_REQUEST['token']    = $token;
    $_REQUEST['order-id'] = $order_id;

    $gateway = new PMProGateway_expresspay( 'expresspay' );
    $gateway->handle_callback();
    exit;
}

/**
 * Set ExpressPay as default gateway on activation.
 */
register_activation_hook( __FILE__, 'geg_activate' );
function geg_activate() {
    if ( ! class_exists( 'PMProMembershipLevel' ) ) {
        return;
    }
    $current = get_option( 'pmpro_gateway', '' );
    if ( ! $current || $current === 'wordpress' ) {
        update_option( 'pmpro_gateway', 'expresspay' );
    }
    if ( ! get_option( 'pmpro_currency' ) ) {
        update_option( 'pmpro_currency', 'GHS' );
    }
}

/**
 * Admin notices.
 */
add_action( 'admin_notices', 'geg_admin_notices' );
function geg_admin_notices() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || 'memberships_page_pmpro-settings' !== $screen->id ) {
        return;
    }

    $settings = get_option( 'g_eg_settings', array() );
    if ( empty( $settings['merchant_id'] ) || empty( $settings['merchant_api_key'] ) ) {
        echo '<div class="notice notice-warning"><p><strong>Gabochie ExpressPay Gateway:</strong> Enter your Merchant ID and API key in the Payment Gateway settings below.</p></div>';
    }

    $callback_url = rest_url( 'geg/v1/callback' );
    echo '<div class="notice notice-info"><p><strong>ExpressPay callback URL:</strong> <code>' . esc_html( $callback_url ) . '</code> — set this as the <strong>post-url</strong> in your <a href="https://merchant.expresspaygh.com" target="_blank" rel="noopener">ExpressPay merchant dashboard</a>.</p></div>';
}
