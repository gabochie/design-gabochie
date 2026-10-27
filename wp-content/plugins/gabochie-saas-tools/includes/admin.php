<?php
defined('ABSPATH') || exit;

function gs_admin_menu() {
    add_options_page(
        'Gabochie SaaS Settings',
        'Gabochie SaaS',
        'manage_options',
        'gabochie-saas',
        'gs_admin_page'
    );
}
add_action('admin_menu', 'gs_admin_menu');

function gs_register_settings() {
    $sections = [
        'gs_payments' => [
            'title' => '💳  Payment Gateways',
            'intro' => 'Connect Paystack (Ghana MoMo & cards) and Stripe (international cards). Users will pay through these.',
            'fields' => [
                'gs_paystack_public'  => ['label' => 'Paystack Public Key',  'type' => 'text', 'hint' => 'pk_live_... from your Paystack dashboard → Settings → API Keys & Webhooks'],
                'gs_paystack_secret'  => ['label' => 'Paystack Secret Key',  'type' => 'password', 'hint' => 'sk_live_... Keep this private. Used to verify payments server-side.'],
                'gs_stripe_publishable' => ['label' => 'Stripe Publishable Key', 'type' => 'text', 'hint' => 'pk_live_... from your Stripe dashboard → Developers → API Keys'],
                'gs_stripe_secret'    => ['label' => 'Stripe Secret Key',    'type' => 'password', 'hint' => 'sk_live_... Used to create checkout sessions and handle webhooks.'],
                'gs_stripe_webhook'   => ['label' => 'Stripe Webhook Secret', 'type' => 'password', 'hint' => 'whsec_... From Stripe dashboard → Developers → Webhooks. Required for subscription status updates.'],
            ],
        ],
        'gs_ai' => [
            'title' => '🤖  AI Document Engine',
            'intro' => 'Connect Claude or GPT to power tool outputs. Without this, tools show demo data.',
            'fields' => [
                'gs_ai_provider'    => ['label' => 'AI Provider', 'type' => 'select', 'options' => ['claude' => 'Anthropic Claude', 'gpt' => 'OpenAI GPT-4o'], 'hint' => 'Claude is recommended for longer document generation (proposals, reports).'],
                'gs_ai_api_key'     => ['label' => 'API Key', 'type' => 'password', 'hint' => 'Your Anthropic (sk-ant-...) or OpenAI (sk-...) API key. Keep this private.'],
                'gs_ai_model'       => ['label' => 'Model', 'type' => 'select', 'options' => ['claude-sonnet-4-20250514' => 'Claude Sonnet 4 (Recommended)', 'claude-haiku-3-5-20241022' => 'Claude Haiku 3.5 (Faster/Cheaper)', 'gpt-4o' => 'GPT-4o', 'gpt-4o-mini' => 'GPT-4o Mini (Faster/Cheaper)'], 'hint' => 'Sonnet 4 is best for long-form Ghana AEC documents. Haiku is 5x cheaper.'],
                'gs_ai_max_tokens'  => ['label' => 'Max Tokens Per Output', 'type' => 'number', 'default' => 4096, 'hint' => 'Higher = longer documents. 4096 handles proposals. 8192 handles full feasibility reports.'],
            ],
        ],
        'gs_email' => [
            'title' => '📧  Transactional Email (SMTP)',
            'intro' => 'Send team invites, invoice receipts, and onboarding emails. Without this, invite emails silently fail.',
            'fields' => [
                'gs_smtp_host'      => ['label' => 'SMTP Host', 'type' => 'text', 'hint' => 'e.g. smtp.gmail.com, smtp.sendgrid.net, mail.yourdomain.com'],
                'gs_smtp_port'      => ['label' => 'SMTP Port', 'type' => 'number', 'default' => 587, 'hint' => '587 (TLS) or 465 (SSL). Most providers use 587.'],
                'gs_smtp_encryption' => ['label' => 'Encryption', 'type' => 'select', 'options' => ['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None (25)'], 'hint' => 'TLS recommended for most providers.'],
                'gs_smtp_username'  => ['label' => 'SMTP Username', 'type' => 'text', 'hint' => 'Usually the full email address.'],
                'gs_smtp_password'  => ['label' => 'SMTP Password', 'type' => 'password', 'hint' => 'App password or SMTP password (not your regular login).'],
                'gs_from_email'     => ['label' => 'From Email', 'type' => 'email', 'default' => 'noreply@tools.gabochie.com', 'hint' => 'Address users see in their inbox. Must be allowed by your SMTP provider.'],
                'gs_from_name'      => ['label' => 'From Name', 'type' => 'text', 'default' => 'Gabochie SaaS', 'hint' => 'e.g. "Gabochie SaaS" or "Gabochie Design Studio"'],
            ],
        ],
        'gs_contact' => [
            'title' => '📞  Contact & Conversion',
            'intro' => 'WhatsApp number used in the hero CTA and final CTA on the landing page.',
            'fields' => [
                'gs_whatsapp'       => ['label' => 'WhatsApp Number', 'type' => 'text', 'default' => '233XXXXXXXXX', 'hint' => 'Full international format, no + sign. e.g. 233501234567. Used for WhatsApp CTA buttons.'],
                'gs_support_email'  => ['label' => 'Support Email', 'type' => 'email', 'default' => 'support@gabochie.com', 'hint' => 'Shown in portal footer and error messages.'],
            ],
        ],
    ];

    foreach ($sections as $section_key => $section) {
        $option_group = str_replace('gs_', 'gs_opt_', $section_key);
        foreach ($section['fields'] as $key => $field) {
            $sanitize = $field['type'] === 'password' ? 'gs_sanitize_password' : 'sanitize_text_field';
            register_setting($option_group, $key, ['sanitize_callback' => $sanitize]);
        }
    }
}
add_action('admin_init', 'gs_register_settings');

