<?php
/**
 * Frontend widget: enqueues the built voice widget bundle and prints the
 * mount point. The widget only renders once a Worker host is configured,
 * so enabling the plugin alone does not change the frontend.
 *
 * @package Gabochie_Voice_Agent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Gabochie_Voice_Widget {

	/** @var Gabochie_Voice_Widget|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_footer', array( $this, 'render' ) );
	}

	public function render() {
		$host = gabochie_voice_agent_host();
		if ( '' === $host || is_admin() || wp_doing_ajax() ) {
			return;
		}

		$handle = 'gabochie-voice-widget';
		wp_enqueue_script(
			$handle,
			GABOCHIE_VOICE_AGENT_URL . 'assets/js/gabochie-voice-widget.js',
			array(),
			GABOCHIE_VOICE_AGENT_VERSION,
			true
		);
		wp_localize_script(
			$handle,
			'GabochieVoiceAgent',
			array(
				'host'     => untrailingslashit( $host ),
				'agent'    => 'GabochieVoiceAgent',
				'siteName' => get_bloginfo( 'name' ),
			)
		);

		// The widget mounts its shadow DOM inside this element.
		echo '<div id="gabochie-voice-widget"></div>';
	}
}
