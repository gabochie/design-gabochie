<?php
/**
 * Blog archive page (auto-selected for the "blog" slug via template hierarchy).
 * Lists the latest posts as res-cards with category filters and pagination.
 *
 * @package Gabochie_Value
 */

get_header();

$clarity = gabochie_value_clarity_url();
$phone   = gabochie_value_phone();

$paged        = max( 1, (int) get_query_var( 'paged' ) );
$cat_slug     = get_query_var( 'category_name', '' );
$current_cat  = $cat_slug ? get_category_by_slug( $cat_slug ) : null;

$blog_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 9,
		'paged'               => $paged,
		'category_name'       => $cat_slug ?: null,
		'ignore_sticky_posts' => true,
	)
);

$cats = get_categories( array( 'hide_empty' => true ) );
?>

<main id="main">

<!-- ========== BLOG HERO ========== -->
<section class="hero hero--solo" id="top">
  <div class="hero-left">
    <div class="hero-location"><span class="hero-location-dot"></span> Sakumono &middot; Tema &mdash; Ghana</div>
    <h1 class="hero-h1"><?php echo esc_html( $current_cat ? $current_cat->name : __( 'Blog', 'gabochie-value' ) ); ?></h1>
    <div class="hero-rule"></div>
    <p class="hero-sub"><?php esc_html_e( 'Practical guidance on building in Ghana, diaspora property and value-driven design.', 'gabochie-value' ); ?></p>
    <div class="hero-actions">
      <a class="btn-gold" href="<?php echo esc_url( $clarity ); ?>">Get Clarity</a>
    </div>
    <div class="hero-note">Prefer to talk? Call or WhatsApp <?php echo esc_html( $phone ); ?></div>
  </div>
</section>

<!-- ========== LISTING ========== -->
<section class="resources" id="posts">
  <div class="container">

    <?php if ( $cats ) : ?>
    <nav class="blog-filters" aria-label="<?php esc_attr_e( 'Blog categories', 'gabochie-value' ); ?>">
      <a class="blog-filter<?php echo $current_cat ? '' : ' is-active'; ?>" href="<?php echo esc_url( get_permalink( get_page_by_path( 'blog' ) ?: get_the_ID() ) ); ?>"><?php esc_html_e( 'All', 'gabochie-value' ); ?></a>
      <?php foreach ( $cats as $c ) : ?>
      <a class="blog-filter<?php echo $current_cat && $current_cat->term_id === $c->term_id ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $c->term_id ) ); ?>"><?php echo esc_html( $c->name ); ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if ( $blog_query->have_posts() ) : ?>
    <div class="res-grid">
      <?php
      while ( $blog_query->have_posts() ) :
          $blog_query->the_post();
          $c = get_the_category();
          $cat_name = ! empty( $c ) ? $c[0]->name : '';
          ?>
      <a class="res-card" href="<?php the_permalink(); ?>">
        <?php if ( has_post_thumbnail() ) : ?>
        <div class="res-card-img"><?php the_post_thumbnail( 'gabochie-value-card' ); ?></div>
        <?php endif; ?>
        <div class="res-card-body">
          <span class="r-tag"><?php echo esc_html( $cat_name ); ?></span>
          <h3><?php the_title(); ?></h3>
          <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
          <span class="r-link"><?php esc_html_e( 'Read more', 'gabochie-value' ); ?> &rarr;</span>
        </div>
      </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <div class="pagination">
      <?php
      if ( $blog_query->max_num_pages > 1 ) {
          echo paginate_links(
              array(
                  'total'     => $blog_query->max_num_pages,
                  'current'   => $paged,
                  'mid_size'  => 2,
                  'prev_text' => '&larr; ' . __( 'Previous', 'gabochie-value' ),
                  'next_text' => __( 'Next', 'gabochie-value' ) . ' &rarr;',
              )
          );
      }
      ?>
    </div>
    <?php else : ?>
    <div class="sec-lead reveal"><?php esc_html_e( 'Nothing published here yet. Check back soon.', 'gabochie-value' ); ?></div>
    <div class="hero-actions">
      <a class="btn-gold" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'gabochie-value' ); ?></a>
    </div>
    <?php endif; ?>

  </div>
</section>

</main>

<?php get_footer(); ?>
