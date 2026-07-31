<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\AI\Client;

use AIGrowthOS\Infrastructure\AI\Provider\AIProvider;
use AIGrowthOS\Infrastructure\AI\Provider\AIResponse;
use AIGrowthOS\Infrastructure\AI\Provider\OpenAIProvider;
use AIGrowthOS\Infrastructure\AI\Provider\AnthropicProvider;
use AIGrowthOS\Infrastructure\AI\Provider\GeminiProvider;
use AIGrowthOS\Infrastructure\AI\Provider\OllamaProvider;
use AIGrowthOS\Infrastructure\AI\Provider\OpenRouterProvider;
use AIGrowthOS\Infrastructure\Logging\Logger;

class AIOrchestrator
{
    private array $providers = [];
    private array $rateLimiters = [];

    public function __construct()
    {
        $this->registerProvider(new OpenAIProvider());
        $this->registerProvider(new AnthropicProvider());
        $this->registerProvider(new GeminiProvider());
        $this->registerProvider(new OllamaProvider());
        $this->registerProvider(new OpenRouterProvider());
    }

    public function registerProvider(AIProvider $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    public function getProvider(string $name): ?AIProvider
    {
        return $this->providers[$name] ?? null;
    }

    public function getAvailableProviders(): array
    {
        return array_filter($this->providers, fn(AIProvider $p) => $p->isAvailable());
    }

    public function complete(array $messages, array $options = []): AIResponse
    {
        $providerName = $options['provider'] ?? $this->selectBestProvider($options);
        $retries = $options['retries'] ?? 2;
        $lastError = null;

        for ($i = 0; $i <= $retries; $i++) {
            try {
                $provider = $this->providers[$providerName] ?? throw new \RuntimeException("Provider '$providerName' not found");

                if (!$provider->isAvailable()) {
                    throw new \RuntimeException("Provider '$providerName' is not available");
                }

                $this->checkRateLimit($providerName);

                $response = $provider->complete($messages, $options);

                $this->recordUsage($providerName, $response);

                return $response;
            } catch (\Throwable $e) {
                $lastError = $e;
                Logger::warning("AI request failed", [
                    'provider' => $providerName,
                    'error'    => $e->getMessage(),
                    'attempt'  => $i + 1,
                ]);

                $providerName = $this->fallbackProvider($providerName);
                if ($providerName === null) {
                    break;
                }
            }
        }

        throw new \RuntimeException("All AI providers failed. Last error: " . ($lastError?->getMessage() ?? 'Unknown'));
    }

    public function stream(array $messages, callable $onChunk, array $options = []): AIResponse
    {
        $providerName = $options['provider'] ?? $this->selectBestProvider($options);
        $provider = $this->providers[$providerName] ?? throw new \RuntimeException("Provider '$providerName' not found");

        if (!$provider->isAvailable()) {
            throw new \RuntimeException("Provider '$providerName' is not available");
        }

        $this->checkRateLimit($providerName);
        $response = $provider->stream($messages, $onChunk, $options);
        $this->recordUsage($providerName, $response);

        return $response;
    }

    private function selectBestProvider(array $options): string
    {
        $available = $this->getAvailableProviders();
        if (empty($available)) {
            throw new \RuntimeException('No AI providers available. Please configure at least one API key.');
        }

        $preferred = $options['preferred_provider'] ?? 'openai';
        if (isset($available[$preferred])) {
            return $preferred;
        }

        return array_key_first($available);
    }

    private function fallbackProvider(string $current): ?string
    {
        $order = ['openai', 'anthropic', 'gemini', 'openrouter', 'ollama'];
        $found = false;
        foreach ($order as $name) {
            if ($name === $current) {
                $found = true;
                continue;
            }
            if ($found && isset($this->providers[$name]) && $this->providers[$name]->isAvailable()) {
                Logger::info("Failing over to provider: $name");
                return $name;
            }
        }
        return null;
    }

    private function checkRateLimit(string $provider): void
    {
        $now = microtime(true);
        $window = 60;
        $maxRequests = 30;

        if (!isset($this->rateLimiters[$provider])) {
            $this->rateLimiters[$provider] = [];
        }

        $this->rateLimiters[$provider] = array_filter(
            $this->rateLimiters[$provider],
            fn(float $time) => ($now - $time) < $window
        );

        if (count($this->rateLimiters[$provider]) >= $maxRequests) {
            $oldest = min($this->rateLimiters[$provider]);
            $wait = $window - ($now - $oldest);
            if ($wait > 0) {
                usleep((int) ($wait * 1000000));
            }
        }

        $this->rateLimiters[$provider][] = $now;
    }

    private function recordUsage(string $provider, AIResponse $response): void
    {
        Logger::debug("AI usage", [
            'provider'      => $provider,
            'model'         => $response->getModel(),
            'input_tokens'  => $response->getInputTokens(),
            'output_tokens' => $response->getOutputTokens(),
            'cost'          => $response->getCost(),
            'duration_ms'   => $response->getDurationMs(),
        ]);
    }
}
