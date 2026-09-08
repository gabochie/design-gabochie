<?php
/**
 * Gabochie News Portal — AI brief helper.
 *
 * Writes the "Gabochie take" for a curated news item. Delegates through the
 * house provider chain first (auto-blog's Gab_Auto_AI, then GHBC_LLM) and
 * falls back to a builtin Gemini caller reading the shared ghbc_llm_key. The
 * note always lands as a DRAFT in `_gnp_take` + `_gnp_take_status=draft` — an
 * editor approves it before it renders on the site. Never publishes alone.
 *
 * @package Gabochie_News_Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * News brief generator.
 */
class Gabochie_News_AI {

	const OPT_MODEL = 'gnp_ai_model';

	/** @var Gabochie_News_AI|null */
	private static $instance = null;

	/** @var string */
	private $last_source = '';

	/** @return Gabochie_News_AI */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/** @return string e.g. 'auto-blog:builtin:gemini' */
	public function last_source() {
		return $this->last_source;
	}

	/**
	 * Write the Gabochie take for a news item (saved as draft meta).
	 *
	 * @param int $post_id
	 * @return string|false Take HTML (wp_kses_post clean), or false on failure.
	 */
	public function draft_take( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || Gabochie_News_Portal::CPT !== $post->post_type ) {
			return false;
		}

		$source  = (string) get_post_meta( $post->ID, Gabochie_News_Portal::META_SOURCE_TXT, true );
		$excerpt = trim( (string) $post->post_excerpt );
		if ( '' === $excerpt ) {
			$excerpt = wp_strip_all_tags( (string) $post->post_content );
		}
		$category = $this->category_name( $post->ID );
		if ( '' === $source ) {
			$source = (string) get_post_meta( $post->ID, Gabochie_News_Portal::META_SOURCE, true );
		}

		$system = 'Act as the Gabochie Design news desk covering Ghana\'s AEC, built environment and property sectors. Write concise, factual, cost-aware briefs for Ghanaian property owners and developers. Doctrine: never invent figures, dates, names or claims; only restate what the linked source says. Keep the brief to two short sections and no closing sign-off.';
		$prompt = 'Produce a short brief for this curated headline from ' . $source . ".\n\n"
			. 'Headline: ' . $post->post_title . "\n"
			. 'Excerpt: ' . $excerpt . "\n"
			. 'Category: ' . $category . "\n\n"
			. "Format exactly:\n"
			. "<b>Summary</b>\n"
			. "one short paragraph (2-3 sentences) — what happened, plainly, citing only what the source says\n"
			. "<b>Gabochie take</b>\n"
			. 'one short paragraph (2-3 sentences) — what it means for the cost, timeline or decisions of a Ghanaian building owner or developer, or what to do next. No slogans, no invented figures. If the story has no obvious building angle, say why it still matters to someone building in Ghana.';

		$text = $this->complete( $system, $prompt );
		if ( '' === trim( (string) $text ) ) {
			Gabochie_News_Portal::instance()->log_error( 'take failed for post ' . $post->ID . ' — all AI backends returned empty.' );
			return false;
		}

