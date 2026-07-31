<?php
/* Template Name: All Tools */
get_header(); ?>

<section class="section" style="padding-top:3rem">
  <div class="tools-header">
    <div>
      <div class="sec-label">The Ecosystem</div>
      <h1 class="sec-title">All Tools &amp; Calculators</h1>
      <div class="sec-sub">Every tool is purpose-built for Ghana's AEC industry — powered by local data and AI.</div>
    </div>
    <div class="filter-row" id="toolFilters">
      <button class="filter-chip on" data-filter="all">All</button>
      <button class="filter-chip" data-filter="live">● Live</button>
      <button class="filter-chip" data-filter="beta">Beta</button>
      <button class="filter-chip" data-filter="new">New</button>
      <button class="filter-chip" data-filter="coming-soon">Coming Soon</button>
    </div>
  </div>

  <?php
  $tools = new WP_Query([
    'post_type' => 'saas_tool',
    'posts_per_page' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC',
  ]);

  if ($tools->have_posts()) : ?>
  <div class="grid grid-3" id="toolsGrid">
    <?php while ($tools->have_posts()) : $tools->the_post();
      $icon = get_post_meta(get_the_ID(), '_tool_icon', true) ?: '🔧';
      $price = get_post_meta(get_the_ID(), '_tool_price', true) ?: '$29/mo';
      $tag = get_post_meta(get_the_ID(), '_tool_tag', true) ?: 'General';
      $terms = wp_get_post_terms(get_the_ID(), 'tool_status');
      $status = $terms ? $terms[0]->slug : 'live';
      $badge_map = ['live'=>'b-live','beta'=>'b-beta','new'=>'b-new','coming-soon'=>'b-soon'];
      $label_map = ['live'=>'● LIVE','beta'=>'BETA','new'=>'NEW','coming-soon'=>'COMING SOON'];
      $bclass = $badge_map[$status] ?? 'b-live';
      $blabel = $label_map[$status] ?? '● LIVE';
    ?>
    <div class="tool-card" data-status="<?php echo esc_attr($status); ?>" onclick="location.href='<?php the_permalink(); ?>'">
      <div class="tool-icon"><?php echo $icon; ?></div>
      <span class="badge <?php echo $bclass; ?>"><?php echo $blabel; ?></span>
      <h3><?php the_title(); ?></h3>
      <p><?php the_excerpt(); ?></p>
      <div class="tool-foot">
        <span class="tool-cat"><?php echo esc_html($tag); ?></span>
        <div class="tool-arr">→</div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php else : ?>
  <div class="text-center" style="padding:3rem 0">
    <p style="color:var(--text-dim);font-size:15px">No tools published yet. Check back soon.</p>
  </div>
  <?php endif; wp_reset_postdata(); ?>

  <!-- COMING SOON -->
  <div class="soon-strip" style="margin-top:3rem;border:1px dashed var(--border-mid);border-radius:12px;padding:2rem 2.5rem;display:flex;align-items:center;justify-content:space-between;gap:2rem">
    <div>
      <div class="sec-label" style="margin-bottom:6px">Roadmap — Next Up</div>
      <h3 style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:#fff">More tools in the pipeline</h3>
      <p style="font-size:13px;color:var(--text-dim);margin-top:4px;max-width:480px">Quantity surveying assistant, project timeline generator, permit cost estimator, and an invoice builder — all tuned for Ghana's market.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0">
      <span style="padding:6px 14px;border-radius:6px;background:rgba(42,158,153,.1);border:1px solid var(--border-subtle);font-size:12px;color:var(--text-dim)">QS Assistant</span>
      <span style="padding:6px 14px;border-radius:6px;background:rgba(42,158,153,.1);border:1px solid var(--border-subtle);font-size:12px;color:var(--text-dim)">Timeline Gen</span>
      <span style="padding:6px 14px;border-radius:6px;background:rgba(42,158,153,.1);border:1px solid var(--border-subtle);font-size:12px;color:var(--text-dim)">Permit Costs</span>
      <span style="padding:6px 14px;border-radius:6px;background:rgba(42,158,153,.1);border:1px solid var(--border-subtle);font-size:12px;color:var(--text-dim)">Invoice Builder</span>
    </div>
  </div>
</section>

<script>
document.querySelectorAll('#toolFilters .filter-chip').forEach(function(chip){
  chip.addEventListener('click', function(){
    document.querySelectorAll('#toolFilters .filter-chip').forEach(function(c){ c.classList.remove('on'); });
    chip.classList.add('on');
    var f = chip.dataset.filter;
    document.querySelectorAll('#toolsGrid .tool-card').forEach(function(card){
      card.style.display = (f === 'all' || card.dataset.status === f) ? '' : 'none';
    });
  });
});
</script>

<?php get_footer(); ?>
