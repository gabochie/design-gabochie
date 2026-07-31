<?php
namespace AIAgenticMaintenancePro\Services;

class GeminiClient {
    private $api_key;

    public function __construct() {
        $settings = get_option( 'aimmp_settings', array() );
        $this->api_key = $settings['gemini_api_key'] ?? '';
    }

    public function generate_reply( $prompt, $history = array() ) {
        if ( empty( $this->api_key ) ) {
            return "Hello! I am Nova, the AI Receptionist. Our site is currently undergoing scheduled maintenance. How can I assist you with project inquiries or service details today?";
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $this->api_key;
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt )
                    )
                )
            )
        );

        $response = wp_remote_post( $url, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            return "Thank you for reaching out! Our team is currently updating the system. Please leave your contact details so we can assist you promptly.";
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Thank you for your message! How can we assist with your project?";
    }
}
