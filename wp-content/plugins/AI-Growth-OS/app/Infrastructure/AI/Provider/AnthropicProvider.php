<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\AI\Provider;

class AnthropicProvider implements AIProvider
{
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (defined('AI_GROWTH_OS_ANTHROPIC_KEY') ? AI_GROWTH_OS_ANTHROPIC_KEY : '');
    }

    public function name(): string
    {
        return 'anthropic';
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    public function complete(array $messages, array $options = []): AIResponse
    {
        $model = $options['model'] ?? 'claude-sonnet-4-20250514';
        $start = microtime(true);

        $system = '';
        $apiMessages = [];
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'system') {
                $system = $msg['content'] ?? '';
            } else {
                $apiMessages[] = $msg;
            }
        }

        $body = ['model' => $model, 'messages' => $apiMessages, 'max_tokens' => $options['max_tokens'] ?? 4096];
        if ($system) {
            $body['system'] = $system;
        }

        $response = wp_remote_post($this->baseUrl . '/messages', [
            'headers' => [
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
        ],
            'body'    => wp_json_encode($body),
            'timeout' => $options['timeout'] ?? 60,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('Anthropic request failed: ' . $response->get_error_message());
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        $duration = (microtime(true) - $start) * 1000;

        $usage = $result['usage'] ?? [];
        $inputTokens = $usage['input_tokens'] ?? 0;
        $outputTokens = $usage['output_tokens'] ?? 0;
        $cost = ($inputTokens * $this->costPerInputToken()) + ($outputTokens * $this->costPerOutputToken());

        $content = '';
        foreach ($result['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $content .= $block['text'];
            }
        }

        return new AIResponse(
            content: $content,
            model: $model,
            provider: $this->name(),
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            cost: $cost,
            durationMs: $duration,
            raw: $result,
        );
    }

    public function stream(array $messages, callable $onChunk, array $options = []): AIResponse
    {
        $model = $options['model'] ?? 'claude-sonnet-4-20250514';
        $start = microtime(true);
        $fullContent = '';

        $response = wp_remote_post($this->baseUrl . '/messages', [
            'headers' => [
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ],
            'body'    => wp_json_encode([
                'model'      => $model,
                'messages'   => $messages,
                'max_tokens' => $options['max_tokens'] ?? 4096,
                'stream'     => true,
            ]),
            'timeout' => $options['timeout'] ?? 120,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('Anthropic stream failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        foreach (explode("\n", $body) as $line) {
            if (str_starts_with($line, 'data: ')) {
                $data = json_decode(substr($line, 6), true);
                if (($data['type'] ?? '') === 'content_block_delta') {
                    $text = $data['delta']['text'] ?? '';
                    if ($text !== '') {
                        $fullContent .= $text;
                        $onChunk($text);
                    }
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
        throw new \RuntimeException('Anthropic does not support embeddings via this interface.');
    }

    public function costPerInputToken(): float
    {
        return 0.000003;
    }

    public function costPerOutputToken(): float
    {
        return 0.000015;
    }

    public function maxTokens(): int
    {
        return 200000;
    }

    public function models(): array
    {
        return ['claude-sonnet-4-20250514', 'claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022'];
    }
}
