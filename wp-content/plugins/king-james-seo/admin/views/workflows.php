<div class="wrap kjs-workflows">
    <h1><?php esc_html_e('Workflow Orchestrator', 'king-james-seo'); ?></h1>

    <h2><?php esc_html_e('9-Phase SOP Pipeline', 'king-james-seo'); ?></h2>
    <div class="kjs-phase-flow">
        <?php foreach ($phases as $i => $phase): ?>
            <div class="kjs-phase-step">
                <span class="kjs-phase-num"><?php echo $i + 1; ?></span>
                <span class="kjs-phase-name"><?php echo esc_html(ucwords(str_replace('_', ' ', $phase))); ?></span>
                <?php if ($i < count($phases) - 1): ?>
                    <span class="kjs-phase-arrow">→</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <h2><?php esc_html_e('Active Runs', 'king-james-seo'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Run', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Status', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Phase', 'king-james-seo'); ?></th>
                <th><?php esc_html_e('Started', 'king-james-seo'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($active_runs)): ?>
                <tr><td colspan="4"><?php esc_html_e('No active runs.', 'king-james-seo'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($active_runs as $run): ?>
                    <tr>
                        <td><?php echo esc_html($run['name']); ?></td>
                        <td><?php echo esc_html($run['status']); ?></td>
                        <td><?php echo esc_html($run['current_phase']); ?></td>
                        <td><?php echo esc_html($run['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