		$clean = wp_kses_post( (string) $text );
		update_post_meta( $post->ID, Gabochie_News_Portal::META_TAKE, $clean );
		update_post_meta( $post->ID, Gabochie_News_Portal::META_TAKE_STAT, Gabochie_News_Portal::TAKE_DRAFT );
		update_post_meta( $post->ID, Gabochie_News_Portal::META_AI_SRC, $this->last_source );
		return $clean;
	}

	/**
	 * Set the editorial take status (draft/approved/rejected).
	 *
	 * @param int    $post_id
	 * @param string $status
	 */
	public function set_take_status( $post_id, $status ) {
		if ( ! in_array( $status, array( Gabochie_News_Portal::TAKE_DRAFT, Gabochie_News_Portal::TAKE_APPROVED, Gabochie_News_Portal::TAKE_REJECTED ), true ) ) {
			return;
		}
		update_post_meta( (int) $post_id, Gabochie_News_Portal::META_TAKE_STAT, $status );
	}

	/**
	 * Approved take for rendering, or ''.
	 *
	 * @param int $post_id
	 * @return string
	 */
	public function approved_take( $post_id ) {
		if ( Gabochie_News_Portal::TAKE_APPROVED !== (string) get_post_meta( $post_id, Gabochie_News_Portal::META_TAKE_STAT, true ) ) {
			return '';
		}
		return (string) get_post_meta( $post_id, Gabochie_News_Portal::META_TAKE, true );
	}

	/**
	 * Run a completion through the house chain.
	 *
	 * @param string $system
	 * @param string $prompt
	 * @return string Trimmed text, or '' when every backend failed.
	 */
	public function complete( $system, $prompt ) {
		if ( class_exists( 'Gab_Auto_AI' ) ) {
			$res = Gab_Auto_AI::instance()->complete( $system, $prompt );
			if ( '' !== trim( (string) $res ) ) {
				$this->last_source = 'auto-blog:' . Gab_Auto_AI::instance()->last_provider();
				return trim( (string) $res );
			}
		}

		if ( class_exists( 'GHBC_LLM' ) && GHBC_LLM::instance()->is_ready() ) {
			$res = GHBC_LLM::instance()->complete(
				(string) $system,
				array(
					array(
						'role'    => 'user',
						'content' => $prompt,
					),
				)
			);
			if ( is_array( $res ) && ! empty( $res['success'] ) && '' !== trim( (string) ( $res['text'] ?? '' ) ) ) {
				$this->last_source = 'ghbc-llm';
				return trim( (string) $res['text'] );
			}
		}

		$text = $this->gemini_direct( $system, $prompt );
		if ( '' !== trim( (string) $text ) ) {
			$this->last_source = 'builtin:gemini';
			return trim( (string) $text );
		}
		$this->last_source = '';
		return '';
	}

	/**
	 * Builtin Gemini completion (shares the ghbc_llm_key, retry-with-backoff).
	 *
	 * @param string $system
	 * @param string $prompt
	 * @return string
	 */
	private function gemini_direct( $system, $prompt ) {
		$key = (string) get_option( 'ghbc_llm_key', '' );
		if ( '' === $key ) {
			return '';
		}
		$model = (string) get_option( self::OPT_MODEL, 'gemini-3.5-flash' );
		$url   = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent?key=' . rawurlencode( $key );
		$body  = array(
			'systemInstruction' => array( 'parts' => array( array( 'text' => $system ) ) ),
			'contents'          => array( array( 'parts' => array( array( 'text' => $prompt ) ) ) ),
			'generationConfig'  => array(
				'temperature'     => 0.5,
				'maxOutputTokens' => 1024,
			),
		);

		$last_error = '';
		for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
			$res = $this->http_post_json( $url, $body );
			if ( ! $res ) {
				$last_error = 'gemini transport error';
				if ( $attempt < 3 ) {
					sleep( $attempt * 5 );
				}
				continue;
			}
			if ( $res['code'] >= 500 || 429 === $res['code'] || 0 === $res['code'] ) {
				$last_error = 'gemini HTTP ' . $res['code'] . ': ' . ( isset( $res['data']['error']['message'] ) ? (string) $res['data']['error']['message'] : 'unknown' );
				if ( $attempt < 3 ) {
					sleep( $attempt * 5 );
				}
				continue;
			}
			if ( $res['code'] >= 400 ) {
				$this->log_err( 'gemini HTTP ' . $res['code'] . ': ' . ( isset( $res['data']['error']['message'] ) ? (string) $res['data']['error']['message'] : 'unknown' ) );
				return '';
			}
			if ( empty( $res['data']['candidates'][0]['content']['parts'] ) ) {
				$reason = isset( $res['data']['promptFeedback']['blockReason'] ) ? 'blocked:' . $res['data']['promptFeedback']['blockReason'] : 'empty-candidates';
				$this->log_err( 'gemini: ' . $reason );
				return '';
			}
			$text = '';
			foreach ( $res['data']['candidates'][0]['content']['parts'] as $part ) {
				if ( isset( $part['text'] ) ) {
					$text .= (string) $part['text'];
				}
			}
			return trim( $text );
		}
		$this->log_err( $last_error . ' (retries exhausted)' );
		return '';
	}

	/**
	 * @param string $url
	 * @param array  $body
	 * @return array|false
	 */
	private function http_post_json( $url, $body ) {
		$response = wp_remote_post(
			$url,
			array(
				'headers'     => array( 'content-type' => 'application/json' ),
				'body'        => wp_json_encode( $body ),
				'timeout'     => 90,
				'httpversion' => '1.1',
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->log_err( $response->get_error_message() );
			return false;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return array(
			'code' => (int) wp_remote_retrieve_response_code( $response ),
			'data' => is_array( $data ) ? $data : array(),
		);
	}

	/** @param string $msg */
	private function log_err( $msg ) {
		Gabochie_News_Portal::instance()->log_error( $msg );
	}

	/**
	 * Readable category label for a news item.
	 *
	 * @param int $post_id
	 * @return string
	 */
	private function category_name( $post_id ) {
		$slug = (string) get_post_meta( $post_id, Gabochie_News_Portal::META_CATEGORY, true );
		$term = get_term_by( 'slug', $slug, Gabochie_News_Portal::TAX );
		return $term ? (string) $term->name : 'Development';
	}
}
