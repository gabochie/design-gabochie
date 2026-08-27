<?php
/**
 * PMPro Orders page override.
 * @package Gabochie_Value
 */
get_header('shop');

$user = wp_get_current_user();
$invoices = pmpro_getInvoicesForUser($user->ID);
?>
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / <a href="<?php echo esc_url(home_url('/membership-account/')); ?>">Account</a> / Orders</div>
        <h1>My <em>Orders</em></h1>
        <p>View your payment history and download invoices.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container">
        <?php if (!empty($invoices)) : ?>
        <table class="pmpro_invoices_table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Level</th>
                    <th>Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice) :
                    $level = pmpro_getLevel($invoice->membership_id);
                ?>
                <tr>
                    <td><?php echo date_i18n(get_option('date_format'), $invoice->timestamp); ?></td>
                    <td><span class="mem-badge mem-badge-<?php echo $invoice->status === 'success' ? 'success' : 'warning'; ?>"><?php echo esc_html(ucfirst($invoice->status)); ?></span></td>
                    <td><?php echo $level ? esc_html($level->name) : 'Unknown'; ?></td>
                    <td><?php echo pmpro_formatPrice($invoice->total); ?></td>
                    <td><a href="<?php echo esc_url(pmpro_url('invoice', 'invoice_id=' . $invoice->code)); ?>">View Invoice</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($invoices) > 20) : ?>
            <div class="pmpro_pagination"><?php pmpro_pagination(); ?></div>
        <?php endif; ?>
        <?php else : ?>
        <div class="mem-empty-state">
            <p>No orders yet. <a href="<?php echo esc_url(home_url('/membership-levels/')); ?>">Browse membership levels</a> to get started.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php get_footer('shop');
