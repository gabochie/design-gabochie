<?php
/**
 * SEO, Ads, Lazy Loading, Image Optimization
 * @package Gabochie_Design
 */
if (!defined('ABSPATH')) exit;

/* ============================================
   ADSENSE AD PLACEMENT HOOKS
   ============================================ */
function gabochie_ad_header() {
    if (is_singular() || is_home() || is_archive()) {
        echo '<div class="gabochie-ad gabochie-ad-header" style="text-align:center;padding:1rem 0"><!-- AdSense header ad --></div>';
    }
}
add_action('wp_body_open', 'gabochie_ad_header');

function gabochie_ad_in_content() {
    if (is_single()) {
        echo '<div class="gabochie-ad gabochie-ad-inline" style="text-align:center;padding:1.5rem 0;margin:2rem 0"><!-- AdSense in-article ad --></div>';
    }
}

function gabochie_ad_sidebar() {
    echo '<div class="gabochie-ad gabochie-ad-sidebar" style="text-align:center;padding:1rem 0"><!-- AdSense sidebar ad --></div>';
}

/* ============================================
   LAZY LOADING + IMAGE OPTIMIZATION
   ============================================ */
function gabochie_lazy_load_images($content) {
    if (is_admin()) return $content;
    if (is_front_page()) {
        $content = preg_replace('/<img((?!.*loading=)[^>]*)data-no-lazy[^>]*>/', '<img$1>', $content);
    }
    $content = preg_replace('/<img((?!.*loading=)(?!.*fetchpriority="high")[^>]*)>/', '<img$1 loading="lazy" decoding="async">', $content);
    return $content;
}
add_filter('the_content', 'gabochie_lazy_load_images', 99);

function gabochie_add_fetchpriority($content) {
    if (is_admin()) return $content;
    if (is_singular() && has_post_thumbnail()) {
        $content = preg_replace('/<img([^>]*)class="([^"]*wp-post-image[^"]*)"([^>]*)>/', '<img$1class="$2"$3 fetchpriority="high">', $content, 1);
    }
    return $content;
}
add_filter('the_content', 'gabochie_add_fetchpriority', 98);

/* ============================================
   ADSENCE: BLOG POSTS ONLY
   ============================================ */
add_action('wp_enqueue_scripts', function() {
    if (!is_singular('post')) {
        wp_dequeue_script('googlesitekit-adsense');
        wp_deregister_script('googlesitekit-adsense');
    }
}, 99);

add_filter('googlesitekit_adsense_tag', function($tag) {
    if (!is_singular('post')) {
        return '';
    }
    return $tag;
});

add_action('wp_head', function() {
    if (!is_singular('post')) {
        ?>
        <style>.google-auto-placed, ins.adsbygoogle { display: none !important; }</style>
        <?php
    }
}, 1);
