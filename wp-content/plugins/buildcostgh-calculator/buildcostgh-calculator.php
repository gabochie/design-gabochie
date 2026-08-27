<?php
/**
 * Plugin Name: BuildCostGH Cost Calculator
 * Description: Ghana construction cost estimator — [buildcostgh_rc] shortcode, AJAX-driven RC calculator with residential & non-residential modes.
 * Version: 0.2.0
 * Author: BuildCostGH
 */

if (!defined('ABSPATH')) exit;

define('BCGH_VERSION', '0.2.0');
define('BCGH_PATH', plugin_dir_path(__FILE__));
define('BCGH_URL', plugin_dir_url(__FILE__));

// -----------------------------------------------------------------
// ACTIVATION: create tables
// -----------------------------------------------------------------
function bcgh_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $t1 = $wpdb->prefix . 'bcgh_cement_log';
    dbDelta("CREATE TABLE $t1 (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        log_date DATE NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        brand VARCHAR(100) DEFAULT '',
        source VARCHAR(255) DEFAULT '',
        logged_by BIGINT UNSIGNED DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        deleted_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;");

    $t2 = $wpdb->prefix . 'bcgh_fx_log';
    dbDelta("CREATE TABLE $t2 (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        rate DECIMAL(10,4) NOT NULL,
        source VARCHAR(255) DEFAULT '',
        is_live TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;");

    $t3 = $wpdb->prefix . 'bcgh_agent_actions';
    dbDelta("CREATE TABLE $t3 (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        agent_type VARCHAR(50) NOT NULL,
        input_snapshot LONGTEXT,
        proposed_output LONGTEXT,
        status VARCHAR(20) DEFAULT 'pending',
        reviewed_by BIGINT UNSIGNED DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;");

    // seed assumptions
    add_option('bcgh_labor_inflation_pct', 18);
    add_option('bcgh_base_per_sqm', 2281.16); // 314800/138 anchor
    add_option('bcgh_cement_anchor', 85);
    add_option('bcgh_fx_anchor', 10.5);
    add_option('bcgh_anomaly_threshold_pct', 25); // flag if jump exceeds this %
}
register_activation_hook(__FILE__, 'bcgh_activate');

// -----------------------------------------------------------------
// REST API (cement-log + agent-queue)
// -----------------------------------------------------------------
add_action('rest_api_init', function () {
    register_rest_route('buildcostgh/v1', '/cement-log', [
        'methods' => 'POST',
        'permission_callback' => function () { return current_user_can('manage_options'); },
        'callback' => 'bcgh_handle_cement_submit',
    ]);

    register_rest_route('buildcostgh/v1', '/agent-queue', [
        'methods' => 'GET',
        'permission_callback' => function () { return current_user_can('manage_options'); },
        'callback' => function () {
            global $wpdb;
            $t = $wpdb->prefix . 'bcgh_agent_actions';
            return rest_ensure_response($wpdb->get_results("SELECT * FROM $t WHERE status='pending' ORDER BY created_at DESC"));
        },
    ]);
});

// -----------------------------------------------------------------
// ANOMALY-FLAG AGENT (rule-based v1 — the one agent that ships day one)
// Intercepts every cement-log submission. If the jump vs trailing average
// exceeds the configured threshold, it does NOT write to the ledger —
// it queues the entry in agent_actions for a human to approve/reject.
// -----------------------------------------------------------------
function bcgh_handle_cement_submit($req) {
    global $wpdb;
    $price = floatval($req->get_param('price'));
    $brand = sanitize_text_field($req->get_param('brand'));
    $source = sanitize_text_field($req->get_param('source'));
    $date = sanitize_text_field($req->get_param('date')) ?: current_time('Y-m-d');

    if ($price <= 0) {
        return new WP_Error('bcgh_invalid', 'Price must be greater than zero', ['status' => 400]);
    }

    $log_table = $wpdb->prefix . 'bcgh_cement_log';
    $trailing_avg = $wpdb->get_var("SELECT AVG(price) FROM $log_table WHERE deleted_at IS NULL AND log_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)");
    $threshold = floatval(get_option('bcgh_anomaly_threshold_pct', 25));

    $is_anomaly = false;
    if ($trailing_avg && $trailing_avg > 0) {
        $delta_pct = abs($price - $trailing_avg) / $trailing_avg * 100;
        if ($delta_pct > $threshold) $is_anomaly = true;
    }

    if ($is_anomaly) {
        $actions_table = $wpdb->prefix . 'bcgh_agent_actions';
        $wpdb->insert($actions_table, [
            'agent_type' => 'anomaly_flag',
            'input_snapshot' => wp_json_encode(['price' => $price, 'brand' => $brand, 'source' => $source, 'date' => $date, 'trailing_avg' => $trailing_avg]),
            'proposed_output' => wp_json_encode(['action' => 'log_cement_price', 'price' => $price]),
            'status' => 'pending',
        ]);
        return rest_ensure_response([
            'status' => 'held_for_review',
            'reason' => "This entry is " . round($delta_pct) . "% away from the trailing 60-day average (GHS " . round($trailing_avg, 2) . "/bag). Sent to the approval queue instead of logging directly.",
        ]);
    }

    $wpdb->insert($log_table, [
        'log_date' => $date, 'price' => $price, 'brand' => $brand,
        'source' => $source, 'logged_by' => get_current_user_id(),
    ]);
    delete_transient('bcgh_cement_latest');
    return rest_ensure_response(['status' => 'logged', 'price' => $price]);
}

// -----------------------------------------------------------------
// RESIDENTIAL COST CALCULATOR (shortcode + AJAX)
// -----------------------------------------------------------------
function bcgh_rc_enqueue() {
    global $post;
    if (is_admin() || !$post) return;
    if (!has_shortcode($post->post_content, 'buildcostgh_rc') && strpos($post->post_content, '[buildcostgh_rc') === false) return;
    wp_enqueue_style('bcgh-rc-style', BCGH_URL . 'assets/calculator.css', [], BCGH_VERSION);
    wp_enqueue_script('bcgh-rc-script', BCGH_URL . 'assets/calculator.js', ['jquery'], BCGH_VERSION, true);
    wp_localize_script('bcgh-rc-script', 'BCGH_RC', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('bcgh_rc_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'bcgh_rc_enqueue');

add_shortcode('buildcostgh_rc', function () {
    ob_start();
    include BCGH_PATH . 'templates/residential-cost-ui.php';
    return ob_get_clean();
});

add_action('wp_ajax_buildcostgh_rc', 'bcgh_ajax_rc');
add_action('wp_ajax_nopriv_buildcostgh_rc', 'bcgh_ajax_rc');
function bcgh_ajax_rc() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bcgh_rc_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }

    $category   = sanitize_text_field($_POST['category'] ?? 'residential');
    $project    = sanitize_text_field($_POST['project'] ?? 'My Project');
    $location   = sanitize_text_field($_POST['location'] ?? 'other-urban');
    $building   = sanitize_text_field($_POST['building'] ?? 'single-storey');
    $area       = max(10, (float)($_POST['area'] ?? 250));
    $floors     = max(1, (int)($_POST['floors'] ?? 1));
    $height     = (float)($_POST['height'] ?? 2.7);
    $finish     = sanitize_text_field($_POST['finish'] ?? 'standard');
    $roof       = sanitize_text_field($_POST['roof'] ?? 'tile');
    $extras     = !empty($_POST['extras']) ? array_map('sanitize_text_field', (array)$_POST['extras']) : [];
    $pro        = sanitize_text_field($_POST['professional'] ?? 'all');
    $contingency = max(0, (int)($_POST['contingency'] ?? 10));

    $loc_mult = [
        'accra' => 1.20, 'tema' => 1.10, 'kumasi' => 1.05, 'takoradi' => 1.05,
        'cape-coast' => 0.95, 'tamale' => 0.90, 'other-urban' => 1.00, 'rural' => 0.85,
    ];
    $location_factor = $loc_mult[$location] ?? 1.0;
    $loc_labels = [
        'accra' => 'Accra', 'tema' => 'Tema', 'kumasi' => 'Kumasi', 'takoradi' => 'Takoradi',
        'cape-coast' => 'Cape Coast', 'tamale' => 'Tamale', 'other-urban' => 'Other Urban', 'rural' => 'Rural',
    ];

    if ($category === 'non-residential') {
        $base_rates = [
            'commercial-office' => 9500, 'retail' => 8500, 'industrial' => 7800,
            'warehouse'         => 6200, 'educational' => 7200, 'healthcare' => 12000,
            'mixed-use'         => 8800, 'hospitality' => 10000,
        ];
        $base_rate = $base_rates[$building] ?? 8000;

        $finish_mult = ['basic' => 0.80, 'standard' => 1.00, 'premium' => 1.30, 'high-spec' => 1.70];
        $finish_factor = $finish_mult[$finish] ?? 1.0;

        $roof_premium = ['metal' => 0, 'tile' => 80, 'flat' => 120, 'green' => 350];
        $roof_extra = ($roof_premium[$roof] ?? 0) * $area;

        $extra_map = [
            'parking' => 80000, 'hvac' => 150000, 'fire-safety' => 65000,
            'elevator' => 180000, 'security' => 45000, 'landscaping' => 50000,
            'backup-gen' => 85000, 'data-cabling' => 35000,
        ];
        $extras_total = 0;
        $extras_list = [];
        foreach ($extra_map as $key => $cost) {
            if (in_array($key, $extras)) {
                $extras_total += $cost;
                $extras_list[] = ['name' => $key, 'cost' => $cost];
            }
        }

        $floor_factor = 1.0 + ($floors - 1) * 0.06;
        $adjusted_rate = $base_rate * $finish_factor * $location_factor * $floor_factor;
        $construction_cost = $adjusted_rate * $area + $roof_extra;

        $element_pct = [
            'Substructure (Foundation)'        => 12,
            'Superstructure (Frame & Walls)'   => 25,
            'Roofing'                          => 8,
            'Flooring & Finishes'              => 12,
            'MEP (Electrical & Plumbing)'      => 10,
            'HVAC & Mechanical Systems'        => 10,
            'Windows, Doors & Glazing'         => 8,
            'Fire Safety & Special Systems'    => 7,
            'External Works & Parking'         => 8,
        ];

        $fee_schedule = [
            'all'   => [['Architect', 5], ['Structural Engineer', 2.5], ['Quantity Surveyor', 1.5], ['MEP Engineer', 2]],
            'basic' => [['Architect', 5], ['Structural Engineer', 2.5]],
            'full'  => [['Architect', 5], ['Structural Engineer', 2.5], ['Quantity Surveyor', 1.5], ['MEP Engineer', 2], ['Project Manager', 3]],
            'none'  => [],
        ];
    } else {
        $base_rates = [
            'single-storey' => 7200, 'multi-storey'  => 9000, 'luxury-villa'  => 14000,
            'apartment'     => 8000, 'townhouse'     => 7800, 'compound'      => 6500,
        ];
        $base_rate = $base_rates[$building] ?? 7500;

        $finish_mult = ['budget' => 0.75, 'standard' => 1.00, 'premium' => 1.35, 'luxury' => 1.80];
        $finish_factor = $finish_mult[$finish] ?? 1.0;

        $roof_premium = ['metal' => 0, 'tile' => 80, 'slate' => 200, 'green' => 450];
        $roof_extra = ($roof_premium[$roof] ?? 0) * $area;

        $extra_map = [
            'carport' => 45000, 'swimming' => 120000, 'garden' => 35000, 'fence' => 28000,
            'solar' => 85000, 'borehole' => 40000, 'generator' => 55000, 'kitchen' => 65000,
        ];
        $extras_total = 0;
        $extras_list = [];
        foreach ($extra_map as $key => $cost) {
            if (in_array($key, $extras)) {
                $extras_total += $cost;
                $extras_list[] = ['name' => $key, 'cost' => $cost];
            }
        }

        $floor_factor = 1.0 + ($floors - 1) * 0.08;
        $adjusted_rate = $base_rate * $finish_factor * $location_factor * $floor_factor;
        $construction_cost = $adjusted_rate * $area + $roof_extra;

        $element_pct = [
            'Substructure (Foundation)'      => 15,
            'Superstructure (Frame & Walls)' => 28,
            'Roofing'                        => 12,
            'Flooring & Finishes'            => 14,
            'MEP (Electrical & Plumbing)'    => 12,
            'Windows & Doors'                => 8,
            'Internal Finishes'              => 6,
            'External Works'                 => 5,
        ];

        $fee_schedule = [
            'all'   => [['Architect', 6], ['Structural Engineer', 3], ['Quantity Surveyor', 2]],
            'basic' => [['Architect', 6], ['Structural Engineer', 3]],
            'full'  => [['Architect', 6], ['Structural Engineer', 3], ['Quantity Surveyor', 2], ['Project Manager', 4]],
            'none'  => [],
        ];
    }

    $elements = [];
    foreach ($element_pct as $name => $pct) {
        $elements[] = ['name' => $name, 'pct' => $pct, 'amount' => round($construction_cost * $pct / 100)];
    }

    $fees_chosen = $fee_schedule[$pro] ?? $fee_schedule['all'];
    $fees_total = 0;
    $fees_list = [];
    foreach ($fees_chosen as $f) {
        $fee_amt = round($construction_cost * $f[1] / 100);
        $fees_total += $fee_amt;
        $fees_list[] = ['name' => $f[0], 'pct' => $f[1], 'amount' => $fee_amt];
    }

    $contingency_amount = round(($construction_cost + $fees_total + $extras_total) * $contingency / 100);
    $grand_total = $construction_cost + $fees_total + $extras_total + $contingency_amount;
    $usd_total = round($grand_total / 15.5);

    wp_send_json_success([
        'project'           => $project,
        'location'          => $loc_labels[$location] ?? $location,
        'location_factor'   => $location_factor,
        'building'          => $building,
        'area'              => $area,
        'floors'            => $floors,
        'finish'            => $finish,
        'category'          => $category,
        'base_rate'         => $base_rate,
        'adjusted_rate'     => round($adjusted_rate),
        'construction_cost' => $construction_cost,
        'roof_extra'        => $roof_extra,
        'elements'          => $elements,
        'extras'            => $extras_list,
        'extras_total'      => $extras_total,
        'fees'              => $fees_list,
        'fees_total'        => $fees_total,
        'contingency_pct'   => $contingency,
        'contingency_amount'=> $contingency_amount,
        'grand_total'       => $grand_total,
        'usd_total'         => $usd_total,
        'cost_per_sqm'      => round($grand_total / $area),
    ]);
}

// -----------------------------------------------------------------
// ADMIN PAGES
// -----------------------------------------------------------------
add_action('admin_menu', function () {
    add_menu_page('BuildCostGH', 'BuildCostGH', 'manage_options', 'bcgh-main', 'bcgh_render_ledger_page', 'dashicons-admin-tools');
    add_submenu_page('bcgh-main', 'Cement Ledger', 'Cement Ledger', 'manage_options', 'bcgh-main', 'bcgh_render_ledger_page');
    add_submenu_page('bcgh-main', 'Approval Queue', 'Approval Queue', 'manage_options', 'bcgh-queue', 'bcgh_render_queue_page');
    add_submenu_page('bcgh-main', 'Assumptions', 'Assumptions', 'manage_options', 'bcgh-assumptions', 'bcgh_render_assumptions_page');
});

function bcgh_render_ledger_page() {
    global $wpdb;
    if (!empty($_POST['bcgh_add_cement']) && check_admin_referer('bcgh_add_cement_action')) {
        $wpdb->insert($wpdb->prefix . 'bcgh_cement_log', [
            'log_date' => sanitize_text_field($_POST['log_date']),
            'price' => floatval($_POST['price']),
            'brand' => sanitize_text_field($_POST['brand']),
            'source' => sanitize_text_field($_POST['source']),
            'logged_by' => get_current_user_id(),
        ]);
        echo '<div class="notice notice-success"><p>Logged.</p></div>';
    }
    $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bcgh_cement_log WHERE deleted_at IS NULL ORDER BY log_date DESC LIMIT 30");
    ?>
    <div class="wrap">
      <h1>Cement Price Ledger</h1>
      <p>No public cement-price API exists. Log a real checked price below — every entry over threshold gets auto-routed to the Approval Queue instead of applying silently.</p>
      <form method="post">
        <?php wp_nonce_field('bcgh_add_cement_action'); ?>
        <table class="form-table">
          <tr><th>Date</th><td><input type="date" name="log_date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>"></td></tr>
          <tr><th>Price (GHS/bag)</th><td><input type="number" step="0.01" name="price"></td></tr>
          <tr><th>Brand</th><td><input type="text" name="brand" placeholder="e.g. Ghacem"></td></tr>
          <tr><th>Source</th><td><input type="text" name="source" placeholder="e.g. vendor visit, URL"></td></tr>
        </table>
        <p><button type="submit" name="bcgh_add_cement" class="button button-primary">Log entry</button></p>
      </form>
      <h2>Recent entries</h2>
      <table class="widefat"><thead><tr><th>Date</th><th>Price</th><th>Brand</th><th>Source</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?>
        <tr><td><?php echo esc_html($r->log_date); ?></td><td>GHS <?php echo esc_html($r->price); ?></td><td><?php echo esc_html($r->brand); ?></td><td><?php echo esc_html($r->source); ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
    <?php
}

function bcgh_render_queue_page() {
    global $wpdb;
    $t = $wpdb->prefix . 'bcgh_agent_actions';

    if (!empty($_POST['bcgh_queue_action']) && check_admin_referer('bcgh_queue_action_nonce')) {
        $id = intval($_POST['action_id']);
        $decision = sanitize_text_field($_POST['bcgh_queue_action']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d", $id));
        if ($row && $decision === 'approve' && $row->agent_type === 'anomaly_flag') {
            $input = json_decode($row->input_snapshot, true);
            $wpdb->insert($wpdb->prefix . 'bcgh_cement_log', [
                'log_date' => $input['date'], 'price' => $input['price'],
                'brand' => $input['brand'], 'source' => $input['source'],
                'logged_by' => get_current_user_id(),
            ]);
        }
        $wpdb->update($t, ['status' => $decision === 'approve' ? 'approved' : 'rejected', 'reviewed_by' => get_current_user_id(), 'reviewed_at' => current_time('mysql')], ['id' => $id]);
        echo '<div class="notice notice-success"><p>Action recorded.</p></div>';
    }

    $rows = $wpdb->get_results("SELECT * FROM $t WHERE status='pending' ORDER BY created_at DESC");
    ?>
    <div class="wrap">
      <h1>Agent Approval Queue</h1>
      <p>Nothing here goes live until you approve it.</p>
      <?php if (!$rows): ?><p><em>Queue is empty.</em></p><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <div style="border:1px solid #ccd0d4;padding:14px;margin-bottom:10px;background:#fff;">
          <strong><?php echo esc_html($r->agent_type); ?></strong> — <?php echo esc_html($r->created_at); ?>
          <pre style="background:#f6f7f7;padding:8px;"><?php echo esc_html($r->input_snapshot); ?></pre>
          <form method="post" style="display:inline;">
            <?php wp_nonce_field('bcgh_queue_action_nonce'); ?>
            <input type="hidden" name="action_id" value="<?php echo esc_attr($r->id); ?>">
            <button type="submit" name="bcgh_queue_action" value="approve" class="button button-primary">Approve</button>
            <button type="submit" name="bcgh_queue_action" value="reject" class="button">Reject</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
}

function bcgh_render_assumptions_page() {
    if (!empty($_POST['bcgh_save_assumptions']) && check_admin_referer('bcgh_assumptions_action')) {
        update_option('bcgh_labor_inflation_pct', floatval($_POST['labor']));
        update_option('bcgh_anomaly_threshold_pct', floatval($_POST['threshold']));
        echo '<div class="notice notice-success"><p>Saved.</p></div>';
    }
    ?>
    <div class="wrap">
      <h1>Model Assumptions</h1>
      <form method="post">
        <?php wp_nonce_field('bcgh_assumptions_action'); ?>
        <table class="form-table">
          <tr><th>Annual labor inflation (%)</th><td><input type="number" name="labor" value="<?php echo esc_attr(get_option('bcgh_labor_inflation_pct', 18)); ?>"></td></tr>
          <tr><th>Anomaly-flag threshold (%)</th><td><input type="number" name="threshold" value="<?php echo esc_attr(get_option('bcgh_anomaly_threshold_pct', 25)); ?>">
          <p class="description">A cement entry more than this % away from the trailing 60-day average gets routed to the Approval Queue instead of applied directly.</p></td></tr>
        </table>
        <p><button type="submit" name="bcgh_save_assumptions" class="button button-primary">Save</button></p>
      </form>
    </div>
    <?php
}
