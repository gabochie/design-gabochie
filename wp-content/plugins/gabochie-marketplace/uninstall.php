<?php
/**
 * Gabochie Marketplace — uninstall.
 *
 * Removes the gd_property posts, taxonomy terms/relationships, the /marketplace/
 * page, the sample-seed flag, the intelligence cron and the plugin options.
 * Leaves the CRM contacts created by enquiries intact (marketing data).
 *
 * @package Gabochie_Marketplace
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Unschedule the intelligence refresh cron.
$hook  = 'gmp_intelligence_refresh';
$crons = _get_cron_array();
if ( is_array( $crons ) ) {
	foreach ( $crons as $timestamp => $cron ) {
		if ( isset( $cron[ $hook ] ) ) {
			unset( $crons[ $timestamp ][ $hook ] );
		}
		if ( empty( $crons[ $timestamp ] ) ) {
			unset( $crons[ $timestamp ] );
		}
	}
	_set_cron_array( $crons );
}

// Delete all gd_property posts (and their meta + terms).
$ids = get_posts(
	array(
		'post_type'      => 'gd_property',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
foreach ( (array) $ids as $listing_id ) {
	wp_delete_post( (int) $listing_id, true );
}

// Remove the /marketplace/ page if the plugin created it.
$page_id = (int) get_option( 'gmp_page_id', 0 );
if ( $page_id > 0 ) {
	$page_obj = get_post( $page_id );
	if ( $page_obj && 'page' === $page_obj->post_type && 'marketplace' === $page_obj->post_name ) {
		wp_delete_post( $page_id, true );
	}
}

// Drop the property type/status terms + term relationships.
foreach ( array( 'gd_property_type', 'gd_property_status' ) as $tax_slug ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $tax_slug,
			'hide_empty' => false,
			'fields'     => 'tt_ids',
		)
	);
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		foreach ( (array) $terms as $tt_id ) {
			$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $tt_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
	}
}

// Plugin options.
delete_option( 'gmp_page_id' );
delete_option( 'gmp_page_created' );
delete_option( 'gmp_seeded' );
delete_option( 'gmp_ai_model' );
delete_option( 'gmp_errors' );

// Drop the CPT/taxonomy rewrite rules we registered.
flush_rewrite_rules();
