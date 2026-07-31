<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Expresspay_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'expresspay_gateway';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_expresspay_gateway_settings', [] );
        $this->gateway = new Expresspay_Gateway();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        $script_version = '2.0';
        wp_register_script(
            'expresspay_gateway-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            $script_version,
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'expresspay_gateway-blocks-integration');
            
        }
        return [ 'expresspay_gateway-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
        ];
    }

}
?>