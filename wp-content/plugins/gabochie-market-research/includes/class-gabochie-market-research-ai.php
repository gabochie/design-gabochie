<?php
/**
 * Gabochie Market Research — AI memo generator.
 *
 * Collects signals from four internal sources (community questions, site search
 * queries, property enquiries, and content coverage), scores them against a
 * fixed niche dictionary, and uses the established 3-tier AI chain to produce
 * a weekly market memo.  The memo contains ranked opportunity proposals that
 * surface in the approval inbox.
 *
 * @package Gabochie_Market_Research
 */

defined( 'ABSPATH' ) || exit;

/**
 * AI memo generator.
 */
class Gabochie_Market_Research_AI {

	/**
	 * @var string Last provider name.
	 */
	private $last_source = '';

	/**
	 * @return string
	 */
	public function last_source() {
		return $this->last_source;
	}

	/**
	 * Collect all signals, score niches, and return a raw memo string.
	 *
	 * @param array $signals Associative array from Gabochie_Market_Research::collect_all_signals().
	 * @return string Raw memo text (or empty on failure).
	 */
	public function build_memo( array $signals ) {
		$context = $this->format_signals( $signals );
		$system  = $this->system_prompt();
		$prompt  = $this->memo_prompt( $context );
		$json    = $this->complete( $system, $prompt );
		return $json;
	}

