<?php
/**
 * PMPro Checkout page override.
 * @package Gabochie_Value
 */
global $pmpro_levels, $pmpro_msg, $pmpro_msgt, $pmpro_error_fields, $pmpro_level;

get_header('shop');
?>
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / <a href="<?php echo esc_url(home_url('/membership-levels/')); ?>">Membership Levels</a> / Checkout</div>
        <h1>Checkout</h1>
        <p>Complete your membership registration.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container" style="max-width:720px">
        <?php if (!empty($pmpro_msg)) : ?>
            <div class="mem-alert mem-alert-<?php echo $pmpro_msgt === 'error' ? 'error' : 'success'; ?>">
                <?php echo wp_kses_post($pmpro_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($pmpro_level)) : ?>
        <div class="pmpro_checkout_level" style="text-align:center;padding:1.5rem;background:var(--cream);border-radius:var(--r);border:1px solid var(--border-light);margin-bottom:2rem">
            <h3 style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:0.5rem"><?php echo esc_html($pmpro_level->name); ?></h3>
            <div style="font-size:1.8rem;font-weight:700;color:var(--gold);font-family:'Playfair Display',serif;margin-bottom:0.5rem">
                <?php echo $pmpro_level->initial_payment > 0 ? pmpro_formatPrice($pmpro_level->initial_payment) : 'Free'; ?>
            </div>
            <?php if (!empty($pmpro_level->description)) : ?>
                <p style="color:var(--mist);font-size:0.9rem"><?php echo nl2br(esc_html($pmpro_level->description)); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="pmpro_section">
            <?php
            // Render PMPro's checkout form
            // PMPro handles the actual form rendering via pmpro_checkout_form()
            if (function_exists('pmpro_checkout_form')) {
                pmpro_checkout_form();
            }
            ?>
        </div>
    </div>
</section>

<?php get_footer('shop');
