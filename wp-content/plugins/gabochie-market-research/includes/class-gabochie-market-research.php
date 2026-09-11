<?php
/**
 * Gabochie Market Research core.
 *
 * Collects demand signals (community questions, site-search log, CRM
 * engagement/enquiries, external RSS pulse) into a per-niche opportunity
 * matrix, scores each niche 0-100, and feeds the top opportunities to the
 * LLM memo generator (see class-gabochie-market-research-ai.php). The memo's
 * concrete opportunities are upserted into {prefix}gmr_proposals for the
 * human-in-the-loop approval inbox.
 *
 * Tables owned (created via dbDelta on activation):
 *   {prefix}gmr_proposals — pending/approved/rejected opportunities
 *   {prefix}gmr_search_log — anonymised internal site-search terms
 *
 * @package Gabochie_Market_Research
 */

defined( 'ABSPATH' ) || exit;

/**
 * Demand intelligence core.
 */
class Gabochie_Market_Research {


	const TABLE_PROPOSALS = 'gmr_proposals';
	const TABLE_SEARCH    = 'gmr_search_log';

	const HOOK_COLLECT = 'gmr_collect';
	const HOOK_MEMO    = 'gmr_weekly_memo';

	const OPT_ENABLED      = 'gmr_enabled';
	const OPT_AI_MODEL     = 'gmr_ai_model';
	const OPT_FEEDS        = 'gmr_feeds';
	const OPT_DIGEST_EMAIL = 'gmr_digest_email';
	const OPT_LAST_SIGNALS = 'gmr_last_signals';
	const OPT_LAST_MEMO    = 'gmr_last_memo';
	const OPT_MEMO_AT      = 'gmr_last_memo_at';
	const OPT_ERRORS       = 'gmr_errors';

	const STATUS_PENDING  = 'pending';
	const STATUS_APPROVED = 'approved';
	const STATUS_REJECTED = 'rejected';
	const STATUS_DEFERRED = 'deferred';

	const RECENT_DAYS = 30;

	/**
	 * @var Gabochie_Market_Research|null
	 */
	private static $instance = null;

	/**
	 * @return Gabochie_Market_Research
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'maybe_install' ) );
		add_action( 'template_redirect', array( $this, 'log_search' ) );
		add_action( self::HOOK_COLLECT, array( $this, 'on_collect' ) );
		add_action( self::HOOK_MEMO, array( $this, 'on_memo' ) );
	}

	/**
	 * Cron-safe void wrapper around run_collect().
	 *
	 * @return void
	 */
	public function on_collect() {
		$this->run_collect();
	}

	/**
	 * Cron-safe void wrapper around run_memo().
	 *
	 * @return void
	 */
	public function on_memo() {
		$this->run_memo();
	}

	/**
	 * Fully-qualified table name.
	 *
	 * @param  string $which TABLE_PROPOSALS or TABLE_SEARCH.
	 * @return string
	 */
	public static function table( $which ) {
		global $wpdb;
		return $wpdb->prefix . $which;
	}

