<?php
/**
 * Template: Property taxonomy archive.
 *
 * Renders the /marketplace/type/<term> and /marketplace/status/<term> grids.
 * Reuses the gmp-mp card styles from the landing template.
 *
 * @package Gabochie_Value
 */

if (! defined('ABSPATH') ) {
    exit;
}

$term = get_queried_object();

get_header();
?>

<main id="main" class="gmp-mp">

<style>
.gmp-mp{--gmp-teal:#0A4840;--gmp-teal-deep:#072E28;--gmp-gold:#C9A96E;--gmp-ivory:#FDFCF9;--gmp-ink:#1C2B28;--gmp-terra:#B8654A;}
.gmp-mp{background:var(--gmp-ivory);color:var(--gmp-ink);}
.gmp-mp .gmp-const{max-width:1120px;margin:0 auto;padding:0 22px;}
.gmp-mp .gmp-h1{font-family:Georgia,'Playfair Display',serif;font-size:clamp(30px,4.4vw,46px);line-height:1.12;color:#fff;margin:0 0 12px 0;}
.gmp-mp .gmp-sub{font-size:16px;line-height:1.6;color:#cfe0db;max-width:640px;margin:0;}
.gmp-mp .gmp-ey{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--gmp-gold);margin:44px 0 6px 0;}
.gmp-mp .gmp-h2{font-family:Georgia,'Playfair Display',serif;font-size:30px;color:var(--gmp-teal-deep);margin:0 0 22px 0;}
.gmp-mp .gmp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin:0 0 50px 0;}
.gmp-mp .gmp-card{display:flex;flex-direction:column;background:#fff;border:1px solid #e4dfd0;border-radius:14px;overflow:hidden;text-decoration:none;color:var(--gmp-ink);transition:transform .16s ease,box-shadow .16s ease;position:relative;}
.gmp-mp .gmp-card:hover{transform:translateY(-3px);box-shadow:0 10px 26px rgba(7,46,40,.10);}
.gmp-mp .gmp-badge{position:absolute;top:14px;left:14px;background:var(--gmp-terra);color:#fff;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;padding:5px 10px;border-radius:6px;}
.gmp-mp .gmp-thumb{height:170px;background:linear-gradient(135deg,var(--gmp-teal),var(--gmp-teal-deep));display:flex;align-items:center;justify-content:center;color:var(--gmp-gold);font-size:34px;}
.gmp-mp .gmp-body{padding:20px;display:flex;flex-direction:column;flex:1;}
.gmp-mp .gmp-loc{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--gmp-gold);margin:0 0 8px 0;}
.gmp-mp .gmp-card h3{font-size:17px;line-height:1.35;margin:0 0 10px 0;}
.gmp-mp .gmp-price{font-size:20px;font-weight:700;color:var(--gmp-teal-deep);margin:0 0 12px 0;}
.gmp-mp .gmp-arr{font-size:13px;color:var(--gmp-teal);font-weight:600;margin-top:auto;padding-top:10px;}
.gmp-mp .gmp-intel{font-size:11px;color:#0a7d33;margin:0 0 12px 0;}
.gmp-mp .gmp-back{display:inline-block;background:#fff;border:1px solid #e4dfd0;border-radius:8px;padding:9px 16px;font-size:13px;color:var(--gmp-teal);text-decoration:none;margin:26px 0 30px 0;}
.gmp-mp .gmp-back:hover{border-color:var(--gmp-gold);}

@media (max-width:900px){.gmp-mp .gmp-grid{grid-template-columns:1fr 1fr;}}
@media (max-width:560px){.gmp-mp .gmp-grid{grid-template-columns:1fr;}}
</style>

<section class="page-hero" style="background:var(--gmp-teal-deep);color:#fff;">
    <div class="container gmp-const" style="padding:50px 22px;">
        <div style="font-size:11px;letter-spacing:3px;text-transform:uppercase;color:var(--gmp-gold);margin:0 0 12px 0;">Gabochie Marketplace</div>
        <h1 class="gmp-h1"><?php echo esc_html($term instanceof WP_Term ? $term->name : ''); ?> opportunities</h1>
        <p class="gmp-sub"><?php echo esc_html($term instanceof WP_Term ? wp_strip_all_tags(term_description($term->term_id)) : ''); ?></p>
    </div>
</section>

<section class="gmp-mp" style="padding:0 0 10px 0;">
    <div class="gmp-const">
        <a class="gmp-back" href="<?php echo esc_url(home_url('/marketplace/')); ?>">&larr; All marketplace listings</a>

        <?php if (have_posts() ) : ?>
        <div class="gmp-grid">
            <?php
            while ( have_posts() ) :
                the_post();
                $loc = (string) get_post_meta(get_the_ID(), Gabochie_Marketplace::META_LOCATION, true);
                $has_i = Gabochie_Marketplace::has_intelligence(get_the_ID());
                ?>
            <a class="gmp-card" href="<?php the_permalink(); ?>">
                <div class="gmp-thumb">◆</div>
                <div class="gmp-body">
                    <p class="gmp-loc"><?php echo esc_html($loc); ?></p>
                    <h3><?php the_title(); ?></h3>
                    <p class="gmp-price"><?php echo esc_html(Gabochie_Marketplace::price_label(get_the_ID())); ?></p>
                <?php if ($has_i ) : ?><p class="gmp-intel">Intelligence report included</p><?php 
                endif; ?>
                    <span class="gmp-arr">View intelligence &rsaquo;</span>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php else : ?>
        <p style="color:#40504b;font-size:15px;margin:0 0 40px 0;">No listings in this category yet — check back soon.</p>
        <?php endif; ?>
    </div>
</section>

</main>

<?php
get_footer();
