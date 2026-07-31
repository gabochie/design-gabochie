<?php get_header(); while (have_posts()) : the_post(); ?>
<section class="page-shell">
  <h1><?php the_title(); ?></h1>
  <div class="page-content"><?php the_content(); ?></div>
</section>
<?php endwhile; get_footer(); ?>
