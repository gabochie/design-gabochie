<?php

 if (!defined('ABSPATH')) {
    exit;
 }

 if (!class_exists('WC_Payment_Gateway')) {
    return;
 }


 if (!class_exists('MPWP_Moolre_Payment_Gateway')) {

 class MPWP_Moolre_Payment_Gateway extends WC_Payment_Gateway 
  {

        protected static $instance = null;

        // Setup our Gateway's id, description and other values
        public $mpwp_moolre_url;
        public $mpwp_pubKey;
        public $mpwp_description;
        public $mpwp_environment;
        public $mpwp_accountNumber;
        public $mpwp_reference;
        public $mpwp_currency;
        public $mpwp_default_order_status;
        public $mpwp_feeBearer;
        public $mpwp_callBack;
        public $mpwp_autocomplete_order;

        /**
         * Class constructor with basic gateway's setup.
         *
         * @codeCoverageIgnore
         */

        public function __construct()
        {
            $this->id              = 'mpwp_moolre_payment_gateway';
            $this->mpwp_moolre_url = 'https://api.moolre.com/embed/src/start';
            $this->icon            = apply_filters('mpwp_woocommerce_moolre_icon', plugins_url('../assets/images/moolre-gh.png', __FILE__));
            $this->method_title    = __('Moolre Payments', 'moolre-payment-gateway');
            $this->method_description = esc_attr__('Moolre Payment Gateway Plug-in for WooCommerce', 'moolre-payment-gateway');
            $this->has_fields      = true;

            $this->init_form_fields();
            $this->init_settings();
            
            $this->title       = $this->get_option('title');
		    $this->description = $this->get_option('description');
            $this->enabled     = $this->get_option('enabled');

            // Turn these settings into variables we can use
            $this->mpwp_environment = $this->get_option('environment', 'sandbox');
            $this->mpwp_pubKey = $this->get_option('pubKey');
            $this->mpwp_accountNumber = $this->get_option('accountNumber');
            $this->mpwp_currency      = $this->get_option('currency');
            $this->mpwp_default_order_status = $this->get_option('defaultOrderStatus', 'processing'); // 'processing'
            $this->mpwp_feeBearer     = $this->get_option('feeBearer');
            $this->mpwp_callBack      = $this->get_option('callBack');
            $this->mpwp_description   = $this->get_option('description');

            $this->add_actions();
        }
 
        // Build the administration fields for this Gateway
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => esc_attr__('Enable / Disable', 'moolre-payment-gateway'),
                    'label' => esc_attr__('Enable this payment gateway', 'moolre-payment-gateway'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ),
                'environment' => array(
                    'title' => esc_attr__('Environment', 'moolre-payment-gateway'),
                    'type' => 'select',
                    'class' => 'my-field-class form-row-wide',
                    'options' => [
                        'sandbox' => esc_attr__('Sandbox (Test Mode)', 'moolre-payment-gateway'),
                        'live'    => esc_attr__('Live (Production Mode)', 'moolre-payment-gateway'),
                    ],
                    'default' => 'sandbox',
                    'desc_tip' => esc_attr__('Switch between sandbox for testing and live for real payments.', 'moolre-payment-gateway'),
                ),
                'title' => array(
                    'title' => esc_attr__('Title', 'moolre-payment-gateway'),
                    'type' => 'text',
                    'desc_tip' => esc_attr__('Payment title the customer will see during the checkout process.', 'moolre-payment-gateway'),
                    'default' => esc_attr__('Moolre', 'moolre-payment-gateway'),
                ),
                'description' => array(
                    'title' => esc_attr__('Description', 'moolre-payment-gateway'),
                    'type' => 'text',
                    'desc_tip' => esc_attr__('Payment description the customer will see during the checkout process.', 'moolre-payment-gateway'),
                    // 'default' => esc_attr__('Pay securely using your Mobile Money Wallet.', 'moolre-payment-gateway'),
                ),
                'pubKey' => array(
                    'title' => esc_attr__('Public Key', 'moolre-payment-gateway'),
                    'type' => 'textarea',
                    'desc_tip' => esc_attr__('Public Key can be found on your Moolre dashboard.', 'moolre-payment-gateway'),
                    'css' => 'max-width:400px;'
                ),
                'accountNumber' => array(
                    'title' => esc_attr__('Account Number', 'moolre-payment-gateway'),
                    'type' => 'text',
                    'desc_tip' => esc_attr__('This is the account number seen on your wallet on the Moolre dashboard.', 'moolre-payment-gateway'),
                ),
                'currency' => array(
                    'title' => esc_attr__('Currency', 'moolre-payment-gateway'),
                    'type' => 'select',
                    'class' => 'my-field-class form-x  -wide', 
                    'options' => [
                        'GHS'  => esc_attr__('Ghana Cedi', 'moolre-payment-gateway'),
                    ],
                    'desc_tip' => esc_attr__('This is the currency to be used for payments.', 'moolre-payment-gateway'),
                ),
                'defaultOrderStatus' => array(
                    'title'       => esc_attr__('Default Order Status', 'moolre-payment-gateway'),
                    'type'        => 'select',
                    'class'       => 'my-field-class form-x -wide',
                    'options'     => [
                        'processing' => esc_attr__('Processing', 'moolre-payment-gateway'),
                        'on-hold'    => esc_attr__('On-hold', 'moolre-payment-gateway'),
                        'completed'  => esc_attr__('Completed', 'moolre-payment-gateway'),
                    ],
                    'default'     => 'processing',
                    'desc_tip'    => esc_attr__('This is your default order status.', 'moolre-payment-gateway'),
                ),


                // 'feeBearer' => array(
                //     'title' => esc_attr__('Fee Bearer', 'moolre-payment-gateway'),
                //     'type' => 'select',
                //     'class' => 'my-field-class form-row-wide',
                //     'options' => [
                //         'self'  => esc_attr__('Merchant', 'moolre-payment-gateway'),
                //         'customer'  => esc_attr__('Customer',  'moolre-payment-gateway')
                //     ],
                //     'desc_tip' => esc_attr__('This is the bearer of fees on payments.', 'moolre-payment-gateway'),
                //     'default' => esc_attr__('This is the bearer of Payment Fees.', 'moolre-payment-gateway'),
                // ),
                'autocomplete_order' => array(
                    'title'       => esc_attr__('Autocomplete Order After Payment', 'moolre-payment-gateway'),
                    'label'       => esc_attr__('Autocomplete Order', 'moolre-payment-gateway'),
                    'type'        => 'checkbox',
                    'class'       => 'wc-moolre-autocomplete-order',
                    'description' => esc_attr__('If enabled, the order will be marked as complete after successful payment', 'moolre-payment-gateway'),
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Register different actions.
         *
         * @codeCoverageIgnore
         */
        private function add_actions()
        {
            // Lets check for SSL
            add_action('admin_notices', array($this, 'admin_notices'));
            
            // Payment Receipt
            add_action('woocommerce_receipt_' . $this->id, array($this, 'mpwp_receipt_page'));

            // Payment listener/API hook.
            add_action('woocommerce_api_mpwp_moolre_payment_gateway', array($this, 'mpwp_verify_moolre_transaction'));

            //register the callback hooks which we will use to receive the payment response from the gateway
            add_action('woocommerce_api_moolre_gateway', array($this, 'payment_callback'));
            // Save settings
            if (is_admin()) {
                // Versions over 2.0
                // Save our administration options. Since we are not going to be doing anything special
                // we have not defined 'process_admin_options' in this class so the method in the parent
                // class will be used instead
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            }
        }
        
        /**
         * Check if Moolre payement gateway is enabled.
         *
         * @return bool
         */
        public function is_available() {
            if ('yes' == $this->enabled) {
                if (!( $this->mpwp_pubKey && $this->mpwp_accountNumber)) {
                    return false;
                }
                return true;
            }
            return false;
        }

        public static function install() {
            if (!current_user_can('activate_plugins')) {
                return false;
            }
        }

        public static function uninstall() {
            if (!current_user_can('activate_plugins')) {
                return false;
            }
        }

        public static function get_instance() {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __clone() {
            return wp_die('Cloning is forbidden!');
        }

        public function __wakeup() {
            return wp_die('Unserializing instances is forbidden!');
        }

        /**
         * Display Moolre Description and Custom Nonce.
         */
        function payment_fields()
        {
            // Display the payment gateway description
            echo esc_html(wptexturize($this->mpwp_description));

            // Add the custom nonce field using the existing function
            $this->mpwp_add_custom_nonce();
        }

        /**
         * Add custom nonce field to the checkout form.
         */
        function mpwp_add_custom_nonce() {
            wp_nonce_field('mpwp_nonce_action', 'mpwp_nonce_field');
        }

        // Submit payment and handle response
        public function process_payment($order_id)
        {

            // generate nonce for block checkouts
            if (empty($_POST['mpwp_nonce_field'])) {
                $nonce = wp_create_nonce('mpwp_nonce_action');
                $_POST['mpwp_nonce_field'] = $nonce; 
            }

            // Check if the nonce field is empty or invalid
            if (empty($_POST['mpwp_nonce_field']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mpwp_nonce_field'])), 'mpwp_nonce_action')) {
                wc_add_notice(esc_html__('Nonce verification failed. Please try again.', 'moolre-payment-gateway'), 'error');
                return;
            }

            // Use configured environment setting
            $environment = $this->mpwp_environment;

            if ('sandbox' === $environment || 'live' === $environment) {
                // Get the order's information
                $order     = wc_get_order($order_id);
                $amount    = $order->get_total();
                $reference = $order_id . '_' . time();
                $callback  = WC()->api_request_url('MPWP_Moolre_Payment_Gateway');
        
                $order->update_meta_data('_moolre_ref', $reference);
                $order->save();

                // Prepare the data to send to the server
                $json_data = [
                    'state'         => 'starter',
                    'accountnumber' => $this->mpwp_accountNumber,
                    'reference'     => $reference,
                    'nonce_value'   => sanitize_text_field(wp_unslash($_POST['mpwp_nonce_field'])),
                    'email'         => $order->get_billing_email(),
                    'amount'        => $amount,
                    'currency'      => $this->mpwp_currency,
                    'callback'      => $callback,
                    'tx_source'     => 'woocommerce-plugin'
                ];

                $headers = [
                    'X-Api-Pubkey' => $this->mpwp_pubKey
                ];

                $args = [
                    'headers' => $headers,
                    'timeout' => 60,
                    'body'    => wp_json_encode($json_data)
                ];

                $request = wp_remote_post($this->mpwp_moolre_url, $args);

                $moolre_response = json_decode(wp_remote_retrieve_body($request), true);

                if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
                    if ($moolre_response['status'] == 0 || $moolre_response['status'] == false) {
                        throw new Exception(esc_html__('Payment failed. Please try again.', 'moolre-payment-gateway'));
                    } else {
                        return [
                            'result'   => 'success',
                            'redirect' => "".$moolre_response['data']['authorization_url']."",
                        ];
                    }
                } else {
                    if (isset($moolre_response['message']) && is_array($moolre_response['message'])) {
                        $all_errors = implode('<br>', array_map('esc_html', $moolre_response['message']));

                        // Translators: This message informs users of errors to address before proceeding.
                        $error_intro = esc_html__('Please address the following errors:', 'moolre-payment-gateway');
                        
                        $error_message = "<strong>{$error_intro}</strong><br>{$all_errors}";

                        throw new Exception(wp_kses_post($error_message));
                    } else {
                        throw new Exception(esc_html__('Payment failed. Please try again.', 'moolre-payment-gateway'));
                    }
                }
            } else {
                throw new Exception(esc_html__('Payment gateway is not configured. Please contact the site administrator.', 'moolre-payment-gateway'));
            }
        }

        /**
         * Retrieve a transaction from Moolre.
        */
        private function get_mpwp_moolre_transaction($moolre_trans_ref)
        {
            // HTTP API
            $json_data = [
                'state' => 'confirm',
                'accountnumber' => $this->mpwp_accountNumber,
                'reference' => $moolre_trans_ref,
            ];

            $headers = [
                'X-Api-Pubkey' => $this->mpwp_pubKey,
            ];

            $args = [
                'headers' => $headers,
                'timeout' => 60,
                'body'    => wp_json_encode($json_data),
            ];

            $response = wp_remote_post($this->mpwp_moolre_url, $args);

            if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
                $moolre_response = json_decode(wp_remote_retrieve_body($response), true);

                
                if ($moolre_response['status'] == 0 || $moolre_response['status'] == false) {
                    throw new Exception(esc_attr__('Verification Failure!', 'moolre-payment-gateway'));
                }

                return $moolre_response;
            } elseif (403 === wp_remote_retrieve_response_code($response)) {
                throw new Exception(esc_attr__('Error in Connection!', 'moolre-payment-gateway'));
            } else {
                wc_add_notice(esc_attr__('Unable to process payment, try again', 'moolre-payment-gateway'), 'error');
            }

            return false;
        }

        /**
         * Checks if autocomplete order is enabled for the payment method.
         */
        protected function mpwp_is_autocomplete_order_enabled($order_id)
        {
            if ($this->mpwp_autocomplete_order == 'yes') {
                $autocomplete_order = true;
            } else {
                $autocomplete_order = false;
            }
            return $autocomplete_order;
        }

        /**
         * Verify Moolre payment.
        */
        public function mpwp_verify_moolre_transaction()
        {
            if (isset($_REQUEST['reference']) && isset($_REQUEST['mpwp_nonce'])) {
                $mpwp_nonce = sanitize_text_field(wp_unslash($_REQUEST['mpwp_nonce']));
                $reference  = sanitize_text_field(wp_unslash($_REQUEST['reference']));

                if (wp_verify_nonce($mpwp_nonce, 'mpwp_nonce_action')) {
                    $value = esc_attr($reference);
                } else {
                    $value = ''; // Handle the case where nonce verification fails
                }
            } else {
                $value = ''; // Handle the case where reference or nonce is not set
            }

            if (!empty($value)) {
                $moolre_txn_ref = $value;
            } else {
                $moolre_txn_ref = false;
            }

            @ob_clean();

            if ($moolre_txn_ref) {
                $moolre_response = $this->get_mpwp_moolre_transaction($moolre_txn_ref);

                if (1 == $moolre_response['data']['status']) {
                    $order_details = explode('_', $moolre_response['data']['reference']);
                    $order_id = (int) $order_details[0];
                    $order = wc_get_order($order_id);

                    // Check if the order has already been processed
                    if (in_array($order->get_status(), array('processing', 'completed', 'on-hold'))) {
                        wp_redirect($this->get_return_url($order));
                        exit;
                    } else {
                        // Update the order status to processing
                        $order->update_status($this->mpwp_default_order_status);
                        $order->payment_complete(); 

                        // Redirect to the return URL after processing
                        wp_redirect($this->get_return_url($order));
                        exit;
                    }
                }

                wp_redirect($this->get_return_url($order));
                exit;
            }

            wp_redirect(wc_get_page_permalink('cart'));
            exit;
        }

        public function mpwp_receipt_page($order_id)
        {
            $order = wc_get_order($order_id);

            echo '<div id="wc-moolre-form">';

            echo '<p>' . esc_html__('Thank you for your order, please click the button below to pay with Moolre.', 'moolre-payment-gateway') . '</p>';

            echo '<div id="moolre_form"><form id="order_review" method="post" action="' . esc_url(WC()->api_request_url('MPWP_Moolre_Payment_Gateway')) . '"></form><button class="button" id="moolre-payment-button">' . esc_html__('Pay Now', 'moolre-payment-gateway') . '</button>';

            echo '</div>';
        }

        // Check if Moolre's merchant details is filled.
        public function admin_notices()
        {

            if ($this->enabled == 'no') {
                return;
            }

            // Check required fields.
            if (!($this->mpwp_pubKey && $this->mpwp_accountNumber)) {
                echo '<div class="error"><p>' . sprintf(
                    // Translators: %s is a placeholder for the admin URL linking to the Moolre settings page.
                    esc_attr__(
                        'Please enter your Moolre\'s public key and account number <a href="%s">here</a> to be able to use the Moolre WooCommerce plugin.',
                        'moolre-payment-gateway'
                    ),
                    esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=moolre'))
                ) . '</p></div>';

                return;

            }
        }
  }
  
}