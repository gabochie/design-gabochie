<?php
/**
 * Default page template — generic page hero + content
 * @package Gabochie_Value
 */
get_header();
?>

<main id="main">

<?php while (have_posts()) : the_post(); ?>

<!-- ========== PAGE HERO (compact page-hero per design guide) ========== -->
<section class="page-hero" id="top">
  <div class="container">
    <div class="breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <?php the_title(); ?></div>
    <h1><?php the_title(); ?></h1>
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
