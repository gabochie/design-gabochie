<?php
/**
 * Task Verification Endpoint
 * @package Gabochie_Value
 * 
 * GET /wp-json/gabochie/v1/verify-tasks
 * Returns verification status for all phase checklist tasks.
 * No auth required (read-only checks).
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('gabochie/v1', '/verify-tasks', array(
        'methods'             => 'GET',
        'callback'            => 'gabochie_verify_tasks',
        'permission_callback' => '__return_true',
    ));
});

function gabochie_verify_tasks() {
    $results = array();

    // Phase A: Workspace Core
    $results['phaseA-0'] = _verify_cpt_exists('client_project', 'client_projects');
    $results['phaseA-1'] = _verify_cpt_exists('project_message', 'project_messages');
    $results['phaseA-2'] = _verify_cpt_exists('project_document', 'project_documents');
    $results['phaseA-3'] = _verify_page_exists('/dashboard/', 'Client Dashboard');
    $results['phaseA-4'] = _verify_rest_route('gabochie/v1', '/workspace');
    $results['phaseA-5'] = _verify_page_template('template-dashboard.php');
    $results['phaseA-6'] = _verify_page_exists('/client-login/', 'Client Login');
    $results['phaseA-7'] = _verify_plugin_active('gabochie-voice-agent');

    // Phase B: Readiness Score
    $results['phaseB-0'] = _verify_rest_route('gabochie/v1', '/projects/1/readiness');
    $results['phaseB-1'] = _verify_postmeta_exists('gabochie_readiness_score');
    $results['phaseB-2'] = _verify_rest_route('gabochie/v1', '/projects/1/readiness', 'POST');
    $results['phaseB-3'] = _verify_dashboard_widget();
    $results['phaseB-4'] = _verify_postmeta_exists('gabochie_readiness_strengths');
    $results['phaseB-5'] = _verify_postmeta_exists('gabochie_readiness_weaknesses');
    $results['phaseB-6'] = _verify_option_exists('ghbc_llm_provider');
    $results['phaseB-7'] = _verify_plugin_active('gabochie-ai-agentic-bridge');

    // Phase C: Feasibility Studio + CostSmart
    $results['phaseC-0'] = _verify_page_exists('/feasibility-studio/', 'Feasibility Studio');
    $results['phaseC-1'] = _verify_option_exists('gabochie_cost_engine');
    $results['phaseC-2'] = _verify_rest_route('gabochie/v1', '/cost-estimate');
    $results['phaseC-3'] = _verify_postmeta_exists('gabochie_project_budget');
    $results['phaseC-4'] = _verify_option_exists('gabochie_materials_db');
    $results['phaseC-5'] = _verify_page_exists('/cost-breakdown/', 'Cost Breakdown');
    $results['phaseC-6'] = _verify_page_exists('/feasibility-report/', 'Feasibility Report');

    // Phase D: Development Strategy + Go Live
    $results['phaseD-0'] = _verify_page_exists('/go-live/', 'Go Live');
    $results['phaseD-1'] = _verify_option_exists('gabochie_launch_checklist');
    $results['phaseD-2'] = _verify_option_exists('gabochie_precheck_list');
    $results['phaseD-3'] = _verify_plugin_active('gabochie-go-live');
    $results['phaseD-4'] = _verify_page_exists('/comparison/', 'Comparison');

    // Phase E: AI Copilot + Reports
    $results['phaseE-0'] = _verify_rest_route('gabochie-voice/v1', '/chat');
    $results['phaseE-1'] = _verify_option_exists('ghbc_llm_provider');
    $results['phaseE-2'] = _verify_page_exists('/reports/', 'Reports');
    $results['phaseE-3'] = _verify_rest_route('gabochie/v1', '/reports');
    $results['phaseE-4'] = _verify_page_exists('/admin-dashboard/', 'Admin Dashboard');
    $results['phaseE-5'] = _verify_rest_route('gabochie/v1', '/export');
    $results['phaseE-6'] = _verify_plugin_active('gabochie-command-center');

    return rest_ensure_response($results);
}

/* ─── Verification Helpers ─── */

function _verify_cpt_exists($cpt, $plural) {
    $types = get_post_types(array('show_in_rest' => true), 'names');
    $found = in_array($cpt, $types);
    return array(
        'verified' => $found,
        'message'  => $found ? ucfirst($plural) . ' CPT active' : ucfirst($plural) . ' CPT not found',
    );
}

function _verify_page_exists($slug, $title) {
    $page = get_page_by_path($slug);
    $found = $page && $page->post_status === 'publish';
    return array(
        'verified' => $found,
        'message'  => $found ? "'$title' page exists (ID {$page->ID})" : "'$title' page not found at $slug",
    );
}

function _verify_page_template($template) {
    $pages = get_pages(array('meta_key' => '_wp_page_template', 'meta_value' => $template, 'number' => 1));
    $found = !empty($pages);
    return array(
        'verified' => $found,
        'message'  => $found ? "Template '$template' in use" : "Template '$template' not assigned to any page",
    );
}

function _verify_rest_route($namespace, $path, $method = 'GET') {
    $routes = rest_get_server()->get_routes();
    $full = '/' . $namespace . $path;
    $found = false;
    foreach ($routes as $route => $handlers) {
        if (strpos($route, $full) === 0) {
            foreach ($handlers as $handler) {
                if (is_array($handler['methods']) && in_array($method, $handler['methods'])) {
                    $found = true;
                    break;
                } elseif (is_string($handler['methods']) && $handler['methods'] === $method) {
                    $found = true;
                    break;
                }
            }
        }
        if ($found) break;
    }
    return array(
        'verified' => $found,
        'message'  => $found ? "REST route $method $full exists" : "REST route $method $full not found",
    );
}

function _verify_postmeta_exists($meta_key) {
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", $meta_key));
    $found = $count > 0;
    return array(
        'verified' => $found,
        'message'  => $found ? "Meta '$meta_key' exists ($count rows)" : "Meta '$meta_key' not found",
    );
}

function _verify_option_exists($option) {
    $val = get_option($option);
    $found = !empty($val);
    return array(
        'verified' => $found,
        'message'  => $found ? "Option '$option' is set" : "Option '$option' not configured",
    );
}

function _verify_plugin_active($plugin) {
    $active = in_array($plugin, get_option('active_plugins', array()));
    return array(
        'verified' => $active,
        'message'  => $active ? "Plugin '$plugin' active" : "Plugin '$plugin' not active",
    );
}

function _verify_dashboard_widget() {
    // Check if the readiness score function exists in theme
    $file = get_stylesheet_directory() . '/inc/client-portal-readiness.php';
    $exists = file_exists($file);
    return array(
        'verified' => $exists,
        'message'  => $exists ? 'Readiness Score engine file exists' : 'Readiness Score engine file missing',
    );
}
