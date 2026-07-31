<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\Entity;

class AuditRun implements Entity
{
    public function __construct(
        private readonly int $id,
        private string $type,
        private string $status = 'pending',
        private ?\DateTimeImmutable $startedAt = null,
        private ?\DateTimeImmutable $completedAt = null,
        private array $results = [],
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function start(): void
    {
        $this->status = 'running';
        $this->startedAt = new \DateTimeImmutable();
    }

    public function complete(array $results): void
    {
        $this->status = 'completed';
        $this->completedAt = new \DateTimeImmutable();
        $this->results = $results;
    }

    public function fail(): void
    {
        $this->status = 'failed';
        $this->completedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'status'       => $this->status,
            'started_at'   => $this->startedAt?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
            'results'      => $this->results,
            'created_at'   => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
