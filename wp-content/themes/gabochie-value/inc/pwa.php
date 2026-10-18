<?php
if ( ! defined( 'GABOCHIE_VALUE_DIR' ) || ! defined( 'GABOCHIE_VALUE_URI' ) ) {
	return;
}
/**
 * PWA support — manifest, service worker, offline page, head links
 * @package Gabochie_Value
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gabochie_value_pwa_get_sw() {
	ob_start();
	include GABOCHIE_VALUE_DIR . '/assets/sw.js';
	$sw = ob_get_clean();
	return str_replace( '{{VERSION}}', GABOCHIE_VALUE_VERSION, $sw );
}

function gabochie_value_pwa_maybe_serve() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! isset( $_SERVER['REQUEST_URI'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
	if ( null === $path ) {
		return;
	}
	$path = rtrim( $path, '/' );
	if ( '/sw.js' === $path ) {
		status_header( 200 );
		nocache_headers();
		if ( function_exists( 'litespeed_control_set_nocache' ) ) {
			litespeed_control_set_nocache( 'pwa-sw' );
		}
		header( 'Content-Type: application/javascript; charset=utf-8' );
		echo gabochie_value_pwa_get_sw(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
	if ( '/manifest.webmanifest' === $path ) {
		status_header( 200 );
		nocache_headers();
		if ( function_exists( 'litespeed_control_set_nocache' ) ) {
			litespeed_control_set_nocache( 'pwa-manifest' );
		}
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		echo file_get_contents( GABOCHIE_VALUE_DIR . '/assets/manifest.webmanifest' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
		exit;
	}
	if ( '/offline.html' === $path ) {
		status_header( 200 );
		nocache_headers();
		if ( function_exists( 'litespeed_control_set_nocache' ) ) {
			litespeed_control_set_nocache( 'pwa-offline' );
		}
		header( 'Content-Type: text/html; charset=utf-8' );
		echo file_get_contents( GABOCHIE_VALUE_DIR . '/offline.html' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
		exit;
	}
}
add_action( 'init', 'gabochie_value_pwa_maybe_serve', 1 );

function gabochie_value_pwa_head() {
	echo '<link rel="manifest" href="' . esc_url( home_url( '/manifest.webmanifest' ) ) . '">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( GABOCHIE_VALUE_URI . '/assets/images/pwa/icon-192.png' ) . '">' . "\n";
}
add_action( 'wp_head', 'gabochie_value_pwa_head', 1 );
