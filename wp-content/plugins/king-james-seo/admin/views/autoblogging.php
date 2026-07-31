<div class="wrap kjs-autoblog">
    <h1><?php esc_html_e('Autoblogging Command Center', 'king-james-seo'); ?></h1>
    <p class="description"><?php esc_html_e('AI-powered content generation and rewriting command center. Scan, queue, generate, and publish — all driven by GSCC audit data.', 'king-james-seo'); ?></p>

    <?php if (!$ai_configured): ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php echo wp_kses_post(__('<strong>AI provider not configured.</strong> Go to <a href="' . admin_url('admin.php?page=king-james-seo-settings') . '">Settings</a> to add an API key (OpenAI, Anthropic, or Gemini).', 'king-james-seo')); ?></p>
        </div>
    <?php endif; ?>

    <div class="kjs-stats-grid">
        <div class="kjs-stat-card">
            <span class="kjs-stat-number"><?php echo esc_html($stats['total_posts']); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Published Posts', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number"><?php echo esc_html($stats['gscc_total']); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Posts Audited (GSCC)', 'king-james-seo'); ?></span>
            <small><?php echo esc_html("Avg score: {$stats['gscc_avg']}"); ?></small>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number" style="color:#d63638;"><?php echo esc_html($stats['gscc_critical']); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Critical (< 40)', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number" style="color:#dba617;"><?php echo esc_html($stats['gscc_warning']); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Warning (40-69)', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number"><?php echo esc_html($stats['rewrite_queue']); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Rewrite Queue', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number" style="color:#008a20;"><?php echo esc_html($stats['rewrite_done']); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Rewrites Completed', 'king-james-seo'); ?></span>
        </div>
    </div>

    <div class="kjs-autoblog-toolbar">
        <button type="button" class="button button-primary" id="kjs-autoblog-scan">
            <span class="dashicons dashicons-search"></span> <?php esc_html_e('Run Full GSCC Scan', 'king-james-seo'); ?>
        </button>
        <button type="button" class="button" id="kjs-autoblog-bulk-queue">
            <?php esc_html_e('Queue Worst 10 for Rewrite', 'king-james-seo'); ?>
        </button>
        <button type="button" class="button" id="kjs-autoblog-rewrite-now">
            <?php esc_html_e('Process Queue Now', 'king-james-seo'); ?>
        </button>
        <span class="kjs-autoblog-spinner" style="display:none;"><?php esc_html_e('Working...', 'king-james-seo'); ?></span>
    </div>

    <div id="kjs-autoblog-results" style="display:none;" class="notice"></div>

    <div class="kjs-publishing-panel">
        <div class="kjs-publishing-header">
            <h2>Publishing Mode &amp; Humanization</h2>
            <span class="kjs-autonomy-badge <?php echo $publishing_mode === 'auto' ? 'autonomy-l4' : 'autonomy-l2'; ?>">
                <?php echo $publishing_mode === 'auto' ? 'AUTONOMOUS' : 'SEMI-AUTONOMOUS'; ?>
            </span>
        </div>
        <div class="kjs-publishing-body">
            <div style="display:flex;align-items:flex-start;gap:24px;flex-wrap:wrap;">
                <div>
                    <div class="kjs-mode-toggle">
                        <button type="button" class="kjs-mode-btn <?php echo $publishing_mode === 'semi' ? 'active' : ''; ?>" data-mode="semi">
                            Semi-Autonomous
                        </button>
                        <button type="button" class="kjs-mode-btn <?php echo $publishing_mode === 'auto' ? 'active' : ''; ?>" data-mode="auto">
                            Autonomous
                        </button>
                    </div>
                    <div class="kjs-mode-desc" id="kjs-mode-desc">
                        <?php if ($publishing_mode === 'auto'): ?>
                            <strong>Autonomous:</strong> Content is generated and <strong>published immediately</strong> with randomized timing and word counts. Skips weekends and enforces daily limits.
                        <?php else: ?>
                            <strong>Semi-Autonomous:</strong> Content is generated as <strong>drafts</strong> for your review. You control what gets published and when.
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="kjs-humanization-grid" id="kjs-humanization-grid">
                <div class="kjs-hum-group-wrap">
                    <div class="kjs-hum-group">
                        <label for="kjs-hum-publish-hour">Base publish time</label>
                        <select id="kjs-hum-publish-hour">
                            <?php for ($h = 6; $h <= 22; $h++): ?>
                                <option value="<?php echo $h; ?>" <?php selected($humanization['publish_time_hour'], $h); ?>><?php echo sprintf('%02d:00', $h); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="kjs-hum-group">
                        <label>
                            <input type="checkbox" id="kjs-hum-randomize-time" <?php checked($humanization['randomize_time']); ?>>
                            Randomize &plusmn;
                        </label>
                        <select id="kjs-hum-time-variance">
                            <?php for ($v = 1; $v <= 6; $v++): ?>
                                <option value="<?php echo $v; ?>" <?php selected($humanization['time_variance'], $v); ?>><?php echo $v; ?>h</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="kjs-hum-group">
                        <label for="kjs-hum-max-per-day">Max posts/day</label>
                        <input type="number" id="kjs-hum-max-per-day" min="1" max="10" value="<?php echo esc_attr($humanization['max_per_day']); ?>">
                    </div>
                </div>

                <div class="kjs-hum-group-wrap">
                    <div class="kjs-hum-group">
                        <label>
                            <input type="checkbox" id="kjs-hum-randomize-words" <?php checked($humanization['randomize_words']); ?>>
                            Word count range
                        </label>
                        <input type="number" id="kjs-hum-min-words" min="300" max="5000" value="<?php echo esc_attr($humanization['min_words']); ?>" style="width:70px;">
                        &ndash;
                        <input type="number" id="kjs-hum-max-words" min="500" max="10000" value="<?php echo esc_attr($humanization['max_words']); ?>" style="width:70px;">
                    </div>
                    <div class="kjs-hum-group">
                        <label>
                            <input type="checkbox" id="kjs-hum-skip-weekends" <?php checked($humanization['skip_weekends']); ?>>
                            Skip weekends
                        </label>
                    </div>
                    <div class="kjs-hum-group">
                        <label for="kjs-hum-skip-days">Skip days/week</label>
                        <select id="kjs-hum-skip-days">
                            <?php for ($d = 0; $d <= 3; $d++): ?>
                                <option value="<?php echo $d; ?>" <?php selected($humanization['skip_days'], $d); ?>><?php echo $d; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="kjs-publishing-save">
                <button type="button" class="button button-primary" id="kjs-save-publishing-settings">
                    Save Publishing Settings
                </button>
                <span id="kjs-settings-saved" style="display:none;margin-left:12px;color:#008a20;font-weight:600;">Settings saved!</span>
            </div>
        </div>
    </div>

    <div class="kjs-autoblog-panels">
        <div class="kjs-autoblog-panel">
            <h2><?php esc_html_e('Rewrite Queue', 'king-james-seo'); ?></h2>
            <?php if (!empty($queue)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Post', 'king-james-seo'); ?></th>
                            <th><?php esc_html_e('Priority', 'king-james-seo'); ?></th>
                            <th><?php esc_html_e('Status', 'king-james-seo'); ?></th>
                            <th><?php esc_html_e('Actions', 'king-james-seo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queue as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($item->post_title ?: "(post {$item->post_id})"); ?></strong>
                                    <?php if ($item->post_id): ?>
                                        <div class="row-actions">
                                            <span class="edit"><a href="<?php echo esc_url(get_edit_post_link($item->post_id)); ?>"><?php esc_html_e('Edit', 'king-james-seo'); ?></a></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($item->priority); ?></td>
                                <td>
                                    <span class="kjs-status-<?php echo esc_attr($item->status); ?>"><?php echo esc_html(ucfirst($item->status)); ?></span>
                                </td>
                                <td>
                                    <?php if ($item->status === 'completed' && $item->result_content): ?>
                                        <button type="button" class="button button-small kjs-autoblog-apply" data-queue-id="<?php echo esc_attr($item->id); ?>">
                                            <?php esc_html_e('Apply', 'king-james-seo'); ?>
                                        </button>
                                        <button type="button" class="button button-small kjs-autoblog-apply-publish" data-queue-id="<?php echo esc_attr($item->id); ?>">
                                            <?php esc_html_e('Apply & Publish', 'king-james-seo'); ?>
                                        </button>
                                    <?php elseif ($item->status === 'queued'): ?>
                                        <span class="kjs-autoblog-pending"><?php esc_html_e('Awaiting processing...', 'king-james-seo'); ?></span>
                                    <?php elseif ($item->status === 'failed'): ?>
                                        <span class="kjs-autoblog-error"><?php echo esc_html($item->result_log ?: 'Failed'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php esc_html_e('No items in rewrite queue. Run a GSCC scan and queue thin posts for rewriting.', 'king-james-seo'); ?></p>
            <?php endif; ?>
        </div>

        <div class="kjs-autoblog-panel">
            <h2><?php esc_html_e('AI Content Generator', 'king-james-seo'); ?></h2>
            <form id="kjs-autoblog-generate-form">
                <table class="form-table">
                    <tr>
                        <th><label for="kjs-ai-topic"><?php esc_html_e('Topic', 'king-james-seo'); ?></label></th>
                        <td><input type="text" id="kjs-ai-topic" name="topic" class="regular-text" placeholder="e.g. How to Choose a Contractor in Ghana"></td>
                    </tr>
                    <tr>
                        <th><label for="kjs-ai-keywords"><?php esc_html_e('Keywords (comma-separated)', 'king-james-seo'); ?></label></th>
                        <td><input type="text" id="kjs-ai-keywords" name="keywords" class="regular-text" placeholder="contractor Ghana, building contractor, hire contractor"></td>
                    </tr>
                    <tr>
                        <th><label for="kjs-ai-instructions"><?php esc_html_e('Instructions', 'king-james-seo'); ?></label></th>
                        <td><textarea id="kjs-ai-instructions" name="instructions" class="large-text" rows="3" placeholder="Write a comprehensive guide..."></textarea></td>
                    </tr>
                </table>
                <p>
                    <button type="submit" class="button button-primary" id="kjs-ai-generate-btn">
                        <?php esc_html_e('Generate Content', 'king-james-seo'); ?>
                    </button>
                </p>
            </form>
            <div id="kjs-ai-output" style="display:none;">
                <h3><?php esc_html_e('Generated Content', 'king-james-seo'); ?></h3>
                <div id="kjs-ai-preview" class="kjs-ai-preview"></div>
                <p>
                    <button type="button" class="button" id="kjs-ai-save-draft"><?php esc_html_e('Save as Draft', 'king-james-seo'); ?></button>
                </p>
            </div>
        </div>
    </div>

    <div class="kjs-autoblog-panel" style="margin-top:20px;">
        <h2><?php esc_html_e('Single Post Rewriter', 'king-james-seo'); ?></h2>
        <p><?php esc_html_e('Select a post and rewrite it with AI. Useful for fixing individual thin or low-scoring posts.', 'king-james-seo'); ?></p>
        <form id="kjs-autoblog-rewrite-form">
            <table class="form-table">
                <tr>
                    <th><label for="kjs-rewrite-post"><?php esc_html_e('Post', 'king-james-seo'); ?></label></th>
                    <td>
                        <select id="kjs-rewrite-post" name="post_id" class="regular-text">
                            <option value=""><?php esc_html_e('Select a post...', 'king-james-seo'); ?></option>
                            <?php
                            $posts = get_posts(['numberposts' => 200, 'post_status' => 'any', 'post_type' => 'post']);
                            foreach ($posts as $p):
                            ?>
                                <option value="<?php echo esc_attr($p->ID); ?>"><?php echo esc_html($p->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="kjs-rewrite-instructions"><?php esc_html_e('Instructions', 'king-james-seo'); ?></label></th>
                    <td><textarea id="kjs-rewrite-instructions" name="instructions" class="large-text" rows="3" placeholder="Expand this to 1200+ words, add practical examples...">Expand this content to 1200+ words. Improve readability and add practical details.</textarea></td>
                </tr>
            </table>
            <p>
                <button type="submit" class="button button-primary" id="kjs-rewrite-btn">
                    <?php esc_html_e('Rewrite', 'king-james-seo'); ?>
                </button>
            </p>
        </form>
        <div id="kjs-rewrite-output" style="display:none;">
            <h3><?php esc_html_e('Rewritten Content', 'king-james-seo'); ?></h3>
            <div id="kjs-rewrite-preview" class="kjs-ai-preview"></div>
            <p>
                <button type="button" class="button" id="kjs-rewrite-apply"><?php esc_html_e('Apply to Post', 'king-james-seo'); ?></button>
            </p>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    var currentGeneratedContent = '';
    var currentRewrittenContent = '';
    var currentRewritePostId = 0;

    // Publishing mode toggle
    $('.kjs-mode-btn').on('click', function() {
        var mode = $(this).data('mode');
        $('.kjs-mode-btn').removeClass('active');
        $(this).addClass('active');

        if (mode === 'auto') {
            $('#kjs-mode-desc').html('<strong>Autonomous:</strong> Content is generated and <strong>published immediately</strong> with randomized timing and word counts. Skips weekends and enforces daily limits.');
            $('.kjs-autonomy-badge').removeClass('autonomy-l2').addClass('autonomy-l4').text('AUTONOMOUS');
        } else {
            $('#kjs-mode-desc').html('<strong>Semi-Autonomous:</strong> Content is generated as <strong>drafts</strong> for your review. You control what gets published and when.');
            $('.kjs-autonomy-badge').removeClass('autonomy-l4').addClass('autonomy-l2').text('SEMI-AUTONOMOUS');
        }
    });

    // Save publishing settings
    $('#kjs-save-publishing-settings').on('click', function() {
        var btn = $(this).prop('disabled', true);
        $('#kjs-settings-saved').hide();

        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_autoblog_save_settings',
            publishing_mode: $('.kjs-mode-btn.active').data('mode'),
            randomize_time: $('#kjs-hum-randomize-time').is(':checked') ? 1 : 0,
            time_variance: $('#kjs-hum-time-variance').val(),
            randomize_words: $('#kjs-hum-randomize-words').is(':checked') ? 1 : 0,
            min_words: $('#kjs-hum-min-words').val(),
            max_words: $('#kjs-hum-max-words').val(),
            skip_weekends: $('#kjs-hum-skip-weekends').is(':checked') ? 1 : 0,
            skip_days: $('#kjs-hum-skip-days').val(),
            max_per_day: $('#kjs-hum-max-per-day').val(),
            publish_time_hour: $('#kjs-hum-publish-hour').val(),
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success) {
                $('#kjs-settings-saved').fadeIn().delay(2000).fadeOut();
            } else {
                alert('Save failed: ' + (response.data.message || 'Unknown error'));
            }
        }).always(function() {
            btn.prop('disabled', false);
        });
    });

    $('#kjs-autoblog-scan').on('click', function() {
        var btn = $(this).prop('disabled', true);
        $('.kjs-autoblog-spinner').show();
        $('#kjs-autoblog-results').hide();

        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_autoblog_run_scan',
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success) {
                $('#kjs-autoblog-results').removeClass('notice-error').addClass('notice-success')
                    .html('<p>Scan complete: ' + response.data.total + ' posts audited. Avg score: ' + response.data.avg + '. Critical: ' + response.data.critical + ', Warning: ' + response.data.warning + '.</p>').show();
            } else {
                $('#kjs-autoblog-results').removeClass('notice-success').addClass('notice-error')
                    .html('<p>Scan failed: ' + (response.data.message || 'Unknown error') + '</p>').show();
            }
        }).always(function() {
            btn.prop('disabled', false);
            $('.kjs-autoblog-spinner').hide();
        });
    });

    $('#kjs-autoblog-bulk-queue').on('click', function() {
        var btn = $(this).prop('disabled', true);
        $('.kjs-autoblog-spinner').show();

        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_autoblog_bulk_queue',
            threshold: 70,
            limit: 10,
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success) {
                $('#kjs-autoblog-results').removeClass('notice-error').addClass('notice-success')
                    .html('<p>' + response.data.message + '</p>').show();
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                $('#kjs-autoblog-results').removeClass('notice-success').addClass('notice-error')
                    .html('<p>' + (response.data.message || 'Failed') + '</p>').show();
            }
        }).always(function() {
            btn.prop('disabled', false);
            $('.kjs-autoblog-spinner').hide();
        });
    });

    $('#kjs-autoblog-rewrite-now').on('click', function() {
        var btn = $(this).prop('disabled', true);
        $('.kjs-autoblog-spinner').show();

        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_ai_rewrite_thin_posts',
            limit: 5,
            threshold: 70,
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success) {
                $('#kjs-autoblog-results').removeClass('notice-error').addClass('notice-success')
                    .html('<p>' + response.data.message + '</p>').show();
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                $('#kjs-autoblog-results').removeClass('notice-success').addClass('notice-error')
                    .html('<p>' + (response.data.message || 'Failed') + '</p>').show();
            }
        }).always(function() {
            btn.prop('disabled', false);
            $('.kjs-autoblog-spinner').hide();
        });
    });

    $('#kjs-autoblog-generate-form').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#kjs-ai-generate-btn').prop('disabled', true).text('Generating...');

        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_ai_generate',
            topic: $('#kjs-ai-topic').val(),
            keywords: $('#kjs-ai-keywords').val().split(',').map(function(s) { return s.trim(); }).filter(Boolean),
            instructions: $('#kjs-ai-instructions').val(),
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success && response.data.content) {
                currentGeneratedContent = response.data.content;
                var preview = '<strong>Word count:</strong> ' + response.data.content.split(' ').length + ' words<br><br>';
                preview += response.data.content;
                $('#kjs-ai-preview').html(preview);
                $('#kjs-ai-output').show();
                $('#kjs-ai-output').get(0).scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Generation failed: ' + (response.data.message || 'Unknown error'));
            }
        }).always(function() {
            btn.prop('disabled', false).text('Generate Content');
        });
    });

    $('#kjs-ai-save-draft').on('click', function() {
        if (!currentGeneratedContent) return;
        var title = $('#kjs-ai-topic').val() || 'AI Generated Post';
        var btn = $(this).prop('disabled', true).text('Saving...');

        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_create_post_from_ai',
            title: title,
            content: currentGeneratedContent,
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success) {
                alert('Saved as draft! Post ID: ' + response.data.post_id);
                currentGeneratedContent = '';
                $('#kjs-ai-output').hide();
                $('#kjs-autoblog-generate-form')[0].reset();
            } else {
                alert('Save failed: ' + (response.data.message || 'Unknown error'));
            }
        }).always(function() {
            btn.prop('disabled', false).text('Save as Draft');
        });
    });

    $('#kjs-autoblog-rewrite-form').on('submit', function(e) {
        e.preventDefault();
        var postId = $('#kjs-rewrite-post').val();
        if (!postId) { alert('Please select a post'); return; }

        currentRewritePostId = postId;
        var btn = $('#kjs-rewrite-btn').prop('disabled', true).text('Rewriting...');

        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_ai_rewrite',
            post_id: postId,
            instructions: $('#kjs-rewrite-instructions').val(),
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success && response.data.content) {
                currentRewrittenContent = response.data.content;
                var d = response.data;
                var preview = '<strong>Words:</strong> ' + d.original_words + ' &rarr; ' + d.new_words + '<br><br>';
                preview += d.content;
                $('#kjs-rewrite-preview').html(preview);
                $('#kjs-rewrite-output').show();
                $('#kjs-rewrite-output').get(0).scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Rewrite failed: ' + (response.data.message || 'Unknown error'));
            }
        }).always(function() {
            btn.prop('disabled', false).text('Rewrite');
        });
    });

    $('#kjs-rewrite-apply').on('click', function() {
        if (!currentRewrittenContent || !currentRewritePostId) return;
        var btn = $(this).prop('disabled', true).text('Applying...');

        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_queue_and_apply_rewrite',
            post_id: currentRewritePostId,
            content: currentRewrittenContent,
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success) {
                alert('Rewrite applied to post!');
                currentRewrittenContent = '';
                $('#kjs-rewrite-output').hide();
            } else {
                alert('Apply failed: ' + (response.data.message || 'Unknown error'));
            }
        }).always(function() {
            btn.prop('disabled', false).text('Apply to Post');
        });
    });

    $('.kjs-autoblog-apply').on('click', function() {
        var btn = $(this).prop('disabled', true);
        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_autoblog_apply_rewrite',
            queue_id: $(this).data('queue-id'),
            publish: 0,
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Apply failed: ' + (response.data.message || 'Unknown error'));
                btn.prop('disabled', false);
            }
        });
    });

    $('.kjs-autoblog-apply-publish').on('click', function() {
        var btn = $(this).prop('disabled', true);
        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_autoblog_apply_rewrite',
            queue_id: $(this).data('queue-id'),
            publish: 1,
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Apply failed: ' + (response.data.message || 'Unknown error'));
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
