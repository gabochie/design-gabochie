<?php
/**
 * Client Portal — Readiness Score REST API (Phase B)
 * @package Gabochie_Value
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    $ns = 'gabochie/v1';

    register_rest_route($ns, '/projects/(?P<id>\d+)/readiness', array(
        array(
            'methods'             => 'GET',
            'callback'            => 'gabochie_api_readiness_get',
            'permission_callback' => 'gabochie_api_client_access',
            'args' => array('id' => array('validate_callback' => 'is_numeric')),
        ),
        array(
            'methods'             => 'POST',
            'callback'            => 'gabochie_api_readiness_recalculate',
            'permission_callback' => 'gabochie_api_client_access',
        ),
    ));
});

function gabochie_api_readiness_get($request) {
    $id = (int) $request->get_param('id');
    $project = get_post($id);
    if (!$project || $project->post_type !== 'client_project') {
        return new WP_REST_Response(array('error' => 'Project not found'), 404);
    }

    $dimensions = gabochie_value_readiness_dimensions();
    $overall    = (int) get_post_meta($id, 'readiness_overall', true);
    $updated    = get_post_meta($id, 'readiness_updated', true);

    /* Recalculate if no score exists yet */
    if (!$overall && !$updated) {
        $result = gabochie_value_readiness_calculate($id);
        $overall = $result['overall'];
    }

    $dims = array();
    foreach ($dimensions as $key => $config) {
        $score  = (int) get_post_meta($id, 'readiness_' . $key, true);
        $dims[$key] = array(
            'score'  => $score,
            'weight' => $config['weight'],
            'label'  => $config['label'],
            'status' => gabochie_value_readiness_status($score),
        );
    }

    $analysis = gabochie_value_readiness_analysis($id);

    /* Upgrade prompt for free users */
    $upgrade_prompt = '';
    if (function_exists('pmpro_hasMembershipLevel') && !pmpro_hasMembershipLevel()) {
        $upgrade_prompt = 'Unlock detailed recommendations and cost analysis with Annual Advisory (₵2,000/year)';
    }

    return wp_json_encode(array(
        'ok'            => true,
        'project_id'    => $id,
        'overall'       => $overall,
        'updated'       => $updated,
        'dimensions'    => $dims,
        'strengths'     => $analysis['strengths'],
        'weaknesses'    => $analysis['weaknesses'],
        'missing'       => $analysis['missing'],
        'blockers'      => $analysis['blockers'],
        'upgrade_prompt' => $upgrade_prompt,
    ));
}

function gabochie_api_readiness_recalculate($request) {
    $id = (int) $request->get_param('id');
    $project = get_post($id);
    if (!$project || $project->post_type !== 'client_project') {
        return new WP_REST_Response(array('error' => 'Project not found'), 404);
    }

    $result = gabochie_value_readiness_calculate($id);

    $dimensions = gabochie_value_readiness_dimensions();
    $dims = array();
    foreach ($dimensions as $key => $config) {
        $score = (int) get_post_meta($id, 'readiness_' . $key, true);
        $dims[$key] = array(
            'score'  => $score,
            'weight' => $config['weight'],
            'label'  => $config['label'],
            'status' => gabochie_value_readiness_status($score),
        );
    }

    $analysis = gabochie_value_readiness_analysis($id);

    return wp_json_encode(array(
        'ok'         => true,
        'project_id' => $id,
        'overall'    => $result['overall'],
        'updated'    => $result['updated'],
        'dimensions' => $dims,
        'strengths'  => $analysis['strengths'],
        'weaknesses' => $analysis['weaknesses'],
        'missing'    => $analysis['missing'],
        'blockers'   => $analysis['blockers'],
    ));
}
