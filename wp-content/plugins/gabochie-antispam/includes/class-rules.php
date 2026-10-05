<?php
/**
 * Gabochie Anti-Spam — heuristic rules engine.
 *
 * Scores a submission against a battery of deterministic spam signals.
 * Returns a score (0–100) + reasons array. Threshold interpretation
 * is left to the hooks layer so sensitivity settings apply there.
 *
 * @package Gabochie_Antispam
 */

defined( 'ABSPATH' ) || exit;

final class Gabochie_Antispam_Rules {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/* ------------------------------------------------------------------ */
	/*  Domain / keyword lists                                            */
	/* ------------------------------------------------------------------ */

	private static $disposable_domains = array(
		'mailinator.com',
		'yopmail.com',
		'guerrillamail.com',
		'guerrillamailblock.com',
		'tempmail.com',
		'throwaway.email',
		'10minutemail.com',
		'mailnesia.com',
		'mailsac.com',
		'trashmail.com',
		'temp-mail.org',
		'emailfake.com',
		'sharklasers.com',
		'guerrillamailblock.com',
		'grr.la',
		'guerrillamail.info',
		'discard.email',
		'discardmail.com',
		'mailcatch.com',
		'tempinbox.com',
		'mailexpire.com',
		'fakeinbox.com',
		'maildrop.cc',
		'yopmail.fr',
		'getnada.com',
		'mohmal.com',
		'emailondeck.com',
		'33mail.com',
		'mytemp.email',
		'harakirimail.com',
		'tmail.ws',
		'tmpmail.net',
		'boun.cr',
		'mailsursor.com',
	);

	private static $suspicious_tlds = array(
		'xyz',
		'club',
		'top',
		'gq',
		'ml',
		'tk',
		'click',
		'work',
		'download',
		'stream',
		'racing',
		'date',
		'science',
		'party',
		'buzz',
		'gdn',
		'bid',
		'loan',
		'win',
		'cricket',
	);

	private static $spam_keywords = array(
		'viagra',
		'cialis',
		'casino',
		'poker',
		'bitcoin',
		'crypto',
		'forex',
		'lottery',
		'jackpot',
		'prize winner',
		'free money',
		'urgent loan',
		'quick cash',
		'make money fast',
		'weight loss',
		'diet pills',
		'cheapcialis',
		'best price for',
		'check out my blog',
		'click here for',
		'buy now',
		'free download',
		'win a prize',
		'nigerian prince',
		'congratulations you won',
		'act now',
		'limited time offer',
		'100% free',
		'no cost',
		'wire transfer',
		'whatsapp group link',
		'join now free',
		'earn per day',
		'investment opportunity',
		'double your money',
		'risk free',
	);

	private static $shorteners = array(
		'bit.ly',
		'tinyurl.com',
		'goo.gl',
		't.co',
		'tiny.cc',
		'is.gd',
		'buff.ly',
		'ow.ly',
		'cutt.ly',
		'rb.gy',
		'shorturl.at',
		'tiny.ly',
		't.ly',
		'clck.ru',
	);

	/* ------------------------------------------------------------------ */
	/*  Public API                                                        */
	/* ------------------------------------------------------------------ */

