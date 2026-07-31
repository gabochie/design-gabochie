<?php

	if ( ! function_exists( 'wp_json_encode' ) ) {
		require_once( ABSPATH . 'wp-includes/functions.php' );
	}

    class Expresspay_Gateway extends WC_Payment_Gateway {
    	/**
		 * Constructor for the gateway.
		 */
		public function __construct()
		{

			$this->id = 'expresspay_gateway';
			$this->icon = apply_filters('woocommerce_expresspay_icon', '');
			$this->has_fields = false;
			$this->method_title = __('Expresspay', 'wc-gateway-expresspay');
			$this->method_description = "Provides an option to receives payments via expressPay's merchant api.";

      		$this->supports = array('products');
		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->merchant_api_url = "";
			$this->session_handler = WC()->session;
			$this->woo_logger = wc_get_logger();
			$this->title = $this->get_option('title');
      		$this->description = $this->get_option('description');
      		$this->merchant_id = $this->get_option('merchant_id');
			$this->environment = $this->get_option('environment');
			$this->woo_context = array('source' => 'expresspay_gateway');
			$this->merchant_api_key = $this->get_option('merchant_api_key');
			$this->merchant_post_url = str_replace('http:','https:',add_query_arg('wc-api', 'Expresspay_Gateway', home_url('/')));
			// $this->merchant_post_url = add_query_arg('wc-api', 'Expresspay_Gateway', home_url('/'));
			
			// Set api url
			$this->set_merchant_api_url();
		  
			// Actions
			add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
			add_action('woocommerce_api_expresspay_gateway', array($this, 'handle_expresspay_callback'));
			add_action('woocommerce_order_details_after_order_table', array($this, 'echo_expresspay_query'));
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		}


    	//settings 
		public function init_form_fields()
		{
			$this->form_fields = apply_filters( 'wc_expresspay_form_fields', array(
			'enabled' => array(
			'title' => __('Enable/Disable', 'wc-gateway-expresspay'),
			'type' => 'checkbox',
			'default' => 'yes',
			'label' => __('Enable Payments via expressPay Ghana Limited', 'wc-gateway-expresspay'),
			),
			'title' => array(
			'title' => __('Title', 'wc-gateway-expresspay'),
			'type' => 'text',
			'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-offline'),
			'default' => __('Pay with expressPay', 'wc-gateway-expresspay'),
			'desc_tip' => true,
			),
			'image_url' => array(
            'title' => __('Image URL', 'wc-gateway-expresspay'),
            'type' => 'text',
            'description' => __('Payment Method Image.', 'wc-gateway-expresspay'),
            'default' => plugins_url('logo.png', __FILE__),
            'desc_tip' => true,
			'custom_attributes' => array(
                'disabled' => 'disabled',
            	),
        	),
			'description' => array(
			'title' => __('Description', 'wc-gateway-expresspay'),
			'type' => 'textarea',
			'description' => __('This controls the description which the user sees during checkout.', 'wc-gateway-expresspay'),
			'default' => __('Make payment using your visa or master card, mobile money, scan to pay and more.', 'wc-gateway-expresspay'),
			'desc_tip' => true
			),
			'environment' => array(
			'title' => __('Environment', 'wc-gateway-expresspay'),
			'type' => 'select',
			'description' => __('This controls which environment to process payments through.', 'wc-gateway-expresspay'),
			'default' => 'production',
			'label' => __('Select Environment', 'wc-gateway-expresspay'),
			'desc_tip' => true,
			'options' => array(
				'sandbox' => __('Sandbox', 'wc-gateway-expresspay'),
				'production' => __('Production', 'wc-gateway-expresspay')
			)
			),
			'merchant_id' => array(
			'title' => __('Merchant ID', 'wc-gateway-expresspay'),
			'type' => 'text',
			'description' => __('Your expressPay merchant ID', 'wc-gateway-expresspay'),
			'default' => '',
			'desc_tip' => true,
			),
			'merchant_api_key' => array(
			'title' => __('Merchant Api-Key', 'wc-gateway-expresspay'),
			'type' => 'text',
			'description' => __('Your expressPay merchant api key', 'wc-gateway-expresspay'),
			'default' => '',
			'desc_tip' => true,
			),
				));
		}

		public function get_title()
		{
			$title = parent::get_title();
			$image_url = $this->get_option('image_url');

			if (!empty($image_url)) {
				$title .= sprintf(' <img src="%s" alt="%s" style="max-height: 20px; margin-left: 10px; margin-top: 5px;">', esc_url($image_url), esc_attr__('ExpressPay Logo', 'wc-gateway-expresspay'));
			}

			return $title;
		}


    	/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment($order_id)
        {
            global $woocommerce;

            $order = wc_get_order($order_id);
            $redirect_url = $this->get_return_url($order);
            // $order->update_status('processing', __('expressPay payment', 'wc-gateway-expresspay'));
            
            $submitParams = [
                "merchant-id" => $this->merchant_id,
                "api-key" => $this->merchant_api_key,
                "currency" => $order->get_currency(),
                "amount" => floatval($order->get_total()),
                "order-id" => $order_id,
                "order-desc" => "Order: " . $order->get_id(),
                "accountnumber" => strval($order->get_billing_phone()),
                "redirect-url" => $redirect_url . "&",
                "post-url" => $this->merchant_post_url,
                "order_img_url" => (!empty($this->icon)) ? $this->icon : "",
                "firstname" => (!empty($order->get_billing_first_name())) ? $order->get_billing_first_name() : "",
                "lastname" => (!empty($order->get_billing_last_name())) ? $order->get_billing_last_name() : "",
                "phonenumber" => (!empty($order->get_billing_phone())) ? $order->get_billing_phone() : "",
                "email" => (!empty($order->get_billing_email())) ? $order->get_billing_email() : "",
            ];
    
            $submit = $this->handle_expresspay_submit($submitParams);
            if (isset($submit['status'])):
                $status = $submit['status'];
                if (($status == 1) && isset($submit['token'])):
                    $submitParams['token'] = $submit['token'];
                    //$this->session_handler->set('expresspay_token', $submit['token']);
                    $redirect_url = $this->handle_expresspay_checkout($submit['token']);
                else:
                    $this->handle_expresspay_error($status);
                endif;
            endif;
            
            //get token and save to orders meta
            $token = $submit['token'];
            update_post_meta($order_id, 'token', $token);

            $woocommerce->cart->empty_cart();
            $order->add_order_note(__('expressPay gateway called successfully', 'woothemes'));
            
            
            return array(
                'result'    => 'success',
                'redirect'  => $redirect_url
            );
        }

		/**
		 * Set base merchant api url to use
		 *
		 * @return void
		 */
    	public function set_merchant_api_url()
		{
			if (!in_array($this->environment, ['sandbox','production']))
			{
				$this->report_error('No environment found');
			}

			if ($this->environment == "sandbox") {
				$this->merchant_api_url = "https://sandbox.expresspaygh.com/api/";
			} else {
				$this->merchant_api_url = "https://expresspaygh.com/api/";
			}
		}

    	/**
		 * handle_expresspay_submit
		 *
		 * @param  mixed $submitParams
		 * @return array
		 */
        public function handle_expresspay_submit($submitParams) {
            $url = $this->merchant_api_url . "submit.php";
            $args = [
                'method' => 'POST',
                'body' => $submitParams,
                'timeout' => 15,
            ];

            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
				$this->report_error($error_message);
            } else {
                $apiResponse = wp_remote_retrieve_body($response);
                return json_decode($apiResponse, true);
            }
        }

        /**
		 * handle_expresspay_checkout
		 *
		 * @param  mixed $token
		 * @return string
		 */
		public function handle_expresspay_checkout($token)
		{
			return sprintf("%scheckout.php?token=%s", $this->merchant_api_url, $token);
		}

    	/**
		 * Handle expresspay error
		 *
		 * @param  mixed $status
		 * @return void
		 */
		public function handle_expresspay_error($status)
		{
			$message = "";

			if ($status == 2) {
				$message = "Sorry, your api credentials are invalid, kindly visit the admin page to set it up.";
			} else if ($status == 3) {
				$message = "Sorry, request is invalid.";
			} elseif($status == 12) {
				$message = "Something bad happened, it's our fault, kindly try again";
			} else {
				$message = "Sorry, invalid IP submitted, kindly contact info@expresspaygh.com to get it verified.";
			}
			
			$this->report_error($message);
		}

		/**
		 * Output for the order received page.
		 */
    	public function thankyou_page($order_id)
		{
      	$order = wc_get_order( $order_id );
      	$order_id  = $order->get_id();
      	$order_data = $order->get_data();
      	$expresspay_token = get_post_meta($order_id, 'token', true);
			$queryParams = [
				"merchant-id" => $this->merchant_id,
				"api-key" => $this->merchant_api_key,
				"token" => $expresspay_token
			];

			$query = $this->handle_expresspay_query($queryParams);
			if (!empty($query)):
				$this->session_handler->set('expresspay_query_response', wp_json_encode($query));
			else:
				$this->handle_expresspay_error(12);
			endif;
		}

    	/**
		 * handle_expresspay_submit
		 *
		 * @param  string $token
		 * @return array
		 */
		public function handle_expresspay_query($queryParams) {
			$url = $this->merchant_api_url . "query.php";
			$args = [
				'method' => 'POST', 
				'body' => $queryParams, 
				'timeout' => 15,
			];

			$response = wp_remote_post($url, $args);

			if (is_wp_error($response)) {
				$error_message = $response->get_error_message();
				$this->report_error($error_message);
			} else {
				$apiResponse = wp_remote_retrieve_body($response);
				return json_decode($apiResponse, true);
			}
		}

    	/**
		 * Report notice to woocommerce
		 *
		 * @param  string $message
		 * @param  string $state
		 * @return void
		 */
		public function report_error($message, $state = null)
		{
			$status = (!is_null($state)) ? $state : 'error';
			wc_add_notice(__('Notice: <br>', 'woothemes') . $message, $status);
			return;
		}
		
		/**
         * echo_expresspay_query
         *
         * @return void
         */
        public function echo_expresspay_query($order_id)
        {

			//check if response already echoed on thank you page
			static $alreadyEchoed = false;

        	if ($alreadyEchoed) {
            return;
        	}

            try {
                
                $order = wc_get_order( $order_id );
                $order_id  = $order->get_id();
                $expresspay_token = get_post_meta($order_id, 'token', true);
                
                $queryParams = [
                "merchant-id" => $this->merchant_id,
                "api-key" => $this->merchant_api_key,
                "token" => $expresspay_token
            ];
                
                $details = $this->handle_expresspay_query($queryParams);

                $detailHeader = "expressPay details";                
                $detailResultText = (isset($details['result-text'])) ? $details['result-text'] : "";
                $detailTransactionID = (isset($details['transaction-id'])) ? $details['transaction-id'] : "";
                $detailAmount = (isset($details['amount'])) ? $details['amount'] : "";
                $detailPaymentOption = (isset($details['payment_option'])) ? $details['payment_option'] : "";

                

                include(dirname(__FILE__) . '/expresspay-thankyou-element.php');

				 $alreadyEchoed = true;

            } catch (Exception $e) {
                $this->report_error($e->getMessage(), 'error');
            }
        }

		/**
		 * handle_expresspay_callback
		 *
		 * @return void
		 */
		public function handle_expresspay_callback()
		{
			$state = "";
			$debugBag = array();
			$logPrefix = "[Oops!] ::: ";

			if ($_SERVER['REQUEST_METHOD'] !== "POST"):
				$this->woo_logger->debug($logPrefix . "Sorry, request should be post!", $this->woo_context);
				return;
			endif;

			$token = (isset($_POST['token'])) ? $_POST['token'] : '';
			$order_id = (isset($_POST['order-id'])) ? $_POST['order-id'] : '';

			if (!empty($token) && !empty($order_id)):

				$order = wc_get_order($order_id);
				if (!$order):
					$this->woo_logger->critical($logPrefix . "No order exist for id: $order_id", $this->woo_context);
					return;
				endif;

				if ($order->has_status('completed')):
					$this->woo_logger->info($logPrefix . "Order has already been completed: $order_id", $this->woo_context);
					return;
				elseif ($order->has_status('refunded')):
					$this->woo_logger->info($logPrefix . "Order has already been refunded: $order_id", $this->woo_context);
					return;
				elseif ($order->has_status('cancelled')):
					$this->woo_logger->info($logPrefix . "Order has already been cancelled: $order_id", $this->woo_context);
					return;
				endif;

				$queryParams = [
					"merchant-id" => $this->merchant_id,
					"api-key" => $this->merchant_api_key,
					"token" => $token
				];

				$query = $this->handle_expresspay_query($queryParams);
				if (!empty($query)):
					if (isset($query['result'])):
						$status = (isset($query['result'])) ? $query['result'] : "";
						$detailResultText = (isset($query['result-text'])) ? $query['result-text'] : "";
						if ($status == 1):
							$order->payment_complete();
							$state = 'Successful result';
							$order->add_order_note("ExpressPay Payment status is:: ".$detailResultText);
							$order->update_status('processing', __('expressPay payment', 'wc-gateway-expresspay'));

						elseif ($status == 2 || $status == 3):
							$state = 'Declined result';
							$order->update_status("failed");
							$order->add_order_note("ExpressPay Payment status is:: ".$detailResultText);

						elseif ($status == 4):
							$state = 'Successful result';
							$order->update_status("pending payment");
							$order->add_order_note("ExpressPay Payment status is:: ".$detailResultText);

						else:
							$state = 'Failed result';
							$order->update_status("failed");
							$order->add_order_note("ExpressPay Payment status is:: ".$detailResultText);

						endif;
					else:
						$state = 'Invalid request';
					endif;
				else:
					$state = "Query is empty!";
				endif;

				array_push($debugBag, [
					'state' => $state,
					'output' => json_encode($query)
				]);

				$this->woo_logger->debug($logPrefix . "Expresspay Query debugBag: " . json_encode($debugBag), $this->woo_context);
				return;
			else:
				$this->woo_logger->debug($logPrefix . "No token nor order-id found in request!", $this->woo_context);
				return;
			endif;
		}

    
 	}
