<?php
/**
 * Gabochie Market Research — uninstall: drop the plugin's tables, options and
 * scheduled hooks (the plugin itself, not the shared CRM/lead-hub data).
 *
 * @package Gabochie_Market_Research
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table_proposals = $wpdb->prefix . 'gmr_proposals';
$table_search    = $wpdb->prefix . 'gmr_search_log';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$table_proposals}" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$table_search}" );

$opts = array(
	'gmr_enabled',
	'gmr_ai_model',
	'gmr_feeds',
	'gmr_digest_email',
	'gmr_last_signals',
	'gmr_last_memo',
	'gmr_last_memo_at',
	'gmr_errors',
);
foreach ( $opts as $opt ) {
	delete_option( $opt );
}

$hooks = array( 'gmr_collect', 'gmr_weekly_memo' );
foreach ( $hooks as $hook ) {
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
}
