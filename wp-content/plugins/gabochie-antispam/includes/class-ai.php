<?php
/**
 * Gabochie Anti-Spam — AI second-opinion classifier.
 *
 * Sends borderline submissions to the GhBC LLM chain for a ham/spam verdict.
 * Capped per hour to prevent runaway API costs. Degrades gracefully when
 * the LLM is unavailable (GhBC not configured / all providers down).
 *
 * @package Gabochie_Antispam
 */

defined( 'ABSPATH' ) || exit;

final class Gabochie_Antispam_AI {

    private static $instance = null;

    const TRANSIENT_KEY = 'gabochie_antispam_ai_calls';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Ask the LLM to classify a borderline submission.
     *
     * @param array $data     Same shape as passed to Gabochie_Antispam_Rules::evaluate().
     * @param array $rule_result Result array from the rules engine.
     * @return array|null  ['verdict'=>'ham'|'spam', 'confidence'=>0.0-1.0, 'reason'=>string] or null on cap/error.
     */
    public function classify( $data, $rule_result ) {
        $settings = gabochie_antispam_settings();
        if ( empty( $settings['ai_enabled'] ) ) {
            return null;
        }
        if ( $this->over_cap( $settings ) ) {
            return null;
        }
        if ( ! class_exists( 'GHBC_LLM' ) ) {
            return null;
        }
        $llm = GHBC_LLM::instance();
        if ( ! $llm->is_ready() ) {
            return null;
        }

        $this->increment_counter();

        $content = wp_strip_all_tags( (string) ( $data['content'] ?? '' ) );
        if ( mb_strlen( $content ) > 1500 ) {
            $content = mb_substr( $content, 0, 1500 ) . '…';
        }

        $system = 'You are an anti-spam classifier for a Ghana-based development facilitation website. '
            . 'Classify the following submission as ham (legitimate) or spam. '
            . 'A submission from a real person about a real building/development project is ham. '
            . 'Mass-marketing, phishing, bot-generated gibberish, or off-topic commercial spam is spam. '
            . 'Respond with STRICT JSON only, no prose: {"verdict":"ham|spam","confidence":0.0-1.0,"reason":"short explanation"}';

        $summary  = "Source: {$data['source']}\n";
        $summary .= "Name: {$data['name']}\n";
        $summary .= "Email: {$data['email']}\n";
        if ( ! empty( $data['url'] ) ) {
            $summary .= "URL: {$data['url']}\n";
        }
        $summary .= "Content: {$content}\n\n";
        $summary .= "Rule engine score: {$rule_result['score']}/100\n";
        $summary .= "Signals: " . implode( ', ', $rule_result['reasons'] ) . "\n";

        $messages = array(
            array(
                'role'    => 'user',
                'content' => $summary,
            ),
        );

        $result = $llm->complete( $system, $messages, array(), true );

        if ( empty( $result['success'] ) || empty( $result['text'] ) ) {
            return null;
        }

        $parsed = $this->extract_json( $result['text'] );
        if ( ! is_array( $parsed ) ) {
            return null;
        }

        $verdict = strtolower( (string) ( $parsed['verdict'] ?? '' ) );
        if ( ! in_array( $verdict, array( 'ham', 'spam' ), true ) ) {
            $verdict = 'ham';
        }
        $confidence = (float) ( $parsed['confidence'] ?? 0.5 );
        $confidence = max( 0.0, min( 1.0, $confidence ) );

        return array(
            'verdict'    => $verdict,
            'confidence' => $confidence,
            'reason'     => sanitize_text_field( (string) ( $parsed['reason'] ?? '' ) ),
        );
    }

    private function over_cap( $settings ) {
        $cap = (int) ( $settings['ai_cap_hour'] ?? 30 );
        $count = (int) get_transient( self::TRANSIENT_KEY );
        return $count >= $cap;
    }

    private function increment_counter() {
        $current = (int) get_transient( self::TRANSIENT_KEY );
        if ( $current === 0 ) {
            set_transient( self::TRANSIENT_KEY, 1, HOUR_IN_SECONDS );
        } else {
            set_transient( self::TRANSIENT_KEY, $current + 1, HOUR_IN_SECONDS );
        }
    }

    /** Pull the first JSON object from an LLM response. */
    private function extract_json( $text ) {
        if ( preg_match( '/\{.*\}/s', $text, $m ) ) {
            $text = $m[0];
        }
        $data = json_decode( $text, true );
        return is_array( $data ) ? $data : null;
    }
}

Gabochie_Antispam_AI::instance();