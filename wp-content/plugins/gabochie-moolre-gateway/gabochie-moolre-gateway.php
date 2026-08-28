<?php
/**
 * Plugin Name: Gabochie Moolre Gateway for PMPro
 * Plugin URI:  https://gabochiedesign.com
 * Description: PMPro payment gateway for Moolre (MTN MoMo). Redirects to Moolre's hosted checkout for mobile money payments, verifies via callback.
 * Version:     1.0.0
 * Author:      Gabochie Design
 * License:     GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: gabochie-moolre
 */

defined( 'ABSPATH' ) || exit;

define( 'GMG_VERSION', '1.0.0' );
define( 'GMG_PATH', plugin_dir_path( __FILE__ ) );
define( 'GMG_URL', plugin_dir_url( __FILE__ ) );

require_once GMG_PATH . 'includes/class-moolre-pmpro-gateway.php';

/**
 * Register the Moolre gateway with PMPro.
 */
add_filter( 'pmpro_gateways', 'gmg_register_gateway' );
function gmg_register_gateway( $gateways ) {
    $gateways['moolre'] = 'Moolre — MTN MoMo';
    return $gateways;
}

/**
 * Webhook endpoint — receives Moolre callback after payment.
 * Registered as wp_ajax_nopriv_moolre_webhook (same pattern as PMPro's ipnhandler, stripe_webhook).
 */
add_action( 'wp_ajax_nopriv_moolre_webhook', 'gmg_webhook_handler' );
add_action( 'wp_ajax_moolre_webhook', 'gmg_webhook_handler' );
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
    if ( empty( $settings['api_key'] ) || empty( $settings['account_number'] ) ) {
        echo '<div class="notice notice-warning"><p><strong>Gabochie Moolre Gateway:</strong> Enter your Moolre API key and account number in the Payment Gateway settings below.</p></div>';
    }

    $webhook_url = admin_url( 'admin-ajax.php?action=moolre_webhook' );
    echo '<div class="notice notice-info"><p><strong>Moolre webhook URL:</strong> <code>' . esc_html( $webhook_url ) . '</code> — set this in your <a href="https://dashboard.moolre.com" target="_blank" rel="noopener">Moolre dashboard</a>.</p></div>';
}
