<?php

declare(strict_types=1);

namespace AIGrowthOS\Presentation\Cli;

use AIGrowthOS\Audit\AuditService;
use AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator;
use AIGrowthOS\Orchestration\Event\EventBus;

class AuditCommand
{
    public function register(): void
    {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }

        \WP_CLI::add_command('ai-growth-os audit', [$this, 'run']);
        \WP_CLI::add_command('ai-growth-os status', [$this, 'status']);
    }

    public function run(array $args, array $assocArgs): void
    {
        $type = $args[0] ?? 'full';

        \WP_CLI::line("Starting $type audit...");

        $ai = new AIOrchestrator();
        $eventBus = new EventBus();
        $service = new AuditService($ai, $eventBus);

        $audit = $service->runFullAudit();

        \WP_CLI::success("Audit #{$audit->getId()} completed");
        \WP_CLI::line("Status: {$audit->getStatus()}");
    }

    public function status(): void
    {
        $health = new \AIGrowthOS\Observability\HealthCheck();
        $result = $health->run();

        \WP_CLI::line("Health Status: {$result['status']}");
        foreach ($result['checks'] as $name => $check) {
            \WP_CLI::line("  $name: {$check['status']} — {$check['message']}");
        }
    }
}
