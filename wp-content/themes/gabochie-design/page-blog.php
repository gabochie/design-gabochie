<?php
/**
 * Template Name: Blog Archive
 * @package Gabochie_Design
 */
get_header();

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$blog_query = new WP_Query(array(
    'post_type'      => 'post',
    'posts_per_page' => 12,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'paged'          => $paged,
));
$cats = get_categories(array('hide_empty' => true));
$current_cat = get_query_var('cat') ? get_query_var('cat') : 0;
?>
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Blog</div>
        <h1>Blog & <em>Insights</em></h1>
        <p>Expert guidance on building in Ghana, diaspora property, and AI-powered architecture.</p>
    </div>
</section>

<section style="padding:4rem 2.5rem;background:var(--cream)">
    <div class="container">
        <div class="blog-toolbar">
            <div class="blog-cat-filters">
                <a href="<?php echo esc_url(get_permalink()); ?>" class="blog-cat-filter<?php echo !$current_cat ? ' active' : ''; ?>">All</a>
                <?php foreach ($cats as $c) : ?>
                    <a href="<?php echo esc_url(get_category_link($c->term_id)); ?>" class="blog-cat-filter<?php echo $current_cat == $c->term_id ? ' active' : ''; ?>"><?php echo esc_html($c->name); ?></a>
                <?php endforeach; ?>
            </div>
            <form role="search" method="get" class="blog-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="post_type" value="post">
                <input type="search" class="blog-search-input" placeholder="Search articles..." value="<?php echo get_search_query(); ?>" name="s" aria-label="Search articles">
                <button type="submit" class="blog-search-btn" aria-label="Search">Search</button>
            </form>
        </div>

        <?php if ($blog_query->have_posts()) : ?>
            <div class="blog-grid">
                <?php while ($blog_query->have_posts()) : $blog_query->the_post();
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
                        <div class="blog-meta"><?php echo get_the_date('M d, Y'); ?> <span>&middot;</span> <?php echo esc_html(get_post_meta(get_the_ID(), '_gabochie_read_time', true) ?: '8'); ?> min read</div>
                    </div>
                </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

            <?php
            $total_pages = $blog_query->max_num_pages;
            if ($total_pages > 1) :
                $big = 999999999;
                $paginate = paginate_links(array(
                    'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format'    => '?paged=%#%',
                    'current'   => max(1, $paged),
                    'total'     => $total_pages,
                    'prev_text' => '&larr; Previous',
                    'next_text' => 'Next &rarr;',
                    'type'      => 'list',
                    'mid_size'  => 2,
                ));
                if ($paginate) echo '<div class="blog-pagination">' . $paginate . '</div>';
            endif;
            ?>
        <?php else : ?>
            <div style="text-align:center;padding:4rem 0;color:var(--mist);">
                <p style="font-size:1.2rem;margin-bottom:1rem;">No posts found.</p>
                <a href="<?php echo esc_url(get_permalink()); ?>" class="btn-gold">&larr; Back to Blog</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
