<?php

declare(strict_types=1);

namespace AIGrowthOS;

use AIGrowthOS\Dashboard\DashboardService;
use AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator;
use AIGrowthOS\Infrastructure\Logging\Logger;
use AIGrowthOS\Infrastructure\Persistence\Repository\AgentRepository;
use AIGrowthOS\Observability\HealthCheck;
use AIGrowthOS\Orchestration\Event\EventBus;
use AIGrowthOS\Orchestration\Workflow\WorkflowEngine;
use AIGrowthOS\Presentation\Admin\DashboardController;
use AIGrowthOS\Presentation\Cli\AuditCommand;
use AIGrowthOS\Presentation\Rest\ScoresController;

final class Plugin
{
    private static ?Plugin $instance = null;
    private AIOrchestrator $ai;
    private EventBus $eventBus;
    private WorkflowEngine $workflowEngine;
    private DashboardService $dashboardService;

    private function __construct()
    {
        $this->ai = new AIOrchestrator();
        $this->eventBus = new EventBus();
        $this->workflowEngine = new WorkflowEngine();
        $this->dashboardService = new DashboardService();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getAI(): AIOrchestrator
    {
        return $this->ai;
    }

    public function getEventBus(): EventBus
    {
        return $this->eventBus;
    }

    public function getWorkflowEngine(): WorkflowEngine
    {
        return $this->workflowEngine;
    }

    public function getDashboardService(): DashboardService
    {
        return $this->dashboardService;
    }

    public function init(): void
    {
        $this->registerTextDomain();
        $this->registerControllers();
        $this->registerRestRoutes();
        $this->registerCliCommands();
        $this->registerGutenbergBlocks();
        $this->registerCronSchedules();

        // Register default workflow handlers
        $this->registerWorkflowHandlers();
    }

    private function registerTextDomain(): void
    {
        load_plugin_textdomain('ai-growth-os', false, AI_GROWTH_OS_DIR . '/languages');
    }

    private function registerControllers(): void
    {
        (new DashboardController())->register();
    }

    private function registerRestRoutes(): void
    {
        (new ScoresController($this->dashboardService))->register();
    }

    private function registerCliCommands(): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            (new AuditCommand())->register();
        }
    }

    private function registerGutenbergBlocks(): void
    {
        add_action('init', function () {
            // Gutenberg blocks registered here
        });
    }

    private function registerCronSchedules(): void
    {
        add_filter('cron_schedules', function ($schedules) {
            $schedules['ai_growth_os_hourly'] = [
                'interval' => 3600,
                'display'  => 'AI Growth OS Hourly',
            ];
            $schedules['ai_growth_os_daily'] = [
                'interval' => 86400,
                'display'  => 'AI Growth OS Daily',
            ];
            return $schedules;
        });

        if (!wp_next_scheduled('ai_growth_os_daily_audit')) {
            wp_schedule_event(time(), 'ai_growth_os_daily', 'ai_growth_os_daily_audit');
        }

        if (!wp_next_scheduled('ai_growth_os_hourly_metrics')) {
            wp_schedule_event(time(), 'ai_growth_os_hourly', 'ai_growth_os_hourly_metrics');
        }

        add_action('ai_growth_os_daily_audit', [$this, 'runDailyAudit']);
        add_action('ai_growth_os_hourly_metrics', [$this, 'collectHourlyMetrics']);
    }

    public function runDailyAudit(): void
    {
        Logger::info('Running daily scheduled audit');
        $auditService = new \AIGrowthOS\Audit\AuditService($this->ai, $this->eventBus);
        $auditService->runFullAudit();
    }

    public function collectHourlyMetrics(): void
    {
        Logger::info('Collecting hourly metrics');
        $metrics = new \AIGrowthOS\Observability\MetricsCollector();
        $health = new HealthCheck();
        $result = $health->run();
        $metrics->record('health_status', $result['status'] === 'healthy' ? 1 : 0);
    }

    private function registerWorkflowHandlers(): void
    {
        $this->workflowEngine->registerHandler('run_audit', function (array $config) {
            $auditService = new \AIGrowthOS\Audit\AuditService($this->ai, $this->eventBus);
            return $auditService->runFullAudit()->toArray();
        });

        $this->workflowEngine->registerHandler('generate_report', function (array $config) {
            $agent = new \AIGrowthOS\Orchestration\Agent\CEOAgent($this->ai);
            return $agent->generateExecutiveSummary(
                $this->dashboardService->getScores(),
                $this->dashboardService->getRecommendations(),
                $this->dashboardService->getRecentAudits()
            );
        });
    }
}
