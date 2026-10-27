<?php
/**
 * AJAX Handlers — AI chat, cost calculator, contact form, rate limiting
 * @package Gabochie_Design
 */
if (!defined('ABSPATH')) exit;

/* ============================================
   RATE LIMITING
   ============================================ */
function gabochie_check_rate_limit($action, $max_per_minute = 10) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'gabochie_ratelimit_' . $action . '_' . md5($ip);
    $transients = get_transient($key);
    $now = time();

    if ($transients === false) {
        $transients = array();
    }

    $transients = array_filter($transients, function($t) use ($now) {
        return ($now - $t) < 60;
    });

    if (count($transients) >= $max_per_minute) {
        wp_send_json_error(array('message' => 'Too many requests. Please try again in a minute.'));
        return false;
    }

    $transients[] = $now;
    set_transient($key, $transients, 120);
    return true;
}

/* ============================================
   AI AGENT AJAX HANDLERS
   ============================================ */
function gabochie_ai_chat_handler() {
    check_ajax_referer('gabochie_ai_nonce', 'nonce');

    if (!gabochie_check_rate_limit('ai_chat', 20)) {
        return;
    }

    $user_message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    if (empty($user_message)) {
        wp_send_json_error(array('message' => 'Please enter a message.'));
    }

    // Build user context for personalization
    $user_context = '';
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $projects = get_posts(array(
            'post_type'   => 'client_project',
            'author'      => $user->ID,
            'numberposts' => 3,
        ));
        $user_context = "The user is logged in as **{$user->display_name}** ({$user->user_email}).";
        if (!empty($projects)) {
            $user_context .= " Their projects:\n";
            foreach ($projects as $p) {
                $status = get_post_meta($p->ID, 'project_status', true);
                $stage  = get_post_meta($p->ID, 'project_stage', true);
                $user_context .= "- {$p->post_title} (Status: {$status}, Stage: {$stage})\n";
            }
        }
        $user_context .= "\nRefer them to /dashboard/ for full details.\n";
    } else {
        $user_context = "The user is a visitor (not logged in). Encourage them to start a project or sign up at /login/.";
    }

    if (class_exists('Gabochie_AI_Engine')) {
        $ai = Gabochie_AI_Engine::instance();
        $ai->refresh_config();
        if ($ai->is_configured()) {
            $system_prompt = "You are GD Assistant, the official AI architecture guide for Gabochie Design, an AI-powered residential architecture firm serving Ghana and the African diaspora. Answer questions about our services, pricing, process, and diaspora support. Be conversational, helpful, and reference Ghana-specific building knowledge. Keep responses concise (3-5 paragraphs max). Current date: " . date('F j, Y') . ".\n\nKey facts:\n- Concept Design from \$800, Full Design from \$2,500, Complete from \$5,000\n- Interior Design add-on \$1,800\n- Project Management 8-12% of build cost\n- Based in Sakumono, Tema, Ghana. Phone: +233 26 824 8970\n- Email: admin@design.gabochie.com\n- Services: Architectural Design, Interior Design, 3D Visualization, Project Management, Cost Calculator, Diaspora Support\n- Over 60% of clients build from abroad (UK, USA, Canada)\n- AI-accelerated process: 4-6 weeks from concept to construction\n\nCurrent user context:\n$user_context";
            $response = $ai->chat(
                array(array('role' => 'user', 'content' => $user_message)),
                $system_prompt,
                0.7
            );
            wp_send_json_success(array('message' => $response));
            return;
        }
    }

    $lower = strtolower($user_message);
    $responses = array(
        'pricing' => array(
            'keywords' => array('price', 'pricing', 'cost', 'how much', 'fee', 'rates', 'budget', 'estimate'),
            'response' => "Our pricing adapts to your project scope:\n\n<strong>Concept Design</strong> — from \$800\n<strong>Full Architectural Plans</strong> — from \$2,500\n<strong>Interior Design Package</strong> — from \$1,800\n<strong>Project Management</strong> — 8-12% of build cost\n\nTry our <a href='/building-cost-calculator'>Cost Calculator</a> for instant estimates.\n\n<em>Configure an AI API key in Settings → Gabochie SaaS → AI Document Engine for AI-powered personalized responses.</em>"
        ),
        'services' => array(
            'keywords' => array('service', 'services', 'what do you', 'offer', 'provide', 'do you do'),
            'response' => "We offer AI-powered architecture services:\n\n<strong>1. Architectural Design</strong> — AI-generated plans + human refinement\n<strong>2. Interior Design</strong> — Smart space planning\n<strong>3. 3D Visualization</strong> — Renders in 48 hours\n<strong>4. Project Management</strong> — AI-oversight\n<strong>5. Cost Calculator</strong> — Live market estimates\n<strong>6. Diaspora Support</strong> — Remote management\n\nConfigure an AI API key for personalized responses."
        ),
        'diaspora' => array(
            'keywords' => array('diaspora', 'abroad', 'overseas', 'international', 'remote', 'outside ghana', 'from abroad', 'live in'),
            'response' => "We specialize in serving the diaspora! Over 60% of our clients build from abroad.\n\n<strong>• Diaspora Manager AI</strong> — Communication & logistics\n<strong>• Virtual Walkthroughs</strong> — 3D tours in real-time\n<strong>• Multi-Currency Pricing</strong> — USD, GBP, EUR, GHS\n<strong>• Progress Reports</strong> — Weekly AI-generated updates\n\nConfigure an AI API key for detailed personalized guidance."
        ),
        'process' => array(
            'keywords' => array('process', 'how does it work', 'steps', 'workflow', 'timeline', 'how long'),
            'response' => "Our process:\n\n<strong>Step 1:</strong> Discovery Call (30 min)\n<strong>Step 2:</strong> AI Concept (48 hours)\n<strong>Step 3:</strong> Refinement (1 week)\n<strong>Step 4:</strong> Full Plans (2-3 weeks)\n<strong>Step 5:</strong> Build with AI oversight\n\nTotal: <strong>4-6 weeks</strong> concept to construction."
        ),
        'contact' => array(
            'keywords' => array('contact', 'email', 'phone', 'whatsapp', 'call', 'consult'),
            'response' => "<strong>Email</strong> admin@design.gabochie.com\n<strong>Phone</strong> +233 26 824 8970\n<strong>WhatsApp</strong> <a href='https://wa.me/233268248970'>Chat now</a>\n<strong>Office</strong> Sakumono, Tema, Ghana\n\n<a href='/contact'>Book a free consultation</a>"
        ),
    );

    $response = null;
    foreach ($responses as $data) {
        foreach ($data['keywords'] as $keyword) {
            if (strpos($lower, $keyword) !== false) {
                $response = $data['response'];
                break 2;
            }
        }
    }

    // Dynamic response: logged-in user asking about their project
    if (is_user_logged_in() && preg_match('/\b(my project|my status|my stage|project progress|my design)\b/i', $lower)) {
        $projects = get_posts(array(
            'post_type'   => 'client_project',
            'author'      => get_current_user_id(),
            'numberposts' => 3,
        ));
        if (!empty($projects)) {
            $lines = array();
            foreach ($projects as $p) {
                $s = get_post_meta($p->ID, 'project_status', true);
                $st = get_post_meta($p->ID, 'project_stage', true);
                $lines[] = "• {$p->post_title} — Status: {$s}, Stage: {$st}";
            }
            $response = "Here's your project info:\n\n" . implode("\n", $lines) . "\n\nVisit your <a href='/dashboard/'>Dashboard</a> for full details.";
        }
    }

    if (!$response) {
        if (is_user_logged_in()) {
            $response = "I'm here to help! You can ask about:\n\n• Your project status & progress\n• Our services & pricing\n• Diaspora building guide\n• Contact & support\n\nWhat would you like to know?";
        } else {
            $response = "I can help with: Services, Pricing, Process, Diaspora support, or Contact. What would you like to know?\n\n<em>Configure an AI API key in Settings → Gabochie SaaS → AI Document Engine for AI-powered responses.</em>";
        }
    }

    wp_send_json_success(array('message' => $response));
}
add_action('wp_ajax_gabochie_ai_chat', 'gabochie_ai_chat_handler');
add_action('wp_ajax_nopriv_gabochie_ai_chat', 'gabochie_ai_chat_handler');

