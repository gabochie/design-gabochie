<?php
namespace AIAgenticMaintenancePro\API;

class RestApi {
    public function init() {
        add_action( 'rest_api_init', function() {
            register_rest_route( 'aimmp/v1', '/chat', array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle_chat' ),
                'permission_callback' => '__return_true',
            ) );
            register_rest_route( 'aimmp/v1', '/leads', array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle_lead' ),
                'permission_callback' => '__return_true',
            ) );
        } );
    }

    public function handle_chat( $request ) {
        $params = $request->get_json_params();
        $message = sanitize_text_field( $params['message'] ?? '' );
        $history = $params['history'] ?? array();

        $client = new \AIAgenticMaintenancePro\Services\GeminiClient();
        $reply = $client->generate_reply( $message, $history );

        return rest_ensure_response( array(
            'success' => true,
            'reply'   => $reply,
        ) );
    }

    public function handle_lead( $request ) {
        $params = $request->get_json_params();
        $manager = new \AIAgenticMaintenancePro\Services\LeadManager();
        $result = $manager->capture_lead( $params );

        return rest_ensure_response( array(
            'success' => true,
            'lead_id' => $result,
        ) );
    }
}
