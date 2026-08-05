<?php
/**
 * Single post template
 * @package Gabochie_Value
 */
get_header();

$clarity = gabochie_value_clarity_url();
$phone   = gabochie_value_phone();
?>

<main id="main">

<?php while (have_posts()) : the_post(); ?>

<!-- ========== POST HERO ========== -->
<section class="hero hero--solo" id="top">
  <div class="hero-left">
    <div class="hero-location"><span class="hero-location-dot"></span> <?php echo esc_html(get_the_date()); ?></div>
    <h1 class="hero-h1"><?php the_title(); ?></h1>
    <div class="hero-rule"></div>
    <div class="hero-actions">
      <a class="btn-gold" href="<?php echo esc_url($clarity); ?>">Get Clarity</a>
    </div>
    <div class="hero-note">Prefer to talk? Call or WhatsApp <?php echo esc_html($phone); ?></div>
  </div>
</section>

<!-- ========== CONTENT ========== -->
<section class="value" id="content">
  <div class="container">
    <?php if (has_post_thumbnail()) : ?>
    <div class="entry-thumb"><?php the_post_thumbnail('gabochie-value-hero'); ?></div>
    <?php endif; ?>
    <div class="entry-content">
      <?php
      the_content();
      wp_link_pages(array('before' => '<div class="page-links">' . __('Pages:', 'gabochie-value'), 'after' => '</div>'));
      ?>
    </div>
    <div class="post-meta">
      <span><?php esc_html_e('Posted in', 'gabochie-value'); ?> <?php the_category(', '); ?></span>
      <?php if (has_tag()) : ?><span class="post-tags"><?php the_tags('', ' '); ?></span><?php endif; ?>
    </div>
    <nav class="post-nav" aria-label="<?php esc_attr_e('Post navigation', 'gabochie-value'); ?>">
      <div class="post-nav-prev"><?php previous_post_link('%link', '&larr; %title'); ?></div>
      <div class="post-nav-next"><?php next_post_link('%link', '%title &rarr;'); ?></div>
    </nav>
    <?php comments_template(); ?>
  </div>
</section>

<?php endwhile; ?>

</main>

<?php get_footer(); ?>
