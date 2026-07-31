<?php
abstract class Growth_AI_Base_Provider {
    abstract public function analyze_website($url, $context);
    abstract public function generate_blueprint($audit);
    abstract public function generate_actions();
}

class Growth_AI_Provider_Abstraction {
    public static function get_provider() {
        $settings = get_option('growth_agent_settings', array());
        $type = isset($settings['primary_provider']) ? $settings['primary_provider'] : 'gemini';

        switch ($type) {
            case 'openai':
                require_once GROWTH_AGENT_PATH . 'ai/providers/class-openai-provider.php';
                return new Growth_AI_OpenAI_Provider($settings);
            case 'anthropic':
                require_once GROWTH_AGENT_PATH . 'ai/providers/class-anthropic-provider.php';
                return new Growth_AI_Anthropic_Provider($settings);
            case 'ollama':
                require_once GROWTH_AGENT_PATH . 'ai/providers/class-ollama-provider.php';
                return new Growth_AI_Ollama_Provider($settings);
            case 'gemini':
            default:
                require_once GROWTH_AGENT_PATH . 'ai/providers/class-gemini-provider.php';
                return new Growth_AI_Gemini_Provider($settings);
        }
    }
}
