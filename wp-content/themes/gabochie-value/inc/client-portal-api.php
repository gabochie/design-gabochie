<?php
/**
 * Client Portal — REST API Endpoints
 * @package Gabochie_Value
 *
 * Routes: /wp-json/gabochie/v1/
 */

if (!defined('ABSPATH')) exit;

define('GABOCHIE_API_KEY', defined('GABOCHIE_AGENT_KEY') ? GABOCHIE_AGENT_KEY : 'gabochie-agent-key-change-me');

function gabochie_value_api_auth(WP_REST_Request $request) {
    $key = $request->get_header('X-Agent-Key');
    if (!$key || $key !== GABOCHIE_API_KEY) {
        return new WP_Error('forbidden', 'Invalid or missing X-Agent-Key', array('status' => 403));
    }
    return true;
}

function gabochie_value_ensure_user($email, $name = '') {
    $user = get_user_by('email', $email);
    if ($user) return $user->ID;

    $username = sanitize_user(current(explode('@', $email)), true);
    if (username_exists($username)) $username .= '_' . wp_rand(100, 999);
    $password = wp_generate_password();

    $uid = wp_insert_user(array(
        'user_login'   => $username,
        'user_email'   => $email,
        'display_name' => $name ?: $username,
        'user_pass'    => $password,
        'role'         => 'subscriber',
    ));

    if (is_wp_error($uid)) return $uid;

    if (function_exists('pmpro_changeMembershipLevel')) {
        global $wpdb;
        $free_level = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}pmpro_membership_levels WHERE name = 'Free Lead' LIMIT 1");
        if ($free_level) {
            pmpro_changeMembershipLevel($free_level, $uid);
        }
    }

    wp_mail($email, 'Your Gabochie Design Portal Account',
        "Welcome to Gabochie Design!\n\nYour portal account has been created.\nUsername: $username\nPassword: $password\n\nLogin at: " . home_url('/login/'));

    return $uid;
}