/* ============================================
   COST CALCULATOR AJAX
   ============================================ */
function gabochie_cost_calculator_handler() {
    check_ajax_referer('gabochie_nonce', 'nonce');

    if (!gabochie_check_rate_limit('cost_calc', 30)) {
        return;
    }

    $area = floatval($_POST['area'] ?? 0);
    $bedrooms = intval($_POST['bedrooms'] ?? 2);
    $style = sanitize_text_field($_POST['style'] ?? 'modern');
    $finish = sanitize_text_field($_POST['finish'] ?? 'standard');
    $currency = sanitize_text_field($_POST['currency'] ?? 'USD');

    $rates = array(
        'modern'  => array('standard' => 450, 'premium' => 650, 'luxury' => 900),
        'classic' => array('standard' => 400, 'premium' => 580, 'luxury' => 800),
        'traditional' => array('standard' => 350, 'premium' => 500, 'luxury' => 700),
    );

    $rate = $rates[$style][$finish] ?? 450;
    $base_cost = $area * $rate;

    $bedroom_addon = $bedrooms * 5000;
    $total_usd = $base_cost + $bedroom_addon;

    $exchange_rates = array(
        'USD' => 1,
        'GBP' => 0.79,
        'EUR' => 0.92,
        'GHS' => 15.8,
    );

    $rate_val = $exchange_rates[$currency] ?? 1;
    $symbols = array('USD' => '$', 'GBP' => '£', 'EUR' => '€', 'GHS' => 'GH₵');
    $total = round($total_usd * $rate_val);
    $symbol = $symbols[$currency] ?? '$';

    $breakdown = array(
        'construction' => round($base_cost * $rate_val),
        'permits' => round(2500 * $rate_val),
        'design' => round(3500 * $rate_val),
        'management' => round(($total_usd * 0.10) * $rate_val),
        'total' => $total,
        'symbol' => $symbol,
        'currency' => $currency,
    );

    wp_send_json_success($breakdown);
}
add_action('wp_ajax_gabochie_cost_calc', 'gabochie_cost_calculator_handler');
add_action('wp_ajax_nopriv_gabochie_cost_calc', 'gabochie_cost_calculator_handler');

