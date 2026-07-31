<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\Entity;

class Workflow implements Entity
{
    public function __construct(
        private readonly int $id,
        private string $name,
        private string $trigger,
        private ?string $schedule = null,
        private string $status = 'active',
        private array $steps = [],
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function addStep(array $step): void
    {
        $this->steps[] = $step;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'trigger'    => $this->trigger,
            'schedule'   => $this->schedule,
            'status'     => $this->status,
            'steps'      => $this->steps,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
