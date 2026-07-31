<?php
class Growth_AI_Anthropic_Provider extends Growth_AI_Base_Provider {
    private $api_key;

    public function __construct($settings) {
        $this->api_key = !empty($settings['anthropic_api_key']) ? $settings['anthropic_api_key'] : '';
    }

    public function analyze_website($url, $context) {
        return array('status' => 'anthropic_audit_completed');
    }

    public function generate_blueprint($audit) {
        return array('status' => 'anthropic_blueprint_completed');
    }

    public function generate_actions() {
        return array();
    }
}
