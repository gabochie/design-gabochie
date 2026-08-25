<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class MPWP_Moolre_Payment_Gateway_Blocks extends AbstractPaymentMethodType {
    
    private $gateway;
    protected $settings;
    // protected $name = 'mpwp_payment_gateway'; 
    protected $name = 'mpwp_moolre_payment_gateway'; 
    const SCRIPT_VERSION = '1.0.0';

    public function __construct($gateway) {
        $this->gateway = $gateway;
    }

    // Implement the initialize method
    public function initialize() {
        $this->settings = get_option('woocommerce_mpwp-moolre_settings', []);
    }

    public function is_active() {
        $isActive = $this->gateway->is_available();
        return $isActive;
    }

    public function get_payment_method_script_handles() {

        // Register the JavaScript file
        wp_enqueue_script(
            'mpwp_payment_gateway_blocks_integration',
            plugin_dir_url(__FILE__) . '../assets/js/mpwp-scripts.js',
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n'],
            filemtime(plugin_dir_path(__FILE__) . '../assets/js/mpwp-scripts.js'),
            true
        );

        // Register the CSS file
        $css_file_path = plugin_dir_path(__FILE__) . '../assets/css/mpwp-styles.css';
        $css_file_url = plugin_dir_url(__FILE__) . '../assets/css/mpwp-styles.css';

        if (file_exists($css_file_path)) {
            wp_enqueue_style(
                'mpwp_moolre_gateway_style',
                $css_file_url,
                array(),
                filemtime($css_file_path)
            );
        }

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('mpwp_payment_gateway_blocks_integration');
        }

        return ['mpwp_payment_gateway_blocks_integration'];
    }

    
    public function get_payment_method_data() {
        return [
            'title'         => $this->gateway->title ?? 'Moolre Payment',
            'description'   => $this->gateway->description ?? 'Secure Mobile Money Payment',
            'icon'          => plugins_url('../assets/images/moolre-gh.png', __FILE__),
            'supports'      => ['products'],
            'paymentMethod' => 'mpwp_moolre_payment_gateway', // unique identifier for the payment method
        ];
    }
}