<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\AI\Provider;

class AIResponse
{
    public function __construct(
        private readonly string $content,
        private readonly string $model,
        private readonly string $provider,
        private readonly int $inputTokens = 0,
        private readonly int $outputTokens = 0,
        private readonly float $cost = 0.0,
        private readonly float $durationMs = 0.0,
        private readonly ?array $raw = null,
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getInputTokens(): int
    {
        return $this->inputTokens;
    }

    public function getOutputTokens(): int
    {
        return $this->outputTokens;
    }

    public function getCost(): float
    {
        return $this->cost;
    }

    public function getDurationMs(): float
    {
        return $this->durationMs;
    }

    public function getRaw(): ?array
    {
        return $this->raw;
    }

    public function toArray(): array
    {
        return [
            'content'       => $this->content,
            'model'         => $this->model,
            'provider'      => $this->provider,
            'input_tokens'  => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'cost'          => $this->cost,
            'duration_ms'   => $this->durationMs,
        ];
    }
}
