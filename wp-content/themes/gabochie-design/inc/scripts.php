<?php
/**
 * Scripts & Styles — enqueuing, localizing, WooCommerce cleanup
 * @package Gabochie_Design
 */
if (!defined('ABSPATH')) exit;

function gabochie_scripts() {
    wp_enqueue_style('gabochie-main', GABOCHIE_URI . '/assets/css/main.css', array(), GABOCHIE_VERSION);
    wp_enqueue_style('gabochie-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap', array(), null);
    wp_enqueue_script('gabochie-main', GABOCHIE_URI . '/assets/js/main.js', array('jquery'), GABOCHIE_VERSION, true);
    wp_enqueue_script('gabochie-ai-chat', GABOCHIE_URI . '/assets/js/ai-chat.js', array('jquery'), GABOCHIE_VERSION, true);

    wp_localize_script('gabochie-main', 'gabochie', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('gabochie_nonce'),
        'homeUrl' => home_url('/'),
        'contactNonce' => wp_create_nonce('gabochie_contact_nonce'),
    ));

    wp_localize_script('gabochie-ai-chat', 'gabochieAI', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('gabochie_ai_nonce'),
        'botName' => 'GD Assistant',
        'welcomeMsg' => "Hello! I'm GD Assistant, your AI-powered architecture guide for building in Ghana. How can I help you today?",
    ));

    if (function_exists('is_woocommerce')) {
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            wp_dequeue_style('woocommerce-layout');
            wp_dequeue_style('woocommerce-smallscreen');
            wp_dequeue_style('woocommerce-general');
            wp_dequeue_style('wc-blocks-style');
            wp_dequeue_script('wc-add-to-cart');
            wp_dequeue_script('wc-cart-fragments');
            wp_dequeue_script('woocommerce');
            wp_dequeue_script('wc-block-registry');
        }
    }
}
add_action('wp_enqueue_scripts', 'gabochie_scripts', 99);
