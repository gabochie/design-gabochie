<?php
/**
 * Frontend widget: enqueues the voice widget JS and prints the mount point.
 * WordPress-native: uses Web Speech API (browser STT/TTS) + Gemini proxy.
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
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		$api_key = gabochie_voice_agent_gemini_key();
		if ( empty( $api_key ) ) {
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
			'GabochieVoiceConfig',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'rest_url'   => rest_url( 'gabochie-voice/v1/chat' ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'site_name'  => get_bloginfo( 'name' ),
				'onboarding' => home_url( '/onboarding/' ),
			)
		);

		echo '<div id="gabochie-voice-widget"></div>';
	}
}
