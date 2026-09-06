<?php
/**
 * Topic-bank executor for Gabochie Auto-Blog.
 *
 * Drives the editorial calendar from data/topic-bank.json: on each scheduled
 * run it processes the post whose bank date matches today (Tue/Thu/Sat
 * cadence), generates it through the AI chain, attaches Yoast metadata,
 * category, featured image and bank lineage, runs a QA hook, and only then
 * publishes - on the day the calendar says the post is due. Drafts generated
 * ahead of their date are held and promoted by the cron on the due date.
 */

defined( 'ABSPATH' ) || exit;

class Gab_Auto_Executor {

	const META_BANK_ID   = '_gab_bank_id';
	const META_BANK_DATE = '_gab_bank_date';
	const META_BANK_SLOT = '_gab_bank_slot';
	const META_QA_STATUS = '_gab_qa_status';
	const META_QA_ISSUES = '_gab_qa_issues';

	const HOOK = 'gab_bank_publish';

	private static $instance = null;

	/**
	 * @return Gab_Auto_Executor
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( self::HOOK, array( $this, 'run_due' ) );
		add_action( 'wp_ajax_gab_bank_run', array( $this, 'ajax_bank_run' ) );
	}

	/** Cron entry point: process today's bank slot if any. */
	public function run_due() {
		if ( '1' !== get_option( 'gab_enabled', '0' ) || 'bank' !== get_option( 'gab_mode', 'bank' ) ) {
			return array( 'skipped' => 'disabled' );
		}
		$result = $this->process_date( null );
		update_option( 'gab_last_bank_run', current_time( 'mysql' ) );
		return $result;
	}

	/**
	 * Generate (or promote) the bank post for a given date.
	 *
	 * @param string|null $date         'Y-m-d'. Null = today.
	 * @param string|null $force_status 'publish'|'draft'|null to override scheduling rule.
	 * @return array Result summary for logs/CLI/admin.
	 */
	public function process_date( $date = null, $force_status = null ) {
		$bank  = Gab_Auto_Bank::instance();
		$today = current_time( 'Y-m-d' );
		if ( null === $date ) {
			$date = $today;
		}
		if ( ! $bank->load() ) {
			return array( 'error' => 'bank-unavailable', 'file' => $bank->data_file() );
		}

		$bank_post = $bank->post_for_date( $date );
		if ( ! $bank_post ) {
			return array(
				'skipped' => 'no-bank-post',
				'date'    => $date,
				'next'    => $bank->next_date(),
			);
		}

		$existing = $this->existing_post( $bank_post['id'] );
		if ( $existing ) {
			if ( 'publish' === get_post_status( $existing ) ) {
				return array( 'skipped' => 'already-published', 'post_id' => $existing );
			}
			// A pre-generated draft is promoted only on its due date unless force='draft' (hold).
			if ( $date === $today && 'draft' !== $force_status ) {
				return $this->maybe_publish_existing( $existing, $bank_post );
			}
			return array( 'skipped' => 'held-draft', 'post_id' => $existing, 'status' => get_post_status( $existing ) );
		}

		return $this->generate_from_bank( $bank_post, $date, $force_status );
	}

	/** Generate a post from a bank entry, apply lineage/SEO, QA, then publish when due. */
	public function generate_from_bank( $bank_post, $date, $force_status = null ) {
		$today = current_time( 'Y-m-d' );
		$ai    = Gab_Auto_AI::instance();
		$text  = $ai->complete( $this->system_prompt(), $this->bank_prompt( $bank_post ) );

		if ( '' === trim( (string) $text ) ) {
			return array( 'error' => 'ai-unavailable', 'provider' => $ai->last_provider() );
		}

		$parsed = $this->parse_response( $text, $bank_post );

		$content = wp_kses_post( $parsed['content'] );
		if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
			return array( 'error' => 'empty-content' );
		}

