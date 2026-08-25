<?php
/**
 * Scripts & Styles — enqueuing
 * @package Gabochie_Value
 */
if (!defined('ABSPATH')) exit;

function gabochie_value_scripts() {
    // Preconnect for Google Fonts
    add_action('wp_head', function () {
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }, 1);

    // Brand fonts (gabochiedesign.com typography)
    wp_enqueue_style(
        'gabochie-value-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap',
        array(),
        null
    );

    // Main styles
    wp_enqueue_style(
        'gabochie-value-main',
        GABOCHIE_VALUE_URI . '/assets/css/styles.css',
        array('gabochie-value-fonts'),
        GABOCHIE_VALUE_VERSION
    );

    // Main script (footer, no jQuery dependency)
    wp_enqueue_script(
        'gabochie-value-main',
        GABOCHIE_VALUE_URI . '/assets/js/main.js',
        array(),
        GABOCHIE_VALUE_VERSION,
        true
    );

    wp_localize_script('gabochie-value-main', 'gabochieValue', array(
        'homeUrl'   => home_url('/'),
        'whatsapp'  => gabochie_value_whatsapp(),
    ));
}
add_action('wp_enqueue_scripts', 'gabochie_value_scripts', 99);
