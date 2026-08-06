<?php
/**
 * Plugin Name: Gabochie Voice Agent
 * Description: Public-facing, lead-qualifying voice agent. Adds the voice widget to the frontend and an intake endpoint (POST /wp-json/gabochie-voice/v1/voice-lead, header X-Gabochie-Voice-Key) for the GabochieVoiceAgent Cloudflare Worker.
 * Version: 1.0.0
 * Author: Gabochie Design
 * Text Domain: gabochie-voice-agent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'GABOCHIE_VOICE_AGENT_VERSION', '1.0.0' );
define( 'GABOCHIE_VOICE_AGENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'GABOCHIE_VOICE_AGENT_URL', plugin_dir_url( __FILE__ ) );

require_once GABOCHIE_VOICE_AGENT_DIR . 'includes/class-rest.php';
require_once GABOCHIE_VOICE_AGENT_DIR . 'includes/class-widget.php';
require_once GABOCHIE_VOICE_AGENT_DIR . 'includes/class-settings.php';

/**
 * Host of the voice-agent Worker (scheme + hostname, no trailing slash),
 * e.g. https://gabochie-voice-agent.example.workers.dev
 * Override in wp-config.php: define( 'GABOCHIE_VOICE_AGENT_HOST', '...' );
 */
function gabochie_voice_agent_host() {
	if ( defined( 'GABOCHIE_VOICE_AGENT_HOST' ) ) {
		return (string) GABOCHIE_VOICE_AGENT_HOST;
	}
	return (string) get_option( 'gabochie_voice_agent_host', '' );
}

/**
 * Shared secret the Worker sends as the X-Gabochie-Voice-Key header.
 * Override in wp-config.php: define( 'GABOCHIE_VOICE_AGENT_KEY', '...' );
 */
function gabochie_voice_agent_key() {
	if ( defined( 'GABOCHIE_VOICE_AGENT_KEY' ) ) {
		return (string) GABOCHIE_VOICE_AGENT_KEY;
	}
	return (string) get_option( 'gabochie_voice_agent_key', '' );
}

final class Gabochie_Voice_Agent {

	/** @var Gabochie_Voice_Agent|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		Gabochie_Voice_Rest::instance();
		Gabochie_Voice_Widget::instance();
		Gabochie_Voice_Settings::instance();
	}
}

Gabochie_Voice_Agent::instance();
