<?php
/**
 * Theme Setup — menus, image sizes, theme supports
 * @package Gabochie_Value
 */
if (!defined('ABSPATH')) exit;

function gabochie_value_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('woocommerce', array(
        'thumbnail_image_width' => 400,
        'single_image_width'    => 600,
        'product_grid'          => array(
            'default_rows'    => 3,
            'min_rows'        => 1,
            'default_columns' => 3,
            'min_columns'     => 1,
            'max_columns'     => 4,
        ),
    ));
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    register_nav_menus(array(
        'primary' => __('Primary Navigation', 'gabochie-value'),
        'footer'  => __('Footer Navigation', 'gabochie-value'),
    ));

    add_image_size('gabochie-value-hero', 1600, 900, true);
    add_image_size('gabochie-value-card', 800, 500, true);
}
add_action('after_setup_theme', 'gabochie_value_setup');
