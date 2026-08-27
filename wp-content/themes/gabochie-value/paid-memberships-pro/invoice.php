<?php
/**
 * PMPro Single Invoice page override.
 * @package Gabochie_Value
 */
global $pmpro_invoice;

get_header('shop');

if (empty($pmpro_invoice)) {
    echo '<div class="mem-container"><div class="mem-empty-state"><p>Invoice not found.</p></div></div>';
    get_footer('shop');
    return;
}

$level = pmpro_getLevel($pmpro_invoice->membership_id);
?>
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / <a href="<?php echo esc_url(home_url('/membership-account/')); ?>">Account</a> / <a href="<?php echo esc_url(pmpro_url('orders')); ?>">Orders</a> / Invoice</div>
        <h1>Invoice <em>#<?php echo esc_html($pmpro_invoice->code); ?></em></h1>
        <p>Payment details and receipt.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container" style="max-width:640px">
        <div class="pmpro_section">
            <!-- Invoice Header -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem;flex-wrap:wrap;gap:1rem">
                <div>
                    <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:0.25rem">Invoice #<?php echo esc_html($pmpro_invoice->code); ?></h2>
                    <p style="font-size:0.85rem;color:var(--mist)"><?php echo date_i18n(get_option('date_format'), $pmpro_invoice->timestamp); ?></p>
                </div>
                <span class="mem-badge mem-badge-<?php echo $pmpro_invoice->status === 'success' ? 'success' : 'warning'; ?>">
                    <?php echo esc_html(ucfirst($pmpro_invoice->status)); ?>
                </span>
            </div>

            <!-- Invoice Details -->
            <table style="width:100%;border-collapse:collapse;margin-bottom:1.5rem">
                <tr>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);color:var(--mist);font-size:0.85rem;width:40%">Membership Level</td>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);font-weight:500"><?php echo $level ? esc_html($level->name) : 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);color:var(--mist);font-size:0.85rem">Payment Date</td>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);font-weight:500"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $pmpro_invoice->timestamp); ?></td>
                </tr>
                <tr>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);color:var(--mist);font-size:0.85rem">Payment Method</td>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);font-weight:500"><?php echo esc_html(ucfirst($pmpro_invoice->gateway)); ?></td>
                </tr>
                <?php if (!empty($pmpro_invoice->subscription_transaction_id)) : ?>
                <tr>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);color:var(--mist);font-size:0.85rem">Transaction ID</td>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);font-weight:500;font-family:monospace;font-size:0.85rem"><?php echo esc_html($pmpro_invoice->subscription_transaction_id); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);color:var(--mist);font-size:0.85rem">Subtotal</td>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light)"><?php echo pmpro_formatPrice($pmpro_invoice->subtotal); ?></td>
                </tr>
                <?php if ($pmpro_invoice->tax > 0) : ?>
                <tr>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);color:var(--mist);font-size:0.85rem">Tax</td>
                    <td style="padding:0.75rem 0;border-bottom:1px solid var(--border-light)"><?php echo pmpro_formatPrice($pmpro_invoice->tax); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding:1rem 0;font-weight:700;font-size:1rem;color:var(--charcoal)">Total Paid</td>
                    <td style="padding:1rem 0;font-weight:700;font-size:1.2rem;color:var(--gold);font-family:'Playfair Display',serif"><?php echo pmpro_formatPrice($pmpro_invoice->total); ?></td>
                </tr>
            </table>

            <!-- Actions -->
            <div style="display:flex;gap:1rem;flex-wrap:wrap">
                <a class="mem-btn mem-btn-outline" href="<?php echo esc_url(pmpro_url('orders')); ?>">&larr; All Orders</a>
                <?php if (function_exists('pmpro_invoice_btcpay')) : ?>
                    <a class="mem-btn" href="<?php echo esc_url(pmpro_url('invoice', 'invoice_id=' . $pmpro_invoice->code . '&action=pdf')); ?>">Download PDF</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php get_footer('shop'); ?>
