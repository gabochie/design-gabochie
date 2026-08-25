<?php
/**
 * Default page template — generic page hero + content
 * @package Gabochie_Value
 */
get_header();

$clarity   = gabochie_value_clarity_url();
$phone     = gabochie_value_phone();
?>

<main id="main">

<?php while (have_posts()) : the_post(); ?>

<!-- ========== PAGE HERO ========== -->
<section class="hero hero--solo" id="top">
  <div class="hero-left">
    <div class="hero-location"><span class="hero-location-dot"></span> Sakumono &middot; Tema &mdash; Ghana</div>
    <h1 class="hero-h1"><?php the_title(); ?></h1>
    <div class="hero-rule"></div>
    <div class="hero-actions">
      <a class="btn-gold" href="<?php echo esc_url($clarity); ?>">Start Here</a>
    </div>
    <div class="hero-note">Prefer to talk? Call or WhatsApp <?php echo esc_html($phone); ?></div>
  </div>
</section>

<!-- ========== CONTENT ========== -->
<section class="value" id="content">
  <div class="container">
    <div class="entry-content">
      <?php
      the_content();
      wp_link_pages(array('before' => '<div class="page-links">' . __('Pages:', 'gabochie-value'), 'after' => '</div>'));
      ?>
    </div>
  </div>
</section>

<?php endwhile; ?>

</main>

<?php get_footer(); ?>
