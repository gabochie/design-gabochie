<?php
/**
 * Customizer Settings — hero, company, social, AI agent
 * @package Gabochie_Design
 */
if (!defined('ABSPATH')) exit;

function gabochie_customize_register($wp_customize) {
    /* Hero Section */
    $wp_customize->add_section('gabochie_hero', array(
        'title'    => __('Hero Section', 'gabochie-design'),
        'priority' => 30,
    ));

    $wp_customize->add_setting('hero_title', array('default' => 'AI-Powered Architecture for Ghana & Diaspora', 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('hero_title', array('label' => 'Hero Title', 'section' => 'gabochie_hero', 'type' => 'text'));

    $wp_customize->add_setting('hero_subtitle', array('default' => 'Where Ghanaian spatial wisdom meets cutting-edge AI. We design homes that honor your roots while embracing the future.', 'sanitize_callback' => 'sanitize_textarea_field'));
    $wp_customize->add_control('hero_subtitle', array('label' => 'Hero Subtitle', 'section' => 'gabochie_hero', 'type' => 'textarea'));

    $wp_customize->add_setting('hero_image', array('default' => '', 'sanitize_callback' => 'esc_url_raw'));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'hero_image', array('label' => 'Hero Image', 'section' => 'gabochie_hero')));

    $wp_customize->add_setting('hero_stat1_num', array('default' => '120+'));
    $wp_customize->add_control('hero_stat1_num', array('label' => 'Stat 1 Number', 'section' => 'gabochie_hero', 'type' => 'text'));
    $wp_customize->add_setting('hero_stat1_label', array('default' => 'Projects Completed'));
    $wp_customize->add_control('hero_stat1_label', array('label' => 'Stat 1 Label', 'section' => 'gabochie_hero', 'type' => 'text'));

    $wp_customize->add_setting('hero_stat2_num', array('default' => '4'));
    $wp_customize->add_control('hero_stat2_num', array('label' => 'Stat 2 Number', 'section' => 'gabochie_hero', 'type' => 'text'));
    $wp_customize->add_setting('hero_stat2_label', array('default' => 'Countries Served'));
    $wp_customize->add_control('hero_stat2_label', array('label' => 'Stat 2 Label', 'section' => 'gabochie_hero', 'type' => 'text'));

    $wp_customize->add_setting('hero_stat3_num', array('default' => '48h'));
    $wp_customize->add_control('hero_stat3_num', array('label' => 'Stat 3 Number', 'section' => 'gabochie_hero', 'type' => 'text'));
    $wp_customize->add_setting('hero_stat3_label', array('default' => 'First Concept'));
    $wp_customize->add_control('hero_stat3_label', array('label' => 'Stat 3 Label', 'section' => 'gabochie_hero', 'type' => 'text'));

    /* Company Info */
    $wp_customize->add_section('gabochie_company', array(
        'title'    => __('Company Info', 'gabochie-design'),
        'priority' => 31,
    ));

    $wp_customize->add_setting('company_phone', array('default' => '+233 26 824 8970', 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('company_phone', array('label' => 'Phone Number', 'section' => 'gabochie_company', 'type' => 'text'));

    $wp_customize->add_setting('company_email', array('default' => 'admin@design.gabochie.com', 'sanitize_callback' => 'sanitize_email'));
    $wp_customize->add_control('company_email', array('label' => 'Email', 'section' => 'gabochie_company', 'type' => 'email'));

    $wp_customize->add_setting('company_address', array('default' => 'P.O.BOX DM SK 2125, Sakumono, Tema, Ghana', 'sanitize_callback' => 'sanitize_textarea_field'));
    $wp_customize->add_control('company_address', array('label' => 'Address', 'section' => 'gabochie_company', 'type' => 'textarea'));

    $wp_customize->add_setting('whatsapp_number', array('default' => '233268248970', 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('whatsapp_number', array('label' => 'WhatsApp Number (digits only with country code)', 'section' => 'gabochie_company', 'type' => 'text'));

    /* Social Links */
    $wp_customize->add_section('gabochie_social', array(
        'title'    => __('Social Links', 'gabochie-design'),
        'priority' => 32,
    ));

    $socials = array('facebook', 'instagram', 'youtube', 'linkedin', 'twitter', 'behance', 'tiktok');
    foreach ($socials as $social) {
        $wp_customize->add_setting("social_{$social}", array('default' => '', 'sanitize_callback' => 'esc_url_raw'));
        $wp_customize->add_control("social_{$social}", array('label' => ucfirst($social) . ' URL', 'section' => 'gabochie_social', 'type' => 'url'));
    }

    /* AI Agent Settings */
    $wp_customize->add_section('gabochie_ai', array(
        'title'    => __('AI Agent Settings', 'gabochie-design'),
        'priority' => 33,
    ));

    $wp_customize->add_setting('ai_chat_enabled', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('ai_chat_enabled', array('label' => 'Enable AI Chat Widget', 'section' => 'gabochie_ai', 'type' => 'checkbox'));

    $wp_customize->add_setting('ai_chat_greeting', array('default' => "Hello! I'm GD Assistant, your AI-powered architecture guide for building in Ghana. How can I help you today?", 'sanitize_callback' => 'sanitize_textarea_field'));
    $wp_customize->add_control('ai_chat_greeting', array('label' => 'AI Chat Greeting', 'section' => 'gabochie_ai', 'type' => 'textarea'));
}
add_action('customize_register', 'gabochie_customize_register');
