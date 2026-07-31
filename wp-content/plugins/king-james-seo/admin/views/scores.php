<div class="wrap kjs-scores">
    <h1><?php esc_html_e('Content Scores', 'king-james-seo'); ?></h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Post', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Score', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Passed', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Scored At', 'king-james-seo'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($scores)): ?>
                <tr><td colspan="4"><?php esc_html_e('No scores yet.', 'king-james-seo'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($scores as $s): ?>
                    <tr>
                        <td><a href="<?php echo get_edit_post_link($s->post_id); ?>"><?php echo esc_html($s->post_title ?: "#{$s->post_id}"); ?></a></td>
                        <td><?php echo esc_html($s->total_score); ?>/104</td>
                        <td><?php echo $s->passed_threshold ? '✓' : '✗'; ?></td>
                        <td><?php echo esc_html($s->scored_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
