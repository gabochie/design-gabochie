<?php
/**
 * Gabochie Cold Outreach — router & segmenter.
 *
 * Chooses the best channel per contact and classifies the direct-response
 * angle (thesis/development vs free account) from the business category.
 *
 * @package Gabochie_Cold_Outreach
 */

defined( 'ABSPATH' ) || exit;

/**
 * Channel + segment decisions.
 */
class Gabochie_Co_Router {

	/**
	 * Category keywords → segment key.
	 *
	 * @return array
	 */
	public static function segments() {
		return array(
			'development' => array(
				'label' => 'Development / Construction',
				'keys'  => array( 'contractor', 'construction', 'real_estate', 'real estate', 'builder', 'architect', 'surveyor', 'developer', 'engineering', 'property', 'land', 'interior' ),
			),
			'service'     => array(
				'label' => 'Service / Retail',
				'keys'  => array( 'restaurant', 'hair', 'salon', 'barber', 'beauty', 'clinic', 'hospital', 'pharmacy', 'school', 'consultant', 'service', 'retail', 'shop', 'store', 'hotel', 'car', 'auto', 'repair', 'legal', 'accounting', 'insurance', 'bank', 'logistics', 'cleaning' ),
			),
		);
	}

	/**
	 * Classify a contact's segment from its category.
	 *
	 * @param string $category Raw category string.
	 * @return string development|service|general
	 */
	public static function classify( $category ) {
		$hay = strtolower( (string) $category );
		foreach ( self::segments() as $key => $cfg ) {
			foreach ( $cfg['keys'] as $k ) {
				if ( false !== strpos( $hay, $k ) ) {
					return $key;
				}
			}
		}
		return 'general';
	}

	/**
	 * Pick the best channel given available identifiers.
	 *
	 * An optional operator override (option `gabochie_co_channel`) forces a
	 * single channel (auto|email|wa) so a pilot can be gated to email-only.
	 *
	 * @param array $contact Contact row (email, phone).
	 * @return string wa|email|skip
	 */
	public static function channel( array $contact ) {
		$phone = $contact['phone'] ?? '';
		$email = $contact['email'] ?? '';

		$phone = self::normalize_phone( $phone );

		$override = (string) get_option( 'gabochie_co_channel', 'auto' );

		// Explicit single-channel mode (email-only / WhatsApp-only waves).
		if ( 'email' === $override ) {
			return ( '' !== $email && is_email( $email ) ) ? 'email' : 'skip';
		}
		if ( 'wa' === $override ) {
			return ( '' !== $phone && strlen( $phone ) >= 11 ) ? 'wa' : 'skip';
		}

		// Auto: WhatsApp is the highest-performant channel for GH businesses.
		if ( '' !== $phone && strlen( $phone ) >= 11 ) {
			return 'wa';
		}
		if ( '' !== $email && is_email( $email ) ) {
			return 'email';
		}
		return 'skip';
	}

	/**
	 * Normalize a Ghana phone to 233+9 digits (or '' if unreachable).
	 *
	 * @param string $phone Raw phone.
	 * @return string
	 */
	public static function normalize_phone( $phone ) {
		$digits = preg_replace( '/[^0-9]/', '', (string) $phone );
		if ( '' === $digits ) {
			return '';
		}
		if ( 10 === strlen( $digits ) && str_starts_with( $digits, '0' ) ) {
			return '233' . substr( $digits, 1 );
		}
		if ( 12 === strlen( $digits ) && str_starts_with( $digits, '233' ) ) {
			return $digits;
		}
		if ( 13 === strlen( $digits ) && str_starts_with( $digits, '2330' ) ) {
			return '233' . substr( $digits, 4 );
		}
		return '';
	}
}
