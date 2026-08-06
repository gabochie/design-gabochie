<?php
/**
 * Plugin Name: Gabochie Guide Delivery
 * Description: Delivers the Ghana Building Project Readiness Checklist by email (PDF attachment) via Brevo. REST endpoint: POST /wp-json/gabochie/v1/guide-request
 * Version: 1.0.0
 * Author: Gabochie Design
 * Text Domain: gabochie-guide-delivery
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Gabochie_Guide_Delivery {

	/** @var Gabochie_Guide_Delivery|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_lead_type' ) );
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/* ---------------------------------------------------------------- CPT */

	public function register_lead_type() {
		register_post_type(
			'gd_guide_request',
			array(
				'public'       => false,
				'show_ui'      => true,
				'menu_icon'    => 'dashicons-email-alt',
				'labels'       => array(
					'name'          => __( 'Guide Requests', 'gabochie-guide-delivery' ),
					'singular_name' => __( 'Guide Request', 'gabochie-guide-delivery' ),
					'add_new'       => __( 'Add New', 'gabochie-guide-delivery' ),
					'edit_item'     => __( 'Edit Guide Request', 'gabochie-guide-delivery' ),
					'view_item'     => __( 'View Guide Request', 'gabochie-guide-delivery' ),
					'search_items'  => __( 'Search Guide Requests', 'gabochie-guide-delivery' ),
					'not_found'     => __( 'No guide requests yet.', 'gabochie-guide-delivery' ),
				),
				'supports'     => array( 'title' ),
				'capabilities' => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap' => true,
			)
		);
	}

	/* -------------------------------------------------------------- REST */

	public function register_route() {
		register_rest_route(
			'gabochie/v1',
			'/guide-request',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function handle_request( WP_REST_Request $request ) {
		$params    = $request->get_json_params();
		$email     = isset( $params['email'] ) ? sanitize_email( (string) $params['email'] ) : '';
		$source    = isset( $params['source'] ) ? sanitize_text_field( (string) $params['source'] ) : 'homepage';
		$journey   = isset( $params['journey'] ) ? sanitize_text_field( (string) $params['journey'] ) : '';
		$blocker   = isset( $params['blocker'] ) ? sanitize_text_field( (string) $params['blocker'] ) : '';
		$honeypot  = isset( $params['website'] ) ? (string) $params['website'] : '';

		// Honeypot: bots fill the hidden field. Pretend success either way.
		if ( '' !== $honeypot ) {
			return rest_ensure_response( array( 'success' => true, 'sent' => false ) );
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'gd_invalid_email', __( 'That email address does not look right.', 'gabochie-guide-delivery' ), array( 'status' => 400 ) );
		}

		$rate = $this->rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$lead_id = $this->store_lead( $email, $source, $journey, $blocker );
		if ( is_wp_error( $lead_id ) ) {
			return $lead_id;
		}

		$sent = $this->send_email( $email, $source, $journey, $blocker );
		if ( ! $sent ) {
			return new WP_Error( 'gd_send_failed', __( 'The guide could not be sent right now. Please try again, or message us on WhatsApp.', 'gabochie-guide-delivery' ), array( 'status' => 502 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'sent'    => true,
				'lead_id' => $lead_id,
			)
		);
	}

	/* -------------------------------------------------------- Rate limit */

	private function rate_limit() {
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key  = 'gd_gl_' . md5( $ip );
		$used = (int) get_transient( $key );
		if ( $used >= 3 ) {
			return new WP_Error( 'gd_rate_limited', __( 'Too many requests — please try again later.', 'gabochie-guide-delivery' ), array( 'status' => 429 ) );
		}
		set_transient( $key, $used + 1, HOUR_IN_SECONDS );
		return true;
	}

	/* --------------------------------------------------------- Storage */

	private function store_lead( $email, $source, $journey, $blocker ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'gd_guide_request',
				'post_status' => 'private',
				'post_title'  => $email,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		$this->last_lead_id = (int) $post_id;
		update_post_meta( $post_id, '_gd_source', $source );
		update_post_meta( $post_id, '_gd_email', $email );
		if ( $journey ) { update_post_meta( $post_id, '_gd_journey', $journey ); }
		if ( $blocker ) { update_post_meta( $post_id, '_gd_blocker', $blocker ); }
		update_post_meta( $post_id, '_gd_time', current_time( 'mysql' ) );
		return $post_id;
	}

	/* ------------------------------------------------------------- Mail */

	private function api_key() {
		if ( defined( 'GABOCHIE_BREVO_API_KEY' ) && GABOCHIE_BREVO_API_KEY ) {
			return GABOCHIE_BREVO_API_KEY;
		}
		$key = get_option( 'sib_api_key_v3' ); // Sendinblue/Brevo plugin's own option.
		return is_string( $key ) && '' !== $key ? $key : '';
	}

	private function sender() {
		$name  = apply_filters( 'gd_guide_sender_name', 'Gabochie Design' );
		$email = get_option( 'gd_guide_sender_email' );
		if ( ! is_string( $email ) || '' === $email || ! is_email( $email ) ) {
			$email = apply_filters( 'gd_guide_sender_email', get_option( 'admin_email' ) );
		}
		return array( 'name' => $name, 'email' => $email );
	}

	public function send_email( $email, $source, $journey, $blocker ) {
		$key = $this->api_key();
		if ( '' === $key ) {
			update_post_meta( $this->last_lead_id, '_gd_error', 'no brevo api key configured' );
			return false;
		}

		$pdf = $this->pdf_path();
		if ( ! $pdf || ! file_exists( $pdf ) ) {
			update_post_meta( $this->last_lead_id, '_gd_error', 'guide pdf missing' );
			return false;
		}

		$sender   = $this->sender();
		$site_url = home_url( '/' );

		$body = array(
			'sender'    => $sender,
			'to'        => array( array( 'email' => $email ) ),
			'subject'   => 'Your Ghana Building Project Readiness Checklist',
			'htmlContent' => $this->email_html( $email, $source ),
			'attachment'  => array(
				array(
					'name'    => 'Gabochie-Readiness-Checklist.pdf',
					'content' => base64_encode( (string) file_get_contents( $pdf ) ),
				),
			),
			'headers'   => array( 'Reply-To' => $sender['name'] . ' <' . $sender['email'] . '>' ),
		);

		$response = wp_remote_post(
			'https://api.brevo.com/v3/smtp/email',
			array(
				'timeout' => 30,
				'headers' => array(
					'api-key'       => $key,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			update_post_meta( $this->last_lead_id, '_gd_error', 'wp_remote_post: ' . $response->get_error_message() );
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		update_post_meta( $this->last_lead_id, '_gd_error', 'brevo http ' . $code . ': ' . substr( (string) wp_remote_retrieve_body( $response ), 0, 400 ) );
		return false;
	}

	private $last_lead_id = 0;

	private function pdf_path() {
		return plugin_dir_path( __FILE__ ) . 'assets/readiness-checklist.pdf';
	}

	private function email_html( $email, $source ) {
		$whatsapp = apply_filters( 'gd_guide_whatsapp', '233268248970' );
		$site     = home_url( '/' );
		return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f4efe6;font-family:Jost,Helvetica,Arial,sans-serif;">
<div style="max-width:600px;margin:0 auto;background:#ffffff;">
  <div style="background:#072E28;padding:28px 32px;">
    <div style="font-family:Georgia,serif;font-size:20px;color:#f4efe6;letter-spacing:1px;">GABOCHIE <span style="color:#C9A96E;">DESIGN</span></div>
    <div style="color:#C9A96E;font-size:11px;letter-spacing:2px;margin-top:4px;">DESIGN . VALUE . TRANSFORMATION</div>
  </div>
  <div style="padding:32px;">
    <h1 style="font-family:Georgia,serif;font-size:24px;color:#072E28;margin:0 0 12px;">Your Readiness Checklist is here.</h1>
    <p style="font-size:15px;color:#2c3a35;line-height:1.7;margin:0 0 16px;">Thank you for requesting <strong>The Ghana Building Project Readiness Checklist</strong>. It is attached to this email — the same 40-point checklist we walk every project through before a single cedi is committed.</p>
    <p style="font-size:15px;color:#2c3a35;line-height:1.7;margin:0 0 16px;">Work through it honestly, in sequence. Every unticked box is a risk: price it, or fix it, before you build.</p>
    <div style="background:#f6f1e7;border-left:4px solid #C9A96E;padding:16px 20px;margin:0 0 20px;">
      <p style="font-size:14px;color:#3a4a44;margin:0;line-height:1.6;">Not sure where your project stands? We take projects from the first question to the final snagging pass. Bring us your checklist — we will show you exactly what is ready and what is not.</p>
    </div>
    <a href="https://wa.me/' . $whatsapp . '" style="display:inline-block;background:#C9A96E;color:#072E28;text-decoration:none;font-weight:600;padding:12px 24px;border-radius:6px;margin:0 0 8px;">Chat with us on WhatsApp</a>
    <p style="font-size:12px;color:#7c8a84;margin:8px 0 0;">' . esc_html( $site ) . ' &middot; Sakumono &middot; Tema &middot; Ghana</p>
  </div>
  <div style="background:#0A4840;padding:16px 32px;font-size:11px;color:#d8d2c4;letter-spacing:1px;text-align:center;">GABOCHIE DESIGN &middot; DESIGN . VALUE . TRANSFORMATION</div>
</div></body></html>';
	}
}

function gabochie_guide_delivery() {
	return Gabochie_Guide_Delivery::instance();
}
add_action( 'plugins_loaded', 'gabochie_guide_delivery' );
