<?php
defined('ABSPATH') || exit;

class Gabochie_SEO_Meta {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('wp_head', array($this, 'output_meta_tags'), 1);
        add_action('wp_ajax_gseo_ai_suggest', array($this, 'ajax_ai_suggest'));
        add_action('wp_ajax_gseo_ai_analyze', array($this, 'ajax_ai_analyze'));
    }

    public function add_meta_box() {
        $post_types = get_post_types(array('public' => true), 'names');
        add_meta_box(
            'gseo_meta_box',
            'Gabochie SEO',
            array($this, 'render_meta_box'),
            $post_types,
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('gseo_meta', 'gseo_meta_nonce');
        $focus_keyword = get_post_meta($post->ID, '_gseo_focus_keyword', true);
        $title = get_post_meta($post->ID, '_gseo_title', true);
        $description = get_post_meta($post->ID, '_gseo_description', true);
        $og_image = get_post_meta($post->ID, '_gseo_og_image', true);
        $noindex = get_post_meta($post->ID, '_gseo_noindex', true);
        ?>
        <div class="gseo-meta-box">
            <style>
                .gseo-meta-box { padding: 12px 0; }
                .gseo-meta-box .gseo-row { margin-bottom: 16px; }
                .gseo-meta-box label { display: block; font-weight: 600; margin-bottom: 4px; color: #1d2327; }
                .gseo-meta-box input[type=text],
                .gseo-meta-box textarea { width: 100%; max-width: 600px; }
                .gseo-meta-box textarea { min-height: 80px; }
                .gseo-meta-box .gseo-hint { font-size: 12px; color: #787c82; margin-top: 3px; }
                .gseo-meta-box .gseo-ai-btn { margin-left: 8px; font-size: 12px; }
                .gseo-meta-box .gseo-preview { background: #f0f6fc; border-left: 4px solid #72aee6; padding: 10px 14px; margin-top: 8px; border-radius: 0 4px 4px 0; font-size: 13px; }
                .gseo-meta-box .gseo-preview .gseo-preview-title { color: #1a0dab; font-size: 14px; line-height: 1.3; }
                .gseo-meta-box .gseo-preview .gseo-preview-url { color: #006621; font-size: 12px; line-height: 1.5; }
                .gseo-meta-box .gseo-preview .gseo-preview-desc { color: #545454; font-size: 13px; line-height: 1.4; }
                .gseo-meta-box .gseo-analysis { margin-top: 16px; padding: 12px; background: #f6f7f7; border-radius: 4px; }
                .gseo-meta-box .gseo-analysis h4 { margin: 0 0 8px; font-size: 13px; }
                .gseo-meta-box .gseo-analysis .gseo-score { display: inline-block; padding: 2px 10px; border-radius: 12px; font-weight: 600; font-size: 12px; }
                .gseo-meta-box .gseo-analysis .gseo-score.good { background: #edfaef; color: #2d8f5e; }
                .gseo-meta-box .gseo-analysis .gseo-score.ok { background: #fcf9e8; color: #996800; }
                .gseo-meta-box .gseo-analysis .gseo-score.bad { background: #fcf0f1; color: #d63638; }
                .gseo-meta-box .gseo-analysis ul { margin: 8px 0 0; padding-left: 16px; }
                .gseo-meta-box .gseo-analysis li { font-size: 12px; margin-bottom: 3px; color: #555; }
            </style>

            <div class="gseo-row">
                <label for="gseo-focus-keyword">Focus Keyphrase</label>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <input type="text" id="gseo-focus-keyword" name="gseo_focus_keyword" value="<?php echo esc_attr($focus_keyword); ?>" placeholder="e.g. build house in Ghana" style="flex:1;min-width:200px;max-width:400px;">
                    <button type="button" class="button button-small gseo-suggest-btn" data-post-id="<?php echo $post->ID; ?>">AI Suggest</button>
                </div>
                <div class="gseo-hint">The primary keyphrase you want this page to rank for. AI will suggest Ghana AEC-relevant keywords.</div>
            </div>

            <div class="gseo-row">
                <label for="gseo-title">SEO Title</label>
                <input type="text" id="gseo-title" name="gseo_title" value="<?php echo esc_attr($title); ?>" placeholder="<?php echo esc_attr($post->post_title); ?>">
                <div class="gseo-hint">Leave empty to use the post title. Recommended: 50-60 characters. <button type="button" class="button button-small gseo-ai-btn" data-target="title" data-post-id="<?php echo $post->ID; ?>">AI Generate</button></div>
            </div>

            <div class="gseo-row">
                <label for="gseo-description">Meta Description</label>
                <textarea id="gseo-description" name="gseo_description" placeholder="Write a compelling description..."><?php echo esc_textarea($description); ?></textarea>
                <div class="gseo-hint">Recommended: 150-160 characters. Appears in search results. <button type="button" class="button button-small gseo-ai-btn" data-target="description" data-post-id="<?php echo $post->ID; ?>">AI Generate</button></div>
            </div>

            <div class="gseo-row">
                <label for="gseo-og-image">OG Image URL</label>
                <input type="text" id="gseo-og-image" name="gseo_og_image" value="<?php echo esc_attr($og_image); ?>" placeholder="https://...">
                <button type="button" class="button button-small gseo-og-media" style="margin-top:4px;">Select from Media</button>
                <div class="gseo-hint">Custom image for social shares. Recommended: 1200x630px.</div>
            </div>

            <div class="gseo-row">
                <label>
                    <input type="checkbox" name="gseo_noindex" value="1" <?php checked($noindex, '1'); ?>>
                    Hide from search results (noindex)
                </label>
            </div>

            <div class="gseo-preview" id="gseo-preview">
                <div class="gseo-preview-title"><?php echo esc_html($title ?: $post->post_title); ?> - Gabochie Design</div>
                <div class="gseo-preview-url"><?php echo esc_url(home_url('/' . $post->post_name . '/')); ?></div>
                <div class="gseo-preview-desc"><?php echo esc_html($description ?: wp_trim_words(strip_tags($post->post_content), 25)); ?></div>
            </div>

            <div class="gseo-analysis" id="gseo-analysis">
                <h4>AI Content Analysis <button type="button" class="button button-small gseo-analyze-btn" data-post-id="<?php echo $post->ID; ?>">Run Analysis</button></h4>
                <div id="gseo-analysis-results"><p style="color:#787c82;font-size:12px;">Click "Run Analysis" to get AI-powered SEO recommendations for this content.</p></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#gseo-title, #gseo-description').on('input', function() { updatePreview(); });
            function updatePreview() {
                var title = $('#gseo-title').val() || $('#gseo-title').attr('placeholder');
                var desc = $('#gseo-description').val() || $('#gseo-preview .gseo-preview-desc').text();
                $('#gseo-preview .gseo-preview-title').text(title + ' - Gabochie Design');
                $('#gseo-preview .gseo-preview-desc').text(desc);
            }

            $('.gseo-ai-btn').on('click', function() {
                var $btn = $(this);
                var target = $btn.data('target');
                var postId = $btn.data('post-id');
                var content = '<?php echo esc_js(wp_trim_words(strip_tags(get_post($post->ID)->post_content), 100)); ?>';
                $btn.prop('disabled', true).text('Generating...');
                $.post(ajaxurl, {
                    action: 'gseo_ai_suggest',
                    type: target,
                    post_id: postId,
                    content: content,
                    _ajax_nonce: '<?php echo wp_create_nonce('gseo_ajax'); ?>'
                }, function(res) {
                    $btn.prop('disabled', false).text('AI Generate');
                    if (res.success && res.data.text) {
                        $('#' + (target === 'title' ? 'gseo-title' : 'gseo-description')).val(res.data.text);
                        updatePreview();
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('AI Generate');
                });
            });

            $('.gseo-suggest-btn').on('click', function() {
                var $btn = $(this);
                var title = '<?php echo esc_js($post->post_title); ?>';
                $btn.prop('disabled', true).text('Suggesting...');
                $.post(ajaxurl, {
                    action: 'gseo_ai_suggest',
                    type: 'keyphrase',
                    title: title,
                    _ajax_nonce: '<?php echo wp_create_nonce('gseo_ajax'); ?>'
                }, function(res) {
                    $btn.prop('disabled', false).text('AI Suggest');
                    if (res.success && res.data.text) {
                        $('#gseo-focus-keyword').val(res.data.text);
                    }
                }).fail(function() { $btn.prop('disabled', false).text('AI Suggest'); });
            });

            $('.gseo-analyze-btn').on('click', function() {
                var $btn = $(this);
                var postId = $btn.data('post-id');
                $btn.prop('disabled', true).text('Analyzing...');
                $.post(ajaxurl, {
                    action: 'gseo_ai_analyze',
                    post_id: postId,
                    _ajax_nonce: '<?php echo wp_create_nonce('gseo_ajax'); ?>'
                }, function(res) {
                    $btn.prop('disabled', false).text('Run Analysis');
                    $('#gseo-analysis-results').html(res.data.html || '<p>Analysis complete.</p>');
                }).fail(function() {
                    $btn.prop('disabled', false).text('Run Analysis');
                    $('#gseo-analysis-results').html('<p style="color:#d63638;">Analysis failed. Configure AI API key in Settings → Gabochie SaaS.</p>');
                });
            });

            $('.gseo-og-media').on('click', function(e) {
                e.preventDefault();
                if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
                    wp.media.editor.send.attachment = function(props, attachment) {
                        $('#gseo-og-image').val(attachment.url);
                    };
                    wp.media.editor.open();
                }
            });
        });
        </script>
        <?php
    }

    public function save_meta($post_id) {
        if (!isset($_POST['gseo_meta_nonce']) || !wp_verify_nonce($_POST['gseo_meta_nonce'], 'gseo_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array('gseo_focus_keyword', 'gseo_title', 'gseo_description', 'gseo_og_image');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        update_post_meta($post_id, '_gseo_noindex', isset($_POST['gseo_noindex']) ? '1' : '');
    }

    public function output_meta_tags() {
        if (is_singular()) {
            global $post;
            $title = get_post_meta($post->ID, '_gseo_title', true) ?: $post->post_title;
            $description = get_post_meta($post->ID, '_gseo_description', true) ?: wp_trim_words(strip_tags($post->post_content), 25);
            $og_image = get_post_meta($post->ID, '_gseo_og_image', true) ?: get_the_post_thumbnail_url($post->ID, 'full');
            $noindex = get_post_meta($post->ID, '_gseo_noindex', true);

            echo "\n<!-- Gabochie SEO -->\n";
            echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
            echo '<meta property="og:title" content="' . esc_attr($title) . ' - ' . get_bloginfo('name') . '" />' . "\n";
            echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
            echo '<meta property="og:type" content="' . (is_single() ? 'article' : 'website') . '" />' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
            if ($og_image) {
                echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
                echo '<meta property="og:image:width" content="1200" />' . "\n";
                echo '<meta property="og:image:height" content="630" />' . "\n";
            }
            echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
            if ($og_image) {
                echo '<meta name="twitter:image" content="' . esc_url($og_image) . '" />' . "\n";
            }
            if ($noindex) {
                echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
            }
            echo "<!-- / Gabochie SEO -->\n";
        }
    }

    public function ajax_ai_suggest() {
        check_ajax_referer('gseo_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        $type = sanitize_text_field($_POST['type'] ?? '');
        $ai = class_exists('Gabochie_AI_Engine') ? Gabochie_AI_Engine::instance() : null;
        if ($ai && $ai->is_configured()) {
            $ai->refresh_config();
            if ($type === 'title') {
                $content = sanitize_textarea_field($_POST['content'] ?? '');
                $text = $ai->generate(
                    "Based on this content, suggest an SEO title (max 60 chars, include keyphrase naturally):\n\n$content",
                    'You are an SEO expert specializing in Ghana AEC industry.',
                    0.5
                );
                wp_send_json_success(array('text' => trim($text)));
            } elseif ($type === 'description') {
                $content = sanitize_textarea_field($_POST['content'] ?? '');
                $text = $ai->generate(
                    "Based on this content, write a meta description (max 160 chars, compelling, include keyphrase):\n\n$content",
                    'You are an SEO copywriter specializing in Ghana architecture and construction.',
                    0.5
                );
                wp_send_json_success(array('text' => trim($text)));
            } elseif ($type === 'keyphrase') {
                $title = sanitize_text_field($_POST['title'] ?? '');
                $text = $ai->generate(
                    "For a page titled '$title' on a Ghana AEC website, suggest ONE focus keyphrase (5 words max, specific to Ghana building industry). Return only the keyphrase, nothing else.",
                    'You are a Ghana AEC SEO specialist.',
                    0.3
                );
                wp_send_json_success(array('text' => trim($text)));
            }
        }
        if ($type === 'keyphrase') {
            $suggestions = array(
                'building a house in Ghana',
                'Ghana architecture firm',
                'diaspora building guide Ghana',
                'architect in Ghana cost',
                'house construction Ghana',
            );
            wp_send_json_success(array('text' => $suggestions[array_rand($suggestions)]));
        }
        wp_send_json_success(array('text' => 'AI-powered suggestion (configure API key for intelligent suggestions)'));
    }

    public function ajax_ai_analyze() {
        check_ajax_referer('gseo_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('message' => 'Post not found.'));
        }

        $content = strip_tags($post->post_content);
        $word_count = str_word_count($content);
        $keyword = get_post_meta($post_id, '_gseo_focus_keyword', true);
        $keyword_count = $keyword ? substr_count(strtolower($content), strtolower($keyword)) : 0;
        $content_length = strlen($content);
        $heading_count = preg_match_all('/<h[1-6][^>]*>/i', $post->post_content);

        $score = 0;
        $suggestions = array();

        if ($word_count >= 300) { $score += 20; } else { $suggestions[] = 'Add more content (aim for 300+ words)'; }
        if ($word_count >= 1000) { $score += 10; }
        if ($keyword_count >= 3) { $score += 20; } else { $suggestions[] = 'Use focus keyphrase at least 3 times in content'; }
        if ($keyword_count >= 1 && $keyword_count < 3) { $score += 10; }
        if ($heading_count >= 3) { $score += 20; } else { $suggestions[] = 'Add more headings (H2/H3) to structure content'; }
        if (has_post_thumbnail($post_id)) { $score += 10; } else { $suggestions[] = 'Add a featured image'; }
        if ($content_length >= 500) { $score += 10; }
        if (!empty(get_post_meta($post_id, '_gseo_description', true))) { $score += 10; } else { $suggestions[] = 'Add a meta description'; }
        if (!empty(get_post_meta($post_id, '_gseo_focus_keyword', true))) { $score += 10; } else { $suggestions[] = 'Set a focus keyphrase'; }

        $grade = $score >= 80 ? 'good' : ($score >= 50 ? 'ok' : 'bad');
        $grade_label = $score >= 80 ? 'Good' : ($score >= 50 ? 'Needs improvement' : 'Needs work');
        $grade_icon = $score >= 80 ? '✓' : ($score >= 50 ? '!' : '✗');

        $ai_suggestions = '';
        $ai = class_exists('Gabochie_AI_Engine') ? Gabochie_AI_Engine::instance() : null;
        if ($ai && $ai->is_configured() && !empty($keyword)) {
            $ai->refresh_config();
            $ai_text = $ai->generate(
                "For a Ghana AEC article titled '{$post->post_title}' with focus keyphrase '{$keyword}', give 3 specific SEO improvement tips. One sentence each.",
                'You are a Ghana AEC SEO specialist.',
                0.5
            );
            if ($ai_text) {
                $ai_suggestions = '<li>' . str_replace(array("\n", "\r"), '</li><li>', trim($ai_text)) . '</li>';
            }
        }

        $html = '<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <span class="gseo-score ' . $grade . '">' . $grade_icon . ' ' . $grade_label . ' (' . $score . '/100)</span>
            <span style="font-size:12px;color:#787c82;">' . $word_count . ' words, ' . $keyword_count . ' keyphrase uses, ' . $heading_count . ' headings</span>
        </div>';

        if (!empty($suggestions) || !empty($ai_suggestions)) {
            $html .= '<ul>';
            foreach ($suggestions as $s) {
                $html .= '<li>' . esc_html($s) . '</li>';
            }
            if ($ai_suggestions) {
                $html .= $ai_suggestions;
            }
            $html .= '</ul>';
        }

        wp_send_json_success(array('html' => $html));
    }
}

Gabochie_SEO_Meta::instance();