	/**
	 * Score a submission.
	 *
	 * @param array $data {
	 *     @type string $name    Author / respondent name.
	 *     @type string $email   Email address.
	 *     @type string $content Main body (comment, project_description, service, etc).
	 *     @type string $url     Author URL (comment_author_url, etc).
	 *     @type string $ip      REMOTE_ADDR.
	 *     @type string $source  lead|onboarding|comment|community|thesis.
	 * }
	 * @return array{score:int, reasons:string[]}
	 */
	public function evaluate( $data = array() ) {
		$name    = sanitize_text_field( (string) ( $data['name'] ?? '' ) );
		$email   = sanitize_email( (string) ( $data['email'] ?? '' ) );
		$content = wp_kses_post( (string) ( $data['content'] ?? '' ) );
		$url     = esc_url_raw( (string) ( $data['url'] ?? '' ) );
		$score   = 0;
		$reasons = array();

		/* --- email rules --- */
		if ( $email ) {
			$domain = strtolower( (string) substr( strrchr( $email, '@' ), 1 ) );
			if ( in_array( $domain, self::$disposable_domains, true ) ) {
				$score    += 25;
				$reasons[] = 'disposable_email';
			}
			if ( preg_match( '/\+/', $email ) ) {
				$score    += 3;
				$reasons[] = 'plus_addressed_email';
			}
		}

		/* --- URL / author URL rules --- */
		if ( $url ) {
			$url_host = strtolower( wp_parse_url( $url, PHP_URL_HOST ) );
			$url_tld  = strtolower( (string) substr( strrchr( $url_host, '.' ), 1 ) );
			if ( in_array( $url_tld, self::$suspicious_tlds, true ) ) {
				$score    += 15;
				$reasons[] = 'suspicious_tld';
			}
			if ( in_array( $url_host, self::$shorteners, true ) ) {
				$score    += 15;
				$reasons[] = 'url_shortener';
			}
		}

		/* --- content link rules --- */
		$link_count = 0;
		if ( preg_match_all( '#https?://\S+#i', $content, $url_matches ) ) {
			$link_count = count( $url_matches[0] );
		}
		if ( $link_count >= 3 ) {
			$score    += 25;
			$reasons[] = "many_links_{$link_count}";
		} elseif ( $link_count === 2 ) {
			$score    += 10;
			$reasons[] = 'two_links';
		}
		$content_len = mb_strlen( wp_strip_all_tags( $content ) );
		if ( $content_len < 80 && $link_count >= 2 ) {
			$score    += 15;
			$reasons[] = 'short_with_links';
		}
		if ( $link_count > 0 && $content_len === 0 ) {
			$score    += 15;
			$reasons[] = 'link_only';
		}

		/* --- keyword rules --- */
		$lower        = mb_strtolower( wp_strip_all_tags( $content ) );
		$keyword_hits = 0;
		foreach ( self::$spam_keywords as $kw ) {
			if ( mb_strpos( $lower, $kw ) !== false ) {
				++$keyword_hits;
			}
		}
		if ( $keyword_hits >= 3 ) {
			$score    += 60;
			$reasons[] = "many_keywords_{$keyword_hits}";
		} elseif ( $keyword_hits === 2 ) {
			$score    += 30;
			$reasons[] = 'two_keywords';
		} elseif ( $keyword_hits === 1 ) {
			$score    += 12;
			$reasons[] = 'one_keyword';
		}

		/* --- gibberish heuristics --- */
		if ( preg_match( '/(.)\1{3,}/', $lower ) ) {
			$score    += 8;
			$reasons[] = 'repeated_chars';
		}
		if ( preg_match( '/[bcdfghjklmnpqrstvwxz]{7,}/', $lower ) ) {
			$score    += 8;
			$reasons[] = 'no_vowels_gibberish';
		}
		if ( preg_match_all( '/[\x{1F600}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $content ) > 5 ) {
			$score    += 8;
			$reasons[] = 'excessive_emoji';
		}

		/* --- name rules --- */
		if ( mb_strlen( $name ) > 60 ) {
			$score    += 6;
			$reasons[] = 'long_name';
		}
		if ( preg_match( '/https?:\/\//', $name ) ) {
			$score    += 15;
			$reasons[] = 'url_in_name';
		}

		/* --- very short content (weak) --- */
		if ( $content_len > 0 && $content_len < 10 && $link_count === 0 ) {
			$score    += 6;
			$reasons[] = 'very_short';
		}

		/* --- all-caps content (weak) --- */
		if ( $content_len > 20 ) {
			$caps = preg_replace( '/[^A-Z]/', '', wp_strip_all_tags( $content ) );
			if ( mb_strlen( $caps ) / max( $content_len, 1 ) > 0.85 ) {
				$score    += 5;
				$reasons[] = 'mostly_caps';
			}
		}

		return array(
			'score'   => min( 100, $score ),
			'reasons' => $reasons,
		);
	}
}

Gabochie_Antispam_Rules::instance();
