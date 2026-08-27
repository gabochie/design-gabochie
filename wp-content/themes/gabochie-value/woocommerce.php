<?php
/**
 * WooCommerce template wrapper.
 * Delegates all rendering to WooCommerce's own template system.
 */
get_header('shop');
?>

<div id="primary" class="content-area">
  <main id="main" class="site-main" role="main">
    <?php woocommerce_content(); ?>
  </main>
</div>

<?php
get_footer('shop');
