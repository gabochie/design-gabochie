<?php
/**
 * Thesis Lead Magnet — email gate + delivery
 * @package Gabochie_Value
 * @version 1.4.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Shared helper — get an existing user by email or create one.
 * Fixes the previously-dangling gabochie_value_ensure_user() calls
 * (lead capture + onboarding handlers now have a concrete definition).
 *
 * @param string $email
 * @param string $name
 * @return int|WP_Error User ID on success, WP_Error on failure.
 */
if (!function_exists('gabochie_value_ensure_user')) {
    function gabochie_value_ensure_user($email, $name = '') {
        $email = sanitize_email($email);
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'A valid email address is required.');
        }

        $existing = get_user_by('email', $email);
        if ($existing) {
            if ($name) {
                $existing->display_name = $name;
                wp_update_user(array('ID' => $existing->ID, 'display_name' => $name));
            }
            return (int) $existing->ID;
        }

        $username = sanitize_user(current(explode('@', $email)), true);
        if (username_exists($username)) {
            $username .= '_' . wp_rand(100, 999);
        }

        $display = $name;
        if (!$display) {
            $display = ucwords(str_replace(array('.', '_', '-'), ' ', current(explode('@', $email))));
        }

        $uid = wp_insert_user(array(
            'user_login'   => $username,
            'user_email'   => $email,
            'display_name' => $display,
            'first_name'   => $name,
            'user_pass'    => wp_generate_password(16, true),
            'role'         => 'subscriber',
        ));

        if (is_wp_error($uid)) {
            return $uid;
        }
        return (int) $uid;
    }
}

/* ---- Thesis download (email gate) ---- */
add_action('wp_ajax_nopriv_gabochie_thesis_download', 'gabochie_value_handle_thesis_download');
add_action('wp_ajax_gabochie_thesis_download', 'gabochie_value_handle_thesis_download');
function gabochie_value_handle_thesis_download() {
    check_ajax_referer('gabochie_thesis_nonce', 'nonce');

    /* Honeypot — bots fill the hidden "website" field, humans never see it */
    if (!empty($_POST['website'])) {
        wp_send_json_success(array('message' => 'Thanks for your interest. Check your inbox.'));
    }

    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $name  = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));

    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'A valid email address is required.'), 400);
    }

    $uid = gabochie_value_ensure_user($email, $name);
    if (is_wp_error($uid)) {
        wp_send_json_error(array('message' => $uid->get_error_message()), 500);
    }

    $first = !get_user_meta($uid, 'gabochie_thesis_downloaded', true);
    update_user_meta($uid, 'gabochie_thesis_downloaded', current_time('mysql'));
    if ($first) {
        update_user_meta($uid, 'gabochie_thesis_first_download', current_time('mysql'));
    }

    /* Ensure the lead has Free Lead membership (they entered the funnel) */
    if (function_exists('pmpro_changeMembershipLevel') && !pmpro_hasMembershipLevel(null, $uid)) {
        global $wpdb;
        $free = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}pmpro_membership_levels WHERE name = 'Free Lead' LIMIT 1");
        if ($free) pmpro_changeMembershipLevel((int) $free, $uid);
    }

    gabochie_value_log_agent_action('thesis_download', array(
        'user_id' => $uid,
        'email'   => $email,
        'name'    => $name,
        'first'   => $first,
    ));

    /* Deliver the thesis link + funnel next step */
    $thesis_url = home_url('/thesis/?dl=1');
    $quiz_url   = home_url('/development-quiz/');
    $site_name  = get_bloginfo('name');

    $subject = 'Your copy of "Ghana\'s Development Execution Gap" — ' . $site_name;
    $message = "Hi " . ($name ?: 'there') . ",\n\n"
        . "Thanks for downloading the thesis: Ghana's Development Execution Gap — Why 60% of Projects Fail Before They Start.\n\n"
        . "Read your copy here:\n" . $thesis_url . "\n\n"
        . "Inside you'll learn:\n"
        . "1. Why Ghana leads West Africa in construction activity — yet most projects stall.\n"
        . "2. The real barrier: not capital — information asymmetry, coordination failure, and zero investor-grade visibility.\n"
        . "3. The data behind the gap, and what the numbers mean for your own project.\n"
        . "4. How a development intelligence platform changes the odds for every developer.\n\n"
        . "Next step — get your free Development Readiness Score in 5 minutes (8 dimensions):\n"
        . $quiz_url . "\n\n"
        . "— The " . $site_name . " Team";

    wp_mail($email, $subject, $message, array('Content-Type: text/plain; charset=UTF-8'));

    wp_send_json_success(array(
        'message' => 'Check your inbox — your copy of the thesis is on its way.',
    ));
}

/**
 * Full thesis content — stored as WordPress page content
 * (the template renders it via the_content()).
 *
 * @return string HTML
 */
