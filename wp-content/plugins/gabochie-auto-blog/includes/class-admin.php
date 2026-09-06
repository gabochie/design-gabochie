<?php
defined('ABSPATH') || exit;

class Gabochie_Auto_Admin {
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
        add_action('wp_ajax_gab_schedule_reset', array($this, 'ajax_schedule_reset'));
        add_action('wp_ajax_gab_generate_now', array($this, 'ajax_generate_now'));
    }

    public function add_menu() {
        add_menu_page(
            'Gabochie Auto-Blog',
            'Auto-Blog',
            'manage_options',
            'gabochie-auto-blog',
            array($this, 'render_dashboard'),
            'dashicons-update',
            76
        );
        add_submenu_page(
            'gabochie-auto-blog',
            'Auto-Blog Dashboard',
            'Dashboard',
            'manage_options',
            'gabochie-auto-blog',
            array($this, 'render_dashboard')
        );
        add_submenu_page(
            'gabochie-auto-blog',
            'Auto-Blog Settings',
            'Settings',
            'manage_options',
            'gabochie-auto-blog-settings',
            array($this, 'render_settings')
        );
        add_submenu_page(
            'gabochie-auto-blog',
            'Auto-Blog Log',
            'Generation Log',
            'manage_options',
            'gabochie-auto-blog-log',
            array($this, 'render_log')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'gabochie-auto-blog') === false) return;
        wp_enqueue_style('gab-admin', GAB_URL . 'assets/admin.css', array(), GAB_VERSION);
        wp_enqueue_script('gab-admin', GAB_URL . 'assets/admin.js', array('jquery'), GAB_VERSION, true);
        wp_localize_script('gab-admin', 'gab', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gab_ajax'),
        ));
    }

    public function register_settings() {
        $settings = array(
            'gab_enabled', 'gab_frequency', 'gab_time', 'gab_category',
            'gab_author', 'gab_status', 'gab_topics',
            'gab_mode', 'gab_ai_provider', 'gab_ai_key', 'gab_ai_model', 'gab_ai_base_url',
        );
        foreach ($settings as $s) {
            register_setting('gab_settings', $s);
        }
    }

    public function render_dashboard() {
        $generator = Gabochie_Auto_Generator::instance();
        $topics = $generator->get_topic_list();
        $enabled = get_option('gab_enabled', '0');
        $frequency = get_option('gab_frequency', 'daily');
        $time = get_option('gab_time', '06:00');
        $last = get_option('gab_last_generated', 'Never');
        $last_bank = get_option('gab_last_bank_run', 'Never');
        $total = get_option('gab_total_generated', 0);
        $next = wp_next_scheduled('gab_generate_content');
        $next_display = $next ? wp_date('Y-m-d H:i:s', $next) : 'Not scheduled';
        $category = get_option('gab_category', 0);
        $cat_name = $category ? get_cat_name($category) : 'Uncategorized';
        $status = get_option('gab_status', 'draft');
        $mode = get_option('gab_mode', 'bank');
        $ai_ready = Gab_Auto_AI::instance()->is_configured();

        $bank = Gab_Auto_Bank::instance();
        $bank_ok = $bank->load();
        $bank_count = $bank_ok ? $bank->count() : 0;
        $next_date = $bank_ok ? $bank->next_date() : null;
        $upcoming = $bank_ok ? $bank->upcoming(6) : array();
        ?>
        <div class="wrap gab-dashboard">
            <h1>Gabochie Auto-Blog</h1>
            <p style="color:#555;">AI-powered automated content generation for Ghana AEC industry.</p>

            <div class="gab-cards">
                <div class="gab-card">
                    <div><strong><?php echo $enabled === '1' ? 'Active' : 'Paused'; ?></strong><span>Status</span></div>
                </div>
                <div class="gab-card">
                    <div><strong><?php echo ucfirst($mode); ?></strong><span>Mode</span></div>
                </div>
                <div class="gab-card">
                    <div><strong><?php echo $ai_ready ? 'Configured' : 'No AI'; ?></strong><span>AI Engine</span></div>
                </div>
                <div class="gab-card">
                    <div><strong><?php echo $bank_ok ? $bank_count : 'Unavailable'; ?></strong><span>Bank Posts</span></div>
                </div>
                <div class="gab-card">
                    <div><strong><?php echo esc_html($next_date ?: '—'); ?></strong><span>Next Bank Date</span></div>
                </div>
                <div class="gab-card">
                    <div><strong><?php echo esc_html($last_bank); ?></strong><span>Last Bank Run</span></div>
                </div>
            </div>

            <?php if ($mode === 'bank'): ?>
            <h2>Topic Bank — Upcoming Slots</h2>
            <?php if ($bank_ok && !empty($upcoming)): ?>
            <table class="wp-list-table widefat fixed striped" style="max-width:760px;">
                <thead><tr><th>Date</th><th>Slot</th><th>Title</th><th>Category</th><th>Hub</th></tr></thead>
                <tbody>
                <?php foreach ($upcoming as $bp):
                    $existing = get_posts(array('post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => Gab_Auto_Executor::META_BANK_ID, 'meta_value' => $bp['id']));
                    $state = $existing ? get_post_status($existing[0]) : '—';
                    $state_badge = 'publish' === $state ? '🏁 published' : ('draft' === $state ? 'draft' : $state);
                ?>
                <tr>
                    <td><?php echo esc_html($bp['date']); ?></td>
                    <td><?php echo esc_html($bp['slot']); ?></td>
                    <td><?php echo esc_html($bp['title']); ?></td>
                    <td><?php echo esc_html($bp['category']); ?></td>
                    <td><?php echo esc_html($state_badge); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>Bank not available (missing data/topic-bank.json?).</p>
            <?php endif; ?>
            <?php endif; ?>

            <div style="margin:20px 0;display:flex;gap:10px;flex-wrap:wrap;">
                <?php if ($mode === 'bank'): ?>
                <button type="button" class="button button-primary gab-bank-run" data-date="" data-force="">Run Due Slot Now</button>
                <button type="button" class="button gab-bank-run" data-date="<?php echo esc_attr($next_date ?: ''); ?>" data-force="draft">Pre-generate Next Slot (draft)</button>
                <button type="button" class="button gab-ai-test">Test AI Connection</button>
                <span id="gab-bank-message" style="display:inline-flex;align-items:center;margin-left:4px;"></span>
                <?php else: ?>
                <button type="button" class="button button-primary gab-generate-now">Generate Now (random topic)</button>
                <?php endif; ?>
                <button type="button" class="button gab-reset-schedule">Reset Schedule</button>
            </div>
            <div id="gab-generate-message" style="margin-top:10px;"></div>

            <h2>Manual Generation (topics mode)</h2>
            <div class="gab-manual-gen" style="background:#f6f7f7;padding:16px;border-radius:6px;max-width:600px;">
                <p><label for="gab-manual-topic" style="font-weight:600;">Topic:</label></p>
                <div style="display:flex;gap:8px;">
                    <select id="gab-manual-topic" style="flex:1;">
                        <option value="">— Pick a topic —</option>
                        <?php foreach ($topics as $t): ?>
                            <option value="<?php echo esc_attr($t); ?>"><?php echo esc_html($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="gab-manual-topic-custom" placeholder="Or type custom topic..." style="flex:1;">
                    <button type="button" class="button button-primary gab-generate-topic" data-status="<?php echo esc_attr($status); ?>" data-category="<?php echo esc_attr($category); ?>">Generate</button>
                </div>
                <p style="color:#787c82;font-size:12px;margin-top:6px;">Post will be created as "<?php echo esc_html($status); ?>" in "<?php echo esc_html($cat_name); ?>".</p>
            </div>
            <div id="gab-generate-message-2" style="margin-top:10px;"></div>
        </div>
        <?php
    }

    public function render_settings() {
        $enabled = get_option('gab_enabled', '0');
        $mode = get_option('gab_mode', 'bank');
        $frequency = get_option('gab_frequency', 'daily');
        $time = get_option('gab_time', '06:00');
        $category = get_option('gab_category', 0);
        $author = get_option('gab_author', 2);
        $status = get_option('gab_status', 'draft');
        $topics = get_option('gab_topics', "Ghana architecture trends\nSustainable building in Ghana");
        $ai_provider = get_option('gab_ai_provider', 'gemini');
        $ai_key = get_option('gab_ai_key', '');
        $ai_model = get_option('gab_ai_model', 'gemini-flash-latest');
        $ai_base = get_option('gab_ai_base_url', 'https://api.openai.com/v1');
        $bank_ok = Gab_Auto_Bank::instance()->load();
        ?>
        <div class="wrap">
            <h1>Auto-Blog Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('gab_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Auto-Blog</th>
                        <td>
                            <label>
                                <input type="checkbox" name="gab_enabled" value="1" <?php checked($enabled, '1'); ?>>
                                Automatically generate content on schedule
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Generation Mode</th>
                        <td>
                            <select name="gab_mode">
                                <option value="bank" <?php selected($mode, 'bank'); ?>>Topic Bank (editorial calendar, Tue/Thu/Sat)</option>
                                <option value="topics" <?php selected($mode, 'topics'); ?>>Random Topic List (legacy)</option>
                            </select>
                            <?php if (!$bank_ok): ?>
                            <p class="description" style="color:#d63638;">Bank mode is selected but data/topic-bank.json is missing or unreadable.</p>
                            <?php else: ?>
                            <p class="description">Bank mode publishes the post scheduled for each calendar date (T/TH/SA), held as draft until its due day.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Post Status</th>
                        <td>
                            <select name="gab_status">
                                <option value="draft" <?php selected($status, 'draft'); ?>>Draft (recommended for topics mode)</option>
                                <option value="publish" <?php selected($status, 'publish'); ?>>Publish Immediately</option>
                                <option value="pending" <?php selected($status, 'pending'); ?>>Pending Review</option>
                            </select>
                            <p class="description">In bank mode this is ignored: posts publish on their calendar date after the QA gate passes.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Frequency</th>
                        <td>
                            <select name="gab_frequency">
                                <option value="daily" <?php selected($frequency, 'daily'); ?>>Daily</option>
                                <option value="gab_twicedaily" <?php selected($frequency, 'gab_twicedaily'); ?>>Twice Daily</option>
                                <option value="gab_every_other_day" <?php selected($frequency, 'gab_every_other_day'); ?>>Every Other Day</option>
                                <option value="gab_weekly" <?php selected($frequency, 'gab_weekly'); ?>>Weekly</option>
                            </select>
                            <p class="description">Applies to the bank check-in too (bank cadence itself is always Tue/Thu/Sat).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Time of Day</th>
                        <td>
                            <input type="time" name="gab_time" value="<?php echo esc_attr($time); ?>">
                            <p class="description">Time of day to generate content (server timezone, Africa/Accra).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Target Category</th>
                        <td>
                            <?php wp_dropdown_categories(array(
                                'name' => 'gab_category',
                                'selected' => $category,
                                'show_option_none' => 'Uncategorized',
                                'option_none_value' => 0,
                                'hierarchical' => true,
                            )); ?>
                            <p class="description">Used by topics mode. Bank mode maps each post to its own category slug.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Author</th>
                        <td>
                            <?php wp_dropdown_users(array(
                                'name' => 'gab_author',
                                'selected' => $author,
                                'who' => 'authors',
                            )); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Topics (one per line)</th>
                        <td>
                            <textarea name="gab_topics" rows="10" style="width:100%;max-width:500px;font-family:monospace;"><?php echo esc_textarea($topics); ?></textarea>
                            <p class="description">Only used in topics mode (legacy). Bank mode ignores this list.</p>
                        </td>
                    </tr>
                </table>

                <h2 style="margin-top:24px;">AI Engine</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Provider</th>
                        <td>
                            <select name="gab_ai_provider">
                                <option value="gemini" <?php selected($ai_provider, 'gemini'); ?>>Google Gemini</option>
                                <option value="claude" <?php selected($ai_provider, 'claude'); ?>>Anthropic Claude</option>
                                <option value="openai" <?php selected($ai_provider, 'openai'); ?>>OpenAI / compatible</option>
                            </select>
                            <p class="description">Built-in caller. When no key is saved, the plugin falls back to GHBC or Gabochie AI Engine if either is configured.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="gab_ai_key" value="<?php echo esc_attr($ai_key); ?>" class="regular-text" style="max-width:420px;" autocomplete="off">
                            <p class="description">Stored as a wp_option, never logged. Gemini: key from Google AI Studio; Claude: sk-ant-...; OpenAI: sk-...</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <input type="text" name="gab_ai_model" value="<?php echo esc_attr($ai_model); ?>" class="regular-text" style="max-width:320px;">
                            <p class="description">Gemini: gemini-flash-latest / gemini-2.5-flash. Claude: claude-sonnet-4-20250514. OpenAI: gpt-4o.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Base URL (OpenAI-compatible)</th>
                        <td>
                            <input type="text" name="gab_ai_base_url" value="<?php echo esc_attr($ai_base); ?>" class="regular-text" style="max-width:420px;">
                            <p class="description">Only used for OpenAI-compatible endpoints (OpenRouter etc.). Example: https://openrouter.ai/api/v1</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Test Connection</th>
                        <td>
                            <button type="button" class="button gab-ai-test">Test saved key now</button>
                            <span id="gab-ai-test-result" style="margin-left:8px;"></span>
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
            $gab_ai_ok = Gab_Auto_AI::instance()->is_configured();
            if ($gab_ai_ok):
                echo '<div class="notice notice-success inline"><p>Auto-Blog has a usable AI backend (' . esc_html(Gab_Auto_AI::instance()->last_provider() ?: 'configured') . ').</p></div>';
            elseif ($has_ai):
                echo '<div class="notice notice-success inline"><p>Gabochie AI Engine is configured. Auto-blog will use it.</p></div>';
            else:
                echo '<div class="notice notice-warning inline"><p>No AI backend configured. Set the key above (or configure GHBC / Gabochie SaaS Tools AI). Without it the executor cannot generate posts.</p></div>';
            endif;
            ?>
        </div>
        <?php
    }

    public function render_log() {
        $last_gen = get_option('gab_last_generated', 'Never');
        $total = get_option('gab_total_generated', 0);
        $errors = get_option('gab_errors', array());
        echo '<div class="wrap">';
        echo '<h1>Generation Log</h1>';
        echo '<p>Last generation: <strong>' . esc_html($last_gen) . '</strong> | Total generated: <strong>' . intval($total) . '</strong></p>';
        echo '<h2>Recent Posts Generated</h2>';

        $auto_posts = get_posts(array(
            'post_type' => 'any',
            'posts_per_page' => 20,
            'meta_key' => '_gab_auto_generated',
            'meta_value' => '1',
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        if ($auto_posts) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Title</th><th>Topic</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
            foreach ($auto_posts as $p) {
                $topic = get_post_meta($p->ID, '_gab_topic', true);
                echo '<tr>';
                echo '<td>' . esc_html($p->post_title) . '</td>';
                echo '<td>' . esc_html($topic ?: '—') . '</td>';
                echo '<td>' . get_the_date('Y-m-d H:i', $p->ID) . '</td>';
                echo '<td>' . ucfirst($p->post_status) . '</td>';
                echo '<td><a href="' . get_edit_post_link($p->ID) . '" class="button button-small">Edit</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No auto-generated posts yet.</p>';
        }

        if (!empty($errors)) {
            echo '<h2 style="margin-top:20px;">Recent Errors</h2>';
            echo '<ul>';
            foreach (array_reverse($errors) as $e) {
                echo '<li>[' . esc_html($e['time']) . '] ' . esc_html($e['error']) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    public function ajax_schedule_reset() {
        check_ajax_referer('gab_ajax');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized.'));
        Gabochie_Auto_Scheduler::setup_schedule();
        wp_send_json_success(array('message' => 'Schedule reset.'));
    }

    public function ajax_generate_now() {
        check_ajax_referer('gab_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
        $generator = Gabochie_Auto_Generator::instance();
        $options = array(
            'category' => intval($_POST['category'] ?? get_option('gab_category', 0)),
            'author' => intval($_POST['author'] ?? get_option('gab_author', 1)),
            'status' => sanitize_text_field($_POST['status'] ?? get_option('gab_status', 'draft')),
        );
        $result = $generator->generate_post($topic, $options);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success(array(
            'post_id' => $result,
            'edit_url' => get_edit_post_link($result, 'raw'),
            'message' => sprintf('Post created: <a href="%s">Edit</a>', get_edit_post_link($result, 'raw')),
        ));
    }
}

Gabochie_Auto_Admin::instance();
