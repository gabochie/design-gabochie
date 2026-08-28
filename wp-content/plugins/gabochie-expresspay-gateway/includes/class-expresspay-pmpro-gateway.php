<?php
/**
 * PMPro Gateway for ExpressPay Ghana.
 *
 * Supports Visa, Mastercard, Mobile Money (MTN, Airtel, Vodafone, Tigo),
 * GhanaPay, GH-Link, PayPal, USSD, and QR via ExpressPay's hosted checkout.
 *
 * @package Gabochie_ExpressPay_Gateway
 */

defined( 'ABSPATH' ) || exit;

class PMProGateway_expresspay extends PMProGateway {

    const API_SANDBOX = 'https://sandbox.expresspaygh.com/api/';
    const API_LIVE    = 'https://expresspaygh.com/api/';
    const OPTIONS_KEY = 'g_eg_settings';

    public function __construct( $gateway = null ) {
        $this->id   = 'expresspay';
        $this->name = 'ExpressPay — Card, MoMo & GhanaPay';
    }

    /**
     * ExpressPay does not support server-initiated recurring billing.
     */
    public static function supports( $feature ) {
        return false;
    }

    /* ------------------------------------------------------------------ */
    /*  Payment initiation                                                */
    /* ------------------------------------------------------------------ */

    public function process( &$order ) {
        error_log( 'GEG: process() called for order #' . $order->id );

        // If returning from ExpressPay (callback already set token), let PMPro finish.
        if ( ! empty( $order->_eg_token ) ) {
            error_log( 'GEG: returning from ExpressPay, token=' . $order->_eg_token );
            $order->payment_type = 'ExpressPay';
            return true;
        }

        $merchant_id  = $this->get_option( 'merchant_id', '' );
        $api_key      = $this->get_option( 'merchant_api_key', '' );
        $environment  = $this->get_option( 'environment', 'sandbox' );

        if ( empty( $merchant_id ) || empty( $api_key ) ) {
            $order->error      = 'Payment gateway not configured. Please contact the site administrator.';
            $order->error_type = 'pmpro_error';
            return false;
        }

        $order->payment_type = 'ExpressPay';
        $order->gateway      = 'expresspay';

        // Save order to get an ID.
        if ( empty( $order->id ) ) {
            $order->saveOrder();
        }

        // Use billing_amount for recurring, initial_payment for one-time.
        $amount = (float) $order->billing_amount;
        if ( empty( $amount ) ) {
            $amount = (float) $order->initial_payment;
        }

        $api_url     = ( 'sandbox' === $environment ) ? self::API_SANDBOX : self::API_LIVE;
        $callback_url = rest_url( 'geg/v1/callback' );

        $submit_params = array(
            'merchant-id'   => $merchant_id,
            'api-key'       => $api_key,
            'currency'      => 'GHS',
            'amount'        => $amount,
            'order-id'      => strval( $order->id ),
            'order-desc'    => 'Order #' . $order->id,
            'accountnumber' => '',
            'redirect-url'  => home_url( '/membership-confirmation/?pmpro_level=' . $order->membership_id ),
            'post-url'      => $callback_url,
            'order_img_url' => '',
            'firstname'     => '',
            'lastname'      => '',
            'phonenumber'   => '',
            'email'         => isset( $order->Email ) ? $order->Email : '',
        );

        error_log( 'GEG: submitting to ExpressPay, order_id=' . $order->id . ' amount=' . $amount );

        $response = wp_remote_post( $api_url . 'submit.php', array(
            'body'    => $submit_params,
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'GEG: API error: ' . $response->get_error_message() );
            $order->error      = 'Could not connect to payment gateway: ' . $response->get_error_message();
            $order->error_type = 'pmpro_error';
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        error_log( 'GEG: submit response code=' . $code . ' body=' . wp_json_encode( $body ) );

        if ( $code !== 200 ) {
            $order->error      = 'Payment gateway returned an error.';
            $order->error_type = 'pmpro_error';
            return false;
        }

        $status = isset( $body['status'] ) ? (int) $body['status'] : 0;
        $token  = isset( $body['token'] ) ? $body['token'] : '';

        if ( 1 !== $status || empty( $token ) ) {
            $msg = isset( $body['message'] ) ? $body['message'] : 'Payment initiation failed.';
            $order->error      = $msg;
            $order->error_type = 'pmpro_error';
            return false;
        }

        // Store token for callback verification.
        $order->_eg_token = $token;
        $order->saveOrder();

        $checkout_url = $api_url . 'checkout.php?token=' . $token;
        error_log( 'GEG: redirecting to ' . $checkout_url );

        wp_redirect( $checkout_url );
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  Callback handling                                                 */
    /* ------------------------------------------------------------------ */

    public function handle_callback() {
        pmpro_doing_webhook( 'expresspay', true );

        $token    = isset( $_REQUEST['token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['token'] ) ) : '';
        $order_id = isset( $_REQUEST['order-id'] ) ? intval( $_REQUEST['order-id'] ) : 0;

        error_log( 'GEG: callback received, order_id=' . $order_id . ' token=' . substr( $token, 0, 20 ) . '...' );

        if ( empty( $token ) || empty( $order_id ) ) {
            status_header( 400 );
            echo 'Missing token or order-id.';
            exit;
        }

        $order = new MemberOrder( $order_id );
        if ( ! $order->id ) {
            error_log( 'GEG: order not found: ' . $order_id );
            status_header( 404 );
            echo 'Order not found.';
            exit;
        }

        // Already processed.
        if ( in_array( $order->status, array( 'success', 'cancelled' ), true ) ) {
            error_log( 'GEG: order already ' . $order->status );
            status_header( 200 );
            echo 'Order already ' . $order->status . '.';
            exit;
        }

        // Query ExpressPay to verify.
        $verified = $this->query_transaction( $token );

        if ( ! $verified ) {
            $order->error = 'ExpressPay verification failed.';
            $order->add_order_note( 'ExpressPay verification failed for token: ' . $token );
            $order->updateStatus( 'error' );
            error_log( 'GEG: verification failed for order #' . $order_id );
            pmpro_unhandled_webhook( 'expresspay' );
            status_header( 400 );
            echo 'Verification failed.';
            exit;
        }

        // Payment confirmed.
        $order->payment_transaction_id = $token;
        $order->status                 = 'success';
        $order->saveOrder();

        // Grant membership.
        if ( function_exists( 'pmpro_enableMembershipLevel' ) ) {
            pmpro_enableMembershipLevel( $order->membership_id, $order->user_id );
        }

        $order->add_order_note( 'ExpressPay payment confirmed. Token: ' . $token );

        error_log( 'GEG: payment completed for order #' . $order_id );
        status_header( 200 );
        echo 'Payment completed. Order #' . $order_id;
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  ExpressPay Query (verify transaction)                             */
    /* ------------------------------------------------------------------ */

    private function query_transaction( $token ) {
        $merchant_id = $this->get_option( 'merchant_id', '' );
        $api_key     = $this->get_option( 'merchant_api_key', '' );
        $environment = $this->get_option( 'environment', 'sandbox' );
        $api_url     = ( 'sandbox' === $environment ) ? self::API_SANDBOX : self::API_LIVE;

        $response = wp_remote_post( $api_url . 'query.php', array(
            'body'    => array(
                'merchant-id' => $merchant_id,
                'api-key'     => $api_key,
                'token'       => $token,
            ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'GEG: query error: ' . $response->get_error_message() );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        error_log( 'GEG: query response=' . wp_json_encode( $body ) );

        if ( empty( $body ) || ! isset( $body['result'] ) ) {
            return false;
        }

        // result=1 means approved.
        return (int) $body['result'] === 1;
    }

    /* ------------------------------------------------------------------ */
    /*  Settings                                                          */
    /* ------------------------------------------------------------------ */

    public function get_option( $key = '', $default = null ) {
        $options = get_option( self::OPTIONS_KEY, array() );
        if ( '' === $key ) {
            return $options;
        }
        return isset( $options[ $key ] ) ? $options[ $key ] : $default;
    }

    public static function show_settings_fields() {
        $options = get_option( self::OPTIONS_KEY, array() );
        ?>
        <tr class="gateway gateway_expresspay">
            <th scope="row"><label for="geg_environment">Environment</label></th>
            <td>
                <select id="geg_environment" name="geg_environment">
                    <option value="sandbox" <?php selected( $options['environment'] ?? '', 'sandbox' ); ?>>Sandbox (Test Mode)</option>
                    <option value="production" <?php selected( $options['environment'] ?? '', 'production' ); ?>>Production (Live)</option>
                </select>
                <p class="description">Use Sandbox for testing with test cards. Production for real payments.</p>
            </td>
        </tr>
        <tr class="gateway gateway_expresspay">
            <th scope="row"><label for="geg_merchant_id">Merchant ID</label></th>
            <td>
                <input type="text" id="geg_merchant_id" name="geg_merchant_id" value="<?php echo esc_attr( $options['merchant_id'] ?? '' ); ?>" class="regular-text">
                <p class="description">From your <a href="https://merchant.expresspaygh.com" target="_blank" rel="noopener">ExpressPay merchant dashboard</a>.</p>
            </td>
        </tr>
        <tr class="gateway gateway_expresspay">
            <th scope="row"><label for="geg_merchant_api_key">API Key</label></th>
            <td>
                <input type="text" id="geg_merchant_api_key" name="geg_merchant_api_key" value="<?php echo esc_attr( $options['merchant_api_key'] ?? '' ); ?>" class="regular-text">
                <p class="description">Merchant API key from ExpressPay.</p>
            </td>
        </tr>
        <?php
    }

    public static function save_settings_fields() {
        $settings = array(
            'environment'     => sanitize_text_field( wp_unslash( $_REQUEST['geg_environment'] ?? 'sandbox' ) ),
            'merchant_id'     => sanitize_text_field( wp_unslash( $_REQUEST['geg_merchant_id'] ?? '' ) ),
            'merchant_api_key' => sanitize_text_field( wp_unslash( $_REQUEST['geg_merchant_api_key'] ?? '' ) ),
        );
        update_option( self::OPTIONS_KEY, $settings );
    }

    public static function get_description_for_gateway_settings() {
        return esc_html__( 'Pay with Visa, Mastercard, Mobile Money, GhanaPay, and more via ExpressPay.', 'gabochie-expresspay' );
    }
}
