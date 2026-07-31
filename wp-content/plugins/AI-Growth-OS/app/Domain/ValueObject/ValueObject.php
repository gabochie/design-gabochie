<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\ValueObject;

interface ValueObject
{
    public function equals(self $other): bool;
    public function jsonSerialize(): mixed;
}
