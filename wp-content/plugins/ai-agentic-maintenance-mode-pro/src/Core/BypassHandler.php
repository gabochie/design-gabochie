<?php
namespace AIAgenticMaintenancePro\Core;

class BypassHandler {
    public function init() {
        add_action( 'template_redirect', array( $this, 'maybe_intercept' ), 1 );
    }

    public function maybe_intercept() {
        $settings = get_option( 'aimmp_settings', array() );
        $enabled = ! empty( $settings['maintenance_enabled'] );

        if ( ! $enabled ) {
            return;
        }

        // 1. Logged in Administrators / Editors bypass
        if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
            return;
        }

        // 2. Secret Query Token Bypass (e.g. ?aimmp_bypass=SECRET)
        $bypass_key = $settings['bypass_secret_key'] ?? '';
        if ( ! empty( $bypass_key ) && isset( $_GET['aimmp_bypass'] ) && $_GET['aimmp_bypass'] === $bypass_key ) {
            setcookie( 'aimmp_bypass_access', $bypass_key, time() + 86400, '/' );
            return;
        }
        if ( ! empty( $bypass_key ) && isset( $_COOKIE['aimmp_bypass_access'] ) && $_COOKIE['aimmp_bypass_access'] === $bypass_key ) {
            return;
        }

        // 3. Whitelisted IP Bypass
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $allowed_ips = array_map( 'trim', explode( ',', $settings['bypass_ips'] ?? '' ) );
        if ( in_array( $user_ip, $allowed_ips, true ) ) {
            return;
        }

        // Output Maintenance Template
        status_header( 503 );
        header( 'Retry-After: 3600' );
        require_once AIMMP_PLUGIN_DIR . 'templates/maintenance-page.php';
        exit;
    }
}
