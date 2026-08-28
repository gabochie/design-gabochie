<?php
/**
 * Client Portal — Chat Assistant (User-Aware)
 * @package Gabochie_Value
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('gabochie/v1', '/chat', array(
        'methods'             => 'POST',
        'callback'            => 'gabochie_value_chat_handler',
        'permission_callback' => '__return_true',
    ));
});

function gabochie_value_chat_handler(WP_REST_Request $request) {
    $message = sanitize_text_field($request->get_param('message') ?: '');
    if (!$message) {
        return new WP_Error('bad_request', 'Message is required', array('status' => 400));
    }

    $context = gabochie_value_get_user_context();
    $response = gabochie_value_chat_build_response($message, $context);

    return array(
        'success'  => true,
        'response' => $response,
        'context'  => $context,
    );
}

function gabochie_value_get_user_context() {
    $context = array(
        'logged_in' => false,
        'name'      => '',
        'projects'  => array(),
    );

    if (!is_user_logged_in()) return $context;

    $user = wp_get_current_user();
    $context['logged_in'] = true;
    $context['name']      = $user->display_name ?: $user->user_login;

    $projects = get_posts(array(
        'post_type'   => 'client_project',
        'author'      => $user->ID,
        'numberposts' => 5,
    ));

    foreach ($projects as $p) {
        $context['projects'][] = array(
            'id'     => $p->ID,
            'title'  => $p->post_title,
            'status' => get_post_meta($p->ID, 'project_status', true),
            'stage'  => get_post_meta($p->ID, 'project_stage', true),
        );
    }

    return $context;
}

function gabochie_value_chat_build_response($message, $context) {
    $msg = strtolower(trim($message));

    if (preg_match('/\b(hi|hello|hey|howdy)\b/', $msg)) {
        if ($context['logged_in']) {
            return "Hello " . $context['name'] . "! How can I help with your project today?";
        }
        return "Hello! Welcome to Gabochie Design. I can help you learn about our services, pricing, or start a new project. What would you like to know?";
    }

    if ($context['logged_in'] && preg_match('/\b(project|status|progress|stage)\b/', $msg)) {
        if (!empty($context['projects'])) {
            $lines = array();
            foreach ($context['projects'] as $proj) {
                $lines[] = "- " . $proj['title'] . " — Status: " . $proj['status'] . ", Stage: " . $proj['stage'];
            }
            return "Here are your projects:\n" . implode("\n", $lines) . "\n\nReply with a project name for more details, or ask me to update something.";
        }
        return "You don't have any active projects yet. Would you like to start one? I can connect you with our team!";
    }

    if (preg_match('/\b(pricing|price|cost|budget|how much)\b/', $msg)) {
        return "Pricing is honest and simple, and it starts free:\n\n- Free — start with the readiness quiz and a build-cost estimate.\n- Active Client — ₵399/month.\n- Premium Service — ₵1,299/month.\n\nSee what each plan includes and upgrade at " . home_url('/membership-levels/') . " — or tell me what you're building and I'll point you to the right start!";
    }

    if (preg_match('/\b(service|design|interior|build)\b/', $msg)) {
        return "Everything you need sits under one roof: start with the five-question wizard so we understand your project, run the build-cost calculator to know what it should cost, and use the dashboard to keep every step documented. Where would you like to dive in?";
    }

    if (preg_match('/\b(human|talk|speak|call|contact|real person)\b/', $msg)) {
        return "No problem! You can reach our team through the contact form at " . home_url('/contact/') . " or call us directly at +233 26 824 8970. We typically respond within one working day.";
    }

    if ($context['logged_in'] && preg_match('/\b(dashboard|account|my )\b/', $msg)) {
        return "You can view your full dashboard at " . home_url('/dashboard/') . ". From there you can track progress, send messages, and manage your project.";
    }

    if (!$context['logged_in'] && preg_match('/\b(start|begin|project|build|help me)\b/', $msg)) {
        return "I'd love to help you start your project! Please sign up at " . home_url('/login/') . " or tell me your email and I'll get you connected with our team.";
    }

    return "Thanks for reaching out! I can help with project status, pricing, services, or connect you with our team. What would you like to know?";
}
