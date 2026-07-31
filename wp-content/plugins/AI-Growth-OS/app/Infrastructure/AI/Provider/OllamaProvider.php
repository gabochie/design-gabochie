<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\AI\Provider;

class OllamaProvider implements AIProvider
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? (defined('AI_GROWTH_OS_OLLAMA_URL') ? AI_GROWTH_OS_OLLAMA_URL : 'http://localhost:11434');
    }

    public function name(): string
    {
        return 'ollama';
    }

    public function isAvailable(): bool
    {
        $response = wp_remote_get($this->baseUrl . '/api/tags', ['timeout' => 5]);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    public function complete(array $messages, array $options = []): AIResponse
    {
        $model = $options['model'] ?? 'llama3';
        $start = microtime(true);

        $response = wp_remote_post($this->baseUrl . '/api/chat', [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'model'    => $model,
                'messages' => $messages,
                'stream'   => false,
            ]),
            'timeout' => $options['timeout'] ?? 120,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('Ollama request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $duration = (microtime(true) - $start) * 1000;
        $content = $body['message']['content'] ?? '';

        return new AIResponse(
            content: $content,
            model: $model,
            provider: $this->name(),
            durationMs: $duration,
            raw: $body,
        );
    }

    public function stream(array $messages, callable $onChunk, array $options = []): AIResponse
    {
        $model = $options['model'] ?? 'llama3';
        $start = microtime(true);
        $fullContent = '';

        $response = wp_remote_post($this->baseUrl . '/api/chat', [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'model'    => $model,
                'messages' => $messages,
                'stream'   => true,
            ]),
            'timeout' => $options['timeout'] ?? 120,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('Ollama stream failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        foreach (explode("\n", trim($body)) as $line) {
            $data = json_decode($line, true);
            if (isset($data['message']['content'])) {
                $fullContent .= $data['message']['content'];
                $onChunk($data['message']['content']);
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
        $response = wp_remote_post($this->baseUrl . '/api/embeddings', [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'model' => 'nomic-embed-text',
                'input' => $input,
            ]),
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('Ollama embedding failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['embedding'] ?? [];
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
        return 8192;
    }

    public function models(): array
    {
        return ['llama3', 'llama3.1', 'mistral', 'mixtral', 'codellama'];
    }
}
