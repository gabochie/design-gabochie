<?php
/**
 * Plugin Name: Gabochie Slug Lock
 * Description: Pins the slug of canonical pages so no plugin, importer, or bulk process can rewrite a live URL.
 * Version: 1.0.0
 * Author: Gabochie Design
 *
 * Why: a published slug is a permanent inbound URL. On this site the Yoast Smart
 * Auto-Fixer rewrote canonical slugs from focus keyphrases (building-cost-board
 * -> ghana-construction-material-prices-2026, thesis -> the-development-execution-gap),
 * which 404'd every internal and external link to those pages.
 *
 * Any post carrying the `_gv_locked_slug` meta has its slug forced back to that
 * value on every write. Removing the meta unlocks the post again.
 *
 * @package Gabochie
 */

defined( 'ABSPATH' ) || exit;

const GV_SLUG_LOCK_META = '_gv_locked_slug';

/**
 * Slugs rejected during the current request, keyed by post ID.
 *
 * The write happens after wp_insert_post_data, so the audit entry is deferred to
 * wp_after_insert_post rather than written from inside the filter.
 *
 * @return array<int, string> Rejected slugs, keyed by post ID.
 */
function &gv_slug_lock_attempts() {
	static $attempts = array();
	return $attempts;
}

/**
 * Force a locked slug back onto the post on every insert/update.
 *
 * Runs on wp_insert_post_data, which fires after wp_unique_post_slug() has
 * already settled the incoming slug but before the row is written, so the
 * corrected slug is what gets persisted (and what save_post observers see).
 *
 * @param array $data    Sanitized post data about to be written.
 * @param array $postarr Raw post array.
 * @return array
 */
function gv_slug_lock_filter_post_data( $data, $postarr ) {
	$post_id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
	if ( $post_id <= 0 ) {
		return $data;
	}

	$locked = get_post_meta( $post_id, GV_SLUG_LOCK_META, true );
	if ( ! is_string( $locked ) || '' === $locked ) {
		return $data;
	}

	if ( isset( $data['post_name'] ) && $data['post_name'] !== $locked ) {
		$attempts             =& gv_slug_lock_attempts();
		$attempts[ $post_id ] = $data['post_name'];
		$data['post_name']    = $locked;
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'gv_slug_lock_filter_post_data', 5, 2 );

/**
 * Record an audit trail entry whenever a locked slug is defended.
 *
 * @param int    $post_id Post ID.
 * @param string $attempt The slug that was rejected.
 * @return void
 */
function gv_slug_lock_log_defence( $post_id, $attempt ) {
	$user  = wp_get_current_user();
	$log   = get_option( 'gv_slug_lock_log', array() );
	$log   = is_array( $log ) ? $log : array();
	$entry = array(
		'post_id' => (int) $post_id,
		'title'   => get_the_title( $post_id ),
		'attempt' => (string) $attempt,
		'time'    => current_time( 'mysql' ),
		'user'    => ( $user instanceof WP_User && $user->exists() ) ? $user->display_name : 'anonymous',
		'agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 120 ) : '',
	);
	array_unshift( $log, $entry );
	$log = array_slice( $log, 0, 50 );
	update_option( 'gv_slug_lock_log', $log, false );
}

/**
 * Log the rejected slug once the write has landed, then re-verify the lock.
 *
 * @param int      $post_id Post ID.
 * @param \WP_Post $post    Post object. Unused; kept because the hook passes it.
 * @return void
 */
function gv_slug_lock_after_insert( $post_id, $post ) {
	unset( $post );
	$attempts =& gv_slug_lock_attempts();

	if ( isset( $attempts[ $post_id ] ) ) {
		$attempt = $attempts[ $post_id ];
		unset( $attempts[ $post_id ] );
		gv_slug_lock_log_defence( $post_id, $attempt );
	}

	// Safety net: catches anything that bypassed the filter (direct DB writes).
	$locked = get_post_meta( $post_id, GV_SLUG_LOCK_META, true );
	if ( is_string( $locked ) && '' !== $locked && get_post_field( 'post_name', $post_id ) !== $locked ) {
		gv_slug_lock_log_defence( $post_id, get_post_field( 'post_name', $post_id ) );
	}
}
add_action( 'wp_after_insert_post', 'gv_slug_lock_after_insert', 10, 2 );

/**
 * Seed the lock meta for a post, from either an ID or a slug.
 *
 * Usage via wp eval: gv_slug_lock_slug( 'building-cost-board' );
 *
 * Refuses to overwrite an existing lock with a different value, so a page that
 * has already drifted can never be re-blessed by running the seeder twice.
 *
 * @param string $slug Post slug to lock.
 * @return string Status message.
 */
function gv_slug_lock_slug( $slug ) {
	$post = get_page_by_path( $slug, OBJECT, array( 'page', 'post' ) );
	if ( ! $post ) {
		return 'not found: ' . $slug;
	}
	$existing = get_post_meta( $post->ID, GV_SLUG_LOCK_META, true );
	if ( is_string( $existing ) && '' !== $existing ) {
		if ( $existing === $post->post_name ) {
			return 'already locked: ' . $slug;
		}
		return 'REFUSED: ' . $post->ID . ' is locked to ' . $existing . ' but the live slug is ' . $post->post_name;
	}
	update_post_meta( $post->ID, GV_SLUG_LOCK_META, $post->post_name );
	return 'locked ' . $post->ID . ' -> ' . $post->post_name;
}

/**
 * Report every locked post and any lock that disagrees with the stored slug.
 *
 * @return array Report rows.
 */
function gv_slug_lock_report() {
	global $wpdb;
	$rows   = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, p.post_name, p.post_type, p.post_status, m.meta_value AS locked
			 FROM {$wpdb->postmeta} m
			 INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
			 WHERE m.meta_key = %s
			 ORDER BY p.ID",
			GV_SLUG_LOCK_META
		),
		ARRAY_A
	);
	$report = array();
	foreach ( (array) $rows as $row ) {
		$row['match'] = ( $row['post_name'] === $row['locked'] ) ? 'OK' : 'MISMATCH';
		$report[]     = $row;
	}
	return $report;
}
