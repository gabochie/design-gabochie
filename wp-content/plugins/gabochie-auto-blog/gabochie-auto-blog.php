<?php
/**
 * Plugin Name:  Gabochie Auto-Blog
 * Plugin URI:   https://design.gabochie.com
 * Description:  AI-powered scheduled content generation for Ghana AEC industry. Topic-bank mode drives the editorial calendar (data/topic-bank.json) with per-post SEO metadata, featured images and a QA gate before auto-publish.
 * Version:      1.1.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://design.gabochie.com
 * Text Domain:  gabochie-auto-blog
 */

defined( 'ABSPATH' ) || exit;

define( 'GAB_VERSION', '1.1.0' );
define( 'GAB_PATH', plugin_dir_path( __FILE__ ) );
define( 'GAB_URL', plugin_dir_url( __FILE__ ) );

require_once GAB_PATH . 'includes/class-ai-adapter.php';
require_once GAB_PATH . 'includes/class-bank.php';
require_once GAB_PATH . 'includes/class-executor.php';
require_once GAB_PATH . 'includes/class-generator.php';
require_once GAB_PATH . 'includes/class-scheduler.php';
require_once GAB_PATH . 'includes/class-admin.php';

register_activation_hook(
	__FILE__,
	function () {
		$defaults = array(
			'gab_frequency'     => 'daily',
			'gab_time'          => '06:00',
			'gab_category'      => 0,
			'gab_author'        => 2,
			'gab_status'        => 'draft',
			'gab_topics'        => "Ghana architecture trends\nSustainable building in Ghana\nDiaspora building tips\nGhana construction costs\nInterior design Ghana\nSmart home technology Ghana\nGhana real estate development\nBuilding regulations Ghana",
			'gab_enabled'       => '0',
			'gab_mode'          => 'bank',
			'gab_ai_provider'   => 'gemini',
			'gab_ai_key'        => '',
			'gab_ai_model'      => 'gemini-flash-latest',
			'gab_ai_base_url'   => 'https://api.openai.com/v1',
			'gab_ai_errors'     => array(),
			'gab_last_bank_run' => '',
		);
		foreach ( $defaults as $key => $val ) {
			if ( ! get_option( $key ) ) {
				update_option( $key, $val );
			}
		}
		if ( ! wp_next_scheduled( 'gab_generate_content' ) ) {
			wp_schedule_event( strtotime( '06:00:00' ), 'daily', 'gab_generate_content' );
		}
		if ( ! wp_next_scheduled( Gab_Auto_Executor::HOOK ) ) {
			wp_schedule_event( strtotime( '06:00:00' ), 'daily', Gab_Auto_Executor::HOOK );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		$timestamp = wp_next_scheduled( 'gab_generate_content' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'gab_generate_content' );
		}
		$bank_ts = wp_next_scheduled( Gab_Auto_Executor::HOOK );
		if ( $bank_ts ) {
			wp_unschedule_event( $bank_ts, Gab_Auto_Executor::HOOK );
		}
	}
);
