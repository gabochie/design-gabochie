<?php
/**
 * PMPro Billing page override.
 * @package Gabochie_Value
 */
get_header('shop');
?>
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / <a href="<?php echo esc_url(home_url('/membership-account/')); ?>">Account</a> / Billing</div>
        <h1>Billing <em>Information</em></h1>
        <p>Update your payment method and billing address.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container" style="max-width:640px">
        <?php if (isset($_GET['update']) && $_GET['update'] === 'success') : ?>
            <div class="mem-alert mem-alert-success">Billing information updated successfully.</div>
        <?php endif; ?>

        <div class="pmpro_section">
            <?php
            $gateway = pmpro_getGateway();
            if ($gateway === 'stripe' || $gateway === 'braintree') :
                // Use PMPro's built-in billing update form for card-based gateways
                pmpro_show_billing_form();
            else :
                // For non-card gateways (like Moolre/MoMo), show address-only form
            ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="pmpro_update_billing">
                <h3>Billing Address</h3>
                <div class="mem-form-group">
                    <label class="mem-form-label">Full Name</label>
                    <input type="text" name="bname" value="<?php echo esc_attr(pmpro_getOption('bfirstname') . ' ' . pmpro_getOption('blastname')); ?>" class="mem-form-input">
                </div>
                <div class="mem-form-group">
                    <label class="mem-form-label">Address Line 1</label>
                    <input type="text" name="baddress1" value="<?php echo esc_attr(pmpro_getOption('baddress1')); ?>" class="mem-form-input">
                </div>
                <div class="mem-form-group">
                    <label class="mem-form-label">City</label>
                    <input type="text" name="bcity" value="<?php echo esc_attr(pmpro_getOption('bcity')); ?>" class="mem-form-input">
                </div>
                <div class="mem-form-group">
                    <label class="mem-form-label">Country</label>
                    <?php
                    $countries = pmpro_getCountries();
                    echo '<select name="bcountry" class="mem-form-input">';
                    foreach ($countries as $code => $name) {
                        $selected = $code === pmpro_getOption('bcountry') ? ' selected' : '';
                        echo '<option value="' . esc_attr($code) . '"' . $selected . '>' . esc_html($name) . '</option>';
                    }
                    echo '</select>';
                    ?>
                </div>
                <div class="mem-form-group">
                    <label class="mem-form-label">Phone</label>
                    <input type="tel" name="bphone" value="<?php echo esc_attr(pmpro_getOption('bphone')); ?>" class="mem-form-input">
                </div>
                <button type="submit" class="mem-btn" style="width:100%">Update Billing</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php get_footer('shop');
