<?php
/* Archive: Tools by Category */
get_header();
$term = get_queried_object();
$cat_name = is_tax('tool_category') ? $term->name : 'All Tools';
?>

<section class="section" style="padding-top:3rem">
  <div class="tools-header">
    <div>
      <div class="sec-label">Tools</div>
      <h1 class="sec-title"><?php echo esc_html($cat_name); ?></h1>
    </div>
    <div class="filter-row">
      <a href="/tools/" class="filter-chip <?php echo !is_tax('tool_category') ? 'on' : ''; ?>">All</a>
      <?php
      $cats = get_terms(['taxonomy' => 'tool_category', 'hide_empty' => false]);
      foreach ($cats as $c) {
        $act = (is_tax('tool_category') && $term->term_id === $c->term_id) ? 'on' : '';
        echo '<a href="' . get_term_link($c) . '" class="filter-chip ' . $act . '">' . $c->name . '</a>';
      }
      ?>
    </div>
  </div>

  <?php if (have_posts()) : ?>
  <div class="grid grid-3">
    <?php while (have_posts()) : the_post();
      $icon = get_post_meta(get_the_ID(), '_tool_icon', true) ?: '🔧';
      $terms = wp_get_post_terms(get_the_ID(), 'tool_status');
      $status = $terms ? $terms[0]->slug : 'live';
      $label_map = ['live'=>'● LIVE','beta'=>'BETA','new'=>'NEW','coming-soon'=>'COMING SOON'];
      $badge_map = ['live'=>'b-live','beta'=>'b-beta','new'=>'b-new','coming-soon'=>'b-soon'];
    ?>
    <div class="tool-card" onclick="location.href='<?php the_permalink(); ?>'">
      <div class="tool-icon"><?php echo $icon; ?></div>
      <span class="badge <?php echo $badge_map[$status] ?? 'b-live'; ?>"><?php echo $label_map[$status] ?? '● LIVE'; ?></span>
      <h3><?php the_title(); ?></h3>
      <p><?php the_excerpt(); ?></p>
      <div class="tool-foot">
        <span class="tool-cat"><?php echo esc_html($cat_name); ?></span>
        <div class="tool-arr">→</div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php else : ?>
  <div class="text-center" style="padding:3rem 0"><p style="color:var(--text-dim)">No tools in this category yet.</p></div>
  <?php endif; wp_reset_postdata(); ?>
</section>

<?php get_footer(); ?>
