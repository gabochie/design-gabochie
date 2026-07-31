<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\Persistence\Schema;

class SchemaManager
{
    private const TABLES = [
        'agents',
        'agent_memory',
        'agent_tasks',
        'agent_execution_history',
        'audit_runs',
        'audit_results',
        'recommendations',
        'workflows',
        'workflow_steps',
        'workflow_executions',
        'scores',
        'events',
        'metrics',
        'logs',
    ];

    public function createTables(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        foreach ($this->getTableSchemas($charset) as $sql) {
            dbDelta($sql);
        }

        update_option('ai_growth_os_db_version', AI_GROWTH_OS_VERSION);
    }

    public function dropTables(): void
    {
        global $wpdb;
        foreach (self::TABLES as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_growth_os_{$table}");
        }
    }

    private function getTableSchemas(string $charset): array
    {
        global $wpdb;
        $p = $wpdb->prefix;

        return [
            // Agents
            "CREATE TABLE {$p}ai_growth_os_agents (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                role varchar(100) NOT NULL,
                mission text NOT NULL,
                status varchar(50) DEFAULT 'idle',
                config longtext,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NULL,
                PRIMARY KEY (id),
                KEY idx_status (status),
                KEY idx_role (role)
            ) $charset;",

            // Agent memory
            "CREATE TABLE {$p}ai_growth_os_agent_memory (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                agent_id bigint(20) UNSIGNED NOT NULL,
                memory_type varchar(50) NOT NULL,
                content longtext NOT NULL,
                context longtext,
                importance float DEFAULT 0.0,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_agent_id (agent_id),
                KEY idx_memory_type (memory_type)
            ) $charset;",

            // Agent task queue
            "CREATE TABLE {$p}ai_growth_os_agent_tasks (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                agent_id bigint(20) UNSIGNED NOT NULL,
                title varchar(255) NOT NULL,
                description text,
                priority int DEFAULT 0,
                status varchar(50) DEFAULT 'pending',
                depends_on bigint(20) UNSIGNED NULL,
                scheduled_at datetime NULL,
                started_at datetime NULL,
                completed_at datetime NULL,
                result longtext,
                error text,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_agent_id (agent_id),
                KEY idx_status (status),
                KEY idx_priority (priority)
            ) $charset;",

            // Agent execution history
            "CREATE TABLE {$p}ai_growth_os_agent_execution_history (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                agent_id bigint(20) UNSIGNED NOT NULL,
                action varchar(255) NOT NULL,
                input longtext,
                output longtext,
                tokens_used int UNSIGNED DEFAULT 0,
                cost decimal(12,6) DEFAULT 0.0,
                duration_ms int UNSIGNED DEFAULT 0,
                status varchar(50) DEFAULT 'completed',
                error text,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_agent_id (agent_id),
                KEY idx_action (action),
                KEY idx_created_at (created_at)
            ) $charset;",

            // Audit runs
            "CREATE TABLE {$p}ai_growth_os_audit_runs (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                type varchar(100) NOT NULL,
                status varchar(50) DEFAULT 'pending',
                scope longtext,
                started_at datetime NULL,
                completed_at datetime NULL,
                error text,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_type (type),
                KEY idx_status (status),
                KEY idx_created_at (created_at)
            ) $charset;",

            // Audit results
            "CREATE TABLE {$p}ai_growth_os_audit_results (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                audit_run_id bigint(20) UNSIGNED NOT NULL,
                category varchar(100) NOT NULL,
                severity varchar(50) NOT NULL,
                title varchar(255) NOT NULL,
                description text,
                details longtext,
                score float DEFAULT 0.0,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_audit_run_id (audit_run_id),
                KEY idx_category (category),
                KEY idx_severity (severity)
            ) $charset;",

            // Recommendations
            "CREATE TABLE {$p}ai_growth_os_recommendations (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                title varchar(255) NOT NULL,
                description longtext NOT NULL,
                category varchar(100) NOT NULL,
                status varchar(50) DEFAULT 'draft',
                impact_score float DEFAULT 0.0,
                effort_score float DEFAULT 0.0,
                confidence_score float DEFAULT 0.0,
                priority_score float DEFAULT 0.0,
                expected_revenue_impact decimal(14,2) NULL,
                agent_id bigint(20) UNSIGNED NULL,
                approved_by bigint(20) UNSIGNED NULL,
                approved_at datetime NULL,
                implemented_at datetime NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_category (category),
                KEY idx_status (status),
                KEY idx_priority_score (priority_score)
            ) $charset;",

            // Workflows
            "CREATE TABLE {$p}ai_growth_os_workflows (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                trigger_type varchar(100) NOT NULL,
                schedule varchar(100) NULL,
                status varchar(50) DEFAULT 'active',
                config longtext,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NULL,
                PRIMARY KEY (id),
                KEY idx_trigger_type (trigger_type),
                KEY idx_status (status)
            ) $charset;",

            // Workflow steps
            "CREATE TABLE {$p}ai_growth_os_workflow_steps (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                workflow_id bigint(20) UNSIGNED NOT NULL,
                step_order int UNSIGNED NOT NULL,
                action varchar(255) NOT NULL,
                config longtext,
                conditions longtext,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_workflow_id (workflow_id),
                KEY idx_step_order (step_order)
            ) $charset;",

            // Workflow executions
            "CREATE TABLE {$p}ai_growth_os_workflow_executions (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                workflow_id bigint(20) UNSIGNED NOT NULL,
                status varchar(50) DEFAULT 'pending',
                started_at datetime NULL,
                completed_at datetime NULL,
                result longtext,
                error text,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_workflow_id (workflow_id),
                KEY idx_status (status)
            ) $charset;",

            // Scores
            "CREATE TABLE {$p}ai_growth_os_scores (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                type varchar(100) NOT NULL,
                value decimal(8,2) NOT NULL,
                previous_value decimal(8,2) NULL,
                trend varchar(20) DEFAULT 'stable',
                calculated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_type (type),
                KEY idx_calculated_at (calculated_at)
            ) $charset;",

            // Events
            "CREATE TABLE {$p}ai_growth_os_events (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                event_name varchar(255) NOT NULL,
                payload longtext NOT NULL,
                occurred_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                processed tinyint(1) DEFAULT 0,
                PRIMARY KEY (id),
                KEY idx_event_name (event_name),
                KEY idx_processed (processed),
                KEY idx_occurred_at (occurred_at)
            ) $charset;",

            // Metrics
            "CREATE TABLE {$p}ai_growth_os_metrics (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                metric_name varchar(255) NOT NULL,
                metric_value double NOT NULL,
                labels longtext,
                recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_metric_name (metric_name),
                KEY idx_recorded_at (recorded_at)
            ) $charset;",

            // Logs
            "CREATE TABLE {$p}ai_growth_os_logs (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                level varchar(20) NOT NULL,
                message text NOT NULL,
                context longtext,
                trace_id varchar(64) NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_level (level),
                KEY idx_created_at (created_at),
                KEY idx_trace_id (trace_id)
            ) $charset;",
        ];
    }
}
