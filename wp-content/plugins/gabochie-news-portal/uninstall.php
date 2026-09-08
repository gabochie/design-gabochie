<?php
/**
 * Gabochie News Portal uninstall.
 *
 * Removes options, the daily cron, all `news` posts (+ meta), and the
 * `news_category` terms when the plugin is deleted via the Plugins screen.
 *
 * @package Gabochie_News_Portal
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$gnp_options = array(
	'gnp_sources',
	'gnp_errors',
	'gnp_last_fetch',
	'gnp_news_page_id',
	'gnp_news_page_created',
	'gnp_ai_model',
);
foreach ( $gnp_options as $option ) {
	delete_option( $option );
}

wp_clear_scheduled_hook( 'gnp_fetch' );

$items = get_posts(
	array(
		'post_type'      => 'news',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
foreach ( $items as $item_id ) {
	wp_delete_post( (int) $item_id, true );
}

$terms = get_terms(
	array(
		'taxonomy'   => 'news_category',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);
if ( is_array( $terms ) ) {
	foreach ( $terms as $term_id ) {
		wp_delete_term( (int) $term_id, 'news_category' );
	}
}
