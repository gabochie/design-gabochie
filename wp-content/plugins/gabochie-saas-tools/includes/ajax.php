<?php
defined('ABSPATH') || exit;

add_action('wp_ajax_gs_calculate', 'gs_ajax_calculate');
add_action('wp_ajax_gs_save_doc', 'gs_ajax_save_doc');
add_action('wp_ajax_gs_ai_test', 'gs_ajax_ai_test');
add_action('wp_ajax_gs_ai_generate', 'gs_ajax_ai_generate');
add_action('wp_ajax_nopriv_gs_ai_generate', 'gs_ajax_ai_generate');

function gs_ajax_calculate() {
    check_ajax_referer('gs_tool_nonce', 'nonce');

    $tool_id = sanitize_key($_POST['tool_id'] ?? '');
    $tools = gs_tool_definitions();
    $tool_ids = array_column($tools, 'id');

    if (!in_array($tool_id, $tool_ids, true)) {
        wp_send_json_error(['message' => 'Unknown tool.']);
    }

    $result = null;
    $ai = Gabochie_AI_Engine::instance();

    if ($tool_id === 'res-cost') {
        $fields = [];
        $allowed = ['building_type', 'region', 'floors', 'area', 'finishing'];
        foreach ($allowed as $key) {
            $fields[$key] = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
        }
        $result = gs_calc_residential_cost($fields);
    } elseif (in_array($tool_id, ['devprop', 'feasibility', 'jv'], true)) {
        $fields = [];
        $tool_defs = gs_tool_definitions();
        $field_names = [];
        foreach ($tool_defs as $t) {
            if ($t['id'] === $tool_id) {
                $field_names = $t['fields'];
                break;
            }
        }
        foreach ($field_names as $key) {
            $fields[$key] = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
        }
        if ($ai->is_configured()) {
            $result = gs_ai_generate_tool_output($tool_id, $fields);
        } else {
            $result = array(
                'text' => gs_generate_mock_output($tool_id, $fields),
                'mode' => 'mock',
            );
        }
    } else {
        $fields = [];
        $allowed = ['building_type', 'region', 'floors', 'area', 'finishing', 'property_type', 'price', 'rent', 'financing', 'material', 'quantity', 'project_type', 'budget', 'scope'];
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $fields[$key] = sanitize_text_field($_POST[$key]);
            }
        }
        $result_text = gs_generate_mock_output($tool_id, $fields);
        $result = array('text' => $result_text, 'mode' => 'mock');
    }

    if (is_user_logged_in()) {
        gs_increment_usage();
    }

    wp_send_json_success($result);
}

function gs_ajax_save_doc() {
    check_ajax_referer('gs_tool_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not authenticated.']);
    }

    $title   = sanitize_text_field($_POST['title'] ?? '');
    $summary = sanitize_textarea_field($_POST['summary'] ?? '');
    $type    = sanitize_text_field($_POST['type'] ?? 'Cost Estimate');

    if (empty($title)) {
        wp_send_json_error(['message' => 'Title is required.']);
    }

    $id = gs_save_document([
        'title'   => $title,
        'type'    => $type,
        'status'  => 'final',
        'summary' => $summary,
    ]);

    wp_send_json_success(['id' => $id]);
}

