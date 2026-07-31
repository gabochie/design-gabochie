<?php
defined('ABSPATH') || exit;

class GCP_Admin {
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
        add_action('wp_ajax_gcp_generate', array($this, 'ajax_generate'));
        add_action('wp_ajax_gcp_get_topics', array($this, 'ajax_get_topics'));
        add_action('wp_ajax_gcp_save_data', array($this, 'ajax_save_data'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_filter('post_row_actions', array($this, 'row_actions'), 10, 2);
    }

    public function add_menu() {
        $queue_count = GCP_Queue::instance()->get_count();
        $badge = $queue_count ? sprintf(' <span class="update-plugins count-%d"><span class="plugin-count">%d</span></span>', $queue_count, $queue_count) : '';

        add_menu_page(
            'Content Pipeline',
            'Content Pipeline' . $badge,
            'edit_posts',
            'gcp-queue',
            array($this, 'render_queue'),
            'dashicons-editor-insertmore',
            75
        );

        add_submenu_page(
            'gcp-queue',
            'Review Queue',
            'Review Queue' . $badge,
            'edit_posts',
            'gcp-queue',
            array($this, 'render_queue')
        );

        add_submenu_page(
            'gcp-queue',
            'New Draft',
            'New Draft',
            'edit_posts',
            'gcp-new-draft',
            array($this, 'render_new_draft')
        );

        add_submenu_page(
            'gcp-queue',
            'Pipeline Settings',
            'Settings',
            'manage_options',
            'gcp-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'gcp-') === false && strpos($hook, 'gcp_new') === false) {
            return;
        }
        wp_enqueue_style('gcp-admin', GCP_URL . 'assets/admin.css', array(), GCP_VERSION);
        wp_enqueue_script('gcp-admin', GCP_URL . 'assets/admin.js', array('jquery'), GCP_VERSION, true);
        wp_localize_script('gcp-admin', 'gcp', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gcp_ajax'),
        ));
    }

    public function register_settings() {
        register_setting('gcp_settings', 'gcp_default_category');
        register_setting('gcp_settings', 'gcp_default_author');
        register_setting('gcp_settings', 'gcp_approval_default', array('default' => 'draft'));
    }

