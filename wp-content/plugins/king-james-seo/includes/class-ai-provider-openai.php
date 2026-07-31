<?php

namespace KingJamesSEO\Includes;

defined('ABSPATH') || exit;

class AI_Provider_OpenAI extends AI_Provider {
    private string $api_url = 'https://api.openai.com/v1/chat/completions';

    public function generate(string $prompt, array $options = []): string {
        $system = $options['system'] ?? 'You are a professional content writer. Write high-quality, original content.';
        $temperature = $options['temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? 2048;

        return $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ], compact('temperature', 'max_tokens'));
    }

    public function rewrite(string $content, string $instructions, array $options = []): string {
        $system = $options['system'] ?? 'You are a professional content editor. Rewrite content following the given instructions. Preserve the original meaning and factual accuracy. Improve clarity, engagement, and SEO value.';
        $temperature = $options['temperature'] ?? 0.5;
        $max_tokens = $options['max_tokens'] ?? 4096;

        $prompt = "Original content:\n\n{$content}\n\nInstructions: {$instructions}\n\nRewrite the content following these instructions. Return only the rewritten content, no explanations.";

        return $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ], compact('temperature', 'max_tokens'));
    }

    public function chat(array $messages, array $options = []): string {
        $temperature = $options['temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? 2048;

        $body = wp_json_encode([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        ]);

        $response = wp_remote_post($this->api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
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

        return $data['choices'][0]['message']['content'] ?? '';
    }
}
