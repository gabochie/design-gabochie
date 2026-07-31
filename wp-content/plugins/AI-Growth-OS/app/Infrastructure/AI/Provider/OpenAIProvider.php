<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\AI\Provider;

class OpenAIProvider implements AIProvider
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (defined('AI_GROWTH_OS_OPENAI_KEY') ? AI_GROWTH_OS_OPENAI_KEY : '');
    }

    public function name(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    public function complete(array $messages, array $options = []): AIResponse
    {
        $model = $options['model'] ?? 'gpt-4o';
        $start = microtime(true);

        $response = wp_remote_post($this->baseUrl . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode(array_merge([
                'model'    => $model,
                'messages' => $messages,
            ], $options)),
            'timeout' => $options['timeout'] ?? 60,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('OpenAI request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $duration = (microtime(true) - $start) * 1000;

        $usage = $body['usage'] ?? [];
        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;
        $cost = ($inputTokens * $this->costPerInputToken()) + ($outputTokens * $this->costPerOutputToken());

        return new AIResponse(
            content: $body['choices'][0]['message']['content'] ?? '',
            model: $model,
            provider: $this->name(),
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            cost: $cost,
            durationMs: $duration,
            raw: $body,
        );
    }

    public function stream(array $messages, callable $onChunk, array $options = []): AIResponse
    {
        $model = $options['model'] ?? 'gpt-4o';
        $start = microtime(true);
        $fullContent = '';

        $response = wp_remote_post($this->baseUrl . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode(array_merge([
                'model'    => $model,
                'messages' => $messages,
                'stream'   => true,
            ], $options)),
            'timeout' => $options['timeout'] ?? 120,
            'stream'  => true,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('OpenAI stream failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        foreach (explode("\n", $body) as $line) {
            if (str_starts_with($line, 'data: ')) {
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
        $response = wp_remote_post($this->baseUrl . '/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode([
                'model' => 'text-embedding-3-small',
                'input' => $input,
            ]),
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('OpenAI embedding failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['data'][0]['embedding'] ?? [];
    }

    public function costPerInputToken(): float
    {
        return 0.0000025;
    }

    public function costPerOutputToken(): float
    {
        return 0.00001;
    }

    public function maxTokens(): int
    {
        return 128000;
    }

    public function models(): array
    {
        return ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'];
    }
}
