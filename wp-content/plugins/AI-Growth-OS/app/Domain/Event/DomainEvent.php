<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\Event;

interface DomainEvent
{
    public function occurredOn(): \DateTimeImmutable;
    public function eventName(): string;
    public function payload(): array;
}
