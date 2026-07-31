<?php
/**
 * Class BOQ_Suite_REST_API
 * Registers WordPress REST API endpoints for all modules including the new Importer.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BOQ_Suite_REST_API {

    public static function register_routes() {
        $namespace = 'boq-suite/v1';

        // 1. BOQ Calculation Endpoint
        register_rest_route($namespace, '/calculate-boq', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'handle_calculate_boq'),
            'permission_callback' => '__return_true',
        ));

        // 2. JV Financial Modeler Endpoint
        register_rest_route($namespace, '/calculate-jv', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'handle_calculate_jv'),
            'permission_callback' => '__return_true',
        ));

        // 3. Rate Search Endpoint
        register_rest_route($namespace, '/search-rates', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'handle_search_rates'),
            'permission_callback' => '__return_true',
        ));

        // 4. PDF Generation Endpoint
        register_rest_route($namespace, '/generate-pdf', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'handle_generate_pdf'),
            'permission_callback' => '__return_true',
        ));

        // 5. Create Invoice Endpoint
        register_rest_route($namespace, '/create-invoice', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'handle_create_invoice'),
            'permission_callback' => '__return_true',
        ));

        // 6. Import BOQ Data Endpoint (restricted to admins)
        register_rest_route($namespace, '/import-boq', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'handle_import_boq'),
            'permission_callback' => array(__CLASS__, 'import_permission'),
        ));
    }

    /**
     * Permission callback for import endpoint – only admins can import.
     */
    public static function import_permission() {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }
        return new WP_Error( 'rest_forbidden', __( 'You do not have permission to import BOQ data.', 'boq-construction-suite' ), array( 'status' => 403 ) );
    }

    public static function handle_calculate_boq($request) {
        $params       = $request->get_json_params();
        $project_type = isset($params['project_type']) ? sanitize_text_field($params['project_type']) : 'classroom_block';
        $area         = isset($params['area']) ? (float)$params['area'] : 250;
        $floors       = isset($params['floors']) ? (int)$params['floors'] : 1;
        $location     = isset($params['location']) ? sanitize_text_field($params['location']) : 'Accra';
        $escalation   = isset($params['escalation']) ? (float)$params['escalation'] : 0;
        $currency     = isset($params['currency']) ? sanitize_text_field($params['currency']) : 'GHS';

        $result = BOQ_Module_BOQ_Estimator::calculate_estimate($project_type, $area, $floors, $location, $escalation, $currency);
        return rest_ensure_response($result);
    }

    public static function handle_calculate_jv($request) {
        $params            = $request->get_json_params();
        $land_value        = isset($params['land_value']) ? (float)$params['land_value'] : 500000;
        $construction_cost = isset($params['construction_cost']) ? (float)$params['construction_cost'] : 1200000;
        $annual_rent       = isset($params['annual_rent']) ? (float)$params['annual_rent'] : 180000;

        $result = BOQ_Module_JV_Calculator::calculate_jv_equity($land_value, $construction_cost, $annual_rent);
        return rest_ensure_response($result);
    }

    public static function handle_search_rates($request) {
        $q        = $request->get_param('q');
        $location = $request->get_param('location');
        $category = $request->get_param('category');
        $currency = $request->get_param('currency');

        $results  = BOQ_Module_Rate_Search::search_rates($q, $location, $category, $currency);
        return rest_ensure_response($results);
    }

    public static function handle_generate_pdf($request) {
        $params = $request->get_json_params();
        $html   = BOQ_Suite_PDF::generate_boq_pdf($params);
        return rest_ensure_response(array('html' => $html));
    }

    public static function handle_create_invoice($request) {
        $params        = $request->get_json_params();
        $client_name   = isset($params['client_name']) ? $params['client_name'] : 'Valued Client';
        $client_email  = isset($params['client_email']) ? $params['client_email'] : 'client@example.com';
        $project_title = isset($params['project_title']) ? $params['project_title'] : 'Residential Development';
        $milestones    = isset($params['milestones']) ? $params['milestones'] : array();

        $invoice = BOQ_Module_QS_Invoicing::create_invoice($client_name, $client_email, $project_title, $milestones);
        return rest_ensure_response($invoice);
    }

    public static function handle_import_boq($request) {
        // Verify nonce (sent via header X-WP-Nonce)
        $nonce = $request->get_header('X-WP-Nonce');
        // Verify the WP REST API nonce (generated via wp_create_nonce('wp_rest'))
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'rest_invalid_nonce', __( 'Invalid request nonce.', 'boq-construction-suite' ), array( 'status' => 403 ) );
        }

        $params   = $request->get_json_params();
        $csv_text = isset($params['csv_text']) ? $params['csv_text'] : '';

        if (empty($csv_text)) {
            return rest_ensure_response(array('success' => false, 'message' => __( 'No CSV content received.', 'boq-construction-suite' )));
        }

        $parsed = BOQ_Module_BOQ_Importer::parse_csv($csv_text);
        if ($parsed['success'] && !empty($params['import_to_rates'])) {
            $import_res = BOQ_Module_BOQ_Importer::import_to_master_rates($parsed['items']);
            $parsed['master_rates_added'] = $import_res['added'];
        }

        return rest_ensure_response($parsed);
    }
}
?>
