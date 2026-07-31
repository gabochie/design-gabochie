<?php

declare(strict_types=1);

namespace AIGrowthOS\Presentation\Admin;

use AIGrowthOS\Infrastructure\Security\PluginSecurity;

final class DashboardController
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuPages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMenuPages(): void
    {
        add_menu_page(
            __('AI Growth OS', 'ai-growth-os'),
            __('AI Growth OS', 'ai-growth-os'),
            'manage_options',
            'ai-growth-os',
            [$this, 'renderDashboard'],
            'dashicons-chart-area',
            30
        );

        add_submenu_page(
            'ai-growth-os',
            __('Dashboard', 'ai-growth-os'),
            __('Dashboard', 'ai-growth-os'),
            'manage_options',
            'ai-growth-os',
            [$this, 'renderDashboard']
        );

        add_submenu_page(
            'ai-growth-os',
            __('Agents', 'ai-growth-os'),
            __('Agents', 'ai-growth-os'),
            'manage_options',
            'ai-growth-os-agents',
            [$this, 'renderAgents']
        );

        add_submenu_page(
            'ai-growth-os',
            __('Recommendations', 'ai-growth-os'),
            __('Recommendations', 'ai-growth-os'),
            'manage_options',
            'ai-growth-os-recommendations',
            [$this, 'renderRecommendations']
        );

        add_submenu_page(
            'ai-growth-os',
            __('Audits', 'ai-growth-os'),
            __('Audits', 'ai-growth-os'),
            'manage_options',
            'ai-growth-os-audits',
            [$this, 'renderAudits']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if (!str_contains($hook, 'ai-growth-os')) {
            return;
        }

        wp_enqueue_style(
            'ai-growth-os-admin',
            AI_GROWTH_OS_URL . 'assets/css/admin.css',
            [],
            AI_GROWTH_OS_VERSION
        );

        wp_enqueue_script(
            'ai-growth-os-admin',
            AI_GROWTH_OS_URL . 'assets/js/admin.js',
            ['wp-api'],
            AI_GROWTH_OS_VERSION,
            true
        );

        wp_localize_script('ai-growth-os-admin', 'AIGrowthOS', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ai_growth_os_nonce'),
            'restUrl' => rest_url('ai-growth-os/v1'),
        ]);
    }

    public function renderDashboard(): void
    {
        PluginSecurity::requireAdmin();
        require AI_GROWTH_OS_DIR . '/resources/views/dashboard.php';
    }

    public function renderAgents(): void
    {
        PluginSecurity::requireAdmin();
        echo '<div class="wrap"><h1>' . esc_html__('AI Agents', 'ai-growth-os') . '</h1><p>' . esc_html__('Agent management interface coming soon.', 'ai-growth-os') . '</p></div>';
    }

    public function renderRecommendations(): void
    {
        PluginSecurity::requireAdmin();
        echo '<div class="wrap"><h1>' . esc_html__('Recommendations', 'ai-growth-os') . '</h1><p>' . esc_html__('Recommendations interface coming soon.', 'ai-growth-os') . '</p></div>';
    }

    public function renderAudits(): void
    {
        PluginSecurity::requireAdmin();
        echo '<div class="wrap"><h1>' . esc_html__('Audits', 'ai-growth-os') . '</h1><p>' . esc_html__('Audit interface coming soon.', 'ai-growth-os') . '</p></div>';
    }
}
