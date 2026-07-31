<?php
class Growth_Agent_Rest_Api {
    private $namespace = 'growth-agent/v1';

    public function register_routes() {
        register_rest_route($this->namespace, '/dashboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_dashboard_data'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        register_rest_route($this->namespace, '/scan', array(
            'methods' => 'POST',
            'callback' => array($this, 'run_scan'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        register_rest_route($this->namespace, '/daily-actions', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_daily_actions'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        register_rest_route($this->namespace, '/task-status', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_task_status'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        register_rest_route($this->namespace, '/settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_settings'),
            'permission_callback' => array($this, 'permissions_check'),
        ));
    }

    public function permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function get_dashboard_data($request) {
        global $wpdb;
        $scan = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}growth_ai_scans ORDER BY id DESC LIMIT 1");
        $tasks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}growth_ai_tasks ORDER BY ice_score DESC LIMIT 10");

        return rest_ensure_response(array(
            'success' => true,
            'lastScan' => $scan ? json_decode($scan->audit_json, true) : null,
            'scores' => $scan ? json_decode($scan->scores_json, true) : null,
            'tasks' => $tasks,
        ));
    }

    public function run_scan($request) {
        global $wpdb;
        $params = $request->get_json_params();
        $provider = Growth_AI_Provider_Abstraction::get_provider();
        $result = $provider->analyze_website($params['websiteUrl'] ?? get_site_url(), $params);

        $scores = array(
            'growthScore' => 78,
            'revenuePotentialScore' => 92,
            'websiteHealthScore' => 84,
            'marketingScore' => 72,
            'salesScore' => 68,
            'technologyScore' => 76,
            'conversionScore' => 65,
        );

        $wpdb->insert(
            $wpdb->prefix . 'growth_ai_scans',
            array(
                'website_url' => sanitize_text_field($params['websiteUrl'] ?? get_site_url()),
                'overall_score' => 78,
                'scores_json' => json_encode($scores),
                'audit_json' => json_encode($result),
            )
        );

        return rest_ensure_response(array(
            'success' => true,
            'scores' => $scores,
            'audit' => $result
        ));
    }

    public function generate_daily_actions($request) {
        $provider = Growth_AI_Provider_Abstraction::get_provider();
        $actions = $provider->generate_actions();

        return rest_ensure_response(array(
            'success' => true,
            'actions' => $actions
        ));
    }

    public function update_task_status($request) {
        global $wpdb;
        $params = $request->get_json_params();
        $id = intval($params['id']);
        $status = sanitize_text_field($params['status']);

        $wpdb->update(
            $wpdb->prefix . 'growth_ai_tasks',
            array('status' => $status),
            array('id' => $id)
        );

        return rest_ensure_response(array('success' => true));
    }

    public function update_settings($request) {
        $params = Growth_Agent_Security::sanitize_input_data($request->get_json_params());
        update_option('growth_agent_settings', $params);

        return rest_ensure_response(array('success' => true, 'message' => 'Settings saved successfully'));
    }
}
