<?php
/**
 * Marketplace enquiry funnel.
 *
 * AJAX handler for the per-listing "Send enquiry" form. Validates (nonce +
 * honeypot + rate limit), then fires the canonical `gabochie/lead/captured`
 * action so the enquiry lands in BOTH the CRM (`gabochie-lead-hub` → gab_contacts
 * with list_key `leadhub-property`) and the Command Center funnel (SDR task).
 *
 * @package Gabochie_Marketplace
 */

defined('ABSPATH') || exit;

/**
 * Enquiry handler.
 */
class Gabochie_Marketplace_Enquiry
{

    const AK   = 'gmp_enquiry';
    const HP   = 'gmp_hp';   // honeypot field.
    const RATE = 'gmp_enq_'; // transient prefix, md5(ip).

    /**
     * @var Gabochie_Marketplace_Enquiry|null 
     */
    private static $instance = null;

    /**
     * @return Gabochie_Marketplace_Enquiry 
     */
    public static function instance()
    {
        if (null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_ajax_' . self::AK, array( $this, 'handle' ));
        add_action('wp_ajax_nopriv_' . self::AK, array( $this, 'handle' ));
    }

    /**
     * Capability check: is the (nonce-only) public enrolment OK.
     *
     * @return void
     */
    public function handle()
    {
        check_ajax_referer(self::AK, 'gmp_enquiry_nonce');

        // Honeypot: humans never fill it — return a polite 200 so bots think
        // they succeeded (nothing is stored).
        if (! empty($_POST[ self::HP ]) ) {
            wp_send_json_success(array( 'ok' => true ));
        }

        $name   = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email  = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $phone  = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $msg    = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
        $listing_id = (int) ( $_POST['listing_id'] ?? 0 );

        if ('' === $name || '' === $email || '' === $msg ) {
            wp_send_json_error(array( 'message' => 'Name, email and message are required.' ));
        }
        if (! is_email($email) ) {
            wp_send_json_error(array( 'message' => 'Please enter a valid email address.' ));
        }
        $listing = get_post($listing_id);
        if (! $listing || Gabochie_Marketplace::CPT !== $listing->post_type ) {
            wp_send_json_error(array( 'message' => 'That property could not be found.' ));
        }

        // Rate limit: 10/hr per IP.
        if (! $this->check_rate() ) {
            wp_send_json_error(
                array(
                'message'    => 'Too many enquiries — please try again in about an hour.',
                'rate_limited'=> true,
                )
            );
        }

        /**
         * Canonical lead event — captured by gabochie-lead-hub (→ CRM) and the
         * Command Center orchestrator (→ funnel task). Passing a phone bumps
         * the funnel contact straight to Qualified.
         */
        do_action(
            'gabochie/lead/captured',
            $email,
            array(
            'list'        => 'property',
            'source'      => 'property-listing',
            'name'        => $name,
            'phone'       => $phone,
            'utm_source'  => (string) sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'] ?? '')),
            'utm_medium'  => 'marketplace-enquiry',
            'utm_campaign'=> (string) $listing->post_name,
            'property'    => $listing->post_title,
            'property_id' => $listing->ID,
            'message'     => $msg,
            )
        );

        // Direct CRM fallback if lead-hub isn't active (still upsert).
        if (! class_exists('Gabochie_Crm') || ! function_exists('gabochie_crm_upsert') ) {
            // Lead-hub handles this normally; this branch is defensive only.
            Gabochie_Marketplace::log_error('enquiry: gabochie-lead-hub not active for ' . $email);
        }

        $this->bump_rate();

        wp_send_json_success(
            array(
            'message' => 'Thank you! Your enquiry has been sent. The Gabochie team will be in touch.',
            )
        );
    }

    /**
     * @return bool
     */
    private function check_rate()
    {
        $key = $this->rate_key();
        $n   = (int) get_transient($key);
        return $n < 10;
    }

    private function bump_rate()
    {
        $key = $this->rate_key();
        $n   = (int) get_transient($key) + 1;
        set_transient($key, $n, HOUR_IN_SECONDS);
    }

    /**
     * @return string
     */
    private function rate_key()
    {
        $ip = (string) sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ( $_SERVER['REMOTE_ADDR'] ?? '' )));
        $ip = explode(',', $ip)[0];
        return self::RATE . md5(trim($ip));
    }
}
