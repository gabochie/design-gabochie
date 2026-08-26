<?php
/**
 * Client Portal — Development Readiness Score™ (Phase B)
 *
 * 8-dimension scoring engine with traffic-light indicators.
 * Each project gets a dynamic 0–100 score based on data completeness.
 *
 * @package Gabochie_Value
 */

if (!defined('ABSPATH')) exit;

/* ─── READINESS META FIELDS ─── */
add_action('init', function () {
    $readiness_fields = array(
        'readiness_land'        => array('type' => 'number', 'default' => 0),
        'readiness_market'      => array('type' => 'number', 'default' => 0),
        'readiness_financial'   => array('type' => 'number', 'default' => 0),
        'readiness_technical'   => array('type' => 'number', 'default' => 0),
        'readiness_regulatory'  => array('type' => 'number', 'default' => 0),
        'readiness_delivery'    => array('type' => 'number', 'default' => 0),
        'readiness_governance'  => array('type' => 'number', 'default' => 0),
        'readiness_revenue'     => array('type' => 'number', 'default' => 0),
        'readiness_overall'     => array('type' => 'number', 'default' => 0),
        'readiness_updated'     => array('type' => 'string',  'default' => ''),
    );

    foreach ($readiness_fields as $key => $config) {
        register_post_meta('client_project', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $config['type'],
            'default'       => $config['default'],
            'auth_callback' => '__return_true',
        ));
    }
});

/* ─── DIMENSION DEFINITIONS ─── */
function gabochie_value_readiness_dimensions() {
    return array(
        'land'       => array('label' => 'Land',        'weight' => 0.15),
        'market'     => array('label' => 'Market',      'weight' => 0.12),
        'financial'  => array('label' => 'Financial',   'weight' => 0.18),
        'technical'  => array('label' => 'Technical',   'weight' => 0.12),
        'regulatory' => array('label' => 'Regulatory',  'weight' => 0.10),
        'delivery'   => array('label' => 'Delivery',    'weight' => 0.10),
        'governance' => array('label' => 'Governance',  'weight' => 0.08),
        'revenue'    => array('label' => 'Revenue',     'weight' => 0.15),
    );
}

/* ─── SCORING RULES ─── */
function gabochie_value_readiness_scoring_rules() {
    return array(
        'land' => array(
            array('field' => 'land_title_type',    'points' => 25, 'condition' => 'not_empty'),
            array('field' => 'land_location',      'points' => 15, 'condition' => 'not_empty'),
            array('field' => 'land_area',          'points' => 15, 'condition' => 'greater_than', 'value' => 0),
            array('field' => 'land_zoning',        'points' => 15, 'condition' => 'not_empty'),
            array('field' => 'land_utilities',     'points' => 15, 'condition' => 'not_empty'),
            array('field' => 'land_environmental', 'points' => 15, 'condition' => 'not_empty_or', 'value' => 'clear'),
        ),
        'market' => array(
            array('field' => 'market_target_customer', 'points' => 25, 'condition' => 'not_empty'),
            array('field' => 'market_demand',          'points' => 25, 'condition' => 'not_empty'),
            array('field' => 'market_competition',     'points' => 20, 'condition' => 'not_empty'),
            array('field' => 'market_pricing',         'points' => 15, 'condition' => 'not_empty'),
            array('field' => 'market_occupancy',       'points' => 15, 'condition' => 'greater_than', 'value' => 0),
        ),
        'financial' => array(
            array('field' => 'financial_capital_available', 'points' => 20, 'condition' => 'greater_than', 'value' => 0),
            array('field' => 'total_dev_cost',              'points' => 25, 'condition' => 'greater_than', 'value' => 0),
            array('field' => 'contingency_pct',             'points' => 10, 'condition' => 'greater_than', 'value' => 0),
            array('field' => 'financing_cost',              'points' => 15, 'condition' => 'greater_than', 'value' => 0),
            array('field' => 'roi',                         'points' => 15, 'condition' => 'greater_than', 'value' => 0),
            array('field' => 'payback_years',               'points' => 15, 'condition' => 'greater_than', 'value' => 0),
        ),
        'technical' => array(
            array('field' => 'technical_feasibility',  'points' => 30, 'condition' => 'not_empty'),
            array('field' => 'technical_buildability', 'points' => 25, 'condition' => 'not_empty'),
            array('field' => 'construction_method',    'points' => 25, 'condition' => 'not_empty'),
            array('field' => 'technical_programme',    'points' => 20, 'condition' => 'not_empty'),
        ),
        'regulatory' => array(
            array('field' => 'regulatory_approvals',   'points' => 35, 'condition' => 'not_empty'),
            array('field' => 'regulatory_permits',     'points' => 35, 'condition' => 'not_empty'),
            array('field' => 'regulatory_statutory',   'points' => 30, 'condition' => 'not_empty'),
        ),
        'delivery' => array(
            array('field' => 'delivery_procurement',  'points' => 30, 'condition' => 'not_empty'),
            array('field' => 'delivery_contractors',  'points' => 25, 'condition' => 'not_empty'),
            array('field' => 'delivery_team',         'points' => 25, 'condition' => 'not_empty'),
            array('field' => 'delivery_pm_capacity',  'points' => 20, 'condition' => 'not_empty'),
        ),
        'governance' => array(
            array('field' => 'governance_structure',      'points' => 30, 'condition' => 'not_empty'),
            array('field' => 'governance_decision_makers', 'points' => 30, 'condition' => 'not_empty'),
            array('field' => 'governance_contracts',      'points' => 20, 'condition' => 'not_empty'),
            array('field' => 'governance_documentation',  'points' => 20, 'condition' => 'not_empty'),
        ),
        'revenue' => array(
            array('field' => 'revenue_type',         'points' => 20, 'condition' => 'not_empty'),
            array('field' => 'expected_sale_price',  'points' => 20, 'condition' => 'greater_than', 'value' => 0),
            array('field' => 'gross_dev_value',      'points' => 20, 'condition' => 'greater_than', 'value' => 0),
            array('field' => 'occupancy_assumption', 'points' => 20, 'condition' => 'greater_than', 'value' => 0),
            array('field' => 'revenue_strategy',     'points' => 20, 'condition' => 'not_empty'),
        ),
    );
}

