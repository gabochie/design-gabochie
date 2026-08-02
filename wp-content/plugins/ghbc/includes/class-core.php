<?php
defined('ABSPATH') || exit;

class GHBC_Core {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_meta']);
        add_action('admin_menu', [$this, 'register_menu_pages']);
    }

    public static function activate() {
        self::register_cpts();
        flush_rewrite_rules();
        self::create_tables();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function register_post_types() {
        self::register_cpts();
    }

    public static function register_cpts() {
        register_post_type('ghbc_chunk', [
            'labels' => [
                'name' => 'Code Chunks',
                'singular_name' => 'Code Chunk',
                'search_items' => 'Search Chunks',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'ghbc-dashboard',
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-book',
        ]);

        register_post_type('ghbc_report', [
            'labels' => [
                'name' => 'Compliance Reports',
                'singular_name' => 'Compliance Report',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'ghbc-dashboard',
            'supports' => ['title', 'editor'],
        ]);

        register_post_type('ghbc_session', [
            'labels' => [
                'name' => 'Chat Sessions',
                'singular_name' => 'Chat Session',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'ghbc-dashboard',
            'supports' => ['title', 'editor'],
        ]);
    }

    public function register_menu_pages() {
        add_menu_page(
            'Ghana Building Code Assistant',
            'GhBC',
            'manage_options',
            'ghbc-main',
            [$this, 'render_dashboard'],
            'dashicons-book'
        );

        add_submenu_page(
            'ghbc-main',
            'GhBC Command Center',
            'Dashboard',
            'manage_options',
            'ghbc-main',
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'ghbc-main',
            'GhBC Settings',
            'Settings',
            'manage_options',
            'ghbc-settings',
            [$this, 'render_settings']
        );
    }

    public function register_meta() {
        register_post_meta('ghbc_chunk', '_ghbc_part', [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
        ]);
        register_post_meta('ghbc_chunk', '_ghbc_section', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);
        register_post_meta('ghbc_chunk', '_ghbc_clause', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);
        register_post_meta('ghbc_chunk', '_ghbc_page_start', [
            'type' => 'integer',
            'single' => true,
        ]);
        register_post_meta('ghbc_chunk', '_ghbc_page_end', [
            'type' => 'integer',
            'single' => true,
        ]);
    }

    private static function create_tables() {
        global $wpdb;
        $table = $wpdb->prefix . 'ghbc_queries';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            query text NOT NULL,
            response longtext,
            tokens_used int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function render_dashboard() {
        $total = wp_count_posts('ghbc_chunk')->publish ?? 0;
        global $wpdb;
        $qtable = $wpdb->prefix . 'ghbc_queries';
        $today = $wpdb->get_var("SELECT COUNT(*) FROM $qtable WHERE DATE(created_at) = CURDATE()");
        $total_q = $wpdb->get_var("SELECT COUNT(*) FROM $qtable");
        $provider = get_option('ghbc_llm_provider', 'none');
        $fallbacks = (array) get_option('ghbc_llm_fallbacks', []);
        $llm_ok = GHBC_LLM::instance()->is_ready();
        ?>
        <div class="wrap">
            <h1>GhBC Command Center</h1>

            <div style="display:flex;gap:14px;margin:16px 0;flex-wrap:wrap;">
                <div style="padding:16px 20px;background:#fff;border:1px solid #ddd;border-radius:8px;flex:1;min-width:140px;text-align:center;">
                    <div style="font-size:26px;font-weight:700;"><?php echo (int)$total; ?></div>
                    <div style="color:#666;font-size:12px;">Code Chunks</div>
                </div>
                <div style="padding:16px 20px;background:#fff;border:1px solid #ddd;border-radius:8px;flex:1;min-width:140px;text-align:center;">
                    <div style="font-size:26px;font-weight:700;"><?php echo (int)$today; ?></div>
                    <div style="color:#666;font-size:12px;">Queries Today</div>
                </div>
                <div style="padding:16px 20px;background:#fff;border:1px solid #ddd;border-radius:8px;flex:1;min-width:140px;text-align:center;">
                    <div style="font-size:26px;font-weight:700;"><?php echo (int)$total_q; ?></div>
                    <div style="color:#666;font-size:12px;">Total Queries</div>
                </div>
                <div style="padding:16px 20px;background:#fff;border:1px solid #ddd;border-radius:8px;flex:1;min-width:140px;text-align:center;">
                    <div style="font-size:26px;font-weight:700;color:<?php echo $llm_ok ? '#2e7d32' : '#c62828'; ?>;"><?php echo $llm_ok ? '&#10003;' : '&#10060;'; ?></div>
                    <div style="color:#666;font-size:12px;"><?php echo $llm_ok ? ucfirst($provider) . (count($fallbacks) ? ' + ' . count($fallbacks) . ' fallback' . (count($fallbacks) === 1 ? '' : 's') : '') : 'LLM Disconnected'; ?></div>
                </div>
            </div>

            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px 20px;margin:16px 0;">
                <h2 style="margin:0 0 12px;font-size:16px;">&#128736; Tool Inventory</h2>
                <table class="wp-list-table widefat fixed striped" style="border:0;">
                    <thead><tr><th>Tool</th><th>Status</th><th>Frontend</th><th>Page</th></tr></thead>
                    <tbody>
                        <?php $tools = [
                            ['AI Code Chat', 'LIVE', 'shortcode', '/ghbc-compliance/'],
                            ['Fire Safety Design', 'LIVE', 'shortcode', '/fire-safety-design-assistant/'],
                            ['Exam Preparation', 'LIVE', 'shortcode', '/ghbc-exam/'],
                            ['Small Building Check', 'LIVE', 'shortcode', '/ghbc-small-building-check/'],
                            ['Travel Distance Calc', 'LIVE', 'shortcode', '/travel-distance-calculator/'],
                            ['Exit Width Calc', 'LIVE', 'shortcode', '/exit-width-calculator/'],
                            ['Fire Rating Calc', 'LIVE', 'shortcode', '/fire-rating-calculator/'],
                            ['Sprinkler Check', 'LIVE', 'shortcode', '/sprinkler-check/'],
                            ['Code Search', 'LIVE', 'shortcode', '/code-search/'],
                            ['Clause Lookup', 'LIVE', 'shortcode', '/clause-lookup/'],
                            ['Tools Hub', 'LIVE', 'shortcode', '/ghbc-tools/'],
                        ];
                        foreach ($tools as $t) { ?>
                            <tr>
                                <td><strong><?php echo $t[0]; ?></strong></td>
                                <td><span style="color:<?php echo $t[1] === 'LIVE' ? '#2e7d32' : '#e65100'; ?>;"><?php echo $t[1]; ?></span></td>
                                <td><?php echo $t[2]; ?></td>
                                <td><?php echo $t[3] !== '—' ? '<a href="' . $t[3] . '" target="_blank">' . $t[3] . '</a>' : '—'; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div style="display:flex;gap:14px;flex-wrap:wrap;">
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px 20px;flex:2;min-width:300px;">
                    <h2 style="margin:0 0 10px;font-size:15px;">&#9000; Quick Actions</h2>
                    <p><a href="edit.php?post_type=ghbc_chunk" class="button">Browse Code Chunks</a>
                    <a href="admin.php?page=ghbc-settings" class="button">LLM Settings</a>
                    <a href="post-new.php?post_type=ghbc_chunk" class="button">Add Code Chunk</a></p>
                    <p><a href="edit.php?post_type=ghbc_report" class="button">Compliance Reports</a>
                    <a href="edit.php?post_type=ghbc_session" class="button">Chat Sessions</a></p>
                </div>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px 20px;flex:1;min-width:200px;">
                    <h2 style="margin:0 0 10px;font-size:15px;">&#128196; Shortcodes</h2>
                    <code style="display:block;margin-bottom:4px;">[ghbc_chat]</code>
                    <code style="display:block;margin-bottom:4px;">[ghbc_exam]</code>
                    <code style="display:block;margin-bottom:4px;">[ghbc_small_building]</code>
                    <code style="display:block;margin-bottom:4px;">[ghbc_fire_safety]</code>
                    <code style="display:block;margin-bottom:4px;">[ghbc_tools]</code>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        if ($_POST && check_admin_referer('ghbc_settings')) {
            update_option('ghbc_llm_provider', sanitize_text_field($_POST['llm_provider'] ?? 'claude'));
            update_option('ghbc_llm_key', sanitize_text_field($_POST['llm_key'] ?? ''));
            update_option('ghbc_llm_model', sanitize_text_field($_POST['llm_model'] ?? 'claude-sonnet-4-20250514'));
            update_option('ghbc_llm_base_url', sanitize_text_field($_POST['llm_base_url'] ?? 'http://localhost:11434'));
            update_option('ghbc_max_queries_free', (int)($_POST['max_queries_free'] ?? 20));

            $fallbacks = [];
            $fp = isset($_POST['fb_provider']) ? (array) $_POST['fb_provider'] : [];
            $fk = isset($_POST['fb_key']) ? (array) $_POST['fb_key'] : [];
            $fm = isset($_POST['fb_model']) ? (array) $_POST['fb_model'] : [];
            $fbu = isset($_POST['fb_base_url']) ? (array) $_POST['fb_base_url'] : [];
            foreach ($fp as $i => $p) {
                $p = sanitize_text_field($p);
                if (empty($p)) continue;
                $fallbacks[] = [
                    'provider' => $p,
                    'key'      => sanitize_text_field($fk[$i] ?? ''),
                    'model'    => sanitize_text_field($fm[$i] ?? ''),
                    'base_url' => sanitize_text_field($fbu[$i] ?? 'http://localhost:11434'),
                ];
            }
            update_option('ghbc_llm_fallbacks', $fallbacks);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $provider = get_option('ghbc_llm_provider', 'claude');
        $key = get_option('ghbc_llm_key', '');
        $model = get_option('ghbc_llm_model', 'claude-sonnet-4-20250514');
        $base_url = get_option('ghbc_llm_base_url', '');
        $free = get_option('ghbc_max_queries_free', 20);
        $fallbacks = (array) get_option('ghbc_llm_fallbacks', []);
        $providers = [
            'claude'    => 'Claude (Anthropic)',
            'openai'    => 'OpenAI',
            'openrouter'=> 'OpenRouter',
            'gemini'    => 'Gemini (Google)',
            'mistral'   => 'Mistral AI',
            'grok'      => 'Grok (xAI)',
            'ollama'    => 'Ollama (Local)',
        ];
        ?>
        <div class="wrap">
            <h1>GhBC Settings</h1>
            <p class="description">The <strong>primary provider</strong> is tried first. If it fails (invalid key, outage, empty response, local endpoint down), GhBC automatically falls back to the <strong>fallback providers</strong> in the order listed below. OpenRouter routes many models through one key (e.g. <code>openai/gpt-4o</code>, <code>anthropic/claude-3.7-sonnet</code>).</p>
            <form method="post">
                <?php wp_nonce_field('ghbc_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>LLM Provider</th>
                        <td>
                            <select name="llm_provider" id="llm-provider">
                                <?php foreach ($providers as $val => $label) { ?>
                                    <option value="<?php echo esc_attr($val); ?>" <?php selected($provider, $val); ?>><?php echo esc_html($label); ?></option>
                                <?php } ?>
                            </select>
                            <p class="description" id="local-hint" style="<?php echo $provider === 'ollama' ? '' : 'display:none;'; ?>color:#666;margin-top:4px;">&#128161; Connects to a local Ollama API. No API key required.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>API Key</th>
                        <td><input type="password" name="llm_key" value="<?php echo esc_attr($key); ?>" class="regular-text" id="llm-key">
                            <p class="description" id="local-key-hint" style="<?php echo $provider === 'ollama' ? '' : 'display:none;'; ?>color:#999;margin-top:4px;">Not needed for local endpoints. Leave blank.</p>
                        </td>
                    </tr>
                    <tr id="base-url-row" style="<?php echo $provider === 'ollama' ? '' : 'display:none;'; ?>">
                        <th>Local Base URL</th>
                        <td><input type="text" name="llm_base_url" value="<?php echo esc_attr($base_url ?: 'http://localhost:11434'); ?>" class="regular-text" id="llm-base-url" placeholder="http://localhost:11434">
                            <p class="description" style="color:#666;margin-top:4px;">Ollama default: <code>http://localhost:11434</code>. If Ollama runs on the same host as the site, leave as-is.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Model</th>
                        <td><input type="text" name="llm_model" value="<?php echo esc_attr($model); ?>" class="regular-text" id="llm-model" placeholder="claude-sonnet-4-20250514">
                            <p class="description" style="color:#666;margin-top:4px;">Cloud defaults fill in automatically. For Ollama, use the model name running locally (e.g., <code>llama3.2</code>, <code>qwen2.5</code>, <code>mistral</code>). For OpenRouter use <code>vendor/model</code> (e.g., <code>openai/gpt-4o</code>).</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Free tier queries/mo</th>
                        <td><input type="number" name="max_queries_free" value="<?php echo (int)$free; ?>" min="0" max="1000"></td>
                    </tr>
                    <tr>
                        <th>Connection Test</th>
                        <td>
                            <button type="button" class="button" id="ghbc-test-llm">Test Connection</button>
                            <span id="ghbc-test-result" style="margin-left:8px;"></span>
                            <p class="description" style="color:#666;margin-top:4px;">Tests the primary provider above (does not touch fallbacks).</p>
                        </td>
                    </tr>
                </table>

                <h2>Fallback Providers <button type="button" class="button" id="ghbc-fb-add" style="margin-left:8px;">+ Add fallback</button></h2>
                <p class="description" style="max-width:900px;">Listed in priority order — each is tried only if all earlier providers fail. An Ollama row needs a model name but no key.</p>
                <div id="ghbc-fallbacks">
                    <?php foreach ($fallbacks as $fb) { echo self::fallback_row_html($fb, $providers); } ?>
                </div>
                <p class="submit"><button type="submit" class="button button-primary">Save</button></p>
            </form>
        </div>
        <script>
        jQuery(function($) {
            var $provider = $('#llm-provider'), $baseUrl = $('#llm-base-url'), $model = $('#llm-model');
            var modelDefaults = {
                claude:    'claude-sonnet-4-20250514',
                openai:    'gpt-4o',
                openrouter:'openai/gpt-4o',
                gemini:    'gemini-2.5-pro',
                mistral:   'mistral-large-latest',
                grok:      'grok-3',
                ollama:    'llama3.2'
            };
            var providerOptions = <?php echo wp_json_encode($providers); ?>;
            function isLocal(p) { return p === 'ollama'; }
            function toggleLocal() {
                var v = $provider.val();
                $('#local-hint, #local-key-hint').toggle(isLocal(v));
                $('#base-url-row').toggle(isLocal(v));
                if (isLocal(v) && (!$baseUrl.val() || $baseUrl.val() === 'http://localhost:1234')) {
                    $baseUrl.val('http://localhost:11434');
                }
            }
            function presetModel() {
                var v = $provider.val();
                var current = $model.val().trim();
                var prevDefault = modelDefaults[$provider.data('last')] || '';
                if (modelDefaults[v] && (!current || current === prevDefault)) {
                    $model.val(modelDefaults[v]);
                }
                $provider.data('last', v);
            }
            $provider.on('change', function() { toggleLocal(); presetModel(); });
            $provider.data('last', $provider.val());
            toggleLocal();
            presetModel();

            function fbModelPreset(row) {
                var $sel = row.find('.fb-provider'), $m = row.find('.fb-model');
                var v = $sel.val();
                var current = $m.val().trim();
                var prevDefault = modelDefaults[$sel.data('last')] || '';
                if (modelDefaults[v] && (!current || current === prevDefault)) {
                    $m.val(modelDefaults[v]);
                }
                $sel.data('last', v);
            }
            function fbToggle(row) {
                var v = row.find('.fb-provider').val();
                row.find('.fb-base-wrap').toggle(isLocal(v));
                row.find('.fb-key-hint').toggle(isLocal(v));
                var $bu = row.find('.fb-base-url');
                if (isLocal(v) && (!$bu.val() || $bu.val().indexOf('localhost') === 0)) {
                    $bu.val('http://localhost:11434');
                }
            }
            function buildRow(p) {
                var opts = '';
                $.each(providerOptions, function(val, label) {
                    opts += '<option value="' + val + '"' + (val === p ? ' selected' : '') + '>' + label + '</option>';
                });
                return $('<div class="fb-row" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:8px;background:#fff;border:1px solid #ddd;border-radius:6px;padding:10px 12px;max-width:900px;">'
                    + '<div><label class="description" style="display:block;margin-bottom:2px;">Provider</label><select name="fb_provider[]" class="fb-provider">' + opts + '</select></div>'
                    + '<div><label class="description" style="display:block;margin-bottom:2px;">API Key</label><input type="password" name="fb_key[]" class="regular-text fb-key"><p class="description fb-key-hint" style="margin:2px 0 0;color:#999;display:none;">Not needed for local.</p></div>'
                    + '<div><label class="description" style="display:block;margin-bottom:2px;">Model</label><input type="text" name="fb_model[]" class="regular-text fb-model" placeholder="claude-sonnet-4-20250514"></div>'
                    + '<div class="fb-base-wrap" style="display:none;"><label class="description" style="display:block;margin-bottom:2px;">Base URL</label><input type="text" name="fb_base_url[]" class="regular-text fb-base-url" value="http://localhost:11434"></div>'
                    + '<button type="button" class="button fb-remove" style="flex-shrink:0;">Remove</button>'
                    + '</div>');
            }
            function bindRow($row) {
                $row.find('.fb-provider').on('change', function() { fbToggle($row); fbModelPreset($row); });
                $row.find('.fb-remove').on('click', function() { $row.remove(); });
                $row.find('.fb-provider').data('last', $row.find('.fb-provider').val());
                fbToggle($row);
                fbModelPreset($row);
            }
            $('#ghbc-fallbacks > .fb-row').each(function() { bindRow($(this)); });
            $('#ghbc-fb-add').on('click', function() {
                var $row = buildRow('openrouter');
                $('#ghbc-fallbacks').append($row);
                bindRow($row);
            });

            $('#ghbc-test-llm').on('click', function() {
                var btn = $(this), result = $('#ghbc-test-result');
                btn.prop('disabled', true);
                result.html('Testing...').css('color', '#666');

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'ghbc_test_connection',
                    nonce: '<?php echo wp_create_nonce('ghbc_test_nonce'); ?>',
                    provider: $provider.val(),
                    model: $('#llm-model').val(),
                    key: $('#llm-key').val(),
                    base_url: $baseUrl.val() || 'http://localhost:11434'
                }, function(res) {
                    if (res.success) {
                        result.html('&#10003; ' + res.data.message).css('color', '#2e7d32');
                    } else {
                        result.html('&#10060; ' + res.data.message).css('color', '#c62828');
                    }
                }).fail(function() {
                    result.html('&#10060; Request failed').css('color', '#c62828');
                }).always(function() {
                    btn.prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }

    private static function fallback_row_html($fb, $providers) {
        $p = $fb['provider'] ?? 'openrouter';
        $is_ollama = $p === 'ollama';
        $opts = '';
        foreach ($providers as $val => $label) {
            $opts .= '<option value="' . esc_attr($val) . '"' . selected($p, $val, false) . '>' . esc_html($label) . '</option>';
        }
        return '<div class="fb-row" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:8px;background:#fff;border:1px solid #ddd;border-radius:6px;padding:10px 12px;max-width:900px;">'
             . '<div><label class="description" style="display:block;margin-bottom:2px;">Provider</label><select name="fb_provider[]" class="fb-provider">' . $opts . '</select></div>'
             . '<div><label class="description" style="display:block;margin-bottom:2px;">API Key</label><input type="password" name="fb_key[]" class="regular-text fb-key" value="' . esc_attr($fb['key'] ?? '') . '"><p class="description fb-key-hint" style="margin:2px 0 0;color:#999;' . ($is_ollama ? '' : 'display:none;') . '">Not needed for local.</p></div>'
             . '<div><label class="description" style="display:block;margin-bottom:2px;">Model</label><input type="text" name="fb_model[]" class="regular-text fb-model" value="' . esc_attr($fb['model'] ?? '') . '" placeholder="openai/gpt-4o"></div>'
             . '<div class="fb-base-wrap" style="' . ($is_ollama ? '' : 'display:none;') . '"><label class="description" style="display:block;margin-bottom:2px;">Base URL</label><input type="text" name="fb_base_url[]" class="regular-text fb-base-url" value="' . esc_attr($fb['base_url'] ?? 'http://localhost:11434') . '"></div>'
             . '<button type="button" class="button fb-remove" style="flex-shrink:0;">Remove</button>'
             . '</div>';
    }
}
