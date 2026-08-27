<?php
/**
 * PMPro Cancel page override.
 * @package Gabochie_Value
 */
global $pmpro_msg, $pmpro_msgt;

get_header('shop');

$user = wp_get_current_user();
$level = pmpro_getMembershipLevelForUser($user->ID);
?>
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / <a href="<?php echo esc_url(home_url('/membership-account/')); ?>">Account</a> / Cancel</div>
        <h1>Cancel <em>Membership</em></h1>
        <p>We're sorry to see you go.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container" style="max-width:640px">
        <?php if (!empty($pmpro_msg)) : ?>
            <div class="mem-alert mem-alert-<?php echo $pmpro_msgt === 'error' ? 'error' : 'success'; ?>">
                <?php echo wp_kses_post($pmpro_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($_REQUEST['confirm'])) : ?>
            <!-- Step 1: Confirmation -->
            <div class="pmpro_section" style="text-align:center">
                <?php if ($level) : ?>
                    <div class="mem-tier-badge mem-tier-<?php echo esc_attr($level->id === 1 ? 'free' : ($level->id === 2 ? 'active' : 'premium')); ?>" style="display:inline-block;margin-bottom:1.5rem">
                        <?php echo esc_html($level->name); ?>
                    </div>
                <?php endif; ?>

                <h2 style="font-family:'Playfair Display',serif;font-size:1.6rem;margin-bottom:1rem">Are you sure you want to cancel?</h2>

                <div style="background:var(--gold-pale);border:1px dashed var(--gold);border-radius:var(--r);padding:1.5rem;margin-bottom:2rem;text-align:left">
                    <h4 style="font-size:0.85rem;font-weight:600;color:var(--charcoal);margin-bottom:0.75rem">When you cancel:</h4>
                    <ul style="list-style:none;padding:0;margin:0">
                        <li style="padding:0.4rem 0;font-size:0.9rem;color:var(--mist);display:flex;align-items:center;gap:0.5rem">
                            <span style="color:#C0392B;font-weight:bold">&times;</span> Your membership will remain active until the end of your billing period
                        </li>
                        <li style="padding:0.4rem 0;font-size:0.9rem;color:var(--mist);display:flex;align-items:center;gap:0.5rem">
                            <span style="color:#C0392B;font-weight:bold">&times;</span> Access to premium features will be removed
                        </li>
                        <li style="padding:0.4rem 0;font-size:0.9rem;color:var(--mist);display:flex;align-items:center;gap:0.5rem">
                            <span style="color:#C0392B;font-weight:bold">&times;</span> Your project data will be preserved for 90 days
                        </li>
                    </ul>
                </div>

                <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
                    <a class="mem-btn mem-btn-outline" href="<?php echo esc_url(home_url('/membership-account/')); ?>">Keep My Membership</a>
                    <form method="post" action="<?php echo esc_url(pmpro_url('cancel', 'pmpro-level=' . ($level ? $level->id : ''))); ?>" style="display:inline">
                        <input type="hidden" name="pmpro_cancel_page" value="1">
                        <button type="submit" class="mem-btn" style="background:#C0392B;border-color:#C0392B">Yes, Cancel Membership</button>
                    </form>
                </div>
            </div>
        <?php else : ?>
            <!-- Step 2: Processed -->
            <div class="pmpro_section" style="text-align:center">
                <div style="width:80px;height:80px;border-radius:50%;background:var(--teal-light);border:2px solid var(--teal);display:flex;align-items:center;justify-content:center;margin:0 auto 2rem;color:var(--teal);font-size:2rem">&#10003;</div>
                <h2 style="font-family:'Playfair Display',serif;font-size:1.6rem;margin-bottom:1rem">Membership Cancelled</h2>
                <p style="color:var(--mist);font-size:1rem;line-height:1.8;margin-bottom:2rem">
                    Your membership has been cancelled. You will retain access until the end of your current billing period.
                </p>
                <a class="mem-btn mem-btn-gold" href="<?php echo esc_url(home_url('/dashboard/')); ?>">Return to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php get_footer('shop'); ?>
