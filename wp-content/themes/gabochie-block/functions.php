<?php
/**
 * Gabochie Block Theme — functions.php
 *
 * @package Gabochie_Block
 */

if (!defined('ABSPATH')) exit;

define('GABOCHIE_BLOCK_VERSION', '1.0.0');

/**
 * Enqueue Google Fonts
 */
add_action('wp_enqueue_scripts', 'gabochie_block_fonts');
function gabochie_block_fonts() {
    wp_enqueue_style(
        'gabochie-google-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600;700&display=swap',
        array(),
        null
    );
}

/**
 * Enqueue theme styles
 */
add_action('wp_enqueue_scripts', 'gabochie_block_styles');
function gabochie_block_styles() {
    wp_enqueue_style(
        'gabochie-block-theme',
        get_stylesheet_directory_uri() . '/assets/theme.css',
        array(),
        GABOCHIE_BLOCK_VERSION
    );
}

/**
 * Editor styles
 */
add_editor_style('assets/editor.css');

/**
 * Theme support
 */
add_action('after_setup_theme', 'gabochie_block_setup');
function gabochie_block_setup() {
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('automatic-feed-links');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
}

/**
 * Custom image sizes
 */
add_action('init', 'gabochie_block_image_sizes');
function gabochie_block_image_sizes() {
    add_image_size('gabochie-hero', 1400, 700, true);
    add_image_size('gabochie-card', 800, 450, true);
}
