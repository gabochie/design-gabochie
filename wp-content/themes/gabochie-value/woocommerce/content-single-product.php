<?php
/**
 * WooCommerce content-single-product template override.
 */
defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
    echo get_the_password_form();
    return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>
    <?php do_action( 'woocommerce_before_single_product_summary' ); ?>
    <div class="summary entry-summary">
        <?php do_action( 'woocommerce_single_product_summary' ); ?>
    </div>
    <?php do_action( 'woocommerce_after_single_product_summary' ); ?>
</div>
<?php do_action( 'woocommerce_after_single_product' ); ?>
