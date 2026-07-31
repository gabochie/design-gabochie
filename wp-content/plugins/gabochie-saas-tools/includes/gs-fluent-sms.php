<?php
defined('ABSPATH') || exit;

/**
 * Gabochie FluentCRM SMS Integration
 * Adds SMS sending + history to FluentCRM via Brevo API.
 */

require_once GST_PATH . 'includes/class-gs-sms.php';

// --- Add "Send SMS" bulk action to contacts list ---

add_filter('fluent_crm/custom_contact_bulk_actions', function (array $actions) {
    $actions[] = [
        'action_name' => 'gs_send_sms',
        'label'       => 'Send SMS (Gabochie)',
    ];
    return $actions;
});

// JS interceptor: redirect to bulk SMS page when action is selected
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if (!$screen || !str_contains($screen->id ?? '', 'fluentcrm')) return;
    ?>
    <script>
    var gs_redirecting = false;
    jQuery(document).on('click', '.fcrm_bulk_select .el-select-dropdown__item, .el-popper.fcrm-dark .el-select-dropdown__item', function() {
        if (gs_redirecting) return;
        if (jQuery(this).text().trim() === 'Send SMS (Gabochie)') {
            gs_redirecting = true;
            setTimeout(function() {
                var ids = [];
                jQuery('.el-table__body-wrapper input[type="checkbox"]:checked').each(function() {
                    var row = jQuery(this).closest('.el-table__row');
                    var link = row.find('a[href*="#/subscribers/"]').first();
                    var match = link.attr('href') && link.attr('href').match(/\/subscribers\/(\d+)/);
                    if (match) ids.push(match[1]);
                });
                if (ids.length) {
                    window.location.href = '<?php echo admin_url('admin.php?page=gabochie-sms-bulk&ids='); ?>' + ids.join(',');
                } else {
                    gs_redirecting = false;
                }
            }, 200);
        }
    });
    </script>
    <?php
});

// --- Add SMS tab to contact profile (FluentCRM v3 Vue-based UI) ---

add_filter('fluentcrm_profile_sections', function (array $sections) {
    $sections['gs_sms_history'] = [
        'name'    => 'fluentcrm_profile_extended',
        'title'   => 'SMS History (Gabochie)',
        'handler' => 'route',
        'query'   => ['handler' => 'gs_sms_history'],
    ];
    return $sections;
});

add_filter('fluencrm_profile_section_gs_sms_history', function ($content, $subscriber) {
    $sms = new GS_SMS();
    $logs = gs_fluent_get_sms_logs($subscriber->id);

    ob_start();
    ?>
    <div style="padding:15px">
        <h3>SMS History</h3>
        <?php if (empty($logs)) : ?>
            <p>No SMS sent yet.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped" style="margin-top:10px">
                <thead><tr><th>Date</th><th>To</th><th>Message</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo esc_html($log['sent_at']); ?></td>
                        <td><?php echo esc_html($log['phone']); ?></td>
                        <td><?php echo esc_html(wp_trim_words($log['message'], 20)); ?></td>
                        <td><?php echo $log['status'] === 'sent' ? '<span style="color:#2d8f5e">Sent</span>' : '<span style="color:#d63638">Failed</span>'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <hr style="margin:20px 0">
        <h4>Send SMS to <?php echo esc_html($subscriber->first_name ?: $subscriber->email); ?></h4>
        <p>Phone: <strong><?php echo esc_html($subscriber->phone ?: 'No phone number'); ?></strong></p>
        <?php if ($subscriber->phone) : ?>
        <div style="max-width:500px">
            <textarea id="gs-sms-message-<?php echo (int) $subscriber->id; ?>" style="width:100%;min-height:80px;padding:8px" placeholder="Type your SMS message..."></textarea>
            <p style="font-size:12px;color:#787c82" id="gs-sms-char-count-<?php echo (int) $subscriber->id; ?>">0 / 160 characters</p>
            <button class="button button-primary" onclick="gs_send_sms(<?php echo (int) $subscriber->id; ?>)">Send SMS</button>
            <span id="gs-sms-result-<?php echo (int) $subscriber->id; ?>" style="margin-left:10px;font-size:13px"></span>
        </div>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_get_clean();

    return [
        'heading'      => 'SMS History (Gabochie)',
        'content_html' => $html,
    ];
}, 10, 2);

// --- AJAX handler: send single SMS ---

