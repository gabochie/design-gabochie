<?php
function gs_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height' => 60, 'width' => 200, 'flex-height' => true, 'flex-width' => true
    ]);
    add_theme_support('menus');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('align-wide');

    register_nav_menus([
        'primary' => 'Primary Navigation',
        'dashboard' => 'Dashboard Navigation',
        'footer' => 'Footer Links'
    ]);
}
add_action('after_setup_theme', 'gs_setup');

function gs_content_width() {
    $GLOBALS['content_width'] = 1160;
}
add_action('after_setup_theme', 'gs_content_width', 0);

function gs_assets() {
    $css_ver = file_exists(GS_THEME_DIR . '/assets/css/main.css') ? filemtime(GS_THEME_DIR . '/assets/css/main.css') : GS_VERSION;
    $js_ver = file_exists(GS_THEME_DIR . '/assets/js/main.js') ? filemtime(GS_THEME_DIR . '/assets/js/main.js') : GS_VERSION;
    wp_enqueue_style('gs-main', GS_THEME_URI . '/assets/css/main.css', [], $css_ver);
    wp_enqueue_script('gs-main', GS_THEME_URI . '/assets/js/main.js', [], $js_ver, true);
    
    if (is_page('dashboard') || is_page_template('page-dashboard.php')) {
        wp_enqueue_script('gs-dashboard', GS_THEME_URI . '/assets/js/dashboard.js', [], $js_ver, true);
        wp_localize_script('gs-dashboard', 'gsData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('gs_tool_nonce'),
        ]);
    }
    
    if (is_page_template('page-pricing.php')) {
        wp_enqueue_script('gs-pricing', GS_THEME_URI . '/assets/js/pricing.js', [], $js_ver, true);
    }
}
add_action('wp_enqueue_scripts', 'gs_assets');

function gs_fonts() {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <?php
}
add_action('wp_head', 'gs_fonts', 1);

function gs_body_classes($classes) {
    if (is_user_logged_in()) { $classes[] = 'logged-in-saas'; }
    return $classes;
}
add_filter('body_class', 'gs_body_classes');

function gs_login_redirect() {
    return home_url('/dashboard/');
}
add_filter('login_redirect', 'gs_login_redirect', 10, 3);

function gs_logout_redirect() {
    return home_url();
}
add_filter('logout_url', function($logout_url, $redirect) {
    return add_query_arg('redirect_to', home_url(), $logout_url);
}, 10, 2);
