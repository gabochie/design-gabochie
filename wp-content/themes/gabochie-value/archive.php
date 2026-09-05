<?php
/**
 * Archive template — post listings
 * @package Gabochie_Value
 */
get_header();

$archive_title = is_home() ? __('Journal', 'gabochie-value') : get_the_archive_title();
?>

<main id="main">

<!-- ========== ARCHIVE HERO (compact page-hero per design guide) ========== -->
<section class="page-hero" id="top">
  <div class="container">
    <div class="breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <?php echo esc_html( $archive_title ); ?></div>
    <h1><?php echo esc_html( $archive_title ); ?></h1>
    <?php the_archive_description( '<p>', '</p>' ); ?>
  </div>
</section>

<!-- ========== LISTING ========== -->
<section class="resources" id="posts">
  <div class="container">
    <?php if (have_posts()) : ?>
    <div class="res-grid">
      <?php while (have_posts()) : the_post(); ?>
      <a class="res-card" href="<?php the_permalink(); ?>">
        <?php if (has_post_thumbnail()) : ?>
        <div class="res-card-img"><?php the_post_thumbnail('gabochie-value-card'); ?></div>
        <?php endif; ?>
        <div class="res-card-body">
          <span class="r-tag"><?php echo esc_html(get_the_date()); ?></span>
          <h3><?php the_title(); ?></h3>
          <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
          <span class="r-link"><?php esc_html_e('Read more', 'gabochie-value'); ?> &rarr;</span>
        </div>
      </a>
      <?php endwhile; ?>
    </div>
    <div class="pagination">
      <?php
      the_posts_pagination(array(
          'mid_size'  => 2,
          'prev_text' => '&larr; ' . __('Previous', 'gabochie-value'),
          'next_text' => __('Next', 'gabochie-value') . ' &rarr;',
      ));
      ?>
    </div>
    <?php else : ?>
    <div class="sec-lead reveal"><?php esc_html_e('Nothing published here yet. Check back soon.', 'gabochie-value'); ?></div>
    <div class="hero-actions">
      <a class="btn-gold" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to home', 'gabochie-value'); ?></a>
    </div>
    <?php endif; ?>
  </div>
</section>

</main>

<?php get_footer(); ?>
