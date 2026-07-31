<?php
defined('ABSPATH') || exit;

function gs_team_members($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $members = get_user_meta($user_id, '_gs_team_members', true);
    return is_array($members) ? $members : [];
}

function gs_invite_team_member($email, $role = 'member', $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $email = sanitize_email($email);
    if (!is_email($email)) return new WP_Error('invalid_email', 'Invalid email address.');

    $existing_user = get_user_by('email', $email);
    $inviter_tier = get_user_meta($user_id, '_gs_tier', true);

    if (!$existing_user) {
        $email_prefix = strstr($email, '@', true);
        $username = sanitize_user($email_prefix, true);
        if (username_exists($username)) {
            $username = $username . '_' . wp_rand(100, 999);
        }
        $password = wp_generate_password();
        $new_user_id = wp_insert_user(array(
            'user_login'    => $username,
            'user_email'    => $email,
            'display_name'  => $email_prefix,
            'role'          => 'subscriber',
            'user_pass'     => $password,
        ));
        if (is_wp_error($new_user_id)) return $new_user_id;

        if (!empty($inviter_tier)) {
            update_user_meta($new_user_id, '_gs_tier', $inviter_tier);
        }
        update_user_meta($new_user_id, '_gs_team_of', $user_id);

        $login_url = wp_login_url();
        $subject = 'You\'ve been invited to Gabochie SaaS';
        $message = "Hello $email_prefix,\n\n";
        $message .= "You have been invited to join Gabochie SaaS.\n\n";
        $message .= "Your login details:\n";
        $message .= "Username: $username\n";
        $message .= "Password: $password\n\n";
        $message .= "Login here: $login_url\n\n";
        $message .= "After logging in, you can set your own password from your profile.\n\n";
        $message .= "Welcome aboard!\n";
        $message .= "— The Gabochie Team";

        wp_mail($email, $subject, $message);
    } else {
        $new_user_id = $existing_user->ID;
        if (!empty($inviter_tier)) {
            update_user_meta($new_user_id, '_gs_tier', $inviter_tier);
        }
        update_user_meta($new_user_id, '_gs_team_of', $user_id);
    }

    $members = get_user_meta($user_id, '_gs_team_members', true) ?: array();
    $members[] = array(
        'user_id' => $new_user_id,
        'email'   => $email,
        'role'    => $role,
        'status'  => 'active',
        'invited' => current_time('mysql'),
    );
    update_user_meta($user_id, '_gs_team_members', $members);

    return true;
}

function gs_remove_team_member($email, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $members = get_user_meta($user_id, '_gs_team_members', true) ?: array();
    $members = array_filter($members, function($m) use ($email) { return $m['email'] !== $email; });
    update_user_meta($user_id, '_gs_team_members', array_values($members));

    $existing_user = get_user_by('email', $email);
    if ($existing_user) {
        delete_user_meta($existing_user->ID, '_gs_team_of');
    }
}
