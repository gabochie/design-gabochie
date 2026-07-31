<?php

declare(strict_types=1);

namespace AIGrowthOS\Orchestration\Workflow;

use AIGrowthOS\Domain\Entity\Workflow;
use AIGrowthOS\Infrastructure\Logging\Logger;

class WorkflowEngine
{
    private array $handlers = [];

    public function registerHandler(string $action, callable $handler): void
    {
        $this->handlers[$action] = $handler;
    }

    public function execute(Workflow $workflow): array
    {
        Logger::info("Executing workflow: {$workflow->getName()}", [
            'workflow_id' => $workflow->getId(),
            'steps'       => count($workflow->getSteps()),
        ]);

        $results = [];

        foreach ($workflow->getSteps() as $step) {
            $action = $step['action'] ?? '';
            $config = $step['config'] ?? [];

            if (!isset($this->handlers[$action])) {
                Logger::warning("No handler for workflow action: $action");
                continue;
            }

            try {
                $result = call_user_func($this->handlers[$action], $config);
                $results[] = [
                    'action'  => $action,
                    'status'  => 'completed',
                    'result'  => $result,
                ];
            } catch (\Throwable $e) {
                Logger::error("Workflow step failed", [
                    'action' => $action,
                    'error'  => $e->getMessage(),
                ]);
                $results[] = [
                    'action'  => $action,
                    'status'  => 'failed',
                    'error'   => $e->getMessage(),
                ];

                if (!empty($step['conditions']['fail_on_error'])) {
                    break;
                }
            }
        }

        return $results;
    }

    public function getScheduledWorkflows(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_growth_os_workflows';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE status = 'active' AND schedule IS NOT NULL"
            ),
            ARRAY_A
        );
    }
}
