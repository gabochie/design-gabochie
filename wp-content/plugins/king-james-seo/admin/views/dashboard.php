<div class="wrap kjs-dashboard">
    <h1><?php esc_html_e('King James SEO Command Center', 'king-james-seo'); ?></h1>

    <div class="kjs-stats-grid">
        <div class="kjs-stat-card">
            <span class="kjs-stat-number"><?php echo count($active_runs); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Active Pipelines', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number"><?php echo count($module_manager->all()); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Modules', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number"><?php echo count($categories); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Categories', 'king-james-seo'); ?></span>
        </div>
        <div class="kjs-stat-card">
            <span class="kjs-stat-number"><?php echo count($recent_logs); ?></span>
            <span class="kjs-stat-label"><?php esc_html_e('Recent Actions', 'king-james-seo'); ?></span>
        </div>
    </div>

    <?php foreach ($categories as $category): ?>
        <div class="kjs-category-section">
            <h2><?php echo esc_html($category); ?></h2>
            <div class="kjs-module-grid">
                <?php foreach ($module_manager->get_by_category($category) as $id => $mod): ?>
                    <?php $level = $guardrails->get_level($id); ?>
                    <div class="kjs-module-card" data-module="<?php echo esc_attr($id); ?>">
                        <div class="kjs-module-header">
                            <h3><?php echo esc_html($mod['name']); ?></h3>
                            <span class="kjs-autonomy-badge autonomy-<?php echo strtolower($level); ?>"><?php echo esc_html($level); ?></span>
                        </div>
                        <div class="kjs-module-body">
                            <p class="kjs-module-deps">
                                <?php if (!empty($mod['dependencies'])): ?>
                                    <?php esc_html_e('Depends on:', 'king-james-seo'); ?>
                                    <?php echo esc_html(implode(', ', $mod['dependencies'])); ?>
                                <?php else: ?>
                                    <span class="kjs-standalone"><?php esc_html_e('Standalone', 'king-james-seo'); ?></span>
                                <?php endif; ?>
                            </p>
                            <p class="kjs-module-status">
                                <?php if ($mod['enabled']): ?>
                                    <span class="kjs-status-active"><?php esc_html_e('Active', 'king-james-seo'); ?></span>
                                <?php else: ?>
                                    <span class="kjs-status-inactive"><?php esc_html_e('Inactive', 'king-james-seo'); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
