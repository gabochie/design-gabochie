<?php
/**
 * Client Portal — Registration & Login
 * @package Gabochie_Value
 */

if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['gabochie_register'])) return;

    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'gabochie_register')) {
        wp_redirect(add_query_arg('register', 'nonce', wp_get_referer() ?: home_url('/login/')));
        exit;
    }

    $email = sanitize_email($_POST['reg_email'] ?? '');
    $name  = sanitize_text_field($_POST['reg_name'] ?? '');
    $pass  = $_POST['reg_password'] ?? '';
    $errors = array();

    if (!is_email($email)) $errors[] = 'valid_email';
    if (email_exists($email)) $errors[] = 'email_taken';
    if (strlen($pass) < 8) $errors[] = 'short_password';
    if (empty($name)) $errors[] = 'name_required';

    if (!empty($errors)) {
        wp_redirect(add_query_arg('register', implode(',', $errors), wp_get_referer() ?: home_url('/login/')));
        exit;
    }

    $username = sanitize_user(current(explode('@', $email)), true);
    if (username_exists($username)) $username .= '_' . wp_rand(100, 999);

    $uid = wp_insert_user(array(
        'user_login'   => $username,
        'user_email'   => $email,
        'display_name' => $name,
        'first_name'   => $name,
        'user_pass'    => $pass,
        'role'         => 'subscriber',
    ));

    if (is_wp_error($uid)) {
        wp_redirect(add_query_arg('register', 'error', wp_get_referer() ?: home_url('/login/')));
        exit;
    }

    if (function_exists('pmpro_changeMembershipLevel')) {
        global $wpdb;
        $free = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}pmpro_membership_levels WHERE name = 'Free Lead' LIMIT 1");
        if ($free) pmpro_changeMembershipLevel((int) $free, $uid);
    }

	// Route the new subscriber into the lead hub (FluentCRM pipeline).
	do_action(
		'gabochie/lead/captured',
		$email,
		array(
			'list'   => 'register',
			'source' => 'register-form',
			'name'   => $name,
		)
	);

    $subject = 'Welcome to Gabochie Design';
    $message = "Hi {$name},\n\nYour account has been created.\n\nLogin: " . home_url('/login/') . "\nEmail: {$email}\n\nYou now have access to your project dashboard and construction tools.\n\n– Gabochie Design Team";
    wp_mail($email, $subject, $message);

    wp_set_current_user($uid);
    wp_set_auth_cookie($uid);
    wp_redirect(home_url('/dashboard/'));
    exit;
});

add_shortcode('gabochie_register_form', function () {
    if (is_user_logged_in()) return '<p>You are already logged in. <a href="' . esc_url(home_url('/dashboard/')) . '">Go to Dashboard</a></p>';

    $status = isset($_GET['register']) ? sanitize_text_field($_GET['register']) : '';
    $errors = array(
        'valid_email'    => 'Please enter a valid email address.',
        'email_taken'    => 'An account with this email already exists. <a href="' . esc_url(home_url('/login/')) . '">Log in instead</a>.',
        'short_password' => 'Password must be at least 8 characters.',
        'name_required'  => 'Please enter your name.',
        'nonce'          => 'Session expired. Please try again.',
        'error'          => 'Something went wrong. Please try again.',
    );
    $error_msg = '';
    if ($status && $status !== 'success') {
        $codes = explode(',', $status);
        foreach ($codes as $c) {
            if (isset($errors[$c])) { $error_msg = $errors[$c]; break; }
        }
    }

    ob_start();
    ?>
    <form method="post" action="" style="display:flex;flex-direction:column;gap:1rem">
        <?php wp_nonce_field('gabochie_register'); ?>
        <input type="hidden" name="gabochie_register" value="1">

        <?php if ($error_msg) : ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:0.75rem 1rem;border-radius:6px;font-size:0.85rem"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div>
            <label style="display:block;font-size:0.75rem;text-transform:uppercase;color:var(--charcoal);margin-bottom:0.4rem;font-weight:500">Full Name</label>
            <input type="text" name="reg_name" placeholder="Your name" required style="width:100%;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:6px;font-family:'Jost',sans-serif;font-size:0.95rem;background:var(--ivory)">
        </div>

        <div>
            <label style="display:block;font-size:0.75rem;text-transform:uppercase;color:var(--charcoal);margin-bottom:0.4rem;font-weight:500">Email Address</label>
            <input type="email" name="reg_email" placeholder="you@example.com" required style="width:100%;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:6px;font-family:'Jost',sans-serif;font-size:0.95rem;background:var(--ivory)">
        </div>

        <div>
            <label style="display:block;font-size:0.75rem;text-transform:uppercase;color:var(--charcoal);margin-bottom:0.4rem;font-weight:500">Password</label>
            <input type="password" name="reg_password" placeholder="Min. 8 characters" required minlength="8" style="width:100%;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:6px;font-family:'Jost',sans-serif;font-size:0.95rem;background:var(--ivory)">
        </div>

        <button type="submit" style="width:100%;padding:0.9rem 2rem;background:var(--gold);color:var(--teal-dark);border:none;border-radius:6px;font-family:'Jost',sans-serif;font-size:0.85rem;font-weight:600;cursor:pointer">Create Free Account</button>

        <p style="font-size:0.8rem;color:var(--mist);text-align:center;margin:0">Already have an account? <a href="<?php echo esc_url(home_url('/login/')); ?>" style="color:var(--teal);text-decoration:underline">Log in</a></p>
    </form>
    <?php
    return ob_get_clean();
});
