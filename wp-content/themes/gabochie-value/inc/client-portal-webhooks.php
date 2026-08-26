<?php
/**
 * Client Portal — Webhooks & Action Hooks
 * @package Gabochie_Value
 */

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_nopriv_gabochie_capture_lead', 'gabochie_value_handle_lead_capture');
add_action('wp_ajax_gabochie_capture_lead', 'gabochie_value_handle_lead_capture');
function gabochie_value_handle_lead_capture() {
    check_ajax_referer('gabochie_lead_nonce', 'nonce');

    $email = sanitize_email($_POST['email'] ?? '');
    $name  = sanitize_text_field($_POST['name'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $service = sanitize_text_field($_POST['service'] ?? '');

    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Valid email required.'), 400);
    }

    $uid = gabochie_value_ensure_user($email, $name);
    if (is_wp_error($uid)) {
        wp_send_json_error(array('message' => $uid->get_error_message()), 500);
    }

    if ($phone) update_user_meta($uid, 'gabochie_phone', $phone);

    $pid = wp_insert_post(array(
        'post_type'   => 'client_project',
        'post_title'  => ($name ?: $email) . ' — ' . ($service ?: 'New Inquiry'),
        'post_author' => $uid,
        'post_status' => 'publish',
        'meta_input'  => array(
            'project_status' => 'inquiry',
            'project_stage'  => 'Discovery',
            'service_type'   => $service,
            'client_phone'   => $phone,
        ),
    ));

    gabochie_value_log_agent_action('lead_captured', array(
        'user_id'  => $uid,
        'email'    => $email,
        'name'     => $name,
        'service'  => $service,
        'project'  => $pid ?: 0,
    ));

    wp_send_json_success(array(
        'user_id'    => $uid,
        'project_id' => $pid ?: 0,
        'message'    => $pid ? 'Account created! Check your email for login credentials.' : 'Account created.',
    ));
}

function gabochie_value_log_agent_action($action, $data = array()) {
    $log = get_option('gabochie_agent_log', array());
    $log[] = array(
        'action' => $action,
        'data'   => $data,
        'time'   => current_time('mysql'),
    );
    if (count($log) > 500) $log = array_slice($log, -500);
    update_option('gabochie_agent_log', $log);
}

/* ---- Onboarding: create account + project in one call ---- */
add_action('wp_ajax_nopriv_gabochie_onb_create', 'gabochie_value_onboarding_create');
function gabochie_value_onboarding_create() {
    check_ajax_referer('gabochie_onb_nonce', 'nonce');

    $name  = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));

    if (!$name) {
        wp_send_json_success(array('success' => false, 'message' => 'Please enter your name.'));
        exit;
    }
    if (!is_email($email)) {
        wp_send_json_success(array('success' => false, 'message' => 'A valid email address is required.'));
        exit;
    }

    /* Existing user → log them in and redirect to dashboard */
    $existing = get_user_by('email', $email);
    if ($existing) {
        wp_set_current_user($existing->ID);
        wp_set_auth_cookie($existing->ID, true);
        wp_send_json_success(array(
            'message'      => 'Welcome back! Logging you in.',
            'dashboard_url' => esc_url(home_url('/dashboard/')),
        ));
        exit;
    }

    /* Create user */
    $uid = gabochie_value_ensure_user($email, $name);
    if (is_wp_error($uid)) {
        wp_send_json_success(array('success' => false, 'message' => $uid->get_error_message()));
        exit;
    }

    /* Log the user in immediately so they land on the dashboard */
    wp_set_current_user($uid);
    wp_set_auth_cookie($uid, true);

    if ($phone) {
        update_user_meta($uid, 'gabochie_phone', $phone);
    }

    /* Sanitize all onboarding fields */
    $goal             = sanitize_text_field(wp_unslash($_POST['goal'] ?? ''));
    $property_type    = sanitize_text_field(wp_unslash($_POST['property_type'] ?? ''));
    $land_status      = sanitize_text_field(wp_unslash($_POST['land_status'] ?? ''));
    $land_location    = sanitize_text_field(wp_unslash($_POST['land_location'] ?? ''));
    $development_scale = sanitize_text_field(wp_unslash($_POST['development_scale'] ?? ''));
    $budget_range     = sanitize_text_field(wp_unslash($_POST['budget_range'] ?? ''));
    $timeline         = sanitize_text_field(wp_unslash($_POST['timeline'] ?? ''));
    $location_based   = sanitize_text_field(wp_unslash($_POST['location_based'] ?? ''));
    $project_desc     = sanitize_textarea_field(wp_unslash($_POST['project_description'] ?? ''));

    /* Build a descriptive project title */
    $goal_labels = array(
        'build_home' => 'Residential Build', 'develop_income' => 'Income Development',
        'have_land' => 'Land Development', 'have_capital' => 'Capital Deployment',
        'unfinished' => 'Project Recovery', 'renovate' => 'Renovation',
        'commercial' => 'Commercial Development', 'attract_investors' => 'Investor-Ready Project',
        'invest' => 'Investment Opportunity', 'professional' => 'Professional Engagement',
        'organisation' => 'Organisation Project', 'notsure' => 'Development Consultation',
    );
    $goal_label = $goal_labels[$goal] ?? 'Development Inquiry';
    $loc_label  = $land_location ? ' (' . str_replace('-', ' ', $land_location) . ')' : '';
    $post_title = $name . ' — ' . $goal_label . $loc_label;

    $pid = wp_insert_post(array(
        'post_type'   => 'client_project',
        'post_title'  => $post_title,
        'post_author' => $uid,
        'post_status' => 'publish',
        'meta_input'  => array(
            'project_status'      => 'new',
            'project_stage'       => 'Discovery',
            'dev_goal'            => $goal,
            'property_type'       => $property_type,
            'land_status'         => $land_status,
            'land_location'       => $land_location,
            'development_scale'   => $development_scale,
            'budget_range'        => $budget_range,
            'timeline'            => $timeline,
            'location_based'      => $location_based,
            'project_description' => $project_desc,
            'client_phone'        => $phone,
        ),
    ));

    gabochie_value_log_agent_action('onboarding_complete', array(
        'user_id'  => $uid,
        'email'    => $email,
        'name'     => $name,
        'goal'     => $goal,
        'budget'   => $budget_range,
        'project'  => $pid ?: 0,
    ));

    /* Send welcome email */
    if ($pid) {
        $site_name = get_bloginfo('name');
        wp_mail($email, 'Your Development Opportunity — ' . $site_name,
            "Hi {$name},\n\n" .
            "Welcome to {$site_name}. Your Development Opportunity profile has been created.\n\n" .
            "Your project goal: {$goal_label}\n" .
            "Project ID: #{$pid}\n\n" .
            "Log in to view your project and recommended next steps:\n" .
            esc_url(home_url('/login/')) . "\n\n" .
            "— The " . $site_name . " Team",
            array('Content-Type: text/plain; charset=UTF-8')
        );
    }

    wp_send_json_success(array(
        'user_id'       => $uid,
        'project_id'    => $pid ?: 0,
        'message'       => 'Account and project created successfully.',
        'dashboard_url' => esc_url(home_url('/dashboard/')),
    ));
}