/* ============================================
   CONTACT FORM AJAX HANDLER
   ============================================ */
function gabochie_contact_form_handler() {
    check_ajax_referer('gabochie_contact_nonce', 'nonce');

    if (!gabochie_check_rate_limit('contact', 5)) {
        return;
    }

    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $service = sanitize_text_field($_POST['service'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        wp_send_json_error(array('message' => 'Please fill in all required fields.'));
    }

    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Please enter a valid email address.'));
    }

    $to = get_theme_mod('company_email', 'admin@design.gabochie.com');
    $subject = sprintf('New Contact Form Submission from %s — %s', $name, $service ?: 'General Inquiry');
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        sprintf('Reply-To: %s <%s>', $name, $email),
    );

    $body = sprintf(
        '<strong>Name:</strong> %s<br><strong>Email:</strong> %s<br><strong>Service:</strong> %s<br><strong>Message:</strong><br>%s',
        esc_html($name),
        esc_html($email),
        esc_html($service ?: 'Not specified'),
        nl2br(esc_html($message))
    );

    $sent = wp_mail($to, $subject, $body, $headers);

    if ($sent) {
        wp_send_json_success(array('message' => 'Thank you! Your message has been sent. We\'ll get back to you within 48 hours.'));
    } else {
        wp_send_json_error(array('message' => 'Something went wrong. Please try again or email us directly at ' . $to));
    }
}
add_action('wp_ajax_gabochie_contact', 'gabochie_contact_form_handler');
add_action('wp_ajax_nopriv_gabochie_contact', 'gabochie_contact_form_handler');
