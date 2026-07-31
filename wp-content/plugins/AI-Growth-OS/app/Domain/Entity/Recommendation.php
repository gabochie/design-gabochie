<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\Entity;

class Recommendation implements Entity
{
    public function __construct(
        private readonly int $id,
        private string $title,
        private string $description,
        private string $category,
        private string $status = 'draft',
        private float $impactScore = 0.0,
        private float $effortScore = 0.0,
        private float $confidenceScore = 0.0,
        private ?float $expectedRevenueImpact = null,
        private ?string $agentId = null,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        private ?\DateTimeImmutable $implementedAt = null,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPriorityScore(): float
    {
        return ($this->impactScore * 0.5) + ($this->confidenceScore * 0.3) - ($this->effortScore * 0.2);
    }

    public function approve(): void
    {
        $this->status = 'approved';
    }

    public function reject(): void
    {
        $this->status = 'rejected';
    }

    public function implement(): void
    {
        $this->status = 'implemented';
        $this->implementedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id'                      => $this->id,
            'title'                   => $this->title,
            'description'             => $this->description,
            'category'                => $this->category,
            'status'                  => $this->status,
            'impact_score'            => $this->impactScore,
            'effort_score'            => $this->effortScore,
            'confidence_score'        => $this->confidenceScore,
            'priority_score'          => $this->getPriorityScore(),
            'expected_revenue_impact' => $this->expectedRevenueImpact,
            'agent_id'                => $this->agentId,
            'created_at'              => $this->createdAt->format('Y-m-d H:i:s'),
            'implemented_at'          => $this->implementedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
