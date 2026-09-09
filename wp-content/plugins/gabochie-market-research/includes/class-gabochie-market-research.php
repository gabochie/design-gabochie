<?php
/**
 * Gabochie Market Research core.
 *
 * The demand-intelligence layer for the autonomy stack. Collects first-party
 * demand signals (site search, community questions, marketplace enquiries,
 * newsletter engagement, news-portal pulse, content coverage), scores a fixed
 * niche dictionary against them, snapshots the result, and feeds a weekly
 * AI-written market memo that proposes new products/content into the approval
 * inbox ({prefix}gmr_proposals).
 *
 * @package Gabochie_Market_Research
 */

defined( 'ABSPATH' ) || exit;

/**
 * Market research engine.
 */
class Gabochie_Market_Research {


	const TABLE_SEARCH  = 'gmr_search_log';
	const TABLE_PROP    = 'gmr_proposals';

	const HOOK_COLLECT = 'gmr_collect';
	const HOOK_MEMO    = 'gmr_market_memo';

	const OPT_COLLECT_AT = 'gmr_last_collect';
	const OPT_SNAPSHOT   = 'gmr_snapshot';
	const OPT_MEMO       = 'gmr_last_memo';
	const OPT_MEMO_AT    = 'gmr_last_memo_at';
	const OPT_MODEL      = 'gmr_ai_model';
	const OPT_ENABLED    = 'gmr_enabled';
	const OPT_ERRORS     = 'gmr_errors';

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
		add_action( 'template_redirect', array( $this, 'maybe_log_search' ) );
		add_action( self::HOOK_COLLECT, array( $this, 'run_collect' ) );
		add_action( self::HOOK_MEMO, array( $this, 'run_memo' ) );
	}

	/**
	 * Identify the search-log table name.
	 *
	 * @return string
	 */
	public static function search_table() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_SEARCH;
	}

	/**
	 * Identify the proposals table name.
	 *
	 * @return string
	 */
	public static function proposals_table() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_PROP;
	}

	/**
	 * Does the schema exist?
	 *
	 * @return bool
	 */
	public static function ready() {
		global $wpdb;
		$table = self::search_table();
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	/**
	 * Create both tables when missing (idempotent).
	 */
	public static function ensure_schema() {
		global $wpdb;
		if ( self::ready() ) {
			return;
		}
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$collate = $wpdb->get_charset_collate();

		$search_table = self::search_table();
		$sql_search   = "CREATE TABLE {$search_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			term VARCHAR(190) NOT NULL,
			hits INT(10) UNSIGNED NOT NULL DEFAULT 0,
			ip VARCHAR(45) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_created (created_at),
			KEY idx_term (term)
		) {$collate};";
		dbDelta( $sql_search );

		$prop_table = self::proposals_table();
		$sql_prop   = "CREATE TABLE {$prop_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			niche VARCHAR(60) NOT NULL,
			title VARCHAR(200) NOT NULL,
			product_type VARCHAR(40) NOT NULL DEFAULT '',
			price_ghes DECIMAL(12,2) NOT NULL DEFAULT 0,
			opportunity_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
			audience VARCHAR(160) NOT NULL DEFAULT '',
			evidence TEXT NULL,
			rationale TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL,
			reviewed_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_niche (niche),
			KEY idx_status (status),
			KEY idx_score (opportunity_score)
		) {$collate};";
		dbDelta( $sql_prop );
	}

	/**
	 * Activation + first-load bootstrap (idempotent): create tables, schedule
	 * the daily collector + weekly memo, seed defaults.
	 */
	public function maybe_install() {
		self::ensure_schema();
		if ( ! wp_next_scheduled( self::HOOK_COLLECT ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK_COLLECT );
		}
		if ( ! wp_next_scheduled( self::HOOK_MEMO ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'weekly', self::HOOK_MEMO );
		}
		if ( false === get_option( self::OPT_ENABLED ) ) {
			update_option( self::OPT_ENABLED, '1' );
		}
		if ( false === get_option( self::OPT_MODEL ) ) {
			update_option( self::OPT_MODEL, 'gemini-3.5-flash' );
		}
	}

	/**
	 * Record every on-site search term (debounced) so search demand accrues.
	 */
	public function maybe_log_search() {
		if ( ! is_search() ) {
			return;
		}
		$term = get_search_query();
		if ( '' === $term ) {
			return;
		}
		$this->log_search( $term, (int) $GLOBALS['wp_query']->found_posts );
	}

	/**
	 * Insert one search-log row unless the same term was logged recently.
	 *
	 * @param string $term
	 * @param int    $hits
	 * @return bool
	 */
	public function log_search( $term, $hits = 0 ) {
		global $wpdb;
		if ( ! self::ready() ) {
			self::ensure_schema();
		}
		$term = trim( mb_substr( sanitize_text_field( (string) $term ), 0, 190 ) );
		if ( '' === $term ) {
			return false;
		}
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		$recent = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE term = %s AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE) ORDER BY id DESC LIMIT 1',
				self::search_table(),
				$term
			)
		);
		if ( null !== $recent ) {
			return true;
		}

		$rows = $wpdb->insert(
			self::search_table(),
			array(
				'term'       => $term,
				'hits'       => (int) $hits,
				'ip'         => $ip,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s' )
		);
		return (bool) $rows;
	}

	/**
	 * The fixed opportunity dictionary the engine scores against.
	 *
	 * @return array<string,array>
	 */
	public function niches() {
		return array(
			'land-title-verification' => array(
				'label'    => 'Land title & document verification',
				'keywords' => array( 'title', 'document', 'fraud', 'scam', 'verify', 'deed', 'stamp duty', 'registration', 'land dispute' ),
			),
			'stalled-recovery'        => array(
				'label'    => 'Stalled build recovery',
				'keywords' => array( 'stalled', 'uncompleted', 'finish or restart', 'incomplete', 'abandoned', 'recovery', 'completion' ),
			),
			'diaspora-buying'         => array(
				'label'    => 'Diaspora remote buying',
				'keywords' => array( 'diaspora', 'uk', 'usa', 'canada', 'abroad', 'remotely', 'remittances', 'buy land from abroad' ),
			),
			'build-cost'              => array(
				'label'    => 'Build cost & budget intelligence',
				'keywords' => array( 'cost', 'price', 'budget', 'estimate', 'material', 'cement', 'per m2', 'quote', 'build' ),
			),
			'rental-yield'            => array(
				'label'    => 'Rental yield & short lets',
				'keywords' => array( 'rent', 'yield', 'airbnb', 'short let', 'income', 'passive income', 'lease' ),
			),
			'financing'               => array(
				'label'    => 'Financing & payment plans',
				'keywords' => array( 'mortgage', 'loan', 'financing', 'payment plan', 'installment', 'payment' ),
			),
			'off-plan'                => array(
				'label'    => 'Off-plan & pre-sale units',
				'keywords' => array( 'off-plan', 'presale', 'pre-sale', 'under construction', 'units', 'developer' ),
			),
			'commercial'              => array(
				'label'    => 'Commercial & shop space',
				'keywords' => array( 'commercial', 'shop', 'office', 'retail', 'warehouse', 'workspace' ),
			),
			'renovation'              => array(
				'label'    => 'Renovation & turnaround',
				'keywords' => array( 'renovate', 'renovation', 'turnaround', 'refurbish', 'upgrade', 'remodel' ),
			),
			'permits'                 => array(
				'label'    => 'Permits & planning approvals',
				'keywords' => array( 'permit', 'approval', 'planning', 'district assembly', 'legalise', 'zoning' ),
			),
			'eco-build'               => array(
				'label'    => 'Eco & sustainable build',
				'keywords' => array( 'eco', 'sustainable', 'green', 'energy', 'solar', 'locally sourced' ),
			),
			'prefab-modular'          => array(
				'label'    => 'Prefab & modular construction',
				'keywords' => array( 'prefab', 'modular', 'precast', 'container', 'panelised' ),
			),
			'land-banking'            => array(
				'label'    => 'Land investment & banking',
				'keywords' => array( 'land', 'plot', 'investment', 'appreciation', 'buy land', 'resale', 'acquisition' ),
			),
			'gated-community'         => array(
				'label'    => 'Gated community development',
				'keywords' => array( 'gated', 'estate', 'community 25', 'compound', 'serviced plot' ),
			),
			'student-housing'         => array(
				'label'    => 'Student & HMO housing',
				'keywords' => array( 'student', 'hostel', 'hmo', 'dormitory', 'campus' ),
			),
		);
	}

	/**
	 * Run the daily collector: gather every signal source, score the niches,
	 * persist the snapshot. Safe to call repeatedly (idempotent).
	 *
	 * @return array Snapshot persisted.
	 */
	public function run_collect() {
		self::ensure_schema();
		$signals = $this->collect_signals();
		$scores  = $this->score_niches( $signals );
		$snap    = array(
			'collected_at' => current_time( 'mysql' ),
			'signals'      => $signals,
			'scores'       => $scores,
		);
		update_option( self::OPT_SNAPSHOT, $snap );
		update_option( self::OPT_COLLECT_AT, $snap['collected_at'] );
		return $snap;
	}

	/**
	 * Gather raw demand signals from first-party sources.
	 *
	 * @return array
	 */
	private function collect_signals() {
		global $wpdb;

		$signals = array(
			'search'    => $this->search_signal(),
			'community' => $this->community_signal(),
			'enquiries' => $this->enquiry_signal(),
			'newsletter'=> $this->newsletter_signal(),
			'feed'      => $this->feed_signal(),
			'coverage'  => $this->coverage_signal(),
		);
		return $signals;
	}

	/**
	 * Site-search terms from the last 30 days.
	 *
	 * @return array{terms:array,total:int,days:int}
	 */
	private function search_signal() {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT term, SUM(hits) AS hits, COUNT(*) AS cnt FROM %i WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY term ORDER BY cnt DESC LIMIT 60',
				self::search_table()
			),
			ARRAY_A
		);
		$terms = array();
		$total = 0;
		foreach ( (array) $rows as $row ) {
			$terms[] = array(
				'term' => (string) $row['term'],
				'hits' => (int) $row['hits'],
				'cnt'  => (int) $row['cnt'],
			);
			$total += (int) $row['cnt'];
		}
		return array( 'terms' => $terms, 'total' => $total, 'days' => 30 );
	}

	/**
	 * Community questions: count by niche keyword match over title + content.
	 *
	 * @return array{total:int, by_niche:array}
	 */
	private function community_signal() {
		if ( ! post_type_exists( 'gabochie_question' ) ) {
			return array( 'total' => 0, 'by_niche' => array() );
		}
		$posts = get_posts(
			array(
				'post_type'      => 'gabochie_question',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'date_query'     => array(
					array( 'after' => '30 days ago' ),
				),
			)
		);
		$by_niche = array();
		foreach ( (array) $posts as $id ) {
			$text = strtolower( (string) get_the_title( $id ) . ' ' . wp_strip_all_tags( (string) get_post_field( 'post_content', $id ) ) );
			foreach ( $this->niches() as $slug => $n ) {
				foreach ( $n['keywords'] as $kw ) {
					if ( false !== strpos( $text, $kw ) ) {
						$by_niche[ $slug ] = (int) ( $by_niche[ $slug ] ?? 0 ) + 1;
						break;
					}
				}
			}
		}
		return array( 'total' => count( (array) $posts ), 'by_niche' => $by_niche );
	}

	/**
	 * Marketplace + lead-hub enquiries in the CRM (list_key=leadhub-property).
	 *
	 * @return array{total:int, by_niche:array}
	 */
	private function enquiry_signal() {
		global $wpdb;
		if ( ! function_exists( 'gabochie_crm_ready' ) || ! gabochie_crm_ready() ) {
			return array( 'total' => 0, 'by_niche' => array() );
		}
		$table = $wpdb->prefix . 'gab_contacts';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT source, tags, list_key FROM %i WHERE list_key = %s AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)',
				$table,
				'leadhub-property'
			),
			ARRAY_A
		);
		$by_niche = array();
		foreach ( (array) $rows as $row ) {
			$text = strtolower( (string) $row['source'] . ' ' . (string) $row['tags'] );
			foreach ( $this->niches() as $slug => $n ) {
				foreach ( $n['keywords'] as $kw ) {
					if ( false !== strpos( $text, $kw ) ) {
						$by_niche[ $slug ] = (int) ( $by_niche[ $slug ] ?? 0 ) + 1;
						break;
					}
				}
			}
		}
		return array( 'total' => count( (array) $rows ), 'by_niche' => $by_niche );
	}

	/**
	 * Newsletter audience health: subscribed count, engaged-30d, unsub map size.
	 *
	 * @return array{subscribed:int, engaged_30d:int, unsubs:int}
	 */
	private function newsletter_signal() {
		$subscribed = 0;
		$engaged    = 0;
		$unsubs     = 0;
		if ( function_exists( 'gabochie_crm_subscribers' ) && function_exists( 'gabochie_crm_ready' ) && gabochie_crm_ready() ) {
			$subscribed = count( gabochie_crm_subscribers() );
			global $wpdb;
			$table = $wpdb->prefix . 'gab_contacts';
			$engaged = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE last_engaged_at > DATE_SUB(NOW(), INTERVAL 30 DAY)',
					$table
				)
			);
		}
		if ( function_exists( 'gabochie_newsletter_unsubs' ) ) {
			$unsubs = count( gabochie_newsletter_unsubs() );
		}
		return array( 'subscribed' => (int) $subscribed, 'engaged_30d' => (int) $engaged, 'unsubs' => (int) $unsubs );
	}

	/**
	 * News-portal feed pulse: how many headlines matched each niche recently.
	 *
	 * @return array{total:int, by_niche:array}
	 */
	private function feed_signal() {
		if ( ! class_exists( 'Gabochie_News_Portal' ) ) {
			return array( 'total' => 0, 'by_niche' => array() );
		}
		$posts = get_posts(
			array(
				'post_type'      => 'gd_news',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'date_query'     => array(
					array( 'after' => '14 days ago' ),
				),
			)
		);
		$by_niche = array();
		foreach ( (array) $posts as $id ) {
			$text = strtolower( (string) get_the_title( $id ) . ' ' . wp_strip_all_tags( (string) get_post_field( 'post_content', $id ) ) );
			foreach ( $this->niches() as $slug => $n ) {
				foreach ( $n['keywords'] as $kw ) {
					if ( false !== strpos( $text, $kw ) ) {
						$by_niche[ $slug ] = (int) ( $by_niche[ $slug ] ?? 0 ) + 1;
						break;
					}
				}
			}
		}
		return array( 'total' => count( (array) $posts ), 'by_niche' => $by_niche );
	}

	/**
	 * Content coverage: published posts + listings + questions mentioning each
	 * niche — the "gap" side of the opportunity (low coverage = more room).
	 *
	 * @return array{by_niche:array}
	 */
	private function coverage_signal() {
		$posts = get_posts(
			array(
				'post_type'      => array( 'post', Gabochie_Market_Research::post_types() ),
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
			)
		);
		$by_niche = array();
		foreach ( (array) $posts as $id ) {
			$text = strtolower( (string) get_the_title( $id ) . ' ' . wp_strip_all_tags( (string) get_post_field( 'post_content', $id ) ) );
			foreach ( $this->niches() as $slug => $n ) {
				foreach ( $n['keywords'] as $kw ) {
					if ( false !== strpos( $text, $kw ) ) {
						$by_niche[ $slug ] = (int) ( $by_niche[ $slug ] ?? 0 ) + 1;
						break;
					}
				}
			}
		}
		return array( 'by_niche' => $by_niche );
	}

	/**
	 * Post types to include in the coverage probe (own CPTs when registered).
	 *
	 * @return array
	 */
	public static function post_types() {
		$types = array();
		foreach ( array( 'gd_property', 'gabochie_question', 'gd_news' ) as $type ) {
			if ( post_type_exists( $type ) ) {
				$types[] = $type;
			}
		}
		return $types;
	}

	/**
	 * Score every niche from the signals. Higher = more unmet, monetizable need.
	 *
	 * @param  array $signals
	 * @return array<string,array{score:int,search:int,community:int,enquiries:int,feed:int,gap:int}> Sorted.
	 */
	public function score_niches( array $signals ) {
		$raw = array();

		foreach ( $this->niches() as $slug => $n ) {
			$search    = $this->niche_search_count( $slug, $signals['search']['terms'] ?? array() );
			$community = (int) ( $signals['community']['by_niche'][ $slug ] ?? 0 );
			$enq       = (int) ( $signals['enquiries']['by_niche'][ $slug ] ?? 0 );
			$feed      = (int) ( $signals['feed']['by_niche'][ $slug ] ?? 0 );
			$coverage  = (int) ( $signals['coverage']['by_niche'][ $slug ] ?? 0 );

			$raw[ $slug ] = array(
				'search'    => $search,
				'community' => $community,
				'enquiries' => $enq,
				'feed'      => $feed,
				'coverage'  => $coverage,
			);
		}

		$max_search    = max( array_column( $raw, 'search' ) ) ?: 1;
		$max_community = max( array_column( $raw, 'community' ) ) ?: 1;
		$max_enq       = max( array_column( $raw, 'enquiries' ) ) ?: 1;
		$max_feed      = max( array_column( $raw, 'feed' ) ) ?: 1;

		$scores = array();
		foreach ( $raw as $slug => $r ) {
			$gap = $r['coverage'] <= 2 ? 10 : ( $r['coverage'] <= 6 ? 5 : 0 );
			$score = (int) round(
				( 40 * ( $r['search'] / $max_search ) )
				+ ( 25 * ( $r['community'] / $max_community ) )
				+ ( 15 * ( $r['enquiries'] / $max_enq ) )
				+ ( 10 * ( $r['feed'] / $max_feed ) )
				+ $gap
			);
			$scores[ $slug ] = array(
				'score'     => min( 100, $score ),
				'search'    => $r['search'],
				'community' => $r['community'],
				'enquiries' => $r['enquiries'],
				'feed'      => $r['feed'],
				'coverage'  => $r['coverage'],
				'gap'       => $gap,
			);
		}

		uasort(
			$scores,
			static function ( $a, $b ) {
				return (int) $b['score'] - (int) $a['score'];
			}
		);
		return $scores;
	}

	/**
	 * Sum 30-day site-search frequency for a niche's keywords.
	 *
	 * @param  string $slug
	 * @param  array  $terms
	 * @return int
	 */
	private function niche_search_count( $slug, array $terms ) {
		$n        = $this->niches()[ $slug ] ?? array( 'keywords' => array() );
		$keywords = array_map( 'strtolower', (array) $n['keywords'] );
		$count    = 0;
		foreach ( $terms as $row ) {
			$term = strtolower( (string) $row['term'] );
			foreach ( $keywords as $kw ) {
				if ( false !== strpos( $term, $kw ) ) {
					$count += (int) $row['cnt'];
					break;
				}
			}
		}
		return $count;
	}

	/**
	 * Trigger a memo generation (cron + admin + CLI entry point).
	 *
	 * @return array|null Memo data, or null on failure.
	 */
	public function run_memo() {
		$snap = get_option( self::OPT_SNAPSHOT, array() );
		if ( empty( $snap ) || empty( $snap['scores'] ) ) {
			$snap = $this->run_collect();
		}
		return Gabochie_Market_Research_AI::instance()->generate_memo( $snap );
	}

	/**
	 * Append an entry to the rolling error log (last 10).
	 *
	 * @param string $message
	 */
	public static function log_error( $message ) {
		$log   = (array) get_option( self::OPT_ERRORS, array() );
		$log[] = array(
			'time'  => current_time( 'mysql' ),
			'error' => (string) $message,
		);
		$log = array_slice( $log, -10 );
		update_option( self::OPT_ERRORS, $log );
	}

	/**
	 * Read the current snapshot with defaults.
	 *
	 * @return array
	 */
	public static function snapshot() {
		return (array) get_option( self::OPT_SNAPSHOT, array() );
	}
}