<div class="wrap kjs-pipelines">
    <h1><?php esc_html_e('Pipeline Runs', 'king-james-seo'); ?></h1>

    <button class="button button-primary" id="kjs-create-pipeline"><?php esc_html_e('New Pipeline Run', 'king-james-seo'); ?></button>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('ID', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Name', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Status', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Current Phase', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Created', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Actions', 'king-james-seo'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($runs)): ?>
                <tr><td colspan="6"><?php esc_html_e('No pipeline runs yet.', 'king-james-seo'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($runs as $run): ?>
                    <tr>
                        <td><?php echo (int) $run['id']; ?></td>
                        <td><?php echo esc_html($run['name']); ?></td>
                        <td><span class="kjs-status-<?php echo esc_attr($run['status']); ?>"><?php echo esc_html($run['status']); ?></span></td>
                        <td><?php echo esc_html($run['current_phase']); ?></td>
                        <td><?php echo esc_html($run['created_at']); ?></td>
                        <td>
                            <?php if ('in_progress' === $run['status']): ?>
                                <button class="button kjs-advance" data-run-id="<?php echo (int) $run['id']; ?>"><?php esc_html_e('Advance', 'king-james-seo'); ?></button>
                                <button class="button kjs-fail" data-run-id="<?php echo (int) $run['id']; ?>"><?php esc_html_e('Fail', 'king-james-seo'); ?></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
