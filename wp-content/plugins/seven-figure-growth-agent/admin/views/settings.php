<?php
if (!defined('ABSPATH')) exit;
$settings = get_option('growth_agent_settings', array());
?>
<div class="wrap growth-agent-wrap">
    <div class="growth-header">
        <h2>⚙️ AI Growth Agent Settings</h2>
    </div>

    <div class="card">
        <form id="growth-agent-settings-form">
            <?php wp_nonce_field('wp_rest'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="primary_provider">Primary AI Provider</label></th>
                    <td>
                        <select name="primary_provider" id="primary_provider">
                            <option value="gemini" <?php selected($settings['primary_provider'] ?? 'gemini', 'gemini'); ?>>Google Gemini (Recommended)</option>
                            <option value="openai" <?php selected($settings['primary_provider'] ?? '', 'openai'); ?>>OpenAI GPT-4o</option>
                            <option value="anthropic" <?php selected($settings['primary_provider'] ?? '', 'anthropic'); ?>>Anthropic Claude</option>
                            <option value="ollama" <?php selected($settings['primary_provider'] ?? '', 'ollama'); ?>>Local Ollama</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="gemini_api_key">Gemini API Key</label></th>
                    <td>
                        <input type="password" name="gemini_api_key" id="gemini_api_key" value="<?php echo esc_attr($settings['gemini_api_key'] ?? ''); ?>" class="regular-text">
                        <p class="description">Required for Google Gemini API integration.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="business_name">Business Name</label></th>
                    <td>
                        <input type="text" name="business_name" id="business_name" value="<?php echo esc_attr($settings['business_name'] ?? get_bloginfo('name')); ?>" class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="target_revenue">Target Revenue Goal</label></th>
                    <td>
                        <input type="text" name="target_revenue" id="target_revenue" value="<?php echo esc_attr($settings['target_revenue'] ?? '$1,000,000 / yr'); ?>" class="regular-text">
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" id="btn-save-settings" class="button button-primary">Save Settings</button>
            </p>
        </form>
        <div id="settings-status-message" style="margin-top:10px;"></div>
    </div>
</div>
