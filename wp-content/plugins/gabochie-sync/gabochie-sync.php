<?php
/**
 * Plugin Name: Gabochie Sync
 * Plugin URI: https://gabochiedesign.com
 * Description: Push/pull content and code between local development and live production.
 * Version: 1.0.0
 * Author: Gabochie Design
 * License: GPL-2.0+
 * Text Domain: gabochie-sync
 */

if (!defined('ABSPATH')) exit;

define('GABOCHIE_SYNC_VERSION', '1.0.0');
define('GABOCHIE_SYNC_DIR', plugin_dir_path(__FILE__));
define('GABOCHIE_SYNC_URL', plugin_dir_url(__FILE__));

class Gabochie_Sync {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once GABOCHIE_SYNC_DIR . 'includes/class-admin-ui.php';
        require_once GABOCHIE_SYNC_DIR . 'includes/class-content-sync.php';
        require_once GABOCHIE_SYNC_DIR . 'includes/class-git-deploy.php';
        require_once GABOCHIE_SYNC_DIR . 'includes/class-hub.php';
        require_once GABOCHIE_SYNC_DIR . 'includes/class-strategy.php';
        require_once GABOCHIE_SYNC_DIR . 'includes/class-roadmap.php';
        require_once GABOCHIE_SYNC_DIR . 'includes/class-revenue.php';
        require_once GABOCHIE_SYNC_DIR . 'includes/class-recovery.php';
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array('Gabochie_Recovery', 'init'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'settings_link'));

        // Custom API key auth for REST API (works over HTTP)
        add_filter('rest_authentication_errors', array($this, 'api_key_auth'));
    }

    public function api_key_auth($result) {
        // Always check for our custom API key first, regardless of what other auth did
        $provided_key = isset($_SERVER['HTTP_X_GABOCHIE_KEY']) ? $_SERVER['HTTP_X_GABOCHIE_KEY'] : '';
        if (empty($provided_key)) {
            $provided_key = isset($_GET['gabochie_key']) ? $_GET['gabochie_key'] : '';
        }

        if (!empty($provided_key)) {
            $stored_key = get_option('gabochie_sync_local_api_key', '');
            if (!empty($stored_key) && hash_equals($stored_key, $provided_key)) {
                $admin = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
                if (!empty($admin)) {
                    wp_set_current_user((int) $admin[0]);
                    return true;
                }
            }
            return new WP_Error('rest_forbidden', 'Invalid API key.', array('status' => 403));
        }

        // No custom key provided — pass through to normal WordPress auth
        return $result;
    }

    public function activate() {
        if (class_exists('Gabochie_Recovery')) {
            Gabochie_Recovery::activate();
        }
        $defaults = array(
            'remote_url' => '',
            'remote_username' => '',
            'api_key' => '',
            'git_repo_path' => '',
            'git_remote' => 'origin',
            'git_branch' => 'main',
            'sync_post_types' => array('post', 'page'),
            'sync_taxonomies' => array('category', 'post_tag'),
            'sync_options' => array(),
            'last_sync' => '',
            'webhook_secret' => '',
        );
        foreach ($defaults as $key => $value) {
            if (false === get_option('gabochie_sync_' . $key)) {
                update_option('gabochie_sync_' . $key, $value);
            }
        }

        // Generate local API key if not exists
        if (false === get_option('gabochie_sync_local_api_key')) {
            update_option('gabochie_sync_local_api_key', wp_generate_password(32, false));
        }
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function load_textdomain() {
        load_plugin_textdomain('gabochie-sync', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function settings_link($links) {
        $url = admin_url('options-general.php?page=gabochie-sync');
        array_unshift($links, '<a href="' . esc_url($url) . '">Settings</a>');
        return $links;
    }

    public function add_admin_menu() {
        // Top-level menu: Gabochie Design
        add_menu_page(
            'Gabochie Design',
            'Gabochie Design',
            'manage_options',
            'gabochie-hub',
            array('Gabochie_Hub', 'render_dashboard'),
            'dashicons-building',
            3
        );
        // Submenu: Dashboard
        add_submenu_page(
            'gabochie-hub',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'gabochie-hub',
            array('Gabochie_Hub', 'render_dashboard')
        );
        // Submenu: Strategy
        add_submenu_page(
            'gabochie-hub',
            'Agentic Strategy',
            'Strategy',
            'manage_options',
            'gabochie-strategy',
            array('Gabochie_Strategy', 'render_page')
        );
        // Submenu: Build Roadmap
        add_submenu_page(
            'gabochie-hub',
            'Build Roadmap',
            'Build Roadmap',
            'manage_options',
            'gabochie-roadmap',
            array('Gabochie_Roadmap', 'render_page')
        );
        // Submenu: Mega Revenue Strategy
        add_submenu_page(
            'gabochie-hub',
            'Mega Revenue Strategy',
            'Revenue Strategy',
            'manage_options',
            'gabochie-revenue',
            array('Gabochie_Revenue', 'render_page')
        );
        // Submenu: Sync (under Settings)
        add_submenu_page(
            'gabochie-hub',
            'Sync Settings',
            'Sync',
            'manage_options',
            'gabochie-sync',
            array('Gabochie_Sync_Admin', 'render_page')
        );
    }

    public function enqueue_admin_assets($hook) {
        // Load on any page under our menu
        $our_pages = array('gabochie-hub', 'gabochie-strategy', 'gabochie-roadmap', 'gabochie-revenue', 'gabochie-sync', 'toplevel_page_gabochie-hub');
        if (!in_array($hook, $our_pages) && strpos($hook, 'gabochie') === false) {
            return;
        }
        wp_enqueue_style('gabochie-sync-admin', GABOCHIE_SYNC_URL . 'assets/admin.css', array(), GABOCHIE_SYNC_VERSION);
        wp_enqueue_script('gabochie-sync-admin', GABOCHIE_SYNC_URL . 'assets/admin.js', array('jquery'), GABOCHIE_SYNC_VERSION, true);
        wp_localize_script('gabochie-sync-admin', 'gabochieSync', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('gabochie-sync/v1/'),
            'nonce' => wp_create_nonce('gabochie_sync_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'confirmPush' => esc_html__('Are you sure you want to PUSH local content to live? This will overwrite remote content.', 'gabochie-sync'),
            'confirmPull' => esc_html__('Are you sure you want to PULL live content to local? This will overwrite local content.', 'gabochie-sync'),
            'confirmDeploy' => esc_html__('Are you sure you want to deploy code to the remote server?', 'gabochie-sync'),
        ));
    }

    public function register_rest_routes() {
        $controller = new Gabochie_Sync_REST();
        register_rest_route('gabochie-sync/v1', '/push', array(
            'methods' => 'POST',
            'callback' => array($controller, 'push_content'),
            'permission_callback' => array($controller, 'check_permission'),
        ));
        register_rest_route('gabochie-sync/v1', '/pull', array(
            'methods' => 'POST',
            'callback' => array($controller, 'pull_content'),
            'permission_callback' => array($controller, 'check_permission'),
        ));
        register_rest_route('gabochie-sync/v1', '/status', array(
            'methods' => 'GET',
            'callback' => array($controller, 'get_status'),
            'permission_callback' => array($controller, 'check_permission'),
        ));
        register_rest_route('gabochie-sync/v1', '/diff', array(
            'methods' => 'GET',
            'callback' => array($controller, 'get_diff'),
            'permission_callback' => array($controller, 'check_permission'),
        ));
        register_rest_route('gabochie-sync/v1', '/deploy', array(
            'methods' => 'POST',
            'callback' => array($controller, 'deploy_code'),
            'permission_callback' => array($controller, 'check_permission'),
        ));
        register_rest_route('gabochie-sync/v1', '/export', array(
            'methods' => 'GET',
            'callback' => array($controller, 'export_content'),
            'permission_callback' => array($controller, 'check_permission'),
        ));
        register_rest_route('gabochie-sync/v1', '/import', array(
            'methods' => 'POST',
            'callback' => array($controller, 'import_content'),
            'permission_callback' => array($controller, 'check_permission'),
        ));
        register_rest_route('gabochie-sync/v1', '/log', array(
            'methods' => 'GET',
            'callback' => array($controller, 'get_log'),
            'permission_callback' => array($controller, 'check_permission'),
        ));
        register_rest_route('gabochie-sync/v1', '/merge', array(
            'methods' => 'POST',
            'callback' => array($controller, 'merge_content'),
            'permission_callback' => array($controller, 'check_permission'),
        ));
    }
}

class Gabochie_Sync_REST {
    public function check_permission() {
        return current_user_can('manage_options');
    }

    public function push_content($request) {
        $sync = new Gabochie_Content_Sync();
        $dry_run = $request->get_param('dry_run') === 'true';
        $post_types = $request->get_param('post_types');
        if (!empty($post_types) && is_string($post_types)) {
            $post_types = array_map('sanitize_text_field', explode(',', $post_types));
        }
        $result = $sync->push($dry_run, $post_types);
        return rest_ensure_response($result);
    }

    public function pull_content($request) {
        $sync = new Gabochie_Content_Sync();
        $dry_run = $request->get_param('dry_run') === 'true';
        $result = $sync->pull($dry_run);
        return rest_ensure_response($result);
    }

    public function get_status($request) {
        $sync = new Gabochie_Content_Sync();
        return rest_ensure_response($sync->get_status());
    }

    public function get_diff($request) {
        $sync = new Gabochie_Content_Sync();
        return rest_ensure_response($sync->get_diff());
    }

    public function deploy_code($request) {
        $deploy = new Gabochie_Git_Deploy();
        $result = $deploy->pull_remote();
        return rest_ensure_response($result);
    }

    public function export_content($request) {
        $sync = new Gabochie_Content_Sync();
        $post_types = $request->get_param('post_types');
        $data = $sync->export($post_types);
        return rest_ensure_response($data);
    }

    public function import_content($request) {
        $sync = new Gabochie_Content_Sync();
        $data = $request->get_json_body();
        $result = $sync->import($data);
        return rest_ensure_response($result);
    }

    public function get_log($request) {
        $log = get_option('gabochie_sync_log', array());
        $limit = $request->get_param('limit') ?: 50;
        $log = array_slice($log, -$limit, $limit, true);
        return rest_ensure_response(array_reverse($log, true));
    }

    public function merge_content($request) {
        $sync = new Gabochie_Content_Sync();
        $dry_run = $request->get_param('dry_run') === 'true';
        $direction = in_array($request->get_param('direction'), array('push', 'pull')) ? $request->get_param('direction') : 'push';
        $post_types = $request->get_param('post_types');
        if (!empty($post_types) && is_string($post_types)) {
            $post_types = array_map('sanitize_text_field', explode(',', $post_types));
        }
        $result = $sync->merge($dry_run, $direction, $post_types);
        return rest_ensure_response($result);
    }
}

Gabochie_Sync::instance();
