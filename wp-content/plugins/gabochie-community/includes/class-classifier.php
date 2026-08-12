<?php
/**
 * Gabochie Community - AI classification + lead detection.
 *
 * Uses the GhBC LLM engine (provider chain + fallbacks already configured
 * in the ghbc plugin) to tag a question with a journey stage and decide
 * whether the asker is a potential paying lead. Degrades gracefully to the
 * asker's own stage selection when the LLM is unavailable.
 *
 * @package Gabochie_Community
 */

defined( 'ABSPATH' ) || exit;

final class Gabochie_Community_Classifier {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Classify a submitted question.
	 *
	 * @param string $title      Question title.
	 * @param string $body       Question body.
	 * @param string $user_stage Stage slug the asker picked (fallback).
	 * @return array{stage:string, lead:bool, lead_reason:string, source:string}
	 */
	public function classify( $title, $body, $user_stage ) {
		$fallback = array(
			'stage'       => $user_stage,
			'lead'        => false,
			'lead_reason' => '',
			'source'      => 'user',
		);

		if ( ! class_exists( 'GHBC_LLM' ) ) {
			return $fallback;
		}
		$llm = GHBC_LLM::instance();
		if ( ! $llm->is_ready() ) {
			return $fallback;
		}

		$system = $this->system_prompt();
		$user   = 'Question title: ' . $title . "\nQuestion body: " . $body;

		$result = $llm->complete( $system, array( array( 'role' => 'user', 'content' => $user ) ), array(), true );

		if ( empty( $result['success'] ) ) {
			return $fallback;
		}

		$json = $this->extract_json( (string) $result['text'] );
		if ( ! $json ) {
			return $fallback;
		}

		$stage = (string) ( $json['stage'] ?? '' );
		if ( ! array_key_exists( $stage, Gabochie_Community_Post_Types::STAGES ) ) {
			$stage = $user_stage;
		}

		return array(
			'stage'       => $stage,
			'lead'        => ! empty( $json['lead'] ),
			'lead_reason' => sanitize_text_field( (string) ( $json['lead_reason'] ?? '' ) ),
			'source'      => 'ai',
		);
	}

	private function system_prompt() {
		$stages = array();
		foreach ( Gabochie_Community_Post_Types::STAGES as $slug => $label ) {
			$stages[] = "- $slug: $label";
		}
		return 'You classify questions posted to a Ghana-based architectural consultancy community (Gabochie Design). '
			. "Respond with STRICT JSON only, no prose: {\"stage\":\"<slug>\",\"lead\":true|false,\"lead_reason\":\"<one short sentence>\"}\n\n"
			. 'Journey stages (slug: label):' . "\n" . implode( "\n", $stages ) . "\n\n"
			. 'Choose the single journey stage that best matches the asker\'s stage in the building journey. '
			. 'A lead is someone with a real project who is seeking professional help (design, feasibility, cost estimate, hiring an architect/consultant). '
			. 'Casual or general information seekers are NOT leads.';
	}

	/** Pull the first JSON object out of an LLM response (handles code fences). */
	private function extract_json( $text ) {
		if ( preg_match( '/\{.*\}/s', $text, $m ) ) {
			$text = $m[0];
		}
		$data = json_decode( $text, true );
		return is_array( $data ) ? $data : null;
	}
}

Gabochie_Community_Classifier::instance();
