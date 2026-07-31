<?php
class Growth_AI_Ollama_Provider extends Growth_AI_Base_Provider {
    public function __construct($settings) {}

    public function analyze_website($url, $context) {
        return array('status' => 'ollama_audit_completed');
    }

    public function generate_blueprint($audit) {
        return array('status' => 'ollama_blueprint_completed');
    }

    public function generate_actions() {
        return array();
    }
}
