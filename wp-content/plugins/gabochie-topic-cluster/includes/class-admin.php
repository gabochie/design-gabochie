<?php
defined('ABSPATH') || exit;

class GTC_Admin {
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
        add_action('wp_ajax_gtc_get_suggestions', array($this, 'ajax_get_suggestions'));
        add_filter('post_row_actions', array($this, 'row_actions'), 10, 2);
        add_filter('page_row_actions', array($this, 'row_actions'), 10, 2);

        GTC_Audit::instance();
        GTC_Linker::instance();
        GTC_Segments::instance();
    }

    public function add_menu() {
        add_menu_page(
            'Topic Clusters',
            'Topic Clusters',
            'edit_posts',
            'gtc-clusters',
            array($this, 'render_dashboard'),
            'dashicons-networking',
            73
        );
        add_submenu_page(
            'gtc-clusters',
            'Cluster Map',
            'Cluster Map',
            'edit_posts',
            'gtc-clusters',
            array($this, 'render_dashboard')
        );
        add_submenu_page(
            'gtc-clusters',
            'Orphaned Pages',
            'Orphaned Pages',
            'edit_posts',
            'gtc-orphans',
            array($this, 'render_orphans')
        );
        add_submenu_page(
            'gtc-clusters',
            'Link Suggestions',
            'Link Suggestions',
            'edit_posts',
            'gtc-links',
            array($this, 'render_links')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'gtc-') === false) return;
        wp_enqueue_style('gtc-admin', GTC_URL . 'assets/admin.css', array(), GTC_VERSION);
        wp_enqueue_script('gtc-admin', GTC_URL . 'assets/admin.js', array('jquery'), GTC_VERSION, true);
        wp_localize_script('gtc-admin', 'gtc', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gtc_ajax'),
        ));
    }

    public function render_dashboard() {
        $segments = GTC_Segments::get_all();
        $segment_counts = GTC_Segments::instance()->get_segment_counts();
        $audit = GTC_Audit::instance();
        $report = $audit->full_report();
        $orphans = $audit->find_orphaned();
        ?>
        <div class="wrap gtc-wrap">
            <h1>Topic Cluster Map</h1>
            <p style="color:#555;">Topical-authority silos based on 35 AEC/property/tiling/ecosystem segments. Assign pages to segments via the meta box in the post editor.</p>

            <div class="gtc-cards">
                <div class="gtc-card"><strong><?php echo count($segments); ?></strong><span>Segments</span></div>
                <div class="gtc-card"><strong><?php echo array_sum($segment_counts); ?></strong><span>Pages Assigned</span></div>
                <div class="gtc-card"><strong style="color:#b32d2e;"><?php echo count($orphans); ?></strong><span>Orphaned Pages</span></div>
            </div>

            <?php foreach ($segments as $portfolio_key => $portfolio): ?>
                <h2 style="margin-top:28px;"><?php echo esc_html($portfolio['label']); ?></h2>
                <div class="gtc-segment-grid">
                    <?php foreach ($portfolio['segments'] as $key => $label):
                        $count = isset($segment_counts[$key]) ? $segment_counts[$key] : 0;
                        $sr = isset($report[$key]) ? $report[$key] : null;
                        $orphan_badge = $sr && $sr['orphan_count'] > 0 ? ' <span style="color:#b32d2e;font-size:11px;">(' . $sr['orphan_count'] . ' orphan)</span>' : '';
                    ?>
                    <div class="gtc-segment-card">
                        <h3><?php echo esc_html($label); ?> <span class="gtc-count"><?php echo $count; ?></span></h3>
                        <?php if ($sr): ?>
                            <div class="gtc-stats">
                                <span>Avg links: <?php echo $sr['avg_internal_links']; ?></span>
                                <span><?php echo $sr['posts_with_internal_links']; ?>/<?php echo $sr['total_posts']; ?> with links</span>
                                <?php echo $orphan_badge; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($count > 0): ?>
                            <button type="button" class="button button-small gtc-view-posts" data-segment="<?php echo esc_attr($key); ?>">View Posts</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public function render_orphans() {
        $audit = GTC_Audit::instance();
        $orphan_ids = $audit->find_orphaned();
        ?>
        <div class="wrap gtc-wrap">
            <h1>Orphaned Pages</h1>
            <p style="color:#555;">Pages with zero incoming internal links. These are invisible to both users and search engines from within your own site.</p>

            <?php if (empty($orphan_ids)): ?>
                <div class="notice notice-success inline"><p>No orphaned pages found. Every page has at least one incoming internal link.</p></div>
            <?php else: ?>
                <p><strong style="color:#b32d2e;"><?php echo count($orphan_ids); ?> orphaned page(s)</strong> — add internal links pointing to these pages.</p>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Segment</th>
                            <th>Outgoing Links</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orphan_ids as $pid):
                            $stats = $audit->get_link_stats($pid);
                        ?>
                        <tr>
                            <td><a href="<?php echo $stats['edit_url']; ?>"><?php echo esc_html($stats['title']); ?></a></td>
                            <td><?php echo ucfirst($stats['type']); ?></td>
                            <td><?php echo esc_html($stats['segment']); ?></td>
                            <td><?php echo $stats['outgoing_internal']; ?> internal / <?php echo $stats['outgoing_external']; ?> external</td>
                            <td>
                                <a href="<?php echo $stats['edit_url']; ?>" class="button button-small">Edit</a>
                                <a href="<?php echo admin_url('admin.php?page=gtc-links&post_id=' . $pid); ?>" class="button button-small">Find Link Sources</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_links() {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        $linker = GTC_Linker::instance();
        ?>
        <div class="wrap gtc-wrap">
            <h1>Link Suggestions</h1>
            <p style="color:#555;">AI-powered internal link suggestions. Review before applying — no links are auto-inserted.</p>

            <div class="gtc-link-picker" style="margin:16px 0;">
                <label for="gtc-post-picker" style="font-weight:600;">Select a page:</label>
                <select id="gtc-post-picker" style="min-width:300px;">
                    <option value="">— Select —</option>
                    <?php
                    $all = get_posts(array(
                        'post_type' => array('post', 'page'),
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'orderby' => 'title',
                        'order' => 'ASC',
                    ));
                    foreach ($all as $p):
                        $segment = get_post_meta($p->ID, '_gtc_segment', true);
                        $label = $segment ? ' [' . GTC_Segments::get_segment_label($segment) . ']' : ' [Unassigned]';
                    ?>
                    <option value="<?php echo $p->ID; ?>" <?php selected($post_id, $p->ID); ?>><?php echo esc_html($p->post_title . $label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-primary" id="gtc-get-suggestions">Get Suggestions</button>
                <span class="spinner" style="float:none;margin-left:8px;"></span>
            </div>

            <div id="gtc-suggestions-results">
                <?php if ($post_id): ?>
                    <p>Loading suggestions for selected page...</p>
                <?php else: ?>
                    <p style="color:#787c82;">Select a page and click "Get Suggestions" to see internal link recommendations.</p>
                <?php endif; ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            function loadSuggestions(postId) {
                var $results = $('#gtc-suggestions-results');
                $results.html('<p><span class="spinner is-active"></span> Analyzing content and finding candidates...</p>');

                $.post(gtc.ajaxurl, {
                    action: 'gtc_suggest_links',
                    post_id: postId,
                    _ajax_nonce: gtc.nonce
                }, function(res) {
                    if (!res.success) {
                        $results.html('<p style="color:#d63638;">' + res.data.message + '</p>');
                        return;
                    }

                    var html = '';
                    html += '<h3>Within-Segment Suggestions</h3>';
                    if (res.data.within_segment.length === 0) {
                        html += '<p style="color:#787c82;">No relevant pages found in the same segment. Assign this page to a segment first.</p>';
                    } else {
                        html += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Title</th><th>Relevance</th><th>Context</th><th>Action</th></tr></thead><tbody>';
                        $.each(res.data.within_segment, function(i, link) {
                            html += '<tr>';
                            html += '<td><a href="' + link.url + '" target="_blank">' + link.title + '</a></td>';
                            html += '<td>' + link.relevance + '</td>';
                            html += '<td><code>' + (link.context || '—') + '</code></td>';
                            html += '<td><button type="button" class="button button-small gtc-apply-link" data-source="' + postId + '" data-target="' + link.post_id + '" data-anchor="' + link.title.replace(/'/g, "\\'") + '">Apply</button></td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table>';
                    }

                    html += '<h3 style="margin-top:20px;">Cross-Segment Suggestions</h3>';
                    if (res.data.cross_segment.length === 0) {
                        html += '<p style="color:#787c82;">No cross-segment link candidates found.</p>';
                    } else {
                        html += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Title</th><th>Relevance</th><th>Action</th></tr></thead><tbody>';
                        $.each(res.data.cross_segment, function(i, link) {
                            html += '<tr>';
                            html += '<td><a href="' + link.url + '" target="_blank">' + link.title + ' <span style="color:#787c82;font-size:11px;">(cross-segment)</span></a></td>';
                            html += '<td>' + link.relevance + '</td>';
                            html += '<td><button type="button" class="button button-small gtc-apply-link" data-source="' + postId + '" data-target="' + link.post_id + '" data-anchor="' + link.title.replace(/'/g, "\\'") + '">Apply</button></td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table>';
                    }

                    $results.html(html);
                }).fail(function() {
                    $results.html('<p style="color:#d63638;">Connection error.</p>');
                });
            }

            $('#gtc-get-suggestions').on('click', function() {
                var postId = $('#gtc-post-picker').val();
                if (!postId) {
                    alert('Select a page first.');
                    return;
                }
                loadSuggestions(postId);
            });

            $('#gtc-post-picker').on('change', function() {
                var postId = $(this).val();
                if (postId) {
                    loadSuggestions(postId);
                }
            });

            $(document).on('click', '.gtc-apply-link', function() {
                var $btn = $(this);
                var source = $btn.data('source');
                var target = $btn.data('target');
                var anchor = $btn.data('anchor');

                if (!confirm('Apply link to "' + anchor + '" from the current page?')) return;

                $btn.prop('disabled', true).text('Applying...');

                $.post(gtc.ajaxurl, {
                    action: 'gtc_apply_link',
                    source_id: source,
                    target_id: target,
                    anchor: anchor,
                    _ajax_nonce: gtc.nonce
                }, function(res) {
                    $btn.prop('disabled', false).text('Applied');
                    if (res.success) {
                        $btn.text('Done ✓').css('color', '#2d8f5e');
                    } else {
                        $btn.text('Failed').css('color', '#d63638');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('Error').css('color', '#d63638');
                });
            });

            <?php if ($post_id): ?>
            $(function() { loadSuggestions(<?php echo $post_id; ?>); });
            <?php endif; ?>
        });
        </script>
        <?php
    }

    public function row_actions($actions, $post) {
        $segment = get_post_meta($post->ID, '_gtc_segment', true);
        if ($segment) {
            $actions['gtc_segment'] = '<span style="color:#787c82;font-size:11px;">' . esc_html($segment) . '</span>';
        }
        $actions['gtc_links'] = '<a href="' . admin_url('admin.php?page=gtc-links&post_id=' . $post->ID) . '">Find Links</a>';
        return $actions;
    }

    public function ajax_get_suggestions() {
        check_ajax_referer('gtc_ajax');
        $post_id = intval($_GET['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(array('message' => 'No post ID.'));
        }
        $linker = GTC_Linker::instance();
        wp_send_json_success(array(
            'within' => $linker->suggest_links($post_id),
            'cross' => $linker->suggest_cross_segment_links($post_id),
        ));
    }
}

GTC_Admin::instance();
