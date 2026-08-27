<?php
/**
 * PMPro Account page override.
 * @package Gabochie_Value
 */
get_header('shop');

$user = wp_get_current_user();
$level = pmpro_getMembershipLevelForUser($user->ID);
$invoices = pmpro_getInvoicesForUser($user->ID, 5);
?>
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / <a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Dashboard</a> / Account</div>
        <h1>My <em>Account</em></h1>
        <p>Manage your membership, billing, and account details.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container">
        <div class="pmpro_account_wrap">
            <!-- Membership Info -->
            <div class="pmpro_section">
                <h3>Membership</h3>
                <?php if ($level) : ?>
                    <div class="pmpro_level_info">
                        <div class="mem-tier-badge mem-tier-<?php echo esc_attr($level->id === 1 ? 'free' : ($level->id === 2 ? 'active' : 'premium')); ?>">
                            <?php echo esc_html($level->name); ?>
                        </div>
                        <p>Status: <strong><?php echo esc_html(ucfirst(pmpro_getMembershipLevelStatusForUser($user->ID))); ?></strong></p>
                        <?php if ($level->initial_payment > 0) : ?>
                            <p>Fee: <?php echo pmpro_formatPrice($level->initial_payment); ?> (one-time)</p>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <p>You don't have an active membership.</p>
                    <a class="mem-btn mem-btn-gold" href="<?php echo esc_url(home_url('/membership-levels/')); ?>">View Membership Levels</a>
                <?php endif; ?>
            </div>

            <!-- Profile -->
            <div class="pmpro_section">
                <h3>Profile</h3>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="pmpro_profile_updates">
                    <div class="mem-form-group">
                        <label class="mem-form-label">Name</label>
                        <input type="text" name="name" value="<?php echo esc_attr($user->display_name); ?>" class="mem-form-input">
                    </div>
                    <div class="mem-form-group">
                        <label class="mem-form-label">Email</label>
                        <input type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" class="mem-form-input">
                    </div>
                    <button type="submit" class="mem-btn">Update Profile</button>
                </form>
            </div>

            <!-- Recent Invoices -->
            <?php if (!empty($invoices)) : ?>
            <div class="pmpro_section">
                <h3>Recent Orders</h3>
                <table class="pmpro_invoices_table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice) : ?>
                        <tr>
                            <td><?php echo date_i18n(get_option('date_format'), $invoice->timestamp); ?></td>
                            <td><span class="mem-badge mem-badge-<?php echo $invoice->status === 'success' ? 'success' : 'warning'; ?>"><?php echo esc_html(ucfirst($invoice->status)); ?></span></td>
                            <td><?php echo pmpro_formatPrice($invoice->total); ?></td>
                            <td><a href="<?php echo esc_url(pmpro_url('invoice', 'invoice_id=' . $invoice->code)); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="<?php echo esc_url(pmpro_url('orders')); ?>" class="mem-link">View All Orders</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php get_footer('shop');
