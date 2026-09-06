<?php
/**
 * AI adapter for Gabochie Auto-Blog.
 *
 * Provider chain for content generation without hard plugin dependencies:
 * the plugin's own built-in callers first (configured in Auto-Blog Settings),
 * then Gabochie_AI_Engine and GHBC_LLM when loaded and configured. Fails safe
 * (returns empty string) when no backend is available so the executor never
 * falls back to template filler for scheduled posts.
 */

defined( 'ABSPATH' ) || exit;

class Gab_Auto_AI {

	const PROVIDER_OPT = 'gab_ai_provider';
	const KEY_OPT      = 'gab_ai_key';
	const MODEL_OPT    = 'gab_ai_model';
	const BASE_URL_OPT = 'gab_ai_base_url';
	const ERRORS_OPT   = 'gab_ai_errors';

	private static $instance = null;
	private $last_provider   = '';

	/**
	 * @return Gab_Auto_AI
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_gab_ai_test', array( $this, 'ajax_test_connection' ) );
	}

	/** Name of the backend that produced the last completion. */
	public function last_provider() {
		return $this->last_provider;
	}

	/** True when at least one usable AI backend is present. */
	public function is_configured() {
		if ( '' !== (string) get_option( self::KEY_OPT, '' ) ) {
			return true;
		}
		if ( class_exists( 'Gabochie_AI_Engine' ) && Gabochie_AI_Engine::instance()->is_configured() ) {
			return true;
		}
		if ( class_exists( 'GHBC_LLM' ) && GHBC_LLM::instance()->is_ready() ) {
			return true;
		}
		return false;
	}

	/**
	 * Complete a prompt through the provider chain.
	 *
	 * @param string $system System prompt.
	 * @param string $prompt User prompt.
	 * @return string Trimmed text, or '' when every backend failed.
	 */
	public function complete( $system, $prompt ) {
		$key = (string) get_option( self::KEY_OPT, '' );
		if ( '' !== $key ) {
			$provider = (string) get_option( self::PROVIDER_OPT, 'gemini' );
			$model    = (string) get_option( self::MODEL_OPT, 'gemini-flash-latest' );
			$text     = $this->call_provider( $provider, $system, $prompt, $model );
			if ( '' !== trim( (string) $text ) ) {
				$this->last_provider = 'builtin:' . $provider;
				return trim( (string) $text );
			}
		}

		if ( class_exists( 'Gabochie_AI_Engine' ) ) {
			$ai = Gabochie_AI_Engine::instance();
			$ai->refresh_config();
			if ( $ai->is_configured() ) {
				$res = $ai->chat(
					array(
						array(
							'role'    => 'user',
							'content' => $prompt,
						),
					),
					(string) $system,
					0.6
				);
				if ( is_string( $res ) && strlen( trim( $res ) ) > 20 ) {
					$this->last_provider = 'gabochie-ai-engine';
					return trim( $res );
				}
			}
		}

		if ( class_exists( 'GHBC_LLM' ) ) {
			$llm = GHBC_LLM::instance();
			if ( $llm->is_ready() ) {
				$res = $llm->complete(
					(string) $system,
					array(
						array(
							'role'    => 'user',
							'content' => $prompt,
						),
					)
				);
				if ( is_array( $res ) && ! empty( $res['success'] ) && '' !== trim( (string) ( $res['text'] ?? '' ) ) ) {
					$this->last_provider = 'ghbc-llm';
					return trim( (string) $res['text'] );
				}
			}
		}

		$this->last_provider = '';
		return '';
	}

