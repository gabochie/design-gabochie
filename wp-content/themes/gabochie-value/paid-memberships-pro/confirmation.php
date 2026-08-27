<?php
/**
 * PMPro Confirmation page override.
 * @package Gabochie_Value
 */
global $pmpro_invoice;

get_header('shop');
?>
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Confirmation</div>
        <h1>Welcome <em>Aboard</em></h1>
        <p>Your membership is now active.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container" style="max-width:640px;text-align:center">
        <div class="pmpro_confirmation_wrap">
            <div class="pmpro_confirmation_icon" style="width:80px;height:80px;border-radius:50%;background:var(--teal-light);border:2px solid var(--teal);display:flex;align-items:center;justify-content:center;margin:0 auto 2rem;color:var(--teal);font-size:2rem">&#10003;</div>

            <h2 style="font-family:'Playfair Display',serif;font-size:2rem;margin-bottom:1rem">Thank You!</h2>

            <p style="color:var(--mist);font-size:1rem;line-height:1.8;margin-bottom:2rem">
                <?php if (!empty($pmpro_invoice)) : ?>
                    Your payment has been processed successfully. A confirmation email has been sent to your email address.
                <?php else : ?>
                    Your membership has been created. A confirmation email has been sent to your email address.
                <?php endif; ?>
            </p>

            <?php if (!empty($pmpro_invoice)) : ?>
            <div style="background:var(--cream);border:1px solid var(--border-light);border-radius:var(--r);padding:1.5rem;margin-bottom:2rem;text-align:left">
                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem">
                    <span style="color:var(--mist);font-size:0.85rem">Invoice</span>
                    <span style="font-weight:600">#<?php echo esc_html($pmpro_invoice->code); ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem">
                    <span style="color:var(--mist);font-size:0.85rem">Amount</span>
                    <span style="font-weight:600;color:var(--gold);font-size:1.1rem"><?php echo pmpro_formatPrice($pmpro_invoice->total); ?></span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--mist);font-size:0.85rem">Status</span>
                    <span class="mem-badge mem-badge-success"><?php echo esc_html(ucfirst($pmpro_invoice->status)); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
                <a class="mem-btn mem-btn-gold" href="<?php echo esc_url(home_url('/dashboard/')); ?>">Go to Dashboard</a>
                <a class="mem-btn mem-btn-outline" href="<?php echo esc_url(home_url('/membership-account/')); ?>">View Account</a>
            </div>
        </div>
    </div>
</section>

<?php get_footer('shop');
