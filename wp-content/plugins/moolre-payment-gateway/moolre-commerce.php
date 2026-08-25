<?php

 /**
 * Plugin Name: Moolre Payment Gateway for WooCommerce
 * Plugin URI: https://docs.moolre.com/wordpress
 * Description: Moolre Payment gateway for WooCommerce
 * Version: 1.2.4
 * License: GPL-2.0+
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * Author: Moolre
 * Author URI: https://github.com/moolrehq
 * Text Domain: moolre-payment-gateway
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * WC requires at least: 8.0.0
 * WC tested up to: 9.4.0
 */


if (!defined('ABSPATH')) {
    exit;
}

/**
 * Declare compatibility with WooCommerce HPOS
 */
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
 */
function mpwp_moolre_declare_cart_checkout_blocks_compatibility()
{
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'mpwp_moolre_declare_cart_checkout_blocks_compatibility');


// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action('woocommerce_blocks_loaded', 'mpwp_moolre_register_order_approval_payment_method_type');

/**
 * Custom function to register a payment method type
 */
function mpwp_moolre_register_order_approval_payment_method_type()
{
    // Check if the required class exists
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'includes/class-mpwp-moolre-woocommerce-block-checkout.php';
 
    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            // Get the gateway instance from the singleton
            $gateway = MPWP_Moolre_Payment_Gateway::get_instance();
            $payment_method_registry->register(new MPWP_Moolre_Payment_Gateway_Blocks($gateway));
        }
    );
}


if (!function_exists('mpwp_moolre_is_woocommerce_active')) {
    /**
     * Return true if Woocommerce plugin is active.
     *
     * @since 0.1
     * @return boolean
     */
    function mpwp_moolre_is_woocommerce_active()
    {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
            return true;
        }
        return false;
    }
}

if (!function_exists('mpwp_moolre_admin_notice_missing_woocommerce')) {
    /**
     * Echo admin notice HTML for missing WooCommerce plugin.
     *
     * @since 0.1
     */
    function mpwp_moolre_admin_notice_missing_woocommerce()
    {
        global $current_screen;
        if ($current_screen->parent_base === 'plugins') {
            ?>
<div class="notice notice-error">
    <p>
        <?php
        /* translators: %s: link to WooCommerce */
        echo sprintf(esc_html__('Please install and activate %s before activating the Moolre WooCommerce Payment Gateway!', 'moolre-payment-gateway'), '<a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a>');
        ?>
    </p>
</div>
<?php
        }
    }
}

if (!mpwp_moolre_is_woocommerce_active()) {
    add_action('admin_notices', 'mpwp_moolre_admin_notice_missing_woocommerce');
    return;
}


 add_action('plugins_loaded', 'initialize_mpwp_moolre_n_gateway', 20);

function initialize_mpwp_moolre_n_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Include the custom gateway class file if it's not already loaded
    if (!class_exists('MPWP_Moolre_Payment_Gateway')) {
        include_once plugin_dir_path(__FILE__) . 'includes/class-mpwp-moolre-payment-gateway.php';
    }

    // Register the gateway
    add_filter('woocommerce_payment_gateways', 'add_mpwp_moolre_payment_gateway');
}

function add_mpwp_moolre_payment_gateway($gateways) {
    $gateways[] = 'MPWP_Moolre_Payment_Gateway';
    return $gateways;
}