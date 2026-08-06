<?php
/**
 * Settings screen: Settings -> Voice Agent.
 * Holds the Worker host URL and the shared intake key. Both can be overridden
 * with constants in wp-config.php (GABOCHIE_VOICE_AGENT_HOST / _KEY).
 *
 * @package Gabochie_Voice_Agent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Gabochie_Voice_Settings {

	/** @var Gabochie_Voice_Settings|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_menu() {
		add_options_page(
			__( 'Voice Agent', 'gabochie-voice-agent' ),
			__( 'Voice Agent', 'gabochie-voice-agent' ),
			'manage_options',
			'gabochie-voice-agent',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'gabochie_voice',
			'gabochie_voice_agent_host',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		register_setting(
			'gabochie_voice',
			'gabochie_voice_agent_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Gabochie Voice Agent', 'gabochie-voice-agent' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'gabochie_voice' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="gabochie_voice_agent_host"><?php esc_html_e( 'Worker host', 'gabochie-voice-agent' ); ?></label>
						</th>
						<td>
							<input id="gabochie_voice_agent_host" name="gabochie_voice_agent_host" type="url" class="regular-text"
								value="<?php echo esc_attr( gabochie_voice_agent_host() ); ?>"
								placeholder="https://gabochie-voice-agent.example.workers.dev" />
							<p class="description">
								<?php esc_html_e( 'The Cloudflare Worker hosting the voice agent. The widget appears site-wide once this is set.', 'gabochie-voice-agent' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="gabochie_voice_agent_key"><?php esc_html_e( 'Shared key', 'gabochie-voice-agent' ); ?></label>
						</th>
						<td>
							<input id="gabochie_voice_agent_key" name="gabochie_voice_agent_key" type="text" class="regular-text"
								value="<?php echo esc_attr( gabochie_voice_agent_key() ); ?>" />
							<p class="description">
								<?php esc_html_e( 'The Worker sends this as the X-Gabochie-Voice-Key header. It must match the VOICE_AGENT_KEY secret set on the Worker.', 'gabochie-voice-agent' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<p>
				<?php esc_html_e( 'Override both in wp-config.php to keep them out of the database:', 'gabochie-voice-agent' ); ?>
				<code>define( 'GABOCHIE_VOICE_AGENT_HOST', 'https://…' );</code>
				<code>define( 'GABOCHIE_VOICE_AGENT_KEY', '…' );</code>
			</p>
		</div>
		<?php
	}
}
