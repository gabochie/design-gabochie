<?php
/**
 * Widget Areas
 * @package Gabochie_Design
 */
if (!defined('ABSPATH')) exit;

function gabochie_widgets_init() {
    register_sidebar(array(
        'name'          => __('Footer Column 1', 'gabochie-design'),
        'id'            => 'footer-1',
        'before_widget' => '<div class="footer-widget">',
        'after_widget'  => '</div>',
    ));
    register_sidebar(array(
        'name'          => __('Footer Column 2', 'gabochie-design'),
        'id'            => 'footer-2',
        'before_widget' => '<div class="footer-widget">',
        'after_widget'  => '</div>',
    ));
}
add_action('widgets_init', 'gabochie_widgets_init');
