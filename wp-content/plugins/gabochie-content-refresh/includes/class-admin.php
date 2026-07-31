<?php
defined('ABSPATH') || exit;

class GCR_Admin {
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
    }

    public function add_menu() {
        add_menu_page(
            'Content Refresh',
            'Content Refresh',
            'manage_options',
            'gcr-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-update',
            71
        );
        add_submenu_page(
            'gcr-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'gcr-dashboard',
            array($this, 'render_dashboard')
        );
        add_submenu_page(
            'gcr-dashboard',
            'Queue',
            'Queue',
            'manage_options',
            'gcr-queue',
            array($this, 'render_queue')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'gcr-') === false) return;
        wp_enqueue_style('gcr-admin', GCR_URL . 'assets/admin.css', array(), GCR_VERSION);
        wp_enqueue_script('gcr-admin', GCR_URL . 'assets/admin.js', array('jquery'), GCR_VERSION, true);
        wp_localize_script('gcr-admin', 'gcr', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gcr_ajax'),
        ));
    }

    public function render_dashboard() {
        $monitor = GCR_Monitor::instance();
        $queue = $monitor->get_queue();

        $all_posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));

        $stale = 0;
        $decaying = 0;
        $critical = 0;

        foreach ($all_posts as $pid) {
            $s = get_post_meta($pid, '_gcr_status', true);
            if ($s === 'stale') $stale++;
            if ($s === 'decaying') $decaying++;
            if ($s === 'critical') $critical++;
        }

        $last_scan = get_option('gcr_last_scan', 'Never');
        ?>
        <div class="wrap gcr-wrap">
            <h1>Content Refresh — Dashboard</h1>
            <p style="color:#555;">Monitors published posts for content decay and queues them for refresh.</p>

            <div class="gcr-cards">
                <div class="gcr-card"><strong><?php echo count($all_posts); ?></strong><span>Total Published</span></div>
                <div class="gcr-card"><strong style="color:#996800;"><?php echo $stale; ?></strong><span>Stale</span></div>
                <div class="gcr-card"><strong style="color:#b32d2e;"><?php echo $decaying; ?></strong><span>Decaying</span></div>
                <div class="gcr-card"><strong style="color:#d63638;"><?php echo $critical; ?></strong><span>Critical</span></div>
                <div class="gcr-card"><strong><?php echo count($queue); ?></strong><span>In Queue</span></div>
            </div>

            <p style="margin-top:16px;">
                <strong>Last scan:</strong> <?php echo esc_html($last_scan); ?>
                <button type="button" class="button button-primary gcr-run-scan" style="margin-left:12px;">Run Scan Now</button>
                <span class="spinner" style="float:none;margin-left:4px;"></span>
                <span id="gcr-scan-result" style="margin-left:8px;color:#787c82;"></span>
            </p>
        </div>
        <?php
    }

    public function render_queue() {
        $monitor = GCR_Monitor::instance();
        $queue_ids = $monitor->get_queue();
        ?>
        <div class="wrap gcr-wrap">
            <h1>Content Refresh — Queue</h1>
            <p style="color:#555;">Posts that need a content refresh pass.</p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queue_ids)): ?>
                    <tr>
                        <td colspan="4">No posts in the queue. All content is fresh.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($queue_ids as $pid):
                            $status = get_post_meta($pid, '_gcr_status', true);
                            $modified = get_post_modified_time('Y-m-d', false, $pid);
                            $color = $status === 'critical' ? '#d63638' : ($status === 'decaying' ? '#b32d2e' : '#996800');
                        ?>
                        <tr>
                            <td><a href="<?php echo get_edit_post_link($pid); ?>"><?php echo esc_html(get_the_title($pid)); ?></a></td>
                            <td style="color:<?php echo $color; ?>;font-weight:600;"><?php echo ucfirst($status); ?></td>
                            <td><?php echo esc_html($modified); ?></td>
                            <td>
                                <button type="button" class="button button-small gcr-mark-refreshed" data-post-id="<?php echo $pid; ?>">Mark Refreshed</button>
                                <a href="<?php echo get_edit_post_link($pid); ?>" class="button button-small">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

GCR_Admin::instance();