add_action('rest_api_init', function () {

    register_rest_route('gabochie/v1', '/register-client', array(
        'methods'             => 'POST',
        'callback'            => function (WP_REST_Request $r) {
            $auth = gabochie_value_api_auth($r);
            if (is_wp_error($auth)) return $auth;

            $email = sanitize_email($r->get_param('email'));
            if (!is_email($email)) return new WP_Error('bad_request', 'Valid email required', array('status' => 400));

            $name  = sanitize_text_field($r->get_param('name') ?: '');
            $phone = sanitize_text_field($r->get_param('phone') ?: '');
            $uid   = gabochie_value_ensure_user($email, $name);
            if (is_wp_error($uid)) return $uid;

            if ($phone) update_user_meta($uid, 'gabochie_phone', $phone);

            return array('success' => true, 'user_id' => $uid, 'email' => $email);
        },
        'permission_callback' => '__return_true',
    ));

    register_rest_route('gabochie/v1', '/projects', array(
        'methods'             => 'POST',
        'callback'            => function (WP_REST_Request $r) {
            $auth = gabochie_value_api_auth($r);
            if (is_wp_error($auth)) return $auth;

            $client_email = sanitize_email($r->get_param('client_email'));
            if (!is_email($client_email)) return new WP_Error('bad_request', 'Valid client_email required', array('status' => 400));
            $client = get_user_by('email', $client_email);
            if (!$client) return new WP_Error('not_found', 'Client not found. Register client first.', array('status' => 404));

            $title = sanitize_text_field($r->get_param('title') ?: 'Project');
            $pid = wp_insert_post(array(
                'post_type'   => 'client_project',
                'post_title'  => $title,
                'post_author' => $client->ID,
                'post_status' => 'publish',
            ));
            if (is_wp_error($pid)) return $pid;

            $fields = array('project_status', 'project_stage', 'project_budget', 'project_currency', 'project_location', 'service_type', 'client_phone', 'next_milestone', 'next_milestone_date');
            foreach ($fields as $f) {
                $val = $r->get_param($f);
                if ($val !== null) update_post_meta($pid, $f, sanitize_text_field((string) $val));
            }

            return array('success' => true, 'project_id' => $pid, 'edit_link' => get_edit_post_link($pid, 'raw'));
        },
        'permission_callback' => '__return_true',
    ));

    register_rest_route('gabochie/v1', '/projects/(?P<email>[^/]+)', array(
        'methods'             => 'GET',
        'callback'            => function (WP_REST_Request $r) {
            $auth = gabochie_value_api_auth($r);
            if (is_wp_error($auth)) return $auth;

            $client = get_user_by('email', sanitize_email($r->get_param('email')));
            if (!$client) return array('projects' => array());

            $projects = get_posts(array(
                'post_type'   => 'client_project',
                'author'      => $client->ID,
                'numberposts' => -1,
            ));

            $data = array();
            foreach ($projects as $p) {
                $data[] = array(
                    'id'         => $p->ID,
                    'title'      => $p->post_title,
                    'status'     => get_post_meta($p->ID, 'project_status', true),
                    'stage'      => get_post_meta($p->ID, 'project_stage', true),
                    'budget'     => get_post_meta($p->ID, 'project_budget', true),
                    'currency'   => get_post_meta($p->ID, 'project_currency', true),
                    'location'   => get_post_meta($p->ID, 'project_location', true),
                    'milestone'  => get_post_meta($p->ID, 'next_milestone', true),
                    'mdate'      => get_post_meta($p->ID, 'next_milestone_date', true),
                    'created'    => $p->post_date,
                );
            }
            return array('projects' => $data);
        },
        'permission_callback' => '__return_true',
    ));

    register_rest_route('gabochie/v1', '/projects/(?P<id>\d+)', array(
        'methods'             => 'PATCH',
        'callback'            => function (WP_REST_Request $r) {
            $auth = gabochie_value_api_auth($r);
            if (is_wp_error($auth)) return $auth;

            $pid = (int) $r->get_param('id');
            $post = get_post($pid);
            if (!$post || $post->post_type !== 'client_project') {
                return new WP_Error('not_found', 'Project not found', array('status' => 404));
            }

            if ($r->get_param('title')) {
                wp_update_post(array('ID' => $pid, 'post_title' => sanitize_text_field($r->get_param('title'))));
            }

            $fields = array('project_status', 'project_stage', 'project_budget', 'project_currency', 'project_location', 'service_type', 'client_phone', 'next_milestone', 'next_milestone_date');
            foreach ($fields as $f) {
                $val = $r->get_param($f);
                if ($val !== null) update_post_meta($pid, $f, sanitize_text_field((string) $val));
            }

            return array('success' => true, 'project_id' => $pid);
        },
        'permission_callback' => '__return_true',
    ));

    register_rest_route('gabochie/v1', '/messages', array(
        'methods'             => 'POST',
        'callback'            => function (WP_REST_Request $r) {
            $auth = gabochie_value_api_auth($r);
            if (is_wp_error($auth)) return $auth;

            $pid = (int) $r->get_param('project_id');
            $post = get_post($pid);
            if (!$post || $post->post_type !== 'client_project') {
                return new WP_Error('not_found', 'Project not found', array('status' => 404));
            }

            $content = wp_kses_post($r->get_param('content') ?: '');
            if (!$content) return new WP_Error('bad_request', 'Content required', array('status' => 400));

            $from_client = (bool) $r->get_param('is_from_client');
            $author_id   = $from_client ? (int) $post->post_author : 0;

            $mid = wp_insert_post(array(
                'post_type'    => 'project_message',
                'post_title'   => 'Message re: ' . $post->post_title,
                'post_content' => $content,
                'post_author'  => $author_id,
                'post_status'  => 'publish',
            ));
            if (is_wp_error($mid)) return $mid;

            update_post_meta($mid, 'project_id', $pid);
            update_post_meta($mid, 'is_from_client', $from_client);

            return array('success' => true, 'message_id' => $mid);
        },
        'permission_callback' => '__return_true',
    ));

    register_rest_route('gabochie/v1', '/messages/(?P<project_id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => function (WP_REST_Request $r) {
            $auth = gabochie_value_api_auth($r);
            if (is_wp_error($auth)) return $auth;

            $pid = (int) $r->get_param('project_id');
            $messages = get_posts(array(
                'post_type'      => 'project_message',
                'posts_per_page' => -1,
                'meta_key'       => 'project_id',
                'meta_value'     => $pid,
                'orderby'        => 'date',
                'order'          => 'ASC',
            ));

            $data = array();
            foreach ($messages as $m) {
                $data[] = array(
                    'id'            => $m->ID,
                    'content'       => $m->post_content,
                    'is_from_client' => (bool) get_post_meta($m->ID, 'is_from_client', true),
                    'created'       => $m->post_date,
                );
            }
            return array('messages' => $data);
        },
        'permission_callback' => '__return_true',
    ));
});
