<?php
defined('ABSPATH') || exit;

add_action('template_redirect', function() {
    if (is_page('dashboard') && !is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }
});

function gs_portal_sections() {
    $tier = function_exists('gs_member_tier') ? gs_member_tier() : 'free';
    return [
        'overview'  => ['label' => 'My Workspace', 'icon' => '📊', 'access' => 'all'],
        'tools'     => ['label' => 'Tools',       'icon' => '🔧', 'access' => 'all'],
        'documents' => ['label' => 'Documents',   'icon' => '📄', 'access' => 'all'],
        'billing'   => ['label' => 'Billing',     'icon' => '💳', 'access' => 'all'],
        'profile'   => ['label' => 'Profile',     'icon' => '👤', 'access' => 'all'],
        'team'      => ['label' => 'Team',        'icon' => '👥', 'access' => 'pro,enterprise'],
    ];
}

function gs_current_section() {
    $s = isset($_GET['section']) ? sanitize_key($_GET['section']) : 'overview';
    return array_key_exists($s, gs_portal_sections()) ? $s : 'overview';
}

function gs_dashboard_menu_shortcode() {
    if (!is_user_logged_in()) return '';
    $tier = function_exists('gs_member_tier') ? gs_member_tier() : 'free';
    $usage = function_exists('gs_member_usage') ? gs_member_usage() : ['used' => 0, 'limit' => 3, 'remaining' => 3];
    $user = wp_get_current_user();
    $sections = gs_portal_sections();
    $current = gs_current_section();
    $first_name = $user->first_name ?: $user->display_name;
    ob_start();
    ?>
    <div class="dash-nav">
        <div class="dash-user" style="margin-bottom:1.5rem">
            <div class="dash-avatar"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
            <div style="flex:1;min-width:0">
                <div class="dash-name" style="font-size:14px"><?php echo esc_html($first_name); ?></div>
                <div class="dash-tier" data-tier="<?php echo esc_attr($tier); ?>" style="font-size:11px">
                    <?php echo ucfirst($tier); ?> Plan
                    <?php if ($tier === 'free') : ?><a href="/pricing/" class="dash-upgrade">Upgrade</a><?php endif; ?>
                </div>
            </div>
        </div>
        <nav class="dash-links">
            <?php foreach ($sections as $key => $sec) :
                $a = $current === $key ? ' active' : '';
                echo '<a href="/dashboard/?section=' . $key . '" class="dash-link' . $a . '">' . $sec['icon'] . ' ' . $sec['label'] . '</a>';
            endforeach; ?>
            <div style="border-top:1px solid var(--border-subtle);margin:.5rem 0;padding-top:.5rem">
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="dash-link dash-exit">🚪 Sign Out</a>
            </div>
        </nav>
        <div class="dash-usage" style="margin-top:auto;padding-top:1rem;border-top:1px solid var(--border-subtle)">
            <div class="dash-usage-label"><?php echo date('M'); ?> Usage</div>
            <div class="dash-usage-bar"><div class="dash-usage-fill" style="width:<?php echo min(100, ($usage['used']/$usage['limit'])*100); ?>%"></div></div>
            <div class="dash-usage-num"><?php echo $usage['used']; ?> / <?php echo $usage['limit'] === 999 ? '∞' : $usage['limit']; ?> tools used</div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('dash_menu', 'gs_dashboard_menu_shortcode');
