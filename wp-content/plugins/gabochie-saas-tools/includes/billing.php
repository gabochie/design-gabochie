<?php
defined('ABSPATH') || exit;

function gs_member_tier($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return 'free';
    $tier = get_user_meta($user_id, '_gs_tier', true);
    return in_array($tier, ['starter', 'pro', 'enterprise']) ? $tier : 'free';
}

function gs_user_plan($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $tier = gs_member_tier($user_id);
    $plans = [
        'free' => ['name' => 'Free', 'price' => 0, 'ghs' => 0, 'next_billing' => null],
        'starter' => ['name' => 'Starter', 'price' => 19, 'ghs' => 140, 'next_billing' => date('M j, Y', strtotime('+1 month'))],
        'pro' => ['name' => 'Pro', 'price' => 29, 'ghs' => 350, 'next_billing' => date('M j, Y', strtotime('+1 month'))],
        'enterprise' => ['name' => 'Enterprise', 'price' => 99, 'ghs' => 1200, 'next_billing' => date('M j, Y', strtotime('+1 month'))],
    ];
    return $plans[$tier] ?? $plans['free'];
}

function gs_get_invoices($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $invoices = get_user_meta($user_id, '_gs_invoices', true);
    if (!is_array($invoices) || empty($invoices)) {
        $plan = gs_user_plan($user_id);
        if ($plan['price'] > 0) {
            $invoices = [
                ['id' => 'INV-001', 'date' => date('M j, Y', strtotime('-1 month')), 'amount' => '$' . $plan['price'], 'status' => 'paid'],
                ['id' => 'INV-002', 'date' => date('M j, Y'), 'amount' => '$' . $plan['price'], 'status' => 'pending'],
            ];
            update_user_meta($user_id, '_gs_invoices', $invoices);
        } else {
            $invoices = [];
        }
    }
    return $invoices;
}

function gs_member_since($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    if ($user) return date('F Y', strtotime($user->user_registered));
    return 'N/A';
}