		$category_term = $this->category_term_id( $bank_post['category'] );
		$author        = (int) get_option( 'gab_author', 2 );
		if ( 0 === $author ) {
			$author = 2;
		}
		$tags = array( 'Ghana', (string) $bank_post['cluster'], (string) $bank_post['category'] );

		$qa = $this->qa_check( $parsed['title'], $content, $bank_post );

		$time        = $this->scheduled_time();
		$publish_now = ( $date === $today ) && $qa['pass']
			&& ( null === $force_status || 'publish' === $force_status );

		$postarr = array(
			'post_title'    => $parsed['title'],
			'post_content'  => $content,
			'post_excerpt'  => $parsed['excerpt'],
			'post_status'   => $publish_now ? 'publish' : 'draft',
			'post_author'   => $author,
			'post_category' => $category_term ? array( $category_term ) : array(),
			'tags_input'    => array_values( array_filter( array_unique( $tags ) ) ),
			'post_date'     => $date . ' ' . $time,
			'post_date_gmt' => get_gmt_from_date( $date . ' ' . $time ),
		);
		$post_id = wp_insert_post( $postarr, true );
		if ( is_wp_error( $post_id ) ) {
			return array( 'error' => $post_id->get_error_message() );
		}

		$this->store_meta( $post_id, $bank_post, $qa );
		$this->set_thumbnail( $post_id, $bank_post );
		$this->set_yoast_meta( $post_id, $bank_post, $parsed );

