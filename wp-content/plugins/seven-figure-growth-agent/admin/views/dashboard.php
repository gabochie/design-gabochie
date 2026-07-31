<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$last_scan = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}growth_ai_scans ORDER BY id DESC LIMIT 1");
$tasks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}growth_ai_tasks ORDER BY ice_score DESC LIMIT 5");
$settings = get_option('growth_agent_settings', array());

$business_name = !empty($settings['business_name']) ? $settings['business_name'] : get_bloginfo('name');
$target_revenue = !empty($settings['target_revenue']) ? $settings['target_revenue'] : '$1,000,000 / yr';
?>
<div class="wrap growth-agent-wrap">
    <div class="growth-header">
        <div class="growth-title-box">
            <h2>🚀 7-Figure Growth Intelligence Operating System</h2>
            <p>Autonomous AI Agent for WordPress Revenue Scale & Audit Optimization</p>
        </div>
        <div class="growth-actions">
            <a href="<?php echo admin_url('admin.php?page=growth-agent-scan'); ?>" class="button button-primary button-hero">⚡ Run Website Scan</a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="growth-dashboard-grid">
        <div class="card score-card">
            <span class="card-label">Growth Score</span>
            <div class="score-display">78 <span class="max-score">/ 100</span></div>
            <p className="card-sub">7-Figure Scale Readiness</p>
        </div>

        <div class="card score-card">
            <span class="card-label">Target Goal</span>
            <div class="score-display text-emerald"><?php echo esc_html($target_revenue); ?></div>
            <p className="card-sub"><?php echo esc_html($business_name); ?></p>
        </div>

        <div class="card score-card">
            <span class="card-label">Website Health</span>
            <div class="score-display text-blue">84 <span class="max-score">/ 100</span></div>
            <p className="card-sub">WP Theme & Plugin Architecture</p>
        </div>

        <div class="card score-card">
            <span class="card-label">Top Focus Today</span>
            <div class="focus-title">Hero Headline CRO + Pricing Split</div>
            <a href="<?php echo admin_url('admin.php?page=growth-agent-actions'); ?>" class="button button-secondary">Open CEO Center →</a>
        </div>
    </div>

    <!-- Quick Actions Banner -->
    <div class="card banner-card">
        <h3>🎯 Autonomous Action Center</h3>
        <p>The AI Agent automatically recalculates top actions based on the ICE Framework (Impact × Confidence / Effort).</p>
        <div class="tasks-preview-list">
            <div class="task-preview-item">
                <span class="badge badge-high">Priority #1</span>
                <strong>Rewrite Hero Headline & Value Proposition</strong>
                <span class="ice-tag">ICE: 27.0</span>
            </div>
            <div class="task-preview-item">
                <span class="badge badge-high">Priority #2</span>
                <strong>Shorten Form to 2 Fields + Instant Calendar</strong>
                <span class="ice-tag">ICE: 18.0</span>
            </div>
        </div>
    </div>
</div>
