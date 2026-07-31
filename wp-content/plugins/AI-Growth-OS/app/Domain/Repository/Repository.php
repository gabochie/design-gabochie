<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\Repository;

interface Repository
{
    public function save(object $entity): void;
    public function delete(object $entity): void;
    public function findById(int|string $id): ?object;
    public function findAll(int $offset = 0, int $limit = 50): array;
    public function count(): int;
}
