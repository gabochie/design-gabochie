<div class="wrap kjs-gscc">
    <h1><?php esc_html_e('Google Scaled Content Compliance', 'king-james-seo'); ?></h1>
    <p class="description"><?php esc_html_e('Audit your content against Google\'s scaled content abuse policies. Detects auto-generated, thin, templated, or low-value content at scale and provides smart recommendations.', 'king-james-seo'); ?></p>

    <?php if (!empty($audit_complete)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html(sprintf(__('Bulk audit complete! %d posts analyzed.', 'king-james-seo'), count($results))); ?></p>
        </div>
    <?php endif; ?>

    <div class="kjs-gscc-toolbar">
        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=king-james-seo-gscc&gscc_action=run_audit'), 'kjs_gscc_run_audit')); ?>" class="button button-primary">
            <?php esc_html_e('Run Full Site Audit', 'king-james-seo'); ?>
        </a>
        <button type="button" class="button" id="kjs-gscc-audit-all-ajax">
            <?php esc_html_e('Audit All (AJAX)', 'king-james-seo'); ?>
        </button>
        <span class="kjs-gscc-spinner" style="display:none;"><?php esc_html_e('Auditing...', 'king-james-seo'); ?></span>
    </div>

    <div class="kjs-stats-grid">
        <div class="kjs-stat-card">
            <span class="kjs-stat-number"><?php echo esc_html($stats['total_audited']); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Posts Audited', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number"><?php echo esc_html(round($stats['avg_score'], 1)); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Avg Compliance Score', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number" style="color:#d63638;"><?php echo esc_html($stats['critical']); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Critical (&lt; 40)', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number" style="color:#dba617;"><?php echo esc_html($stats['warning']); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Warning (40–69)', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number" style="color:#008a20;"><?php echo esc_html($stats['passing']); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Passing (≥ 70)', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number"><?php echo esc_html($total_recommendations); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Smart Recommendations', 'king-james-seo'); ?></span>
        </div>
    </div>

    <?php if (!empty($audits)): ?>
        <h2><?php esc_html_e('Audit Results', 'king-james-seo'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Post', 'king-james-seo'); ?></th>
                    <th><?php esc_html_e('Type', 'king-james-seo'); ?></th>
                    <th><?php esc_html_e('Words', 'king-james-seo'); ?></th>
                    <th><?php esc_html_e('Issues', 'king-james-seo'); ?></th>
                    <th><?php esc_html_e('Score', 'king-james-seo'); ?></th>
                    <th><?php esc_html_e('Status', 'king-james-seo'); ?></th>
                    <th><?php esc_html_e('Actions', 'king-james-seo'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audits as $audit): ?>
                    <?php
                        $score_class = 'kjs-score-pass';
                        $score_label = __('Passing', 'king-james-seo');
                        if ($audit->compliance_score < 40) {
                            $score_class = 'kjs-score-critical';
                            $score_label = __('Critical', 'king-james-seo');
                        } elseif ($audit->compliance_score < 70) {
                            $score_class = 'kjs-score-warning';
                            $score_label = __('Warning', 'king-james-seo');
                        }
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($audit->post_title ?: __('(untitled)', 'king-james-seo')); ?></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="<?php echo esc_url(get_edit_post_link($audit->post_id)); ?>"><?php esc_html_e('Edit', 'king-james-seo'); ?></a></span>
                                <span class="view"><a href="<?php echo esc_url(get_permalink($audit->post_id)); ?>"><?php esc_html_e('View', 'king-james-seo'); ?></a></span>
                            </div>
                        </td>
                        <td><?php echo esc_html($audit->post_type); ?></td>
                        <td><?php echo esc_html($audit->word_count); ?></td>
                        <td><?php echo esc_html($audit->issues_found); ?></td>
                        <td class="<?php echo $score_class; ?>"><?php echo esc_html($audit->compliance_score); ?></td>
                        <td><span class="kjs-badge kjs-badge-<?php echo $score_class; ?>"><?php echo esc_html($score_label); ?></span></td>
                        <td>
                            <button type="button" class="button button-small kjs-gscc-reaudit" data-post-id="<?php echo esc_attr($audit->post_id); ?>">
                                <?php esc_html_e('Re-audit', 'king-james-seo'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('No audits yet. Click "Run Full Site Audit" to analyze your content.', 'king-james-seo'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($recommendations)): ?>
        <h2><?php esc_html_e('Smart Recommendations', 'king-james-seo'); ?></h2>
        <div class="kjs-gscc-recommendations">
            <?php foreach ($recommendations as $rec): ?>
                <?php
                    $sev_class = 'warning' === $rec->severity ? 'kjs-rec-warning' : 'kjs-rec-critical';
                    $sev_icon  = 'warning' === $rec->severity ? '&#9888;' : '&#10060;';
                ?>
                <div class="kjs-rec-card <?php echo $sev_class; ?>">
                    <div class="kjs-rec-header">
                        <span class="kjs-rec-icon"><?php echo $sev_icon; ?></span>
                        <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $rec->issue_type))); ?></strong>
                        <span class="kjs-rec-severity"><?php echo esc_html($rec->severity); ?></span>
                    </div>
                    <div class="kjs-rec-body">
                        <p><strong><?php esc_html_e('Post:', 'king-james-seo'); ?></strong> <?php echo esc_html($rec->post_title); ?></p>
                        <p><strong><?php esc_html_e('Issue:', 'king-james-seo'); ?></strong> <?php echo esc_html($rec->description); ?></p>
                        <div class="kjs-rec-fix">
                            <strong><?php esc_html_e('Recommendation:', 'king-james-seo'); ?></strong>
                            <p><?php echo esc_html($rec->recommendation); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(function($) {
    $('#kjs-gscc-audit-all-ajax').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true);
        $('.kjs-gscc-spinner').show();

        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_gscc_audit_all',
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success) {
                alert('Audit complete! ' + response.data.total + ' posts analyzed. Average score: ' + response.data.average);
                location.reload();
            } else {
                alert('Audit failed: ' + (response.data.message || 'Unknown error'));
            }
        }).always(function() {
            btn.prop('disabled', false);
            $('.kjs-gscc-spinner').hide();
        });
    });

    $('.kjs-gscc-reaudit').on('click', function() {
        var btn = $(this);
        var postId = btn.data('post-id');
        btn.prop('disabled', true).text('Auditing...');

        $.post(kjsAdmin.ajaxUrl, {
            action: 'kjs_gscc_audit_single',
            post_id: postId,
            _ajax_nonce: kjsAdmin.nonce
        }, function(response) {
            if (response.success) {
                alert('Re-audit complete! Score: ' + response.data.compliance_score);
                location.reload();
            } else {
                alert('Audit failed: ' + (response.data.message || 'Unknown error'));
            }
        }).always(function() {
            btn.prop('disabled', false).text('Re-audit');
        });
    });
});
</script>
