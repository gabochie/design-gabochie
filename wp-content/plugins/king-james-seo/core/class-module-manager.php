<?php

namespace KingJamesSEO\Core;

defined('ABSPATH') || exit;

class Module_Manager {
    private array $modules = [];
    private array $categories = [];

    public function register(string $id, array $config): void {
        $this->modules[$id] = wp_parse_args($config, [
            'id'           => $id,
            'name'         => ucwords(str_replace('-', ' ', $id)),
            'category'     => 'uncategorized',
            'class'        => '',
            'autonomy'     => 'L2',
            'standalone'   => true,
            'dependencies' => [],
            'enabled'      => true,
        ]);
        $this->categories[$this->modules[$id]['category']][] = $id;
    }

    public function get(string $id): ?array {
        return $this->modules[$id] ?? null;
    }

    public function all(): array {
        return $this->modules;
    }

    public function get_by_category(string $category): array {
        $ids = $this->categories[$category] ?? [];
        return array_intersect_key($this->modules, array_flip($ids));
    }

    public function categories(): array {
        return array_keys($this->categories);
    }

    public function init_active(): void {
        foreach ($this->modules as $id => $config) {
            if (!$config['enabled']) {
                continue;
            }
            if ($config['class'] && class_exists($config['class'])) {
                $instance = $config['class']::instance();
                if (method_exists($instance, 'init')) {
                    $instance->init();
                }
            }
        }
    }

    public function is_enabled(string $id): bool {
        return !empty($this->modules[$id]['enabled']);
    }

    public function enable(string $id): void {
        if (isset($this->modules[$id])) {
            $this->modules[$id]['enabled'] = true;
            update_option('kjs_module_' . $id, '1');
        }
    }

    public function disable(string $id): void {
        if (isset($this->modules[$id])) {
            $this->modules[$id]['enabled'] = false;
            update_option('kjs_module_' . $id, '0');
        }
    }

