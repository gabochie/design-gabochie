<?php
/**
 * Theme Setup — menus, image sizes, theme supports
 * @package Gabochie_Design
 */
if (!defined('ABSPATH')) exit;

function gabochie_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('html5', array('search-form','comment-form','comment-list','gallery','caption'));
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');

    register_nav_menus(array(
        'primary'  => __('Primary Navigation', 'gabochie-design'),
        'footer'   => __('Footer Navigation', 'gabochie-design'),
        'social'   => __('Social Links', 'gabochie-design'),
    ));

    add_image_size('gabochie-hero', 1600, 900, true);
    add_image_size('gabochie-card', 800, 500, true);
    add_image_size('gabochie-thumb', 400, 300, true);
}
add_action('after_setup_theme', 'gabochie_setup');
