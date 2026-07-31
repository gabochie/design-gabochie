<?php
/**
 * Helper Functions — SVG logo, social links, shared data
 * @package Gabochie_Design
 */
if (!defined('ABSPATH')) exit;

function gabochie_svg_logo() {
    return '<svg class="logo-hex" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
        <polygon points="26,3 49,15 49,37 26,49 3,37 3,15" fill="none" stroke="rgba(201,169,110,0.5)" stroke-width="1.2"/>
        <polygon points="26,7 45,17.5 45,34.5 26,45 7,34.5 7,17.5" fill="none" stroke="rgba(201,169,110,0.2)" stroke-width="0.5"/>
        <text x="26" y="25" text-anchor="middle" font-family="Playfair Display,serif" font-size="13" font-weight="700" fill="#FAF7F2" letter-spacing="0.5">GD</text>
        <text x="26" y="35" text-anchor="middle" font-family="Jost,sans-serif" font-size="4.8" fill="rgba(201,169,110,0.75)" letter-spacing="1.8">GABOCHIE</text>
    </svg>';
}

function gabochie_get_social_links() {
    $socials = array();
    $links = array(
        'facebook'  => get_theme_mod('social_facebook', ''),
        'youtube'   => get_theme_mod('social_youtube', ''),
        'behance'   => get_theme_mod('social_behance', ''),
        'linkedin'  => get_theme_mod('social_linkedin', ''),
        'tiktok'    => get_theme_mod('social_tiktok', ''),
        'instagram' => get_theme_mod('social_instagram', ''),
        'twitter'   => get_theme_mod('social_twitter', ''),
    );
    foreach ($links as $name => $url) {
        if (!empty($url)) {
            $socials[$name] = $url;
        }
    }
    return $socials;
}

/* ============================================
   SHARED DATA — pricing, services, process steps
   ============================================ */
function gabochie_get_pricing_plans() {
    return array(
        array('tier' => 'Concept', 'usd' => 800, 'per' => 'one-time', 'badge' => '', 'featured' => false, 'features' => array('AI-Generated Floor Plans', '2 Revision Rounds', 'Basic 3D Renders', 'Cost Estimate', 'Digital Delivery')),
        array('tier' => 'Full Design', 'usd' => 2500, 'per' => 'one-time', 'badge' => 'Most Popular', 'featured' => true, 'features' => array('Everything in Concept', 'Architectural Drawings', 'Structural Plans', 'Photorealistic 3D Renders', 'Material Specifications', 'Bill of Quantities')),
        array('tier' => 'Complete', 'usd' => 5000, 'per' => 'one-time', 'badge' => 'Full Service', 'featured' => false, 'features' => array('Everything in Full Design', 'Interior Design Package', 'Landscape Concept', 'Permit Applications', 'Contractor Selection', 'Project Management (3 months)')),
    );
}

function gabochie_get_services() {
    return array(
        array('num' => '01', 'title' => 'Architectural Design', 'desc' => 'AI-generated floor plans refined by expert architects. From concept sketches to construction-ready drawings.'),
        array('num' => '02', 'title' => 'Interior Design', 'desc' => 'Smart space planning with material selection, mood boards, and detailed specifications for your dream home.'),
        array('num' => '03', 'title' => '3D Visualization', 'desc' => 'Photorealistic renders and virtual walkthroughs. See your home before a single brick is laid.'),
        array('num' => '04', 'title' => 'Project Management', 'desc' => 'AI-oversight of your construction with weekly progress reports, milestone tracking, and contractor coordination.'),
        array('num' => '05', 'title' => 'Cost Calculator', 'desc' => 'Instant, AI-powered cost estimates based on real market rates. Plan your budget with confidence.'),
        array('num' => '06', 'title' => 'Diaspora Support', 'desc' => 'End-to-end remote project management for Ghanaians building from abroad. Virtual walkthroughs and weekly AI reports.'),
    );
}

function gabochie_get_process_steps() {
    return array(
        array('num' => '01', 'title' => 'Discovery Call', 'desc' => 'We learn about your vision, lifestyle, budget, and timeline in a relaxed 30-minute conversation.'),
        array('num' => '02', 'title' => 'AI Concept', 'desc' => 'Our AI generates multiple design concepts within 48 hours, informed by Ghanaian spatial wisdom.'),
        array('num' => '03', 'title' => 'Refinement', 'desc' => 'You choose a direction. We refine it together — adjusting layouts, materials, and details until it feels right.'),
        array('num' => '04', 'title' => 'Full Plans', 'desc' => 'Architectural drawings, structural plans, material specs, and a detailed bill of quantities.'),
        array('num' => '05', 'title' => 'Build', 'desc' => 'Construction begins with AI-powered oversight. Weekly reports keep you informed, wherever you are.'),
    );
}

function gabochie_get_pricing_comparison() {
    return array(
        array('name' => 'AI Floor Plans', 'c' => true, 'f' => true, 'co' => true),
        array('name' => 'Revision Rounds', 'c' => '2', 'f' => 'Unlimited', 'co' => 'Unlimited'),
        array('name' => '3D Renders', 'c' => 'Basic', 'f' => 'Photorealistic', 'co' => 'Photorealistic'),
        array('name' => 'Architectural Drawings', 'c' => false, 'f' => true, 'co' => true),
        array('name' => 'Structural Plans', 'c' => false, 'f' => true, 'co' => true),
        array('name' => 'Material Specs', 'c' => false, 'f' => true, 'co' => true),
        array('name' => 'Bill of Quantities', 'c' => false, 'f' => true, 'co' => true),
        array('name' => 'Interior Design', 'c' => false, 'f' => false, 'co' => true),
        array('name' => 'Landscape Concept', 'c' => false, 'f' => false, 'co' => true),
        array('name' => 'Permit Applications', 'c' => false, 'f' => false, 'co' => true),
        array('name' => 'Contractor Selection', 'c' => false, 'f' => false, 'co' => true),
        array('name' => 'Project Management', 'c' => false, 'f' => false, 'co' => '3 months'),
    );
}
