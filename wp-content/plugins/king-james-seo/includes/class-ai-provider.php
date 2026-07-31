<?php

namespace KingJamesSEO\Includes;

defined('ABSPATH') || exit;

abstract class AI_Provider {
    protected string $api_key = '';
    protected string $model = '';

    public function __construct(string $api_key, string $model = '') {
        $this->api_key = $api_key;
        $this->model = $model;
    }

    public static function factory(string $provider = ''): ?self {
        $settings = get_option('kjs_ai_settings', []);
        $provider = $provider ?: ($settings['provider'] ?? 'openai');
        $api_key = $settings[$provider . '_api_key'] ?? '';
        $model = $settings[$provider . '_model'] ?? '';

        if (!$api_key) return null;

        switch ($provider) {
            case 'openai':
                return new AI_Provider_OpenAI($api_key, $model ?: 'gpt-4o');
            case 'anthropic':
                return new AI_Provider_Anthropic($api_key, $model ?: 'claude-sonnet-4-20250514');
            case 'gemini':
                return new AI_Provider_Gemini($api_key, $model ?: 'gemini-2.5-pro');
            default:
                return null;
        }
    }

    abstract public function generate(string $prompt, array $options = []): string;

    abstract public function rewrite(string $content, string $instructions, array $options = []): string;

    abstract public function chat(array $messages, array $options = []): string;

    public function set_model(string $model): void {
        $this->model = $model;
    }
}
