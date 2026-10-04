<?php
/**
 * Gabochie Anti-Spam — hooks: comment filter + AJAX interception + honeypot.
 *
 * Intercepts submissions at the earliest WordPress hook point, runs the
 * rules engine (+ AI if borderline), and either blocks or lets them through.
 * Injects the honeypot CSS/JS on the frontend.
 *
 * @package Gabochie_Antispam
 */

defined( 'ABSPATH' ) || exit;

final class Gabochie_Antispam_Hooks {

    private static $instance = null;

    /** AJAX actions we protect, mapped to their source label. */
    private static $ajax_actions = array(
        'gabochie_capture_lead' => 'lead',
        'gabochie_onb_create'   => 'onboarding',
        'gabochie_thesis_download' => 'thesis',
        'gabochie_ask'          => 'community',
    );

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        /* --- comment interception --- */
        add_filter( 'pre_comment_approved', array( $this, 'filter_comment_approved' ), 10, 2 );

        /* --- AJAX interceptions (priority 5 — runs before theme handlers at 10) --- */
        foreach ( self::$ajax_actions as $action => $source ) {
            add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'guard_ajax' ), 5 );
            add_action( 'wp_ajax_' . $action, array( $this, 'guard_ajax' ), 5 );
        }

        /* --- honeypot injection (frontend) --- */
        add_action( 'wp_footer', array( $this, 'enqueue_honeypot' ), 999 );
        add_action( 'comment_form_defaults', array( $this, 'honeypot_comment_field' ), 20 );

        /* --- nightly prune --- */
        if ( ! wp_next_scheduled( 'gabochie_antispam_prune' ) ) {
            wp_schedule_event( time(), 'daily', 'gabochie_antispam_prune' );
        }
        add_action( 'gabochie_antispam_prune', array( 'Gabochie_Antispam_DB', 'prune' ) );
    }

    /* ================================================================== */
    /*  Comment filter                                                    */
    /* ================================================================== */

    /**
     * Fires before WP decides the approval status.
     *
     * @param string|int $approved  Current decision ('1','0','spam','hold').
     * @param array      $commentdata Full comment data array.
     * @return string|int Modified approval decision.
     */
    public function filter_comment_approved( $approved, $commentdata ) {
        if ( is_user_logged_in() ) {
            return $approved;
        }
        $settings = gabochie_antispam_settings();

        $data = array(
            'source'  => 'comment',
            'name'    => (string) ( $commentdata['comment_author'] ?? '' ),
            'email'   => (string) ( $commentdata['comment_author_email'] ?? '' ),
            'content' => (string) ( $commentdata['comment_content'] ?? '' ),
            'url'     => (string) ( $commentdata['comment_author_url'] ?? '' ),
            'ip'      => (string) ( $commentdata['comment_author_IP'] ?? '' ),
        );

        /* honeypot check */
        if ( ! empty( $settings['honeypot'] ) && ! empty( $_POST['gabochie_website'] ) ) {
            $id = $this->log_entry( $data, 100, 'spam', array( 'honeypot' ), false );
            return 'spam';
        }

        $rules   = Gabochie_Antispam_Rules::instance()->evaluate( $data );
        $verdict = $this->decide_verdict( $rules['score'], $settings );

        if ( $verdict === 'spam' ) {
            $this->log_entry( $data, $rules['score'], 'spam', $rules['reasons'], false );
            return 'spam';
        }

        if ( $verdict === 'borderline' ) {
            $ai = Gabochie_Antispam_AI::instance()->classify( $data, $rules );
            if ( $ai && $ai['verdict'] === 'spam' && $ai['confidence'] >= 0.6 ) {
                $this->log_entry( $data, $rules['score'], 'spam', $rules['reasons'], true );
                return 'spam';
            }
            $this->log_entry( $data, $rules['score'], 'borderline', $rules['reasons'], ! empty( $ai ) );
            return $approved;
        }

        /* ham — log it for the record */
        $this->log_entry( $data, $rules['score'], 'ham', $rules['reasons'], false );
        return $approved;
    }

    /* ================================================================== */
    /*  AJAX interception                                                 */
    /* ================================================================== */

    /**
     * Read one POST field as a plain, unslashed string.
     *
     * The guarded actions are reachable without authentication and a visitor
     * can send name[]=x. sanitize_email() and wp_kses_post() both raise a
     * TypeError on an array in PHP 8, which would turn a junk field into a
     * fatal on every guarded form, so the type is forced before sanitizing.
     *
     * @param string $key POST key.
     * @return string Field value, or '' when absent or not scalar.
     */
    private static function post_scalar( $key ) {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) ) {
            return '';
        }
        return (string) wp_unslash( $_POST[ $key ] );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    public function guard_ajax() {
        $action = sanitize_text_field( self::post_scalar( 'action' ) );
        $source = self::$ajax_actions[ $action ] ?? 'unknown';
        $settings = gabochie_antispam_settings();

        $data = array(
            'source'  => $source,
            'name'    => sanitize_text_field( self::post_scalar( 'name' ) ),
            'email'   => sanitize_email( self::post_scalar( 'email' ) ),
            'content' => wp_kses_post( self::post_scalar( 'service' ) ),
            'url'     => '',
            'ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
        );

        /* merge extra context for richer scoring */
        $phone = sanitize_text_field( self::post_scalar( 'phone' ) );
        if ( '' !== $phone ) {
            $data['content'] .= ' ' . $phone;
        }
        $description = wp_kses_post( self::post_scalar( 'project_description' ) );
        if ( '' !== $description ) {
            $data['content'] .= ' ' . $description;
        }

        /* honeypot check */
        if ( ! empty( $settings['honeypot'] ) && ! empty( $_POST['gabochie_website'] ) ) {
            $this->log_entry( $data, 100, 'spam', array( 'honeypot' ), false );
            wp_send_json_error( array( 'message' => 'Your submission was flagged as spam.', 'code' => 'spam' ), 403 );
            exit;
        }

        $rules   = Gabochie_Antispam_Rules::instance()->evaluate( $data );
        $verdict = $this->decide_verdict( $rules['score'], $settings );

        if ( $verdict === 'spam' ) {
            $this->log_entry( $data, $rules['score'], 'spam', $rules['reasons'], false );
            wp_send_json_error( array( 'message' => 'Your submission was flagged as spam.', 'code' => 'spam' ), 403 );
            exit;
        }

        if ( $verdict === 'borderline' ) {
            $ai = Gabochie_Antispam_AI::instance()->classify( $data, $rules );
            if ( $ai && $ai['verdict'] === 'spam' && $ai['confidence'] >= 0.6 ) {
                $this->log_entry( $data, $rules['score'], 'spam', $rules['reasons'], true );
                wp_send_json_error( array( 'message' => 'Your submission was flagged as spam.', 'code' => 'spam' ), 403 );
                exit;
            }
            $this->log_entry( $data, $rules['score'], 'borderline', $rules['reasons'], ! empty( $ai ) );
        }

        /* ham — let the theme handler run */
    }

    /* ================================================================== */
    /*  Honeypot injection                                                */
    /* ================================================================== */

    public function enqueue_honeypot() {
        $settings = gabochie_antispam_settings();
        if ( empty( $settings['honeypot'] ) ) {
            return;
        }
        wp_enqueue_script(
            'gabochie-antispam',
            GABOCHIE_ANTISPAM_URL . 'assets/antispam.js',
            array(),
            GABOCHIE_ANTISPAM_VERSION,
            true
        );
    }

    /**
     * Add a hidden honeypot field to the WordPress comment form (server-side).
     */
    public function honeypot_comment_field( $defaults ) {
        $settings = gabochie_antispam_settings();
        if ( empty( $settings['honeypot'] ) ) {
            return $defaults;
        }
        $hp = '<p class="comment-form-hp" style="position:absolute!important;left:-9999px!important">'
            . '<label for="gabochie_website">Leave this empty</label>'
            . '<input id="gabochie_website" name="gabochie_website" type="text" value="" tabindex="-1" autocomplete="off" />'
            . '</p>';
        if ( is_array( $defaults ) ) {
            if ( ! empty( $defaults['fields'] ) ) {
                $defaults['fields'] .= $hp;
            } else {
                $defaults['fields'] = $hp;
            }
        }
        return $defaults;
    }

    /* ================================================================== */
    /*  Internal helpers                                                  */
    /* ================================================================== */

    /**
     * Map a numeric score to a verdict string based on sensitivity.
     */
    private function decide_verdict( $score, $settings ) {
        $sensitivity = (string) ( $settings['sensitivity'] ?? 'normal' );
        $thresholds  = array(
            'strict'  => array( 'spam' => 40, 'borderline' => 20 ),
            'normal'  => array( 'spam' => 60, 'borderline' => 30 ),
            'lenient' => array( 'spam' => 80, 'borderline' => 45 ),
        );
        $t = $thresholds[ $sensitivity ] ?? $thresholds['normal'];
        if ( $score >= $t['spam'] ) {
            return 'spam';
        }
        if ( $score >= $t['borderline'] ) {
            return 'borderline';
        }
        return 'ham';
    }

    private function log_entry( $data, $score, $verdict, $reasons, $ai ) {
        $db = Gabochie_Antispam_DB::instance();

        /*
         * Storage-abuse guard. The guarded actions are unauthenticated, so
         * without a cap a bot can append unlimited rows (each holding an
         * email, an IP and LONGTEXT content) just by POSTing. Only the audit
         * row is dropped once the cap is hit: spam is still blocked and ham
         * still passes, so real visitors are never affected.
         */
        $settings = gabochie_antispam_settings();
        $cap = (int) apply_filters( 'gabochie_antispam_log_cap_hour', max( 0, (int) ( $settings['log_cap_hour'] ?? 20 ) ) );
        if ( $cap > 0 && $db->count_recent_by_ip( (string) ( $data['ip'] ?? '' ), 1 ) >= $cap ) {
            return 0;
        }

        return $db->log( array(
            'source'  => $data['source'],
            'name'    => $data['name'],
            'email'   => $data['email'],
            'ip'      => $data['ip'],
            'content' => $data['content'],
            'score'   => $score,
            'verdict' => $verdict,
            'reasons' => $reasons,
            'ai'      => $ai,
        ) );
    }
}

Gabochie_Antispam_Hooks::instance();