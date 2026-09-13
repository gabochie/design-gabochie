<?php
/**
 * Gabochie Value Theme Functions
 *
 * @package Gabochie_Value
 * @version 1.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GABOCHIE_VALUE_VERSION', '1.11.0' );
define( 'GABOCHIE_VALUE_DIR', get_template_directory() );
define( 'GABOCHIE_VALUE_URI', get_template_directory_uri() );

/* ============================================
	SHOP — WooCommerce support + PMPro product bridge
	============================================ */
add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}
);

remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

/* ============================================
	MODULAR INCLUDES
	============================================ */
require_once GABOCHIE_VALUE_DIR . '/inc/setup.php';
require_once GABOCHIE_VALUE_DIR . '/inc/scripts.php';
require_once GABOCHIE_VALUE_DIR . '/inc/helpers.php';
require_once GABOCHIE_VALUE_DIR . '/inc/seo.php';

/* ============================================
	CLIENT PORTAL
	============================================ */
require_once GABOCHIE_VALUE_DIR . '/inc/client-portal-cpts.php';
require_once GABOCHIE_VALUE_DIR . '/inc/client-portal-api.php';
require_once GABOCHIE_VALUE_DIR . '/inc/client-portal-auth.php';
require_once GABOCHIE_VALUE_DIR . '/inc/client-portal-webhooks.php';
require_once GABOCHIE_VALUE_DIR . '/inc/client-portal-chat.php';

/* ============================================
	THESIS LEAD MAGNET (email gate + delivery)
	============================================ */
require_once GABOCHIE_VALUE_DIR . '/inc/client-portal-thesis.php';
require_once GABOCHIE_VALUE_DIR . '/inc/class-gabochie-thesis-pdf.php';

/* ============================================
	DEVELOPMENT READINESS SCORE (Phase B)
	============================================ */
require_once GABOCHIE_VALUE_DIR . '/inc/client-portal-readiness.php';
require_once GABOCHIE_VALUE_DIR . '/inc/client-portal-readiness-api.php';

/* ============================================
	TASK VERIFICATION ENDPOINT
	============================================ */
require_once GABOCHIE_VALUE_DIR . '/inc/client-portal-verify.php';

/* ============================================
	PROJECT RECOVERY WORKSPACE (MVP)
	============================================ */
require_once GABOCHIE_VALUE_DIR . '/inc/client-portal-recovery.php';

/* ============================================
	MEMBERSHIP — Client Login & Redirects
	============================================ */
add_filter(
	'login_redirect',
	function ( $redirect_to, $requested_redirect_to, $user ) {
		// Super admins always go to wp-admin; only members go to the client dashboard.
		if ( $user && is_object( $user ) && in_array( 'administrator', (array) $user->roles, true ) ) {
			return admin_url();
		}
		return ! empty( $requested_redirect_to ) ? $requested_redirect_to : home_url( '/dashboard/' );
	},
	10,
	3
);

add_action(
	'wp_login_failed',
	function ( $username ) {
		$referrer = wp_get_referer();
		if ( $referrer && ! str_contains( $referrer, 'wp-login' ) ) {
			wp_redirect( add_query_arg( 'login', 'failed', $referrer ) );
			exit;
		}
	}
);

/* ============================================
	LEGACY / ORPHAN PAGE REDIRECTS (platform funnel)
	Old-theme pages not part of the v3 nav are 301'd
	to their canonical conversion destination.
	============================================ */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() ) {
			return;
		}
		$page = get_queried_object();
		if ( ! ( $page instanceof WP_Post ) || 'page' !== $page->post_type ) {
			return;
		}

		$map = array(
			'pricing'                        => '/membership-levels/',
			'services'                       => 'section:services',
			'portfolio'                      => 'section:projects',
			'faq'                            => '/contact/',
			'residential-cost-calculator'    => '/building-cost-calculator/',
			'nonresidential-cost-calculator' => '/building-cost-calculator/',
			'architectural-design'           => 'section:services',
			'interior-design'                => 'section:services',
			'3d-renders'                     => 'section:services',
			'project-management'             => 'section:services',
			'3d-visualization'               => 'section:services',
			'ai-consultation'                => '/onboarding/',
			'diaspora-guide'                 => 'section:diaspora',
		);

		$slug = $page->post_name;
		if ( ! isset( $map[ $slug ] ) ) {
			return;
		}

		$target = $map[ $slug ];
		if ( str_starts_with( $target, 'section:' ) ) {
			wp_safe_redirect( gabochie_value_section_url( substr( $target, 8 ) ), 301 );
			exit;
		}
		wp_safe_redirect( home_url( $target ), 301 );
		exit;
	},
	5
);

