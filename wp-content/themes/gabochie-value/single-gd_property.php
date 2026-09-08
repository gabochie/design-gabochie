<?php
/**
 * Template Name: Property Listing (single)
 *
 * Single gd_property template: full listing with the AI Property Intelligence
 * Report (location, comparables, development feasibility, risk, recommendation)
 * and the enquiry funnel (nonce + honeypot + rate limit) wired to the CRM +
 * Command Center.
 *
 * @package Gabochie_Value
 */

if (! defined('ABSPATH') ) {
    exit;
}

$post_id = get_the_ID();

$price    = (float) get_post_meta($post_id, Gabochie_Marketplace::META_PRICE, true);
$location = (string) get_post_meta($post_id, Gabochie_Marketplace::META_LOCATION, true);
$size     = (float) get_post_meta($post_id, Gabochie_Marketplace::META_SIZE_M2, true);
$bed      = (int) get_post_meta($post_id, Gabochie_Marketplace::META_BEDROOMS, true);
$bath     = (int) get_post_meta($post_id, Gabochie_Marketplace::META_BATHROOMS, true);
$agent    = (string) get_post_meta($post_id, Gabochie_Marketplace::META_AGENT_NAME, true);

$type   = ucwords(str_replace('-', ' ', Gabochie_Marketplace::get_type_slug($post_id)));
$status = ucwords(str_replace('-', ' ', Gabochie_Marketplace::get_status_slug($post_id)));

$intel = Gabochie_Marketplace::intelligence($post_id);

get_header();
?>

<main id="main" class="gmp-single">

