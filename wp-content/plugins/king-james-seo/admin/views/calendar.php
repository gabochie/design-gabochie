<div class="wrap kjs-calendar">
    <h1><?php esc_html_e('Content Calendar', 'king-james-seo'); ?></h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Title', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Module', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Scheduled', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Status', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Cluster', 'king-james-seo'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="5"><?php esc_html_e('No calendar items.', 'king-james-seo'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo esc_html($item->title); ?></td>
                        <td><?php echo esc_html($item->module); ?></td>
                        <td><?php echo esc_html($item->scheduled_date ?: '—'); ?></td>
                        <td><?php echo esc_html($item->status); ?></td>
                        <td><?php echo esc_html($item->cluster ?: '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