function gabochie_value_thesis_content() {
    return <<<HTML
<h2>The Paradox: Ghana Is Building — and Stalling — at the Same Time</h2>
<p>Ghana currently records some of the fastest construction growth in West Africa. Cranes are visible over Accra's skyline, cement sales are climbing, and demand for quality housing and commercial space has never been higher. On paper, this should be the best time in a generation to be a developer.</p>
<p>Yet on the ground, the picture is different. Time after time, projects stall — not weeks or months in, but <strong>before they ever start</strong>. Land sits idle. Half-built structures dot the landscape. Families hold titled land and capital for years, waiting for a path forward that never arrives.</p>
<blockquote>The paradox of Ghanaian development: activity is at record levels, but most projects never break ground. The gap between the two is the market's biggest quiet problem.</blockquote>
<p>This thesis explains that gap, names its true causes, and lays out the practical tools — already being built — that close it.</p>

<h2>The Real Barrier: It Was Never Just Capital</h2>
<p>The instinct is to blame funding. Money is scarce, the story goes — if developers had access to capital, projects would move. But look closer at where projects actually die and the cost of land and construction turns out to be only part of the problem.</p>
<p>The real barrier is threefold:</p>
<ul>
<li><strong>Information asymmetry.</strong> The knowledge that makes a project succeed — realistic costs, market demand, feasibility data, compliance requirements — sits with a handful of large firms and consultants. The ordinary developer never sees it, and so cannot plan around it.</li>
<li><strong>Coordination failure.</strong> A project is not one task; it is dozens of decisions across land, permits, design, finance, contractors, and materials — sequenced over months. Without a delivery system that holds the thread, one missed step stalls everything.</li>
<li><strong>Zero investor-grade visibility.</strong> Lenders, investors, and partners will only back what they can understand. A developer who cannot show a credible readiness score, a realistic cost model, or a clear delivery plan is invisible to capital — even when the numbers would work.</li>
</ul>
<p>Each of these is an information problem. None of them is solved by more money alone.</p>

<h2>The Data Behind the Gap</h2>
<p>Ghana's housing deficit is consistently estimated in the millions of units — government and market research put the figure in the range of 1.8–2 million homes, with formal supply delivering only a fraction of that each year. At the same time, the Ghana Statistical Service and the Ministry of Works and Housing track a construction sector growing faster than most of the economy.</p>
<ul>
<li><strong>Rapid growth, thin delivery.</strong> Construction activity expands year on year, yet formal completions trail demand dramatically.</li>
<li><strong>A stalled-project epidemic.</strong> A large share of formally initiated developments never reach completion — consistent with the pattern of projects stalling during the planning and pre-construction phase, before ground is ever broken.</li>
<li><strong>An expertise bottleneck.</strong> The professional delivery pipeline of large firms — feasibility, budgeting, phasing, compliance — is neither documented nor accessible to the retail developer. The market is not short of money or demand; it is short of accessible expertise.</li>
</ul>
<p>The numbers reframe the problem: capital is not the binding constraint as often as it appears. <strong>Information and coordination are.</strong></p>

<h2>The Solution: A Development Intelligence Platform</h2>
<p>Every successful large firm runs on the same quiet machinery: structured intake, feasibility analysis, realistic costing, phased delivery, and continuous tracking. The idea behind <strong>Gabochie Design's development intelligence platform</strong> is to give every Ghanaian developer that same machinery.</p>
<ul>
<li><strong>The Development Readiness Score.</strong> Eight dimensions — from land status to financial structure — scored honestly, so you know exactly where your project stands before you spend a cedi on it.</li>
<li><strong>Realistic costing.</strong> Market-based build cost estimates for Ghana, adjusted for region, type, and specification — before you commit, not after.</li>
<li><strong>A 14-stage delivery pipeline.</strong> The full sequence a professional team follows, made explicit and trackable for your own project.</li>
<li><strong>Investor-grade visibility.</strong> A structured project record — documents, budget, stage, feasibility — that turns a vague idea into something a bank or partner can actually evaluate.</li>
</ul>
<p>Development succeeds when the right information reaches the right decision at the right time. A platform that puts that information in every developer's hands is not a convenience — it is the mechanism that closes the execution gap.</p>

<h2>The Invitation</h2>
<p>You do not need to scale the gap on your own. The first step is free, and it is five minutes long.</p>
<p><strong>Get your free Development Readiness Score</strong> — eight dimensions, straight answers, and a clear read on what your project needs next. Then use the tools that fit: estimate your build cost, map your 14-stage pipeline, and when you are ready, bring the whole system to bear on your project.</p>
<blockquote>A project that is understood is a project that can be built. The question is no longer whether you have capital. It is whether you have clarity.</blockquote>
HTML;
}

/* ---- Auto-create the /thesis/ page with seeded content ---- */
add_action('init', function () {
    if (get_option('gabochie_value_thesis_page_created')) return;

    $page = get_page_by_path('thesis', OBJECT, 'page');
    if (!$page || !isset($page->ID)) {
        wp_insert_post(array(
            'post_title'   => 'The Development Execution Gap',
            'post_name'    => 'thesis',
            'post_content' => gabochie_value_thesis_content(),
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'meta_input'   => array('_wp_page_template' => 'page-templates/template-thesis.php'),
        ));
    }

    update_option('gabochie_value_thesis_page_created', true);
});