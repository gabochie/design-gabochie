<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\Entity;

class Agent implements Entity
{
    public function __construct(
        private readonly int $id,
        private string $name,
        private string $role,
        private string $mission,
        private string $status = 'idle',
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

    public function getRole(): string
    {
        return $this->role;
    }

    public function getMission(): string
    {
        return $this->mission;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'role'       => $this->role,
            'mission'    => $this->mission,
            'status'     => $this->status,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
