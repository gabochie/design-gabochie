<?php

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Optionally clean up all data
if (get_option('ai_growth_os_delete_data_on_uninstall', false)) {
    global $wpdb;

    $tables = [
        'ai_growth_os_agents',
        'ai_growth_os_agent_memory',
        'ai_growth_os_agent_tasks',
        'ai_growth_os_agent_execution_history',
        'ai_growth_os_audit_runs',
        'ai_growth_os_audit_results',
        'ai_growth_os_recommendations',
        'ai_growth_os_workflows',
        'ai_growth_os_workflow_steps',
        'ai_growth_os_workflow_executions',
        'ai_growth_os_scores',
        'ai_growth_os_events',
        'ai_growth_os_metrics',
        'ai_growth_os_logs',
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
    }

    delete_option('ai_growth_os_version');
    delete_option('ai_growth_os_db_version');
    delete_option('ai_growth_os_activated_at');
}
