<?php
/**
 * Plugin Name: Gabochie DeFi & Crypto Payment Gateway
 * Plugin URI: https://gabochiedesign.com
 * Description: Direct self-custodial Web3 & DeFi payment gateway for WooCommerce. Accepts USDT, USDC, ETH, SOL, MATIC, and BNB across Polygon, Base, Arbitrum, BSC, Ethereum, and Solana with automated on-chain transaction verification.
 * Version: 1.0.0
 * Author: Gabochie Design
 * Author URI: https://gabochiedesign.com
 * Text Domain: gabochie-defi
 * WC requires at least: 6.0
 * WC tests up to: 10.9
 *
 * @package Gabochie_DeFi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GABOCHIE_DEFI_VERSION', '1.0.0' );
define( 'GABOCHIE_DEFI_PATH', plugin_dir_path( __FILE__ ) );
define( 'GABOCHIE_DEFI_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize the gateway when WooCommerce is loaded.
 */
add_action( 'plugins_loaded', 'gabochie_defi_init', 11 );

function gabochie_defi_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once GABOCHIE_DEFI_PATH . 'includes/class-gabochie-defi-verifier.php';
	require_once GABOCHIE_DEFI_PATH . 'includes/class-gabochie-defi-gateway.php';

	add_filter( 'woocommerce_payment_gateways', 'gabochie_defi_add_gateway' );
}

/**
 * Register gateway in WooCommerce.
 *
 * @param array $methods Gateways.
 * @return array
 */
function gabochie_defi_add_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Gabochie_DeFi';
	return $methods;
}

/**
 * Enqueue frontend scripts and styles on checkout page.
 */
add_action( 'wp_enqueue_scripts', 'gabochie_defi_enqueue_assets' );

function gabochie_defi_enqueue_assets() {
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		wp_enqueue_style(
			'gabochie-defi-checkout',
			GABOCHIE_DEFI_URL . 'assets/css/defi-checkout.css',
			array(),
			GABOCHIE_DEFI_VERSION
		);
		wp_enqueue_script(
			'gabochie-defi-checkout',
			GABOCHIE_DEFI_URL . 'assets/js/defi-checkout.js',
			array( 'jquery' ),
			GABOCHIE_DEFI_VERSION,
			true
		);
		wp_localize_script(
			'gabochie-defi-checkout',
			'gabochieDeFi',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'gabochie_defi_nonce' ),
			)
		);
	}
}

/**
 * Register AJAX verification endpoint for TxHash submission.
 */
add_action( 'wp_ajax_gabochie_defi_verify_tx', 'gabochie_defi_ajax_verify_tx' );
add_action( 'wp_ajax_nopriv_gabochie_defi_verify_tx', 'gabochie_defi_ajax_verify_tx' );

function gabochie_defi_ajax_verify_tx() {
	check_ajax_referer( 'gabochie_defi_nonce', 'nonce' );

	$tx_hash  = isset( $_POST['tx_hash'] ) ? sanitize_text_field( wp_unslash( $_POST['tx_hash'] ) ) : '';
	$chain    = isset( $_POST['chain'] ) ? sanitize_text_field( wp_unslash( $_POST['chain'] ) ) : 'polygon';
	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

	if ( empty( $tx_hash ) || empty( $order_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Missing transaction hash or order ID.', 'gabochie-defi' ) ) );
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => __( 'Invalid order.', 'gabochie-defi' ) ) );
	}

	// Verify transaction on-chain via RPC
	$verifier = new Gabochie_DeFi_Verifier();
	$result   = $verifier->verify_transaction( $tx_hash, $chain, $order );

	if ( $result['success'] ) {
		$order->add_order_note( sprintf(
			__( 'DeFi Payment Verified on-chain (%1$s)! TxHash: %2$s', 'gabochie-defi' ),
			strtoupper( $chain ),
			$tx_hash
		) );
		$order->payment_complete( $tx_hash );

		wp_send_json_success( array(
			'message'      => __( 'Transaction verified on-chain! Order completed.', 'gabochie-defi' ),
			'redirect_url' => $order->get_checkout_order_received_url(),
		) );
	} else {
		wp_send_json_error( array(
			'message' => $result['message'] ? $result['message'] : __( 'Verification pending or not found yet on RPC node. Please wait a few seconds and try again.', 'gabochie-defi' ),
		) );
	}
}
