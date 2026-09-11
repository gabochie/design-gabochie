<?php
/**
 * Gabochie Market Research — LLM memo generator.
 *
 * Turns the scored niche matrix produced by Gabochie_Market_Research::collect()
 * into a weekly market memo: an executive summary plus 3-5 concrete product
 * opportunities (title, product type, price hint, audience, rationale). Uses
 * the same 3-tier completion chain as the marketplace intelligence reports
 * (auto-blog → GHBC_LLM → builtin Gemini direct with retry-with-backoff).
 *
 * Returns STRICT JSON — parsed + validated by the caller.
 *
 * @package Gabochie_Market_Research
 */

defined( 'ABSPATH' ) || exit;

/**
 * Market memo generator.
 */
class Gabochie_Market_Research_AI {


	/**
	 * @var Gabochie_Market_Research_AI|null
	 */
	private static $instance = null;

	/**
	 * @var string Last provider that succeeded ('' if none).
	 */
	private $last_source = '';

	/**
	 * @return Gabochie_Market_Research_AI
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
	}

	/**
	 * @return string
	 */
	public function last_source() {
		return $this->last_source;
	}

	/**
	 * Generate the memo JSON for the given scored matrix.
	 *
	 * @param  array  $rows       Summarized niche lines.
	 * @param  bool   $json_forced Skip the prose-capable chain and call the
	 *                             JSON-mime provider directly.
	 * @return string
	 */
	public function generate_memo( array $rows, $json_forced = false ) {
		$system = 'You are the market-research strategist for Gabochie Design, a Ghana '
			. 'development intelligence platform (property + construction). Each week you turn a '
			. 'scored opportunity matrix into a decision-useful memo for the founder. '
			. 'Doctrine: use ONLY the provided signal counts. Never invent demand data, market '
			. 'sizes or revenue figures. Price hints come from the matrix. '
			. 'Return STRICT JSON only — no markdown code fences, no commentary outside the object.';

		$prompt = "Weekly opportunity matrix (window: 30 days):\n\n"
			. implode( "\n", $rows ) . "\n\n"
			. "Return exactly one raw JSON object with this shape and nothing else:\n"
			. '{"summary": "one paragraph, top 3 insights + recommended focus for the week", '
			. '"opportunities": [{"niche": "<slug from above>", "title": "<product/offer title>", '
			. '"product_type": "guide|diagnostic|tool|service|membership", '
			. '"pricing_hint": "<GHS or Free>", "audience": "<who>", '
			. '"opportunity_score": <0-100, must reflect the matrix score>, '
			. '"rationale": "<2-3 sentences using the signal counts>"}]}. '
			. 'Pick the 3-5 highest-signal niches; prefer ones with questions or enquiries over '
			. 'empty ones. Under-developed (low coverage) but high-signal niches get bonus '
			. 'weight. Each opportunity title must be a sellable offer, not a topic. '
			. 'Do not wrap the object in markdown; do not add any text before or after it.';

		if ( $json_forced ) {
			return $this->gemini_direct( $system, $prompt );
		}

		return $this->complete( $system, $prompt );
	}

	/**
	 * Run the 3-tier completion chain and return the raw text.
	 *
	 * @param  string $system
	 * @param  string $prompt
	 * @return string
	 */
	public function complete( $system, $prompt ) {
		if ( class_exists( 'Gab_Auto_AI' ) ) {
			$out = Gab_Auto_AI::instance()->complete( $system, $prompt );
			if ( is_string( $out ) && '' !== trim( $out ) ) {
				$this->last_source = 'auto-blog';
				return $out;
			}
		}
		if ( class_exists( 'GHBC_LLM' ) ) {
			$llm = GHBC_LLM::instance();
			if ( $llm->is_ready() ) {
				$out = $llm->complete(
					$system,
					array(
						array(
							'role'    => 'user',
							'content' => $prompt,
						),
					)
				);
				if ( is_string( $out ) && '' !== trim( $out ) ) {
					$this->last_source = 'ghbc-llm';
					return $out;
				}
			}
		}
		$out = $this->gemini_direct( $system, $prompt );
		if ( '' !== $out ) {
			$this->last_source = 'builtin:gemini';
		}
		return $out;
	}

	/**
	 * Direct Google Generative Language call with retry-with-backoff.
	 *
	 * @param  string $system
	 * @param  string $prompt
	 * @return string
	 */
	private function gemini_direct( $system, $prompt ) {
		$key = (string) get_option( 'ghbc_llm_key', '' );
		if ( '' === $key ) {
			return '';
		}
		$model = (string) get_option( Gabochie_Market_Research::OPT_AI_MODEL, 'gemini-3.5-flash' );
		$url   = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent?key=' . rawurlencode( $key );

		$body = array(
			'systemInstruction' => array(
				'parts' => array( array( 'text' => $system ) ),
			),
			'contents'          => array(
				array(
					'role'  => 'user',
					'parts' => array( array( 'text' => $prompt ) ),
				),
			),
			'generationConfig'  => array(
				'temperature'      => 0.4,
				'maxOutputTokens'  => 2048,
				'responseMimeType' => 'application/json',
			),
		);

		$attempts = 3;
		for ( $i = 1; $i <= $attempts; $i++ ) {
			$resp = wp_remote_post(
				$url,
				array(
					'headers' => array( 'Content-Type' => 'application/json' ),
					'body'    => wp_json_encode( $body ),
					'timeout' => 90,
				)
			);
			if ( is_wp_error( $resp ) ) {
				Gabochie_Market_Research::log_error( 'memo: gemini transport: ' . $resp->get_error_message() );
			} else {
				$code = (int) wp_remote_retrieve_response_code( $resp );
				if ( $code >= 200 && $code < 300 ) {
					$parsed = json_decode( wp_remote_retrieve_body( $resp ), true );
					if ( ! empty( $parsed['candidates'][0]['content']['parts'][0]['text'] ) ) {
						return (string) $parsed['candidates'][0]['content']['parts'][0]['text'];
					}
				} elseif ( 429 === $code || $code >= 500 ) {
					Gabochie_Market_Research::log_error( "memo: gemini HTTP {$code} (attempt {$i})" );
				} else {
					Gabochie_Market_Research::log_error( "memo: gemini HTTP {$code}" );
					return '';
				}
			}
			if ( $i < $attempts ) {
				sleep( $i * 5 );
			}
		}
		return '';
	}
}
