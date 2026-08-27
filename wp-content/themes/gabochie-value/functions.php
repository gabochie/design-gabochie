<?php
/**
 * Gabochie Value Theme Functions
 *
 * @package Gabochie_Value
 * @version 1.4.0
 */

if (!defined('ABSPATH')) exit;

define('GABOCHIE_VALUE_VERSION', '1.4.0');
define('GABOCHIE_VALUE_DIR', get_template_directory());
define('GABOCHIE_VALUE_URI', get_template_directory_uri());

/* ============================================
   MODULAR INCLUDES
   ============================================ */
require_once GABOCHIE_VALUE_DIR . '/inc/setup.php';
require_once GABOCHIE_VALUE_DIR . '/inc/scripts.php';
require_once GABOCHIE_VALUE_DIR . '/inc/helpers.php';

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
   MEMBERSHIP — Client Login & Redirects
   ============================================ */
add_filter('login_redirect', function ($redirect_to, $requested_redirect_to, $user) {
    return !empty($requested_redirect_to) ? $requested_redirect_to : home_url('/dashboard/');
}, 10, 3);

add_action('wp_login_failed', function ($username) {
    $referrer = wp_get_referer();
    if ($referrer && !str_contains($referrer, 'wp-login')) {
        wp_redirect(add_query_arg('login', 'failed', $referrer));
        exit;
    }
});

add_action('init', function () {
    if (get_option('gabochie_value_pages_created')) return;

    $dash = get_page_by_path('dashboard', OBJECT, 'page');
    if (!$dash || !isset($dash->ID)) {
        wp_insert_post(array(
            'post_title'   => 'Dashboard',
            'post_name'    => 'dashboard',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'meta_input'   => array('_wp_page_template' => 'page-templates/template-dashboard.php'),
        ));
    }

    $login_slug = 'client-login';
    $login = get_page_by_path($login_slug, OBJECT, 'page');
    if (!$login || !isset($login->ID)) {
        wp_insert_post(array(
            'post_title'   => 'Client Login',
            'post_name'    => $login_slug,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'meta_input'   => array('_wp_page_template' => 'page-templates/template-login.php'),
        ));
    }

    update_option('gabochie_value_pages_created', true);
});

add_shortcode('gabochie_client_name', function () {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        return $user->display_name ?: $user->user_login;
    }
    return 'Guest';
});

/* ============================================
   PMPRO INTEGRATION
   ============================================ */
add_action('admin_init', function () {
    if (!defined('PMPRO_VERSION')) return;
    if (get_option('gabochie_value_pmpro_configured')) return;

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
    foreach ($pages as $slug => $title) {
        $existing_id = get_option('pmpro_' . $slug . '_page_id', 0);
        if ($existing_id && get_post($existing_id)) continue;
        $pid = wp_insert_post(array(
            'post_title'   => $title,
            'post_name'    => 'membership-' . $slug,
            'post_content' => $slug === 'login' ? '<!-- wp:shortcode -->[pmpro_login]<!-- /wp:shortcode -->' : '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ));
        if ($pid && !is_wp_error($pid)) {
            update_option('pmpro_' . $slug . '_page_id', $pid);
        }
    }

    global $wpdb;
    $levels_table = $wpdb->prefix . 'pmpro_membership_levels';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $levels_table");
    if ((int) $count === 0) {
        $wpdb->insert($levels_table, array(
            'name' => 'Free Lead',
            'description' => 'Free inquiry lead — access to project portal and initial consultation.',
            'initial_payment' => 0, 'billing_amount' => 0,
            'cycle_number' => 0, 'cycle_period' => 'Month',
            'expiration_number' => 0, 'expiration_period' => '',
        ));
        $wpdb->insert($levels_table, array(
            'name' => 'Active Client',
            'description' => 'Full access to project dashboard, documents, messages, and design reviews.',
            'initial_payment' => 2500, 'billing_amount' => 0,
            'cycle_number' => 0, 'cycle_period' => 'Month',
            'expiration_number' => 0, 'expiration_period' => '',
        ));
        $wpdb->insert($levels_table, array(
            'name' => 'Premium Service',
            'description' => 'Complete service package with interior design, project management, and priority support.',
            'initial_payment' => 5000, 'billing_amount' => 0,
            'cycle_number' => 0, 'cycle_period' => 'Month',
            'expiration_number' => 0, 'expiration_period' => '',
        ));
    }

    update_option('pmpro_restrict_dashboard', 'yes');
    update_option('pmpro_hide_toolbar', 'yes');
    update_option('pmpro_filterqueries', 'yes');
    update_option('pmpro_showexcerpts', '1');
    update_option('pmpro_login_redirect', home_url('/dashboard/'));

    update_option('gabochie_value_pmpro_configured', true);
});

// Redirect PMPro login to our custom login page
add_filter('pmpro_login_url', function ($url) {
    return home_url('/login/');
});

// Redirect /login/ to /client-login/ (the actual login page)
add_action('template_redirect', function () {
    if (is_page('login') || (isset($_SERVER['REQUEST_URI']) && preg_match('#/login/?$#', $_SERVER['REQUEST_URI']))) {
        $append = isset($_GET['action']) ? '?' . http_build_query($_GET) : '';
        wp_redirect(home_url('/client-login/' . $append));
        exit;
    }
});

// Redirect PMPro checkout to linked WooCommerce product for paid levels
add_action('template_redirect', function () {
    if (!is_page('membership-checkout') || empty($_GET['pmpro_level'])) return;

    $level_id = (int) $_GET['pmpro_level'];
    if ($level_id <= 1) return;

    $product_levels = get_option('_pmprowoo_product_levels', []);
    $product_id = array_search($level_id, $product_levels);

    if ($product_id) {
        $product = wc_get_product($product_id);
        if ($product && $product->is_purchasable()) {
            wp_redirect($product->get_permalink());
            exit;
        }
    }
});

// After PMPro login, redirect to dashboard
add_filter('pmpro_login_redirect', function ($redirect_to) {
    return home_url('/dashboard/');
});

// Protect dashboard page for members only
add_action('template_redirect', function () {
    if (!is_page('dashboard')) return;
    if (!is_user_logged_in()) {
        wp_redirect(home_url('/client-login/'));
        exit;
    }
    if (function_exists('pmpro_hasMembershipLevel') && !pmpro_hasMembershipLevel()) {
        wp_redirect(home_url('/membership-levels/'));
        exit;
    }
});