add_action('wp_ajax_gabochie_agent_update', 'gabochie_value_ajax_agent_update');
function gabochie_value_ajax_agent_update() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $action = sanitize_text_field($_POST['agent_action'] ?? '');
    $pid    = (int) ($_POST['project_id'] ?? 0);
    $value  = sanitize_text_field($_POST['value'] ?? '');

    if (!$pid || !$action) {
        wp_send_json_error(array('message' => 'project_id and agent_action required'), 400);
    }

    switch ($action) {
        case 'update_status':
            update_post_meta($pid, 'project_status', $value);
            break;
        case 'update_stage':
            update_post_meta($pid, 'project_stage', $value);
            break;
        case 'set_milestone':
            update_post_meta($pid, 'next_milestone', $value);
            if (!empty($_POST['milestone_date'])) {
                update_post_meta($pid, 'next_milestone_date', sanitize_text_field($_POST['milestone_date']));
            }
            break;
        case 'add_note':
            $note = wp_kses_post($_POST['note'] ?? '');
            if ($note) {
                $mid = wp_insert_post(array(
                    'post_type'    => 'project_message',
                    'post_title'   => 'System Note — ' . get_the_title($pid),
                    'post_content' => $note,
                    'post_status'  => 'publish',
                ));
                if ($mid) {
                    update_post_meta($mid, 'project_id', $pid);
                    update_post_meta($mid, 'is_from_client', false);
                }
            }
            break;
        default:
            wp_send_json_error(array('message' => 'Unknown action'), 400);
    }

    gabochie_value_log_agent_action($action, array('project_id' => $pid, 'value' => $value));
    wp_send_json_success(array('message' => 'OK'));
}

add_action('rest_api_init', function () {
    register_rest_route('gabochie/v1', '/agent-log', array(
        'methods'             => 'GET',
        'callback'            => function () {
            if (!current_user_can('manage_options')) {
                return new WP_Error('forbidden', 'Admins only', array('status' => 403));
            }
            return array('log' => get_option('gabochie_agent_log', array()));
        },
        'permission_callback' => '__return_true',
    ));
});

add_action('wp_enqueue_scripts', function () {
    if (!is_page_template('page-templates/template-login.php')) return;
    wp_add_inline_script('gabochie-value-main', '
document.addEventListener("DOMContentLoaded", function() {
    var form = document.querySelector("section[style*=\'max-width:1100px\'] form[action*=\'/contact/\']");
    if (!form) return;
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        var data = new FormData(form);
        data.append("action", "gabochie_capture_lead");
        data.append("nonce", "' . wp_create_nonce('gabochie_lead_nonce') . '");
        var btn = form.querySelector("button[type=submit]");
        btn.textContent = "Creating your portal...";
        btn.disabled = true;
        fetch("' . admin_url('admin-ajax.php') . '", { method:"POST", body:data })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.success) {
                    form.innerHTML = "<div style=\'text-align:center;padding:2rem 0\'><div style=\'font-size:2rem;margin-bottom:1rem\'>✅</div><h3 style=\'margin-bottom:0.5rem\'>You\'re All Set!</h3><p style=\'color:var(--mist);font-size:0.9rem\'>" + res.data.message + "</p></div>";
                } else {
                    btn.textContent = "Get Your Free Consultation";
                    btn.disabled = false;
                    alert(res.data.message || "Something went wrong. Please try again.");
                }
            })
            .catch(function(){ btn.textContent = "Get Your Free Consultation"; btn.disabled = false; });
    });
});
    ');
});
