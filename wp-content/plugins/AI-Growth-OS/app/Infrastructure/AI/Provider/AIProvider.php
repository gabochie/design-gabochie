<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\AI\Provider;

interface AIProvider
{
    public function name(): string;
    public function isAvailable(): bool;
    public function complete(array $messages, array $options = []): AIResponse;
    public function stream(array $messages, callable $onChunk, array $options = []): AIResponse;
    public function embed(string $input): array;
    public function costPerInputToken(): float;
    public function costPerOutputToken(): float;
    public function maxTokens(): int;
    public function models(): array;
}
