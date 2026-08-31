<?php
/**
 * Gabochie Cold Outreach — Command Center integration hub.
 *
 * Bridges cold outreach activity into the Revenue Command Center (when it is
 * active): every armed send enters the funnel as a captured lead (so the SDR
 * qualifies it), wave/import events are logged to the orchestrator task table,
 * and opt-outs are recorded so the machine stops contacting them. Inert when
 * the Command Center plugin is not active.
 *
 * Fired events (all no-op unless the CC is loaded):
 *   - gabochie_co_wave_started  ($size)   operator armed/larger wave
 *   - gabochie_co_sent          (row, channel)  a first contact was delivered
 *   - gabochie_co_opted_out     (identifier, channel)
 *
 * @package Gabochie_Cold_Outreach
 */

defined( 'ABSPATH' ) || exit;

/**
 * Command Center adapter.
 */
class Gabochie_Co_Hub {

	/**
	 * Hook wiring.
	 */
	public static function init() {
		add_action( 'gabochie_co_wave_started', array( __CLASS__, 'on_wave_started' ), 10, 2 );
		add_action( 'gabochie_co_sent', array( __CLASS__, 'on_sent' ), 10, 2 );
		add_action( 'gabochie_co_opted_out', array( __CLASS__, 'on_opted_out' ), 10, 2 );
	}

	/**
	 * Whether the Revenue Command Center is available to receive events.
	 *
	 * @return bool
	 */
	protected static function cc_ready() {
		return class_exists( 'Gabochie_CC_Orchestrator' );
	}

	/**
	 * Wave (re)armed — log a RevenueOps task so the operator sees intent.
	 *
	 * @param int    $size Wave size.
	 * @param string $mode dry-run|live.
	 */
	public static function on_wave_started( $size, $mode ) {
		if ( ! self::cc_ready() || is_callable( array( 'Gabochie_CC_Orchestrator', 'add_task' ) ) === false ) {
			return;
		}
		Gabochie_CC_Orchestrator::add_task(
			'revops',
			'cold_outreach',
			'wave_started',
			'Cold outreach wave armed (' . (int) $size . ')' . ( 'live' === $mode ? ' — LIVE' : ' — dry-run' ),
			'Mode: ' . $mode . ' | Wave size: ' . (int) $size
		);
	}

	/**
	 * A first contact was delivered — feed the funnel as a captured lead.
	 *
	 * @param array  $row     Contact row (email/phone/name/biz).
	 * @param string $channel wa|email.
	 */
	public static function on_sent( array $row, $channel ) {
		if ( ! self::cc_ready() ) {
			return;
		}
		$email = sanitize_email( $row['email'] ?? '' );
		$phone = sanitize_text_field( $row['phone'] ?? '' );
		if ( '' === $email && '' === $phone ) {
			return;
		}
		// Funnel capture so the SDR sees this as an inbound-to-qualify.
		do_action(
			'gabochie/lead/captured',
			$email ? $email : $phone . '@wa', // Lead Hub needs an email-ish identity.
			array(
				'list'   => 'cold-outreach',
				'source' => 'cold_outreach:' . $channel,
				'name'   => sanitize_text_field( $row['biz'] ?? '' ),
				'phone'  => $phone,
			)
		);
		if ( is_callable( array( 'Gabochie_CC_Orchestrator', 'add_task' ) ) ) {
			Gabochie_CC_Orchestrator::add_task(
				'sdr',
				'cold_outreach',
				'sent',
				'Cold outreach delivered (' . $channel . ') — qualify',
				'Email: ' . $email . ( $phone ? ' | Phone: ' . $phone : '' ),
				0,
				'info'
			);
		}
	}

	/**
	 * A contact opted out — record it so no further sends occur.
	 *
	 * @param string $identifier Email or phone.
	 * @param string $channel    wa|email.
	 */
	public static function on_opted_out( $identifier, $channel ) {
		if ( ! self::cc_ready() ) {
			return;
		}
		// A manual opt-out is a compliance event, not a lead; just log it.
		if ( is_callable( array( 'Gabochie_CC_Orchestrator', 'add_task' ) ) ) {
			Gabochie_CC_Orchestrator::add_task(
				'cs_support',
				'opt_out',
				'optout',
				'Cold outreach opt-out (' . $channel . ')',
				'Identifier: ' . sanitize_text_field( $identifier ),
				0,
				'info'
			);
		}
	}
}
