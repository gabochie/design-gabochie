<?php
/**
 * WooCommerce template wrapper for classic themes.
 */
if (!defined('ABSPATH')) exit;

// Use WC's own template system
if (is_product()) {
    wc_get_template('single-product.php');
} elseif (is_product_category() || is_product_tag() || is_shop()) {
    wc_get_template('archive-product.php');
} else {
    wc_get_template('product-archive.php');
}