add_action('wp_ajax_gs_send_sms', function () {
    check_ajax_referer('gs_sms_nonce', 'nonce');

    $contact_id = (int) ($_POST['contact_id'] ?? 0);
    $message    = sanitize_textarea_field($_POST['message'] ?? '');

    if (!$contact_id || empty($message)) {
        wp_send_json_error(['message' => 'Contact ID and message required.']);
    }

    if (!class_exists('FluentCrm\App\Models\Subscriber')) {
        wp_send_json_error(['message' => 'FluentCRM not available.']);
    }

    $contact = \FluentCrm\App\Models\Subscriber::find($contact_id);
    if (!$contact || empty($contact->phone)) {
        wp_send_json_error(['message' => 'Contact not found or no phone number.']);
    }

    $sms = new GS_SMS();
    $result = $sms->send($contact->phone, $message, [
        'tag' => 'fluentcrm-single',
    ]);

    gs_fluent_log_sms($contact_id, $contact->phone, $message, $result['success'] ? 'sent' : 'failed', $result['error'] ?? '');

    if ($result['success']) {
        $contact->add_note(sprintf('SMS sent via Gabochie: %s', wp_trim_words($message, 15)), 'sms', 'Gabochie SMS');
        wp_send_json_success(['message' => 'SMS sent successfully.']);
    } else {
        wp_send_json_error(['message' => $result['error']]);
    }
});

// --- AJAX handler: bulk SMS ---

add_action('wp_ajax_gs_bulk_send_sms', function () {
    check_ajax_referer('gs_sms_nonce', 'nonce');

    $contact_ids = array_map('intval', $_POST['contact_ids'] ?? []);
    $message     = sanitize_textarea_field($_POST['message'] ?? '');

    if (empty($contact_ids) || empty($message)) {
        wp_send_json_error(['message' => 'Contacts and message required.']);
    }

    $sms = new GS_SMS();
    $sent = 0;
    $failed = 0;
    $errors = [];

    foreach ($contact_ids as $cid) {
        $contact = \FluentCrm\App\Models\Subscriber::find($cid);
        if (!$contact || empty($contact->phone)) {
            $failed++;
            $errors[] = "Contact #$cid: no phone";
            continue;
        }

        $result = $sms->send($contact->phone, $message, [
            'tag' => 'fluentcrm-bulk',
        ]);

        gs_fluent_log_sms($cid, $contact->phone, $message, $result['success'] ? 'sent' : 'failed', $result['error'] ?? '');

        if ($result['success']) {
            $contact->add_note(sprintf('Bulk SMS sent via Gabochie: %s', wp_trim_words($message, 15)), 'sms', 'Gabochie SMS');
            $sent++;
        } else {
            $failed++;
            $errors[] = "Contact #$cid: {$result['error']}";
        }
    }

    $msg = "Sent: $sent, Failed: $failed";
    if ($sent > 0) {
        wp_send_json_success(['message' => $msg, 'errors' => $errors]);
    } else {
        wp_send_json_error(['message' => $msg, 'errors' => $errors]);
    }
});

// --- Bulk action AJAX handler (store IDs, return redirect marker) ---

add_filter('fluent_crm/contact_bulk_action_gs_send_sms', function ($response, array $subscriber_ids, array $data) {
    $hash = wp_hash(implode(',', $subscriber_ids) . time());
    $key = 'gs_sms_bulk_' . $hash;
    set_transient($key, $subscriber_ids, 300);

    return [
        'message' => 'Redirecting...###GABOCHIE_SMS_REDIRECT###' . $hash,
    ];
}, 10, 3);

// --- SMS log helpers ---

function gs_fluent_log_sms(int $contact_id, string $phone, string $message, string $status, string $error = ''): void {
    global $wpdb;
    $table = $wpdb->prefix . 'gs_sms_log';
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        contact_id BIGINT NOT NULL,
        phone VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'sent',
        error TEXT DEFAULT '',
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (contact_id)
    )");

    $wpdb->insert($table, [
        'contact_id' => $contact_id,
        'phone'      => $phone,
        'message'    => $message,
        'status'     => $status,
        'error'      => $error,
        'sent_at'    => current_time('mysql'),
    ]);
}

function gs_fluent_get_sms_logs(int $contact_id, int $limit = 50): array {
    global $wpdb;
    $table = $wpdb->prefix . 'gs_sms_log';
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$exists) return [];

    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM {$table} WHERE contact_id = %d ORDER BY sent_at DESC LIMIT %d", $contact_id, $limit),
        ARRAY_A
    );
}

