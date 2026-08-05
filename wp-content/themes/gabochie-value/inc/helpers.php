<?php
/**
 * Template helpers — phone, WhatsApp, section links, socials, JSON-LD
 * @package Gabochie_Value
 */
if (!defined('ABSPATH')) exit;

/** Display phone: '+233 26 824 8970' */
function gabochie_value_phone() {
    return get_theme_mod('company_phone', '+233 26 824 8970');
}

/** Digits for tel: links: '+233268248970' */
function gabochie_value_phone_tel() {
    return preg_replace('/[^0-9+]/', '', gabochie_value_phone());
}

/** WhatsApp number without '+': '233268248970' */
function gabochie_value_whatsapp() {
    return get_theme_mod('whatsapp_number', '233268248970');
}

/** Absolute URL to a front-page section: gabochie_value_section_url('clarity') -> https://site/#clarity */
function gabochie_value_section_url($anchor) {
    return home_url('/#' . sanitize_title($anchor));
}

/**
 * "Get Clarity" target: on the front page scroll to its wizard; on any other
 * page go to the Contact page wizard when that page exists, else the front
 * page wizard. Matches VDAC's per-page clarity links.
 */
function gabochie_value_clarity_url() {
    if (is_front_page()) {
        return home_url('/#clarity');
    }
    $contact = get_page_by_path('contact');
    if ($contact && $contact->ID) {
        return get_permalink($contact) . '#clarity';
    }
    return home_url('/#clarity');
}

/** Social network handle => URL (VDAC brand links) */
function gabochie_value_social_links() {
    return array(
        'facebook' => 'https://web.facebook.com/GabochieDesign',
        'x'        => 'https://x.com/gabochiedesign',
        'behance'  => 'https://www.behance.net/gabochiedesign',
        'linkedin' => 'https://www.linkedin.com/company/gabochiedesign',
        'tiktok'   => 'https://www.tiktok.com/@gabochiedesign',
    );
}

/** JSON-LD ProfessionalService (front page only) */
function gabochie_value_json_ld() {
    if (!is_front_page()) return;
    $data = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'ProfessionalService',
        'name'        => 'Gabochie Design',
        'slogan'      => 'Value-Driven Architectural Consultancy',
        'description' => 'A value-driven architectural consultancy and built-environment advisory company serving homeowners, investors, developers and the Ghanaian diaspora — from first idea to feasibility, design, cost, construction and beyond.',
        'url'         => home_url('/'),
        'telephone'   => '+' . gabochie_value_whatsapp(),
        'address'     => array('@type' => 'PostalAddress', 'addressLocality' => 'Sakumono, Tema', 'addressCountry' => 'GH'),
        'sameAs'      => array_values(gabochie_value_social_links()),
        'areaServed'  => array('Ghana', 'Diaspora'),
        'knowsAbout'  => array('Architecture', 'Feasibility', 'Cost intelligence', 'Construction', 'Property development', "Owner's representation"),
    );
    echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'gabochie_value_json_ld', 1);
