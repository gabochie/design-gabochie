<?php
/**
 * Template Name: Marketplace
 *
 * Gabochie Marketplace landing page: featured listings, the type/status filter
 * lanes, a live listings grid and the enquiry/recovery CTA. Assigned to
 * /marketplace/ by the gabochie-marketplace plugin on activation.
 *
 * @package Gabochie_Value
 */

if (! defined('ABSPATH') ) {
    exit;
}

$marketplace = Gabochie_Marketplace::instance();

$featured = new WP_Query(
    array(
        'post_type'      => Gabochie_Marketplace::CPT,
        'post_status'    => 'publish',
        'posts_per_page' => 6,
        'no_found_rows'  => true,
        'meta_key'       => Gabochie_Marketplace::META_FEATURED, // phpcs:ignore WordPress.DB.SlowDBQuery
        'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
    )
);

$latest = new WP_Query(
    array(
        'post_type'      => Gabochie_Marketplace::CPT,
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'no_found_rows'  => true,
    )
);

$types   = Gabochie_Marketplace::type_terms();
$statuses = Gabochie_Marketplace::status_terms();

get_header();
?>

<main id="main" class="gmp-mp">

<style>
.gmp-mp{--gmp-teal:#0A4840;--gmp-teal-deep:#072E28;--gmp-gold:#C9A96E;--gmp-ivory:#FDFCF9;--gmp-ink:#1C2B28;--gmp-terra:#B8654A;}
.gmp-mp{background:var(--gmp-ivory);color:var(--gmp-ink);}
.gmp-mp .gmp-const{max-width:1120px;margin:0 auto;padding:0 22px;}
.gmp-mp .gmp-h1{font-family:Georgia,'Playfair Display',serif;font-size:clamp(30px,4.4vw,48px);line-height:1.12;color:#fff;margin:0 0 14px 0;}
.gmp-mp .gmp-sub{font-size:17px;line-height:1.6;color:#cfe0db;max-width:680px;margin:0 0 26px 0;}

/* filter lanes */
.gmp-mp .gmp-lanes{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 30px 0;}
.gmp-mp .gmp-lane{display:inline-block;background:#fff;border:1px solid #e4dfd0;border-radius:100px;padding:7px 16px;font-size:13px;color:var(--gmp-ink);text-decoration:none;}
.gmp-mp .gmp-lane:hover{border-color:var(--gmp-gold);}
.gmp-mp .gmp-lane.act{border-color:var(--gmp-teal);color:#fff;background:var(--gmp-teal);}

/* grid */
.gmp-mp .gmp-ey{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--gmp-gold);margin:44px 0 6px 0;}
.gmp-mp .gmp-h2{font-family:Georgia,'Playfair Display',serif;font-size:30px;color:var(--gmp-teal-deep);margin:0 0 22px 0;}
.gmp-mp .gmp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin:0 0 40px 0;}
.gmp-mp .gmp-card{display:flex;flex-direction:column;background:#fff;border:1px solid #e4dfd0;border-radius:14px;overflow:hidden;text-decoration:none;color:var(--gmp-ink);transition:transform .16s ease,box-shadow .16s ease;position:relative;}
.gmp-mp .gmp-card:hover{transform:translateY(-3px);box-shadow:0 10px 26px rgba(7,46,40,.10);}
.gmp-mp .gmp-badge{position:absolute;top:14px;left:14px;background:var(--gmp-terra);color:#fff;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;padding:5px 10px;border-radius:6px;}
.gmp-mp .gmp-badge.feat{background:var(--gmp-gold);color:#072E28;}
.gmp-mp .gmp-thumb{height:170px;background:linear-gradient(135deg,var(--gmp-teal),var(--gmp-teal-deep));display:flex;align-items:center;justify-content:center;color:var(--gmp-gold);font-size:34px;}
.gmp-mp .gmp-body{padding:20px;display:flex;flex-direction:column;flex:1;}
.gmp-mp .gmp-loc{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--gmp-gold);margin:0 0 8px 0;}
.gmp-mp .gmp-card h3{font-size:17px;line-height:1.35;margin:0 0 10px 0;}
.gmp-mp .gmp-price{font-size:20px;font-weight:700;color:var(--gmp-teal-deep);margin:0 0 12px 0;}
.gmp-mp .gmp-price small{font-weight:500;font-size:12px;color:#7a857f;display:block;}
.gmp-mp .gmp-arr{font-size:13px;color:var(--gmp-teal);font-weight:600;margin-top:auto;padding-top:10px;}
.gmp-mp .gmp-intel{font-size:11px;color:#0a7d33;margin:0 0 12px 0;}

/* cta band */
.gmp-mp .gmp-rec{background:var(--gmp-teal);border-radius:16px;padding:32px;color:#fff;margin:0 0 50px 0;display:flex;justify-content:space-between;align-items:center;gap:22px;flex-wrap:wrap;}
.gmp-mp .gmp-rec h3{font-family:Georgia,'Playfair Display',serif;font-size:22px;color:#fff;margin:0 0 6px 0;}
.gmp-mp .gmp-rec p{font-size:14px;line-height:1.6;color:#d8e4e0;margin:0;max-width:640px;}
.gmp-mp .gmp-rec a{display:inline-block;background:var(--gmp-gold);color:#072E28;text-decoration:none;padding:13px 22px;border-radius:8px;font-weight:700;white-space:nowrap;}
.gmp-mp .gmp-rec a:hover{background:#dcbf8c;}

@media (max-width:900px){.gmp-mp .gmp-grid{grid-template-columns:1fr 1fr;}}
@media (max-width:560px){.gmp-mp .gmp-grid{grid-template-columns:1fr;}}
</style>

<!-- ========== HERO ========== -->
<section class="page-hero" style="background:var(--gmp-teal-deep);color:#fff;">
    <div class="container gmp-const" style="padding:56px 22px;">
        <div style="font-size:11px;letter-spacing:3px;text-transform:uppercase;color:var(--gmp-gold);margin:0 0 12px 0;">Gabochie Marketplace</div>
        <h1 class="gmp-h1">Find property. Know its potential. Make it happen.</h1>
        <p class="gmp-sub">Curated Ghana property and development opportunities, each with a fully automated AI intelligence report — location, comparables, build feasibility and the honest risk picture — before you ever pick up the phone.</p>
    </div>
</section>

<section class="gmp-mp" style="padding:34px 0 10px 0;">
    <div class="gmp-const">

        <div class="gmp-lanes">
            <span style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#7a857f;padding:8px 6px 0 0;">Type</span>
            <a class="gmp-lane act" href="<?php echo esc_url(home_url('/marketplace/')); ?>">All</a>
            <?php foreach ( (array) $types as $term ) : ?>
                <?php if (! is_wp_error($term) ) : ?>
                <a class="gmp-lane" href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="gmp-ey">Hand-picked opportunities</div>
        <h2 class="gmp-h2">Featured listings</h2>

        <?php if ($featured->have_posts() ) : ?>
        <div class="gmp-grid">
            <?php
            while ( $featured->have_posts() ) :
                $featured->the_post();
                $loc   = (string) get_post_meta(get_the_ID(), Gabochie_Marketplace::META_LOCATION, true);
                $type  = ucwords(str_replace('-', ' ', Gabochie_Marketplace::get_type_slug(get_the_ID())));
                $has_i = Gabochie_Marketplace::has_intelligence(get_the_ID());
                ?>
            <a class="gmp-card" href="<?php the_permalink(); ?>">
                <span class="gmp-badge feat">Featured</span>
                <div class="gmp-thumb">◆</div>
                <div class="gmp-body">
                    <p class="gmp-loc"><?php echo esc_html($loc); ?><?php echo '' !== $type ? ' &middot; ' . esc_html($type) : ''; ?></p>
                    <h3><?php the_title(); ?></h3>
                    <p class="gmp-price"><?php echo esc_html(Gabochie_Marketplace::price_label(get_the_ID())); ?><small><?php echo esc_html(wp_trim_words(get_the_excerpt(), 14, '…')); ?></small></p>
                <?php if ($has_i ) : ?><p class="gmp-intel">Intelligence report included</p><?php 
                endif; ?>
                    <span class="gmp-arr">View intelligence &rsaquo;</span>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
        <p style="color:#40504b;font-size:15px;">Featured opportunities will appear here as the marketplace fills.</p>
        <?php endif; ?>

        <div class="gmp-ey">All opportunities</div>
        <h2 class="gmp-h2">Latest listings</h2>

        <?php if ($latest->have_posts() ) : ?>
        <div class="gmp-grid">
            <?php
            while ( $latest->have_posts() ) :
                $latest->the_post();
                $loc   = (string) get_post_meta(get_the_ID(), Gabochie_Marketplace::META_LOCATION, true);
                $type  = ucwords(str_replace('-', ' ', Gabochie_Marketplace::get_type_slug(get_the_ID())));
                $has_i = Gabochie_Marketplace::has_intelligence(get_the_ID());
                ?>
            <a class="gmp-card" href="<?php the_permalink(); ?>">
                <span class="gmp-badge"><?php echo esc_html($type); ?></span>
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
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
        <p style="color:#40504b;font-size:15px;">The marketplace is being stocked — listings land here shortly.</p>
        <?php endif; ?>

        <div class="gmp-rec">
            <div>
                <h3>Falling for a listing? Price is only half the story.</h3>
                <p>Every Gabochie listing comes with an AI intelligence report. If you are buying to develop, verify the build maths, the comparables and the risks before you commit — or book the GHS 1,500 Recovery Diagnostic for a deeper verdict.</p>
            </div>
            <a href="<?php echo esc_url(gabochie_value_recovery_product_url()); ?>">Book the Diagnostic</a>
        </div>
    </div>
</section>

</main>

<?php
get_footer();
