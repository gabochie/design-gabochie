<?php
/**
 * Class BOQ_Suite_Loader
 * Registers actions, filters, REST API endpoints, and admin/public components.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BOQ_Suite_Loader {

    protected $admin;
    protected $public;

    public function __construct() {
        $this->admin  = new BOQ_Suite_Admin();
        $this->public = new BOQ_Suite_Public();
    }

    public function run() {
        // CPT Initialization
        add_action('init', array('BOQ_Suite_CPT', 'register_cpts'));
        add_action('init', array('BOQ_Suite_CPT', 'register_taxonomies'));

        // REST API
        add_action('rest_api_init', array('BOQ_Suite_REST_API', 'register_routes'));

        // Admin Hooks
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_admin_assets'));

        // Public Hooks & Shortcodes
        add_action('wp_enqueue_scripts', array($this->public, 'enqueue_public_assets'));
        add_action('init', array($this->public, 'register_shortcodes'));
    }
}
