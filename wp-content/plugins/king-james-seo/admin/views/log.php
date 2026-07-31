<div class="wrap kjs-log">
    <h1><?php esc_html_e('Audit Log', 'king-james-seo'); ?></h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Time', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Module', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Action', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Summary', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('User', 'king-james-seo'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5"><?php esc_html_e('No log entries yet.', 'king-james-seo'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log['created_at']); ?></td>
                        <td><?php echo esc_html($log['module']); ?></td>
                        <td><?php echo esc_html($log['action']); ?></td>
                        <td><?php echo esc_html($log['summary']); ?></td>
                        <td><?php echo (int) $log['user_id'] ? get_userdata((int) $log['user_id'])->display_name : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
