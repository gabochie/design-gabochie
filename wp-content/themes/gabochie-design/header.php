<?php
/**
 * Header template
 * @package Gabochie_Design
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">
    <?php
    $og_title = is_singular() ? get_the_title() : 'Gabochie Design — AI-Powered Architecture for Ghana & Diaspora';
    $og_desc = is_singular() ? wp_trim_words(get_the_excerpt(), 30) : 'AI-powered residential architecture and interior design for Ghana and the African diaspora. From concept to construction in 4-6 weeks.';
    $og_image = 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&q=80';
    if (is_singular() && has_post_thumbnail()) {
        $thumb = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large');
        if ($thumb) $og_image = $thumb[0];
    }
    ?>
    <meta property="og:title" content="<?php echo esc_attr($og_title); ?>">
    <meta property="og:description" content="<?php echo esc_attr($og_desc); ?>">
    <meta property="og:image" content="<?php echo esc_url($og_image); ?>">
    <meta property="og:type" content="<?php echo is_singular('post') ? 'article' : 'website'; ?>">
    <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
    <meta property="og:site_name" content="Gabochie Design">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($og_title); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr($og_desc); ?>">
    <meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">
    <?php
    $hero_img = get_theme_mod('hero_image', 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=900&q=80');
    ?>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
    <?php if (is_front_page()) : ?>
    <link rel="preload" as="image" href="<?php echo esc_url($hero_img); ?>" fetchpriority="high">
    <?php endif; ?>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="reading-progress"></div>

<a href="#main-content" class="skip-link">Skip to main content</a>

<div class="nav-wrap">
    <nav id="mainnav" class="<?php echo is_front_page() ? '' : 'scrolled'; ?>">
        <div class="logo-wrap">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="logo-link" aria-label="Home">
                <?php echo gabochie_svg_logo(); ?>
                <div class="logo-text">
                    <div class="logo-name">GABOCHIE DESIGN</div>
                    <div class="logo-tag">Residential Architecture</div>
                </div>
            </a>
        </div>

        <?php if (has_nav_menu('primary')) : ?>
            <?php wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'nav-links',
                'fallback_cb'    => false,
                'depth'          => 2,
            )); ?>
        <?php else : ?>
            <ul class="nav-links">
                <li><a href="<?php echo esc_url(home_url('/about/')); ?>">About</a></li>
                <li><a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a></li>
                <li><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a></li>
                <li><a href="<?php echo esc_url(home_url('/pricing/')); ?>">Pricing</a></li>
                <li><a href="<?php echo esc_url(home_url('/services/')); ?>">Services</a></li>
                <li class="nav-cta-wrap"><a href="mailto:<?php echo esc_attr(get_theme_mod('company_email', 'admin@gabochiedesign.com')); ?>?subject=Book%20Consultation" class="nav-cta">Book Consultation</a></li>
            </ul>
        <?php endif; ?>

        <div class="nav-signin-wrap"><?php if (is_user_logged_in()) : ?><a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">Sign Out</a><?php else : ?><a href="<?php echo esc_url(home_url('/login/')); ?>">Sign In</a><?php endif; ?></div>

        <button class="mobile-menu-btn" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>

        <button class="dark-toggle" aria-label="Toggle dark mode">
            <svg class="icon-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            <svg class="icon-moon" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
    </nav>
</div>