	/**
	 * Extract proposals from raw LLM JSON and upsert into gmr_proposals.
	 *
	 * @param string $raw_json LLM output.
	 * @return int Number of proposals created.
	 */
	public function extract_proposals( $raw_json ) {
		$parsed = json_decode( $raw_json, true );
		if ( ! is_array( $parsed ) || empty( $parsed['proposals'] ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $parsed['proposals'] as $row ) {
			if ( empty( $row['niche'] ) || empty( $row['title'] ) ) {
				continue;
			}
			$niche_key = sanitize_title( $row['niche'] );
			if ( empty( Gabochie_Market_Research::NICHE_SCORES[ $niche_key ] ) ) {
				$niche_key = 'other';
			}
			Gabochie_Market_Research::upsert_proposal(
				$row['title'],
				$niche_key,
				$row['evidence'] ?? '',
				(int) ( $row['opportunity_score'] ?? 50 ),
				$row['product_type'] ?? 'unknown',
				(float) ( $row['suggested_price_ghes'] ?? 0 ),
				$row['audience'] ?? '',
				$row['rationale'] ?? ''
			);
			++$count;
		}
		return $count;
	}

	/**
	 * System prompt for the memo generator.
	 *
	 * @return string
	 */
	private function system_prompt() {
		return 'You are the Demand Intelligence Engine for Gabochie Design — '
		. 'a Ghana property + development platform.  You turn internal demand '
		. 'signals (community questions, site searches, property enquiries, '
		. 'content coverage gaps) into a ranked weekly market memo.  '
		. 'Rules:\n'
		. '- Only propose niches where there is real observed demand (signal count > 0).\n'
		. '- Never invent signal counts, figures or data.\n'
		. '- scored is 0-100 based on signal strength + gap + monetisation potential.\n'
		. '- Return STRICT JSON with a top-level "proposals" array and a "summary" string.\n'
		. '- proposals = [{niche, title, evidence, opportunity_score, product_type, '
		. 'suggested_price_ghes, audience, rationale}].\n'
		. '- product_type is one of: guide, diagnostic, tool, service, tier, newsletter, course.\n'
		. '- If there are no actionable signals, return {"proposals":[], "summary":"No actionable opportunities this week."}';
	}

	/**
	 * Build the user prompt with formatted signals.
	 *
	 * @param string $context Formatted signal text.
	 * @return string
	 */
	private function memo_prompt( $context ) {
		$niches = '';
		foreach ( Gabochie_Market_Research::NICHE_SCORES as $key => $def ) {
			$niches .= "- {$key}: {$def['label']}\n";
		}
		return "=== WEEKLY DEMAND SIGNALS ===\n{$context}\n\n"
		. "=== NICHES WE COVER ===\n{$niches}\n"
		. 'Analyse the signals above.  For each niche with real demand, produce a proposal.  '
		. 'Return the result as strict JSON matching the schema in your instructions.';
	}

	/**
	 * Format the raw signals array into readable text for the LLM.
	 *
	 * @param array $signals Output of collect_all_signals().
	 * @return string
	 */
	private function format_signals( array $signals ) {
		$parts = array();
		// Community questions.
		if ( ! empty( $signals['community'] ) ) {
			$lines = array( 'COMMUNITY QUESTIONS (recent):' );
			foreach ( $signals['community'] as $q ) {
				$lines[] = sprintf(
					'  [%s] %s (tags: %s)',
					$q['date'],
					$q['title'],
					 implode( ', ', $q['tags'] )
				);
			}
			$parts[] = implode( "\n", $lines );
		}
		// Search queries.
		if ( ! empty( $signals['searches'] ) ) {
			$lines = array( 'SITE SEARCH QUERIES (last 30 days):' );
			foreach ( $signals['searches'] as $s ) {
				$lines[] = sprintf(
					'  "%s" (%d hits)',
					$s['term'],
					(int) $s['hits']
				);
			}
			$parts[] = implode( "\n", $lines );
		}
		// Enquiries.
		if ( ! empty( $signals['enquiries'] ) ) {
			$lines = array( 'PROPERTY ENQUIRIES (recent):' );
			foreach ( $signals['enquiries'] as $e ) {
				$lines[] = sprintf(
					'  [%s] %s — %s',
					$e['date'],
					$e['title'],
					$e['reason']
				);
			}
			$parts[] = implode( "\n", $lines );
		}
		// Content gaps.
		if ( ! empty( $signals['gaps'] ) ) {
			$lines = array( 'CONTENT COVERAGE (posts per niche):' );
			foreach ( $signals['gaps'] as $gap ) {
				$lines[] = sprintf(
					'  %s: %d posts (last published: %s)',
					$gap['niche'],
					(int) $gap['count'],
					$gap['latest'] ?? 'none'
				);
			}
			$parts[] = implode( "\n", $lines );
		}
		// Contact stats.
		if ( ! empty( $signals['contacts'] ) ) {
			$c = $signals['contacts'];
			$parts[] = sprintf(
				'CONTACTS: total=%d, subscribed=%d, engaged_30d=%d',
				(int) $c['total'],
				(int) $c['subscribed'],
				(int) $c['engaged_30d']
			);
		}
		return empty( $parts ) ? 'No signals collected yet.' : implode( "\n\n", $parts );
	}

	/**
	 * Run the 3-tier AI chain (auto-blog -> GhBC -> builtin Gemini).
	 *
	 * @param  string $system
	 * @param  string $prompt
	 * @return string Raw output or ''.
	 */
	public function complete( $system, $prompt ) {
		// Tier 1: gabochie-auto-blog adapter.
		if ( class_exists( 'Gab_Auto_AI' ) ) {
			$out = Gab_Auto_AI::instance()->complete( $system, $prompt );
			if ( is_string( $out ) && '' !== trim( $out ) ) {
				$this->last_source = 'auto-blog';
				return $out;
			}
		}
		// Tier 2: GhBC_LLM.
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
		// Tier 3: builtin Gemini direct.
		$out = $this->gemini_direct( $system, $prompt );
		if ( '' !== $out ) {
			$this->last_source = 'builtin:gemini';
		}
		return $out;
	}

	/**
	 * Direct Gemini call with retry-with-backoff.
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
		$url   = 'https://generativelanguage.googleapis.com/v1beta/models/'
			. rawurlencode( $model ) . ':generateContent?key=' . rawurlencode( $key );
		$body  = array(
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
				'temperature'      => 0.5,
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
				Gabochie_Market_Research::log_error( 'ai: gemini transport: ' . $resp->get_error_message() );
			} else {
				$code = (int) wp_remote_retrieve_response_code( $resp );
				if ( $code >= 200 && $code < 300 ) {
					$parsed = json_decode( wp_remote_retrieve_body( $resp ), true );
					if ( ! empty( $parsed['candidates'][0]['content']['parts'][0]['text'] ) ) {
						return (string) $parsed['candidates'][0]['content']['parts'][0]['text'];
					}
				} elseif ( 429 === $code || $code >= 500 ) {
					Gabochie_Market_Research::log_error( "ai: gemini HTTP {$code} (attempt {$i})" );
				} else {
					Gabochie_Market_Research::log_error( "ai: gemini HTTP {$code}" );
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
