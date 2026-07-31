<?php
/**
 * Plugin Name: ExpressPay Gateway for WooCommerce
 * Plugin URI: https://github.com/expresspaygh/woocommerce-plugin-v2
 * Description: Provides an option to receives payments via the expressPay merchant api.
 * Author: expressPay Ghana Limited
 * Author URI: https://www.expresspaygh.com
 * Version: 3.0.0
 * Text Domain: expressPay-gateway-for-woocommerce
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2015-2024 expressPay Ghana Limited. (info@expresspaygh.com) and WooCommerce
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Gateway-ExpressPay
 * @author    expressPay Ghana Limited
 * @category  Admin
 * @copyright Copyright (c) 2015-2024 expressPay Ghana Limited. and WooCommerce
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * 
 * WC requires at least: 6.0
 * WC tested up to: 8.4
 *
 * This ExpressPay Gateway allows option to receive payments via expressPay's merchant api.
 */

 // Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;


add_action('plugins_loaded', 'woocommerce_expresspay', 0);
function woocommerce_expresspay(){
    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class 

    include(plugin_dir_path(__FILE__) . 'class-gateway.php');
}


add_filter('woocommerce_payment_gateways', 'expresspay_gateway');

function expresspay_gateway($gateways) {
  $gateways[] = 'Expresspay_Gateway';
  return $gateways;
}

/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_expresspay_gateway_plugin_links($links)
{
	$plugin_links = array(
		'<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=expresspay_gateway') . '">' . __('Configure', 'wc-gateway-expresspay') . '</a>'
	);

	return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_expresspay_gateway_plugin_links');

/**
 * Adds plugin currencies
 * 
 * @since 1.0.0
 * @param array $currencies list of currencies
 * @return array $currencies all currencies plus our custom one
 */
function add_expresspay_currencies($currencies)
{
  $currencies['GHS'] = __('Ghana Cedi', 'woocommerce');
  return $currencies;
}
add_filter('woocommerce_currencies', 'add_expresspay_currencies');

/**
 * Adds plugin currency symbols
 * 
 * @since 1.0.0
 * @param string $currency_symbol symbol for currency
 * @param string $currency selected currency
 * @return string $currency_symbol all currencies plus our custom one
 */
function add_expresspay_currencies_symbol($currency_symbol, $currency) {
    if ($currency == 'GHS') {
        $currency_symbol = 'GHS ';
    }
  return $currency_symbol;
}
add_filter('woocommerce_currency_symbol', 'add_expresspay_currencies_symbol', 10, 2);

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
*/
function declare_cart_checkout_blocks_compatibility() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', 'register_payment_method_type' );

//Declare HPOS compatibility
add_action('before_woocommerce_init', function(){
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

/**
 * Custom function to register a payment method type

 */
function register_payment_method_type() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'class-block.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of Expresspay_Gateway_Blocks
            $payment_method_registry->register( new Expresspay_Gateway_Blocks );
        }
    );
}
