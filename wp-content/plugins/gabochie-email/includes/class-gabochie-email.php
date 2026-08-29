<?php
/**
 * Gabochie Email — Brevo Transactional API sender.
 *
 * Intercepts wp_mail() at the pre_wp_mail short-circuit filter and delivers
 * through the Brevo /smtp/email endpoint. On any Brevo failure the filter
 * returns null so the core PHPMailer path sends as a fallback — email
 * delivery can never silently break.
 *
 * @package Gabochie_Email
 */

defined( 'ABSPATH' ) || exit;

final class Gabochie_Email {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'pre_wp_mail', array( $this, 'route_through_brevo' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_gabochie_email_test', array( $this, 'ajax_test' ) );
	}

	/**
		 * Intercept wp_mail() before core. Return non-null to short-circuit.
		 *
		 * @param mixed $carry Short-circuit value (null unless another filter set it).
		 * @param array $atts {
		 *     Mail atoms: to, subject, message, headers, attachments.
		 * }
		 * @return mixed True on Brevo success, null to let core send.
		 */
	public function route_through_brevo( $carry, $atts ) {
		$api_key = $this->get_api_key();
		if ( empty( $api_key ) ) {
			return $carry;
		}

		$prepared = $this->prepare_mail( $atts );
		if ( is_wp_error( $prepared ) ) {
			return $carry;
		}

		$payload = $this->build_payload( $prepared );
		if ( is_wp_error( $payload ) ) {
			return $carry;
		}

		$response = wp_remote_post(
			GABOCHIE_EMAIL_API . '/smtp/email',
			array(
				'timeout'     => 30,
				'redirection' => 5,
				'headers'     => array(
					'api-key'      => $api_key,
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
					'User-Agent'   => 'gabochie-design/' . get_bloginfo( 'version' ),
				),
				'body'        => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log( 'Brevo send failed (network): ' . $response->get_error_message() );
			return $carry;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code && 201 !== $code && 202 !== $code ) {
			$this->log( 'Brevo send failed (HTTP ' . $code . '): ' . wp_remote_retrieve_body( $response ) );
			return $carry;
		}

		return true;
	}

	/**
	 * Normalise the compacted wp_mail arguments into a usable array.
	 *
	 * @param array $atts Compacted mail atoms.
	 * @return array|WP_Error
	 */
	private function prepare_mail( $atts ) {
		if ( empty( $atts['to'] ) || empty( $atts['subject'] ) || '' === $atts['message'] ) {
			return new WP_Error( 'gabochie_email_empty', 'Missing to/subject/message.' );
		}

		$atts['to']      = is_array( $atts['to'] ) ? $atts['to'] : array( $atts['to'] );
		$atts['headers'] = empty( $atts['headers'] ) ? array() : $atts['headers'];
		if ( ! is_array( $atts['headers'] ) ) {
			$atts['headers'] = preg_split( "/\r\n|\n|\r/", (string) $atts['headers'] );
		}
		$atts['headers'] = array_values( array_filter( $atts['headers'], array( $this, 'nonempty' ) ) );

		return $atts;
	}

	/**
	 * Build the Brevo /smtp/email JSON payload from prepared mail atoms.
	 *
	 * @param array $prepared Normalised mail atoms.
	 * @return array|WP_Error
	 */
	private function build_payload( $prepared ) {
		$to    = array();
		$cc    = array();
		$bcc   = array();
		$reply = '';
		$from  = '';

		foreach ( $prepared['to'] as $recipient ) {
			$parsed = $this->parse_address( $recipient );
			if ( $parsed ) {
				$to[] = $parsed;
			}
		}
		if ( empty( $to ) ) {
			return new WP_Error( 'gabochie_email_no_to', 'No valid recipients.' );
		}

		foreach ( $prepared['headers'] as $header ) {
			$header = trim( $header );
			if ( '' === $header || false === strpos( $header, ':' ) ) {
				continue;
			}
			list( $name, $value ) = explode( ':', $header, 2 );
			$name                 = strtolower( trim( $name ) );
			$value                = trim( $value );

			switch ( $name ) {
				case 'from':
					$from = $value;
					break;
				case 'cc':
					foreach ( explode( ',', $value ) as $addr ) {
						$parsed = $this->parse_address( $addr );
						if ( $parsed ) {
							$cc[] = $parsed;
						}
					}
					break;
				case 'bcc':
					foreach ( explode( ',', $value ) as $addr ) {
						$parsed = $this->parse_address( $addr );
						if ( $parsed ) {
							$bcc[] = $parsed;
						}
					}
					break;
				case 'reply-to':
					$reply = $value;
					break;
			}
		}

		$sender  = $this->sender_from( $from );
		$payload = array(
			'sender'      => $sender,
			'to'          => $to,
			'subject'     => $prepared['subject'],
			'htmlContent' => $this->to_html( $prepared['message'] ),
		);

		if ( ! empty( $cc ) ) {
			$payload['cc'] = $cc;
		}
		if ( ! empty( $bcc ) ) {
			$payload['bcc'] = $bcc;
		}
		if ( '' !== $reply ) {
			$parsed = $this->parse_address( $reply );
			if ( $parsed ) {
				$payload['replyTo'] = $parsed;
			}
		}
		if ( ! empty( $prepared['attachments'] ) ) {
			$attachments = $this->attachment_structs( (array) $prepared['attachments'] );
			if ( ! empty( $attachments ) ) {
				$payload['attachment'] = $attachments;
			}
		}

		return $payload;
	}

	/**
	 * Split "Name <email>" and validate the email portion.
	 *
	 * @param string $address Raw address.
	 * @return array{email:string,name?:string}|false
	 */
	private function parse_address( $address ) {
		$address = trim( (string) $address );
		if ( '' === $address ) {
			return false;
		}

		$name  = '';
		$email = $address;
		if ( preg_match( '/(.*)<(.+)>/', $address, $m ) ) {
			$name  = trim( trim( $m[1] ), '"' );
			$email = trim( $m[2] );
		}

		$email = filter_var( $email, FILTER_VALIDATE_EMAIL );
		if ( false === $email ) {
			return false;
		}

		if ( '' !== $name ) {
			return array(
				'email' => $email,
				'name'  => $name,
			);
		}
		return array( 'email' => $email );
	}

	/**
	 * Decide the Brevo sender. Prefer the parsed From header (as sent by
	 * PMPro, WooCommerce, etc.), else wp_mail_from/wp_mail_from_name filters,
	 * else admin email/name.
	 *
	 * @param string $from Header From value (already trimmed) if present.
	 * @return array{email:string,name?:string}
	 */
	private function sender_from( $from ) {
		if ( '' !== $from ) {
			$parsed = $this->parse_address( $from );
			if ( $parsed ) {
				return $parsed;
			}
		}

		$from_email = apply_filters( 'wp_mail_from', get_option( 'admin_email' ) );
		$from_name  = apply_filters( 'wp_mail_from_name', get_bloginfo( 'name' ) );

		$sender = array( 'email' => $from_email );
		if ( '' !== trim( $from_name ) ) {
			$sender['name'] = trim( $from_name );
		}
		return $sender;
	}

	/**
	 * Convert a plain-text message to an HTML body like WP does.
	 *
	 * @param string $message Raw message body.
	 * @return string HTML content.
	 */
	private function to_html( $message ) {
		return nl2br( esc_html( (string) $message ) );
	}

	/**
	 * Build Brevo attachment structs from local paths.
	 *
	 * @param array $attachments Absolute file paths.
	 * @return array
	 */
	private function attachment_structs( $attachments ) {
		$structs = array();
		foreach ( $attachments as $path ) {
			$path = (string) $path;
			if ( '' === $path || ! is_file( $path ) ) {
				continue;
			}
			$content = file_get_contents( $path );
			if ( false === $content ) {
				continue;
			}
			$structs[] = array(
				'name'    => basename( $path ),
				'content' => base64_encode( $content ),
			);
		}
		return $structs;
	}

	/**
	 * Filter callback: keep non-empty trimmed values.
	 *
	 * @param mixed $value Value to check.
	 * @return bool
	 */
	public function nonempty( $value ) {
		return '' !== trim( (string) $value );
	}

	/**
	 * Get the configured Brevo API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		return (string) get_option( GABOCHIE_EMAIL_OPTION, '' );
	}

	/**
	 * Diagnostic log — silently dropped unless WP_DEBUG.
	 *
	 * @param string $message Log line.
	 */
	private function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			error_log( '[gabochie-email] ' . $message );
		}
	}

	/* ================================================================== */
	/*  Settings page                                                      */
	/* ================================================================== */

	public function admin_menu() {
		add_options_page(
			'Gabochie Email',
			'Gabochie Email',
			'manage_options',
			'gabochie-email',
			array( $this, 'settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'gabochie_email', GABOCHIE_EMAIL_OPTION, array( 'type' => 'string' ) );
	}

	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$key = $this->get_api_key();
		?>
		<div class="wrap">
			<h1>Gabochie Email</h1>
			<p>Routes all <code>wp_mail()</code> email through the Brevo Transactional API. If Brevo is unavailable the email falls back to the core WordPress mailer, so delivery never silently breaks.</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'gabochie_email' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gabochie_email_api_key">Brevo API key</label></th>
						<td>
							<input type="password" name="gabochie_email_api_key" id="gabochie_email_api_key" value="<?php echo esc_attr( $key ); ?>" class="regular-text" autocomplete="off" />
							<p class="description">Start with <code>xkeysib-</code>. Get it from Brevo → Settings → API Keys. Stored in the WordPress options table — never committed to the repository.</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Save API key' ); ?>
			</form>
			<hr />
			<h2>Test</h2>
			<p>Send a test email to the site admin address through Brevo.</p>
			<p>
				<button type="button" class="button button-secondary" id="gabochie-email-test">
					Send test email to <?php echo esc_html( get_option( 'admin_email' ) ); ?>
				</button>
				<span id="gabochie-email-test-result" style="margin-left:10px;"></span>
			</p>
		</div>
		<script>
		(function () {
			document.getElementById('gabochie-email-test').addEventListener('click', function (e) {
				var btn = e.currentTarget;
				var out = document.getElementById('gabochie-email-test-result');
				btn.disabled = true;
				out.textContent = 'Sending…';
				fetch(ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: 'action=gabochie_email_test&_ajax_nonce=' + encodeURIComponent(<?php echo wp_json_encode( wp_create_nonce( 'gabochie_email_test' ) ); ?>)
				}).then(function (r) { return r.json(); }).then(function (data) {
					btn.disabled = false;
					out.textContent = data && data.data ? data.data : 'Unknown response.';
				}).catch(function () {
					btn.disabled = false;
					out.textContent = 'Request failed.';
				});
			});
		})();
		</script>
		<?php
	}

	public function ajax_test() {
		check_ajax_referer( 'gabochie_email_test' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$admin_email = get_option( 'admin_email' );
		$sent        = wp_mail(
			$admin_email,
			__( '[Gabochie Email] test via Brevo', 'gabochie-email' ),
			__( 'If you can read this, transactional email is flowing through Brevo.', 'gabochie-email' )
		);

		wp_send_json_success( $sent ? __( 'Sent via Brevo successfully.', 'gabochie-email' ) : __( 'wp_mail returned false — check the debug log.', 'gabochie-email' ) );
	}
}