<?php
/**
 * Gabochie Newsletter core.
 *
 * Fires on `transition_post_status` into `publish` for blog posts, reads the
 * subscribed contacts from the Gabochie CRM table, and delivers a branded HTML
 * brief to each via wp_mail (which the gabochie-email plugin routes through
 * Brevo).
 *
 * @package Gabochie_Newsletter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Publish-to-newsletter delivery for Gabochie Design.
 */
class Gabochie_Newsletter {

	/** @var Gabochie_Newsletter|null */
	private static $instance = null;

	const OPT_ENABLED = 'gabochie_newsletter_enabled';
	const OPT_DAILY   = 'gabochie_newsletter_daily_cap';
	const OPT_RUN_CAP = 'gabochie_newsletter_run_cap';
	const OPT_SECRET  = 'gabochie_newsletter_secret';
	const OPT_TRACKING = 'gabochie_newsletter_tracking';
	const OPT_UNSUBS  = 'gabochie_newsletter_unsubs';
	const OPT_LOG     = 'gabochie_newsletter_log';
	const OPT_USAGE   = 'gabochie_newsletter_usage';
	const META_SENT   = '_gab_nl_sent';
	const LOG_LIMIT   = 20;

	/**
	 * Singleton accessor.
	 *
	 * @return Gabochie_Newsletter
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire the hooks.
	 */
	private function __construct() {
		add_action( 'transition_post_status', array( $this, 'on_transition' ), 12, 3 );
		add_action( 'template_redirect', array( $this, 'maybe_handle_tracking' ) );
		add_action( 'template_redirect', array( $this, 'maybe_handle_unsub' ) );
		add_action( 'wp_login', array( $this, 'on_login' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_gabochie_nl_test', array( $this, 'handle_test_send' ) );
	}

	/**
	 * Activation defaults: master toggle off, sane caps, random unsub secret.
	 */
	public function activate() {
		if ( false === get_option( self::OPT_ENABLED, false ) ) {
			add_option( self::OPT_ENABLED, '0' );
		}
		if ( false === get_option( self::OPT_DAILY, false ) ) {
			add_option( self::OPT_DAILY, 250 );
		}
		if ( false === get_option( self::OPT_RUN_CAP, false ) ) {
			add_option( self::OPT_RUN_CAP, 200 );
		}
		if ( false === get_option( self::OPT_SECRET, false ) ) {
			add_option( self::OPT_SECRET, wp_generate_password( 48, false ) );
		}
		if ( false === get_option( self::OPT_TRACKING, false ) ) {
			add_option( self::OPT_TRACKING, '1' );
		}
		if ( false === get_option( self::OPT_UNSUBS, false ) ) {
			add_option( self::OPT_UNSUBS, array() );
		}
		if ( false === get_option( self::OPT_LOG, false ) ) {
			add_option( self::OPT_LOG, array() );
		}
		if ( false === get_option( self::OPT_USAGE, false ) ) {
			add_option( self::OPT_USAGE, array() );
		}
	}

	/**
	 * React to a blog post becoming published (new publish or draft  promoted).
	 *
	 * @param string  $new_status
	 * @param string  $old_status
	 * @param WP_Post $post
	 */
	public function on_transition( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		$obj = get_post( $post );
		if ( ! $obj || 'post' !== $obj->post_type ) {
			return;
		}
		if ( '1' !== get_option( self::OPT_ENABLED, '0' ) ) {
			return;
		}
		if ( get_post_meta( $obj->ID, self::META_SENT, true ) ) {
			return;
		}
		$this->send( $obj );
	}

	/**
	 * Send the announcement for one post to all subscribed CRM contacts.
	 *
	 * @param WP_Post $post
	 */
	public function send( $post ) {
		$receivers = $this->subscribers();
		if ( empty( $receivers ) ) {
			$this->log( $post, 0, 0, 'no-subscribers' );
			update_post_meta( $post->ID, self::META_SENT, gmdate( 'Y-m-d H:i:s' ) . '|no-subscribers' );
			return false;
		}

		$run_cap = max( 1, (int) get_option( self::OPT_RUN_CAP, 200 ) );
		$subject = $this->subject( $post );
		$sent    = 0;
		$errors  = 0;

		foreach ( $receivers as $r ) {
			if ( $sent >= $run_cap ) {
				break;
			}
			$email = strtolower( trim( sanitize_email( (string) $r['email'] ) ) );
			if ( '' === $email || ! is_email( $email ) || $this->is_unsubscribed( $email ) ) {
				continue;
			}
			if ( ! $this->tick_usage() ) {
				$this->log( $post, $sent, $errors, 'daily-cap' );
				break;
			}
			$name = ( isset( $r['name'] ) && '' !== trim( (string) $r['name'] ) ) ? trim( (string) $r['name'] ) : '';
			$body = $this->html_email( $post, $email );
			$ok   = $this->deliver( $email, $name, $subject, $body );
			if ( $ok ) {
				++$sent;
			} else {
				++$errors;
			}
		}

		update_post_meta( $post->ID, self::META_SENT, gmdate( 'Y-m-d H:i:s' ) . '|sent:' . $sent . ',errors:' . $errors );
		$this->log( $post, $sent, $errors );
		return $sent > 0;
	}

	/**
	 * Active Gabochie CRM contacts (status subscribed), excluding known unsubs.
	 *
	 * @return array<int,array{email:string,name:string}>
	 */
	public function subscribers() {
		if ( ! class_exists( 'Gabochie_Crm' ) ) {
			return array();
		}
		$out = array();
		foreach ( Gabochie_Crm::subscribers() as $row ) {
			$name  = trim( (string) $row['first_name'] . ' ' . (string) $row['last_name'] );
			$out[] = array(
				'email' => $row['email'],
				'name'  => $name,
			);
		}
		return $out;
	}

	/**
	 * Send one email via wp_mail (gabochie-email routes it to Brevo).
	 *
	 * @param string $email
	 * @param string $name
	 * @param string $subject
	 * @param string $html
	 * @return bool
	 */
	private function deliver( $email, $name, $subject, $html ) {
		$to = ( '' !== $name ) ? $name . ' <' . $email . '>' : $email;
		$h  = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( '' !== (string) get_option( 'gabochie_email_api_key', '' ) ) {
			$h[] = 'Reply-To: no-reply@gabochiedesign.com';
		} else {
			$h[] = 'Reply-To: ' . sanitize_email( get_option( 'admin_email', '' ) );
		}
		$sent = wp_mail( $to, $subject, $html, $h );
		usleep( 150000 );
		return $sent;
	}

	/**
	 * Branded HTML brief for one recipient.
	 *
	 * @param WP_Post $post
	 * @param string  $email
	 * @return string
	 */
	private function html_email( $post, $email ) {
		$perma = get_permalink( $post->ID );
		$perma = $perma ? $perma : home_url( '/' );
		$title = htmlspecialchars( get_the_title( $post ), ENT_QUOTES, 'UTF-8' );

		$track   = $this->tracking_enabled();
		$click_a = $track ? $this->click_url( $perma, $email ) : $perma;
		$pixel   = $track ? $this->open_pixel( $email ) : '';

		$image = get_the_post_thumbnail_url( $post, 'large' );
		if ( ! $image ) {
			$image = '';
		}
		$excerpt = trim( (string) $post->post_excerpt );
		if ( '' === $excerpt ) {
			$excerpt = wp_strip_all_tags( (string) $post->post_content );
		}
		$excerpt = wp_html_excerpt( $excerpt, 320 );
		$excerpt = htmlspecialchars( $excerpt, ENT_QUOTES, 'UTF-8' );

		$unsub = esc_url(
			add_query_arg(
				array(
					'gabochie-nl' => 'unsub',
					'e'           => rawurlencode( $email ),
					'k'           => $this->unsub_hash( $email ),
					'n'           => wp_create_nonce( 'gab-nl-unsub-' . $email ),
				),
				home_url( '/' )
			)
		);

		$img_html = $image ? '<p style="margin:0 0 18px 0;"><img src="' . esc_url( $image ) . '" alt="' . $title . '" style="display:block;width:100%;max-width:560px;height:auto;border-radius:10px;"/></p>' : '';

		return '<div style="margin:0;padding:0;background:#f4f2ee;">
<div style="max-width:600px;margin:0 auto;padding:24px;font-family:Helvetica,Arial,sans-serif;color:#16232e;">
<p style="margin:0 0 18px 0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#a8843c;">New from Gabochie Design</p>
<div style="background:#ffffff;border:1px solid #e4e0d8;border-radius:12px;padding:28px;">
<p style="margin:0 0 6px 0;font-size:13px;color:#6b7681;">The Ghana Development Intelligence Newsletter</p>
' . $img_html . '
<h1 style="margin:0 0 14px 0;font-size:24px;line-height:1.3;color:#16232e;"><a href="' . $click_a . '" style="color:#0e4d45;text-decoration:none;">' . $title . '</a></h1>
<p style="margin:0 0 22px 0;font-size:15px;line-height:1.6;color:#3a4652;">' . $excerpt . '&hellip;</p>
<p style="margin:0 0 10px 0;"><a href="' . $click_a . '" style="display:inline-block;background:#0e4d45;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-size:14px;">Read the full article</a></p>
<p style="margin:0 0 26px 0;"><a href="' . esc_url( home_url( '/project-recovery/' ) ) . '" style="color:#0e4d45;font-size:13px;">Stuck with a half-built house or stalled project? See the project recovery route &rarr;</a></p>
</div>
<p style="margin:20px 0 0 0;font-size:11px;line-height:1.5;color:#8a929b;">Gabochie Design &middot; Development made understandable, measurable, investable. &middot; <a href="' . esc_url( home_url( '/' ) ) . '" style="color:#8a929b;">gabochiedesign.com</a></p>
<p style="margin:0 0 26px 0;font-size:11px;color:#8a929b;"><a href="' . $unsub . '" style="color:#8a929b;">Unsubscribe from future updates</a></p>
</div>
' . $pixel . '
</div>';
	}

	/**
	 * Subject line for one post.
	 *
	 * @param WP_Post $post
	 * @return string
	 */
	private function subject( $post ) {
		$title = trim( wp_strip_all_tags( get_the_title( $post ) ) );
		return 'Gabochie Design: ' . ( strlen( $title ) > 60 ? substr( $title, 0, 57 ) . '...' : $title );
	}

	/**
	 * Deterministic per-email unsub token.
	 *
	 * @param string $email
	 * @return string
	 */
	private function unsub_hash( $email ) {
		return md5( $email . ':' . get_option( self::OPT_SECRET, '' ) );
	}

	/**
	 * Whether open/click tracking is enabled.
	 *
	 * @return bool
	 */
	private function tracking_enabled() {
		return '1' === get_option( self::OPT_TRACKING, '1' );
	}

	/**
	 * Deterministic per-email tracking token (same secret as unsub).
	 *
	 * @param string $email
	 * @return string
	 */
	private function track_hash( $email ) {
		return md5( $email . ':' . get_option( self::OPT_SECRET, '' ) );
	}

	/**
	 * Open-tracking 1x1 pixel URL for one recipient.
	 *
	 * @param string $email
	 * @return string
	 */
	private function open_pixel( $email ) {
		$src = esc_url(
			home_url( '/' ) . '?gab-nl=open&e=' . rawurlencode( $email ) . '&k=' . $this->track_hash( $email )
		);
		return '<img src="' . $src . '" alt="" width="1" height="1" style="display:block;width:1px;height:1px;border:0;" />';
	}

	/**
	 * Click-tracking redirect URL for one recipient.
	 *
	 * @param string $target Absolute destination URL (same host).
	 * @param string $email
	 * @return string
	 */
	private function click_url( $target, $email ) {
		return esc_url_raw(
			home_url( '/' ) . '?gab-nl=click&e=' . rawurlencode( $email ) . '&k=' . $this->track_hash( $email ) . '&u=' . rawurlencode( $target )
		);
	}

	/**
	 * Open (+click) tracking endpoint (?gab-nl=open|click&e=..&k=..[&u=..]).
	 * Validates the per-email token, stamps last_engaged_at on the CRM contact
	 * (feeds engaged_30d) and serves the pixel / 302 redirect.
	 */
	public function maybe_handle_tracking() {
		if ( is_admin() ) {
			return;
		}
		$action = sanitize_key( wp_unslash( $_GET['gab-nl'] ?? '' ) );
		if ( ! in_array( $action, array( 'open', 'click' ), true ) ) {
			return;
		}
		$email = strtolower( trim( sanitize_email( wp_unslash( $_GET['e'] ?? '' ) ) ) );
		$key   = (string) preg_replace( '/[^a-f0-9]/', '', sanitize_key( wp_unslash( $_GET['k'] ?? '' ) ) );
		if ( '' === $email || ! is_email( $email ) || ! hash_equals( $this->track_hash( $email ), $key ) ) {
			status_header( 404 );
			nocache_headers();
			wp_die( 'Invalid tracking request.', 'Gabochie Design', array( 'response' => 404 ) );
		}

		$this->stamp( $email );
		$this->no_cache();

		if ( 'click' === $action ) {
			$target = esc_url_raw( wp_unslash( $_GET['u'] ?? '' ) );
			if ( '' !== $target ) {
				wp_safe_redirect( $target, 302 );
				exit;
			}
			status_header( 404 );
		}

		// Open pixel: 1x1 transparent GIF.
		$gif = base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true );
		status_header( 200 );
		header( 'Content-Type: image/gif' );
		header( 'Content-Length: ' . strlen( $gif ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DiscouragedPHPFunctions
		echo $gif;
		exit;
	}

	/**
	 * Stamp last_engaged_at on a contact, throttled to once per hour so a mail
	 * client re-loading the pixel or a drive-by URL cannot inflate the KPI.
	 *
	 * @param string $email
	 * @return bool
	 */
	private function stamp( $email ) {
		if ( ! class_exists( 'Gabochie_Crm' ) || ! Gabochie_Crm::ready() ) {
			return false;
		}
		global $wpdb;
		$last = $wpdb->get_var(
			$wpdb->prepare( 'SELECT last_engaged_at FROM %i WHERE email = %s', Gabochie_Crm::table(), $email )
		);
		if ( null !== $last && $last ) {
			$ts = strtotime( (string) $last );
			if ( $ts && ( time() - $ts ) < HOUR_IN_SECONDS ) {
				return false;
			}
		}
		return Gabochie_Crm::stamp_engagement( $email );
	}

	/**
	 * Keep tracking requests out of any page cache (LiteSpeed + core).
	 */
	private function no_cache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		do_action( 'litespeed_control_set_nocache', 'gab-nl-tracking' );
		nocache_headers();
	}

	/**
	 * A contact logging into the site is a strong engagement signal.
	 *
	 * @param string  $user_login
	 * @param WP_User $user
	 */
	public function on_login( $user_login, $user ) {
		if ( ! $user instanceof WP_User || '' === $user->user_email ) {
			return;
		}
		$this->stamp( strtolower( trim( sanitize_email( $user->user_email ) ) ) );
	}

	/**
	 * One-click unsubscribe route (?gabochie-nl=unsub&e=..&k=..). Marks the
	 * contact unsubscribed in the Gabochie CRM table and serves a confirmation
	 * page.
	 */
	public function maybe_handle_unsub() {
		if ( 'unsub' !== sanitize_key( wp_unslash( $_GET['gabochie-nl'] ?? '' ) ) || is_admin() ) {
			return;
		}
		$email = strtolower( trim( sanitize_email( wp_unslash( $_GET['e'] ?? '' ) ) ) );
		$key   = (string) preg_replace( '/[^a-f0-9]/', '', sanitize_key( wp_unslash( $_GET['k'] ?? '' ) ) );
		$nonce = (string) sanitize_key( wp_unslash( $_GET['n'] ?? '' ) );
		if (
			'' === $email ||
			! is_email( $email ) ||
			! wp_verify_nonce( $nonce, 'gab-nl-unsub-' . $email ) ||
			! hash_equals( $this->unsub_hash( $email ), $key )
		) {
			status_header( 404 );
			nocache_headers();
			wp_die( 'Invalid unsubscribe request.', 'Gabochie Design', array( 'response' => 404 ) );
		}
		$this->stamp( $email );
		$this->mark_unsubscribed( $email );
		nocache_headers();
		echo '<!doctype html><html><head><meta charset="utf-8"><title>Unsubscribed</title><style>body{font-family:Helvetica,Arial,sans-serif;background:#f4f2ee;color:#16232e;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;} .box{max-width:440px;padding:32px;background:#fff;border:1px solid #e4e0d8;border-radius:12px;} h1{font-size:20px;color:#0e4d45;} p{line-height:1.6;} a{color:#0e4d45;}</style></head><body><div class="box"><h1>You are unsubscribed</h1><p>You have been removed from the Gabochie Design newsletter. No further content updates will be emailed to this address.</p><p><a href="' . esc_url( home_url( '/' ) ) . '">Return to gabochiedesign.com</a></p></div></body></html>';
		exit;
	}

	/**
	 * Persist an unsubscribe (own map + Gabochie CRM status).
	 *
	 * @param string $email
	 */
	private function mark_unsubscribed( $email ) {
		$unsubs           = get_option( self::OPT_UNSUBS, array() );
		$unsubs[ $email ] = current_time( 'mysql' );
		update_option( self::OPT_UNSUBS, $unsubs );

		if ( class_exists( 'Gabochie_Crm' ) ) {
			Gabochie_Crm::set_status( $email, 'unsubscribed' );
		}
	}

	/**
	 * Whether the address is on our own unsubscribe list.
	 *
	 * @param string $email
	 * @return bool
	 */
	private function is_unsubscribed( $email ) {
		$unsubs = get_option( self::OPT_UNSUBS, array() );
		return isset( $unsubs[ $email ] );
	}

	/**
	 * Consume one unit of the daily send budget. False when the cap is hit.
	 *
	 * @return bool
	 */
	private function tick_usage() {
		$cap   = max( 1, (int) get_option( self::OPT_DAILY, 250 ) );
		$usage = (array) get_option( self::OPT_USAGE, array() );
		$day   = gmdate( 'Y-m-d' );
		$n     = isset( $usage[ $day ] ) ? (int) $usage[ $day ] : 0;
		if ( $n >= $cap ) {
			return false;
		}
		$usage[ $day ] = $n + 1;
		update_option( self::OPT_USAGE, $usage );
		return true;
	}

	/**
	 * Append to the bounded send log.
	 *
	 * @param WP_Post $post
	 * @param int     $sent
	 * @param int     $errors
	 * @param string  $note
	 */
	private function log( $post, $sent, $errors, $note = '' ) {
		$log   = (array) get_option( self::OPT_LOG, array() );
		$entry = array(
			'time'  => current_time( 'mysql' ),
			'id'    => (int) $post->ID,
			'title' => trim( wp_strip_all_tags( get_the_title( $post ) ) ),
			'sent'  => (int) $sent,
			'errs'  => (int) $errors,
			'note'  => $note,
		);
		array_unshift( $log, $entry );
		update_option( self::OPT_LOG, array_slice( $log, 0, self::LOG_LIMIT ) );
	}

	/**
	 * Settings page under Settings → Newsletter.
	 */
	public function add_admin_menu() {
		add_options_page(
			'Gabochie Newsletter',
			'Newsletter',
			'manage_options',
			'gabochie-newsletter',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Register the options API fields.
	 */
	public function register_settings() {
		register_setting(
			'gabochie-newsletter',
			self::OPT_ENABLED,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_enabled' ),
			)
		);
		register_setting(
			'gabochie-newsletter',
			self::OPT_DAILY,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			)
		);
		register_setting(
			'gabochie-newsletter',
			self::OPT_RUN_CAP,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			)
		);
		register_setting(
			'gabochie-newsletter',
			self::OPT_TRACKING,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_enabled' ),
			)
		);
	}

