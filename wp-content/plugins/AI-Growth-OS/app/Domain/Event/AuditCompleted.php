<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\Event;

class AuditCompleted implements DomainEvent
{
    public function __construct(
        private readonly int $auditRunId,
        private readonly string $auditType,
        private readonly int $findingsCount,
        private readonly \DateTimeImmutable $occurredOn = new \DateTimeImmutable(),
    ) {}

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function eventName(): string
    {
        return 'audit.completed';
    }

    public function payload(): array
    {
        return [
            'audit_run_id'   => $this->auditRunId,
            'audit_type'     => $this->auditType,
            'findings_count' => $this->findingsCount,
            'occurred_on'    => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }
}