/* ─── SCORING ENGINE ─── */
function gabochie_value_readiness_calculate($project_id) {
    $dimensions = gabochie_value_readiness_dimensions();
    $rules      = gabochie_value_readiness_scoring_rules();
    $overrides  = get_option('gabochie_value_readiness_weights', '');
    if ($overrides) {
        $custom = json_decode($overrides, true);
        if (is_array($custom)) {
            foreach ($custom as $dim => $w) {
                if (isset($dimensions[$dim])) {
                    $dimensions[$dim]['weight'] = (float) $w;
                }
            }
        }
    }

    $scores    = array();
    $all_items = array();

    foreach ($rules as $dimension => $items) {
        $dim_score = 0;
        $dim_total = 0;
        foreach ($items as $item) {
            $value   = get_post_meta($project_id, $item['field'], true);
            $earned  = 0;
            $match   = false;

            switch ($item['condition']) {
                case 'not_empty':
                    $match = !empty($value) && $value !== '0';
                    break;
                case 'greater_than':
                    $match = is_numeric($value) && (float) $value > (float) $item['value'];
                    break;
                case 'not_empty_or':
                    $match = !empty($value) && $value !== '0' && $value !== $item['value'];
                    break;
            }

            $earned = $match ? $item['points'] : 0;
            $dim_total += $item['points'];
            $dim_score += $earned;

            $all_items[] = array(
                'field'   => $item['field'],
                'points'  => $item['points'],
                'earned'  => $earned,
                'scored'  => $match,
            );
        }

        $pct = $dim_total > 0 ? round(($dim_score / $dim_total) * 100) : 0;
        $scores[$dimension] = $pct;
    }

    /* Calculate weighted overall */
    $overall = 0;
    $total_weight = 0;
    foreach ($dimensions as $dim => $config) {
        $overall += $scores[$dim] * $config['weight'];
        $total_weight += $config['weight'];
    }
    $overall = $total_weight > 0 ? round($overall / $total_weight) : 0;

    /* Write scores to meta */
    foreach ($scores as $dim => $pct) {
        update_post_meta($project_id, 'readiness_' . $dim, (int) $pct);
    }
    update_post_meta($project_id, 'readiness_overall', (int) $overall);
    update_post_meta($project_id, 'readiness_updated', gmdate('c'));

    /* Audit log */
    if (function_exists('gabochie_log_audit')) {
        gabochie_log_audit($project_id, 'readiness_scored', 'Overall: ' . $overall . '/100');
    }

    return array(
        'overall'   => (int) $overall,
        'dimensions' => $scores,
        'items'     => $all_items,
        'updated'   => gmdate('c'),
    );
}

/* ─── TRAFFIC LIGHT HELPER ─── */
function gabochie_value_readiness_status($score) {
    if ($score >= 70) return 'green';
    if ($score >= 40) return 'amber';
    return 'red';
}

