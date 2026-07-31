<?php
define('GS_VERSION', '1.0.0');
define('GS_THEME_DIR', get_template_directory());
define('GS_THEME_URI', get_template_directory_uri());

if (!function_exists('gs_member_tier')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-warning"><p><strong>Gabochie SaaS:</strong> The <code>gabochie-saas-tools</code> plugin is required. Please install and activate it.</p></div>';
    });
}

require_once GS_THEME_DIR . '/inc/setup.php';
require_once GS_THEME_DIR . '/inc/portal-ui.php';
