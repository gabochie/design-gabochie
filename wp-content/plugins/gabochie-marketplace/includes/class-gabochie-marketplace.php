<?php
/**
 * Gabochie Marketplace core.
 *
 * Owns the property + development marketplace on design.gabochie.com: a
 * `gd_property` post type plus `gd_property_type` + `gd_property_status`
 * taxonomies, sample-listing seeding, the weekly intelligence-refresh cron,
 * and the helpers both the theme templates and the enquiry funnel read.
 *
 * The marketplace position: "Find property, know its potential, make it
 * happen." Every listing carries an AI-generated Property Intelligence Report
 * (location, comparables, development feasibility via the build-cost estimator,
 * risk flags, recommendation) cached in `_gd_intelligence`.
 *
 * @package Gabochie_Marketplace
 */

defined( 'ABSPATH' ) || exit;

/**
 * Marketplace core.
 */
class Gabochie_Marketplace {


	const CPT          = 'gd_property';
	const TAX_TYPE     = 'gd_property_type';
	const TAX_STATUS   = 'gd_property_status';
	const HOOK_REFRESH = 'gmp_intelligence_refresh';

	const OPT_PAGE      = 'gmp_page_id';
	const OPT_PAGE_SEED = 'gmp_page_created';
	const OPT_SEEDED    = 'gmp_seeded';
	const OPT_AI_MODEL  = 'gmp_ai_model';
	const OPT_ERRORS    = 'gmp_errors';

	const META_PRICE          = '_gd_price';
	const META_PRICE_USD      = '_gd_price_usd';
	const META_LOCATION       = '_gd_location_text';
	const META_LAT            = '_gd_lat';
	const META_LNG            = '_gd_lng';
	const META_SIZE_M2        = '_gd_size_m2';
	const META_BEDROOMS       = '_gd_bedrooms';
	const META_BATHROOMS      = '_gd_bathrooms';
	const META_AGENT_NAME     = '_gd_agent_name';
	const META_AGENT_PHONE    = '_gd_agent_phone';
	const META_AGENT_EMAIL    = '_gd_agent_email';
	const META_IMAGES         = '_gd_images';
	const META_INTEL          = '_gd_intelligence';
	const META_INTEL_AT       = '_gd_intelligence_at';
	const META_INTEL_PROVIDER = '_gd_intelligence_provider';
	const META_FEATURED       = '_gd_featured';

	/**
	 * @var Gabochie_Marketplace|null
	 */
	private static $instance = null;