function gs_calc_residential_cost($f) {
    $type = $f['building_type'] ?? 'Bungalow';
    $region = $f['region'] ?? 'Greater Accra';
    $floors = max(1, (int)($f['floors'] ?? 1));
    $area = max(10, (int)($f['area'] ?? 200));
    $finish = $f['finishing'] ?? 'Standard';

    $rates = ['Standard' => 3800, 'Premium' => 5200, 'Luxury' => 7800];
    $rate = $rates[$finish] ?? 3800;

    $regional_mult = [
        'Greater Accra' => 1.00, 'Ashanti' => 0.92, 'Western' => 0.88,
        'Central' => 0.85, 'Eastern' => 0.82, 'Volta' => 0.84,
        'Northern' => 0.80, 'Bono' => 0.83, 'Ahafo' => 0.83,
        'Oti' => 0.84, 'Western North' => 0.86, 'Bono East' => 0.82,
        'Savannah' => 0.79, 'North East' => 0.78, 'Upper East' => 0.76,
        'Upper West' => 0.76,
    ];
    $mult = $regional_mult[$region] ?? 0.90;

    $gross = $area * $rate * $floors;
    $total_ghs = round($gross * $mult);

    $breakdown = [
        'substructure'       => ['label' => 'Substructure',       'pct' => 0.15, 'desc' => 'Foundations, ground slab, site prep'],
        'superstructure'     => ['label' => 'Superstructure',     'pct' => 0.35, 'desc' => 'Columns, beams, walls, floors, roof'],
        'finishes'           => ['label' => 'Finishes',           'pct' => 0.22, 'desc' => 'Floor/wall finishes, ceilings, painting'],
        'services_mep'       => ['label' => 'Services (MEP)',     'pct' => 0.18, 'desc' => 'Electrical, plumbing, mechanical, fire'],
        'prelims_contingency' => ['label' => 'Prelims & Contingency', 'pct' => 0.10, 'desc' => 'Site overheads, design contingencies'],
    ];

    $categories = [];
    foreach ($breakdown as $key => $item) {
        $categories[] = [
            'id'    => $key,
            'label' => $item['label'],
            'pct'   => $item['pct'] * 100,
            'ghs'   => round($total_ghs * $item['pct']),
            'usd'   => round(($total_ghs * $item['pct']) / 15.5),
            'desc'  => $item['desc'],
        ];
    }

    return [
        'inputs' => [
            'building_type' => $type,
            'region'       => $region,
            'floors'       => $floors,
            'area'         => $area,
            'finishing'    => $finish,
        ],
        'rate'           => $rate,
        'regional_mult'  => $mult,
        'categories'     => $categories,
        'totals'         => [
            'ghs'     => $total_ghs,
            'usd'     => round($total_ghs / 15.5),
            'per_sqm' => round($total_ghs / ($area * $floors)),
        ],
        'summary' => sprintf(
            "Estimated cost for a %s %s in %s: GH₵ %s (∼$%s).\nRate: GH₵ %s/m² × %d m² × %d floor(s) × regional factor %.2f.",
            $finish, $type, $region,
            number_format($total_ghs),
            number_format(round($total_ghs / 15.5)),
            number_format($rate), $area, $floors, $mult
        ),
    ];
}

function gs_ajax_ai_test() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized.']);
    }
    $ai = Gabochie_AI_Engine::instance();
    $ai->refresh_config();
    $result = $ai->test_connection();
    if ($result['success']) {
        wp_send_json_success($result);
    }
    wp_send_json_error($result);
}

function gs_ajax_ai_generate() {
    check_ajax_referer('gs_tool_nonce', 'nonce');
    $ai = Gabochie_AI_Engine::instance();
    $ai->refresh_config();
    $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
    $system = isset($_POST['system']) ? sanitize_textarea_field($_POST['system']) : '';
    if (empty($prompt)) {
        wp_send_json_error(['message' => 'Prompt is required.']);
    }
    $response = $ai->generate($prompt, $system);
    wp_send_json_success(['text' => $response]);
}

function gs_ai_generate_tool_output($tool_id, $fields) {
    $ai = Gabochie_AI_Engine::instance();
    $ai->refresh_config();
    $field_text = '';
    foreach ($fields as $key => $value) {
        if (!empty($value)) {
            $field_text .= ucfirst(str_replace('_', ' ', $key)) . ": $value\n";
        }
    }
    $prompts = array(
        'devprop' => array(
            'system' => 'You are an expert Ghanaian architectural project proposal writer. Generate a professional development proposal with: executive summary, project overview, location analysis, design concept, budget breakdown, timeline, and risk assessment. Use Ghana-specific references (building codes, regional costs, cultural considerations). Format with clear headings and markdown.',
            'user' => "Generate a professional development proposal based on these project details:\n$field_text",
        ),
        'feasibility' => array(
            'system' => 'You are an expert Ghanaian real estate feasibility analyst. Generate a feasibility report with: market analysis, financial projections, risk assessment, regulatory requirements, recommendation. Use Ghana-specific data (Accra/Ghana market trends, GHS/USD rates, regional factors). Format with clear headings.',
            'user' => "Generate a feasibility study for this project:\n$field_text",
        ),
        'jv' => array(
            'system' => 'You are an expert Ghanaian joint venture negotiator and real estate deal structurer. Generate a JV analysis with: deal summary, valuation analysis, equity structure recommendation, risk allocation, exit strategy, negotiation points. Use Ghana-specific legal and market context.',
            'user' => "Analyze this joint venture opportunity:\n$field_text",
        ),
    );
    if (isset($prompts[$tool_id])) {
        $p = $prompts[$tool_id];
        $text = $ai->generate($p['user'], $p['system']);
        return array('text' => $text, 'mode' => 'ai');
    }
    return array('text' => gs_generate_mock_output($tool_id, $fields), 'mode' => 'mock');
}
