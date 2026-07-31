<?php
/**
 * Plugin Name: BOQ – Silence stray output during REST API calls
 * Description: Buffers and discards any HTML/echo output that would break JSON responses.
 * Author: Antigravity (auto‑generated)
 */

// Start buffering for every REST request.
add_action( 'rest_api_init', function () {
    ob_start();
} );

// Clean the buffer just before the response is sent.
add_action( 'rest_api_init', function () {
    if ( ob_get_length() ) {
        // Discard any stray output.
        ob_clean();
    }
    // End buffering – now the pure JSON will be sent.
    ob_end_flush();
}, PHP_INT_MAX );
