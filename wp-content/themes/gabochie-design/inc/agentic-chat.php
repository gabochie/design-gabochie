<?php
/**
 * Agentic — Chat Assistant (User-Aware)
 * @package Gabochie_Design
 */

if (!defined('ABSPATH')) exit;

/* ============================================
   REST Endpoint: Chat with AI Assistant
   Returns context-rich response based on user
   ============================================ */
add_action('rest_api_init', function () {
    register_rest_route('gabochie/v1', '/chat', array(
        'methods'             => 'POST',
        'callback'            => 'gabochie_chat_handler',
        'permission_callback' => '__return_true',
    ));
});

function gabochie_chat_handler(WP_REST_Request $request) {
    $message = sanitize_text_field($request->get_param('message') ?: '');
    if (!$message) {
        return new WP_Error('bad_request', 'Message is required', array('status' => 400));
    }

    // Gather context from logged-in user
    $context = gabochie_get_user_context();

    // Basic response builder (replace with AI API later)
    $response = gabochie_chat_build_response($message, $context);

    return array(
        'success'  => true,
        'response' => $response,
        'context'  => $context,
    );
}

/* ============================================
   BUILD USER CONTEXT
   ============================================ */
function gabochie_get_user_context() {
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

/* ============================================
   RESPONSE BUILDER (intent-based, swappable)
   ============================================ */
function gabochie_chat_build_response($message, $context) {
    $msg = strtolower(trim($message));

    // Greeting
    if (preg_match('/\b(hi|hello|hey|howdy)\b/', $msg)) {
        if ($context['logged_in']) {
            return "Hello " . $context['name'] . "! 👋 How can I help with your project today?";
        }
        return "Hello! 👋 Welcome to Gabochie Design. I can help you learn about our services, pricing, or start a new project. What would you like to know?";
    }

    // Project status
    if ($context['logged_in'] && preg_match('/\b(project|status|progress|stage)\b/', $msg)) {
        if (!empty($context['projects'])) {
            $lines = array();
            foreach ($context['projects'] as $proj) {
                $lines[] = "• " . $proj['title'] . " — Status: " . $proj['status'] . ", Stage: " . $proj['stage'];
            }
            return "Here are your projects:\n" . implode("\n", $lines) . "\n\nReply with a project name for more details, or ask me to update something.";
        }
        return "You don't have any active projects yet. Would you like to start one? I can connect you with our team!";
    }

    // Pricing
    if (preg_match('/\b(pricing|price|cost|budget|how much)\b/', $msg)) {
        return "Our packages start at \$800 for the Concept plan (AI floor plans + renders), \$2,500 for Full Design, and \$5,000 for Complete Service. Check our full pricing at " . home_url('/pricing/') . " or tell me what you're looking for!";
    }

    // Services
    if (preg_match('/\b(service|architect|interior|design)\b/', $msg)) {
        return "We offer: Architectural Design, Interior Design, 3D Visualization, Project Management, AI Cost Calculator, and Diaspora Support. Which would you like to explore?";
    }

    // Contact / human
    if (preg_match('/\b(human|talk|speak|call|contact|real person)\b/', $msg)) {
        return "No problem! You can reach our team directly:\n• Email: admin@design.gabochie.com\n• Phone: +233 26 824 8970\n• WhatsApp: https://wa.me/233268248970\n\nOr use the contact form at " . home_url('/contact/');
    }

    // Dashboard / account
    if ($context['logged_in'] && preg_match('/\b(dashboard|account|my )\b/', $msg)) {
        return "You can view your full dashboard at " . home_url('/dashboard/') . ". From there you can track progress, send messages, and manage your project.";
    }

    // Lead capture for non-logged-in
    if (!$context['logged_in'] && preg_match('/\b(start|begin|project|build|help me)\b/', $msg)) {
        return "I'd love to help you start your project! Please sign up at " . home_url('/login/') . " or tell me your email and I'll get you connected with our team.";
    }

    // Default fallback
    return "Thanks for reaching out! I can help with project status, pricing, services, or connect you with our team. What would you like to know?";
}
