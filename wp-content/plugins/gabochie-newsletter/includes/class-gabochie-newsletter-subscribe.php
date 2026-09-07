<?php
/**
 * Gabochie Newsletter public subscribe form.
 *
 * Renders `[gabochie_subscribe]` and exposes a POST REST endpoint
 * (`gabochie-newsletter/v1/subscribe`) that adds a contact to the Gabochie
 * CRM table (single opt-in + explicit consent checkbox). Re-subscribing an
 * unsubscribed address flips the CRM status back to `subscribed` and removes
 * the address from the newsletter's own unsubscribe map.
 *
 * @package Gabochie_Newsletter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public opt-in form + endpoint for the Gabochie newsletter.
 */
class Gabochie_Newsletter_Subscribe {

	const OPT_MAX_PER_IP = 'gabochie_newsletter_sub_max';
	const NONCE_ACTION   = 'gab-nl-subscribe';

	/**
	 * Wire the shortcode and REST route.
	 */
	public static function register() {
		add_shortcode( 'gabochie_subscribe', array( __CLASS__, 'render_form' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	/**
	 * Register the public subscribe POST route.
	 */
	public static function register_route() {
		register_rest_route(
			'gabochie-newsletter/v1',
			'/subscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_subscribe' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'first_name'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'email'         => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_email',
					),
					'consent'       => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'website'       => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'_gab_nl_nonce' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Render the subscribe form.
	 *
	 * @param array|string $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_form( $atts = array() ) {
		$atts = (array) shortcode_atts( array(), $atts, 'gabochie_subscribe' );

		wp_enqueue_script(
			'gab-nl-subscribe',
			plugin_dir_url( __DIR__ ) . 'assets/js/subscribe.js',
			array(),
			GABOCHIE_NEWSLETTER_VERSION,
			true
		);
		wp_localize_script(
			'gab-nl-subscribe',
			'gabNlSubmit',
			array(
				'rest'  => esc_url_raw( rest_url( 'gabochie-newsletter/v1/subscribe' ) ),
				'nonce' => wp_create_nonce( self::NONCE_ACTION ),
			)
		);

		ob_start();
		?>
		<form class="gab-nl-sub" novalidate>
			<div class="gab-nl-sub-fields">
				<label class="gab-nl-sub-label" for="gab-nl-sub-name"><?php esc_html_e( 'First name', 'gabochie-design' ); ?></label>
				<input class="gab-nl-sub-input" type="text" id="gab-nl-sub-name" name="first_name" autocomplete="given-name">
				<label class="gab-nl-sub-label" for="gab-nl-sub-email"><?php esc_html_e( 'Email address', 'gabochie-design' ); ?> <span class="gab-nl-sub-req">*</span></label>
				<input class="gab-nl-sub-input" type="email" id="gab-nl-sub-email" name="email" required autocomplete="email">
				<label class="gab-nl-sub-label" for="gab-nl-sub-website" aria-hidden="true" style="position:absolute;left:-9999px;"><?php esc_html_e( 'Website', 'gabochie-design' ); ?></label>
				<input class="gab-nl-sub-input" type="text" id="gab-nl-sub-website" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;">
				<label class="gab-nl-sub-consent" for="gab-nl-sub-consent">
					<input type="checkbox" id="gab-nl-sub-consent" name="consent" value="1" required>
					<span><?php esc_html_e( 'Yes, send me the Gabochie Design newsletter. I can unsubscribe at any time with one click.', 'gabochie-design' ); ?></span>
				</label>
				<input type="hidden" name="_gab_nl_nonce" value="<?php echo esc_attr( wp_create_nonce( self::NONCE_ACTION ) ); ?>">
			</div>
			<button class="gab-nl-sub-btn" type="submit"><?php esc_html_e( 'Subscribe', 'gabochie-design' ); ?></button>
			<p class="gab-nl-sub-msg" role="status" aria-live="polite"></p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Handle a subscribe POST.
	 *
	 * Validates the form nonce + consent, checks a per-IP rate limit and a
	 * honeypot, then upserts the contact into the Gabochie CRM table.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_subscribe( $request ) {
		$nonce = (string) $request->get_param( '_gab_nl_nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return new WP_Error( 'gab_nl_bad_nonce', __( 'Session expired – please reload the page and try again.', 'gabochie-design' ), array( 'status' => 400 ) );
		}

		// Honeypot: bots fill it, humans never see it. Answer cheerfully.
		$website = (string) $request->get_param( 'website' );
		if ( '' !== $website ) {
			return self::ok();
		}

		if ( '1' !== (string) $request->get_param( 'consent' ) ) {
			return new WP_Error( 'gab_nl_no_consent', __( 'Please confirm you want to receive the newsletter.', 'gabochie-design' ), array( 'status' => 400 ) );
		}

		$email = strtolower( trim( (string) $request->get_param( 'email' ) ) );
		if ( '' === $email || ! is_email( $email ) ) {
			return new WP_Error( 'gab_nl_bad_email', __( 'Please enter a valid email address.', 'gabochie-design' ), array( 'status' => 400 ) );
		}

		if ( ! self::consume_rate_limit( $request ) ) {
			return new WP_Error( 'gab_nl_rate_limited', __( 'Too many attempts – please try again later.', 'gabochie-design' ), array( 'status' => 429 ) );
		}

		if ( ! function_exists( 'gabochie_crm_upsert' ) ) {
			return new WP_Error( 'gab_nl_crm_missing', __( 'Subscription is temporarily unavailable. Please try again later.', 'gabochie-design' ), array( 'status' => 503 ) );
		}

		$first = trim( (string) $request->get_param( 'first_name' ) );
		$id    = gabochie_crm_upsert(
			$email,
			array(
				'first_name' => '' === $first ? null : $first,
				'status'     => 'subscribed',
				'source'     => 'newsletter-subscribe',
				'list_key'   => 'newsletter-subscribe',
			)
		);
		if ( ! $id ) {
			return new WP_Error( 'gab_nl_save_failed', __( 'Could not save your subscription. Please try again later.', 'gabochie-design' ), array( 'status' => 500 ) );
		}

		// Re-subscribe: flip status + clear the newsletter-level unsubscribe flag.
		if ( function_exists( 'gabochie_crm_set_status' ) ) {
			gabochie_crm_set_status( $email, 'subscribed' );
		}
		$unsubs = get_option( Gabochie_Newsletter::OPT_UNSUBS, array() );
		if ( isset( $unsubs[ $email ] ) ) {
			unset( $unsubs[ $email ] );
			update_option( Gabochie_Newsletter::OPT_UNSUBS, $unsubs );
		}

		return self::ok();
	}

	/**
	 * Consume one per-IP rate-limit token (sliding, reset hourly).
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	private static function consume_rate_limit( $request ) {
		$ip = (string) $request->get_header( 'X-Forwarded-For' );
		if ( '' !== $ip ) {
			$ip = trim( (string) strtok( $ip, ',' ) );
		}
		if ( '' === $ip ) {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification
		}
		if ( '' === $ip ) {
			return true;
		}

		$max = max( 1, (int) get_option( self::OPT_MAX_PER_IP, 10 ) );
		$key = 'gab_nl_sub_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= $max ) {
			return false;
		}
		set_transient( $key, $n + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Successful response (kept identical for bots and humans).
	 *
	 * @return WP_REST_Response
	 */
	private static function ok() {
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Thanks for subscribing – check your inbox for the next build brief.', 'gabochie-design' ),
			),
			200
		);
	}
}