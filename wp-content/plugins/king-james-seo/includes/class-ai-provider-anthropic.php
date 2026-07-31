<?php

namespace KingJamesSEO\Includes;

defined('ABSPATH') || exit;

class AI_Provider_Anthropic extends AI_Provider {
    private string $api_url = 'https://api.anthropic.com/v1/messages';

    public function generate(string $prompt, array $options = []): string {
        $system = $options['system'] ?? 'You are a professional content writer. Write high-quality, original content.';
        $temperature = $options['temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? 2048;

        return $this->call_api($system, [
            ['role' => 'user', 'content' => $prompt],
        ], $temperature, $max_tokens);
    }

    public function rewrite(string $content, string $instructions, array $options = []): string {
        $system = $options['system'] ?? 'You are a professional content editor. Rewrite content following the given instructions. Preserve the original meaning and factual accuracy. Improve clarity, engagement, and SEO value.';
        $temperature = $options['temperature'] ?? 0.5;
        $max_tokens = $options['max_tokens'] ?? 4096;

        $prompt = "Original content:\n\n{$content}\n\nInstructions: {$instructions}\n\nRewrite the content following these instructions. Return only the rewritten content, no explanations.";

        return $this->call_api($system, [
            ['role' => 'user', 'content' => $prompt],
        ], $temperature, $max_tokens);
    }

    public function chat(array $messages, array $options = []): string {
        $system = $options['system'] ?? 'You are a helpful assistant.';
        $temperature = $options['temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? 2048;

        return $this->call_api($system, $messages, $temperature, $max_tokens);
    }

    private function call_api(string $system, array $messages, float $temperature, int $max_tokens): string {
        $body = wp_json_encode([
            'model' => $this->model,
            'system' => $system,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        ]);

        $response = wp_remote_post($this->api_url, [
            'headers' => [
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            'body' => $body,
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            return '// Error: ' . $response->get_error_message();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!empty($data['error'])) {
            return '// Error: ' . ($data['error']['message'] ?? 'Unknown API error');
        }

        return $data['content'][0]['text'] ?? '';
    }
}
