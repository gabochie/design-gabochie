<?php
/**
 * Default Index Template
 * @package Gabochie_Design
 */
get_header(); ?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Blog</div>
        <h1>Blog & <em>Insights</em></h1>
        <p>Expert guidance on building in Ghana, diaspora property, and AI-powered architecture.</p>
    </div>
</section>

<section style="padding:4rem 2.5rem;background:var(--cream)">
    <div class="container">
        <?php if (have_posts()) : ?>
            <div class="blog-grid">
                <?php while (have_posts()) : the_post();
                    $cat = get_the_category();
                    $cat_name = !empty($cat) ? $cat[0]->name : 'Tips';
                ?>
                <a href="<?php the_permalink(); ?>" class="blog-card" aria-label="<?php the_title_attribute(); ?>">
                    <div class="blog-img">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php echo get_the_post_thumbnail(get_the_ID(), 'gabochie-card', array('alt' => get_the_title())); ?>
                        <?php else : ?>
                            <img src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&q=80" alt="<?php the_title_attribute(); ?>">
                        <?php endif; ?>
                        <div class="blog-img-overlay"></div>
                        <span class="blog-cat-pill"><?php echo esc_html($cat_name); ?></span>
                    </div>
                    <div class="blog-body">
                        <div class="blog-title"><?php the_title(); ?></div>
                        <div class="blog-meta"><?php echo get_the_date('M d, Y'); ?> <span>·</span> <?php echo esc_html(get_post_meta(get_the_ID(), '_gabochie_read_time', true) ?: '8'); ?> min read</div>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p style="text-align:center;color:var(--mist);padding:4rem 0;">No blog posts found. Import content using the Gabochie Content Importer plugin.</p>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