	/**
	 * @return Gabochie_Marketplace
	 */
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
		add_action( 'init', array( $this, 'maybe_install' ) );
		add_action( self::HOOK_REFRESH, array( $this, 'run_intelligence_refresh' ) );
		add_action( 'transition_post_status', array( $this, 'maybe_generate_intelligence' ), 10, 3 );
		add_filter( 'rewrite_rules_array', array( $this, 'trim_marketplace_attachment_rules' ) );
	}

	/**
	 * Drop the CPT's leaked trailing-attachment rules that shadow the nested
	 * `marketplace/type/<term>` / `marketplace/status/<term>` taxonomy rewrites
	 * (marketplace/<slug>/<any> resolved as attachment= before the taxonomy
	 * rule ever ran).
	 *
	 * @param  array $rules
	 * @return array
	 */
	public function trim_marketplace_attachment_rules( $rules ) {
		foreach ( (array) $rules as $regex => $query ) {
			if ( 0 === strpos( $regex, 'marketplace/[^/]+/([^/]+)' ) && false !== strpos( (string) $query, 'attachment=' ) ) {
				unset( $rules[ $regex ] );
			}
		}
		return $rules;
	}

	/**
	 * Cron-safe wrapper around the intelligence refresh.
	 */
	public function run_intelligence_refresh() {
		$this->refresh_stale_intelligence();
	}

	/**
	 * Auto-generate an intelligence report when a listing is published with
	 * none yet (idempotent — only when _gd_intelligence is empty).
	 *
	 * @param string  $new_status
	 * @param string  $old_status
	 * @param WP_Post $post
	 */
	public function maybe_generate_intelligence( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || self::CPT !== $post->post_type ) {
			return;
		}
		if ( '' !== (string) get_post_meta( $post->ID, self::META_INTEL, true ) ) {
			return;
		}
		Gabochie_Marketplace_AI::instance()->generate_report( (int) $post->ID );
	}

	/**
	 * Register the gd_property post type + the two taxonomies once.
	 */
	public function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'               => __( 'Properties', 'gabochie-marketplace' ),
					'singular_name'      => __( 'Property', 'gabochie-marketplace' ),
					'add_new'            => __( 'Add listing', 'gabochie-marketplace' ),
					'add_new_item'       => __( 'Add property listing', 'gabochie-marketplace' ),
					'edit_item'          => __( 'Edit listing', 'gabochie-marketplace' ),
					'new_item'           => __( 'New listing', 'gabochie-marketplace' ),
					'view_item'          => __( 'View listing', 'gabochie-marketplace' ),
					'search_items'       => __( 'Search listings', 'gabochie-marketplace' ),
					'not_found'          => __( 'No listings found.', 'gabochie-marketplace' ),
					'not_found_in_trash' => __( 'No listings found in trash.', 'gabochie-marketplace' ),
				),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => true,
				'has_archive'     => false,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
				'taxonomies'      => array( self::TAX_TYPE, self::TAX_STATUS ),
				'rewrite'         => array(
					'slug'       => 'marketplace',
					'with_front' => false,
				),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);

		register_taxonomy(
			self::TAX_TYPE,
			array( self::CPT ),
			array(
				'labels'            => array(
					'name'          => __( 'Property types', 'gabochie-marketplace' ),
					'singular_name' => __( 'Property type', 'gabochie-marketplace' ),
					'search_items'  => __( 'Search property types', 'gabochie-marketplace' ),
					'all_items'     => __( 'All property types', 'gabochie-marketplace' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'rewrite'           => array(
					'slug'       => 'marketplace/type',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			self::TAX_STATUS,
			array( self::CPT ),
			array(
				'labels'            => array(
					'name'          => __( 'Listing statuses', 'gabochie-marketplace' ),
					'singular_name' => __( 'Listing status', 'gabochie-marketplace' ),
					'search_items'  => __( 'Search listing statuses', 'gabochie-marketplace' ),
					'all_items'     => __( 'All listing statuses', 'gabochie-marketplace' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'rewrite'           => array(
					'slug'       => 'marketplace/status',
					'with_front' => false,
				),
			)
		);

		$this->seed_terms();
	}

	/**
	 * Activation + first-load bootstrap (idempotent): seed terms, create the
	 * /marketplace/ page, schedule the intelligence refresh.
	 */
	public function maybe_install() {
		$this->seed_terms();
		$this->ensure_marketplace_page();
		$this->ensure_refresh_cron();
	}

	/**
	 * Ensure the default property-type + status terms exist.
	 */
	public function seed_terms() {
		$types = array(
			'land'             => __( 'Land', 'gabochie-marketplace' ),
			'house'            => __( 'House', 'gabochie-marketplace' ),
			'apartment'        => __( 'Apartment', 'gabochie-marketplace' ),
			'commercial'       => __( 'Commercial', 'gabochie-marketplace' ),
			'off-plan'         => __( 'Off-Plan', 'gabochie-marketplace' ),
			'development-site' => __( 'Development Site', 'gabochie-marketplace' ),
		);
		foreach ( $types as $slug => $label ) {
			if ( ! term_exists( $slug, self::TAX_TYPE ) ) {
				wp_insert_term( $label, self::TAX_TYPE, array( 'slug' => $slug ) );
			}
		}

		$statuses = array(
			'for-sale'          => __( 'For Sale', 'gabochie-marketplace' ),
			'for-rent'          => __( 'For Rent', 'gabochie-marketplace' ),
			'under-development' => __( 'Under Development', 'gabochie-marketplace' ),
			'sold'              => __( 'Sold', 'gabochie-marketplace' ),
			'jv-opportunity'    => __( 'JV Opportunity', 'gabochie-marketplace' ),
		);
		foreach ( $statuses as $slug => $label ) {
			if ( ! term_exists( $slug, self::TAX_STATUS ) ) {
				wp_insert_term( $label, self::TAX_STATUS, array( 'slug' => $slug ) );
			}
		}
	}

	/**
	 * Create (once) the /marketplace/ landing page bound to the theme template.
	 */
	public function ensure_marketplace_page() {
		if ( get_option( self::OPT_PAGE_SEED, '0' ) === '1' ) {
			return;
		}
		$existing = get_page_by_path( 'marketplace' );
		if ( $existing ) {
			update_option( self::OPT_PAGE, (int) $existing->ID );
			update_option( self::OPT_PAGE_SEED, '1' );
			return;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Marketplace', 'gabochie-marketplace' ),
				'post_name'    => 'marketplace',
				'post_content' => '',
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return;
		}
		update_post_meta( (int) $id, '_wp_page_template', 'page-templates/template-marketplace.php' );
		update_option( self::OPT_PAGE, (int) $id );
		update_option( self::OPT_PAGE_SEED, '1' );
	}

	/**
	 * Schedule the weekly intelligence refresh.
	 */
	public function ensure_refresh_cron() {
		if ( wp_next_scheduled( self::HOOK_REFRESH ) ) {
			return;
		}
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::HOOK_REFRESH );
	}

	/**
	 * Refresh intelligence reports for published listings that are stale (older
	 * than a week) or missing entirely. Returns count of reports generated.
	 *
	 * @return int
	 */
	public function refresh_stale_intelligence() {
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS );
		$ids    = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => self::META_INTEL_AT,
						'compare' => '<',
						'value'   => $cutoff,
					),
					array(
						'key'     => self::META_INTEL,
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		$count  = 0;
		foreach ( (array) $ids as $id ) {
			$ok = Gabochie_Marketplace_AI::instance()->generate_report( (int) $id );
			if ( $ok ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Pricing helpers shared by templates + AI.
	 *
	 * @param  int $post_id
	 * @return string Formatted GHS price (e.g. "GHS 1,200,000").
	 */
	public static function price_label( $post_id ) {
		$price = (float) get_post_meta( $post_id, self::META_PRICE, true );
		if ( $price <= 0 ) {
			return __( 'Price on request', 'gabochie-marketplace' );
		}
		return 'GHS ' . number_format( $price );
	}

	/**
	 * All type terms.
	 *
	 * @return array<int,WP_Term>
	 */
	public static function type_terms() {
		return get_terms(
			array(
				'taxonomy'   => self::TAX_TYPE,
				'hide_empty' => false,
			)
		);
	}

	/**
	 * All status terms.
	 *
	 * @return array<int,WP_Term>
	 */
	public static function status_terms() {
		return get_terms(
			array(
				'taxonomy'   => self::TAX_STATUS,
				'hide_empty' => false,
			)
		);
	}

	/**
	 * The first type term for a listing (slug), or ''.
	 *
	 * @param  int $post_id
	 * @return string
	 */
	public static function get_type_slug( $post_id ) {
		$t = get_the_terms( $post_id, self::TAX_TYPE );
		return ( $t && ! is_wp_error( $t ) ) ? $t[0]->slug : '';
	}

	/**
	 * The first status term for a listing (slug), or ''.
	 *
	 * @param  int $post_id
	 * @return string
	 */
	public static function get_status_slug( $post_id ) {
		$t = get_the_terms( $post_id, self::TAX_STATUS );
		return ( $t && ! is_wp_error( $t ) ) ? $t[0]->slug : '';
	}

	/**
	 * Whether a listing has a cached intelligence report.
	 *
	 * @param  int $post_id
	 * @return bool
	 */
	public static function has_intelligence( $post_id ) {
		return '' !== (string) get_post_meta( $post_id, self::META_INTEL, true );
	}

	/**
	 * Decoded intelligence report (array) or null.
	 *
	 * @param  int $post_id
	 * @return array|null
	 */
	public static function intelligence( $post_id ) {
		$raw = get_post_meta( $post_id, self::META_INTEL, true );
		if ( '' === $raw ) {
			return null;
		}
		$data = json_decode( (string) $raw, true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Normalize + record an error in the log.
	 *
	 * @param string $message
	 */
	public static function log_error( $message ) {
		$log   = get_option( self::OPT_ERRORS, array() );
		$log[] = array(
			'time'  => current_time( 'mysql' ),
			'error' => sanitize_text_field( $message ),
		);
		$log   = array_slice( $log, -20 );
		update_option( self::OPT_ERRORS, $log );
	}

	/**
	 * Import the shipped sample listings (idempotent via post_name).
	 *
	 * @return int Number of listings created (not updated).
	 */
	public function seed_sample_listings() {
		if ( get_option( self::OPT_SEEDED, '0' ) === '1' ) {
			return 0;
		}
		$file = dirname( GABOCHIE_MARKETPLACE_FILE ) . '/data/sample-listings.json';
		$json = is_readable( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( (string) $json, true );
		if ( ! is_array( $data ) || empty( $data['listings'] ) || ! is_array( $data['listings'] ) ) {
			return 0;
		}
		$created = 0;
		foreach ( $data['listings'] as $row ) {
			$slug = sanitize_title( (string) ( $row['slug'] ?? '' ) );
			if ( '' === $slug ) {
				continue;
			}
			$existing = get_page_by_path( $slug, OBJECT, self::CPT );
			if ( $existing ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => self::CPT,
					'post_status'  => 'publish',
					'post_title'   => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
					'post_name'    => $slug,
					'post_content' => wp_kses_post( (string) ( $row['content'] ?? '' ) ),
					'post_excerpt' => sanitize_textarea_field( (string) ( $row['excerpt'] ?? '' ) ),
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			$id = (int) $id;

			$price = isset( $row['price'] ) ? (float) $row['price'] : 0.0;
			update_post_meta( $id, self::META_PRICE, $price );
			update_post_meta( $id, self::META_PRICE_USD, $price > 0 ? round( $price / 15.5 ) : 0 );
			update_post_meta( $id, self::META_LOCATION, sanitize_text_field( (string) ( $row['location'] ?? '' ) ) );
			update_post_meta( $id, self::META_LAT, isset( $row['lat'] ) ? (float) $row['lat'] : 0 );
			update_post_meta( $id, self::META_LNG, isset( $row['lng'] ) ? (float) $row['lng'] : 0 );
			update_post_meta( $id, self::META_SIZE_M2, isset( $row['size_m2'] ) ? (float) $row['size_m2'] : 0 );
			update_post_meta( $id, self::META_BEDROOMS, isset( $row['bedrooms'] ) ? (int) $row['bedrooms'] : 0 );
			update_post_meta( $id, self::META_BATHROOMS, isset( $row['bathrooms'] ) ? (int) $row['bathrooms'] : 0 );
			update_post_meta( $id, self::META_AGENT_NAME, sanitize_text_field( (string) ( $row['agent_name'] ?? '' ) ) );
			update_post_meta( $id, self::META_AGENT_PHONE, sanitize_text_field( (string) ( $row['agent_phone'] ?? '' ) ) );
			update_post_meta( $id, self::META_AGENT_EMAIL, sanitize_email( (string) ( $row['agent_email'] ?? '' ) ) );

			$type = sanitize_key( (string) ( $row['type'] ?? '' ) );
			if ( '' !== $type && term_exists( $type, self::TAX_TYPE ) ) {
				wp_set_object_terms( $id, $type, self::TAX_TYPE );
			}
			$status = sanitize_key( (string) ( $row['status'] ?? '' ) );
			if ( '' !== $status && term_exists( $status, self::TAX_STATUS ) ) {
				wp_set_object_terms( $id, $status, self::TAX_STATUS );
			}
			if ( ! empty( $row['featured'] ) ) {
				update_post_meta( $id, self::META_FEATURED, '1' );
			}
			++$created;
		}
		update_option( self::OPT_SEEDED, '1' );
		return $created;
	}
}
