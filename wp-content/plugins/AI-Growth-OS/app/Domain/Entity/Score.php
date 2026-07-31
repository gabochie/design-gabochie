<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\Entity;

class Score implements Entity
{
    public function __construct(
        private readonly int $id,
        private string $type,
        private float $value,
        private ?float $previousValue = null,
        private \DateTimeImmutable $calculatedAt = new \DateTimeImmutable(),
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getTrend(): string
    {
        if ($this->previousValue === null) {
            return 'stable';
        }
        $diff = $this->value - $this->previousValue;
        return match (true) {
            $diff > 0 => 'up',
            $diff < 0 => 'down',
            default   => 'stable',
        };
    }

    public function setValue(float $value): void
    {
        $this->previousValue = $this->value;
        $this->value = $value;
        $this->calculatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'value'          => $this->value,
            'previous_value' => $this->previousValue,
            'trend'          => $this->getTrend(),
            'calculated_at'  => $this->calculatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
