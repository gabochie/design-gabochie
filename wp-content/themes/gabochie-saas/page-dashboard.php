<?php
/**
 * Template Name: Portal
 * Production-grade member portal for Gabochie ecosystem users.
 */
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$cu = wp_get_current_user();
$tier = gs_member_tier();
$usage = gs_member_usage();
$section = gs_current_section();
$first_name = $cu->first_name ?: $cu->display_name;
$is_pro = ($tier !== 'free');

gs_seed_demo_documents();

/* ── POST-REDIRECT-GET: Handle form submissions ── */
$status_msg = '';
$status_type = 'success';
if (!empty($_GET['gs_status'])) {
    $map = [
        'saved'     => ['✅ Document generated and saved to your vault.', 'success'],
        'deleted'   => ['🗑️ Document deleted.', 'success'],
        'profile'   => ['✅ Profile updated successfully.', 'success'],
        'invited'   => ['📧 Team member invited.', 'success'],
        'removed'   => ['👋 Team member removed.', 'success'],
        'upgraded'  => ['🎉 Welcome to Pro! Your account has been upgraded.', 'success'],
        'cancelled' => ['Your plan has been cancelled. You retain access until end of billing period.', 'success'],
        'nonce'     => ['Security check failed. Please try again.', 'error'],
        'limit'     => ['You\'ve reached your usage limit. Upgrade to continue.', 'error'],
        'error'     => ['Something went wrong. If this persists, contact support.', 'error'],
    ];
    $s = sanitize_key($_GET['gs_status']);
    if (isset($map[$s])) {
        $status_msg = $map[$s][0];
        $status_type = $map[$s][1];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect = remove_query_arg('gs_status', wp_get_referer() ?: home_url('/dashboard/'));
    $redirect = add_query_arg('section', $section, $redirect);

    /* Generate tool output */
    if (isset($_POST['gs_generate_tool'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gs_generate_tool')) {
            wp_redirect(add_query_arg('gs_status', 'nonce', $redirect));
            exit;
        }
        if ($usage['remaining'] <= 0 && !$is_pro) {
            wp_redirect(add_query_arg('gs_status', 'limit', $redirect));
            exit;
        }
        $tool_id = sanitize_key($_POST['gs_tool_id'] ?? '');
        $tool_name = '';
        foreach (gs_tool_definitions() as $t) {
            if ($t['id'] === $tool_id) { $tool_name = $t['name']; break; }
        }
        $doc_title = sanitize_text_field($_POST['gs_doc_title'] ?? $tool_name . ' — ' . date('M j, Y'));
        $fields = [];
        foreach ($_POST as $k => $v) {
            if (strpos($k, 'gs_f_') === 0) {
                $fields[substr($k, 5)] = sanitize_text_field($v);
            }
        }
        $output = gs_generate_mock_output($tool_id, $fields);
        gs_save_document([
            'title'   => $doc_title,
            'type'    => $tool_name,
            'status'  => 'final',
            'summary' => $fields['project_name'] ?? $fields['building_type'] ?? $fields['property_type'] ?? 'Generated ' . date('M j, Y'),
            'fields'  => $fields,
            'output'  => $output,
        ]);
        gs_increment_usage();
        wp_redirect(add_query_arg('gs_status', 'saved', remove_query_arg('gs_status', $redirect)));
        exit;
    }

    /* Delete document */
    if (isset($_POST['gs_delete_doc'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gs_delete_doc')) {
            wp_redirect(add_query_arg('gs_status', 'nonce', $redirect));
            exit;
        }
        $doc_id = sanitize_key($_POST['gs_doc_id'] ?? '');
        if ($doc_id) {
            gs_delete_document($doc_id);
        }
        wp_redirect(add_query_arg('gs_status', 'deleted', remove_query_arg('gs_status', $redirect)));
        exit;
    }

    /* Update profile */
    if (isset($_POST['gs_update_profile'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gs_update_profile')) {
            wp_redirect(add_query_arg('gs_status', 'nonce', $redirect));
            exit;
        }
        update_user_meta($cu->ID, 'first_name', sanitize_text_field($_POST['gs_first_name'] ?? ''));
        update_user_meta($cu->ID, 'last_name', sanitize_text_field($_POST['gs_last_name'] ?? ''));
        update_user_meta($cu->ID, '_gs_firm', sanitize_text_field($_POST['gs_firm'] ?? ''));
        update_user_meta($cu->ID, '_gs_title', sanitize_text_field($_POST['gs_title'] ?? ''));
        update_user_meta($cu->ID, '_gs_phone', sanitize_text_field($_POST['gs_phone'] ?? ''));
        wp_update_user([
            'ID'           => $cu->ID,
            'display_name' => sanitize_text_field($_POST['gs_display_name'] ?? $cu->display_name),
        ]);
        wp_redirect(add_query_arg('gs_status', 'profile', remove_query_arg('gs_status', $redirect)));
        exit;
    }

    /* Invite team member */
    if (isset($_POST['gs_invite_team'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gs_invite_team')) {
            wp_redirect(add_query_arg('gs_status', 'nonce', $redirect));
            exit;
        }
        $email = sanitize_email($_POST['gs_team_email'] ?? '');
        if (is_email($email)) {
            gs_invite_team_member($email);
            /* Attempt real email — silent on failure, the meta save is what matters */
            $from_name = get_option('gs_from_name', 'Gabochie SaaS');
            $from_email = get_option('gs_from_email', 'noreply@tools.gabochie.com');
            $subject = sprintf('[Gabochie SaaS] %s has invited you to join their team', $cu->display_name);
            $message = sprintf(
                "Hi,\n\n%s has invited you to join their team on Gabochie SaaS.\n\nClick here to accept: %s\n\n– %s",
                $cu->display_name,
                home_url('/dashboard/?section=team'),
                $from_name
            );
            $headers = ['From: ' . $from_name . ' <' . $from_email . '>'];
            wp_mail($email, $subject, $message, $headers);
        }
        wp_redirect(add_query_arg('gs_status', 'invited', remove_query_arg('gs_status', $redirect)));
        exit;
    }

    /* Remove team member */
    if (isset($_POST['gs_remove_team'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gs_remove_team')) {
            wp_redirect(add_query_arg('gs_status', 'nonce', $redirect));
            exit;
        }
        gs_remove_team_member(sanitize_email($_POST['gs_team_email'] ?? ''));
        wp_redirect(add_query_arg('gs_status', 'removed', remove_query_arg('gs_status', $redirect)));
        exit;
    }
}
?>

<div class="dash-layout" role="main" aria-label="Member portal">
  <aside class="dash-sidebar" role="navigation" aria-label="Portal navigation">
    <?php echo do_shortcode('[dash_menu]'); ?>
  </aside>

  <main class="dash-content" id="portal-content">
    <?php if ($status_msg) : ?>
    <div class="portal-notice show <?php echo esc_attr($status_type); ?>" role="alert">
      <?php echo esc_html($status_msg); ?>
    </div>
    <?php endif; ?>

    <?php if (!$is_pro) : ?>
    <div class="dash-upgrade-banner" role="note">
      <div class="dub-text">
        You're on the <strong>Free plan</strong> — <?php echo esc_html($usage['remaining']); ?> tool use<?php echo $usage['remaining'] !== 1 ? 's' : ''; ?> left this month.
        <a href="/pricing/" style="color:var(--gold-light);text-decoration:underline">Upgrade to Pro</a> for unlimited access.
      </div>
      <a href="/pricing/" class="dub-btn" aria-label="Upgrade to Pro plan">Upgrade →</a>
    </div>
    <?php endif; ?>

    <?php if ($section === 'overview') : ?>
    <!-- ═══════════ DASHBOARD HOME ═══════════ -->
    <div class="dash-greeting">
      <h1>Welcome back, <?php echo esc_html($first_name); ?></h1>
      <p><?php echo esc_html(date('l, F j')); ?> — Here's your activity snapshot.</p>
    </div>
    <div class="dash-stats">
      <div class="dash-stat"><div class="ds-n"><?php echo esc_html($usage['used']); ?></div><div class="ds-l">Tools Used</div><div class="ds-t"><?php echo $usage['limit'] === 999 ? 'Unlimited' : esc_html($usage['limit']) . ' limit'; ?></div></div>
      <div class="dash-stat"><div class="ds-n"><?php echo esc_html($usage['remaining']); ?></div><div class="ds-l">Remaining</div><div class="ds-t"><?php echo $usage['limit'] === 999 ? '∞' : 'Resets next month'; ?></div></div>
      <div class="dash-stat"><div class="ds-n"><?php echo esc_html(ucfirst($tier)); ?></div><div class="ds-l">Plan</div><div class="ds-t"><?php echo $is_pro ? 'Active' : 'Upgrade for more'; ?></div></div>
      <div class="dash-stat"><div class="ds-n"><?php echo esc_html(count(gs_get_documents())); ?></div><div class="ds-l">Documents</div><div class="ds-t">In your vault</div></div>
    </div>

    <h2 class="dash-section-title">Quick Access</h2>
    <div class="dash-quick">
      <a href="/dashboard/?section=tools" class="dash-qcard" aria-label="Start a cost estimate"><div class="dq-icon">🏗️</div><div class="dq-name">Cost Calculator</div><div class="dq-desc">Start a new estimate</div></a>
      <a href="/dashboard/?section=tools" class="dash-qcard" aria-label="Analyze an investment"><div class="dq-icon">📈</div><div class="dq-name">ROI Projector</div><div class="dq-desc">Analyze investment</div></a>
      <a href="/dashboard/?section=tools" class="dash-qcard" aria-label="Generate a proposal"><div class="dq-icon">📋</div><div class="dq-name">DevProposal</div><div class="dq-desc">Generate proposal</div></a>
      <a href="/dashboard/?section=documents" class="dash-qcard" aria-label="View your document vault"><div class="dq-icon">📄</div><div class="dq-name">Documents</div><div class="dq-desc">View your vault</div></a>
    </div>

    <h2 class="dash-section-title">Recent Documents</h2>
    <div class="dash-activity">
      <?php $docs = gs_get_documents(); if (count($docs) > 0) : $i = 0; foreach ($docs as $d) : if ($i++ >= 5) break; ?>
      <a href="/dashboard/?section=documents&amp;doc=<?php echo esc_attr($d['id']); ?>" class="da-item" style="text-decoration:none;color:inherit">
        <div class="da-icon" aria-hidden="true"><?php
          $t = $d['type'] ?? '';
          echo strpos($t, 'Cost') !== false ? '🏗️' : (strpos($t, 'ROI') !== false ? '📈' : (strpos($t, 'Proposal') !== false ? '📋' : '📄'));
        ?></div>
        <div class="da-info">
          <div class="da-t"><?php echo esc_html($d['title']); ?></div>
          <div class="da-d"><?php echo esc_html(wp_trim_words($d['summary'] ?? '', 10)); ?></div>
        </div>
        <div class="da-date"><?php echo esc_html(date('M j', strtotime($d['created']))); ?></div>
      </a>
      <?php endforeach; else : ?>
      <div class="da-item"><div class="da-icon" aria-hidden="true">🚀</div><div class="da-info"><div class="da-t">No documents yet</div><div class="da-d">Generate your first tool output to see it here.</div></div></div>
      <?php endif; ?>
    </div>

    <?php elseif ($section === 'tools') : ?>
    <!-- ═══════════ TOOLS ═══════════ -->
    <?php
    $active_tool = isset($_GET['tool']) ? sanitize_key($_GET['tool']) : '';
    $tools = gs_tool_definitions();
    $fields_def = gs_tool_fields();
    ?>
    <div class="section-header">
      <div>
        <h2 id="tools-heading">🔧 Tools</h2>
        <div class="sh-sub">Select a tool to generate a professional output.</div>
      </div>
    </div>

    <?php if ($active_tool && $usage['remaining'] > 0) :
      $tool = null;
      foreach ($tools as $t) { if ($t['id'] === $active_tool) { $tool = $t; break; } }
      if (!$tool) { $tool = $tools[0]; }
      if ($active_tool === 'res-cost') :
        include GS_THEME_DIR . '/templates/tool-res-cost.php';
      else : ?>
    <div class="tool-runner">
      <a href="/dashboard/?section=tools" class="tr-back" aria-label="Back to all tools">← All Tools</a>
      <div style="display:flex;align-items:center;gap:10px">
        <span aria-hidden="true" style="font-size:24px"><?php echo esc_html($tool['icon']); ?></span>
        <h2><?php echo esc_html($tool['name']); ?></h2>
      </div>
      <div class="tr-meta">Fill in the details below. Your output will be ready in under 60 seconds.</div>
      <form class="tr-form" method="post" novalidate>
        <?php wp_nonce_field('gs_generate_tool'); ?>
        <input type="hidden" name="gs_tool_id" value="<?php echo esc_attr($tool['id']); ?>">
        <div class="tr-row">
          <label for="gs_doc_title">Document Title</label>
          <input type="text" id="gs_doc_title" name="gs_doc_title" value="<?php echo esc_attr($tool['name'] . ' — ' . date('M j, Y')); ?>" required>
        </div>
        <?php foreach ($tool['fields'] as $f) : if (isset($fields_def[$f])) : $fd = $fields_def[$f]; $fid = 'gs_f_' . $f; ?>
        <div class="tr-row">
          <label for="<?php echo esc_attr($fid); ?>"><?php echo esc_html($fd['label']); ?></label>
          <?php if ($fd['type'] === 'select' && !empty($fd['options'])) : ?>
          <select id="<?php echo esc_attr($fid); ?>" name="gs_f_<?php echo esc_attr($f); ?>">
            <?php foreach ($fd['options'] as $o) : printf('<option value="%s"%s>%s</option>', esc_attr($o), selected($fd['default'] ?? '', $o, false), esc_html($o)); endforeach; ?>
          </select>
          <?php elseif ($fd['type'] === 'textarea') : ?>
          <textarea id="<?php echo esc_attr($fid); ?>" name="gs_f_<?php echo esc_attr($f); ?>" placeholder="Enter <?php echo esc_attr($fd['label']); ?>..." rows="3"></textarea>
          <?php else : ?>
          <input type="<?php echo esc_attr($fd['type']); ?>" id="<?php echo esc_attr($fid); ?>" name="gs_f_<?php echo esc_attr($f); ?>" value="<?php echo esc_attr($fd['default'] ?? ''); ?>" <?php echo $fd['type'] === 'number' ? 'min="0" step="any"' : ''; ?>>
          <?php endif; ?>
        </div>
        <?php endif; endforeach; ?>
        <button type="submit" name="gs_generate_tool" class="tr-submit">Generate Output →</button>
      </form>
    </div>
    <?php endif; elseif ($usage['remaining'] <= 0 && $active_tool) : ?>
    <div class="portal-empty">
      <div class="pe-icon" aria-hidden="true">⛔</div>
      <div class="pe-title">Usage Limit Reached</div>
      <div class="pe-desc">You've used all <?php echo esc_html($usage['limit']); ?> free tool uses this month. Upgrade to Pro for unlimited access.</div>
      <a href="/pricing/" class="btn-primary">Upgrade to Pro →</a>
    </div>
    <?php else : ?>
    <div class="tool-select" role="list" aria-labelledby="tools-heading">
      <?php foreach ($tools as $t) : $disabled = ($usage['remaining'] <= 0 && !$is_pro); ?>
      <a href="/dashboard/?section=tools&amp;tool=<?php echo esc_attr($t['id']); ?>" class="ts-card" role="listitem" aria-label="<?php echo esc_attr($t['name']); ?>" tabindex="<?php echo $disabled ? '-1' : '0'; ?>" style="<?php echo $disabled ? 'opacity:.4;pointer-events:none' : ''; ?>">
        <div class="ts-icon" aria-hidden="true"><?php echo esc_html($t['icon']); ?></div>
        <div class="ts-name"><?php echo esc_html($t['name']); ?></div>
        <div class="ts-cat"><?php echo esc_html($t['cat']); ?></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php if (!$is_pro && $usage['remaining'] <= 0) : ?>
    <div class="portal-empty" style="margin-top:1.5rem">
      <div class="pe-icon" aria-hidden="true">📊</div>
      <div class="pe-title">You've Exhausted Free Usage</div>
      <div class="pe-desc">Upgrade to Pro for unlimited access to all 8 tools.</div>
      <a href="/pricing/" class="btn-primary">Upgrade to Pro →</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php elseif ($section === 'documents') : ?>
    <!-- ═══════════ DOCUMENTS ═══════════ -->
    <?php
    $doc_id = isset($_GET['doc']) ? sanitize_key($_GET['doc']) : '';
    $docs = gs_get_documents();
    ?>
    <div class="section-header">
      <div>
        <h2 id="documents-heading">📄 Document Vault</h2>
        <div class="sh-sub">All your generated proposals, estimates, and reports.</div>
      </div>
      <div>
        <a href="/dashboard/?section=tools" class="btn-primary" style="padding:9px 18px;font-size:13px" aria-label="Generate new document">+ New Document</a>
      </div>
    </div>

    <?php if ($doc_id) : $doc = gs_get_document($doc_id); if ($doc) : ?>
    <div class="doc-detail">
      <a href="/dashboard/?section=documents" class="tr-back" aria-label="Back to all documents">← All Documents</a>
      <div class="dd-header">
        <div class="dd-type"><?php echo esc_html($doc['type'] ?? 'Document'); ?></div>
        <h2><?php echo esc_html($doc['title']); ?></h2>
      </div>
      <div class="dd-meta">
        <span>📅 Created: <?php echo esc_html(date('F j, Y g:i a', strtotime($doc['created']))); ?></span>
        <span>📊 Status: <span style="color:var(--color-pro)"><?php echo esc_html(ucfirst($doc['status'] ?? 'final')); ?></span></span>
      </div>
      <div class="dd-body"><?php echo esc_html($doc['output'] ?? $doc['summary'] ?? 'No content'); ?></div>
      <div class="dd-actions">
        <button class="tr-submit" onclick="alert('PDF export will be available on Pro. Upgrade to unlock.');" aria-label="Download PDF (Pro feature)">Download PDF</button>
        <button class="tr-submit" style="background:transparent;border:1px solid var(--border-mid);color:var(--text-body)" onclick="alert('DOCX export will be available on Pro. Upgrade to unlock.');" aria-label="Export DOCX (Pro feature)">Export DOCX</button>
        <form method="post" style="display:inline" onsubmit="return confirm('Permanently delete this document?')">
          <?php wp_nonce_field('gs_delete_doc'); ?>
          <input type="hidden" name="gs_doc_id" value="<?php echo esc_attr($doc['id']); ?>">
          <button type="submit" name="gs_delete_doc" class="tr-submit" style="background:rgba(200,50,50,.2);border:1px solid rgba(200,50,50,.3);color:#E06060">Delete</button>
        </form>
      </div>
    </div>
    <?php else : ?><div class="portal-empty"><div class="pe-icon" aria-hidden="true">🔍</div><div class="pe-title">Document Not Found</div><a href="/dashboard/?section=documents" class="btn-primary">Back to Vault</a></div><?php endif; return; endif; ?>

    <div class="doc-filters" role="tablist" aria-label="Filter documents by status">
      <button class="filter-chip on" role="tab" aria-selected="true" onclick="filterDocs('all',this)">All</button>
      <button class="filter-chip" role="tab" aria-selected="false" onclick="filterDocs('final',this)">Final</button>
      <button class="filter-chip" role="tab" aria-selected="false" onclick="filterDocs('draft',this)">Drafts</button>
    </div>

    <?php if (count($docs) > 0) : ?>
    <div class="doc-list" role="list" aria-labelledby="documents-heading">
      <?php foreach ($docs as $d) :
        $t = $d['type'] ?? '';
        $icon = strpos($t, 'Cost') !== false ? '🏗️' : (strpos($t, 'ROI') !== false ? '📈' : (strpos($t, 'Proposal') !== false ? '📋' : (strpos($t, 'Feasibility') !== false ? '📊' : (strpos($t, 'JV') !== false ? '🤝' : '📄'))));
      ?>
      <a href="/dashboard/?section=documents&amp;doc=<?php echo esc_attr($d['id']); ?>" class="doc-row" role="listitem" data-status="<?php echo esc_attr($d['status'] ?? 'final'); ?>">
        <div class="doc-icon" aria-hidden="true"><?php echo $icon; ?></div>
        <div class="doc-info">
          <div class="doc-t"><?php echo esc_html($d['title']); ?></div>
          <div class="doc-d"><?php echo esc_html(wp_trim_words($d['summary'] ?? '', 10)); ?></div>
        </div>
        <div class="doc-meta">
          <div class="doc-status <?php echo esc_attr($d['status'] ?? 'final'); ?>"><?php echo esc_html(ucfirst($d['status'] ?? 'final')); ?></div>
          <div class="doc-date"><?php echo esc_html(date('M j', strtotime($d['created']))); ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="portal-empty">
      <div class="pe-icon" aria-hidden="true">📂</div>
      <div class="pe-title">Your vault is empty</div>
      <div class="pe-desc">Generate your first proposal, estimate, or report to see it here.</div>
      <a href="/dashboard/?section=tools" class="btn-primary">Start a Tool →</a>
    </div>
    <?php endif; ?>

    <?php elseif ($section === 'billing') : ?>
    <!-- ═══════════ BILLING ═══════════ -->
    <?php $plan = gs_user_plan(); $invoices = gs_get_invoices(); ?>
    <div class="section-header">
      <div>
        <h2 id="billing-heading">💳 Billing &amp; Subscription</h2>
        <div class="sh-sub">Manage your plan, payment methods, and invoices.</div>
      </div>
    </div>

    <div class="billing-grid" aria-labelledby="billing-heading">
      <div class="bc-card">
        <div class="bc-label">Current Plan</div>
        <div class="bc-value"><?php echo esc_html($plan['name']); ?></div>
        <div class="bc-sub"><?php echo $plan['price'] > 0 ? '$' . esc_html($plan['price']) . '/mo' : 'Free forever'; ?></div>
      </div>
      <div class="bc-card">
        <div class="bc-label">Usage This Month</div>
        <div class="bc-value"><?php echo esc_html($usage['used']); ?><span style="font-size:14px;font-weight:400;color:var(--text-dim)"> / <?php echo $usage['limit'] === 999 ? '∞' : esc_html($usage['limit']); ?></span></div>
        <div class="dash-usage-bar" style="margin-top:6px;height:6px" aria-label="<?php echo esc_attr($usage['used'] . ' of ' . ($usage['limit'] === 999 ? 'unlimited' : $usage['limit']) . ' tools used'); ?>"><div class="dash-usage-fill" style="width:<?php echo esc_attr(min(100, ($usage['used'] / max(1, $usage['limit'])) * 100)); ?>%"></div></div>
      </div>
      <div class="bc-card">
        <div class="bc-label">Next Billing</div>
        <div class="bc-value"><?php echo esc_html($plan['next_billing'] ?? '—'); ?></div>
        <div class="bc-sub"><?php echo $plan['price'] > 0 ? 'Auto-renew monthly' : 'No upcoming charges'; ?></div>
      </div>
      <div class="bc-card">
        <div class="bc-label">Payment Method</div>
        <div class="bc-value" style="font-size:1rem">💳 •••• 4242</div>
        <div class="bc-sub">Expires 12/26</div>
        <div class="bc-action"><a href="/dashboard/?section=billing" class="bc-ghost" aria-label="Update payment method">Update</a></div>
      </div>
      <div class="bc-card bc-wide">
        <div class="bc-action" style="display:flex;gap:8px;flex-wrap:wrap">
          <?php if ($tier === 'free') : ?>
            <form method="post" action="<?php echo esc_url(home_url('/dashboard/?section=billing')); ?>">
              <?php wp_nonce_field('gs_upgrade'); ?>
              <input type="hidden" name="gs_upgrade_action" value="upgrade">
              <input type="hidden" name="gs_plan" value="pro">
              <button type="submit" class="btn-primary" style="font-size:13px;padding:9px 20px;border:none;cursor:pointer">Upgrade to Pro →</button>
            </form>
          <?php else : ?>
            <form method="post" action="<?php echo esc_url(home_url('/dashboard/?section=billing')); ?>" style="display:inline">
              <?php wp_nonce_field('gs_upgrade'); ?>
              <input type="hidden" name="gs_upgrade_action" value="cancel">
              <button type="submit" class="bc-ghost" style="font-size:13px;padding:9px 20px;border:1px solid var(--border-mid);background:transparent;color:var(--text-body);border-radius:4px;cursor:pointer;font-family:var(--font-body)" onclick="return confirm('Cancel your subscription? You will lose Pro access at the end of the billing period.')">Cancel Plan</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (count($invoices) > 0) : ?>
    <div class="invoice-list">
      <h3>Invoice History</h3>
      <?php foreach ($invoices as $inv) : ?>
      <div class="invoice-row">
        <span><?php echo esc_html($inv['id']); ?></span>
        <span><?php echo esc_html($inv['date']); ?></span>
        <span><?php echo esc_html($inv['amount']); ?></span>
        <span class="ir-status <?php echo esc_attr($inv['status']); ?>"><?php echo esc_html(ucfirst($inv['status'])); ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php elseif ($section === 'profile') : ?>
    <!-- ═══════════ PROFILE ═══════════ -->
    <div class="section-header">
      <div>
        <h2 id="profile-heading">👤 Profile Settings</h2>
        <div class="sh-sub">Manage your account details and preferences.</div>
      </div>
    </div>

    <form class="profile-form" method="post" aria-labelledby="profile-heading">
      <?php wp_nonce_field('gs_update_profile'); ?>
      <div class="pf-avatar">
        <div class="pf-av" aria-hidden="true"><?php echo esc_html(strtoupper(substr($first_name, 0, 1))); ?></div>
        <div style="font-size:12px;color:var(--text-dim)">To update your photo, use <a href="https://gravatar.com" target="_blank" rel="noopener" style="color:var(--gs-300)">Gravatar</a>.</div>
      </div>
      <div class="pf-row"><label for="gs_first_name">First Name</label><input type="text" id="gs_first_name" name="gs_first_name" value="<?php echo esc_attr($cu->first_name); ?>"></div>
      <div class="pf-row"><label for="gs_last_name">Last Name</label><input type="text" id="gs_last_name" name="gs_last_name" value="<?php echo esc_attr($cu->last_name); ?>"></div>
      <div class="pf-row"><label for="gs_display_name">Display Name</label><input type="text" id="gs_display_name" name="gs_display_name" value="<?php echo esc_attr($cu->display_name); ?>"></div>
      <div class="pf-row">
        <label for="gs_email">Email</label>
        <input type="email" id="gs_email" value="<?php echo esc_attr($cu->user_email); ?>" disabled style="opacity:.5">
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px">To change your email, contact <a href="mailto:<?php echo esc_attr(get_option('gs_support_email', 'support@gabochie.com')); ?>" style="color:var(--gs-300)"><?php echo esc_html(get_option('gs_support_email', 'support@gabochie.com')); ?></a>.</div>
      </div>
      <div class="pf-row"><label for="gs_firm">Firm / Company</label><input type="text" id="gs_firm" name="gs_firm" value="<?php echo esc_attr(get_user_meta($cu->ID, '_gs_firm', true)); ?>" placeholder="e.g. Kwame Architecture Studio"></div>
      <div class="pf-row"><label for="gs_title">Professional Title</label><input type="text" id="gs_title" name="gs_title" value="<?php echo esc_attr(get_user_meta($cu->ID, '_gs_title', true)); ?>" placeholder="e.g. Principal Architect"></div>
      <div class="pf-row"><label for="gs_phone">Phone</label><input type="text" id="gs_phone" name="gs_phone" value="<?php echo esc_attr(get_user_meta($cu->ID, '_gs_phone', true)); ?>" placeholder="+233 XX XXX XXXX"></div>
      <div class="pf-row"><label>Member Since</label><div style="font-size:14px;color:var(--text-dim)"><?php echo esc_html(gs_member_since()); ?></div></div>
      <button type="submit" name="gs_update_profile" class="pf-save">Save Changes</button>
    </form>

    <?php elseif ($section === 'team') : ?>
    <!-- ═══════════ TEAM ═══════════ -->
    <?php if ($tier === 'free') : ?>
    <div class="portal-empty">
      <div class="pe-icon" aria-hidden="true">👥</div>
      <div class="pe-title">Team Management</div>
      <div class="pe-desc">Team features are available on Pro and Enterprise plans. Upgrade to invite colleagues and collaborate.</div>
      <a href="/pricing/" class="btn-primary">Upgrade to Pro →</a>
    </div>
    <?php else :
    $members = gs_team_members();
    $seat_limit = $tier === 'enterprise' ? 999 : 3;
    $seats_left = max(0, $seat_limit - count($members));
    ?>
    <div class="section-header">
      <div>
        <h2 id="team-heading">👥 Team Management</h2>
        <div class="sh-sub"><?php echo $tier === 'enterprise' ? 'Unlimited seats.' : sprintf('%d of %d seats used.', count($members), $seat_limit); ?> Invite colleagues to your workspace.</div>
      </div>
    </div>

    <?php if ($seats_left > 0) : ?>
    <div class="team-form">
      <h3>Invite Team Member</h3>
      <form method="post" class="tf-row" aria-labelledby="team-heading">
        <?php wp_nonce_field('gs_invite_team'); ?>
        <input type="email" name="gs_team_email" placeholder="colleague@firm.com" required aria-label="Email address of team member to invite">
        <button type="submit" name="gs_invite_team">Invite</button>
      </form>
    </div>
    <?php else : ?>
    <div class="portal-notice show" style="display:block;background:rgba(201,150,42,.1);border-color:rgba(201,150,42,.25);color:var(--color-new)">You've reached your seat limit. Upgrade to Enterprise for unlimited seats.</div>
    <?php endif; ?>

    <?php if (count($members) > 0) : ?>
    <h3 style="font-family:var(--font-display);font-size:1rem;font-weight:700;margin-bottom:1rem">Current Members (<?php echo count($members); ?>)</h3>
    <?php foreach ($members as $m) : ?>
    <div class="team-member">
      <div class="tm-av" aria-hidden="true"><?php echo esc_html(strtoupper(substr($m['email'], 0, 1))); ?></div>
      <div class="tm-info">
        <div class="tm-email"><?php echo esc_html($m['email']); ?></div>
        <div class="tm-role"><?php echo esc_html($m['role']); ?> <span class="tm-status <?php echo esc_attr($m['status']); ?>"><?php echo esc_html(ucfirst($m['status'])); ?></span></div>
      </div>
      <form method="post" style="display:inline" onsubmit="return confirm('Remove <?php echo esc_js($m['email']); ?> from your team?')">
        <?php wp_nonce_field('gs_remove_team'); ?>
        <input type="hidden" name="gs_team_email" value="<?php echo esc_attr($m['email']); ?>">
        <button type="submit" name="gs_remove_team" class="tm-remove" aria-label="Remove <?php echo esc_attr($m['email']); ?>">✕</button>
      </form>
    </div>
    <?php endforeach; ?>
    <?php else : ?>
    <div class="portal-empty" style="padding:2rem 0"><div class="pe-title">No team members yet</div><div class="pe-desc">Invite your first colleague to get started.</div></div>
    <?php endif; ?>
    <?php endif; ?>

    <?php endif; ?>
  </main>
</div>

<script>
function filterDocs(status, btn) {
  document.querySelectorAll('.doc-filters .filter-chip').forEach(function(c) {
    c.classList.remove('on');
    c.setAttribute('aria-selected', 'false');
  });
  btn.classList.add('on');
  btn.setAttribute('aria-selected', 'true');
  document.querySelectorAll('.doc-row').forEach(function(row) {
    row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
  });
}
</script>

<?php get_footer(); ?>