	/**
	 * Does a table exist?
	 *
	 * @param  string $which TABLE_PROPOSALS or TABLE_SEARCH.
	 * @return bool
	 */
	public static function ready( $which ) {
		global $wpdb;
		$table = self::table( $which );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	/**
	 * Create both tables when missing (idempotent).
	 */
	public static function ensure_schema() {
		global $wpdb;
		if ( self::ready( self::TABLE_PROPOSALS ) && self::ready( self::TABLE_SEARCH ) ) {
			return;
		}
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$proposals = self::table( self::TABLE_PROPOSALS );
		$search    = self::table( self::TABLE_SEARCH );
		$collate   = $wpdb->get_charset_collate();
		$sql       = "CREATE TABLE {$proposals} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			niche VARCHAR(80) NOT NULL DEFAULT '',
			title VARCHAR(255) NOT NULL DEFAULT '',
			product_type VARCHAR(40) NOT NULL DEFAULT 'guide',
			evidence TEXT NULL,
			summary TEXT NULL,
			price_hint VARCHAR(120) NOT NULL DEFAULT '',
			opportunity_score INT(11) NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			source VARCHAR(80) NOT NULL DEFAULT 'ai-memo',
			created_at DATETIME NOT NULL,
			reviewed_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY idx_status (status),
			KEY idx_niche (niche),
			KEY idx_created (created_at)
		) {$collate};
		CREATE TABLE {$search} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			term VARCHAR(190) NOT NULL DEFAULT '',
			ip_hash VARCHAR(64) NOT NULL DEFAULT '',
			hits INT(11) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_term (term),
			KEY idx_created (created_at)
		) {$collate};";
		dbDelta( $sql );
	}

	/**
	 * Activation + first-load bootstrap (idempotent).
	 */
	public function maybe_install() {
		self::ensure_schema();
		if ( ! get_option( self::OPT_ENABLED ) ) {
			add_option( self::OPT_ENABLED, '1' );
		}
		if ( ! get_option( self::OPT_AI_MODEL ) ) {
			add_option( self::OPT_AI_MODEL, 'gemini-3.5-flash' );
		}
		if ( ! get_option( self::OPT_DIGEST_EMAIL ) ) {
			add_option( self::OPT_DIGEST_EMAIL, get_option( 'admin_email' ) );
		}
		$this->ensure_crons();
	}

	/**
	 * Schedule the daily collect + weekly memo crons (idempotent).
	 */
	public function ensure_crons() {
		if ( ! wp_next_scheduled( self::HOOK_COLLECT ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK_COLLECT );
		}
		if ( ! wp_next_scheduled( self::HOOK_MEMO ) ) {
			// Next Monday 06:00 local; guard against weekday drift by just +6 days.
			wp_schedule_event( strtotime( 'next monday 06:00' ), 'weekly', self::HOOK_MEMO );
		}
	}

	/**
	 * Anonymised internal site-search logging (one row + hit counter per term,
	 * debounced per IP+term for an hour).
	 */
	public function log_search() {
		if ( is_admin() || ! is_search() ) {
			return;
		}
		$term = trim( (string) get_search_query() );
		if ( '' === $term ) {
			return;
		}
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$hash = md5( strtolower( $term ) . '|' . $ip );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		$key = 'gmr_srch_' . substr( $hash, 0, 20 );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		if ( get_transient( $key ) ) {
			return;
		}
		set_transient( $key, 1, HOUR_IN_SECONDS );

		self::ensure_schema();
		global $wpdb;
		$table = self::table( self::TABLE_SEARCH );
		$hits  = 0;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id, hits FROM {$table} WHERE term = %s ORDER BY id DESC LIMIT 1", $term ) );
		if ( $row ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $table, array( 'hits' => (int) $row->hits + 1 ), array( 'id' => (int) $row->id ), array( '%d' ), array( '%d' ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			$wpdb->insert(
				$table,
				array(
					'term'       => $term,
					'ip_hash'    => $hash,
					'hits'       => 1,
					'created_at' => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%d', '%s' )
			);
		}
	}

	/**
	 * The opportunity niche dictionary. Each niche carries a keyword set used
	 * to match signals, a product-type default and a GHS price hint for the
	 * memo writer.
	 *
	 * @return array
	 */
	public function niches() {
		return array(
			'stalled-recovery' => array(
				'label'        => 'Stalled build recovery',
				'keywords'     => array( 'stalled', 'unfinished', 'uncompleted', 'abandoned', 'restart build', 'finish build' ),
				'product_type' => 'diagnostic',
				'price_hint'   => 'GHS 1,500',
			),
			'land-acquisition' => array(
				'label'        => 'Land & plot acquisition',
				'keywords'     => array( 'land', 'plot', 'acre', 'acquisition', 'title' ),
				'product_type' => 'service',
				'price_hint'   => 'GHS 1,000',
			),
			'verification'     => array(
				'label'        => 'Document & title verification',
				'keywords'     => array( 'scam', 'fraud', 'fake title', 'verification', 'affidavit', 'document' ),
				'product_type' => 'service',
				'price_hint'   => 'GHS 750',
			),
			'diaspora'         => array(
				'label'        => 'Diaspora remote building',
				'keywords'     => array( 'diaspora', 'remote', 'abroad', 'from uk', 'from usa', 'remittance' ),
				'product_type' => 'guide',
				'price_hint'   => 'GHS 1,000',
			),
			'financing'        => array(
				'label'        => 'Build financing & payment plans',
				'keywords'     => array( 'mortgage', 'loan', 'financing', 'payment plan', 'instalment', 'salary' ),
				'product_type' => 'guide',
				'price_hint'   => 'GHS 500',
			),
			'price-intel'      => array(
				'label'        => 'Costs, materials & price board',
				'keywords'     => array( 'cost', 'price board', 'material', 'rebar', 'cement', 'quote', 'estimate' ),
				'product_type' => 'tool',
				'price_hint'   => 'Free',
			),
			'rental-yield'     => array(
				'label'        => 'Rental yield & short lets',
				'keywords'     => array( 'rent', 'rental', 'airbnb', 'yield', 'short let', 'investment return' ),
				'product_type' => 'guide',
				'price_hint'   => 'GHS 750',
			),
			'off-plan'         => array(
				'label'        => 'Off-plan & joint ventures',
				'keywords'     => array( 'off-plan', 'pre-sale', 'forward fund', 'joint venture', 'jv', 'partner' ),
				'product_type' => 'service',
				'price_hint'   => 'GHS 2,000',
			),
			'commercial'       => array(
				'label'        => 'Commercial development',
				'keywords'     => array( 'commercial', 'office', 'shop', 'warehouse', 'retail', 'showroom' ),
				'product_type' => 'service',
				'price_hint'   => 'GHS 5,000',
			),
			'gated-estate'     => array(
				'label'        => 'Gated estates & communities',
				'keywords'     => array( 'gated', 'estate', 'community', 'serviced', 'compound' ),
				'product_type' => 'service',
				'price_hint'   => 'GHS 3,000',
			),
			'renovation'       => array(
				'label'        => 'Renovation & turnaround',
				'keywords'     => array( 'renovation', 'refurbish', 'remodel', 'finish work', 'extension', 'turnaround' ),
				'product_type' => 'tool',
				'price_hint'   => 'GHS 500',
			),
			'prefab'           => array(
				'label'        => 'Prefab & modular methods',
				'keywords'     => array( 'prefab', 'modular', 'container', 'steel frame', 'precast' ),
				'product_type' => 'guide',
				'price_hint'   => 'GHS 750',
			),
			'green-build'      => array(
				'label'        => 'Green & solar building',
				'keywords'     => array( 'solar', 'eco', 'sustainable', 'green', 'energy' ),
				'product_type' => 'guide',
				'price_hint'   => 'GHS 500',
			),
			'purpose-built'    => array(
				'label'        => 'Purpose-built housing (student/senior)',
				'keywords'     => array( 'student housing', 'hostel', 'senior housing', 'care home', 'rental blocks' ),
				'product_type' => 'service',
				'price_hint'   => 'GHS 5,000',
			),
		);
	}

	/**
	 * Niche opportunity matrix from current signals. Each row returns the raw
	 * counts per signal source plus a weighted 0-100 score.
	 *
	 * @return array
	 */
	public function collect() {
		self::ensure_schema();
		$signals = $this->raw_signals();
		$out     = array();

		foreach ( $this->niches() as $slug => $niche ) {
			$counts = $this->counts_for( $slug, $niche['keywords'], $signals );

			// Weights: questions are the strongest voiced demand.
			$score = (int) round(
				$counts['questions'] * 30
				+ $counts['searches'] * 20
				+ $counts['enquiries'] * 25
				+ $counts['engaged'] * 12
				+ $counts['rss'] * 5
				+ $this->coverage_gap( $counts['coverage'] ) * 12
			);
			$score = min( 100, $score );

			$out[ $slug ] = array(
				'label'        => $niche['label'],
				'product_type' => $niche['product_type'],
				'price_hint'   => $niche['price_hint'],
				'questions'    => $counts['questions'],
				'searches'     => $counts['searches'],
				'enquiries'    => $counts['enquiries'],
				'engaged'      => $counts['engaged'],
				'rss'          => $counts['rss'],
				'coverage'     => $counts['coverage'],
				'score'        => $score,
			);
		}

		// Sort by score, keeping the array keyed by slug.
		uasort(
			$out,
			static function ( $a, $b ) {
				return (int) $b['score'] <=> (int) $a['score'];
			}
		);

		update_option(
			self::OPT_LAST_SIGNALS,
			array(
				'generated_at' => gmdate( 'Y-m-d H:i:s' ),
				'window_days'  => self::RECENT_DAYS,
				'niches'       => $out,
			)
		);
		return $out;
	}

	/**
	 * Gather raw cross-source data buckets used by everything below.
	 *
	 * @return array
	 */
	private function raw_signals() {
		global $wpdb;

		$since = gmdate( 'Y-m-d H:i:s', time() - self::RECENT_DAYS * DAY_IN_SECONDS );

		// Community questions (CPT 'gabochie_question') in the window, as IDs.
		$question_ids = array();
		if ( post_type_exists( 'gabochie_question' ) ) {
			$question_ids = get_posts(
				array(
					'post_type'      => 'gabochie_question',
					'post_status'    => 'publish',
					'posts_per_page' => 200,
					'fields'         => 'ids',
					'date_query'     => array( array( 'after' => $since ) ),
				)
			);
		}

		// Site-search terms in the window.
		$searches = array();
		if ( self::ready( self::TABLE_SEARCH ) ) {
			$table = self::table( self::TABLE_SEARCH );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT term FROM {$table} WHERE created_at >= %s", $since ) );
			foreach ( (array) $rows as $row ) {
				$searches[] = strtolower( (string) $row->term );
			}
		}

		// CRM signal: recent engagements + property/enquiry leads (when the CRM exists).
		$engaged   = 0;
		$enquiries = 0;
		if ( class_exists( 'Gabochie_Crm' ) && Gabochie_Crm::ready() ) {
			$engaged   = $this->crm_count_where( 'last_engaged_at', '>=', $since );
			$enquiries = $this->crm_count_where( 'source', '=', 'property-listing' );
		}

		return array(
			'since'      => $since,
			'questions'  => $question_ids,
			'searches'   => $searches,
			'engaged'    => (int) $engaged,
			'enquiries'  => (int) $enquiries,
			'rss_titles' => $this->rss_titles(),
		);
	}

	/**
	 * Count CRM rows matching a column/value predicate safely.
	 *
	 * @param  string $col
	 * @param  string $op
	 * @param  string $value
	 * @return int
	 */
	private function crm_count_where( $col, $op, $value ) {
		global $wpdb;
		static $allowed = array( 'last_engaged_at', 'source' );
		if ( ! in_array( $col, $allowed, true ) ) {
			return 0;
		}
		$table = Gabochie_Crm::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$table} WHERE {$col} {$op} %s",
				$value
			)
		);
	}

	/**
	 * Fetch latest RSS headlines from configured feeds for keyword matching.
	 *
	 * @return array List of lowercase titles.
	 */
	private function rss_titles() {
		$feeds  = array_map( 'esc_url_raw', (array) get_option( self::OPT_FEEDS, array() ) );
		$titles = array();
		foreach ( $feeds as $feed ) {
			if ( '' === $feed ) {
				continue;
			}
			$rss = fetch_feed( $feed );
			if ( is_wp_error( $rss ) ) {
				continue;
			}
			$items = $rss->get_items( 0, 20 );
			foreach ( $items as $item ) {
				$title = trim( (string) $item->get_title() );
				if ( '' !== $title ) {
					$titles[] = strtolower( $title );
				}
			}
		}
		return $titles;
	}

	/**
	 * Count signal hits + existing coverage per niche.
	 *
	 * @param  string $slug
	 * @param  array  $keywords
	 * @param  array  $signals
	 * @return array
	 */
	private function counts_for( $slug, $keywords, $signals ) {
		$questions = 0;
		$searches  = 0;
		$rss       = 0;

		foreach ( (array) $signals['questions'] as $qid ) {
			$hay = strtolower( (string) get_the_title( $qid ) . ' ' . (string) get_the_excerpt( $qid ) );
			foreach ( $keywords as $k ) {
				if ( false !== strpos( $hay, $k ) ) {
					++$questions;
					break;
				}
			}
		}
		foreach ( (array) $signals['searches'] as $term ) {
			foreach ( $keywords as $k ) {
				if ( false !== strpos( $term, $k ) ) {
					++$searches;
					break;
				}
			}
		}
		foreach ( (array) $signals['rss_titles'] as $title ) {
			foreach ( $keywords as $k ) {
				if ( false !== strpos( $title, $k ) ) {
					++$rss;
					break;
				}
			}
		}

		return array(
			'questions' => (int) $questions,
			'searches'  => (int) $searches,
			'enquiries' => (int) $signals['enquiries'],
			'engaged'   => (int) $signals['engaged'],
			'rss'       => (int) $rss,
			'coverage'  => $this->coverage_count( $keywords ),
		);
	}

	/**
	 * Published posts whose title mentions any keyword (coverage baseline).
	 *
	 * @param  array $keywords
	 * @return int
	 */
	private function coverage_count( $keywords ) {
		global $wpdb;
		$like = array();
		foreach ( $keywords as $k ) {
			$like[] = $wpdb->esc_like( $k );
		}
		$counts = 0;
		foreach ( $like as $term ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$counts += (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'post' AND post_title LIKE %s",
					'%' . $term . '%'
				)
			);
		}
		return (int) $counts;
	}

	/**
	 * Coverage gap bonus: under-covered niches rank higher (room to grow),
	 * flat at 2+ posts.
	 *
	 * @param  int $coverage
	 * @return int
	 */
	private function coverage_gap( $coverage ) {
		return max( 0, 2 - $coverage );
	}

	/**
	 * Collect now (cron + CLI + admin) — returns the new matrix.
	 *
	 * @return array
	 */
	public function run_collect() {
		return $this->collect();
	}

	/**
	 * Generate the weekly market memo: LLM turns the scored matrix into 3-5
	 * concrete product opportunities, upserted as pending proposals.
	 *
	 * @param  array $niches Optional pre-collected matrix (defaults to fresh collect).
	 * @return array {summary, opportunities} or {error}
	 */
	public function run_memo( array $niches = array() ) {
		self::ensure_schema();
		$matrix = empty( $niches ) ? $this->collect() : $niches;

		$has_signal = 0;
		foreach ( $matrix as $row ) {
			$has_signal += (int) $row['questions'] + (int) $row['searches'] + (int) $row['enquiries'] + (int) $row['engaged'] + (int) $row['rss'];
		}
		if ( $has_signal <= 0 ) {
			$data = array(
				'summary'       => 'No meaningful demand signals recorded this window; no proposals generated.',
				'opportunities' => array(),
			);
			update_option( self::OPT_LAST_MEMO, $data );
			update_option( self::OPT_MEMO_AT, current_time( 'mysql' ) );
			return array(
				'summary'       => $data['summary'],
				'opportunities' => array(),
				'upserted'      => 0,
			);
		}

		$summarize = array();
		foreach ( $matrix as $slug => $row ) {
			$summarize[] = $slug . '|' . $row['label'] . '|score:' . $row['score'] . '|q:' . $row['questions'] . '|s:' . $row['searches'] . '|e:' . $row['enquiries'] . '|eng:' . $row['engaged'] . '|rss:' . $row['rss'] . '|cov:' . $row['coverage'] . '|product:' . $row['product_type'] . '|price:' . $row['price_hint'];
		}

		$ai       = Gabochie_Market_Research_AI::instance();
		$data     = null;
		$attempts = 3;
		for ( $i = 1; $i <= $attempts; $i++ ) {
			$json = $ai->generate_memo( $summarize, $i > 1 );
			$data = $this->parse_memo_json( $json );
			if ( is_array( $data ) && ! empty( $data['summary'] ) ) {
				break;
			}
			$this->log_error( 'memo: invalid LLM JSON (attempt ' . $i . ', source ' . $ai->last_source() . ') · ' . substr( (string) $json, 0, 120 ) );
			if ( $i < $attempts ) {
				sleep( 2 * $i );
			}
		}
		if ( ! is_array( $data ) || empty( $data['summary'] ) ) {
			$msg = 'memo: LLM returned invalid JSON after ' . $attempts . ' attempts';
			$this->log_error( $msg );
			return array( 'error' => $msg );
		}

		$upserted = $this->apply_proposals( $data );

		update_option( self::OPT_LAST_MEMO, $data );
		update_option( self::OPT_MEMO_AT, current_time( 'mysql' ) );
		unset( $data['opportunities'] );
		$data['upserted'] = $upserted;

		$this->send_digest( $data );
		return $data;
	}

	/**
	 * Tolerant memo-JSON parser: extracts the first { … } block and decodes it.
	 * Returns null when the payload is empty, has no object, or the object lacks
	 * a summary (so the caller can retry with a JSON-forced provider).
	 *
	 * @param  mixed $json
	 * @return array|null
	 */
	private function parse_memo_json( $json ) {
		$raw   = trim( (string) $json );
		$start = strpos( $raw, '{' );
		$end   = strrpos( $raw, '}' );
		if ( false === $start || false === $end || $end <= $start ) {
			return null;
		}
		$data = json_decode( substr( $raw, $start, ( $end - $start ) + 1 ), true );
		if ( ! is_array( $data ) || empty( $data['summary'] ) ) {
			return null;
		}
		return $data;
	}

	/**
	 * Validate + upsert the memo's proposals (dedupe on niche+title).
	 *
	 * @param  array $data
	 * @return int Number created.
	 */
	private function apply_proposals( $data ) {
		global $wpdb;
		$table         = self::table( self::TABLE_PROPOSALS );
		$created       = 0;
		$allow_product = array( 'guide', 'diagnostic', 'tool', 'service', 'membership' );

		foreach ( (array) $data['opportunities'] as $op ) {
			$niche = isset( $op['niche'] ) ? sanitize_key( (string) $op['niche'] ) : '';
			$title = isset( $op['title'] ) ? sanitize_text_field( (string) $op['title'] ) : '';
			if ( '' === $niche || '' === $title ) {
				continue;
			}
			$product_type = isset( $op['product_type'] ) ? sanitize_key( (string) $op['product_type'] ) : 'guide';
			if ( ! in_array( $product_type, $allow_product, true ) ) {
				$product_type = 'guide';
			}
			$score = isset( $op['opportunity_score'] ) ? max( 0, min( 100, (int) $op['opportunity_score'] ) ) : 0;

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE niche = %s AND title = %s LIMIT 1", $niche, $title ) );
			if ( $exists ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
				$wpdb->update(
					$table,
					array(
						'product_type'      => $product_type,
						'opportunity_score' => $score,
						'summary'           => isset( $op['rationale'] ) ? sanitize_textarea_field( (string) $op['rationale'] ) : '',
						'price_hint'        => isset( $op['pricing_hint'] ) ? sanitize_text_field( (string) $op['pricing_hint'] ) : '',
						'evidence'          => wp_json_encode( $op ),
					),
					array( 'id' => (int) $exists ),
					array( '%s', '%d', '%s', '%s', '%s' ),
					array( '%d' )
				);
				continue;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			$wpdb->insert(
				$table,
				array(
					'niche'             => $niche,
					'title'             => $title,
					'product_type'      => $product_type,
					'evidence'          => wp_json_encode( $op ),
					'summary'           => isset( $op['rationale'] ) ? sanitize_textarea_field( (string) $op['rationale'] ) : '',
					'price_hint'        => isset( $op['pricing_hint'] ) ? sanitize_text_field( (string) $op['pricing_hint'] ) : '',
					'opportunity_score' => $score,
					'status'            => self::STATUS_PENDING,
					'source'            => 'ai-memo',
					'created_at'        => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
			);
			++$created;
		}
		return $created;
	}

	/**
	 * Email the memo digest to the configured address via wp_mail
	 * (gabochie-email → Brevo).
	 *
	 * @param  array $data Memo summary (opportunities stripped).
	 * @return void
	 */
	private function send_digest( $data ) {
		$to = (string) get_option( self::OPT_DIGEST_EMAIL, get_option( 'admin_email' ) );
		if ( '' === $to || ! is_email( $to ) ) {
			return;
		}
		$subject = '[Gabochie Market Research] Weekly opportunity memo';
		$body    = '<h2>Weekly market memo</h2>'
			. '<p>' . esc_html( (string) $data['summary'] ) . '</p>'
			. '<p>Review pending proposals in WP Admin → Market Research → Inbox.</p>';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Proposal status mutation with review timestamp.
	 *
	 * @param  int    $id
	 * @param  string $status
	 * @return bool
	 */
	public function set_proposal_status( $id, $status ) {
		global $wpdb;
		$allow = array( self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_DEFERRED );
		if ( ! in_array( $status, $allow, true ) ) {
			return false;
		}
		$table = self::table( self::TABLE_PROPOSALS );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->update(
			$table,
			array(
				'status'      => $status,
				'reviewed_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function delete_proposal( $id ) {
		global $wpdb;
		$table = self::table( self::TABLE_PROPOSALS );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->delete( $table, array( 'id' => (int) $id ), array( '%d' ) );
	}

	/**
	 * Append to the rolling error log (last N=20).
	 *
	 * @param string $message
	 */
	public static function log_error( $message ) {
		$log   = get_option( self::OPT_ERRORS, array() );
		$log[] = array(
			'time'  => gmdate( 'Y-m-d H:i:s' ),
			'error' => (string) $message,
		);
		$log   = array_slice( $log, -20 );
		update_option( self::OPT_ERRORS, $log );
	}
}
