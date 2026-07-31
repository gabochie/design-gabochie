<?php
class Growth_AI_OpenAI_Provider extends Growth_AI_Base_Provider {
    private $api_key;

    public function __construct($settings) {
        $this->api_key = !empty($settings['openai_api_key']) ? $settings['openai_api_key'] : '';
    }

    public function analyze_website($url, $context) {
        return array('status' => 'openai_audit_completed');
    }

    public function generate_blueprint($audit) {
        return array('status' => 'openai_blueprint_completed');
    }

    public function generate_actions() {
        return array();
    }
}
