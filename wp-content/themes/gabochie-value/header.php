<?php
/**
 * Header template
 * @package Gabochie_Value
 */
$hero_img_default = 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=1200&q=80';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<script>document.documentElement.className += ' js';</script>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="mobile-web-app-capable" content="yes">
<meta name="format-detection" content="telephone=no">
<meta name="theme-color" content="#072E28">
<?php
$og_title = is_singular() ? get_the_title() : 'Gabochie Design — Value-Driven Architectural Consultancy, Ghana';
$og_desc  = is_singular()
    ? wp_trim_words(get_the_excerpt() ?: get_the_content(), 30)
    : 'A value-driven architectural consultancy and built-environment advisory for ambitious homeowners, investors, developers and the Ghanaian diaspora. From first idea to feasibility, design, cost, construction and beyond. Get Clarity.';
$og_image = $hero_img_default;
if (is_singular() && has_post_thumbnail()) {
    $thumb = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large');
    if ($thumb) { $og_image = $thumb[0]; }
}
$og_type = is_singular('post') ? 'article' : 'website';
?>
<meta property="og:title" content="<?php echo esc_attr($og_title); ?>">
<meta property="og:description" content="<?php echo esc_attr($og_desc); ?>">
<meta property="og:image" content="<?php echo esc_url($og_image); ?>">
<meta property="og:type" content="<?php echo esc_attr($og_type); ?>">
<meta property="og:url" content="<?php echo esc_url(is_singular() ? get_permalink() : home_url('/')); ?>">
<meta property="og:site_name" content="Gabochie Design">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo esc_attr($og_title); ?>">
<meta name="twitter:description" content="<?php echo esc_attr($og_desc); ?>">
<meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 52 52'%3E%3Cpolygon points='26,3 49,15 49,37 26,49 3,37 3,15' fill='%23072E28' stroke='%23C9A96E' stroke-width='2'/%3E%3Ctext x='26' y='28' text-anchor='middle' font-family='Georgia,serif' font-size='16' font-weight='700' fill='%23C9A96E'%3EGD%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.unsplash.com" crossorigin>
<?php if (is_front_page()) : ?>
<link rel="preload" as="image" href="<?php echo esc_url($hero_img_default); ?>" fetchpriority="high">
<?php endif; ?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>
<div class="reading-progress" id="readingProgress"></div>

<!-- ========== NAV ========== -->
<header class="nav-wrap">
<nav aria-label="Primary">
  <a class="logo-link" href="<?php echo esc_url(home_url('/')); ?>">
    <svg class="logo-hex" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <polygon points="26,4 47,15 47,37 26,48 5,37 5,15" fill="#072E28" stroke="#C9A96E" stroke-width="2"/>
      <text x="26" y="29" text-anchor="middle" font-family="Georgia,serif" font-size="17" font-weight="700" fill="#C9A96E">GD</text>
    </svg>
    <span class="logo-text">
      <span class="logo-name">GABOCHIE</span>
      <span class="logo-tag">Value-Driven Architectural Consultancy</span>
    </span>
  </a>
  <?php if (has_nav_menu('primary')) : ?>
    <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'menu_class' => 'nav-links', 'fallback_cb' => false, 'depth' => 1)); ?>
  <?php else : ?>
  <ul class="nav-links" id="navLinks">
    <li><a href="<?php echo esc_url(gabochie_value_section_url('services')); ?>">Services</a></li>
    <li><a href="<?php echo esc_url(gabochie_value_section_url('journey')); ?>">Journey</a></li>
    <li><a href="<?php echo esc_url(gabochie_value_section_url('diaspora')); ?>">Diaspora</a></li>
    <li><a href="<?php echo esc_url(gabochie_value_section_url('resources')); ?>">Resources</a></li>
    <li><a href="<?php echo esc_url(gabochie_value_section_url('projects')); ?>">Projects</a></li>
  </ul>
  <?php endif; ?>
  <div class="nav-right">
    <button class="dark-toggle" id="darkToggle" aria-label="Toggle dark mode">
      <svg class="icon-sun" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.5"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      <svg class="icon-moon" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
    </button>
    <a class="nav-cta" href="<?php echo esc_url(gabochie_value_clarity_url()); ?>">Get Clarity</a>
    <button class="mobile-menu-btn" id="navToggle" aria-label="Open menu" aria-expanded="false"><span></span><span></span><span></span></button>
  </div>
</nav>
<div class="mobile-menu" id="mobileMenu">
  <a href="<?php echo esc_url(gabochie_value_section_url('services')); ?>">Services</a>
  <a href="<?php echo esc_url(gabochie_value_section_url('journey')); ?>">Building Journey</a>
  <a href="<?php echo esc_url(gabochie_value_section_url('diaspora')); ?>">For Diaspora</a>
  <a href="<?php echo esc_url(gabochie_value_section_url('recovery')); ?>">Project Recovery</a>
  <a href="<?php echo esc_url(gabochie_value_section_url('resources')); ?>">Resources</a>
  <a href="<?php echo esc_url(gabochie_value_section_url('projects')); ?>">Projects</a>
  <a class="nav-cta" href="<?php echo esc_url(gabochie_value_clarity_url()); ?>">Get Clarity</a>
</div>
</header>