/* ─── ANALYSIS HELPER ─── */
function gabochie_value_readiness_analysis($project_id) {
    $dimensions = gabochie_value_readiness_dimensions();
    $rules      = gabochie_value_readiness_scoring_rules();
    $strengths  = array();
    $weaknesses = array();
    $missing    = array();
    $blockers   = array();

    foreach ($rules as $dimension => $items) {
        $score = (int) get_post_meta($project_id, 'readiness_' . $dimension, true);
        $label = $dimensions[$dimension]['label'];

        if ($score >= 70) {
            $strengths[] = $label . ' well-defined';
        } elseif ($score < 40) {
            if ($score === 0) {
                $blockers[] = $label . ' not started';
            } else {
                $weaknesses[] = $label . ' needs attention';
            }
        }

        foreach ($items as $item) {
            $value = get_post_meta($project_id, $item['field'], true);
            $match = false;
            switch ($item['condition']) {
                case 'not_empty':
                    $match = !empty($value) && $value !== '0';
                    break;
                case 'greater_than':
                    $match = is_numeric($value) && (float) $value > (float) $item['value'];
                    break;
                case 'not_empty_or':
                    $match = !empty($value) && $value !== '0' && $value !== ($item['value'] ?? '');
                    break;
            }
            if (!$match) {
                $field_label = ucwords(str_replace('_', ' ', str_replace($dimension . '_', '', $item['field'])));
                $missing[] = $label . ': ' . $field_label;
            }
        }
    }

    return array(
        'strengths'  => array_slice($strengths, 0, 3),
        'weaknesses' => array_slice($weaknesses, 0, 3),
        'missing'    => array_slice($missing, 0, 5),
        'blockers'   => array_slice($blockers, 0, 3),
    );
}

/* ─── AUTO-RECALC ON SAVE ─── */
add_action('save_post_client_project', function ($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    /* Debounce: max once per 30 seconds */
    $last = get_post_meta($post_id, 'readiness_updated', true);
    if ($last) {
        $age = time() - strtotime($last);
        if ($age < 30) return;
    }

    gabochie_value_readiness_calculate($post_id);
}, 10, 3);

/* ─── SHORTCODE: [gabochie_readiness_ring] ─── */
add_shortcode('gabochie_readiness_ring', function ($atts) {
    $atts = shortcode_atts(array('project' => 0), $atts);
    $pid  = (int) $atts['project'];
    if (!$pid && is_user_logged_in()) {
        $projects = get_posts(array(
            'post_type'   => 'client_project',
            'author'      => get_current_user_id(),
            'numberposts' => 1,
            'orderby'     => 'modified',
            'order'       => 'DESC',
        ));
        $pid = $projects ? $projects[0]->ID : 0;
    }
    if (!$pid) return '<p>No project found.</p>';

    $overall = (int) get_post_meta($pid, 'readiness_overall', true);
    $status  = gabochie_value_readiness_status($overall);
    $color   = $status === 'green' ? '#27AE60' : ($status === 'amber' ? '#F39C12' : '#E74C3C');
    $circumference = 2 * 45;
    $offset = $circumference - ($overall / 100) * $circumference;

    ob_start();
    ?>
    <div class="gr-ring-wrap" style="text-align:center;padding:20px 0;">
        <svg width="120" height="120" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="45" fill="none" stroke="#E8E8E4" stroke-width="8"/>
            <circle cx="50" cy="50" r="45" fill="none" stroke="<?php echo esc_attr($color); ?>" stroke-width="8"
                    stroke-dasharray="<?php echo esc_attr($circumference); ?>"
                    stroke-dashoffset="<?php echo esc_attr($offset); ?>"
                    stroke-linecap="round" transform="rotate(-90 50 50)"
                    style="transition: stroke-dashoffset 1s ease-out;"/>
            <text x="50" y="50" text-anchor="middle" dominant-baseline="central"
                  font-size="24" font-weight="700" fill="<?php echo esc_attr($color); ?>">
                <?php echo esc_html($overall); ?>
            </text>
            <text x="50" y="65" text-anchor="middle" font-size="8" fill="#888">/ 100</text>
        </svg>
        <div style="margin-top:8px;font-size:13px;color:#666;">
            Updated <?php echo esc_html(human_time_diff(strtotime(get_post_meta($pid, 'readiness_updated', true)), time())); ?> ago
        </div>
    </div>
    <?php
    return ob_get_clean();
});
