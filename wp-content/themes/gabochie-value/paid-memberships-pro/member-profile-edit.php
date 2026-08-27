<?php
/**
 * PMPro Member Profile Edit page override.
 * @package Gabochie_Value
 */
global $pmpro_msg, $pmpro_msgt;

get_header('shop');

$user = wp_get_current_user();

// Process form submission
if (isset($_POST['pmpro_member_profile_submit']) && wp_verify_nonce($_POST['pmpro_member_profile_nonce'], 'pmpro_member_profile')) {
    $user_id = $user->ID;
    $display_name = sanitize_text_field($_POST['display_name']);
    $user_email = sanitize_email($_POST['user_email']);

    $updates = array(
        'display_name' => $display_name,
        'user_email'   => $user_email,
    );

    // Password change (optional)
    $new_pass = $_POST['user_pass'] ?? '';
    $confirm_pass = $_POST['user_pass_confirm'] ?? '';
    if (!empty($new_pass)) {
        if ($new_pass !== $confirm_pass) {
            $pmpro_msg = 'Passwords do not match.';
            $pmpro_msgt = 'error';
        } elseif (strlen($new_pass) < 8) {
            $pmpro_msg = 'Password must be at least 8 characters.';
            $pmpro_msgt = 'error';
        } else {
            $updates['user_pass'] = $new_pass;
        }
    }

    if (empty($pmpro_msg)) {
        wp_update_user($updates);
        $pmpro_msg = 'Profile updated successfully.';
        $pmpro_msgt = 'success';
        $user = wp_get_current_user(); // refresh
    }
}
?>
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / <a href="<?php echo esc_url(home_url('/membership-account/')); ?>">Account</a> / Edit Profile</div>
        <h1>Edit <em>Profile</em></h1>
        <p>Update your account details and password.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container" style="max-width:640px">
        <?php if (!empty($pmpro_msg)) : ?>
            <div class="mem-alert mem-alert-<?php echo $pmpro_msgt === 'error' ? 'error' : 'success'; ?>">
                <?php echo wp_kses_post($pmpro_msg); ?>
            </div>
        <?php endif; ?>

        <div class="pmpro_section">
            <form method="post" action="">
                <input type="hidden" name="pmpro_member_profile_submit" value="1">
                <input type="hidden" name="pmpro_member_profile_nonce" value="<?php echo wp_create_nonce('pmpro_member_profile'); ?>">

                <h3 style="font-family:'Playfair Display',serif;font-size:1.2rem;margin-bottom:1.5rem">Account Details</h3>

                <div class="mem-form-group">
                    <label class="mem-form-label" for="display_name">Display Name</label>
                    <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" class="mem-form-input" required>
                </div>

                <div class="mem-form-group">
                    <label class="mem-form-label" for="user_email">Email Address</label>
                    <input type="email" id="user_email" name="user_email" value="<?php echo esc_attr($user->user_email); ?>" class="mem-form-input" required>
                </div>

                <div class="mem-form-group">
                    <label class="mem-form-label" for="user_login">Username</label>
                    <input type="text" id="user_login" value="<?php echo esc_attr($user->user_login); ?>" class="mem-form-input" disabled style="opacity:0.6">
                    <span style="font-size:0.78rem;color:var(--mist)">Username cannot be changed.</span>
                </div>

                <hr style="border:none;border-top:1px solid var(--border-light);margin:2rem 0">

                <h3 style="font-family:'Playfair Display',serif;font-size:1.2rem;margin-bottom:1.5rem">Change Password</h3>
                <p style="font-size:0.85rem;color:var(--mist);margin-bottom:1.5rem">Leave blank to keep your current password.</p>

                <div class="mem-form-group">
                    <label class="mem-form-label" for="user_pass">New Password</label>
                    <input type="password" id="user_pass" name="user_pass" class="mem-form-input" autocomplete="new-password" minlength="8">
                </div>

                <div class="mem-form-group">
                    <label class="mem-form-label" for="user_pass_confirm">Confirm New Password</label>
                    <input type="password" id="user_pass_confirm" name="user_pass_confirm" class="mem-form-input" autocomplete="new-password" minlength="8">
                </div>

                <button type="submit" class="mem-btn" style="width:100%;margin-top:1rem">Save Changes</button>
            </form>
        </div>

        <div style="text-align:center;margin-top:1.5rem">
            <a href="<?php echo esc_url(home_url('/membership-account/')); ?>" style="font-size:0.85rem;color:var(--teal);text-decoration:underline">&larr; Back to Account</a>
        </div>
    </div>
</section>

<?php get_footer('shop'); ?>
