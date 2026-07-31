<?php get_header(); ?>
<section class="section" style="padding-top:3rem">
  <div class="text-center" style="margin-bottom:2rem">
    <div class="sec-label"><?php echo is_day() ? 'Daily' : (is_month() ? 'Monthly' : (is_year() ? 'Yearly' : 'Archives')); ?></div>
    <h1 class="sec-title">
      <?php if (is_category()) : single_cat_title();
      elseif (is_tag()) : single_tag_title();
      elseif (is_author()) : the_author();
      elseif (is_day()) : echo get_the_date('F j, Y');
      elseif (is_month()) : echo get_the_date('F Y');
      elseif (is_year()) : echo get_the_date('Y');
      else : echo 'Blog';
      endif; ?>
    </h1>
  </div>
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <article style="background:rgba(20,82,80,.25);border:1px solid var(--border-subtle);border-radius:10px;padding:1.5rem;margin-bottom:1rem">
    <h2 style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;margin-bottom:.4rem"><a href="<?php the_permalink(); ?>" style="color:#fff;text-decoration:none"><?php the_title(); ?></a></h2>
    <div style="font-size:12px;color:var(--text-dim);margin-bottom:.6rem"><?php echo get_the_date(); ?></div>
    <p style="font-size:14px;color:var(--text-body)"><?php the_excerpt(); ?></p>
  </article>
  <?php endwhile; endif; wp_reset_postdata(); ?>
</section>
<?php get_footer(); ?>