// --- Enqueue JS ---

add_action('admin_enqueue_scripts', function (string $hook) {
    if (!str_contains($hook, 'fluentcrm') && !str_contains($hook, 'gabochie-sms')) return;

    wp_add_inline_script('jquery', '
    function gs_send_sms(contact_id) {
        var msg = jQuery("#gs-sms-message-" + contact_id).val();
        var $result = jQuery("#gs-sms-result-" + contact_id);
        if (!msg || msg.trim() === "") { $result.html("<span style=\"color:#d63638\">Message is empty</span>"); return; }
        $result.html("Sending...");
        jQuery.post(ajaxurl, {
            action: "gs_send_sms",
            contact_id: contact_id,
            message: msg,
            nonce: "' . wp_create_nonce('gs_sms_nonce') . '"
        }, function(res) {
            if (res.success) {
                $result.html("<span style=\"color:#2d8f5e\">\u2713 Sent</span>");
                jQuery("#gs-sms-message-" + contact_id).val("");
            } else {
                $result.html("<span style=\"color:#d63638\">\u2717 " + res.data.message + "</span>");
            }
        });
    }

    // Watch for FluentCRM bulk action redirect marker
    var observer = new MutationObserver(function() {
        var el = document.querySelector(".el-message__content, .el-notification__content");
        if (!el) return;
        var text = el.textContent || "";
        if (text.indexOf("###GABOCHIE_SMS_REDIRECT###") === -1) return;
        var hash = text.replace("Redirecting...###GABOCHIE_SMS_REDIRECT###", "").trim();
        if (hash) {
            window.location.href = ajaxurl.replace("admin-ajax.php", "admin.php?page=gabochie-sms-bulk&hash=" + hash);
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });
    ' . "\n" . 'jQuery(document).on("fcrm:bulk-action-complete", function(e, hash) {
        if (hash) window.location.href = ajaxurl.replace("admin-ajax.php", "admin.php?page=gabochie-sms-bulk&hash=" + hash);
    });
    ');
});

// --- Char count on SMS textarea ---
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if (!$screen || !str_contains($screen->id ?? '', 'fluentcrm')) return;
    ?>
    <script>
    jQuery(document).on('input', 'textarea[id^="gs-sms-message-"]', function() {
        var id = this.id.replace('gs-sms-message-', '');
        var len = this.value.length;
        var segs = Math.ceil(len / 160);
        jQuery('#gs-sms-char-count-' + id).text(len + ' / ' + (segs > 1 ? (segs * 160) : 160) + ' characters (' + segs + ' segment' + (segs > 1 ? 's' : '') + ')');
    });
    </script>
    <?php
});

// --- Admin page for bulk SMS ---

add_action('admin_menu', function () {
    add_submenu_page(
        null,
        'Bulk SMS',
        'Bulk SMS',
        'manage_options',
        'gabochie-sms-bulk',
        'gs_fluent_bulk_sms_page'
    );
});

