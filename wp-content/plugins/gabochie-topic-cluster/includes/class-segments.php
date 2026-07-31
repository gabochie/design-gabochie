<?php
defined('ABSPATH') || exit;

class GTC_Segments {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_segment'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    }

    public function enqueue($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) return;
        wp_enqueue_style('gtc-admin', GTC_URL . 'assets/admin.css', array(), GTC_VERSION);
    }

    public static function get_all() {
        return array(
            'aec' => array(
                'label' => 'AEC Professions',
                'segments' => array(
                    'arch' => 'Architects',
                    'qs' => 'Quantity Surveyors',
                    'mep' => 'MEP Engineers',
                    'id' => 'Interior Designers',
                    'str' => 'Structural / Civil Engineers',
                    'surv' => 'Land Surveyors',
                    'dev' => 'Developers & Property Managers',
                    'gc' => 'Contractors / Builders',
                ),
            ),
            'property' => array(
                'label' => 'Property Management & Real Estate',
                'segments' => array(
                    'pm' => 'Property Managers',
                    'll' => 'Landlords (Individual Investors)',
                    'ds' => 'Diaspora Owners',
                    'tn' => 'Tenants / Renters',
                    'ag' => 'Real Estate Agents & Brokers',
                    'fm' => 'Facilities Managers',
                    'dv' => 'Developers & Real Estate Firms',
                ),
            ),
            'tiling' => array(
                'label' => 'Tiling Industry',
                'segments' => array(
                    'til' => 'Tilers / Installers',
                    'rtl' => 'Tile Retailers',
                    'imp' => 'Tile Importers',
                    'dist' => 'Tile Distributors',
                    'arch_til' => 'Architects (Tile Spec)',
                    'const_til' => 'Contractors (Tile Works)',
                ),
            ),
            'ecosystem' => array(
                'label' => 'Built Environment Ecosystem',
                'segments' => array(
                    'legal' => 'Legal (Property Law, Rent Act)',
                    'finance' => 'Finance (Construction Loans, Mortgages)',
                    'gov' => 'Government (MMDA Permits, Lands Commission)',
                    'edu' => 'Education (Architecture Schools, KNUST)',
                    'insurance' => 'Insurance (Builders Risk, Title Insurance)',
                    'materials' => 'Building Materials (Suppliers, Pricing)',
                    'security' => 'Security (Construction Site Security)',
                    'water' => 'Water & Utilities (GWCL, Boreholes)',
                    'energy' => 'Energy (Solar, Grid, Backup Power)',
                    'transport' => 'Transport & Logistics (Materials Haulage)',
                    'waste' => 'Waste Management (Construction Waste)',
                    'telco' => 'Telecom (Internet, Smart Building)',
                    'health_safety' => 'Health & Safety (Construction HSE)',
                    'valuation' => 'Valuation & Appraisal',
                ),
            ),
        );
    }

    public static function get_flat() {
        $flat = array();
        foreach (self::get_all() as $portfolio => $data) {
            foreach ($data['segments'] as $key => $label) {
                $flat[$key] = array('label' => $label, 'portfolio' => $portfolio);
            }
        }
        return $flat;
    }

    public static function get_segment_label($key) {
        $flat = self::get_flat();
        return isset($flat[$key]) ? $flat[$key]['label'] : '';
    }

    public static function get_segment_portfolio($key) {
        $flat = self::get_flat();
        return isset($flat[$key]) ? $flat[$key]['portfolio'] : '';
    }

    public function add_meta_box() {
        $post_types = array('post', 'page');
        add_meta_box(
            'gtc_segment_box',
            'Topic Cluster Segment',
            array($this, 'render_meta_box'),
            $post_types,
            'side',
            'default'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('gtc_segment', 'gtc_segment_nonce');
        $current = get_post_meta($post->ID, '_gtc_segment', true);
        $clusters = self::get_all();
        ?>
        <p style="margin-top:4px;font-size:12px;color:#787c82;">
            Assign this page to a topic cluster for internal-linking analysis.
        </p>
        <select name="gtc_segment" style="width:100%;">
            <option value="">— No segment —</option>
            <?php foreach ($clusters as $portfolio => $data): ?>
                <optgroup label="<?php echo esc_attr($data['label']); ?>">
                    <?php foreach ($data['segments'] as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($current, $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <p style="margin:6px 0 0;font-size:11px;color:#787c82;">
            <?php if ($current): ?>
                Current: <strong><?php echo esc_html(self::get_segment_label($current)); ?></strong>
            <?php else: ?>
                Not assigned to any segment.
            <?php endif; ?>
        </p>
        <?php
    }

    public function save_segment($post_id) {
        if (!isset($_POST['gtc_segment_nonce']) || !wp_verify_nonce($_POST['gtc_segment_nonce'], 'gtc_segment')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (isset($_POST['gtc_segment'])) {
            $segment = sanitize_key($_POST['gtc_segment']);
            if (empty($segment) || !isset(self::get_flat()[$segment])) {
                delete_post_meta($post_id, '_gtc_segment');
            } else {
                update_post_meta($post_id, '_gtc_segment', $segment);
            }
        }
    }

    public function get_posts_in_segment($segment_key) {
        return get_posts(array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_key' => '_gtc_segment',
            'meta_value' => $segment_key,
        ));
    }

    public function get_segment_counts() {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT meta_value as segment, COUNT(*) as count
             FROM {$wpdb->postmeta}
             WHERE meta_key = '_gtc_segment'
             AND meta_value != ''
             GROUP BY meta_value
             ORDER BY count DESC"
        );
        $counts = array();
        foreach ($results as $r) {
            $counts[$r->segment] = intval($r->count);
        }
        return $counts;
    }
}

GTC_Segments::instance();
