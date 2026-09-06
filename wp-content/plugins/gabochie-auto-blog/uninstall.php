<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = array(
	'gab_frequency',
	'gab_time',
	'gab_category',
	'gab_author',
	'gab_status',
	'gab_topics',
	'gab_enabled',
	'gab_last_generated',
	'gab_total_generated',
	'gab_errors',
	'gab_mode',
	'gab_ai_provider',
	'gab_ai_key',
	'gab_ai_model',
	'gab_ai_base_url',
	'gab_ai_errors',
	'gab_last_bank_run',
);
foreach ( $options as $opt ) {
	delete_option( $opt );
}

$ts = wp_next_scheduled( 'gab_generate_content' );
if ( $ts ) {
	wp_unschedule_event( $ts, 'gab_generate_content' );
}
$bank_ts = wp_next_scheduled( 'gab_bank_publish' );
if ( $bank_ts ) {
	wp_unschedule_event( $bank_ts, 'gab_bank_publish' );
}

$posts = get_posts(
	array(
		'post_type'      => 'any',
		'meta_key'       => '_gab_auto_generated',
		'meta_value'     => '1',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
foreach ( $posts as $pid ) {
	delete_post_meta( $pid, '_gab_auto_generated' );
	delete_post_meta( $pid, '_gab_topic' );
	delete_post_meta( $pid, '_gab_generated_date' );
}

$bank_posts = get_posts(
	array(
		'post_type'      => 'any',
		'meta_key'       => '_gab_bank_id',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
foreach ( $bank_posts as $pid ) {
	delete_post_meta( $pid, '_gab_bank_id' );
	delete_post_meta( $pid, '_gab_bank_date' );
	delete_post_meta( $pid, '_yoast_wpseo_focuskw' );
	delete_post_meta( $pid, '_yoast_wpseo_metadesc' );
}
