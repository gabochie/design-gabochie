<?php
namespace AIAgenticMaintenancePro\Services;

class LeadManager {
    public function capture_lead( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'aimmp_leads';

        $name    = sanitize_text_field( $data['name'] ?? '' );
        $email   = sanitize_email( $data['email'] ?? '' );
        $phone   = sanitize_text_field( $data['phone'] ?? '' );
        $budget  = sanitize_text_field( $data['budget'] ?? '' );
        $service = sanitize_text_field( $data['service'] ?? '' );
        $score   = intval( $data['score'] ?? 75 );

        $wpdb->insert(
            $table,
            array(
                'name'        => $name,
                'email'       => $email,
                'phone'       => $phone,
                'budget'      => $budget,
                'service'     => $service,
                'score'       => $score,
                'captured_at' => current_time( 'mysql' ),
            )
        );

        return $wpdb->insert_id;
    }
}
