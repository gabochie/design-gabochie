<?php
/**
 * Reads the validated 05b topic bank shipped with the plugin.
 *
 * Binds the executor to the strategy resource: 192 posts across 16 months,
 * one post per bank date (Tue/Thu/Sat cadence), full SEO metadata, CTA,
 * citations and GHS figures per post.
 */

defined( 'ABSPATH' ) || exit;

class Gab_Auto_Bank {

	private static $instance = null;
	private $posts           = array(); // id => post array.
	private $by_date         = array(); // 'Y-m-d' => post array.
	private $loaded          = false;

	/**
	 * @return Gab_Auto_Bank
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/** Path to the JSON bank; filterable for overrides. */
	public function data_file() {
		return (string) apply_filters( 'gab_bank_data_file', GAB_PATH . 'data/topic-bank.json' );
	}

	/** Load and index the bank. Returns true when posts are available. */
	public function load() {
		if ( $this->loaded ) {
			return ! empty( $this->posts );
		}
		$this->loaded = true;

		$file = $this->data_file();
		if ( ! is_readable( $file ) ) {
			return false;
		}
		$json = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $json ) {
			return false;
		}
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) || empty( $data['posts'] ) ) {
			return false;
		}
		foreach ( $data['posts'] as $post ) {
			if ( empty( $post['id'] ) || ! is_array( $post ) ) {
				continue;
			}
			$this->posts[ $post['id'] ] = $post;
			if ( ! empty( $post['date'] ) ) {
				$this->by_date[ $post['date'] ] = $post;
			}
		}
		return ! empty( $this->posts );
	}

	public function count() {
		return count( $this->posts );
	}

	/** Single post by bank id, or null. */
	public function get( $id ) {
		return isset( $this->posts[ $id ] ) ? $this->posts[ $id ] : null;
	}

	/** Post scheduled for a given 'Y-m-d' date, or null. */
	public function post_for_date( $date ) {
		return isset( $this->by_date[ $date ] ) ? $this->by_date[ $date ] : null;
	}

	/** First bank date on or after $from (defaults to today, site tz). */
	public function next_date( $from_ts = null ) {
		if ( empty( $this->by_date ) ) {
			return null;
		}
		$from = ( null === $from_ts ) ? current_time( 'Y-m-d' ) : date( 'Y-m-d', $from_ts ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$dates = array_keys( $this->by_date );
		sort( $dates );
		foreach ( $dates as $d ) {
			if ( $d >= $from ) {
				return $d;
			}
		}
		return null;
	}

	/** Upcoming dates (bank order) for dashboard display. */
	public function upcoming( $limit = 7 ) {
		$from  = current_time( 'Y-m-d' );
		$dates = array_keys( $this->by_date );
		sort( $dates );
		$out = array();
		foreach ( $dates as $d ) {
			if ( $d < $from ) {
				continue;
			}
			$out[] = $this->by_date[ $d ];
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}
}

Gab_Auto_Bank::instance();