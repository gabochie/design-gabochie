<?php
defined('ABSPATH') || exit;

class Gabochie_SEO_Admin {
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
        add_action('wp_ajax_gseo_regenerate_sitemap', array($this, 'ajax_regenerate_sitemap'));
        add_action('wp_ajax_gseo_clear_analysis_cache', array($this, 'ajax_clear_analysis_cache'));
        add_action('wp_ajax_gseo_bulk_clear_cache', array($this, 'ajax_bulk_clear_cache'));
        add_action('wp_ajax_gseo_sitemap_settings', array($this, 'ajax_sitemap_settings'));
        add_action('init', array($this, 'maybe_serve_sitemap'));
        add_action('save_post', array($this, 'clear_post_cache'));
    }

    public function add_menu() {
        add_menu_page(
            'Gabochie SEO',
            'Gabochie SEO',
            'manage_options',
            'gabochie-seo',
            array($this, 'render_dashboard'),
            'dashicons-search',
            75
        );
        add_submenu_page(
            'gabochie-seo',
            'SEO Dashboard',
            'Dashboard',
            'manage_options',
            'gabochie-seo',
            array($this, 'render_dashboard')
        );
        add_submenu_page(
            'gabochie-seo',
            'Gabochie SEO Settings',
            'Settings',
            'manage_options',
            'gabochie-seo-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'gabochie-seo') === false && $hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_style('gseo-admin', GSEO_URL . 'assets/admin.css', array(), GSEO_VERSION);
        wp_enqueue_script('gseo-admin', GSEO_URL . 'assets/admin.js', array('jquery'), GSEO_VERSION, true);
        wp_localize_script('gseo-admin', 'gseo', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gseo_ajax'),
        ));
    }

    public function render_dashboard() {
        $post_count = wp_count_posts('post')->publish;
        $page_count = wp_count_posts('page')->publish;
        $total = $post_count + $page_count;

        $analyzed = 0;
        $avg_score = 0;
        $all_posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => -1,
            'fields' => 'ids',
        ));
        $analysis_inst = Gabochie_SEO_Analysis::instance();
        foreach ($all_posts as $pid) {
            $a = get_transient('gseo_analysis_' . $pid);
            if ($a) {
                $analyzed++;
                $avg_score += $a['score'];
            }
        }
        $avg_score = $analyzed > 0 ? round($avg_score / $analyzed) : 0;

        $keyword_count = 0;
        foreach ($all_posts as $pid) {
            if (!empty(get_post_meta($pid, '_gseo_focus_keyword', true))) $keyword_count++;
        }
        ?>
        <div class="wrap gseo-dashboard">
            <h1>Gabochie Agentic SEO</h1>
            <p style="color:#555;">AI-powered SEO for Ghana AEC content. Focus keyphrases, schema markup, and content analysis.</p>

            <div class="gseo-cards">
                <div class="gseo-card">
                    <span class="gseo-card-icon dashicons dashicons-admin-page"></span>
                    <div>
                        <strong><?php echo $total; ?></strong>
                        <span>Published Pages/Posts</span>
                    </div>
                </div>
                <div class="gseo-card">
                    <span class="gseo-card-icon dashicons dashicons-chart-bar"></span>
                    <div>
                        <strong><?php echo $analyzed; ?> / <?php echo $total; ?></strong>
                        <span>Content Analyzed</span>
                    </div>
                </div>
                <div class="gseo-card">
                    <span class="gseo-card-icon dashicons dashicons-star-filled"></span>
                    <div>
                        <strong><?php echo $avg_score; ?>/100</strong>
                        <span>Average SEO Score</span>
                    </div>
                </div>
                <div class="gseo-card">
                    <span class="gseo-card-icon dashicons dashicons-tag"></span>
                    <div>
                        <strong><?php echo $keyword_count; ?></strong>
                        <span>With Focus Keyphrase</span>
                    </div>
                </div>
            </div>

            <h2 style="margin-top:30px;">Content Analysis</h2>
            <p>
                <button type="button" class="button button-primary gseo-analyze-all" data-nonce="<?php echo wp_create_nonce('gseo_ajax'); ?>">Analyze All Content</button>
                <button type="button" class="button gseo-clear-cache" data-nonce="<?php echo wp_create_nonce('gseo_ajax'); ?>">Clear Analysis Cache</button>
            </p>
            <div id="gseo-analysis-progress" style="margin:10px 0;display:none;"><span class="spinner is-active"></span> Analyzing...</div>

            <table class="wp-list-table widefat fixed striped" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>SEO Score</th>
                        <th>Focus Keyphrase</th>
                        <th>Words</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent = get_posts(array(
                        'post_type' => array('post', 'page'),
                        'posts_per_page' => 20,
                        'orderby' => 'modified',
                        'order' => 'DESC',
                    ));
                    foreach ($recent as $p) {
                        $a = get_transient('gseo_analysis_' . $p->ID) ?: $analysis_inst->analyze_post($p->ID);
                        $kw = get_post_meta($p->ID, '_gseo_focus_keyword', true);
                        $edit_link = get_edit_post_link($p->ID);
                        $color = $a['grade'] === 'good' ? '#2d8f5e' : ($a['grade'] === 'ok' ? '#996800' : '#d63638');
                        $bg = $a['grade'] === 'good' ? '#edfaef' : ($a['grade'] === 'ok' ? '#fcf9e8' : '#fcf0f1');
                        $score_display = $a ? '<span style="display:inline-block;padding:2px 10px;border-radius:12px;font-weight:600;font-size:12px;color:' . $color . ';background:' . $bg . ';">' . $a['score'] . '/100</span>' : '<span style="color:#787c82;">—</span>';
                        echo '<tr>
                            <td><a href="' . $edit_link . '">' . esc_html($p->post_title) . '</a></td>
                            <td>' . ucfirst($p->post_type) . '</td>
                            <td>' . $score_display . '</td>
                            <td>' . esc_html($kw ?: '—') . '</td>
                            <td>' . ($a ? $a['word_count'] : '—') . '</td>
                            <td><a href="' . $edit_link . '#gseo_meta_box" class="button button-small">Edit SEO</a></td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_settings() {
        $sitemap_enabled = get_option('gseo_sitemap_enabled', '1');
        $sitemap_types = get_option('gseo_sitemap_types', array('post', 'page'));
        ?>
        <div class="wrap">
            <h1>Gabochie SEO Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('gseo_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">XML Sitemap</th>
                        <td>
                            <label>
                                <input type="checkbox" name="gseo_sitemap_enabled" value="1" <?php checked($sitemap_enabled, '1'); ?>>
                                Enable XML sitemap
                            </label>
                            <p class="description">Sitemap URL: <a href="<?php echo home_url('/sitemap.xml'); ?>" target="_blank"><?php echo home_url('/sitemap.xml'); ?></a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sitemap Post Types</th>
                        <td>
                            <label><input type="checkbox" name="gseo_sitemap_types[]" value="post" <?php checked(in_array('post', $sitemap_types)); ?>> Posts</label><br>
                            <label><input type="checkbox" name="gseo_sitemap_types[]" value="page" <?php checked(in_array('page', $sitemap_types)); ?>> Pages</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Rank Math Compatibility</th>
                        <td>
                            <p class="description">Gabochie SEO extends Rank Math's schema with Ghana AEC-specific data. Both plugins can run simultaneously. To disable Rank Math on specific pages, use the checkbox in the Gabochie SEO meta box.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
            <hr>
            <h2>Tools</h2>
            <p>
                <button type="button" class="button button-primary gseo-regen-sitemap">Regenerate Sitemap</button>
                <button type="button" class="button gseo-clear-cache">Clear Analysis Cache</button>
            </p>
            <div id="gseo-tools-message" style="margin-top:10px;"></div>
        </div>
        <?php
    }

    public function maybe_serve_sitemap() {
        if (get_option('gseo_sitemap_enabled', '1') !== '1') return;
        $request_path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        if ($request_path !== 'sitemap.xml') return;

        $sitemap = get_transient('gseo_sitemap_xml');
        if (!$sitemap) {
            $sitemap = $this->generate_sitemap();
            set_transient('gseo_sitemap_xml', $sitemap, HOUR_IN_SECONDS);
        }
        if ($sitemap) {
            header('Content-Type: application/xml; charset=utf-8');
            echo $sitemap;
            exit;
        }
    }

    private function generate_sitemap() {
        $types = get_option('gseo_sitemap_types', array('post', 'page'));
        if (empty($types)) return '';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $xml .= '  <url><loc>' . esc_url(home_url('/')) . '</loc><priority>1.0</priority></url>' . "\n";

        $posts = get_posts(array(
            'post_type' => $types,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ));
        foreach ($posts as $p) {
            $priority = $p->post_type === 'page' && $p->post_parent === 0 ? '0.9' : '0.7';
            $xml .= '  <url>';
            $xml .= '<loc>' . esc_url(get_permalink($p->ID)) . '</loc>';
            $xml .= '<lastmod>' . get_the_modified_date('c', $p->ID) . '</lastmod>';
            $xml .= '<priority>' . $priority . '</priority>';
            $xml .= '</url>' . "\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    public function ajax_regenerate_sitemap() {
        check_ajax_referer('gseo_ajax');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized.'));
        delete_transient('gseo_sitemap_xml');
        $sitemap = $this->generate_sitemap();
        set_transient('gseo_sitemap_xml', $sitemap, HOUR_IN_SECONDS);
        wp_send_json_success(array('message' => 'Sitemap regenerated.'));
    }

    public function ajax_clear_analysis_cache() {
        check_ajax_referer('gseo_ajax');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized.'));
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_gseo_analysis_%' OR option_name LIKE '_transient_timeout_gseo_analysis_%'");
        wp_send_json_success(array('message' => 'Analysis cache cleared.'));
    }

    public function ajax_bulk_clear_cache() {
        $this->ajax_clear_analysis_cache();
    }

    public function ajax_sitemap_settings() {
        check_ajax_referer('gseo_ajax');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized.'));
        $enabled = isset($_POST['enabled']) ? '1' : '0';
        $types = isset($_POST['types']) ? (array)$_POST['types'] : array('post', 'page');
        update_option('gseo_sitemap_enabled', $enabled);
        update_option('gseo_sitemap_types', $types);
        delete_transient('gseo_sitemap_xml');
        wp_send_json_success(array('message' => 'Sitemap settings saved.'));
    }

    public function clear_post_cache($post_id) {
        delete_transient('gseo_analysis_' . $post_id);
    }
}

Gabochie_SEO_Admin::instance();
