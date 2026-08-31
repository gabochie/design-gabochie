<?php
/**
 * Gabochie Cold Outreach — send engine.
 *
 * WP-Cron driver. Hard per-channel daily caps, dry-run by default, opt-out
 * gate before every send. Transports are pluggable: WhatsApp is delivered
 * through the external wa-sender service (or a configurable HTTP endpoint);
 * email through wp_mail (Brevo-backed via gabochie-email).
 *
 * @package Gabochie_Cold_Outreach
 */

defined( 'ABSPATH' ) || exit;

/**
 * Send driver + hard-limit enforcement.
 */
class Gabochie_Co_Sender {

	/** @var string */
	const CRON = 'gabochie_co_send';

	/** @var string */
	const OPT_MODE = 'gabochie_co_mode';

	/** @var string */
	const OPT_WAVE = 'gabochie_co_wave';

	/** @var string */
	const OPT_DAY_EMAIL = 'gabochie_co_day_email';

	/** @var string */
	const OPT_DAY_WA = 'gabochie_co_day_wa';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( self::CRON, array( __CLASS__, 'run' ) );
	}

	/**
	 * Hard per-channel caps (conservative for safety + ban-avoidance).
	 *
	 * @param string $channel wa|email.
	 * @return int
	 */
	public static function cap( $channel ) {
		$caps = array(
			'wa'    => (int) apply_filters( 'gabochie_co_cap_wa', 50 ),
			'email' => (int) apply_filters( 'gabochie_co_cap_email', 25 ),
		);
		return max( 0, $caps[ $channel ] ?? 0 );
	}

	/**
	 * Whether outbound is armed (non-dry-run).
	 *
	 * @return bool
	 */
	public static function is_armed() {
		return 'dry-run' !== get_option( self::OPT_MODE, 'dry-run' );
	}

	/**
	 * Set wave size (number of sends per cron pass) and optionally arm mode.
	 *
	 * @param int    $size Wave size.
	 * @param string $mode dry-run|live.
	 */
	public static function set_wave( $size, $mode = 'dry-run' ) {
		update_option( self::OPT_WAVE, max( 1, (int) $size ), false );
		update_option( self::OPT_MODE, 'live' === $mode ? 'live' : 'dry-run', false );
	}

	/**
	 * Get configured wave size.
	 *
	 * @return int
	 */
	public static function wave_size() {
		return max( 1, (int) get_option( self::OPT_WAVE, 10 ) );
	}

	/**
	 * Daily sent-tracking for hard caps (reset on date change).
	 *
	 * @param string $channel Channel.
	 * @return int
	 */
	public static function used_today( $channel ) {
		$key  = 'wa' === $channel ? self::OPT_DAY_WA : self::OPT_DAY_EMAIL;
		$data = get_option(
			$key,
			array(
				'd' => 0,
				'n' => 0,
			)
		);
		if ( (int) ( $data['d'] ?? 0 ) !== (int) gmdate( 'Ymd' ) ) {
			$data = array(
				'd' => (int) gmdate( 'Ymd' ),
				'n' => 0,
			);
		}
		return (int) $data['n'];
	}

	/**
	 * Record a sent message on a channel.
	 *
	 * @param string $channel Channel.
	 */
	public static function mark_sent( $channel ) {
		$key  = 'wa' === $channel ? self::OPT_DAY_WA : self::OPT_DAY_EMAIL;
		$data = get_option(
			$key,
			array(
				'd' => 0,
				'n' => 0,
			)
		);
		if ( (int) ( $data['d'] ?? 0 ) !== (int) gmdate( 'Ymd' ) ) {
			$data = array(
				'd' => (int) gmdate( 'Ymd' ),
				'n' => 0,
			);
		}
		$data['n'] = (int) $data['n'] + 1;
		update_option( $key, $data, false );
	}

	/**
	 * The cron tick: pull queued/imported contacts, respect caps, send (or
	 * dry-run log), and record state. Indexes from the oldest imported rows.
	 */
	public static function run() {
		if ( self::used_today( 'wa' ) >= self::cap( 'wa' ) && self::used_today( 'email' ) >= self::cap( 'email' ) ) {
			return; // Both channels saturated for the day.
		}

		$rows = Gabochie_Co_Db::contacts_by_status( 'imported', self::wave_size() * 2 );

		$sent_this_pass = 0;
		foreach ( $rows as $row ) {
			if ( $sent_this_pass >= self::wave_size() ) {
				break;
			}
			$channel = Gabochie_Co_Router::channel( $row );
			if ( 'skip' === $channel ) {
				Gabochie_Co_Db::set_status( (int) $row['id'], 'failed' );
				Gabochie_Co_Db::log_send( (int) $row['id'], 'skip', 'skip', 'failed', 'no reachable channel' );
				continue;
			}

			if ( Gabochie_Co_Db::is_opted_out( $row['email'] ?? '', $row['phone'] ?? '' ) ) {
				Gabochie_Co_Db::set_status( (int) $row['id'], 'opted_out' );
				continue;
			}

			if ( self::used_today( $channel ) >= self::cap( $channel ) ) {
				continue; // This channel's cap hit; leave row imported for later.
			}

			$built = Gabochie_Co_Messaging::build( $row, $channel );
			$armed = self::is_armed();

			$ok = true;
			if ( $armed ) {
				$ok = self::deliver( $channel, $row, $built['message'] );
			}

			$log_status = $armed ? ( $ok ? 'sent' : 'failed' ) : 'dry-run';
			Gabochie_Co_Db::log_send(
				(int) $row['id'],
				$channel,
				$built['kind'],
				$log_status,
				$built['message']
			);

			if ( $armed && $ok ) {
				self::mark_sent( $channel );
				do_action( 'gabochie_co_sent', $row, $channel );
			}
			Gabochie_Co_Db::set_status( (int) $row['id'], $ok ? 'sent' : 'failed' );

			++$sent_this_pass;
		}
	}

	/**
	 * Deliver a message over the chosen channel.
	 *
	 * @param string $channel wa|email.
	 * @param array  $contact Contact row.
	 * @param string $message Rendered body.
	 * @return bool
	 */
	protected static function deliver( $channel, array $contact, $message ) {
		if ( 'wa' === $channel ) {
			return self::send_whatsapp( $contact['phone'] ?? '', $message );
		}
		if ( 'email' === $channel ) {
			return self::send_email( $contact['email'] ?? '', $contact['biz'] ?? '', $message );
		}
		return false;
	}

	/**
	 * WhatsApp via external wa-sender HTTP endpoint.
	 *
	 * @param string $phone   Normalized phone.
	 * @param string $message Message body.
	 * @return bool
	 */
	protected static function send_whatsapp( $phone, $message ) {
		$endpoint = (string) get_option( 'gabochie_co_wa_endpoint', 'http://127.0.0.1:8002/api/wa/send' );
		if ( '' === $endpoint ) {
			return false;
		}
		$r = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 30,
				'body'    => wp_json_encode(
					array(
						'number'  => $phone,
						'message' => $message,
					)
				),
				'headers' => array( 'Content-Type' => 'application/json' ),
			)
		);
		if ( is_wp_error( $r ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[ColdOutreach] WA transport error: ' . $r->get_error_message() );
			return false;
		}
		$code = (int) wp_remote_retrieve_response_code( $r );
		$body = json_decode( wp_remote_retrieve_body( $r ), true );
		return 200 === $code && ( ( $body['ok'] ?? false ) || ( $body['queued'] ?? 0 ) > 0 );
	}

	/**
	 * Email via wp_mail (Brevo transp ort via gabochie-email).
	 *
	 * @param string $email   Recipient.
	 * @param string $biz     Contact name.
	 * @param string $message Plain-text body.
	 * @return bool
	 */
	protected static function send_email( $email, $biz, $message ) {
		$subject = 'Free guide from Gabochie';
		return wp_mail( $email, $subject, $message );
	}
}
