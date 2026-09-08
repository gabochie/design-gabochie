<?php
/**
 * Gabochie News Portal core.
 *
 * Owns the gnp news vertical on gabochiedesign.com: a `news` post type plus
 * `news_category` taxonomy for AEC / built-environment / property headlines in
 * Ghana, curated from RSS sources. Ships the sources registry, the SimplePie
 * fetch + relevance filter (each item lands as a DRAFT in the editorial review
 * queue — nothing publishes automatically), the "Gabochie take" AI note, and
 * the helpers the newsletter digest reads.
 *
 * Never publishes without a human: ingestion stops at `draft`; the review
 * queue (Gabochie News Portal admin) is the only publish gate.
 *
 * @package Gabochie_News_Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * News portal core.
 */
class Gabochie_News_Portal {

	const CPT           = 'news';
	const TAX           = 'news_category';
	const HOOK_FETCH    = 'gnp_fetch';
	const OPT_SOURCES   = 'gnp_sources';
	const OPT_ERRORS    = 'gnp_errors';
	const OPT_LAST      = 'gnp_last_fetch';
	const OPT_PAGE      = 'gnp_news_page_id';
	const OPT_PAGE_SEED = 'gnp_news_page_created';

	const META_SOURCE     = '_gnp_source';
	const META_SOURCE_URL = '_gnp_source_url';
	const META_SOURCE_TXT = '_gnp_source_name';
	const META_TAKE       = '_gnp_take';
	const META_TAKE_STAT  = '_gnp_take_status';
	const META_AI_SRC     = '_gnp_ai_source';
	const META_FETCHED    = '_gnp_fetched_at';
	const META_CATEGORY   = '_gnp_category';

	const TAKE_DRAFT    = 'draft';
	const TAKE_APPROVED = 'approved';
	const TAKE_REJECTED = 'rejected';

	/** @var Gabochie_News_Portal|null */
	private static $instance = null;

	/** Slugs never filtered out (domain-focused outlets). */
	private $always_relevant = array( 'bank-of-ghana', 'construction-review' );

