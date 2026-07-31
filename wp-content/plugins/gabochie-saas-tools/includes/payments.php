<?php
defined('ABSPATH') || exit;

function gs_paystack_public_key() {
    return defined('GS_PAYSTACK_PUB') ? GS_PAYSTACK_PUB : '';
}

function gs_stripe_public_key() {
    return defined('GS_STRIPE_PUB') ? GS_STRIPE_PUB : '';
}

function gs_subscription_tiers() {
    return [
        'free' => ['name' => 'Free', 'price' => 0, 'ghs' => 0, 'desc' => 'Try before you buy', 'tools' => 3, 'popular' => false, 'features' => ['3 tool uses/month', 'Basic PDF output', 'Standard templates', 'Email support']],
        'pro' => ['name' => 'Pro', 'price' => 29, 'ghs' => 350, 'desc' => 'For active professionals', 'tools' => 999, 'popular' => true, 'features' => ['Unlimited tool usage', 'PDF + DOCX export', 'All premium templates', 'Priority email support', 'API access', 'Team seats (3)']],
        'enterprise' => ['name' => 'Enterprise', 'price' => 99, 'ghs' => 1200, 'desc' => 'For firms & institutions', 'tools' => 9999, 'popular' => false, 'features' => ['Unlimited everything', 'White-label output', 'Custom AI training', 'Dedicated account manager', 'SSO / SAML', 'SLA guarantee', 'Bulk onboarding']],
    ];
}

function gs_upgrade_url() {
    return home_url('/pricing/');
}