<style>
.gmp-single{--gmp-teal:#0A4840;--gmp-teal-deep:#072E28;--gmp-gold:#C9A96E;--gmp-ivory:#FDFCF9;--gmp-ink:#1C2B28;--gmp-terra:#B8654A;}
.gmp-single{background:var(--gmp-ivory);color:var(--gmp-ink);}
.gmp-single .gmp-const{max-width:1120px;margin:0 auto;padding:34px 22px;}

/* hero */
.gmp-single .gmp-hero{display:grid;grid-template-columns:1.1fr .9fr;gap:30px;align-items:stretch;margin:0 0 34px 0;}
.gmp-single .gmp-hero-img{background:linear-gradient(135deg,var(--gmp-teal),var(--gmp-teal-deep));border-radius:16px;display:flex;align-items:center;justify-content:center;color:var(--gmp-gold);font-size:64px;min-height:300px;}
.gmp-single .gmp-hero-info{display:flex;flex-direction:column;justify-content:center;}
.gmp-single .gmp-ey{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--gmp-gold);margin:0 0 10px 0;}
.gmp-single .gmp-h1{font-family:Georgia,'Playfair Display',serif;font-size:clamp(26px,3.6vw,36px);line-height:1.15;color:var(--gmp-teal-deep);margin:0 0 12px 0;}
.gmp-single .gmp-price{font-size:28px;font-weight:700;color:var(--gmp-teal-deep);margin:0 0 6px 0;}
.gmp-single .gmp-price small{font-weight:500;font-size:13px;color:#7a857f;display:block;}
.gmp-single .gmp-specs{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0;list-style:none;padding:0;}
.gmp-single .gmp-specs li{background:#fff;border:1px solid #e4dfd0;border-radius:8px;padding:7px 12px;font-size:13px;}

/* intel report */
.gmp-single .gmp-intel{background:#fff;border:1px solid #e4dfd0;border-radius:16px;padding:30px;margin:0 0 34px 0;}
.gmp-single .gmp-intel h2{font-family:Georgia,'Playfair Display',serif;font-size:24px;color:var(--gmp-teal-deep);margin:0 0 18px 0;}
.gmp-single .gmp-zone{border-top:1px solid #ede7d8;padding:18px 0 4px 0;}
.gmp-single .gmp-zone h3{font-size:13px;letter-spacing:1px;text-transform:uppercase;color:var(--gmp-terra);margin:0 0 8px 0;}
.gmp-single .gmp-zone p{font-size:14.5px;line-height:1.6;color:#2c3a35;margin:0 0 8px 0;}
.gmp-single .gmp-tbl{width:100%;border-collapse:collapse;font-size:13.5px;}
.gmp-single .gmp-tbl th,.gmp-single .gmp-tbl td{text-align:left;padding:7px 8px;border-bottom:1px solid #efe9db;}
.gmp-single .gmp-tbl th{color:#7a857f;font-weight:600;}

/* enquiry */
.gmp-single .gmp-enq{background:var(--gmp-teal);border-radius:16px;padding:28px;color:#fff;margin:0 0 40px 0;}
.gmp-single .gmp-enq h2{font-family:Georgia,'Playfair Display',serif;font-size:22px;color:#fff;margin:0 0 6px 0;}
.gmp-single .gmp-enq p{font-size:14px;color:#d8e4e0;margin:0 0 16px 0;}
.gmp-single .gmp-enq label{display:block;font-size:12px;color:#d8e4e0;margin:8px 0 3px 0;}
.gmp-single .gmp-enq input,.gmp-single .gmp-enq textarea{width:100%;box-sizing:border-box;padding:11px 13px;border:0;border-radius:8px;font-size:14px;color:#1C2B28;}
.gmp-single .gmp-enq textarea{min-height:80px;resize:vertical;}
.gmp-single .gmp-enq-btn{border:0;border-radius:8px;background:var(--gmp-gold);color:#072E28;font-weight:700;padding:13px 20px;cursor:pointer;font-size:14px;margin-top:14px;}
.gmp-single .gmp-enq-btn:disabled{opacity:.6;cursor:progress;}
.gmp-single .gmp-enq-msg{font-size:14px;margin:12px 0 0 0;min-height:20px;}
.gmp-single .gmp-enq-msg.ok{color:#d8f0e8;}
.gmp-single .gmp-enq-msg.err{color:#f3c9c0;}
.gmp-single .gmp-hp{position:absolute;left:-9999px;opacity:0;height:0;width:0;}

@media (max-width:820px){.gmp-single .gmp-hero{grid-template-columns:1fr;}}
</style>

<section class="gmp-single">
    <div class="gmp-const">

        <div class="gmp-hero">
            <div class="gmp-hero-img">◆</div>
            <div class="gmp-hero-info">
                <p class="gmp-ey"><?php echo esc_html($location); ?><?php echo '' !== $type ? ' &middot; ' . esc_html($type) : ''; ?><?php echo '' !== $status ? ' &middot; ' . esc_html($status) : ''; ?></p>
                <h1 class="gmp-h1"><?php the_title(); ?></h1>
                <p class="gmp-price">
                    <?php echo esc_html(Gabochie_Marketplace::price_label($post_id)); ?>
                    <?php if ($price > 0 ) : ?><small>Approx. USD <?php echo esc_html(number_format((float) get_post_meta($post_id, Gabochie_Marketplace::META_PRICE_USD, true))); ?></small><?php 
                    endif; ?>
                </p>
                <ul class="gmp-specs">
                    <?php if ($size > 0 ) : ?><li><?php echo esc_html(number_format($size)); ?> m²</li><?php 
                    endif; ?>
                    <?php if ($bed > 0 ) : ?><li><?php echo esc_html((string) $bed); ?> bed</li><?php 
                    endif; ?>
                    <?php if ($bath > 0 ) : ?><li><?php echo esc_html((string) $bath); ?> bath</li><?php 
                    endif; ?>
                    <?php if ('' !== $agent ) : ?><li>Listed by <?php echo esc_html($agent); ?></li><?php 
                    endif; ?>
                </ul>
                <div class="the-content"><?php the_content(); ?></div>
            </div>
        </div>

        <?php if ($intel ) : ?>
        <div class="gmp-intel">
            <h2>Property Intelligence Report</h2>

            <?php if (! empty($intel['location']) ) : ?>
            <div class="gmp-zone">
                <h3>Location &amp; Demand</h3>
                <?php if (! empty($intel['location']['area']) ) : ?><p><strong>Area:</strong> <?php echo esc_html($intel['location']['area']); ?></p><?php 
                endif; ?>
                <?php if (! empty($intel['location']['infrastructure']) ) : ?><p><strong>Infrastructure:</strong> <?php echo esc_html($intel['location']['infrastructure']); ?></p><?php 
                endif; ?>
                <?php if (! empty($intel['location']['demand']) ) : ?><p><strong>Demand:</strong> <?php echo esc_html($intel['location']['demand']); ?></p><?php 
                endif; ?>
            </div>
            <?php endif; ?>

            <?php if (! empty($intel['comparables']) ) : ?>
            <div class="gmp-zone">
                <h3>Comparables</h3>
                <table class="gmp-tbl">
                    <thead><tr><th>Listing</th><th>Price</th><th>Size</th><th>₵/m²</th></tr></thead>
                    <tbody>
                <?php foreach ( (array) $intel['comparables'] as $c ) : ?>
                        <tr>
                            <td><?php echo esc_html($c['title'] ?? ''); ?></td>
                            <td><?php echo isset($c['price']) ? esc_html('GHS ' . number_format((float) $c['price'])) : '—'; ?></td>
                            <td><?php echo isset($c['size_m2']) ? esc_html(number_format((float) $c['size_m2']) . ' m²') : '—'; ?></td>
                            <td><?php echo isset($c['price_per_m2']) ? esc_html(number_format((float) $c['price_per_m2'])) : '—'; ?></td>
                        </tr>
                <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (! empty($intel['feasibility']) ) : ?>
            <div class="gmp-zone">
                <h3>Development Feasibility</h3>
                <?php foreach ( (array) $intel['feasibility'] as $fk => $fv ) : ?>
                    <?php if ('' !== (string) $fv && null !== $fv ) : ?>
                        <p><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $fk))); ?>:</strong> <?php echo esc_html(is_array($fv) ? wp_json_encode($fv) : (string) $fv); ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (! empty($intel['risk']) ) : ?>
            <div class="gmp-zone">
                <h3>Risk Flags</h3>
                <?php foreach ( (array) $intel['risk'] as $rk => $rv ) : ?>
                    <?php if ('' !== (string) $rv && null !== $rv ) : ?>
                        <p><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $rk))); ?>:</strong> <?php echo esc_html((string) $rv); ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (! empty($intel['recommendation']) ) : ?>
            <div class="gmp-zone">
                <h3>Recommendation</h3>
                <p><?php echo esc_html($intel['recommendation']); ?></p>
            </div>
            <?php endif; ?>

            <p style="font-size:11px;color:#a8b0ab;margin-top:14px;">Report auto-generated <?php echo esc_html(gmdate('j M Y', strtotime((string) ( $intel['generated_at'] ?? '' )))); ?> · <?php echo esc_html($intel['provider'] ?? ''); ?>. Figures are model estimates from the Gabochie build-cost engine and curated market context; always verify title and engage a professional surveyor before transacting.</p>
        </div>
        <?php else : ?>
        <div class="gmp-rec" style="background:var(--gmp-teal);border-radius:16px;padding:26px;color:#fff;margin:0 0 34px 0;">
            <p style="margin:0;color:#d8e4e0;">The AI intelligence report for this listing is being generated. Check back shortly for location, comparables, feasibility and risk.</p>
        </div>
        <?php endif; ?>

        <div class="gmp-enq">
            <h2>Interested in this property?</h2>
            <p>Send an enquiry and the Gabochie team will connect you with the seller or developer.</p>
            <form id="gmp-enquiry-form" data-listing="<?php echo esc_attr((string) $post_id); ?>">
                <?php wp_nonce_field('gmp_enquiry', 'gmp_enquiry_nonce'); ?>
                <label for="gmp-name">Full name</label>
                <input type="text" id="gmp-name" name="name" required />
                <label for="gmp-email">Email</label>
                <input type="email" id="gmp-email" name="email" required />
                <label for="gmp-phone">Phone (optional)</label>
                <input type="text" id="gmp-phone" name="phone" />
                <label for="gmp-message">Message</label>
                <textarea id="gmp-message" name="message" required></textarea>
                <input type="text" class="gmp-hp" name="gmp_hp" tabindex="-1" autocomplete="off" />
                <button type="submit" class="gmp-enq-btn">Send enquiry</button>
                <div class="gmp-enq-msg" id="gmp-enq-msg"></div>
            </form>
        </div>

    </div>
</section>

<script>
(function () {
    var form = document.getElementById('gmp-enquiry-form');
    if (!form) return;
    var msg = document.getElementById('gmp-enq-msg');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = form.querySelector('.gmp-enq-btn');
        btn.disabled = true;
        form.querySelectorAll('input,textarea').forEach(function (f) { f.disabled = true; });
        msg.className = 'gmp-enq-msg ok';
        msg.textContent = 'Sending…';
        var fd = new FormData(form);
        fd.append('action', 'gmp_enquiry');
        fd.append('listing_id', form.getAttribute('data-listing'));
        fetch('<?php echo esc_url_raw(admin_url('admin-ajax.php')); ?>', {
            method: 'POST',
            body: fd
        }).then(function (r) { return r.json(); }).then(function (res) {
            if (res && res.success) {
                msg.textContent = (res.data && res.data.message) ? res.data.message : 'Thanks — your enquiry has been sent.';
                form.reset();
            } else {
                msg.className = 'gmp-enq-msg err';
                msg.textContent = (res && res.data && res.data.message) ? res.data.message : 'Something went wrong. Please try again.';
            }
        }).catch(function () {
            msg.className = 'gmp-enq-msg err';
            msg.textContent = 'Network error. Please try again.';
        }).finally(function () {
            btn.disabled = false;
            form.querySelectorAll('input,textarea').forEach(function (f) { f.disabled = false; });
        });
    });
})();
</script>

</main>

<?php
get_footer();
