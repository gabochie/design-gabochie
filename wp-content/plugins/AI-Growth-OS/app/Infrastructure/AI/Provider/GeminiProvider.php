<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\AI\Provider;

class GeminiProvider implements AIProvider
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (defined('AI_GROWTH_OS_GEMINI_KEY') ? AI_GROWTH_OS_GEMINI_KEY : '');
    }

    public function name(): string
    {
        return 'gemini';
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    public function complete(array $messages, array $options = []): AIResponse
    {
        $model = $options['model'] ?? 'gemini-2.0-flash';
        $start = microtime(true);

        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            $contents[] = [
                'role'  => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content'] ?? '']],
            ];
        }

        $response = wp_remote_post(
            $this->baseUrl . "/models/{$model}:generateContent?key={$this->apiKey}",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => wp_json_encode(['contents' => $contents]),
                'timeout' => $options['timeout'] ?? 60,
            ]
        );

        if (is_wp_error($response)) {
            throw new \RuntimeException('Gemini request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $duration = (microtime(true) - $start) * 1000;
        $content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $usage = $body['usageMetadata'] ?? [];

        return new AIResponse(
            content: $content,
            model: $model,
            provider: $this->name(),
            inputTokens: $usage['promptTokenCount'] ?? 0,
            outputTokens: $usage['candidatesTokenCount'] ?? 0,
            durationMs: $duration,
            raw: $body,
        );
    }

    public function stream(array $messages, callable $onChunk, array $options = []): AIResponse
    {
        $model = $options['model'] ?? 'gemini-2.0-flash';
        $start = microtime(true);
        $fullContent = '';

        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            $contents[] = [
                'role'  => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content'] ?? '']],
            ];
        }

        $response = wp_remote_post(
            $this->baseUrl . "/models/{$model}:streamGenerateContent?key={$this->apiKey}",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => wp_json_encode(['contents' => $contents]),
                'timeout' => $options['timeout'] ?? 120,
            ]
        );

        if (is_wp_error($response)) {
            throw new \RuntimeException('Gemini stream failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        foreach (explode("\n", trim($body)) as $line) {
            if (empty($line)) continue;
            $data = json_decode($line, true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if ($text !== '') {
                $fullContent .= $text;
                $onChunk($text);
            }
        }

        $duration = (microtime(true) - $start) * 1000;

        return new AIResponse(
            content: $fullContent,
            model: $model,
            provider: $this->name(),
            durationMs: $duration,
        );
    }

    public function embed(string $input): array
    {
        $model = 'text-embedding-004';
        $response = wp_remote_post(
            $this->baseUrl . "/models/{$model}:embedContent?key={$this->apiKey}",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode([
                    'model' => "models/{$model}",
                    'content' => ['parts' => [['text' => $input]]],
                ]),
            ]
        );

        if (is_wp_error($response)) {
            throw new \RuntimeException('Gemini embedding failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['embedding']['values'] ?? [];
    }

    public function costPerInputToken(): float
    {
        return 0.0000001;
    }

    public function costPerOutputToken(): float
    {
        return 0.0000004;
    }

    public function maxTokens(): int
    {
        return 1048576;
    }

    public function models(): array
    {
        return ['gemini-2.0-flash', 'gemini-2.0-pro', 'gemini-1.5-pro', 'gemini-1.5-flash'];
    }
}
