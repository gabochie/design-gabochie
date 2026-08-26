<?php
/**
 * Plugin Name: Gabochie Voice Agent
 * Description: WordPress-native voice agent with English + Twi-nglish support. Browser-based speech recognition and synthesis with Gemini AI. Lead-qualifying conversational assistant.
 * Version: 2.0.0
 * Author: Gabochie Design
 * Text Domain: gabochie-voice-agent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'GABOCHIE_VOICE_AGENT_VERSION', '2.0.0' );
define( 'GABOCHIE_VOICE_AGENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'GABOCHIE_VOICE_AGENT_URL', plugin_dir_url( __FILE__ ) );

require_once GABOCHIE_VOICE_AGENT_DIR . 'includes/class-rest.php';
require_once GABOCHIE_VOICE_AGENT_DIR . 'includes/class-widget.php';
require_once GABOCHIE_VOICE_AGENT_DIR . 'includes/class-settings.php';

/**
 * Get the Gemini API key. Checks plugin settings first, falls back to GhBC.
 */
function gabochie_voice_agent_gemini_key() {
	$key = get_option( 'gabochie_voice_agent_gemini_key', '' );
	if ( empty( $key ) ) {
		$key = get_option( 'ghbc_llm_key', '' );
	}
	return $key;
}

/**
 * Get the system prompt for the voice agent.
 */
function gabochie_voice_agent_system_prompt() {
	$default = 'You are Gabochie, a friendly building consultant helping people in Ghana with construction, permits, and building projects. You speak in casual Ghanaian English mixed with Twi phrases. Be warm, helpful, and encouraging. Keep responses concise (2-3 sentences max) since this is a voice conversation. If someone wants to start a project, guide them to fill out the onboarding form at /onboarding/. You know about: building permits in Accra, construction costs in Ghana, finding contractors, land ownership, and the Ghana Building Code (GS 1207:2018).';
	return apply_filters( 'gabochie_voice_agent_prompt', $default );
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