function gs_sanitize_password($value) {
    if (empty($value)) return get_option(str_replace('_password', '', current_filter()) ?: '');
    return sanitize_text_field($value);
}

function gs_admin_styles($hook) {
    if ($hook !== 'settings_page_gabochie-saas') return;
    ?>
    <style>
    .gs-admin-wrap { max-width:800px; margin:24px 0; }
    .gs-admin-wrap h1 { margin-bottom:8px; }
    .gs-admin-wrap .gs-desc { font-size:14px; color:#555; margin-bottom:24px; }
    .gs-section { background:#fff; border:1px solid #c3c4c7; border-radius:6px; padding:20px 24px; margin-bottom:20px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
    .gs-section h2 { margin-top:0; font-size:16px; font-weight:600; padding-bottom:12px; border-bottom:1px solid #f0f0f1; }
    .gs-section .gs-intro { font-size:13px; color:#555; margin:8px 0 16px; }
    .gs-row { margin-bottom:14px; }
    .gs-row label { display:block; font-weight:600; font-size:13px; margin-bottom:4px; color:#1d2327; }
    .gs-row .gs-hint { font-size:12px; color:#787c82; margin-top:3px; }
    .gs-row input[type=text],
    .gs-row input[type=password],
    .gs-row input[type=email],
    .gs-row input[type=number],
    .gs-row select { width:100%; max-width:480px; }
    .gs-row input[type=password] { font-family:monospace; }
    .gs-status { display:inline-flex; align-items:center; gap:5px; font-size:12px; padding:3px 10px; border-radius:20px; font-weight:500; }
    .gs-status.ok { background:#edfaef; color:#2d8f5e; }
    .gs-status.missing { background:#fcf0f1; color:#d63638; }
    .gs-flex { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .gs-actions { margin-top:16px; padding-top:16px; border-top:1px solid #f0f0f1; }
    .gs-actions .button { min-width:100px; text-align:center; }
    .gs-notice { background:#f0f6fc; border-left:4px solid #72aee6; padding:10px 14px; font-size:13px; margin-top:12px; border-radius:0 4px 4px 0; }
    .gs-badge { display:inline-block; font-size:10px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:1px 8px; border-radius:20px; margin-left:6px; }
    .gs-badge.prod { background:#edfaef; color:#2d8f5e; }
    .gs-badge.test { background:#fcf9e8; color:#996800; }
    </style>
    <?php
}
add_action('admin_enqueue_scripts', 'gs_admin_styles');

function gs_admin_page() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap gs-admin-wrap">
        <h1>Gabochie SaaS Settings</h1>
        <?php
        $brevo_key = get_option('sib_api_key_v3', '');
        if (empty($brevo_key)) :
        ?>
        <div class="notice notice-warning is-dismissible" style="max-width:760px">
            <p><strong>Brevo (Sendinblue) Not Configured:</strong> Transactional emails (welcome emails, password resets) will not be sent. <a href="<?php echo esc_url(admin_url('admin.php?page=mailin_page')); ?>">Configure Brevo API Key</a> to enable email delivery.</p>
        </div>
        <?php endif; ?>
        <p class="gs-desc">Configure integrations for payments, AI document generation, email, and contact channels. All fields are stored encrypted at rest via WordPress options.</p>

        <form method="post" action="options.php">
            <?php
            $sections = apply_filters('gs_admin_sections', [
                'gs_payments' => ['title' => '💳  Payment Gateways', 'intro' => 'Connect Paystack (Ghana MoMo & cards) and Stripe (international cards). Users will pay through these.'],
                'gs_ai' => ['title' => '🤖  AI Document Engine', 'intro' => 'Connect Claude or GPT to power tool outputs. Without this, tools show demo/mock data.'],
                'gs_email' => ['title' => '📧  Transactional Email (SMTP)', 'intro' => 'Send team invites, invoice receipts, and onboarding emails. Without this, email features silently fail.'],
                'gs_contact' => ['title' => '📞  Contact & Conversion', 'intro' => 'WhatsApp number and support email used across the site.'],
            ]);

            foreach ($sections as $section_key => $section) :
                $option_group = 'gs_opt_' . str_replace('gs_', '', $section_key);
            ?>
            <div class="gs-section">
                <h2><?php echo $section['title']; ?></h2>
                <p class="gs-intro"><?php echo $section['intro']; ?></p>

                <?php
                $fields = [
                    'gs_payments' => [
                        'gs_paystack_public'  => ['label' => 'Paystack Public Key',  'type' => 'text', 'hint' => 'pk_live_... from Paystack dashboard → Settings → API Keys'],
                        'gs_paystack_secret'  => ['label' => 'Paystack Secret Key',  'type' => 'password', 'hint' => 'sk_live_... Used server-side to verify payments.'],
                        'gs_stripe_publishable' => ['label' => 'Stripe Publishable Key', 'type' => 'text', 'hint' => 'pk_live_... from Stripe dashboard → Developers → API Keys'],
                        'gs_stripe_secret'    => ['label' => 'Stripe Secret Key',    'type' => 'password', 'hint' => 'sk_live_... Creates checkout sessions & handles webhooks.'],
                        'gs_stripe_webhook'   => ['label' => 'Stripe Webhook Secret', 'type' => 'password', 'hint' => 'whsec_... From Stripe → Developers → Webhooks. Required for subscription status.'],
                    ],
                    'gs_ai' => [
                        'gs_ai_provider'    => ['label' => 'AI Provider', 'type' => 'select', 'options' => ['claude' => 'Anthropic Claude', 'gpt' => 'OpenAI GPT-4o'], 'hint' => 'Claude recommended for long Ghana AEC documents.'],
                        'gs_ai_api_key'     => ['label' => 'API Key', 'type' => 'password', 'hint' => 'sk-ant-... (Claude) or sk-... (OpenAI). Keep private.'],
                        'gs_ai_model'       => ['label' => 'Model', 'type' => 'select', 'options' => ['claude-sonnet-4-20250514' => 'Claude Sonnet 4 (Recommended)', 'claude-haiku-3-5-20241022' => 'Claude Haiku 3.5 (Faster/Cheaper)', 'gpt-4o' => 'GPT-4o', 'gpt-4o-mini' => 'GPT-4o Mini (Faster/Cheaper)'], 'hint' => 'Sonnet 4 for full proposals. Haiku 5x cheaper for shorter outputs.'],
                        'gs_ai_max_tokens'  => ['label' => 'Max Tokens Per Output', 'type' => 'number', 'hint' => '4096 = ~3K words. 8192 = full feasibility report.', 'default' => 4096],
                    ],
                    'gs_email' => [
                        'gs_smtp_host'      => ['label' => 'SMTP Host', 'type' => 'text', 'hint' => 'e.g. smtp.gmail.com, smtp.sendgrid.net, mail.yourdomain.com'],
                        'gs_smtp_port'      => ['label' => 'SMTP Port', 'type' => 'number', 'hint' => '587 (TLS) or 465 (SSL).', 'default' => 587],
                        'gs_smtp_encryption' => ['label' => 'Encryption', 'type' => 'select', 'options' => ['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None (25)'], 'hint' => 'TLS recommended.'],
                        'gs_smtp_username'  => ['label' => 'SMTP Username', 'type' => 'text', 'hint' => 'Usually the full email address.'],
                        'gs_smtp_password'  => ['label' => 'SMTP Password', 'type' => 'password', 'hint' => 'App password or SMTP password (not your regular login).'],
                        'gs_from_email'     => ['label' => 'From Email', 'type' => 'email', 'hint' => 'Must be allowed by your SMTP provider.', 'default' => 'noreply@tools.gabochie.com'],
                        'gs_from_name'      => ['label' => 'From Name', 'type' => 'text', 'hint' => 'Appears as the sender name in recipients\' inboxes.', 'default' => 'Gabochie SaaS'],
                    ],
                    'gs_contact' => [
                        'gs_whatsapp'      => ['label' => 'WhatsApp Number', 'type' => 'text', 'hint' => 'Full international format, no +. e.g. 233501234567', 'default' => '233XXXXXXXXX'],
                        'gs_support_email' => ['label' => 'Support Email', 'type' => 'email', 'hint' => 'Shown in portal footer and error messages.', 'default' => 'support@gabochie.com'],
                    ],
                ];

                $section_fields = $fields[$section_key] ?? $section['fields'] ?? [];
                settings_fields($option_group);
                foreach ($section_fields as $key => $f) :
                    if (($f['type'] ?? '') === 'info') :
                ?>
                <div class="gs-row">
                    <div class="gs-flex">
                        <label><?php echo esc_html($f['label']); ?></label>
                        <span class="gs-status ok"><?php echo esc_html($f['value'] ?? ''); ?></span>
                    </div>
                </div>
                <?php continue; endif;
                    $value = get_option($key, $f['default'] ?? '');
                    $is_configured = !empty($value) && $value !== ($f['default'] ?? '');
                    $status_class = $is_configured ? 'ok' : 'missing';
                    $status_label = $is_configured ? '✓ Configured' : '✗ Not set';
                ?>
                <div class="gs-row">
                    <div class="gs-flex">
                        <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($f['label']); ?></label>
                        <span class="gs-status <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                    </div>
                    <?php if ($f['type'] === 'select' && !empty($f['options'])) : ?>
                        <select id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>">
                            <?php foreach ($f['options'] as $opt_val => $opt_label) : printf('<option value="%s"%s>%s</option>', esc_attr($opt_val), selected($value, $opt_val, false), esc_html($opt_label)); endforeach; ?>
                        </select>
                    <?php elseif ($f['type'] === 'number') : ?>
                        <input type="number" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text" min="0">
                    <?php elseif ($f['type'] === 'password') : ?>
                        <input type="password" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text" autocomplete="off">
                    <?php elseif ($f['type'] === 'info') : ?>
                        <span class="gs-status ok"><?php echo esc_html($f['value'] ?? ''); ?></span>
                    <?php else : ?>
                        <input type="<?php echo esc_attr($f['type']); ?>" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text" <?php echo $f['type'] === 'email' ? 'style="max-width:360px"' : ''; ?>>
                    <?php endif; ?>
                    <?php if (!empty($f['hint'])) : ?><div class="gs-hint"><?php echo esc_html($f['hint']); ?></div><?php endif; ?>
                </div>
                <?php endforeach; ?>

                <div class="gs-actions">
                    <?php $has_form_fields = array_filter($section_fields, fn($f) => ($f['type'] ?? '') !== 'info'); ?>
                    <?php if ($has_form_fields) : ?>
                        <?php submit_button('Save ' . explode(' ', $section['title'])[1] . ' Settings', 'primary', 'submit', false, ['style' => 'float:left;margin-right:8px']); ?>
                    <?php endif; ?>
                    <?php if ($section_key === 'gs_ai') : ?>
                        <button type="button" class="button button-secondary" id="gs-ai-test" style="margin-left:8px">Test Connection</button>
                        <span id="gs-ai-test-result" style="margin-left:12px;font-size:13px;"></span>
                    <?php endif; ?>
                    <?php if (!empty($section['actions'])) : ?>
                        <?php foreach ($section['actions'] as $action) : ?>
                            <a href="<?php echo esc_url($action['url']); ?>" class="button <?php echo esc_attr($action['class'] ?? 'button-secondary'); ?>" style="margin-left:8px"><?php echo esc_html($action['label']); ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </form>

        <div class="gs-section" style="background:#f6f7f7">
            <h2>🔗  Quick Reference</h2>
            <p class="gs-intro">Where to find the keys you need for each service.</p>
            <table class="widefat striped" style="max-width:600px;font-size:13px">
                <thead><tr><th>Service</th><th>Dashboard URL</th><th>What to create</th></tr></thead>
                <tbody>
                    <tr><td>Paystack</td><td><a href="https://dashboard.paystack.com" target="_blank" rel="noopener">dashboard.paystack.com</a></td><td>Settings → API Keys → Live keys</td></tr>
                    <tr><td>Stripe</td><td><a href="https://dashboard.stripe.com" target="_blank" rel="noopener">dashboard.stripe.com</a></td><td>Developers → API Keys + Webhooks</td></tr>
                    <tr><td>Anthropic</td><td><a href="https://console.anthropic.com" target="_blank" rel="noopener">console.anthropic.com</a></td><td>API Keys → Create key</td></tr>
                    <tr><td>OpenAI</td><td><a href="https://platform.openai.com" target="_blank" rel="noopener">platform.openai.com</a></td><td>API Keys → Create new secret key</td></tr>
                    <tr><td>SMTP (Gmail)</td><td><a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">Google App Passwords</a></td><td>App password (not your regular password)</td></tr>
                    <tr><td>SendGrid</td><td><a href="https://app.sendgrid.com" target="_blank" rel="noopener">app.sendgrid.com</a></td><td>Settings → API Keys → Create API Key</td></tr>
                </tbody>
            </table>
        </div>

        <div class="gs-section" style="background:#f6f7f7">
            <h2>🧪  Next Steps After Configuring</h2>
            <ol style="margin:8px 0 0 20px;font-size:13px;color:#555;line-height:1.8">
                <li><strong>Payments:</strong> Create a product + price in Stripe, set your webhook to <code><?php echo esc_url(home_url('/wp-json/gs/v1/stripe-webhook')); ?></code></li>
                <li><strong>AI:</strong> Visit the portal → Tools → run any tool. If the API key is valid, output switches from mock to AI-generated.</li>
                <li><strong>Email:</strong> Send a test invite from the Team section in the portal. Check the recipient's inbox (and spam).</li>
                <li><strong>WhatsApp:</strong> The hero CTA button on the landing page will point to your number. Test the link.</li>
            </ol>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#gs-ai-test').on('click', function() {
            var $btn = $(this);
            var $result = $('#gs-ai-test-result');
            $btn.prop('disabled', true);
            $result.html('Testing...').css('color', '#787c82');
            $.post(ajaxurl, {
                action: 'gs_ai_test'
            }, function(res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    $result.html('✓ ' + res.data.message).css('color', '#2d8f5e');
                } else {
                    $result.html('✗ ' + res.data.message).css('color', '#d63638');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $result.html('✗ Connection failed').css('color', '#d63638');
            });
        });
    });
    </script>
    <?php
}
