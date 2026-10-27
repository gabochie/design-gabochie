<?php
defined('ABSPATH') || exit;

class GRG_Admin {
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
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('wp_ajax_grg_refresh', array($this, 'ajax_refresh'));
    }

    public function add_menu() {
        add_menu_page(
            'Rank Gap Tracker',
            'Rank Gaps',
            'manage_options',
            'grg-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-area',
            72
        );
        add_submenu_page(
            'grg-dashboard',
            'Gap Analysis',
            'Gap Analysis',
            'manage_options',
            'grg-dashboard',
            array($this, 'render_dashboard')
        );
        add_submenu_page(
            'grg-dashboard',
            'Keyword Clusters',
            'Clusters',
            'manage_options',
            'grg-clusters',
            array($this, 'render_clusters')
        );
        add_submenu_page(
            'grg-dashboard',
            'Content Targets',
            'Content Targets',
            'edit_posts',
            'grg-targets',
            array($this, 'render_targets')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'grg-') === false) return;
        wp_enqueue_style('grg-admin', GRG_URL . 'assets/admin.css', array(), GRG_VERSION);
        wp_enqueue_script('grg-admin', GRG_URL . 'assets/admin.js', array('jquery'), GRG_VERSION, true);
        wp_localize_script('grg-admin', 'grg', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('grg_ajax'),
        ));
    }

    public function handle_actions() {
        if (isset($_POST['grg_save_clusters']) && check_admin_referer('grg_save_clusters')) {
            GRG_Keywords::instance()->save_clusters($this->sanitize_clusters($_POST['clusters'] ?? array()));
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>Clusters saved.</p></div>';
            });
        }
    }

    private function sanitize_clusters($raw) {
        $clusters = array();
        foreach ($raw as $key => $data) {
            $key = sanitize_key($key);
            $clusters[$key] = array(
                'label' => sanitize_text_field($data['label'] ?? ''),
                'keywords' => array(),
            );
            if (isset($data['keywords']) && is_string($data['keywords'])) {
                $lines = array_filter(array_map('trim', explode("\n", $data['keywords'])));
                foreach ($lines as $line) {
                    $clusters[$key]['keywords'][] = sanitize_text_field($line);
                }
            }
        }
        return $clusters;
    }

    public function render_dashboard() {
        $keywords = GRG_Keywords::instance();
        $gaps = GRG_Gaps::instance();
        $analysis = $gaps->analyze_gaps();
        $competitors = $gaps->get_competitor_keywords();

        $total = 0;
        $covered = 0;
        $high_priority = 0;
        foreach ($analysis as $a) {
            $total += $a['total_keywords'];
            $covered += $a['covered'];
            if ($a['priority'] === 'high') $high_priority++;
        }
        $gap_count = $total - $covered;
        $coverage = $total > 0 ? round($covered / $total * 100) : 0;
        ?>
        <div class="wrap grg-wrap">
            <h1>Rank Gap Tracker — Gap Analysis</h1>
            <p style="color:#555;">Keyword coverage analysis vs. Ghana AEC competitors. Identifies content gaps that INT-01 should target.</p>

            <div class="grg-cards">
                <div class="grg-card"><strong><?php echo $total; ?></strong><span>Tracked Keywords</span></div>
                <div class="grg-card"><strong style="color:#2d8f5e;"><?php echo $coverage; ?>%</strong><span>Coverage</span></div>
                <div class="grg-card"><strong style="color:#b32d2e;"><?php echo $gap_count; ?></strong><span>Content Gaps</span></div>
                <div class="grg-card"><strong><?php echo $high_priority; ?></strong><span>High-Priority Clusters</span></div>
            </div>

            <h2 style="margin-top:24px;">Cluster Coverage</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Cluster</th>
                        <th style="width:80px;">Keywords</th>
                        <th style="width:80px;">Covered</th>
                        <th style="width:80px;">Gaps</th>
                        <th style="width:100px;">Coverage</th>
                        <th style="width:100px;">Priority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis as $key => $a):
                        $color = $a['priority'] === 'high' ? '#b32d2e' : ($a['priority'] === 'medium' ? '#996800' : '#2d8f5e');
                    ?>
                    <tr>
                        <td><a href="<?php echo admin_url('admin.php?page=grg-clusters&cluster=' . $key); ?>"><?php echo esc_html($a['label']); ?></a></td>
                        <td><?php echo $a['total_keywords']; ?></td>
                        <td><?php echo $a['covered']; ?></td>
                        <td style="color:<?php echo $a['gaps'] > 0 ? '#b32d2e' : '#2d8f5e'; ?>;"><?php echo $a['gaps']; ?></td>
                        <td>
                            <div style="background:#f0f0f1;border-radius:10px;height:18px;width:100%;position:relative;overflow:hidden;">
                                <div style="background:<?php echo $a['coverage_percent'] >= 50 ? '#2d8f5e' : '#d63638'; ?>;width:<?php echo $a['coverage_percent']; ?>%;height:100%;border-radius:10px;"></div>
                                <span style="position:absolute;top:0;left:6px;font-size:10px;line-height:18px;font-weight:600;"><?php echo $a['coverage_percent']; ?>%</span>
                            </div>
                        </td>
                        <td style="color:<?php echo $color; ?>;font-weight:600;"><?php echo ucfirst($a['priority']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2 style="margin-top:28px;">Competitor Keyword Scan</h2>
            <p style="color:#787c82;">Keywords competitors rank for that design.gabochie.com may not cover well.</p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Competitor Keyword</th>
                        <th>Source</th>
                        <th style="width:100px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competitors as $c): ?>
                    <tr>
                        <td><?php echo esc_html($c['keyword']); ?></td>
                        <td style="font-size:12px;color:#787c82;"><?php echo esc_html($c['source']); ?></td>
                        <td><?php echo $c['we_cover'] ? '<span style="color:#2d8f5e;">Covered</span>' : '<span style="color:#b32d2e;font-weight:600;">GAP</span>'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_clusters() {
        $keywords = GRG_Keywords::instance();
        $clusters = $keywords->get_clusters();
        ?>
        <div class="wrap grg-wrap">
            <h1>Keyword Clusters</h1>
            <p style="color:#555;">Edit the keyword clusters used for gap analysis. Each cluster targets a specific AEC topic area.</p>

            <form method="post">
                <?php wp_nonce_field('grg_save_clusters'); ?>
                <div class="grg-clusters-grid">
                    <?php foreach ($clusters as $key => $cluster): ?>
                    <div class="grg-cluster-card">
                        <h3>
                            <input type="text" name="clusters[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($cluster['label']); ?>" style="width:100%;font-weight:600;border:none;background:transparent;padding:0;">
                        </h3>
                        <label style="font-size:11px;color:#787c82;display:block;margin-bottom:4px;">Keywords (one per line):</label>
                        <textarea name="clusters[<?php echo esc_attr($key); ?>][keywords]" rows="8" style="width:100%;font-family:monospace;font-size:11px;"><?php echo esc_textarea(implode("\n", $cluster['keywords'])); ?></textarea>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top:16px;">
                    <button type="submit" name="grg_save_clusters" class="button button-primary">Save All Clusters</button>
                </p>
            </form>
        </div>
        <?php
    }

    public function render_targets() {
        $gaps = GRG_Gaps::instance();
        $targets = $gaps->get_priority_content_targets(50);
        ?>
        <div class="wrap grg-wrap">
            <h1>Content Targets for INT-01</h1>
            <p style="color:#555;">Priority content gaps that should be fed into ContentPipeline for drafting. Sorted by priority.</p>

            <?php if (empty($targets)): ?>
                <div class="notice notice-success inline"><p>No content gaps found! All tracked keywords are covered.</p></div>
            <?php else: ?>
                <p><strong style="color:#b32d2e;"><?php echo count($targets); ?> gap(s)</strong> — use ContentPipeline to create drafts for these topics.</p>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:80px;">Priority</th>
                            <th>Keyword</th>
                            <th>Cluster</th>
                            <th style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($targets as $t):
                            $color = $t['priority'] === 'high' ? '#b32d2e' : ($t['priority'] === 'medium' ? '#996800' : '#787c82');
                        ?>
                        <tr>
                            <td style="color:<?php echo $color; ?>;font-weight:600;"><?php echo ucfirst($t['priority']); ?></td>
                            <td><strong><?php echo esc_html($t['keyword']); ?></strong></td>
                            <td style="color:#787c82;font-size:12px;"><?php echo esc_html($t['cluster']); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=gcp-new-draft&topic=' . urlencode($t['keyword'])); ?>" class="button button-small" target="_blank">Create Draft</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public function ajax_refresh() {
        check_ajax_referer('grg_ajax');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        $gaps = GRG_Gaps::instance();
        $analysis = $gaps->analyze_gaps();
        $competitors = $gaps->get_competitor_keywords();
        $targets = $gaps->get_priority_content_targets(20);

        wp_send_json_success(array(
            'analysis' => $analysis,
            'competitors' => $competitors,
            'targets' => $targets,
            'message' => 'Analysis refreshed.',
        ));
    }
}

GRG_Admin::instance();