	/** AJAX: Test AI connection using the saved settings. */
	public function ajax_test_connection() {
		check_ajax_referer( 'gab_ajax' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}

		$key = (string) get_option( self::KEY_OPT, '' );
		if ( '' === $key ) {
			$msg = 'No API key saved in Auto-Blog Settings yet. ';
			if ( function_exists( 'class_exists' ) && class_exists( 'GHBC_LLM' ) && GHBC_LLM::instance()->is_ready() ) {
				wp_send_json_success(
					array(
						'message' => $msg . 'GHBC LLM is already configured and will be used.',
						'path'    => 'ghbc-llm',
					)
				);
			}
			if ( class_exists( 'Gabochie_AI_Engine' ) && Gabochie_AI_Engine::instance()->is_configured() ) {
				wp_send_json_success(
					array(
						'message' => $msg . 'Gabochie AI Engine is already configured and will be used.',
						'path'    => 'gabochie-ai-engine',
					)
				);
			}
			wp_send_json_error( array( 'message' => $msg . 'Configure an API key below (or in GHBC / Gabochie SaaS Tools) before running the executor.' ) );
		}

		$provider = (string) get_option( self::PROVIDER_OPT, 'gemini' );
		$model    = (string) get_option( self::MODEL_OPT, 'gemini-flash-latest' );
		$text     = $this->call_provider( $provider, 'You are a helpful assistant. Reply with exactly: OK', 'Reply with exactly the single word: OK', $model );

		if ( '' !== $text ) {
			wp_send_json_success(
				array(
					'message' => sprintf( 'Connection succeeded via %s (%s). Reply: %s', $provider, $model, substr( $text, 0, 60 ) ),
					'path'    => $provider,
				)
			);
		}

		$errs = (array) get_option( self::ERRORS_OPT, array() );
		$last = empty( $errs ) ? '' : (string) end( $errs )['error'];
		wp_send_json_error(
			array( 'message' => 'Connection failed on ' . $provider . '/' . $model . ( '' !== $last ? ' - last error: ' . $last : '' ) )
		);
	}

	/** @param string $provider One of: gemini, claude, or openai-compatible. */
	private function call_provider( $provider, $system, $prompt, $model ) {
		switch ( $provider ) {
			case 'claude':
				return $this->claude_chat( $system, $prompt, $model );
			case 'gemini':
				return $this->gemini_chat( $system, $prompt, $model );
			default:
				return $this->openai_compatible_chat( $system, $prompt, $model );
		}
	}

	private function gemini_chat( $system, $prompt, $model ) {
		$key = (string) get_option( self::KEY_OPT, '' );
		if ( '' === $key ) {
			return '';
		}
		$url  = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent?key=' . rawurlencode( $key );
		$body = array(
			'systemInstruction' => array( 'parts' => array( array( 'text' => $system ) ) ),
			'contents'          => array( array( 'parts' => array( array( 'text' => $prompt ) ) ) ),
			'generationConfig'  => array(
				'temperature'     => 0.6,
				'maxOutputTokens' => 8192,
			),
		);

		// Retry-with-backoff on 5xx/429/transport: free-tier Gemini is frequently
		// saturated ("high demand") and spikes are transient.
		$last_error = '';
		for ( $attempt = 1; $attempt <= 4; $attempt++ ) {
			$res = $this->http_post_json( $url, $body, array() );
			if ( ! $res ) {
				$last_error = 'gemini transport error';
				if ( $attempt < 4 ) {
					sleep( $attempt * 6 );
				}
				continue;
			}
			$data = $res['data'];
			$code = $res['code'];
			if ( $code >= 500 || 429 === $code || 0 === $code ) {
				$last_error = 'gemini HTTP ' . $code . ': ' . ( isset( $data['error']['message'] ) ? (string) $data['error']['message'] : 'unknown' );
				if ( $attempt < 4 ) {
					sleep( $attempt * 6 );
				}
				continue;
			}
			if ( $code >= 400 ) {
				$this->log_error( 'gemini HTTP ' . $code . ': ' . ( isset( $data['error']['message'] ) ? (string) $data['error']['message'] : 'unknown' ) );
				return '';
			}
			if ( empty( $data['candidates'][0]['content']['parts'] ) ) {
				$reason = isset( $data['promptFeedback']['blockReason'] ) ? 'blocked:' . $data['promptFeedback']['blockReason'] : 'empty-candidates';
				$this->log_error( 'gemini: ' . $reason );
				return '';
			}
			$text = '';
			foreach ( $data['candidates'][0]['content']['parts'] as $part ) {
				if ( isset( $part['text'] ) ) {
					$text .= (string) $part['text'];
				}
			}
			return trim( $text );
		}
		$this->log_error( $last_error . ' (retries exhausted)' );
		return '';
	}

