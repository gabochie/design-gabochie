<?php
defined('ABSPATH') || exit;

function gs_process_upgrade($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return new WP_Error('auth', 'Not authenticated.');

    $plan = sanitize_key($_POST['gs_plan'] ?? 'pro');
    if (!in_array($plan, ['starter', 'pro', 'enterprise'])) {
        return new WP_Error('invalid', 'Invalid plan selected.');
    }

    if (wp_get_environment_type() !== 'local') {
        $stripe_secret = get_option('gs_stripe_secret', '');
        if (empty($stripe_secret)) {
            return new WP_Error('no_payment', 'Payment gateway not configured. Please contact support.');
        }
    }

    update_user_meta($user_id, '_gs_tier', $plan);
    update_user_meta($user_id, '_gs_plan_changed', current_time('mysql'));

    return true;
}

function gs_process_cancel($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return new WP_Error('auth', 'Not authenticated.');

    if (wp_get_environment_type() !== 'local') {
        $stripe_secret = get_option('gs_stripe_secret', '');
        if (empty($stripe_secret)) {
            return new WP_Error('no_payment', 'Payment gateway not configured. Please contact support.');
        }
    }

    update_user_meta($user_id, '_gs_tier', 'free');
    update_user_meta($user_id, '_gs_cancelled_at', current_time('mysql'));

    return true;
}

function gs_handle_upgrade_endpoint() {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['gs_upgrade_action'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'gs_upgrade')) {
        wp_redirect(add_query_arg('gs_status', 'nonce', wp_get_referer() ?: home_url('/dashboard/')));
        exit;
    }

    $action = sanitize_key($_POST['gs_upgrade_action']);
    $result = null;

    if ($action === 'upgrade' || $action === 'downgrade') {
        $result = gs_process_upgrade();
    } elseif ($action === 'cancel') {
        $result = gs_process_cancel();
    }

    $status = is_wp_error($result) ? 'error' : ($action === 'cancel' ? 'cancelled' : 'upgraded');
    wp_redirect(add_query_arg('gs_status', $status, remove_query_arg('gs_status', wp_get_referer() ?: home_url('/dashboard/'))));
    exit;
}
add_action('init', 'gs_handle_upgrade_endpoint');
