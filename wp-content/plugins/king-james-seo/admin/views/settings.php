<div class="wrap kjs-settings">
    <h1><?php esc_html_e('Settings', 'king-james-seo'); ?></h1>

    <?php
    $ai_settings = get_option('kjs_ai_settings', []);
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'modules';
    ?>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-james-seo-settings&tab=modules')); ?>" class="nav-tab <?php echo $active_tab === 'modules' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Modules', 'king-james-seo'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-james-seo-settings&tab=ai')); ?>" class="nav-tab <?php echo $active_tab === 'ai' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('AI Provider', 'king-james-seo'); ?></a>
    </nav>

    <?php if ($active_tab === 'ai'): ?>

        <form method="post" action="options.php">
            <?php settings_fields('kjs_ai_settings'); ?>

            <h2><?php esc_html_e('AI Content Provider', 'king-james-seo'); ?></h2>
            <p><?php esc_html_e('Configure your AI provider for content generation and rewriting. All providers are supported — enter your API key and select a model.', 'king-james-seo'); ?></p>

            <table class="form-table">
                <tr>
                    <th><label for="kjs-ai-provider"><?php esc_html_e('Provider', 'king-james-seo'); ?></label></th>
                    <td>
                        <select name="kjs_ai_settings[provider]" id="kjs-ai-provider" class="regular-text">
                            <option value="openai" <?php selected($ai_settings['provider'] ?? '', 'openai'); ?>>OpenAI (GPT-4o, GPT-4.1)</option>
                            <option value="anthropic" <?php selected($ai_settings['provider'] ?? '', 'anthropic'); ?>>Anthropic (Claude Sonnet 4)</option>
                            <option value="gemini" <?php selected($ai_settings['provider'] ?? '', 'gemini'); ?>>Google Gemini 2.5 Pro</option>
                        </select>
                    </td>
                </tr>

                <tr class="kjs-ai-key-row" data-provider="openai">
                    <th><label for="kjs-openai-key"><?php esc_html_e('OpenAI API Key', 'king-james-seo'); ?></label></th>
                    <td>
                        <input type="password" name="kjs_ai_settings[openai_api_key]" id="kjs-openai-key" class="regular-text" value="<?php echo esc_attr($ai_settings['openai_api_key'] ?? ''); ?>" placeholder="sk-...">
                        <p class="description"><?php esc_html_e('Get your key at platform.openai.com/api-keys', 'king-james-seo'); ?></p>
                    </td>
                </tr>
                <tr class="kjs-ai-key-row" data-provider="openai">
                    <th><label for="kjs-openai-model"><?php esc_html_e('OpenAI Model', 'king-james-seo'); ?></label></th>
                    <td>
                        <select name="kjs_ai_settings[openai_model]" id="kjs-openai-model">
                            <option value="gpt-4o" <?php selected($ai_settings['openai_model'] ?? '', 'gpt-4o'); ?>>GPT-4o</option>
                            <option value="gpt-4.1" <?php selected($ai_settings['openai_model'] ?? '', 'gpt-4.1'); ?>>GPT-4.1</option>
                            <option value="gpt-4o-mini" <?php selected($ai_settings['openai_model'] ?? '', 'gpt-4o-mini'); ?>>GPT-4o Mini (cheaper)</option>
                        </select>
                    </td>
                </tr>

                <tr class="kjs-ai-key-row" data-provider="anthropic" style="display:none;">
                    <th><label for="kjs-anthropic-key"><?php esc_html_e('Anthropic API Key', 'king-james-seo'); ?></label></th>
                    <td>
                        <input type="password" name="kjs_ai_settings[anthropic_api_key]" id="kjs-anthropic-key" class="regular-text" value="<?php echo esc_attr($ai_settings['anthropic_api_key'] ?? ''); ?>" placeholder="sk-ant-...">
                        <p class="description"><?php esc_html_e('Get your key at console.anthropic.com', 'king-james-seo'); ?></p>
                    </td>
                </tr>
                <tr class="kjs-ai-key-row" data-provider="anthropic" style="display:none;">
                    <th><label for="kjs-anthropic-model"><?php esc_html_e('Anthropic Model', 'king-james-seo'); ?></label></th>
                    <td>
                        <select name="kjs_ai_settings[anthropic_model]" id="kjs-anthropic-model">
                            <option value="claude-sonnet-4-20250514" <?php selected($ai_settings['anthropic_model'] ?? '', 'claude-sonnet-4-20250514'); ?>>Claude Sonnet 4</option>
                            <option value="claude-4-opus-20250514" <?php selected($ai_settings['anthropic_model'] ?? '', 'claude-4-opus-20250514'); ?>>Claude Opus 4</option>
                            <option value="claude-3-5-haiku-20241022" <?php selected($ai_settings['anthropic_model'] ?? '', 'claude-3-5-haiku-20241022'); ?>>Claude 3.5 Haiku</option>
                        </select>
                    </td>
                </tr>

                <tr class="kjs-ai-key-row" data-provider="gemini" style="display:none;">
                    <th><label for="kjs-gemini-key"><?php esc_html_e('Gemini API Key', 'king-james-seo'); ?></label></th>
                    <td>
                        <input type="password" name="kjs_ai_settings[gemini_api_key]" id="kjs-gemini-key" class="regular-text" value="<?php echo esc_attr($ai_settings['gemini_api_key'] ?? ''); ?>" placeholder="AIza...">
                        <p class="description"><?php esc_html_e('Get your key at aistudio.google.com', 'king-james-seo'); ?></p>
                    </td>
                </tr>
                <tr class="kjs-ai-key-row" data-provider="gemini" style="display:none;">
                    <th><label for="kjs-gemini-model"><?php esc_html_e('Gemini Model', 'king-james-seo'); ?></label></th>
                    <td>
                        <select name="kjs_ai_settings[gemini_model]" id="kjs-gemini-model">
                            <option value="gemini-2.5-pro" <?php selected($ai_settings['gemini_model'] ?? '', 'gemini-2.5-pro'); ?>>Gemini 2.5 Pro</option>
                            <option value="gemini-2.5-flash" <?php selected($ai_settings['gemini_model'] ?? '', 'gemini-2.5-flash'); ?>>Gemini 2.5 Flash (cheaper)</option>
                        </select>
                    </td>
                </tr>
            </table>

            <script>
            jQuery(function($) {
                $('#kjs-ai-provider').on('change', function() {
                    $('.kjs-ai-key-row').hide();
                    $('.kjs-ai-key-row[data-provider="' + $(this).val() + '"]').show();
                });
            });
            </script>

            <?php submit_button(__('Save AI Settings', 'king-james-seo')); ?>
        </form>

    <?php else: ?>

        <form method="post" action="options.php">
            <?php settings_fields('kjs_settings'); ?>

            <h2><?php esc_html_e('Module Autonomy Levels', 'king-james-seo'); ?></h2>
            <p><?php esc_html_e('Set the autonomy level for each module. L1 = fully assisted, L4 = fully autonomous.', 'king-james-seo'); ?></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Module', 'king-james-seo'); ?></th>
                        <th><?php esc_html_e('Category', 'king-james-seo'); ?></th>
                        <th><?php esc_html_e('Autonomy Level', 'king-james-seo'); ?></th>
                        <th><?php esc_html_e('Status', 'king-james-seo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($module_manager->all() as $id => $mod): ?>
                        <?php $level = $guardrails->get_level($id); ?>
                        <tr>
                            <td><?php echo esc_html($mod['name']); ?></td>
                            <td><?php echo esc_html($mod['category']); ?></td>
                            <td>
                                <select name="kjs_autonomy_<?php echo esc_attr($id); ?>" class="kjs-autonomy-select">
                                    <?php foreach (['L1', 'L2', 'L3', 'L4'] as $l): ?>
                                        <option value="<?php echo esc_attr($l); ?>" <?php selected($level, $l); ?>><?php echo esc_html($l); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <label class="kjs-toggle">
                                    <input type="checkbox" name="kjs_module_<?php echo esc_attr($id); ?>" value="1" <?php checked($mod['enabled']); ?>>
                                    <span class="kjs-toggle-slider"></span>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php submit_button(); ?>
        </form>

    <?php endif; ?>
</div>
