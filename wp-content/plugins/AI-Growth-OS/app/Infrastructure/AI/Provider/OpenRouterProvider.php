<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\AI\Provider;

class OpenRouterProvider implements AIProvider
{
    private string $apiKey;
    private string $baseUrl = 'https://openrouter.ai/api/v1';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (defined('AI_GROWTH_OS_OPENROUTER_KEY') ? AI_GROWTH_OS_OPENROUTER_KEY : '');
    }

    public function name(): string
    {
        return 'openrouter';
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    public function complete(array $messages, array $options = []): AIResponse
    {
        $model = $options['model'] ?? 'openai/gpt-4o';
        $start = microtime(true);

        $response = wp_remote_post($this->baseUrl . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => get_site_url(),
            ],
            'body'    => wp_json_encode([
                'model'    => $model,
                'messages' => $messages,
            ]),
            'timeout' => $options['timeout'] ?? 60,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('OpenRouter request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $duration = (microtime(true) - $start) * 1000;

        $usage = $body['usage'] ?? [];
        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;

        return new AIResponse(
            content: $body['choices'][0]['message']['content'] ?? '',
            model: $model,
            provider: $this->name(),
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            durationMs: $duration,
            raw: $body,
        );
    }

    public function stream(array $messages, callable $onChunk, array $options = []): AIResponse
    {
        $model = $options['model'] ?? 'openai/gpt-4o';
        $start = microtime(true);
        $fullContent = '';

        $response = wp_remote_post($this->baseUrl . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => get_site_url(),
            ],
            'body'    => wp_json_encode([
                'model'    => $model,
                'messages' => $messages,
                'stream'   => true,
            ]),
            'timeout' => $options['timeout'] ?? 120,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('OpenRouter stream failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        foreach (explode("\n", $body) as $line) {
            if (str_starts_with($line, 'data: ') && !str_contains($line, '[DONE]')) {
                $data = json_decode(substr($line, 6), true);
                $delta = $data['choices'][0]['delta']['content'] ?? '';
                if ($delta !== '') {
                    $fullContent .= $delta;
                    $onChunk($delta);
                }
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
        throw new \RuntimeException('OpenRouter embeddings not supported.');
    }

    public function costPerInputToken(): float
    {
        return 0.0;
    }

    public function costPerOutputToken(): float
    {
        return 0.0;
    }

    public function maxTokens(): int
    {
        return 128000;
    }

    public function models(): array
    {
        return ['openai/gpt-4o', 'openai/gpt-4o-mini', 'anthropic/claude-sonnet-4', 'google/gemini-2.0-flash'];
    }
}
