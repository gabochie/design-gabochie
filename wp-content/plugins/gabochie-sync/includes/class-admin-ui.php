<?php
if (!defined('ABSPATH')) exit;

class Gabochie_Sync_Admin {

    public static function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submissions
        if (isset($_POST['gabochie_sync_save']) && check_admin_referer('gabochie_sync_save_action')) {
            self::save_settings($_POST);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }

        $status = self::get_connection_status();
        include GABOCHIE_SYNC_DIR . 'templates/admin-page.php';
    }

    private static function save_settings($data) {
        $text_fields = array('remote_url', 'api_key', 'remote_username', 'git_repo_path', 'git_remote', 'git_branch', 'webhook_secret');
        foreach ($text_fields as $field) {
            if (isset($data[$field])) {
                update_option('gabochie_sync_' . $field, sanitize_text_field($data[$field]));
            }
        }

        if (isset($data['sync_post_types'])) {
            update_option('gabochie_sync_sync_post_types', array_map('sanitize_text_field', $data['sync_post_types']));
        }
    }

    private static function get_connection_status() {
        $remote_url = get_option('gabochie_sync_remote_url', '');
        if (empty($remote_url)) {
            return 'disconnected';
        }
        $remote_user = get_option('gabochie_sync_remote_username', '');
        $api_key = get_option('gabochie_sync_api_key', '');
        $headers = array('X-Gabochie-Key' => $api_key);
        if (!empty($remote_user) && !empty($api_key)) {
            $headers['Authorization'] = 'Basic ' . base64_encode($remote_user . ':' . $api_key);
        }
        $response = wp_remote_get($remote_url . '/wp-json/gabochie-sync/v1/status', array(
            'headers' => $headers,
            'timeout' => 10,
        ));
        return (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) ? 'connected' : 'error';
    }
}
