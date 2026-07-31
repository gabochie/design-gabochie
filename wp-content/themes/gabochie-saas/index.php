<?php get_header();
if (have_posts()) : while (have_posts()) : the_post(); ?>
<section class="page-shell">
  <h1><?php the_title(); ?></h1>
  <div class="page-content"><?php the_content(); ?></div>
</section>
<?php endwhile; else : ?>
<section class="error-404">
  <div class="e404">—</div>
  <h1>No Content Found</h1>
  <a href="/" class="btn-primary">Back to Home</a>
</section>
<?php endif; get_footer(); ?>