		$result = array(
			'status'   => $postarr['post_status'],
			'post_id'  => $post_id,
			'qa'       => $qa,
			'provider' => $ai->last_provider(),
		);
		if ( ! $publish_now && ! empty( $qa['reasons'] ) ) {
			$result['held_reasons'] = $qa['reasons'];
		}
		$result['edit_url'] = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
		return $result;
	}

	/** QA hook: quality gate that must pass before a scheduled post publishes. */
	public function qa_check( $title, $content, $bank_post ) {
		$reasons  = array();
		$warnings = array();

		$text  = (string) wp_strip_all_tags( $content );
		$words = count( preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) );
		if ( $words < 900 ) {
			$reasons[] = 'short:' . $words;
		} elseif ( $words < 1200 ) {
			$warnings[] = 'short:' . $words;
		}

		$h2 = preg_match_all( '/<h2\b/i', $content, $unused );
		if ( $h2 < 3 ) {
			$reasons[] = 'h2:' . $h2;
		}
		$h2id = preg_match_all( '/<h2[^>]*\bid\s*=\s*["\']/i', $content, $unused );
		if ( $h2id < 3 ) {
			$reasons[] = 'h2ids:' . $h2id;
		}
		$h3q = preg_match_all( '/<h3[^>]*>[^<]*\?[^<]*<\/h3>/i', $content, $unused );
		if ( $h3q < 3 ) {
			$reasons[] = 'faq-h3:' . $h3q;
		}

		$hub = isset( $bank_post['hub']['url'] ) ? (string) $bank_post['hub']['url'] : '';
		if ( '' !== $hub && false === stripos( $content, $hub ) ) {
			$reasons[] = 'hub-link';
		}

		$cta_urls = isset( $bank_post['cta']['urls'] ) ? (array) $bank_post['cta']['urls'] : array();
		$cta_hit  = false;
		foreach ( $cta_urls as $cu ) {
			if ( '' !== (string) $cu && false !== stripos( $content, (string) $cu ) ) {
				$cta_hit = true;
				break;
			}
		}
		if ( ! $cta_hit ) {
			$reasons[] = 'cta-link';
		}

		$cites  = isset( $bank_post['citations'] ) ? (array) $bank_post['citations'] : array();
		$linked = 0;
		foreach ( $cites as $cite ) {
			if ( '' !== (string) $cite && false !== stripos( $content, (string) $cite ) ) {
				++$linked;
			}
		}
		$need = min( 3, count( $cites ) );
		if ( $linked < $need ) {
			$reasons[] = 'citations:' . $linked . '/' . $need;
		}

		$focuskw = isset( $bank_post['focuskw'] ) ? (string) $bank_post['focuskw'] : '';
		if ( '' !== $focuskw && false === stripos( $title . ' ' . $text, strtolower( $focuskw ) ) ) {
			$reasons[] = 'no-focuskw';
		}

		return array(
			'pass'     => empty( $reasons ),
			'reasons'  => $reasons,
			'warnings' => $warnings,
			'words'    => $words,
		);
	}

	private function maybe_publish_existing( $post_id, $bank_post ) {
		$post = get_post( $post_id );
		$qa   = $this->qa_check( $post->post_title, $post->post_content, $bank_post );
		$this->store_qa( $post_id, $qa );
		if ( ! $qa['pass'] ) {
			return array( 'status' => 'draft', 'post_id' => $post_id, 'qa' => $qa );
		}
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);
		return array( 'status' => 'publish', 'post_id' => $post_id, 'qa' => $qa );
	}

	private function existing_post( $bank_id ) {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META_BANK_ID, // phpcs:ignore WordPress.DB.SlowDBQuery -- exact-match meta lookup.
				'meta_value'     => (string) $bank_id,
			)
		);
		return $posts ? (int) $posts[0] : 0;
	}

	private function store_meta( $post_id, $bank_post, $qa ) {
		update_post_meta( $post_id, '_gab_auto_generated', '1' );
		update_post_meta( $post_id, self::META_BANK_ID, (string) $bank_post['id'] );
		update_post_meta( $post_id, self::META_BANK_DATE, (string) $bank_post['date'] );
		update_post_meta( $post_id, self::META_BANK_SLOT, (string) $bank_post['slot'] );
		update_post_meta( $post_id, '_gab_topic', (string) $bank_post['focuskw'] );
		update_post_meta( $post_id, '_gab_generated_date', current_time( 'mysql' ) );
		$this->store_qa( $post_id, $qa );
	}

	private function store_qa( $post_id, $qa ) {
		update_post_meta( $post_id, self::META_QA_STATUS, $qa['pass'] ? 'pass' : 'fail' );
		update_post_meta( $post_id, self::META_QA_ISSUES, wp_json_encode( $qa ) );
	}

	private function set_thumbnail( $post_id, $bank_post ) {
		$feat = isset( $bank_post['featured_image_id'] ) ? (int) $bank_post['featured_image_id'] : 0;
		if ( $feat && 'attachment' === get_post_type( $feat ) ) {
			set_post_thumbnail( $post_id, $feat );
		}
	}

	private function set_yoast_meta( $post_id, $bank_post, $parsed ) {
		$focuskw = isset( $bank_post['focuskw'] ) ? (string) $bank_post['focuskw'] : '';
		if ( '' === $focuskw ) {
			$focuskw = $parsed['title'];
		}
		update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focuskw );
		if ( '' !== $parsed['metadesc'] ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $parsed['metadesc'] );
		}
	}

	/** Map a bank category slug to a WP term id (0 = none). */
	public function category_term_id( $slug ) {
		if ( empty( $slug ) ) {
			return 0;
		}
		$term = get_term_by( 'slug', sanitize_key( (string) $slug ), 'category' );
		return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
	}

	private function scheduled_time() {
		$time = (string) get_option( 'gab_time', '06:00' );
		if ( ! preg_match( '/^\d{1,2}:\d{2}$/', $time ) ) {
			$time = '06:00';
		}
		return $time . ':00';
	}

	private function system_prompt() {
		return 'You are the senior content writer for Gabochie Design, an architecture, engineering and construction consultancy for Ghana. '
			. 'Write in clear US English for Ghanaian property owners and the Ghanaian diaspora. Ground every claim in GHS figures, Ghanaian '
			. 'institutions (Lands Commission, Ghana Revenue Authority, Ghana Statistical Service 2021 census) and the real local building '
			. 'market. Output clean, valid HTML only (p, h2, h3, ul, li, strong, em, a). Never wrap the CONTENT in markdown code fences.';
	}

	/** Build the generation prompt from a bank entry. */
	private function bank_prompt( $bank_post ) {
		$hub      = isset( $bank_post['hub']['url'] ) ? (string) $bank_post['hub']['url'] : '';
		$siblings = isset( $bank_post['siblings'] ) ? (array) $bank_post['siblings'] : array();
		$links    = array();
		if ( '' !== $hub ) {
			$links[] = $hub;
		}
		foreach ( $siblings as $sib ) {
			if ( '' !== (string) $sib ) {
				$links[] = '/' . trim( (string) $sib, '/' ) . '/';
			}
		}

		$cites = isset( $bank_post['citations'] ) ? (array) $bank_post['citations'] : array();
		$cites = implode( ', ', array_filter( array_map( 'trim', $cites ) ) );

		$figs = isset( $bank_post['ghs_figures'] ) ? (array) $bank_post['ghs_figures'] : array();
		$figs = implode( "\n", array_map( 'trim', $figs ) );

		$cta       = isset( $bank_post['cta'] ) ? $bank_post['cta'] : array();
		$cta_copy  = isset( $cta['copy'] ) ? (string) $cta['copy'] : '';
		$cta_urls  = isset( $cta['urls'] ) ? (array) $cta['urls'] : array();
		$cta_links = implode( ', ', array_filter( array_map( 'trim', $cta_urls ) ) );

		$prompt  = "Write a complete, ready-to-publish blog post for gabochiedesign.com.\n\n";
		$prompt .= "TITLE (use verbatim): {$bank_post['title']}\n";
		$prompt .= "Focus keyphrase: {$bank_post['focuskw']}\n";
		$prompt .= "Category: {$bank_post['category']} ({$bank_post['cluster']}, {$bank_post['month']} theme: {$bank_post['theme']})\n";
		$prompt .= 'Word count: 1,200-2,000 words. No padding, no generic filler. Each sentence must add information the reader can use or a fact they can verify.' . "\n\n";

		$prompt .= "STRUCTURE REQUIREMENTS:\n";
		$prompt .= "- Open with a 2-4 sentence lead paragraph that names the problem and the stake (GHS figures if available). No 'In today's world' openers.\n";
		$prompt .= "- At least 3 H2 sections. Give EVERY H2 a lowercase kebab-case id attribute (example: <h2 id=\"what-it-costs\">).\n";
		$prompt .= "- End with a FAQ section under <h2 id=\"faqs\"> containing at least 3 questions written as <h3> headings that each end with a question mark, each followed by a 2-4 sentence answer in a <p>.\n";
		$prompt .= "- Close with a CTA section: <h2 id=\"next-step-your-project\"> with a clear next step for the reader.\n";

		$prompt .= "\nINTERNAL LINKS (use exactly these relative or absolute paths inside <a href>):\n";
		$prompt .= ( empty( $links ) ? '- (none listed)' : '- ' . implode( "\n- ", $links ) ) . "\n";

		$prompt .= "\nMANDATORY AUTHORITY CITATIONS: link at least 3 of these domains with descriptive anchor text (first mention ideally):\n";
		$prompt .= ( '' === $cites ? '- statsghana.gov.gh (2021 Population and Housing Census), lc.gov.gh (Lands Commission), gra.gov.gh (GRA)' : $cites ) . "\n";

		$prompt .= "\nGHS FIGURES TO USE (rate-dated, incorporate in context where they fit):\n" . $figs . "\n";

		$prompt .= "\nCALL-TO-ACTION: " . $cta_copy . " Target URLs: " . ( '' === $cta_links ? '/project-recovery-diagnostic/' : $cta_links ) . "\n";

		$prompt .= "\nFORMATTING: US English, GHS amounts as 'GHS 1,500'. Escape nothing. One <a href> per link. FAQ <h3> questions must literally end with '?'.\n";
		$prompt .= "Return EXACTLY this format (no extra prose before or after, no code fences):\n";
		$prompt .= "TITLE:\n{$bank_post['title']}\nMETADESC:\n<meta description 120-158 chars containing the focus keyphrase>\nCONTENT:\n<html>";

		return $prompt;
	}

	/** Normalise the model output into title/metadesc/content/excerpt. */
	private function parse_response( $text, $bank_post ) {
		$title    = isset( $bank_post['title'] ) ? (string) $bank_post['title'] : '';
		$metadesc = '';
		$content  = (string) $text;

		if ( preg_match( '/TITLE:\s*(.+)/i', $text, $mt ) ) {
			$title = trim( $mt[1] );
		}
		if ( preg_match( '/METADESC:\s*(.+)/i', $text, $mm ) ) {
			$metadesc = trim( $mm[1] );
		}
		if ( preg_match( '/CONTENT:\s*([\s\S]*)$/i', $text, $mc ) ) {
			$content = trim( $mc[1] );
		}

		// Strip markdown code fences that some models wrap around the content.
		$content = preg_replace( '/^```(?:html)?\s*/i', '', $content );
		$content = preg_replace( '/\s*```$/i', '', $content );

		// Remove a verbatim title echo at the very top of the body.
		if ( ! empty( $title ) && 0 === stripos( $content, $title ) ) {
			$content = trim( substr( $content, strlen( $title ) ) );
		}

		$metadesc = $this->sanitize_metadesc( $metadesc, $content );
		$excerpt  = wp_trim_words( (string) wp_strip_all_tags( $content ), 30 );

		return array(
			'title'    => sanitize_text_field( $title ),
			'content'  => $content,
			'metadesc' => $metadesc,
			'excerpt'  => $excerpt,
		);
	}

	private function sanitize_metadesc( $metadesc, $content ) {
		if ( '' === $metadesc ) {
			$metadesc = (string) wp_strip_all_tags( $content );
		}
		$metadesc = trim( preg_replace( '/\s+/', ' ', $metadesc ) );
		$metadesc = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $metadesc ); // drop control chars.
		$metadesc = html_entity_decode( $metadesc, ENT_QUOTES, 'UTF-8' );
		return $this->utf8_truncate( $metadesc, 158 );
	}

	/** mbstring-free UTF-8 truncation (keeps multibyte sequences intact). */
	private function utf8_truncate( $str, $max ) {
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $str, 0, $max, 'UTF-8' );
		}
		$out  = '';
		$len  = 0;
		$i    = 0;
		$n    = strlen( $str );
		while ( $i < $n && $len < $max ) {
			$c = ord( $str[ $i ] );
			if ( $c < 0x80 ) {
				$out .= $str[ $i ];
				++$i;
			} elseif ( $c >= 0xC2 && $c <= 0xDF ) {
				$out .= substr( $str, $i, 2 );
				$i   += 2;
			} elseif ( $c >= 0xE0 && $c <= 0xEF ) {
				$out .= substr( $str, $i, 3 );
				$i   += 3;
			} else {
				$out .= substr( $str, $i, 4 );
				$i   += 4;
			}
			++$len;
		}
		return $out;
	}

	/** AJAX: force a run for a date (default today). Adjunct to cron for ops. */
	public function ajax_bank_run() {
		check_ajax_referer( 'gab_ajax' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
		$date  = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
		$force = isset( $_POST['force'] ) ? sanitize_text_field( wp_unslash( $_POST['force'] ) ) : '';
		if ( '' !== $date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( array( 'message' => 'Invalid date format.' ) );
		}
		$result = $this->process_date(
			'' === $date ? null : $date,
			'publish' === $force ? 'publish' : null
		);
		wp_send_json_success( array( 'result' => $result ) );
	}
}

Gab_Auto_Executor::instance();