	/**
	 * @param mixed $v
	 * @return string
	 */
	public function sanitize_enabled( $v ) {
		return ( '1' === (string) $v ) ? '1' : '0';
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$count = count( $this->subscribers() );
		$log   = (array) get_option( self::OPT_LOG, array() );
		$usage = (array) get_option( self::OPT_USAGE, array() );
		$today = gmdate( 'Y-m-d' );
		$used  = isset( $usage[ $today ] ) ? (int) $usage[ $today ] : 0;
		?>
		<div class="wrap">
			<h1>Gabochie Newsletter</h1>
			<p>Emails subscribed contacts as soon as a blog post is published. Recipients are read from the Gabochie CRM subscribers table (<code>gab_contacts</code>); delivery goes through <em>gabochie-email</em> (Brevo).</p>
			<hr>
			<form method="post" action="options.php">
				<?php settings_fields( 'gabochie-newsletter' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="gabochie-nl-en">Enable on publish</label></th>
						<td>
							<input type="checkbox" id="gabochie-nl-en" name="<?php echo esc_attr( self::OPT_ENABLED ); ?>" value="1" <?php checked( get_option( self::OPT_ENABLED, '0' ), '1' ); ?>>
							<p class="description">Sends the newsletter for every newly published blog post (including posts the auto-blog publishes).</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gabochie-nl-daily">Daily send cap</label></th>
						<td><input type="number" id="gabochie-nl-daily" name="<?php echo esc_attr( self::OPT_DAILY ); ?>" min="1" value="<?php echo esc_attr( (string) get_option( self::OPT_DAILY, 250 ) ); ?>"><p class="description">Hard stop per calendar day (keep under the Brevo plan limit; free = 300/day).</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="gabochie-nl-run">Max per run</label></th>
						<td><input type="number" id="gabochie-nl-run" name="<?php echo esc_attr( self::OPT_RUN_CAP ); ?>" min="1" value="<?php echo esc_attr( (string) get_option( self::OPT_RUN_CAP, 200 ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="gabochie-nl-track">Track opens &amp; clicks</label></th>
						<td>
							<input type="checkbox" id="gabochie-nl-track" name="<?php echo esc_attr( self::OPT_TRACKING ); ?>" value="1" <?php checked( get_option( self::OPT_TRACKING, '1' ), '1' ); ?>>
							<p class="description">Adds an open-tracking pixel and click-tracking redirect to every newsletter email. Each open/click stamps <code>last_engaged_at</code> on the contact (<code>gab_contacts</code>) — this is what feeds the Command Center <em>engaged_30d</em> KPI.</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Save settings' ); ?>
			</form>
			<hr>
			<h2>Test send</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="gabochie_nl_test">
				<?php wp_nonce_field( 'gabochie_nl_test', 'gabochie_nl_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="gabochie-nl-test-email">Send a copy to</label></th>
						<td><input type="email" id="gabochie-nl-test-email" name="email" value="<?php echo esc_attr( get_option( 'admin_email', '' ) ); ?>"><p class="description">Delivers the newsletter for the most recent published post to this address only — no subscribers are emailed.</p></td>
					</tr>
				</table>
				<?php submit_button( 'Send test' ); ?>
			</form>
			<hr>
			<h2>Status</h2>
			<p>Subscribed contacts: <strong><?php echo esc_html( (string) $count ); ?></strong>&ensp;|&ensp;Sends today: <strong><?php echo esc_html( (string) $used ); ?></strong> / <?php echo esc_html( (string) get_option( self::OPT_DAILY, 250 ) ); ?></p>
			<?php if ( empty( $log ) ) : ?>
				<p><em>No sends logged yet.</em></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:720px;">
					<thead><tr><th>Time</th><th>Post</th><th>Sent</th><th>Errors</th><th>Note</th></tr></thead>
					<tbody>
					<?php foreach ( $log as $row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $row['time'] ); ?></td>
							<td><a href="<?php echo esc_url( get_edit_post_link( (int) $row['id'] ) ? admin_url( 'post.php?post=' . (int) $row['id'] . '&action=edit' ) : '#' ); ?>"><?php echo esc_html( (string) $row['title'] ); ?></a></td>
							<td><?php echo esc_html( (string) $row['sent'] ); ?></td>
							<td><?php echo esc_html( (string) $row['errs'] ); ?></td>
							<td><?php echo esc_html( (string) $row['note'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * admin-post test-send handler.
	 */
	public function handle_test_send() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized.', 403 );
		}
		check_admin_referer( 'gabochie_nl_test', 'gabochie_nl_nonce' );
		$email = strtolower( trim( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) ) );
		$url   = admin_url( 'options-general.php?page=gabochie-newsletter' );
		if ( '' === $email || ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'gabochie-nl-msg', 'bad-email', $url ) );
			exit;
		}
		$post = get_posts(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);
		if ( empty( $post ) ) {
			wp_safe_redirect( add_query_arg( 'gabochie-nl-msg', 'no-post', $url ) );
			exit;
		}
		$body = $this->html_email( $post[0], $email );
		$ok   = $this->deliver( $email, '', $this->subject( $post[0] ), $body );
		wp_safe_redirect( add_query_arg( 'gabochie-nl-msg', $ok ? 'sent' : 'failed', $url ) );
		exit;
	}
}