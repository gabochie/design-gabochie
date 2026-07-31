<?php
/**
 * Single Post Template
 * @package Gabochie_Design
 */
get_header();
?>

<?php while (have_posts()) : the_post();
    $cat = get_the_category();
    $cat_name = !empty($cat) ? $cat[0]->name : 'Tips';
    $read_time = get_post_meta(get_the_ID(), '_gabochie_read_time', true) ?: '8';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / <a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a> / <?php the_title(); ?></div>
        <h1><?php the_title(); ?></h1>
        <p><?php echo get_the_date('F d, Y'); ?> &middot; <?php echo esc_html($read_time); ?> min read &middot; <?php echo esc_html($cat_name); ?></p>
    </div>
</section>

<?php if (has_post_thumbnail()) : ?>
<section style="margin:0 2.5rem">
    <div class="container" style="max-width:960px">
        <?php the_post_thumbnail('gabochie-hero', array('style' => 'width:100%;height:auto;max-height:500px;object-fit:cover;border-radius:12px;display:block', 'alt' => get_the_title(), 'loading' => 'eager')); ?>
    </div>
</section>
<?php endif; ?>

<section style="padding:4rem 2.5rem;background:var(--cream)">
    <div class="container" style="max-width:780px">
        <article style="font-size:1.05rem;line-height:2;color:var(--charcoal)">
            <?php the_content(); ?>
        </article>

        <div style="margin-top:3rem;padding-top:2rem;border-top:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
            <div style="display:flex;gap:0.5rem">
                <?php foreach (get_the_tags() ?: array() as $tag) : ?>
                    <span style="background:var(--teal-light);color:var(--teal);padding:0.3rem 0.85rem;border-radius:20px;font-size:0.75rem;letter-spacing:0.05em"><?php echo esc_html($tag->name); ?></span>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:0.5rem">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_the_permalink()); ?>" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Share on Facebook">FB</a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_the_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Share on X (Twitter)">X</a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(get_the_permalink()); ?>" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Share on LinkedIn">LI</a>
            </div>
        </div>

        <div style="margin-top:3rem;display:flex;gap:2rem;justify-content:space-between;flex-wrap:wrap">
            <?php $prev = get_previous_post(); $next = get_next_post(); ?>
            <?php if ($prev) : ?>
                <a href="<?php echo get_permalink($prev); ?>" style="flex:1;min-width:200px;padding:1.5rem;border:1px solid var(--border);border-radius:var(--r);text-decoration:none;transition:border-color 0.2s">
                    <div style="font-size:0.7rem;letter-spacing:0.15em;text-transform:uppercase;color:var(--mist);margin-bottom:0.5rem">&larr; Previous</div>
                    <div style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--charcoal)"><?php echo esc_html($prev->post_title); ?></div>
                </a>
            <?php endif; ?>
            <?php if ($next) : ?>
                <a href="<?php echo get_permalink($next); ?>" style="flex:1;min-width:200px;padding:1.5rem;border:1px solid var(--border);border-radius:var(--r);text-decoration:none;text-align:right;transition:border-color 0.2s">
                    <div style="font-size:0.7rem;letter-spacing:0.15em;text-transform:uppercase;color:var(--mist);margin-bottom:0.5rem">Next &rarr;</div>
                    <div style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--charcoal)"><?php echo esc_html($next->post_title); ?></div>
                </a>
            <?php endif; ?>
        </div>

        <?php
        // Internal linking: Related posts
        $post_id = get_the_ID();
        $cats = wp_get_post_categories($post_id, array('fields' => 'ids'));
        $tags = wp_get_post_tags($post_id, array('fields' => 'ids'));
        $related = get_posts(array(
            'post__not_in' => array($post_id),
            'posts_per_page' => 3,
            'orderby' => 'rand',
            'tax_query' => array(
                'relation' => 'OR',
                array('taxonomy' => 'category', 'field' => 'term_id', 'terms' => $cats),
                array('taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $tags),
            ),
        ));
        if (empty($related)) {
            $related = get_posts(array('post__not_in' => array($post_id), 'posts_per_page' => 3, 'orderby' => 'rand'));
        }
        if (!empty($related)) :
        ?>
        <div style="margin-top:3rem;padding-top:2rem;border-top:1px solid var(--border-light)">
            <h3 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--charcoal);margin-bottom:1.5rem">Related Articles</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem">
                <?php foreach ($related as $r) : ?>
                    <a href="<?php echo get_permalink($r->ID); ?>" style="text-decoration:none;padding:1.2rem;border:1px solid var(--border-light);border-radius:var(--r);transition:border-color 0.2s">
                        <?php if (has_post_thumbnail($r->ID)) : ?>
                            <img src="<?php echo get_the_post_thumbnail_url($r->ID, 'gabochie-thumb'); ?>" alt="<?php echo esc_attr($r->post_title); ?>" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:0.8rem" loading="lazy">
                        <?php endif; ?>
                        <div style="font-family:'Playfair Display',serif;font-size:0.95rem;color:var(--charcoal);margin-bottom:0.3rem"><?php echo esc_html($r->post_title); ?></div>
                        <div style="font-size:0.75rem;color:var(--mist)"><?php echo get_the_date('M d, Y', $r->ID); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Ready to build something <em>beautiful</em> in Ghana?</h2>
        <p>No commitment &middot; 30 minutes &middot; Any time zone</p>
        <a href="mailto:<?php echo esc_attr(get_theme_mod('company_email', 'admin@gabochiedesign.com')); ?>?subject=Book%20Consultation" class="btn-gold">Book Free Consultation</a>
    </div>
</section>

<?php endwhile; ?>
<?php get_footer(); ?>