	private function claude_chat( $system, $prompt, $model ) {
		$key = (string) get_option( self::KEY_OPT, '' );
		if ( '' === $key ) {
			return '';
		}
		$body    = array(
			'model'       => $model,
			'max_tokens'  => 4096,
			'temperature' => 0.6,
			'system'      => (string) $system,
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
		);
		$headers = array(
			'x-api-key'         => $key,
			'anthropic-version' => '2023-06-01',
		);
		$res     = $this->http_post_json( 'https://api.anthropic.com/v1/messages', $body, $headers );
		if ( ! $res ) {
			return '';
		}
		$data = $res['data'];
		if ( $res['code'] >= 400 ) {
			$this->log_error( 'claude HTTP ' . $res['code'] . ': ' . ( isset( $data['error']['message'] ) ? (string) $data['error']['message'] : 'unknown' ) );
			return '';
		}
		if ( empty( $data['content'][0]['text'] ) ) {
			$this->log_error( 'claude: empty content block' );
			return '';
		}
		return trim( (string) $data['content'][0]['text'] );
	}

	private function openai_compatible_chat( $system, $prompt, $model ) {
		$key = (string) get_option( self::KEY_OPT, '' );
		if ( '' === $key ) {
			return '';
		}
		$base    = untrailingslashit( (string) get_option( self::BASE_URL_OPT, 'https://api.openai.com/v1' ) );
		$body    = array(
			'model'       => $model,
			'max_tokens'  => 4096,
			'temperature' => 0.6,
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => (string) $system,
				),
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
		);
		$headers = array( 'authorization' => 'Bearer ' . $key );
		$res     = $this->http_post_json( $base . '/chat/completions', $body, $headers );
		if ( ! $res ) {
			return '';
		}
		$data = $res['data'];
		if ( $res['code'] >= 400 ) {
			$this->log_error( 'openai-compatible HTTP ' . $res['code'] . ': ' . ( isset( $data['error']['message'] ) ? (string) $data['error']['message'] : 'unknown' ) );
			return '';
		}
		if ( empty( $data['choices'][0]['message']['content'] ) ) {
			$this->log_error( 'openai-compatible: empty choices content' );
			return '';
		}
		return trim( (string) $data['choices'][0]['message']['content'] );
	}

	/**
	 * Shared JSON POST helper.
	 *
	 * @return array|false ['code' => int, 'data' => array] or false on transport error.
	 */
	private function http_post_json( $url, $body, $headers ) {
		$headers  = wp_parse_args(
			$headers,
			array( 'content-type' => 'application/json' )
		);
		$response = wp_remote_post(
			$url,
			array(
				'headers'     => $headers,
				'body'        => wp_json_encode( $body ),
				'timeout'     => 120,
				'httpversion' => '1.1',
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->log_error( $response->get_error_message() );
			return false;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return array(
			'code' => (int) wp_remote_retrieve_response_code( $response ),
			'data' => is_array( $data ) ? $data : array(),
		);
	}

	/** Never log secrets - messages only. */
	private function log_error( $msg ) {
		$errors   = (array) get_option( self::ERRORS_OPT, array() );
		$errors[] = array(
			'time'  => current_time( 'mysql' ),
			'error' => substr( $msg, 0, 300 ),
		);
		update_option( self::ERRORS_OPT, array_slice( $errors, -10 ) );
	}

	/** @return array<int,array{time:string,error:string}> */
	public function recent_errors() {
		return (array) get_option( self::ERRORS_OPT, array() );
	}
}

Gab_Auto_AI::instance();