function gs_fluent_bulk_sms_page() {
    if (!current_user_can('manage_options')) return;

    $ids = [];
    if (!empty($_GET['hash'])) {
        $key = 'gs_sms_bulk_' . sanitize_key($_GET['hash']);
        $ids = get_transient($key);
        if (!empty($ids)) {
            delete_transient($key);
        }
    }
    if (empty($ids) && !empty($_GET['ids'])) {
        $ids = array_map('intval', explode(',', $_GET['ids']));
    }
    if (empty($ids)) {
        echo '<div class="wrap"><h1>Bulk SMS</h1><p>No contacts selected. Go to FluentCRM → Contacts, select contacts, and use "Send SMS (Gabochie)" bulk action.</p></div>';
        return;
    }

    $contacts = \FluentCrm\App\Models\Subscriber::whereIn('id', $ids)->get();
    $has_phone = $contacts->filter(fn($c) => !empty($c->phone));
    $no_phone = $contacts->filter(fn($c) => empty($c->phone));
    ?>
    <div class="wrap">
        <h1>Bulk SMS — <?php echo count($contacts); ?> Contacts</h1>
        <p><?php echo $has_phone->count(); ?> with phone numbers, <?php echo $no_phone->count(); ?> without.</p>

        <?php if ($no_phone->isNotEmpty()) : ?>
        <div class="notice notice-warning">
            <p>These contacts have no phone number and will be skipped:</p>
            <ul><?php foreach ($no_phone as $c) echo '<li>' . esc_html($c->first_name ?: $c->email) . '</li>'; ?></ul>
        </div>
        <?php endif; ?>

        <div style="max-width:600px;margin-top:20px">
            <div class="gs-section" style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:20px">
                <h3>Compose SMS</h3>
                <p style="font-size:13px;color:#555">Message will be sent to <strong><?php echo $has_phone->count(); ?></strong> recipients.</p>
                <textarea id="gs-bulk-message" style="width:100%;min-height:120px;padding:10px;font-size:14px" placeholder="Type your SMS message..."></textarea>
                <p style="font-size:12px;color:#787c82" id="gs-bulk-char-count">0 / 160 characters</p>
                <button class="button button-primary" onclick="gs_send_bulk_sms()">Send SMS to <?php echo $has_phone->count(); ?> Contacts</button>
                <span id="gs-bulk-result" style="margin-left:10px;font-size:13px"></span>
            </div>
        </div>
    </div>

    <script>
    var gs_bulk_ids = <?php echo wp_json_encode($has_phone->pluck('id')->toArray()); ?>;
    function gs_send_bulk_sms() {
        var msg = jQuery("#gs-bulk-message").val();
        var $result = jQuery("#gs-bulk-result");
        if (!msg || msg.trim() === "") { $result.html("<span style=\"color:#d63638\">Message is empty</span>"); return; }
        if (!confirm("Send SMS to " + gs_bulk_ids.length + " contacts?")) return;
        $result.html("Sending...");
        jQuery.post(ajaxurl, {
            action: "gs_bulk_send_sms",
            contact_ids: gs_bulk_ids,
            message: msg,
            nonce: "<?php echo wp_create_nonce('gs_sms_nonce'); ?>"
        }, function(res) {
            if (res.success) {
                $result.html("<span style=\"color:#2d8f5e\">✓ " + res.data.message + "</span>");
            } else {
                $result.html("<span style=\"color:#d63638\">✗ " + res.data.message + "</span>");
            }
        });
    }
    jQuery(document).on('input', '#gs-bulk-message', function() {
        var len = this.value.length;
        var segs = Math.ceil(len / 160);
        jQuery('#gs-bulk-char-count').text(len + ' / ' + (segs > 1 ? (segs * 160) : 160) + ' chars (' + segs + ' segment' + (segs > 1 ? 's' : '') + ')');
    });
    </script>
    <?php
}

// --- Register SMS settings ---

add_filter('gs_admin_sections', function (array $sections) {
    $sms = new GS_SMS();
    $connected = $sms->is_configured();
    $credits = $connected ? $sms->get_credits() : null;

    $sections['gs_sms'] = [
        'title' => 'SMS Outreach (Brevo)',
        'intro' => 'Send SMS to FluentCRM contacts via Brevo. Messages are logged per contact.',
        'fields' => [
            'gs_sms_brevo_key' => [
                'label' => 'Brevo API Key',
                'type'  => 'info',
                'value' => $connected ? 'Connected (from Brevo settings)' : 'NOT CONFIGURED — go to Brevo settings',
            ],
            'gs_sms_credits' => [
                'label' => 'SMS Credits',
                'type'  => 'info',
                'value' => $credits !== null ? number_format($credits) : 'Unknown',
            ],
            'gs_sms_sender' => [
                'label' => 'SMS Sender Name',
                'type'  => 'text',
                'hint'  => 'Alphanumeric sender ID (max 11 chars). Shown as the sender on recipient phones.',
                'default' => 'Gabochie',
            ],
            'gs_sms_total_sent' => [
                'label' => 'Total SMS Sent',
                'type'  => 'info',
                'value' => gs_fluent_total_sms_sent() . ' messages',
            ],
        ],
        'actions' => [
            [
                'label' => '📱 Test SMS Connection',
                'url'   => add_query_arg('gs_sms_test', '1'),
                'class' => 'button-secondary',
            ],
        ],
    ];
    return $sections;
});

function gs_fluent_total_sms_sent(): int {
    global $wpdb;
    $table = $wpdb->prefix . 'gs_sms_log';
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$exists) return 0;
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
}

// --- Test SMS connection ---

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['gs_sms_test'])) return;

    $sms = new GS_SMS();
    $result = $sms->test_connection();

    add_action('admin_notices', function () use ($result) {
        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>📱 SMS Test: ' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>📱 SMS Test Failed: ' . esc_html($result['error']) . '</p></div>';
        }
    });
});