    public function register_all(): void {
        $this->register('cluster-content-authority', [
            'name'         => 'Cluster Content Authority',
            'category'     => 'Content Generation',
            'class'        => 'KingJamesSEO\\Modules\\Generation\\Cluster_Content_Authority',
            'autonomy'     => 'L2',
            'standalone'   => false,
            'dependencies' => ['quality-gate-auditor'],
        ]);
        $this->register('pillar-content-authority', [
            'name'         => 'Pillar Content Authority',
            'category'     => 'Content Generation',
            'class'        => 'KingJamesSEO\\Modules\\Generation\\Pillar_Content_Authority',
            'autonomy'     => 'L2',
            'standalone'   => false,
            'dependencies' => ['quality-gate-auditor'],
        ]);
        $this->register('one-keyword-system', [
            'name'         => 'One Keyword System',
            'category'     => 'Content Generation',
            'class'        => 'KingJamesSEO\\Modules\\Generation\\One_Keyword_System',
            'autonomy'     => 'L2',
            'standalone'   => false,
            'dependencies' => ['quality-gate-auditor'],
        ]);
        $this->register('one-niche-system', [
            'name'         => 'One Niche System',
            'category'     => 'Content Generation',
            'class'        => 'KingJamesSEO\\Modules\\Generation\\One_Niche_System',
            'autonomy'     => 'L3',
            'standalone'   => true,
            'dependencies' => ['seo-tool-integration'],
        ]);
        $this->register('seo-title-engineer', [
            'name'         => 'SEO Title Engineer',
            'category'     => 'Optimization',
            'class'        => 'KingJamesSEO\\Modules\\Optimization\\SEO_Title_Engineer',
            'autonomy'     => 'L3',
            'standalone'   => true,
        ]);
        $this->register('meta-description-architect', [
            'name'         => 'Meta Description Architect',
            'category'     => 'Optimization',
            'class'        => 'KingJamesSEO\\Modules\\Optimization\\Meta_Description_Architect',
            'autonomy'     => 'L3',
            'standalone'   => true,
        ]);
        $this->register('schema-markup-generator', [
            'name'         => 'Schema Markup Generator',
            'category'     => 'Optimization',
            'class'        => 'KingJamesSEO\\Modules\\Optimization\\Schema_Markup_Generator',
            'autonomy'     => 'L3',
            'standalone'   => true,
        ]);
        $this->register('content-structure-optimizer', [
            'name'         => 'Content Structure Optimizer',
            'category'     => 'Optimization',
            'class'        => 'KingJamesSEO\\Modules\\Optimization\\Content_Structure_Optimizer',
            'autonomy'     => 'L3',
            'standalone'   => true,
        ]);
        $this->register('quality-gate-auditor', [
            'name'         => 'Quality Gate Auditor',
            'category'     => 'Quality Assurance',
            'class'        => 'KingJamesSEO\\Modules\\Quality\\Quality_Gate_Auditor',
            'autonomy'     => 'L4',
            'standalone'   => true,
        ]);
        $this->register('ymyl-compliance', [
            'name'         => 'YMYL Compliance',
            'category'     => 'Quality Assurance',
            'class'        => 'KingJamesSEO\\Modules\\Quality\\YMYL_Compliance',
            'autonomy'     => 'L1',
            'standalone'   => true,
        ]);
        $this->register('ai-detection-shield', [
            'name'         => 'AI Detection Shield',
            'category'     => 'Quality Assurance',
            'class'        => 'KingJamesSEO\\Modules\\Quality\\AI_Detection_Shield',
            'autonomy'     => 'L3',
            'standalone'   => true,
        ]);
        $this->register('content-scoring-dashboard', [
            'name'         => 'Content Scoring Dashboard',
            'category'     => 'Quality Assurance',
            'class'        => 'KingJamesSEO\\Modules\\Quality\\Content_Scoring_Dashboard',
            'autonomy'     => 'L4',
            'standalone'   => true,
        ]);
        $this->register('workflow-orchestrator', [
            'name'         => 'Workflow Orchestrator',
            'category'     => 'Integration',
            'class'        => 'KingJamesSEO\\Modules\\Integration\\Workflow_Orchestrator',
            'autonomy'     => 'L4',
            'standalone'   => false,
            'dependencies' => [
                'cluster-content-authority',
                'pillar-content-authority',
                'one-keyword-system',
                'one-niche-system',
                'quality-gate-auditor',
            ],
        ]);
        $this->register('content-calendar', [
            'name'         => 'Content Calendar',
            'category'     => 'Integration',
            'class'        => 'KingJamesSEO\\Modules\\Integration\\Content_Calendar',
            'autonomy'     => 'L3',
            'standalone'   => true,
        ]);
        $this->register('seo-tool-integration', [
            'name'         => 'SEO Tool Integration',
            'category'     => 'Integration',
            'class'        => 'KingJamesSEO\\Modules\\Integration\\SEO_Tool_Integration',
            'autonomy'     => 'L4',
            'standalone'   => true,
        ]);
        $this->register('api-connector', [
            'name'         => 'API Connector',
            'category'     => 'Integration',
            'class'        => 'KingJamesSEO\\Modules\\Integration\\API_Connector',
            'autonomy'     => 'L2',
            'standalone'   => true,
        ]);
        $this->register('google-scaled-content-compliance', [
            'name'         => 'Google Scaled Content Compliance',
            'category'     => 'Quality Assurance',
            'class'        => 'KingJamesSEO\\Modules\\Quality\\Google_Scaled_Content_Compliance',
            'autonomy'     => 'L4',
            'standalone'   => true,
        ]);
        $this->register('ai-content-engine', [
            'name'         => 'AI Content Engine',
            'category'     => 'Content Generation',
            'class'        => 'KingJamesSEO\\Modules\\Generation\\AI_Content_Engine',
            'autonomy'     => 'L3',
            'standalone'   => true,
        ]);
        $this->register('autoblogging-engine', [
            'name'         => 'Autoblogging Engine',
            'category'     => 'Integration',
            'class'        => 'KingJamesSEO\\Modules\\Integration\\Autoblogging_Engine',
            'autonomy'     => 'L3',
            'standalone'   => true,
            'dependencies' => ['ai-content-engine', 'google-scaled-content-compliance'],
        ]);
    }
}
