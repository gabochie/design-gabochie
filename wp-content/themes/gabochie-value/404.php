<?php
/**
 * 404 template
 * @package Gabochie_Value
 */
get_header();

$clarity = gabochie_value_clarity_url();
$phone   = gabochie_value_phone();
?>

<main id="main">

<!-- ========== 404 HERO ========== -->
<section class="hero hero--solo" id="top">
  <div class="hero-left">
    <div class="hero-location"><span class="hero-location-dot"></span> Error 404</div>
    <h1 class="hero-h1">This page <em>doesn't exist.</em></h1>
    <div class="hero-h1-dim">It may have moved, or never existed at all.</div>
    <div class="hero-rule"></div>
    <p class="hero-sub">Let's get you back on track &mdash; your building journey is easier than you think.</p>
    <div class="hero-actions">
      <a class="btn-gold" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to home', 'gabochie-value'); ?></a>
      <a class="btn-ghost-w" href="<?php echo esc_url($clarity); ?>">Start Here</a>
    </div>
    <div class="hero-note">Prefer to talk? Call or WhatsApp <?php echo esc_html($phone); ?></div>
  </div>
</section>

</main>

<?php get_footer(); ?>
