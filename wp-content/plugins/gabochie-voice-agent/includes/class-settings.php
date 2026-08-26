<?php
/**
 * Settings screen: Settings -> Voice Agent.
 * Holds the Gemini API key (falls back to GhBC key if empty).
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
			'gabochie_voice_agent_gemini_key',
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

		$ghbc_key = get_option( 'ghbc_llm_key', '' );
		$ghbc_key_set = ! empty( $ghbc_key );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Gabochie Voice Agent', 'gabochie-voice-agent' ); ?></h1>

			<div class="card" style="max-width: 600px; padding: 20px; margin-bottom: 20px;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'How it works', 'gabochie-voice-agent' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Visitors click the microphone button on your site', 'gabochie-voice-agent' ); ?></li>
					<li><?php esc_html_e( 'They speak in English or Twi-nglish (casual Ghanaian English)', 'gabochie-voice-agent' ); ?></li>
					<li><?php esc_html_e( 'The agent responds with building advice in the same language', 'gabochie-voice-agent' ); ?></li>
					<li><?php esc_html_e( 'Qualifying leads are captured and you get notified by email', 'gabochie-voice-agent' ); ?></li>
				</ol>
				<p><strong><?php esc_html_e( 'Requirements:', 'gabochie-voice-agent' ); ?></strong></p>
				<ul>
					<li><?php esc_html_e( 'Works in Chrome, Edge, and Safari (browser speech recognition)', 'gabochie-voice-agent' ); ?></li>
					<li><?php esc_html_e( 'Requires HTTPS (secure context for microphone access)', 'gabochie-voice-agent' ); ?></li>
					<li><?php esc_html_e( 'Gemini API key for AI responses', 'gabochie-voice-agent' ); ?></li>
				</ul>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'gabochie_voice' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="gabochie_voice_agent_gemini_key"><?php esc_html_e( 'Gemini API Key', 'gabochie-voice-agent' ); ?></label>
						</th>
						<td>
							<input id="gabochie_voice_agent_gemini_key" name="gabochie_voice_agent_gemini_key" type="password" class="regular-text"
								value="<?php echo esc_attr( gabochie_voice_agent_gemini_key() ); ?>"
								placeholder="AIza..." />
							<?php if ( $ghbc_key_set ) : ?>
								<p class="description" style="color: #00a32a;">
									<?php esc_html_e( 'Using GhBC LLM key as fallback. You can override it here.', 'gabochie-voice-agent' ); ?>
								</p>
							<?php else : ?>
								<p class="description">
									<?php esc_html_e( 'Get a key from https://aistudio.google.com/apikey', 'gabochie-voice-agent' ); ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
