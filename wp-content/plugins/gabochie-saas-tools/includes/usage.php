<?php
defined('ABSPATH') || exit;

function gs_member_usage($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return ['used' => 0, 'limit' => 3, 'remaining' => 3];
    $month = date('Ym');
    $used = (int) get_user_meta($user_id, "_gs_usage_{$month}", true);
    $tier = gs_member_tier($user_id);
    $limits = ['free' => 3, 'starter' => 15, 'pro' => 999, 'enterprise' => 9999];
    $limit = $limits[$tier] ?? 3;
    return ['used' => $used, 'limit' => $limit, 'remaining' => max(0, $limit - $used)];
}

function gs_increment_usage($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return;
    $month = date('Ym');
    $used = (int) get_user_meta($user_id, "_gs_usage_{$month}", true);
    update_user_meta($user_id, "_gs_usage_{$month}", $used + 1);
}