add_action(
	'init',
	function () {
		if ( get_option( 'gabochie_value_pages_created' ) ) {
			return;
		}

		$dash = get_page_by_path( 'dashboard', OBJECT, 'page' );
		if ( ! $dash || ! isset( $dash->ID ) ) {
			wp_insert_post(
				array(
					'post_title'   => 'Dashboard',
					'post_name'    => 'dashboard',
					'post_content' => '',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'meta_input'   => array( '_wp_page_template' => 'page-templates/template-dashboard.php' ),
				)
			);
		}

		$login_slug = 'login';
		$login      = get_page_by_path( $login_slug, OBJECT, 'page' );
		if ( ! $login || ! isset( $login->ID ) ) {
			wp_insert_post(
				array(
					'post_title'   => 'Client Login',
					'post_name'    => $login_slug,
					'post_content' => '',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'meta_input'   => array( '_wp_page_template' => 'page-templates/template-login.php' ),
				)
			);
		}

		update_option( 'gabochie_value_pages_created', true );
	}
);

/* ============================================
	PROJECT RECOVERY — dedicated diagnostic page
	Self-healing: recreates the page (with the right
	template) if it is ever deleted or orphaned.
	============================================ */
add_action(
	'init',
	function () {
		$slug     = 'project-recovery';
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing && isset( $existing->ID ) && 'trash' !== $existing->post_status ) {
			return;
		}
		wp_insert_post(
			array(
				'post_title'   => 'Project Recovery',
				'post_name'    => $slug,
				'post_content' => 'A structured diagnosis for stalled projects — most can be rescued, and often finished for less than starting over.',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'meta_input'   => array( '_wp_page_template' => 'page-templates/template-project-recovery.php' ),
			)
		);
	}
);

add_shortcode(
	'gabochie_client_name',
	function () {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return $user->display_name ?: $user->user_login;
		}
		return 'Guest';
	}
);

/* ============================================
	PMPRO INTEGRATION
	============================================ */
add_action(
	'admin_init',
	function () {
		if ( ! defined( 'PMPRO_VERSION' ) ) {
			return;
		}
		if ( get_option( 'gabochie_value_pmpro_configured' ) ) {
			return;
		}

		$pages = array(
			'account'      => 'Membership Account',
			'billing'      => 'Membership Billing',
			'cancel'       => 'Membership Cancel',
			'checkout'     => 'Membership Checkout',
			'confirmation' => 'Membership Confirmation',
			'invoices'     => 'Membership Orders',
			'levels'       => 'Membership Levels',
			'login'        => 'Log In',
		);
		foreach ( $pages as $slug => $title ) {
			$existing_id = get_option( 'pmpro_' . $slug . '_page_id', 0 );
			if ( $existing_id && get_post( $existing_id ) ) {
				continue;
			}
			$pid = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_name'    => 'membership-' . $slug,
					'post_content' => $slug === 'login' ? '<!-- wp:shortcode -->[pmpro_login]<!-- /wp:shortcode -->' : '',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
			if ( $pid && ! is_wp_error( $pid ) ) {
				update_option( 'pmpro_' . $slug . '_page_id', $pid );
			}
		}

		global $wpdb;
		$levels_table = $wpdb->prefix . 'pmpro_membership_levels';
		$count        = $wpdb->get_var( "SELECT COUNT(*) FROM $levels_table" );
		if ( (int) $count === 0 ) {
			$wpdb->insert(
				$levels_table,
				array(
					'name'              => 'Free Lead',
					'description'       => 'Free inquiry lead — access to project portal and initial consultation.',
					'initial_payment'   => 0,
					'billing_amount'    => 0,
					'cycle_number'      => 0,
					'cycle_period'      => 'Month',
					'expiration_number' => 0,
					'expiration_period' => '',
				)
			);
			$wpdb->insert(
				$levels_table,
				array(
					'name'              => 'Active Client',
					'description'       => 'Full access to project dashboard, documents, messages, and design reviews.',
					'initial_payment'   => 2500,
					'billing_amount'    => 0,
					'cycle_number'      => 0,
					'cycle_period'      => 'Month',
					'expiration_number' => 0,
					'expiration_period' => '',
				)
			);
			$wpdb->insert(
				$levels_table,
				array(
					'name'              => 'Premium Service',
					'description'       => 'Complete service package with interior design, project management, and priority support.',
					'initial_payment'   => 5000,
					'billing_amount'    => 0,
					'cycle_number'      => 0,
					'cycle_period'      => 'Month',
					'expiration_number' => 0,
					'expiration_period' => '',
				)
			);
		}

		update_option( 'pmpro_restrict_dashboard', 'yes' );
		update_option( 'pmpro_hide_toolbar', 'yes' );
		update_option( 'pmpro_filterqueries', 'yes' );
		update_option( 'pmpro_showexcerpts', '1' );
		update_option( 'pmpro_login_redirect', home_url( '/dashboard/' ) );

		update_option( 'gabochie_value_pmpro_configured', true );
	}
);