	/** @return Gabochie_News_Portal */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire core hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( self::HOOK_FETCH, array( $this, 'run_fetch' ) );
		add_action( 'init', array( $this, 'maybe_install' ) );
		add_action( 'after_plugin_row_' . plugin_basename( GABOCHIE_NEWS_FILE ), array( $this, 'render_activation_notice' ) );
	}

	/**
	 * Cron-safe wrapper around fetch_all() (hooks must not return values).
	 */
	public function run_fetch() {
		$this->fetch_all();
	}

	/**
	 * Register the news post type + news_category taxonomy once.
	 */
	public function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'               => __( 'News', 'gabochie-news-portal' ),
					'singular_name'      => __( 'News item', 'gabochie-news-portal' ),
					'add_new'            => __( 'Add news', 'gabochie-news-portal' ),
					'add_new_item'       => __( 'Add news item', 'gabochie-news-portal' ),
					'edit_item'          => __( 'Edit news item', 'gabochie-news-portal' ),
					'new_item'           => __( 'New news item', 'gabochie-news-portal' ),
					'view_item'          => __( 'View news item', 'gabochie-news-portal' ),
					'search_items'       => __( 'Search news', 'gabochie-news-portal' ),
					'not_found'          => __( 'No news items found.', 'gabochie-news-portal' ),
					'not_found_in_trash' => __( 'No news items found in trash.', 'gabochie-news-portal' ),
				),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => true,
				'has_archive'     => false,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
				'taxonomies'      => array( self::TAX ),
				'rewrite'         => array(
					'slug'       => 'news',
					'with_front' => true,
				),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);

		register_taxonomy(
			self::TAX,
			array( self::CPT ),
			array(
				'labels'            => array(
					'name'          => __( 'News categories', 'gabochie-news-portal' ),
					'singular_name' => __( 'News category', 'gabochie-news-portal' ),
					'search_items'  => __( 'Search news categories', 'gabochie-news-portal' ),
					'all_items'     => __( 'All news categories', 'gabochie-news-portal' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'rewrite'           => array(
					'slug'       => 'news/category',
					'with_front' => false,
				),
			)
		);

		$this->seed_categories();
	}

	/**
	 * Activation + first-load bootstrap (idempotent): seed sources, schedule the
	 * daily fetch, create the /news/ landing page.
	 */
	public function maybe_install() {
		$this->seed_sources();
		$this->seed_categories();
		$this->ensure_fetch_cron();
		$this->ensure_news_page();
	}

	/**
	 * Fill the sources option from the shipped data/sources.json when empty.
	 */
	public function seed_sources() {
		if ( false !== get_option( self::OPT_SOURCES, false ) ) {
			return;
		}
		$file    = dirname( GABOCHIE_NEWS_FILE ) . '/data/sources.json';
		$json    = is_readable( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data    = json_decode( (string) $json, true );
		$sources = array();
		if ( is_array( $data ) && ! empty( $data['sources'] ) && is_array( $data['sources'] ) ) {
			foreach ( $data['sources'] as $row ) {
				$slug = isset( $row['slug'] ) ? sanitize_key( (string) $row['slug'] ) : '';
				$url  = isset( $row['url'] ) ? esc_url_raw( (string) $row['url'] ) : '';
				$name = isset( $row['name'] ) ? sanitize_text_field( (string) $row['name'] ) : '';
				if ( '' === $slug || '' === $url || '' === $name ) {
					continue;
				}
				$sources[ $slug ] = array(
					'slug'    => $slug,
					'name'    => $name,
					'url'     => $url,
					'trusted' => ! empty( $row['trusted'] ),
					'ai_take' => ! empty( $row['ai_take'] ),
					'enabled' => '1',
					'added'   => current_time( 'mysql' ),
				);
			}
		}
		update_option( self::OPT_SOURCES, $sources );
	}

	/**
	 * Ensure the seven default news categories exist.
	 */
	public function seed_categories() {
		$terms = array(
			'policy-finance'   => __( 'Policy & Finance', 'gabochie-news-portal' ),
			'projects'         => __( 'Projects', 'gabochie-news-portal' ),
			'land-titles'      => __( 'Land & Titles', 'gabochie-news-portal' ),
			'materials-costs'  => __( 'Materials & Costs', 'gabochie-news-portal' ),
			'market-data'      => __( 'Market Data', 'gabochie-news-portal' ),
			'jobs-procurement' => __( 'Jobs & Procurement', 'gabochie-news-portal' ),
			'development'      => __( 'Development', 'gabochie-news-portal' ),
		);
		foreach ( $terms as $slug => $label ) {
			if ( ! term_exists( $slug, self::TAX ) ) {
				wp_insert_term( $label, self::TAX, array( 'slug' => $slug ) );
			}
		}
	}

	/**
	 * Schedule the daily 05:45 fetch (Accra is UTC+0, no DST).
	 */
	public function ensure_fetch_cron() {
		if ( wp_next_scheduled( self::HOOK_FETCH ) ) {
			return;
		}
		$when = strtotime( gmdate( 'Y-m-d' ) . ' 05:45:00' );
		if ( $when < time() ) {
			$when += DAY_IN_SECONDS;
		}
		wp_schedule_event( (int) $when, 'daily', self::HOOK_FETCH );
	}

	/**
	 * Create (once) the /news/ landing page bound to the theme News Hub template.
	 */
	public function ensure_news_page() {
		if ( get_option( self::OPT_PAGE_SEED, '0' ) === '1' ) {
			return;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'News', 'gabochie-news-portal' ),
				'post_name'    => 'news',
				'post_content' => '',
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return;
		}
		update_post_meta( (int) $id, '_wp_page_template', 'page-templates/template-news.php' );
		update_option( self::OPT_PAGE, (int) $id );
		update_option( self::OPT_PAGE_SEED, '1' );
	}

	/** @return array Source registry (slug-indexed). */
	public function sources() {
		$sources = get_option( self::OPT_SOURCES, array() );
		return is_array( $sources ) ? $sources : array();
	}

	/**
	 * @param string $slug
	 * @return array|null
	 */
	public function get_source( $slug ) {
		$sources = $this->sources();
		return isset( $sources[ $slug ] ) ? $sources[ $slug ] : null;
	}

	/**
	 * @param array $sources Full registry to persist (already sanitized).
	 */
	public function save_sources( array $sources ) {
		update_option( self::OPT_SOURCES, $sources );
	}

	/**
	 * Does the text mention the AEC / built-environment / property sector?
	 *
	 * @param string $text
	 * @param string $slug Source slug (domain-focused outlets bypass the filter).
	 * @return bool
	 */
	public function is_relevant( $text, $slug = '' ) {
		if ( in_array( $slug, $this->always_relevant, true ) ) {
			return true;
		}
		$needles = $this->relevance_keywords();
		$hay     = strtolower( (string) $text );
		foreach ( $needles as $n ) {
			if ( false !== strpos( $hay, $n ) ) {
				return true;
			}
		}
		return false;
	}

	/** Keywords that mark a story as AEC/built-environment/property in Ghana. */
	private function relevance_keywords() {
		return array(
			'construction',
			'constructor',
			'contractor',
			'building',
			'built',
			'real estate',
			'property',
			'housing',
			'affordable housing',
			'estate',
			'developer',
			'land',
			'title',
			'deed',
			'lease',
			'mortgage',
			'cement',
			'rebar',
			'steel',
			'aggregate',
			'sand',
			'timber',
			'infrastructure',
			'road',
			'bridge',
			'rail',
			'seaport',
			'architect',
			'architecture',
			'structural',
			'built environment',
			'quarry',
			'block manufacturer',
			'works and housing',
			'minister for works',
			'zoning',
			'planning permit',
			'building permit',
			'building code',
			'greda',
			'year of roads',
			'redevelopment',
			'property market',
			'rent',
			'price index',
			'colonel',
			'land commission',
		);
	}

	/**
	 * Map headline text onto a news_category slug (falls back to development).
	 *
	 * @param string $text
	 * @return string
	 */
	public function map_category( $text ) {
		$t   = strtolower( (string) $text );
		$map = array(
			'policy-finance'   => array( 'policy', 'budget', 'finance', 'tax', 'levy', 'tariff', 'regulation', 'act ', 'code ', 'permit', 'ministry', 'parliament', 'authority', 'bank of ghana', 'interest rate', 'monetary', 'inflation', 'cedi', 'exchange rate', 'loan', 'credit', 'bond', 'treasury', 'mortgage' ),
			'materials-costs'  => array( 'cement', 'rebar', 'steel', 'aggregate', 'sand', 'timber', 'plywood', 'building material', 'material price', 'cost of building', 'building cost', 'price of ', 'diesel', 'inflation' ),
			'land-titles'      => array( 'land', 'title', 'deed', 'lease', 'compensation', 'acquisition', 'conveyancing', 'registration', 'land commission', 'public lands' ),
			'projects'         => array( 'project', 'contract', 'tender', 'procurement', 'bid', 'awarded', 'groundbreaking', 'sod cutting', 'commencement', 'completion', 'rail', 'road', 'bridge', 'seaport', 'airport', 'dam', 'hospital', 'school' ),
			'market-data'      => array( 'property market', 'real estate', 'rent', 'price index', 'housing deficit', 'demand', 'supply', 'vacancy' ),
			'jobs-procurement' => array( 'jobs', 'vacancies', 'recruitment', 'hiring', 'tender', 'procurement', 'bid' ),
		);
		foreach ( $map as $slug => $needles ) {
			foreach ( $needles as $n ) {
				if ( false !== strpos( $t, $n ) ) {
					return $slug;
				}
			}
		}
		return 'development';
	}

	/**
	 * Ingest every enabled source. Returns a per-source result summary.
	 *
	 * @return array
	 */
	public function fetch_all() {
		if ( ! function_exists( 'fetch_feed' ) ) {
			include_once ABSPATH . 'wp-includes/feed.php';
		}
		$out      = array();
		$existing = $this->existing_source_urls();

		foreach ( $this->sources() as $source ) {
			if ( '1' !== (string) ( isset( $source['enabled'] ) ? $source['enabled'] : '0' ) ) {
				continue;
			}
			$res                    = $this->fetch_source( $source, $existing );
			$out[ $source['slug'] ] = $res;
		}

		update_option( self::OPT_LAST, current_time( 'mysql' ) );
		return $out;
	}

	/**
	 * Fetch one source and create draft posts for new, relevant items.
	 *
	 * @param array $source   Single source row.
	 * @param array $existing Set of URLs already ingested.
	 * @return array
	 */
	public function fetch_source( array $source, array $existing = array() ) {
		if ( empty( $existing ) ) {
			$existing = $this->existing_source_urls();
		}
		$feed = fetch_feed( $source['url'] );
		$out  = array(
			'ok'     => false,
			'error'  => '',
			'added'  => 0,
			'dup'    => 0,
			'hidden' => 0,
			'items'  => 0,
		);
		if ( is_wp_error( $feed ) ) {
			$out['error'] = $feed->get_error_message();
			$this->log_error( $source['slug'] . ': ' . $out['error'] );
			return $out;
		}
		$out['ok'] = true;

		$items        = (array) $feed->get_items( 0, 20 );
		$out['items'] = count( $items );

		foreach ( $items as $item ) {
			$link = (string) $item->get_link();
			$link = $link ? $link : (string) $item->get_permalink();
			if ( '' === $link || isset( $existing[ $link ] ) ) {
				++$out['dup'];
				continue;
			}
			$title = sanitize_text_field( wp_strip_all_tags( (string) $item->get_title() ) );
			$text  = $title . ' ' . $this->feed_text( (string) $item->get_description() );
			if ( '' === $title || ! $this->is_relevant( $text, $source['slug'] ) ) {
				++$out['hidden'];
				continue;
			}
			$id = $this->insert_item( $source, $item, $link, $title, $text );
			if ( $id ) {
				$existing[ $link ] = true;
				++$out['added'];
			}
		}
		return $out;
	}

	/** Sanitized plain text from a feed description. */
	private function feed_text( $html ) {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $html ) ) );
	}

	/**
	 * Create the draft news post for one feed item.
	 *
	 * @param array $source Source row.
	 * @param object $item  SimplePie_Item.
	 * @param string $link  Original permalink (dedupe key).
	 * @param string $title Cleaned title.
	 * @param string $text  Title + excerpt text for category mapping.
	 * @return int|false Post ID.
	 */
	private function insert_item( array $source, $item, $link, $title, $text ) {
		$stamp   = $item->get_date( 'U' );
		$post_ts = $stamp ? (int) $stamp : time();

		$excerpt = wp_trim_words( $this->feed_text( (string) $item->get_description() ), 45, '…' );
		if ( '' === $excerpt ) {
			$excerpt = $title . '.';
		}

		$category = $this->map_category( $text );
		$content  = '<p>' . esc_html( $excerpt ) . '</p>' . "\n";
		$content .= '<p><a href="' . esc_url( $link ) . '" rel="noopener">' . esc_html__( 'Read the full story at', 'gabochie-news-portal' ) . ' ' . esc_html( $source['name'] ) . '</a></p>';

		$id = wp_insert_post(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'draft',
				'post_title'     => $title,
				'post_excerpt'   => $excerpt,
				'post_content'   => $content,
				'post_date'      => gmdate( 'Y-m-d H:i:s', $post_ts ),
				'post_author'    => 1,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			$this->log_error( 'insert failed for "' . $title . '": ' . $id->get_error_message() );
			return false;
		}

		$id = (int) $id;
		update_post_meta( $id, self::META_SOURCE, sanitize_key( $source['slug'] ) );
		update_post_meta( $id, self::META_SOURCE_URL, esc_url_raw( $link ) );
		update_post_meta( $id, self::META_SOURCE_TXT, sanitize_text_field( $source['name'] ) );
		update_post_meta( $id, self::META_CATEGORY, $category );
		update_post_meta( $id, self::META_FETCHED, current_time( 'mysql' ) );
		update_post_meta( $id, self::META_TAKE_STAT, self::TAKE_DRAFT );

		wp_set_object_terms( $id, array( $category ), self::TAX );
		return $id;
	}

	/**
	 * Existing source URLs as a hash set (slug_of_url => true).
	 *
	 * @return array<string,bool>
	 */
	public function existing_source_urls() {
		$rows = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'any',
				'posts_per_page' => 2000,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$set  = array();
		foreach ( $rows as $pid ) {
			$url = (string) get_post_meta( (int) $pid, self::META_SOURCE_URL, true );
			if ( '' !== $url ) {
				$set[ $url ] = true;
			}
		}
		return $set;
	}

	/**
	 * News items published since a timestamp (for the daily digest).
	 *
	 * @param int $from_ts
	 * @param int $limit
	 * @return WP_Post[]
	 */
	public function published_since( $from_ts, $limit = 5 ) {
		$args = array(
			'post_type'      => self::CPT,
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, $limit ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'date_query'     => array(
				array(
					'after'     => gmdate( 'Y-m-d H:i:s', (int) $from_ts ),
					'inclusive' => true,
				),
			),
		);
		return get_posts( $args );
	}

	/** @param string $msg */
	public function log_error( $msg ) {
		$errors   = get_option( self::OPT_ERRORS, array() );
		$errors   = is_array( $errors ) ? $errors : array();
		$errors[] = array(
			'time'  => current_time( 'mysql' ),
			'error' => substr( $msg, 0, 300 ),
		);
		update_option( self::OPT_ERRORS, array_slice( $errors, -20 ) );
	}

	/** @return array<int,array{time:string,error:string}> */
	public function recent_errors() {
		$errors = get_option( self::OPT_ERRORS, array() );
		return is_array( $errors ) ? $errors : array();
	}

	/** Row shown under the plugin on the Plugins screen after upgrades. */
	public function render_activation_notice() {
		if ( get_option( self::OPT_PAGE_SEED, '0' ) !== '0' ) {
			return;
		}
		$url = admin_url( 'admin.php?page=gabochie-news-portal' );
		echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Gabochie News Portal:', 'gabochie-news-portal' ) . ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'review sources and the editorial queue', 'gabochie-news-portal' ) . '</a>.</p></div>';
	}
}
