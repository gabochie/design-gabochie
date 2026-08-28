<?php
/**
 * PMPro Gateway for Moolre (MTN MoMo).
 *
 * Sends users to Moolre's hosted checkout for mobile money payment,
 * then verifies via the webhook callback.
 *
 * Follows the same pattern as PayPal Express: wp_redirect() inside process().
 *
 * @package Gabochie_Moolre_Gateway
 */

defined( 'ABSPATH' ) || exit;

class PMProGateway_moolre extends PMProGateway {

    const API_URL     = 'https://api.moolre.com/embed/src/start';
    const OPTIONS_KEY = 'gmg_settings';

    public function __construct( $gateway = null ) {
        $this->id      = 'moolre';
        $this->name    = 'Moolre (MTN MoMo)';
        $this->gateway = $gateway;
    }

    /**
     * Describe gateway features.
     */
    public static function supports( $feature ) {
        switch ( $feature ) {
            case 'gateway_scheduled_membership_payments':
                return false;
            default:
                return false;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Payment initiation (offsite redirect)                             */
    /* ------------------------------------------------------------------ */

    /**
     * Process checkout: call Moolre API, redirect to hosted checkout.
     *
     * On the FIRST call (_moolre_ref not set): initiate payment + redirect.
     * On the SECOND call (user returns from Moolre, _moolre_ref already set):
     * return true so PMPro completes checkout.
     */
    public function process( &$order ) {
        error_log( 'GMG: process() called for order #' . $order->id );

        // If returning from Moolre (callback already verified or pending),
        // the _moolre_ref is set. Let PMPro finish checkout.
        $moolre_ref = $order->getMeta( '_moolre_ref' );
        if ( ! empty( $moolre_ref ) ) {
            error_log( 'GMG: returning from Moolre, ref=' . $moolre_ref );
            $order->payment_type = 'Moolre';
            return true;
        }

        $api_key     = $this->get_option( 'api_key', '' );
        $account_num = $this->get_option( 'account_number', '' );
        $currency    = $this->get_option( 'currency', 'GHS' );

        if ( empty( $api_key ) || empty( $account_num ) ) {
            $order->error      = 'Payment gateway not configured. Please contact the site administrator.';
            $order->error_type = 'pmpro_error';
            return false;
        }

        $order->payment_type = 'Moolre';
        $order->gateway      = 'moolre';

        $amount = isset( $order->initial_payment ) ? (float) $order->initial_payment : 0;
        if ( empty( $amount ) && isset( $order->billing_amount ) ) {
            $amount = (float) $order->billing_amount;
        }
        error_log( 'GMG: amount=' . $amount );

        $reference = $order->id . '_' . time();
        $callback  = rest_url( 'gmg/v1/callback' );

        $email = isset( $order->Email ) ? $order->Email : ( isset( $order->user_email ) ? $order->user_email : '' );
        $payload = array(
            'state'         => 'starter',
            'accountnumber' => $account_num,
            'reference'     => $reference,
            'nonce_value'   => wp_create_nonce( 'gmg_nonce_' . $order->id ),
            'email'         => $email,
            'amount'        => (float) $amount,
            'currency'      => $currency,
            'callback'      => $callback,
            'tx_source'     => 'pmpro-plugin',
        );

        error_log( 'GMG: calling Moolre API, ref=' . $reference . ' amount=' . $amount );

        $response = wp_remote_post( self::API_URL, array(
            'headers' => array( 'X-Api-Pubkey' => $api_key ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'GMG: API error: ' . $response->get_error_message() );
            $order->error      = 'Could not connect to payment gateway: ' . $response->get_error_message();
            $order->error_type = 'pmpro_error';
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $raw_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $raw_body, true );
        error_log( 'GMG: API response code=' . $code . ' body=' . substr( $raw_body, 0, 500 ) );

        if ( $code !== 200 ) {
            $msg = $body['message'] ?? 'Payment gateway error.';
            if ( is_array( $msg ) ) {
                $msg = implode( ', ', $msg );
            }
            $order->error      = $msg;
            $order->error_type = 'pmpro_error';
            return false;
        }

        if ( empty( $body['status'] ) || empty( $body['data']['authorization_url'] ) ) {
            $order->error      = 'Payment initiation failed. Please try again.';
            $order->error_type = 'pmpro_error';
            return false;
        }

        $order->updateMeta( '_moolre_ref', $reference );
        $order->save();

        error_log( 'GMG: redirecting to ' . $body['data']['authorization_url'] );
        wp_redirect( $body['data']['authorization_url'] );
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  Webhook / callback handling                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Handle the callback from Moolre after payment.
     *
     * Called via: admin-ajax.php?action=moolre_webhook
     */
    public function webhook() {
        pmpro_doing_webhook( 'moolre', true );

        $reference = isset( $_REQUEST['reference'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['reference'] ) ) : '';
        if ( empty( $reference ) && isset( $GLOBALS['wp_rest_request'] ) ) {
            $reference = sanitize_text_field( $GLOBALS['wp_rest_request']->get_param( 'reference' ) );
        }
        $order_id  = $reference ? (int) explode( '_', $reference )[0] : 0;

        if ( ! $order_id ) {
            pmpro_unhandled_webhook( 'moolre' );
            status_header( 400 );
            echo 'Missing or invalid reference.';
            exit;
        }

        $order = new MemberOrder( $order_id );
        if ( ! $order->id ) {
            pmpro_unhandled_webhook( 'moolre' );
            status_header( 404 );
            echo 'Order not found.';
            exit;
        }

        // Already processed.
        if ( in_array( $order->status, array( 'success', 'cancelled' ), true ) ) {
            status_header( 200 );
            echo 'Order already ' . $order->status . '.';
            exit;
        }

        // Verify with Moolre.
        $verified = $this->verify_payment( $reference );

        if ( ! $verified ) {
            $order->error  = 'Moolre verification failed.';
            $order->notes .= 'Verification failed for reference: ' . $reference . "\n";
            $order->updateStatus( 'error' );
            pmpro_unhandled_webhook( 'moolre' );
            status_header( 400 );
            echo 'Verification failed.';
            exit;
        }

        // Payment confirmed — complete the order.
        $order->payment_transaction_id = $reference;
        $order->status                 = 'success';
        $order->save();

        // Grant membership.
        if ( function_exists( 'pmpro_enableMembershipLevel' ) ) {
            pmpro_enableMembershipLevel( $order->membership_id, $order->user_id );
        }

        // Send the checkout confirmation email via PMPro's email system.
        if ( function_exists( 'pmpro_email_send' ) ) {
            do_action( 'pmpro_email_checkout_confirmed', $order );
        }

        status_header( 200 );
        echo 'Payment completed. Order #' . $order_id;
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  Moolre API verification                                           */
    /* ------------------------------------------------------------------ */

    private function verify_payment( $reference ) {
        $api_key     = $this->get_option( 'api_key', '' );
        $account_num = $this->get_option( 'account_number', '' );

        $payload = array(
            'state'         => 'confirm',
            'accountnumber' => $account_num,
            'reference'     => $reference,
        );

        $response = wp_remote_post( self::API_URL, array(
            'headers' => array( 'X-Api-Pubkey' => $api_key ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return $code === 200 && ! empty( $body['status'] ) && ! empty( $body['data']['status'] );
    }

    /* ------------------------------------------------------------------ */
    /*  Settings                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Retrieve a setting value.
     */
    public function get_option( $key = '', $default = null ) {
        $options = get_option( self::OPTIONS_KEY, array() );
        if ( '' === $key ) {
            return $options;
        }
        return isset( $options[ $key ] ) ? $options[ $key ] : $default;
    }

    /**
     * Show the settings fields inside PMPro Settings → Payment Gateway.
     *
     * PMPro calls this via: call_user_func( array( $gateway_class, 'show_settings_fields' ) );
     */
    public static function show_settings_fields() {
        $options = get_option( self::OPTIONS_KEY, array() );
        ?>
        <tr class="gateway gateway_moolre">
            <th scope="row"><label for="gmg_environment">Environment</label></th>
            <td>
                <select id="gmg_environment" name="gmg_environment">
                    <option value="sandbox" <?php selected( $options['environment'] ?? '', 'sandbox' ); ?>>Sandbox (Test Mode)</option>
                    <option value="live" <?php selected( $options['environment'] ?? '', 'live' ); ?>>Live (Production)</option>
                </select>
                <p class="description">Switch to Sandbox for testing, Live for real MoMo payments.</p>
            </td>
        </tr>
        <tr class="gateway gateway_moolre">
            <th scope="row"><label for="gmg_api_key">Moolre API Key</label></th>
            <td>
                <input type="text" id="gmg_api_key" name="gmg_api_key" value="<?php echo esc_attr( $options['api_key'] ?? '' ); ?>" class="regular-text">
                <p class="description">Public key from your <a href="https://dashboard.moolre.com" target="_blank" rel="noopener">Moolre dashboard</a>.</p>
            </td>
        </tr>
        <tr class="gateway gateway_moolre">
            <th scope="row"><label for="gmg_account_number">Moolre Account Number</label></th>
            <td>
                <input type="text" id="gmg_account_number" name="gmg_account_number" value="<?php echo esc_attr( $options['account_number'] ?? '' ); ?>" class="regular-text">
                <p class="description">Account number from your Moolre wallet.</p>
            </td>
        </tr>
        <tr class="gateway gateway_moolre">
            <th scope="row"><label for="gmg_currency">Currency</label></th>
            <td>
                <select id="gmg_currency" name="gmg_currency">
                    <option value="GHS" <?php selected( $options['currency'] ?? '', 'GHS' ); ?>>Ghana Cedi (GHS)</option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Save the settings fields.
     *
     * PMPro calls this via: call_user_func( array( $gateway_class, 'save_settings_fields' ) );
     */
    public static function save_settings_fields() {
        $settings = array(
            'environment'    => sanitize_text_field( wp_unslash( $_REQUEST['gmg_environment'] ?? 'sandbox' ) ),
            'api_key'        => sanitize_text_field( wp_unslash( $_REQUEST['gmg_api_key'] ?? '' ) ),
            'account_number' => sanitize_text_field( wp_unslash( $_REQUEST['gmg_account_number'] ?? '' ) ),
            'currency'       => sanitize_text_field( wp_unslash( $_REQUEST['gmg_currency'] ?? 'GHS' ) ),
        );
        update_option( self::OPTIONS_KEY, $settings );
    }

    /**
     * Description for the gateway listing.
     */
    public static function get_description_for_gateway_settings() {
        return esc_html__( 'Pay with MTN Mobile Money via Moolre.', 'gabochie-moolre' );
    }

}
