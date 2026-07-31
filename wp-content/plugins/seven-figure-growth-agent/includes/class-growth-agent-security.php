<?php
class Growth_Agent_Security {
    public static function verify_nonce($nonce, $action = 'growth_agent_nonce') {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_send_json_error(array('message' => 'Invalid security token'), 403);
            exit;
        }
    }

    public static function check_capability($capability = 'manage_options') {
        if (!current_user_can($capability)) {
            wp_send_json_error(array('message' => 'Unauthorized access'), 401);
            exit;
        }
    }

    public static function sanitize_input_data($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize_input_data($value);
            }
        } elseif (is_string($data)) {
            $data = sanitize_text_field($data);
        }
        return $data;
    }
}
