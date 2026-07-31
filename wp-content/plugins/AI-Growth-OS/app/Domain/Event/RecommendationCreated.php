<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\Event;

class RecommendationCreated implements DomainEvent
{
    public function __construct(
        private readonly int $recommendationId,
        private readonly string $category,
        private readonly float $priorityScore,
        private readonly \DateTimeImmutable $occurredOn = new \DateTimeImmutable(),
    ) {}

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function eventName(): string
    {
        return 'recommendation.created';
    }

    public function payload(): array
    {
        return [
            'recommendation_id' => $this->recommendationId,
            'category'          => $this->category,
            'priority_score'    => $this->priorityScore,
            'occurred_on'       => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }
}
