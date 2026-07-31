<?php
namespace AIAgenticMaintenancePro\Core;

class AdminMenu {
    public function init() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    public function register_menu() {
        add_menu_page(
            'AI Maintenance',
            'AI Maintenance',
            'manage_options',
            'aimmp-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-superhero',
            30
        );
    }

    public function render_dashboard() {
        echo '<div class="wrap"><h1>AI Agentic Maintenance Mode Pro</h1><p>Full control panel active. Manage AI reception, leads, and secret bypasses.</p></div>';
    }
}
