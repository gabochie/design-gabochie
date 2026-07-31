<?php get_header();
while (have_posts()) : the_post();
$icon = get_post_meta(get_the_ID(), '_tool_icon', true) ?: '🔧';
$price = get_post_meta(get_the_ID(), '_tool_price', true) ?: '$29/mo';
$tag = get_post_meta(get_the_ID(), '_tool_tag', true) ?: 'General';
$terms = wp_get_post_terms(get_the_ID(), 'tool_status');
$status = $terms ? $terms[0]->slug : 'live';
$label_map = ['live'=>'LIVE','beta'=>'BETA','new'=>'NEW','coming-soon'=>'COMING SOON'];
$badge_map = ['live'=>'#2D8F5E','beta'=>'#2D7FA8','new'=>'#A67B2A','coming-soon'=>'#888'];
$blabel = $label_map[$status] ?? 'LIVE';
$bcolor = $badge_map[$status] ?? '#2D8F5E';
?>
<section style="padding:3rem 2rem;max-width:var(--max-w);margin:0 auto">
  <a href="/tools/" style="color:var(--gs-300);font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:2rem">← All Tools</a>
  <div style="display:flex;align-items:flex-start;gap:1.5rem;flex-wrap:wrap">
    <div style="font-size:3rem;width:72px;height:72px;background:rgba(61,189,182,.1);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0"><?php echo $icon; ?></div>
    <div style="flex:1;min-width:280px">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px">
        <span style="background:rgba(50,200,120,.12);color:<?php echo $bcolor; ?>;font-size:10px;padding:3px 10px;border-radius:3px;font-weight:500"><?php echo $blabel; ?></span>
        <span style="font-size:11px;color:var(--gs-300);text-transform:uppercase;letter-spacing:.08em"><?php echo esc_html($tag); ?></span>
      </div>
      <h1 style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;margin-bottom:.5rem"><?php the_title(); ?></h1>
      <p style="font-size:15px;color:var(--text-body);line-height:1.7;max-width:600px"><?php echo get_the_excerpt(); ?></p>
    </div>
    <div style="background:rgba(20,82,80,.25);border:1px solid var(--border-subtle);border-radius:10px;padding:1.25rem 1.5rem;text-align:center;flex-shrink:0">
      <div style="font-size:12px;color:var(--text-dim);margin-bottom:4px">Starting at</div>
      <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700"><?php echo esc_html($price); ?></div>
      <a href="/pricing/" style="display:inline-block;margin-top:10px;background:var(--gs-400);color:#fff;text-decoration:none;padding:9px 22px;border-radius:4px;font-size:13px;font-weight:500">Get Access</a>
    </div>
  </div>
  <div style="margin-top:2.5rem;border-top:1px solid var(--border-subtle);padding-top:2rem">
    <div style="max-width:720px;font-size:15px;line-height:1.75;color:var(--text-body)"><?php the_content(); ?></div>
  </div>
</section>
<?php endwhile; get_footer(); ?>
