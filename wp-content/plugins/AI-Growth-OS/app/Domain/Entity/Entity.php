<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\Entity;

interface Entity
{
    public function getId(): mixed;
    public function toArray(): array;
}
