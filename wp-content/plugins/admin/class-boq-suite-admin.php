<?php
/**
 * Class BOQ_Suite_Admin
 * Unified WP Admin Page & Settings interface.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BOQ_Suite_Admin {

    public function add_admin_menu() {
        add_menu_page(
            __('BuildSuite Pro', 'boq-construction-suite'),
            __('BuildSuite Pro', 'boq-construction-suite'),
            'manage_options',
            'buildsuite-pro',
            array($this, 'render_dashboard_page'),
            'dashicons-building',
            25
        );

        add_submenu_page(
            'buildsuite-pro',
            __('Dashboard', 'boq-construction-suite'),
            __('Dashboard', 'boq-construction-suite'),
            'manage_options',
            'buildsuite-pro',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'buildsuite-pro',
            __('Master Unit Rates', 'boq-construction-suite'),
            __('Master Unit Rates', 'boq-construction-suite'),
            'manage_options',
            'boq-master-rates',
            array($this, 'render_rates_page')
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'buildsuite-pro') !== false || strpos($hook, 'boq-master-rates') !== false) {
            wp_enqueue_style('boq-suite-admin-css', BOQ_SUITE_URL . 'admin/css/boq-suite-admin.css', array(), BOQ_SUITE_VERSION);
            wp_enqueue_script('boq-suite-admin-js', BOQ_SUITE_URL . 'admin/js/boq-suite-admin.js', array('jquery'), BOQ_SUITE_VERSION, true);
        }
    }

    public function render_dashboard_page() {
        if (isset($_POST['save_boq_suite_modules'])) {
            check_admin_referer('boq_suite_save_modules');
            $modules = array(
                'estimator'  => isset($_POST['mod_estimator']) ? 1 : 0,
                'invoicing'  => isset($_POST['mod_invoicing']) ? 1 : 0,
                'jv_modeler' => isset($_POST['mod_jv_modeler']) ? 1 : 0,
                'rate_search'=> isset($_POST['mod_rate_search']) ? 1 : 0,
            );
            update_option('boq_suite_active_modules', $modules);
            echo '<div class="updated"><p>BuildSuite Pro module settings saved successfully!</p></div>';
        }

        $active_modules = get_option('boq_suite_active_modules', array(
            'estimator'  => 1,
            'invoicing'  => 1,
            'jv_modeler' => 1,
            'rate_search'=> 1,
        ));

        ?>
        <div class="wrap boq-admin-container">
            <h1 class="boq-admin-title"><span class="dashicons dashicons-building"></span> BuildSuite Pro — Construction Suite Dashboard</h1>
            <p class="boq-admin-subtitle">Unified WordPress Construction Estimating, QS Invoicing, JV Financial Modeling & Rate Search Engine</p>

            <div class="boq-metrics-grid">
                <div class="boq-metric-card">
                    <div class="boq-metric-icon" style="background:#0073aa;"><span class="dashicons dashicons-calculator"></span></div>
                    <div class="boq-metric-content">
                        <h3>BOQ Estimator</h3>
                        <p class="boq-metric-status">Active — Ready for Front-End</p>
                    </div>
                </div>
                <div class="boq-metric-card">
                    <div class="boq-metric-icon" style="background:#46b450;"><span class="dashicons dashicons-media-document"></span></div>
                    <div class="boq-metric-content">
                        <h3>QS Invoicing</h3>
                        <p class="boq-metric-status">Active — PDF Engine Ready</p>
                    </div>
                </div>
                <div class="boq-metric-card">
                    <div class="boq-metric-icon" style="background:#00a0d2;"><span class="dashicons dashicons-chart-pie"></span></div>
                    <div class="boq-metric-content">
                        <h3>JV Financial Modeler</h3>
                        <p class="boq-metric-status">Active — ROI Engine Ready</p>
                    </div>
                </div>
                <div class="boq-metric-card">
                    <div class="boq-metric-icon" style="background:#826eb4;"><span class="dashicons dashicons-search"></span></div>
                    <div class="boq-metric-content">
                        <h3>Rate Search Index</h3>
                        <p class="boq-metric-status">Active — Master Rates Indexed</p>
                    </div>
                </div>
            </div>

            <div class="boq-admin-card" style="margin-top:25px;">
                <h2>Shortcode Deployment Hub</h2>
                <p>Copy and paste any of these shortcodes onto any page, post, or Elementor layout:</p>
                <table class="widefat striped">
                    <thead>
                        <tr><th>Shortcode</th><th>Module</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>[boq_suite_estimator]</code></td><td>BOQ Estimator</td><td>Renders the interactive construction cost estimator & PDF generator.</td></tr>
                        <tr><td><code>[boq_suite_invoices]</code></td><td>QS Invoicing</td><td>Displays client billing portal & invoice creator.</td></tr>
                        <tr><td><code>[boq_suite_jv_calculator]</code></td><td>JV Modeler</td><td>Displays land equity vs. developer financial ROI modeler.</td></tr>
                        <tr><td><code>[boq_suite_rate_search]</code></td><td>Rate Search</td><td>Renders live searchable rate index bar.</td></tr>
                        <tr><td><code>[boq_suite_all_in_one]</code></td><td>Full Suite</td><td>Renders tabbed multi-module suite portal.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function render_rates_page() {
        if (isset($_POST['save_boq_rates'])) {
            check_admin_referer('boq_suite_save_rates');
            $rates = get_option('boq_suite_master_rates', array());
            if (isset($_POST['rates']) && is_array($_POST['rates'])) {
                foreach ($_POST['rates'] as $key => $val) {
                    if (isset($rates[$key])) {
                        $rates[$key]['rate']  = (float)$val['rate'];
                        $rates[$key]['title'] = sanitize_text_field($val['title']);
                        $rates[$key]['unit']  = sanitize_text_field($val['unit']);
                    }
                }
                update_option('boq_suite_master_rates', $rates);
                echo '<div class="updated"><p>Master unit rates updated successfully!</p></div>';
            }
        }

        $rates = get_option('boq_suite_master_rates', array());
        ?>
        <div class="wrap boq-admin-container">
            <h1>Master Material & Unit Rates Editor</h1>
            <p>Update base unit rates (GHS) used across all cost estimators and search engines.</p>
            <form method="post">
                <?php wp_nonce_field('boq_suite_save_rates'); ?>
                <table class="widefat striped" style="margin-top:15px;">
                    <thead>
                        <tr>
                            <th>Item Key</th>
                            <th>Description</th>
                            <th>Unit</th>
                            <th>Base Rate (GHS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rates as $key => $item): ?>
                            <tr>
                                <td><code><?php echo esc_html($key); ?></code></td>
                                <td><input type="text" name="rates[<?php echo esc_attr($key); ?>][title]" value="<?php echo esc_attr($item['title']); ?>" style="width:100%;"></td>
                                <td><input type="text" name="rates[<?php echo esc_attr($key); ?>][unit]" value="<?php echo esc_attr($item['unit']); ?>" style="width:100px;"></td>
                                <td><input type="number" step="0.01" name="rates[<?php echo esc_attr($key); ?>][rate]" value="<?php echo esc_attr($item['rate']); ?>" style="width:120px;"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="submit"><input type="submit" name="save_boq_rates" class="button button-primary" value="Save Master Rates"></p>
            </form>
        </div>
        <?php
    }
}
