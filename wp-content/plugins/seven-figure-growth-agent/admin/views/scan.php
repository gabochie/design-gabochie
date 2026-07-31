<?php
if (!defined('ABSPATH')) exit;
$settings = get_option('growth_agent_settings', array());
$site_url = get_site_url();
?>
<div class="wrap growth-agent-wrap">
    <div class="growth-header">
        <h2>🔍 Website Intelligence & Growth Audit Scanner</h2>
    </div>

    <div class="card font-card">
        <h3>Run AI Intelligence Audit</h3>
        <form id="growth-agent-scan-form" class="growth-form-grid">
            <div class="form-field full-width">
                <label>Website URL to Audit</label>
                <input type="url" name="websiteUrl" value="<?php echo esc_url($site_url); ?>" required class="regular-text">
            </div>

            <div class="form-field">
                <label>Business Name</label>
                <input type="text" name="businessName" value="<?php echo esc_attr($settings['business_name'] ?? get_bloginfo('name')); ?>" class="regular-text">
            </div>

            <div class="form-field">
                <label>Target Annual Revenue</label>
                <input type="text" name="targetRevenue" value="<?php echo esc_attr($settings['target_revenue'] ?? '$1,000,000 / yr'); ?>" class="regular-text">
            </div>

            <div class="form-field full-width">
                <button type="submit" id="btn-run-scan" class="button button-primary button-hero">
                    ✨ Execute Full AI Intelligence Scan
                </button>
            </div>
        </form>

        <div id="scan-loading-spinner" style="display:none;" class="scan-loading-box">
            <div class="spinner is-active"></div>
            <p>Evaluating website copy, sales funnel, pricing tiers, and performance...</p>
        </div>
    </div>

    <div id="scan-results-container" class="card" style="margin-top:20px;">
        <h3>Growth Audit Results</h3>
        <div class="audit-tabs">
            <button class="audit-tab active" data-tab="content">Content & Copy</button>
            <button class="audit-tab" data-tab="business">Business Model</button>
            <button class="audit-tab" data-tab="sales">Sales Funnel</button>
            <button class="audit-tab" data-tab="tech">Tech Stack</button>
        </div>

        <div id="audit-tab-content" class="audit-tab-body">
            <div className="audit-section">
                <h4>Value Proposition Analysis</h4>
                <p>We help growing businesses scale through custom workflow automation and growth services.</p>
                <div class="audit-badge badge-strong">Moderate Clarity</div>
            </div>
        </div>
    </div>
</div>
