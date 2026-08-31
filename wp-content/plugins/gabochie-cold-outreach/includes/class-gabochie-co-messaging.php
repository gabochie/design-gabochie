<?php
/**
 * Gabochie Cold Outreach — direct-response messaging.
 *
 * Bilingual (English + Twi/Ga) segmented templates for the two offers:
 *   - development/construction contacts → thesis lead magnet (free PDF).
 *   - service/retail contacts          → free client account.
 * Every message carries a tracked landing link + an opt-out line.
 *
 * @package Gabochie_Cold_Outreach
 */

defined( 'ABSPATH' ) || exit;

/**
 * Message generation for a contact row.
 */
class Gabochie_Co_Messaging {

	/**
	 * Tracked landing URLs (utm-marked) by offer.
	 *
	 * @param string $offer thesis|account.
	 * @return string
	 */
	public static function landing_url( $offer ) {
		$base = home_url();
		if ( 'thesis' === $offer ) {
			return $base . '/thesis/?utm_source=cold_outreach&utm_medium=wa&utm_campaign=cold';
		}
		return $base . '/onboarding/?utm_source=cold_outreach&utm_medium=wa&utm_campaign=cold';
	}

	/**
	 * Visa / opt-out line (works for both WA + email). Global opt-out pixels
	 * can be added later via a short link.
	 *
	 * @return string
	 */
	public static function optout_line() {
		return 'Reply STOP to opt out';
	}

	/**
	 * Build the message for a contact.
	 *
	 * @param array  $contact Contact row (biz, segment).
	 * @param string $channel wa|email.
	 * @return array{kind:string,message:string}
	 */
	public static function build( array $contact, $channel = 'wa' ) {
		$segment = $contact['segment'] ?? 'general';
		$biz     = trim( (string) ( $contact['biz'] ?? '' ) );
		$label   = '' !== $biz ? $biz : 'there';

		if ( 'email' === $channel ) {
			return array(
				'kind'    => 'cold_intro',
				'message' => self::email( $label, $segment ),
			);
		}

		return array(
			'kind'    => 'cold_intro',
			'message' => self::wa( $label, $segment ),
		);
	}

	/**
	 * WhatsApp copy, bilingual.
	 *
	 * @param string $label   Business/contact name.
	 * @param string $segment Segment key.
	 * @return string
	 */
	protected static function wa( $label, $segment ) {
		if ( 'development' === $segment ) {
			return "Hi {$label} — we help Ghanaians build, price and manage development projects without guesswork. "
				. "Our free 5-min guide (PDF) shows the real gap holding projects back and the exact next steps.\n\n"
				. 'FREE download → ' . self::landing_url( 'thesis' ) . "\n\n"
				. 'Twi: Yɛboa Ghanafoɔ sɛ wɔnsi na wɔnyɛ adwuma a ɛnyɛ abodwiri. Fa guide a ɛyɛ FREE yi. '
				. self::landing_url( 'thesis' ) . "\n\n"
				. self::optout_line();
		}

		return "Hi {$label} — Gabochie helps Ghanaian businesses get a free client account to plan, esti"
			. "mate and track their projects. 100% free to start, no card needed.\n\n"
			. 'Start free → ' . self::landing_url( 'account' ) . "\n\n"
			. 'Twi: Wo account FREE a obiara ntua hwee. '
			. self::landing_url( 'account' ) . "\n\n"
			. self::optout_line();
	}

	/**
	 * Email copy, bilingual + plain-text friendly.
	 *
	 * @param string $label   Business/contact name.
	 * @param string $segment Segment key.
	 * @return string
	 */
	protected static function email( $label, $segment ) {
		if ( 'development' === $segment ) {
			return "Hello {$label},\n\n"
				. "Gabochie helps development teams and property owners in Ghana plan, cost and execute projects without guesswork.\n\n"
				. "Here is a free 5-minute PDF that shows the real gap holding projects back and the exact next steps:\n"
				. self::landing_url( 'thesis' ) . "\n\n"
				. "You'll find it helpful. If you'd rather not hear from us, just let us know and we'll stop.\n\n"
				. self::optout_line();
		}

		return "Hello {$label},\n\n"
			. "Gabochie gives Ghanaian businesses a free client account to plan and track their projects — no card, no commitment.\n\n"
			. 'Start free here: ' . self::landing_url( 'account' ) . "\n\n"
			. "If you'd rather not hear from us, just let us know and we'll stop.\n\n"
			. self::optout_line();
	}
}
