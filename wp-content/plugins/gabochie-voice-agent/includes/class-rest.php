<?php
/**
 * REST API endpoints for the voice agent.
 * - POST /wp-json/gabochie-voice/v1/chat — Gemini conversation (proxied, key server-side)
 * - POST /wp-json/gabochie-voice/v1/voice-lead — Lead capture from voice conversation
 *
 * @package Gabochie_Voice_Agent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Gabochie_Voice_Rest {

	/** @var Gabochie_Voice_Rest|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_lead_type' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'manage_gabochie_lead_posts_columns', array( $this, 'lead_columns' ) );
		add_action( 'manage_gabochie_lead_posts_custom_column', array( $this, 'lead_column_content' ), 10, 2 );
	}

	/* ---------------------------------------------------------------- CPT */

	public function register_lead_type() {
		register_post_type(
			'gabochie_lead',
			array(
				'public'       => false,
				'show_ui'      => true,
				'menu_icon'    => 'dashicons-phone',
				'labels'       => array(
					'name'          => __( 'Voice Leads', 'gabochie-voice-agent' ),
					'singular_name' => __( 'Voice Lead', 'gabochie-voice-agent' ),
					'add_new'       => __( 'Add New', 'gabochie-voice-agent' ),
					'edit_item'     => __( 'Edit Voice Lead', 'gabochie-voice-agent' ),
					'view_item'     => __( 'View Voice Lead', 'gabochie-voice-agent' ),
					'search_items'  => __( 'Search Voice Leads', 'gabochie-voice-agent' ),
					'not_found'     => __( 'No voice leads yet.', 'gabochie-voice-agent' ),
				),
				'supports'     => array( 'title' ),
				'capabilities' => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap' => true,
			)
		);
	}

	/* -------------------------------------------------------------- REST */

	public function register_routes() {
		// Chat endpoint — proxies to Gemini
		register_rest_route(
			'gabochie-voice/v1',
			'/chat',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_chat' ),
				'permission_callback' => '__return_true',
			)
		);

		// Lead capture endpoint
		register_rest_route(
			'gabochie-voice/v1',
			'/voice-lead',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_voice_lead' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle chat request — proxy to Gemini API with system prompt.
	 */
	public function handle_chat( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();

		$message    = isset( $params['message'] ) ? sanitize_text_field( (string) $params['message'] ) : '';
		$history    = isset( $params['history'] ) && is_array( $params['history'] ) ? $params['history'] : array();
		$session_id = isset( $params['session_id'] ) ? sanitize_text_field( (string) $params['session_id'] ) : '';

		if ( empty( $message ) ) {
			return new WP_Error(
				'gva_empty_message',
				__( 'Message is required.', 'gabochie-voice-agent' ),
				array( 'status' => 400 )
			);
		}

		$api_key = gabochie_voice_agent_gemini_key();
		if ( empty( $api_key ) ) {
			return new WP_Error(
				'gva_no_api_key',
				__( 'Voice agent is not configured. Please set a Gemini API key.', 'gabochie-voice-agent' ),
				array( 'status' => 500 )
			);
		}

		// Rate limit: 30 messages per session per 10 minutes
		if ( $session_id ) {
			$rate_key  = 'gva_chat_' . md5( $session_id );
			$chat_count = (int) get_transient( $rate_key );
			if ( $chat_count >= 30 ) {
				return new WP_Error(
					'gva_rate_limited',
					__( 'Too many messages. Please wait a moment.', 'gabochie-voice-agent' ),
					array( 'status' => 429 )
				);
			}
			set_transient( $rate_key, $chat_count + 1, 600 );
		}

		$reply = $this->call_gemini( $message, $history, $api_key );

		if ( is_wp_error( $reply ) ) {
			return $reply;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'reply'   => $reply,
			)
		);
	}

	/**
	 * Call Gemini API with the message and conversation history.
	 */
	private function call_gemini( $message, $history, $api_key ) {
		$model  = get_option( 'ghbc_llm_model', 'gemini-3.5-flash' );
		$url    = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;

		// Build contents array from history
		$contents = array();
		foreach ( $history as $msg ) {
			$role = isset( $msg['role'] ) ? $msg['role'] : ( 'assistant' === ( $msg['role'] ?? '' ) ? 'model' : 'user' );
			$text = isset( $msg['text'] ) ? $msg['text'] : ( $msg['content'] ?? '' );
			if ( empty( $text ) ) {
				continue;
			}
			$contents[] = array(
				'role'  => $role === 'assistant' ? 'model' : 'user',
				'parts' => array( array( 'text' => $text ) ),
			);
		}

		// Add current message
		$contents[] = array(
			'role'  => 'user',
			'parts' => array( array( 'text' => $message ) ),
		);

		$body = array(
			'system_instruction' => array(
				'parts' => array( array( 'text' => gabochie_voice_agent_system_prompt() ) ),
			),
			'contents'          => $contents,
			'safetySettings'    => array(
				array(
					'category'  => 'HARM_CATEGORY_HARASSMENT',
					'threshold' => 'BLOCK_NONE',
				),
				array(
					'category'  => 'HARM_CATEGORY_HATE_SPEECH',
					'threshold' => 'BLOCK_NONE',
				),
				array(
					'category'  => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
					'threshold' => 'BLOCK_NONE',
				),
				array(
					'category'  => 'HARM_CATEGORY_DANGEROUS_CONTENT',
					'threshold' => 'BLOCK_NONE',
				),
			),
			'generationConfig'  => array(
				'maxOutputTokens' => 150,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'gva_gemini_error',
				__( 'Failed to connect to AI service.', 'gabochie-voice-agent' ),
				array( 'status' => 502 )
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$error_msg = $data['error']['message'] ?? 'Unknown Gemini error';
			return new WP_Error(
				'gva_gemini_error',
				sprintf( __( 'AI error: %s', 'gabochie-voice-agent' ), $error_msg ),
				array( 'status' => 502 )
			);
		}

		return $data['candidates'][0]['content']['parts'][0]['text'];
	}

	/**
	 * Handle voice lead submission.
	 */
	public function handle_voice_lead( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();

		$lead = array(
			'name'          => isset( $params['name'] ) ? sanitize_text_field( (string) $params['name'] ) : '',
			'email'         => isset( $params['email'] ) ? sanitize_email( (string) $params['email'] ) : '',
			'phone'         => isset( $params['phone'] ) ? sanitize_text_field( (string) $params['phone'] ) : '',
			'location'      => isset( $params['location'] ) ? sanitize_text_field( (string) $params['location'] ) : '',
			'land_size'     => isset( $params['land_size'] ) ? sanitize_text_field( (string) $params['land_size'] ) : '',
			'ownership'     => isset( $params['ownership'] ) ? sanitize_text_field( (string) $params['ownership'] ) : '',
			'building_type' => isset( $params['building_type'] ) ? sanitize_text_field( (string) $params['building_type'] ) : '',
			'budget'        => isset( $params['budget'] ) ? sanitize_text_field( (string) $params['budget'] ) : '',
			'goal'          => isset( $params['goal'] ) ? sanitize_text_field( (string) $params['goal'] ) : '',
			'objectives'    => isset( $params['objectives'] ) ? sanitize_text_field( (string) $params['objectives'] ) : '',
			'referrer'      => isset( $params['referrer'] ) ? sanitize_text_field( (string) $params['referrer'] ) : 'voice-agent',
		);

		if ( empty( $lead['name'] ) || ! is_email( $lead['email'] ) ) {
			return new WP_Error(
				'gva_invalid_lead',
				__( 'A name and a valid email address are required.', 'gabochie-voice-agent' ),
				array( 'status' => 400 )
			);
		}

		// Rate limit: 3 leads per email per hour
		$rate = $this->rate_limit( $lead['email'] );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$lead_id = $this->store_lead( $lead );
		if ( is_wp_error( $lead_id ) ) {
			return $lead_id;
		}

		// Route the lead into the lead hub so it reaches FluentCRM too.
		do_action(
			'gabochie/lead/captured',
			$lead['email'],
			array(
				'list'   => 'voice',
				'source' => 'voice-agent',
				'name'   => $lead['name'],
				'phone'  => $lead['phone'],
			)
		);

		$this->notify( $lead, $lead_id );

		return rest_ensure_response(
			array(
				'success' => true,
				'lead_id' => $lead_id,
				'message' => 'Thanks — your project has been received.',
			)
		);
	}

	/* -------------------------------------------------------- Rate limit */

	private function rate_limit( $email ) {
		$key  = 'gva_rl_em_' . md5( strtolower( $email ) );
		$used = (int) get_transient( $key );
		if ( $used >= 3 ) {
			return new WP_Error(
				'gva_rate_limited',
				__( 'A lead from this email was received recently. Please try again later.', 'gabochie-voice-agent' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $used + 1, HOUR_IN_SECONDS );
		return true;
	}

	/* ----------------------------------------------------------- Storage */

	private function store_lead( array $lead ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'gabochie_lead',
				'post_status' => 'publish',
				'post_title'  => $lead['name'],
				'meta_input'  => array(
					'_gabochie_email'         => $lead['email'],
					'_gabochie_phone'         => $lead['phone'],
					'_gabochie_location'      => $lead['location'],
					'_gabochie_land_size'     => $lead['land_size'],
					'_gabochie_ownership'     => $lead['ownership'],
					'_gabochie_building_type' => $lead['building_type'],
					'_gabochie_budget'        => $lead['budget'],
					'_gabochie_goal'          => $lead['goal'],
					'_gabochie_objectives'    => $lead['objectives'],
					'_gabochie_referrer'      => $lead['referrer'],
					'_gabochie_source'        => 'voice',
				),
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		if ( 0 === $post_id ) {
			return new WP_Error(
				'gva_store_failed',
				__( 'The lead could not be stored.', 'gabochie-voice-agent' ),
				array( 'status' => 500 )
			);
		}
		return $post_id;
	}

	private function notify( array $lead, $lead_id ) {
		$admin = get_option( 'admin_email' );
		if ( ! is_email( $admin ) ) {
			return;
		}

		$body = implode(
			"\n",
			array(
				'Name: ' . $lead['name'],
				'Email: ' . $lead['email'],
				'Phone: ' . $lead['phone'],
				'Location: ' . $lead['location'],
				'Land size: ' . $lead['land_size'],
				'Ownership: ' . $lead['ownership'],
				'Building type: ' . $lead['building_type'],
				'Budget: ' . $lead['budget'],
				'Goal: ' . $lead['goal'],
				'Objectives: ' . $lead['objectives'],
				'Referrer: ' . $lead['referrer'],
				'',
				'View: ' . esc_url( admin_url( 'post.php?post=' . (int) $lead_id . '&action=edit' ) ),
			)
		);

		wp_mail(
			$admin,
			'New voice lead: ' . $lead['name'],
			$body,
			array( 'Reply-To: ' . $lead['email'] )
		);
	}

	/* ---------------------------------------------------- Admin columns */

	public function lead_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['gva_email']    = __( 'Email', 'gabochie-voice-agent' );
				$new['gva_phone']    = __( 'Phone', 'gabochie-voice-agent' );
				$new['gva_location'] = __( 'Location', 'gabochie-voice-agent' );
				$new['gva_budget']   = __( 'Budget', 'gabochie-voice-agent' );
			}
			$new[ $key ] = $label;
		}
		return $new;
	}

	public function lead_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'gva_email':
				echo esc_html( (string) get_post_meta( $post_id, '_gabochie_email', true ) );
				break;
			case 'gva_phone':
				echo esc_html( (string) get_post_meta( $post_id, '_gabochie_phone', true ) );
				break;
			case 'gva_location':
				echo esc_html( (string) get_post_meta( $post_id, '_gabochie_location', true ) );
				break;
			case 'gva_budget':
				echo esc_html( (string) get_post_meta( $post_id, '_gabochie_budget', true ) );
				break;
		}
	}
}
