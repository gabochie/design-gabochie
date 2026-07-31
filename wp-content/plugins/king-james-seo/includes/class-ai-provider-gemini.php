<?php

namespace KingJamesSEO\Includes;

defined('ABSPATH') || exit;

class AI_Provider_Gemini extends AI_Provider {
    public function generate(string $prompt, array $options = []): string {
        $system = $options['system'] ?? 'You are a professional content writer. Write high-quality, original content.';
        $temperature = $options['temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? 2048;

        return $this->call_api($prompt, $system, $temperature, $max_tokens);
    }

    public function rewrite(string $content, string $instructions, array $options = []): string {
        $system = $options['system'] ?? 'You are a professional content editor. Rewrite content following the given instructions. Preserve the original meaning and factual accuracy. Improve clarity, engagement, and SEO value.';
        $temperature = $options['temperature'] ?? 0.5;
        $max_tokens = $options['max_tokens'] ?? 4096;

        $prompt = "System: {$system}\n\nOriginal content:\n\n{$content}\n\nInstructions: {$instructions}\n\nRewrite the content following these instructions. Return only the rewritten content, no explanations.";

        return $this->call_api($prompt, '', $temperature, $max_tokens);
    }

    public function chat(array $messages, array $options = []): string {
        $combined = '';
        foreach ($messages as $msg) {
            $combined .= ucfirst($msg['role']) . ': ' . $msg['content'] . "\n";
        }
        $system = $options['system'] ?? '';
        $temperature = $options['temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? 2048;

        return $this->call_api($combined, $system, $temperature, $max_tokens);
    }

    private function call_api(string $prompt, string $system, float $temperature, int $max_tokens): string {
        $contents = [];
        if ($system) {
            $contents[] = ['role' => 'user', 'parts' => [['text' => "[System Instructions]\n{$system}"]]];
            $contents[] = ['role' => 'model', 'parts' => [['text' => 'Understood. I will follow these instructions.']]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $prompt]]];

        $url = add_query_arg('key', $this->api_key, "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent");

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => $max_tokens,
                ],
            ]),
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

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}
