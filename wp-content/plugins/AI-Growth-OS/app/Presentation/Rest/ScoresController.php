<?php

declare(strict_types=1);

namespace AIGrowthOS\Presentation\Rest;

use AIGrowthOS\Dashboard\DashboardService;
use WP_REST_Request;
use WP_REST_Response;

class ScoresController
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function register(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('ai-growth-os/v1', '/scores', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getScores'],
                'permission_callback' => [$this, 'checkPermission'],
            ]);

            register_rest_route('ai-growth-os/v1', '/recommendations', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getRecommendations'],
                'permission_callback' => [$this, 'checkPermission'],
            ]);

            register_rest_route('ai-growth-os/v1', '/audits', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getAudits'],
                'permission_callback' => [$this, 'checkPermission'],
            ]);

            register_rest_route('ai-growth-os/v1', '/metrics', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getMetrics'],
                'permission_callback' => [$this, 'checkPermission'],
            ]);

            register_rest_route('ai-growth-os/v1', '/health', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getHealth'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public function checkPermission(): bool
    {
        return current_user_can('manage_options');
    }

    public function getScores(): WP_REST_Response
    {
        return new WP_REST_Response($this->dashboard->getScores(), 200);
    }

    public function getRecommendations(WP_REST_Request $request): WP_REST_Response
    {
        $limit = (int) $request->get_param('limit') ?: 10;
        return new WP_REST_Response($this->dashboard->getRecommendations($limit), 200);
    }

    public function getAudits(WP_REST_Request $request): WP_REST_Response
    {
        $limit = (int) $request->get_param('limit') ?: 5;
        return new WP_REST_Response($this->dashboard->getRecentAudits($limit), 200);
    }

    public function getMetrics(): WP_REST_Response
    {
        return new WP_REST_Response($this->dashboard->getMetrics(), 200);
    }

    public function getHealth(): WP_REST_Response
    {
        $healthCheck = new \AIGrowthOS\Observability\HealthCheck();
        return new WP_REST_Response($healthCheck->run(), 200);
    }
}