// Redirect PMPro login to our custom login page
add_filter(
	'pmpro_login_url',
	function () {
		return home_url( '/login/' );
	}
);

// Keep the branded register form on /login/?action=register usable.
// By default PMPro (pmpro_login_head) redirects any action=register on the
// login page straight to the levels page; the theme has its own register
// form ([gabochie_register_form]) that must render instead.
add_filter(
	'pmpro_register_redirect',
	function () {
		return '';
	},
	20
);

// Canonical login URL is /login/ (branded Client Portal). Back-compat redirects:
// /client-login/, /log-in/, /log-in-2/ all land on the branded page.
add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
		if ( $path && preg_match( '#^/(client-login|log-in|log-in-2)(/.*)?$#', $path ) ) {
			$qs = isset( $_SERVER['QUERY_STRING'] ) ? '?' . sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
			wp_safe_redirect( home_url( '/login/' . $qs ), 301 );
			exit;
		}
	}
);

// Redirect PMPro checkout to linked WooCommerce product for paid levels
add_action(
	'template_redirect',
	function () {
		if ( ! is_page( 'membership-checkout' ) || empty( $_GET['pmpro_level'] ) ) {
			return;
		}

		$level_id = (int) $_GET['pmpro_level'];
		if ( $level_id <= 1 ) {
			return;
		}

		$product_levels = get_option( '_pmprowoo_product_levels', array() );
		$product_id     = 0;
		foreach ( (array) $product_levels as $pid => $lvl_ids ) {
			foreach ( (array) $lvl_ids as $lid ) {
				if ( (int) $lid === $level_id ) {
					$product_id = (int) $pid;
					break 2;
				}
			}
		}

		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->is_purchasable() ) {
				wp_redirect( $product->get_permalink() );
				exit;
			}
		}
	}
);

// After PMPro login, admins go to wp-admin; members go to the client dashboard.
add_filter(
	'pmpro_login_redirect',
	function ( $redirect_to ) {
		$user = wp_get_current_user();
		if ( $user && in_array( 'administrator', (array) $user->roles, true ) ) {
			return admin_url();
		}
		return home_url( '/dashboard/' );
	}
);

// Protect dashboard page for members only
add_action(
	'template_redirect',
	function () {
		if ( ! is_page( 'dashboard' ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			wp_redirect( home_url( '/login/' ) );
			exit;
		}
		if ( function_exists( 'pmpro_hasMembershipLevel' ) && ! pmpro_hasMembershipLevel() ) {
			wp_redirect( home_url( '/membership-levels/' ) );
			exit;
		}
	}
);

/**
 * Auto-complete Direct Mobile Money Transfer (bacs) orders upon checkout placement.
 * Ensures clients receive instant portal access and digital membership fulfillment.
 */
add_filter(
	'woocommerce_bacs_process_payment_order_status',
	function ( $status, $order ) {
		return 'completed';
	},
	10,
	2
);

/* ============================================
	BLOG COMMENTS DISABLED (policy: no replies on posts, now + future)
	============================================ */
// Force comments and pings closed for every post regardless of stored status.
add_filter( 'comments_open', '__return_false', 10, 2 );
add_filter( 'pings_open', '__return_false', 10, 2 );
// Suppress the legacy "Comments are closed" template block on single posts.
add_filter(
	'comments_template',
	function ( $file, $post_id ) {
		return '';
	},
	10,
	2
);
// Remove comment support from the post type so no UI surfaces anywhere.
add_action(
	'init',
	function () {
		remove_post_type_support( 'post', 'comments' );
		remove_post_type_support( 'post', 'trackbacks' );
	}
);

