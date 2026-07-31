<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a href="#main-content" class="skip-link" style="position:absolute;top:-999px;left:0;padding:8px 16px;background:var(--gs-300);color:var(--gs-900);font-size:14px;font-weight:600;text-decoration:none;z-index:9999">Skip to main content</a>
<div class="wrap">

<header class="site-header">
  <div class="nav-inner">
    <a class="nav-brand" href="<?php echo esc_url(home_url()); ?>">
      <svg class="hex" viewBox="0 0 38 44" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M19 2L36 11.5V32.5L19 42L2 32.5V11.5Z" stroke="rgba(255,255,255,0.75)" stroke-width="1.4" fill="rgba(255,255,255,0.04)"/>
        <text x="19" y="26" text-anchor="middle" fill="white" font-family="'Syne',sans-serif" font-weight="800" font-size="12">GS</text>
      </svg>
      <div class="brand-text">
        <div class="b1">GABOCHIE</div>
        <div class="b2">SAAS</div>
      </div>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Menu">☰</button>

    <div class="nav-collapse" id="navCollapse">
      <?php if (has_nav_menu('primary')) :
        wp_nav_menu([
          'theme_location' => 'primary',
          'container' => false,
          'menu_class' => 'nav-list',
          'depth' => 1,
          'fallback_cb' => function() {
            echo '<ul class="nav-list">';
            $items = [['/tools/', 'Tools'], ['/pricing/', 'Pricing'], ['/about/', 'About']];
            foreach ($items as $i) {
              $act = (strpos($_SERVER['REQUEST_URI'], $i[0]) === 0) ? ' act' : '';
              echo '<li><a href="' . $i[0] . '" class="' . $act . '">' . $i[1] . '</a></li>';
            }
            echo '</ul>';
          }
        ]);
      endif; ?>

      <div class="nav-actions">
        <?php if (is_user_logged_in()) :
          $cu = wp_get_current_user(); ?>
          <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="nav-cta">My Workspace</a>
          <div class="nav-user">
            <div class="nav-avatar"><?php echo strtoupper(substr($cu->display_name, 0, 1)); ?></div>
          </div>
        <?php else : ?>
          <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="nav-login">Sign In</a>
          <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="nav-cta">Get Access →</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<main class="saas-main" id="main-content">