    public function render_queue() {
        $queue = GCP_Queue::instance();
        $items = $queue->get_queue();
        $count = $queue->get_count();
        $generated_total = get_option('gcp_total_generated', 0);
        $approved_total = get_option('gcp_total_approved', 0);
        ?>
        <div class="wrap gcp-wrap">
            <h1>Content Pipeline — Review Queue</h1>
            <p style="color:#555;">Drafts awaiting your review. Nothing publishes without human approval.</p>

            <div class="gcp-cards">
                <div class="gcp-card"><strong><?php echo $count; ?></strong><span>Awaiting Review</span></div>
                <div class="gcp-card"><strong><?php echo $generated_total; ?></strong><span>Total Drafted</span></div>
                <div class="gcp-card"><strong><?php echo $approved_total; ?></strong><span>Approved</span></div>
                <div class="gcp-card"><a href="<?php echo admin_url('admin.php?page=gcp-new-draft'); ?>" class="button button-primary">+ New Draft</a></div>
            </div>

            <?php if (empty($items)): ?>
                <div class="notice notice-info inline" style="margin-top:16px;">
                    <p>No drafts awaiting review. <a href="<?php echo admin_url('admin.php?page=gcp-new-draft'); ?>">Create a new draft</a> to get started.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped gcp-queue-table" style="margin-top:16px;">
                    <thead>
                        <tr>
                            <th scope="col" style="width:40px;"><input type="checkbox" id="gcp-select-all"></th>
                            <th scope="col">Title / Topic</th>
                            <th scope="col" style="width:140px;">Generated</th>
                            <th scope="col" style="width:100px;">AI Used</th>
                            <th scope="col" style="width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $post): 
                            $topic = get_post_meta($post->ID, '_gcp_topic', true);
                            $ai_used = get_post_meta($post->ID, '_gcp_ai_used', true);
                            $approve_url = wp_nonce_url(admin_url('admin.php?action=gcp_approve&post=' . $post->ID), 'gcp_approve_' . $post->ID);
                            $publish_url = wp_nonce_url(admin_url('admin.php?action=gcp_approve_publish&post=' . $post->ID), 'gcp_approve_publish_' . $post->ID);
                            $reject_url = wp_nonce_url(admin_url('admin.php?action=gcp_reject&post=' . $post->ID), 'gcp_reject_' . $post->ID);
                            $regenerate_url = wp_nonce_url(admin_url('admin.php?action=gcp_regenerate&post=' . $post->ID), 'gcp_regenerate_' . $post->ID);
                        ?>
                        <tr>
                            <td><input type="checkbox" class="gcp-check" value="<?php echo $post->ID; ?>"></td>
                            <td>
                                <strong><?php echo esc_html($post->post_title); ?></strong>
                                <?php if ($topic): ?>
                                    <br><span style="color:#787c82;font-size:11px;">Topic: <?php echo esc_html($topic); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_the_date('Y-m-d H:i', $post->ID); ?></td>
                            <td><?php echo $ai_used ? '<span style="color:#2d8f5e;">AI</span>' : '<span style="color:#787c82;">Template</span>'; ?></td>
                            <td class="gcp-actions">
                                <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo $approve_url; ?>" class="button button-small button-primary" onclick="return confirm('Approve to Draft?')">Draft</a>
                                <a href="<?php echo $publish_url; ?>" class="button button-small" onclick="return confirm('Publish immediately?')">Publish</a>
                                <a href="<?php echo $regenerate_url; ?>" class="button button-small" onclick="return confirm('Regenerate content? This replaces the current draft.')">Regen</a>
                                <a href="<?php echo $reject_url; ?>" class="button button-small" style="color:#b32d2e;" onclick="return confirm('Reject and trash this draft?')">Reject</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="gcp-bulk-actions" style="margin-top:12px;">
                    <select id="gcp-bulk-action">
                        <option value="">Bulk Actions</option>
                        <option value="gcp_bulk_approve">Approve to Draft</option>
                        <option value="gcp_bulk_approve_publish">Approve & Publish</option>
                        <option value="gcp_bulk_reject">Reject (Trash)</option>
                    </select>
                    <button type="button" class="button" id="gcp-bulk-apply">Apply</button>
                </div>

                <form id="gcp-bulk-form" method="post" action="<?php echo admin_url('edit.php'); ?>" style="display:none;">
                    <input type="hidden" name="post_ids" id="gcp-bulk-ids" value="">
                    <input type="hidden" name="action" id="gcp-bulk-action-field" value="">
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_new_draft() {
        $data = GCP_Data::instance();
        $segments = $data->get_segments();
        $categories = get_categories(array('hide_empty' => false));
        $default_category = get_option('gcp_default_category', 0);
        $default_author = get_option('gcp_default_author', get_current_user_id());
        ?>
        <div class="wrap gcp-wrap">
            <h1>Content Pipeline — New Draft</h1>
            <p style="color:#555;">Enter a topic to generate a structured first draft. It will land in the Review Queue for your approval.</p>

            <div class="gcp-new-draft-form" style="background:#f6f7f7;padding:20px;border-radius:6px;max-width:700px;">
                <div class="gcp-form-row">
                    <label for="gcp-topic" style="font-weight:600;">Topic or Keyword:</label>
                    <input type="text" id="gcp-topic" class="regular-text" placeholder="e.g. Architect fee scale Ghana, Building permit Accra 2026, QS rate guide..." style="width:100%;max-width:500px;">
                </div>

                <div class="gcp-form-row" style="margin-top:12px;">
                    <label style="font-weight:600;display:block;margin-bottom:4px;">Use segment context (optional):</label>
                    <select id="gcp-segment" style="width:100%;max-width:500px;">
                        <option value="">— No specific segment —</option>
                        <?php foreach ($segments as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="gcp-form-row" style="margin-top:12px;">
                    <label for="gcp-category" style="font-weight:600;display:block;margin-bottom:4px;">Category:</label>
                    <select id="gcp-category" style="width:100%;max-width:500px;">
                        <option value="0">— No category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->term_id; ?>" <?php selected($default_category, $cat->term_id); ?>><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="gcp-form-row" style="margin-top:16px;">
                    <button type="button" class="button button-primary gcp-generate-btn" style="min-width:160px;padding:6px 20px;font-size:14px;">Generate Draft</button>
                    <span class="spinner" style="float:none;margin-left:8px;"></span>
                </div>

                <div id="gcp-result" style="margin-top:16px;"></div>
            </div>

            <div class="gcp-guide" style="margin-top:24px;max-width:700px;">
                <h3>Writing Guide</h3>
                <ul style="color:#555;">
                    <li><strong>Good topics</strong> are specific Ghana AEC questions: "How architect fees work in Ghana," "Building permit timeline for Accra," "Cost to build a 3-bedroom house in Ghana 2026"</li>
                    <li><strong>Weak topics</strong> are generic: "Architecture," "Construction," "Building tips" — the AI needs a specific angle to produce useful content</li>
                    <li>Selecting a <strong>market segment</strong> injects relevant rate data, professional body references, and pricing psychology into the prompt</li>
                    <li>Every draft goes to <strong>Needs Review</strong> — nothing auto-publishes. Review, edit, fact-check, then approve.</li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        $default_category = get_option('gcp_default_category', 0);
        $default_author = get_option('gcp_default_author', get_current_user_id());
        $approval_default = get_option('gcp_approval_default', 'draft');
        $categories = get_categories(array('hide_empty' => false));
        ?>
        <div class="wrap">
            <h1>Content Pipeline Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('gcp_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Category</th>
                        <td>
                            <select name="gcp_default_category">
                                <option value="0">— No category —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat->term_id; ?>" <?php selected($default_category, $cat->term_id); ?>><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Author</th>
                        <td>
                            <?php wp_dropdown_users(array(
                                'name' => 'gcp_default_author',
                                'selected' => $default_author,
                                'who' => 'authors',
                            )); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">On Approval, move to</th>
                        <td>
                            <select name="gcp_approval_default">
                                <option value="draft" <?php selected($approval_default, 'draft'); ?>>Draft (review further)</option>
                                <option value="publish" <?php selected($approval_default, 'publish'); ?>>Publish (goes live)</option>
                            </select>
                            <p class="description">This is the default action for the "Approve" button. You can always choose "Publish" for individual posts.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>

            <hr>
            <h2>AI Engine Status</h2>
            <?php
            $ai = class_exists('Gabochie_AI_Engine') ? Gabochie_AI_Engine::instance() : null;
            $has_ai = $ai && $ai->is_configured();
            if ($has_ai):
                echo '<div class="notice notice-success inline"><p>AI Engine is configured. Drafts will be generated using ' . esc_html($ai->get_config()['model']) . '.</p></div>';
            else:
                echo '<div class="notice notice-warning inline"><p>AI Engine not configured. Drafts will use template content. <a href="' . admin_url('options-general.php?page=gabochie-saas') . '">Configure API keys in Gabochie SaaS → AI Document Engine</a>.</p></div>';
            endif;
            ?>

            <hr>
            <h2>Ghana AEC Reference Data</h2>
            <p>The following data is injected into AI prompts to add authority and specificity. <a href="#" id="gcp-toggle-data">View/Edit reference data</a></p>
            <div id="gcp-data-editor" style="display:none;margin-top:12px;">
                <p style="color:#787c82;">Edit the JSON below to update rate data, professional body references, and regulatory information used in prompts.</p>
                <textarea id="gcp-data-json" rows="15" style="width:100%;max-width:700px;font-family:monospace;font-size:12px;"><?php echo esc_textarea(wp_json_encode(GCP_Data::instance()->get_all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></textarea>
                <p><button type="button" class="button" id="gcp-save-data">Save Reference Data</button> <span id="gcp-data-message" style="margin-left:8px;"></span></p>
            </div>
        </div>
        <?php
    }

    public function admin_notices() {
        if (isset($_GET['page']) && $_GET['page'] === 'gcp-queue') {
            if (isset($_GET['approved'])) {
                echo '<div class="notice notice-success is-dismissible"><p>Draft approved and moved to Draft status.</p></div>';
            }
            if (isset($_GET['published'])) {
                echo '<div class="notice notice-success is-dismissible"><p>Draft approved and published.</p></div>';
            }
            if (isset($_GET['rejected'])) {
                echo '<div class="notice notice-info is-dismissible"><p>Draft rejected and trashed.</p></div>';
            }
            if (isset($_GET['regenerated'])) {
                echo '<div class="notice notice-success is-dismissible"><p>Draft regenerated successfully.</p></div>';
            }
        }
        if (isset($_GET['gcp_bulk_count'])) {
            $count = intval($_GET['gcp_bulk_count']);
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf('Bulk action completed: %d posts processed.', $count) . '</p></div>';
        }
    }

    public function row_actions($actions, $post) {
        if ($post->post_status === 'needs-review') {
            $actions['gcp_edit'] = '<a href="' . get_edit_post_link($post->ID) . '">Edit</a>';
            $actions['gcp_approve'] = '<a href="' . wp_nonce_url(admin_url('admin.php?action=gcp_approve&post=' . $post->ID), 'gcp_approve_' . $post->ID) . '" style="color:#2d8f5e;">Approve</a>';
            $actions['gcp_publish'] = '<a href="' . wp_nonce_url(admin_url('admin.php?action=gcp_approve_publish&post=' . $post->ID), 'gcp_approve_publish_' . $post->ID) . '">Publish</a>';
            $actions['gcp_reject'] = '<a href="' . wp_nonce_url(admin_url('admin.php?action=gcp_reject&post=' . $post->ID), 'gcp_reject_' . $post->ID) . '" style="color:#b32d2e;">Reject</a>';
        }
        return $actions;
    }

    public function ajax_generate() {
        check_ajax_referer('gcp_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        $topic = sanitize_text_field($_POST['topic'] ?? '');
        if (empty($topic)) {
            wp_send_json_error(array('message' => 'Topic is required.'));
        }

        $segment = sanitize_key($_POST['segment'] ?? '');
        $category = intval($_POST['category'] ?? get_option('gcp_default_category', 0));

        if ($segment && isset(GCP_Data::get_segments()[$segment])) {
            $topic = $topic . ' (' . GCP_Data::get_segments()[$segment] . ')';
        }

        $generator = GCP_Generator::instance();
        $post_id = $generator->create_draft($topic, array(
            'author' => get_current_user_id(),
            'category' => $category,
        ));

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }

        $total = get_option('gcp_total_generated', 0);
        update_option('gcp_total_generated', $total + 1);

        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'queue_url' => admin_url('admin.php?page=gcp-queue'),
            'message' => sprintf(
                'Draft created! <a href="%s">Edit in post editor</a> or <a href="%s">return to Queue</a>.',
                get_edit_post_link($post_id, 'raw'),
                admin_url('admin.php?page=gcp-queue')
            ),
        ));
    }

    public function ajax_save_data() {
        check_ajax_referer('gcp_ajax');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        $data = isset($_POST['data']) ? json_decode(wp_unslash($_POST['data']), true) : null;
        if (!$data) {
            wp_send_json_error(array('message' => 'Invalid JSON.'));
        }
        update_option('gcp_ae_data', $data);
        wp_send_json_success(array('message' => 'Reference data saved.'));
    }

    public function ajax_get_topics() {
        check_ajax_referer('gcp_ajax');
        $queue = GCP_Queue::instance();
        wp_send_json_success(array(
            'queue_count' => $queue->get_count(),
        ));
    }
}

GCP_Admin::instance();
