<?php
defined('ABSPATH') || exit;

class GAR_Admin {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_menu() {
        add_menu_page(
            'Ad RPM Optimizer',
            'Ad RPM',
            'manage_options',
            'gar-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            70
        );
        add_submenu_page(
            'gar-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'gar-dashboard',
            array($this, 'render_dashboard')
        );
        add_submenu_page(
            'gar-dashboard',
            'Placement Settings',
            'Settings',
            'manage_options',
            'gar-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'gar-') === false) return;
        wp_enqueue_style('gar-admin', GAR_URL . 'assets/admin.css', array(), GAR_VERSION);
        wp_enqueue_script('gar-admin', GAR_URL . 'assets/admin.js', array('jquery'), GAR_VERSION, true);
        wp_localize_script('gar-admin', 'gar', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gar_ajax'),
        ));
    }

    public function register_settings() {
        register_setting('gar_settings', 'gar_placement_config', array(
            'sanitize_callback' => array($this, 'sanitize_config'),
        ));
    }

    public function sanitize_config($input) {
        $config = array();
        $config['positions'] = isset($input['positions']) && is_array($input['positions'])
            ? array_map('sanitize_text_field', $input['positions'])
            : array();
        $config['count'] = isset($input['count']) ? intval($input['count']) : 3;
        $config['lazy_load'] = isset($input['lazy_load']) && $input['lazy_load'] === '1';
        return $config;
    }

    public function render_dashboard() {
        $config = GAR_Tracker::instance()->get_placement_config();
        ?>
        <div class="wrap gar-wrap">
            <h1>Ad RPM Optimizer — Dashboard</h1>
            <p style="color:#555;">A/B test ad placement, count, and lazy-load timing against RPM data.</p>

            <div class="gar-cards">
                <div class="gar-card" id="gar-avg-rpm"><strong>—</strong><span>Avg RPM</span></div>
                <div class="gar-card" id="gar-impressions"><strong>—</strong><span>Total Impressions</span></div>
                <div class="gar-card" id="gar-clicks"><strong>—</strong><span>Total Clicks</span></div>
                <div class="gar-card" id="gar-ctr"><strong>—</strong><span>CTR</span></div>
                <div class="gar-card" id="gar-tracked"><strong>—</strong><span>Tracked Posts</span></div>
            </div>

            <h2 style="margin-top:24px;">Current Placement Config</h2>
            <table class="wp-list-table widefat fixed striped">
                <tr><th>Positions</th><td><?php echo esc_html(implode(', ', $config['positions'])); ?></td></tr>
                <tr><th>Ad Count</th><td><?php echo intval($config['count']); ?></td></tr>
                <tr><th>Lazy Load</th><td><?php echo $config['lazy_load'] ? 'Enabled' : 'Disabled'; ?></td></tr>
            </table>

            <h2 style="margin-top:24px;">Test Log</h2>
            <div id="gar-test-log">
                <p style="color:#787c82;">Loading test log...</p>
            </div>

            <button type="button" class="button" id="gar-refresh-stats" style="margin-top:12px;">Refresh Stats</button>
            <span class="spinner" style="float:none;margin-left:4px;"></span>
        </div>
        <?php
    }

    public function render_settings() {
        $config = GAR_Tracker::instance()->get_placement_config();
        $all_positions = array('after_title', 'after_content', 'sidebar', 'before_comments', 'after_comments', 'before_footer');
        ?>
        <div class="wrap gar-wrap">
            <h1>Ad Placement Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('gar_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Ad Positions</th>
                        <td>
                            <?php foreach ($all_positions as $pos): ?>
                                <label style="display:block;margin-bottom:4px;">
                                    <input type="checkbox" name="gar_placement_config[positions][]" value="<?php echo esc_attr($pos); ?>" <?php checked(in_array($pos, $config['positions'])); ?>>
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $pos))); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Number of Ads</th>
                        <td>
                            <input type="number" name="gar_placement_config[count]" value="<?php echo intval($config['count']); ?>" min="1" max="20">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Lazy Load</th>
                        <td>
                            <label>
                                <input type="checkbox" name="gar_placement_config[lazy_load]" value="1" <?php checked($config['lazy_load'], true); ?>>
                                Enable lazy loading for ad units
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }
}

GAR_Admin::instance();
