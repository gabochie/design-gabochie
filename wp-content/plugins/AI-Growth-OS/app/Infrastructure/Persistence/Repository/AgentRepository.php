<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\Persistence\Repository;

use AIGrowthOS\Domain\Entity\Agent;
use AIGrowthOS\Domain\Repository\Repository;

class AgentRepository implements Repository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'ai_growth_os_agents';
    }

    public function save(object $entity): void
    {
        if (!($entity instanceof Agent)) {
            throw new \InvalidArgumentException('Expected Agent entity');
        }

        global $wpdb;
        $data = $entity->toArray();
        unset($data['id']);

        if ($entity->getId() === 0) {
            $wpdb->insert($this->table, $data);
        } else {
            $wpdb->update($this->table, $data, ['id' => $entity->getId()]);
        }
    }

    public function delete(object $entity): void
    {
        global $wpdb;
        $wpdb->delete($this->table, ['id' => $entity->getId()]);
    }

    public function findById(int|string $id): ?Agent
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) return null;

        return new Agent(
            id: (int) $row['id'],
            name: $row['name'],
            role: $row['role'],
            mission: $row['mission'],
            status: $row['status'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: $row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null,
        );
    }

    public function findAll(int $offset = 0, int $limit = 50): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $this->table ORDER BY id ASC LIMIT %d OFFSET %d", $limit, $offset),
            ARRAY_A
        );

        return array_map(fn($row) => new Agent(
            id: (int) $row['id'],
            name: $row['name'],
            role: $row['role'],
            mission: $row['mission'],
            status: $row['status'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: $row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null,
        ), $rows ?: []);
    }

    public function count(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $this->table");
    }
}
