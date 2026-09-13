<?php
/**
 * Template Name: Client Dashboard (v2)
 * @package Gabochie_Value
 */

if (!is_user_logged_in()) { wp_redirect(home_url('/login/')); exit; }

get_header();
$user = wp_get_current_user();
$is_admin = in_array('administrator', $user->roles);
$user_id = $user->ID;
$rest_base = rest_url('gabochie/v1/');
$nonce = wp_create_nonce('wp_rest');

/* Allow deep-link ?project=ID for recovery workspace */
$forced_project_id = isset( $_GET['project'] ) ? (int) $_GET['project'] : 0;
if ( $forced_project_id ) {
	$forced = get_post( $forced_project_id );
	if ( $forced && 'client_project' === $forced->post_type && ( $is_admin || (int) $forced->post_author === (int) $user_id || ( function_exists( 'gabochie_recovery_can_access' ) && gabochie_recovery_can_access( $forced_project_id, $user_id ) ) ) ) {
		$latest_forced = $forced;
	}
}

/* Fetch user's latest project (or all for admin) */
$project_args = array(
    'post_type'      => 'client_project',
    'posts_per_page' => $is_admin ? 20 : 1,
    'orderby'        => 'date',
    'order'          => 'DESC',
);
if (!$is_admin) $project_args['author'] = $user_id;
$projects = get_posts($project_args);
$latest = !empty($projects) ? $projects[0] : null;
if ( isset( $latest_forced ) && $latest_forced ) {
	$latest = $latest_forced;
}
$is_recovery = $latest && function_exists( 'gabochie_recovery_is_active' ) && gabochie_recovery_is_active( $latest->ID );
$recovery_status = $is_recovery ? get_post_meta( $latest->ID, 'recovery_status', true ) : '';
$recovery_step   = $is_recovery && function_exists( 'gabochie_recovery_current_step' ) ? gabochie_recovery_current_step( $latest->ID ) : '';

/* Helper functions */
$statuses = function_exists('gabochie_value_project_statuses') ? gabochie_value_project_statuses() : array();
$stages   = function_exists('gabochie_value_project_stages') ? gabochie_value_project_stages() : array();
$all_stages = array_keys($stages);

function d2_project_stats($pid) {
    global $statuses, $stages, $all_stages;
    $status = get_post_meta($pid, 'project_status', true);
    $stage  = get_post_meta($pid, 'project_stage', true);
    $budget = (float) get_post_meta($pid, 'project_budget', true);
    $currency = get_post_meta($pid, 'project_currency', true) ?: 'GHS';
    $stage_idx = array_search($stage, $all_stages);
    $progress = $stage_idx !== false ? round(($stage_idx / (count($all_stages) - 1)) * 100) : 0;
    return array(
        'status'  => $statuses[$status] ?? ucfirst($status),
        'stage'   => $stages[$stage] ?? ucfirst($stage),
        'budget'  => $budget,
        'currency'=> $currency,
        'progress'=> $progress,
    );
}
function d2_milestone_counts($pid) {
    $miles = get_posts(array('post_type'=>'project_milestone','posts_per_page'=>-1,'meta_query'=>array(array('key'=>'project_id','value'=>$pid,'compare'=>'='))));
    $done = 0;
    foreach ($miles as $m) { if (get_post_meta($m->ID,'milestone_status',true)==='completed') $done++; }
    return array('total'=>count($miles),'completed'=>$done);
}
function d2_doc_stats($pid) {
    $docs = get_posts(array('post_type'=>'project_document','posts_per_page'=>-1,'meta_query'=>array(array('key'=>'project_id','value'=>$pid,'compare'=>'=')),'fields'=>'ids'));
    return count($docs);
}
function d2_message_stats($pid) {
    $msgs = get_posts(array('post_type'=>'project_message','posts_per_page'=>-1,'meta_query'=>array(array('key'=>'project_id','value'=>$pid,'compare'=>'='))));
    $unread = 0;
    foreach ($msgs as $m) {
        if (!get_post_meta($m->ID,'is_read',true) && !get_post_meta($m->ID,'is_from_client',true)) $unread++;
    }
    return array('total'=>count($msgs),'unread'=>$unread);
}
function d2_cost_total($pid) {
    $costs = get_posts(array('post_type'=>'project_cost_item','posts_per_page'=>-1,'meta_query'=>array(array('key'=>'project_id','value'=>$pid,'compare'=>'='))));
    $total = 0;
    foreach ($costs as $c) $total += (float) get_post_meta($c->ID,'amount',true);
    return $total;
}
?>



<section class="page-hero" style="padding-bottom:2rem">
    <div class="container" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
        <div>
            <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Dashboard</div>
            <h1>Welcome, <em><?php echo esc_html($user->display_name ?: $user->user_login); ?></em></h1>
            <p>Your project hub — track progress, review designs, and stay in the loop.</p>
        </div>
        <a href="<?php echo esc_url(wp_logout_url(home_url('/login/'))); ?>" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.6rem 1.2rem;border:1px solid rgba(250,247,242,0.3);border-radius:var(--r-sm);color:rgba(250,247,242,0.8);text-decoration:none;font-size:0.8rem;letter-spacing:0.05em;transition:all 0.2s;min-height:44px;white-space:nowrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:16px;height:16px"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
        </a>
    </div>
</section>

<div class="d2-wrap" id="d2-app" data-rest="<?php echo esc_attr($rest_base); ?>" data-nonce="<?php echo esc_attr($nonce); ?>" data-project="<?php echo $latest ? $latest->ID : 0; ?>" data-admin="<?php echo $is_admin ? '1' : '0'; ?>">

<?php
$current_level = function_exists('pmpro_getMembershipLevelForUser') ? pmpro_getMembershipLevelForUser($user->ID) : null;
$tier_class = 'free';
$tier_label = 'Free Discovery';
if ($current_level) {
    $tier_label = esc_html($current_level->name);
    $tier_class = ($current_level->id === 1) ? 'free' : 'active';
}
?>
<div class="d2-member-tier">
    <div class="d2-tier-info">
        <span class="d2-tier-label">Membership Tier</span>
        <span class="d2-tier-name"><?php echo $tier_label; ?></span>
    </div>
    <div class="d2-tier-badge mem-tier-<?php echo esc_attr($tier_class); ?>"><?php echo $tier_label; ?></div>
    <?php if ($current_level && $current_level->id !== 2) : ?>
        <a href="<?php echo esc_url(home_url('/membership-levels/')); ?>" class="d2-tier-upgrade">Upgrade &rarr;</a>
    <?php endif; ?>
</div>

<?php if (!$latest) : ?>
    <div class="d2-section"><div class="d2-section-body d2-empty">
        <div class="d2-empty-icon">&#128203;</div>
        <h3>No Project Yet</h3>
        <p>Start your journey by completing the onboarding form.</p>
        <a href="<?php echo esc_url(home_url('/onboarding/')); ?>" style="display:inline-block;margin-top:1rem;padding:.7rem 2rem;background:var(--teal);color:var(--white);border-radius:var(--r-sm);text-decoration:none;font-weight:600;font-size:.85rem">Start Onboarding</a>
    </div></div>
<?php else : ?>

<?php
    $stats = d2_project_stats($latest->ID);
    $m_counts = d2_milestone_counts($latest->ID);
    $doc_count = d2_doc_stats($latest->ID);
    $msg_stats = d2_message_stats($latest->ID);
    $total_cost = d2_cost_total($latest->ID);
?>

<!-- ═══ HERO STATS ═══ -->
<div class="d2-hero">
    <div class="d2-stat">
        <div class="d2-stat-label">Project Status</div>
        <div class="d2-stat-value"><?php echo esc_html($stats['status']); ?></div>
        <div class="d2-stat-sub">Stage: <?php echo esc_html($stats['stage']); ?></div>
    </div>
    <div class="d2-stat">
        <div class="d2-stat-label">Progress</div>
        <div class="d2-stat-value" style="color:var(--gold)"><?php echo $stats['progress']; ?>%</div>
        <div class="d2-progress"><div class="d2-progress-fill" style="width:<?php echo $stats['progress']; ?>%"></div></div>
    </div>
    <div class="d2-stat">
        <div class="d2-stat-label">Budget</div>
        <div class="d2-stat-value"><?php echo $stats['budget'] > 0 ? esc_html($stats['currency'] . ' ' . number_format($stats['budget'])) : '—'; ?></div>
        <div class="d2-stat-sub"><?php echo $total_cost > 0 ? 'Spent: ' . esc_html($stats['currency'] . ' ' . number_format($total_cost)) : 'No costs yet'; ?></div>
    </div>
    <div class="d2-stat">
        <div class="d2-stat-label">Activity</div>
        <div class="d2-stat-value" style="font-size:1.2rem"><?php echo $msg_stats['unread']; ?> unread</div>
        <div class="d2-stat-sub"><?php echo $m_counts['completed'] . '/' . $m_counts['total']; ?> milestones done &middot; <?php echo $doc_count; ?> docs</div>
    </div>
</div>

<!-- ═══ READINESS SCORE ═══ -->
<div class="d2-section gr-section" id="gr-readiness" style="margin-bottom:1.5rem">
    <div class="d2-section-head">
        <h3 style="display:flex;align-items:center;gap:.5rem">
            <span style="font-size:1.1rem">&#9679;</span> Development Readiness Score&trade;
        </h3>
        <span class="d2-badge" id="gr-overall-badge">Loading&hellip;</span>
    </div>
    <div class="d2-section-body" style="display:grid;grid-template-columns:140px 1fr;gap:1.5rem;align-items:start">
        <div id="gr-ring" style="text-align:center"></div>
        <div>
            <div id="gr-dimensions" style="margin-bottom:1rem"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--teal);margin-bottom:.4rem">Strengths</div>
                    <div id="gr-strengths"></div>
                </div>
                <div>
                    <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#E67E22;margin-bottom:.4rem">Top Gaps</div>
                    <div id="gr-weaknesses"></div>
                </div>
            </div>
            <div id="gr-upgrade" style="display:none;margin-top:1rem;padding:.75rem 1rem;background:rgba(201,169,110,.08);border-radius:8px;border:1px solid rgba(201,169,110,.2)">
                <a href="<?php echo esc_url(home_url('/membership-levels/')); ?>" style="color:var(--gold);font-weight:600;font-size:.82rem;text-decoration:none">
                    Unlock Full Analysis &rarr;
                </a>
                <div style="font-size:.72rem;color:var(--mist);margin-top:.2rem">Get detailed recommendations, cost analysis, and AI-powered insights</div>
            </div>
        </div>
    </div>
</div>

<?php if ( $is_recovery ) : ?>
<div class="d2-recovery-banner">
    <div><strong>🏗️ Real Estate Development — Project Recovery Workspace</strong><div class="d2-sub">Step-by-step: take charge of your stuck project. Facilitator can co-drive.</div></div>
    <span class="d2-badge gold"><?php echo esc_html( ucwords( str_replace( '_', ' ', $recovery_status ?: 'triage' ) ) ); ?></span>
</div>
<?php endif; ?>

<!-- ═══ TABS ═══ -->
<div class="d2-tabs">
    <?php if ( $is_recovery ) : ?><div class="d2-tab active" data-tab="recovery">Recovery <span class="d2-badge gold">NEW</span></div><?php endif; ?>
    <div class="d2-tab<?php echo $is_recovery ? '' : ' active'; ?>" data-tab="timeline">Timeline</div>
    <div class="d2-tab" data-tab="documents">Documents</div>
    <div class="d2-tab" data-tab="messages">Messages <?php if ($msg_stats['unread'] > 0) echo '<span class="d2-badge gold">' . $msg_stats['unread'] . '</span>'; ?></div>
    <div class="d2-tab" data-tab="audit">Activity Log</div>
    <div class="d2-tab" data-tab="costs">Costs</div>
</div>

<?php if ( $is_recovery ) : ?>
<!-- ═══ RECOVERY TAB (All Building Types — Ghana) ═══ -->
<div class="d2-tab-content active" data-tab="recovery" id="d2-recovery-tab">
    <div class="d2-section">
        <div class="d2-section-head">
            <h3>Recovery Workspace — All Building Types</h3>
            <span class="d2-badge gold" id="d2-rec-progress-badge">Loading…</span>
        </div>
        <div class="d2-section-body">
            <div style="display:flex;flex-wrap:wrap;gap:0.6rem;margin-bottom:1rem;font-size:0.78rem">
                <?php
                $rtype = get_post_meta( $latest->ID, 'project_type', true ) ?: get_post_meta( $latest->ID, 'property_type', true ) ?: '—';
                $rloc  = get_post_meta( $latest->ID, 'project_location', true ) ?: get_post_meta( $latest->ID, 'land_location', true ) ?: '—';
                $rblockers = get_post_meta( $latest->ID, 'rec_blockers', true ) ?: '—';
                ?>
                <span class="d2-badge teal">Type: <?php echo esc_html( ucwords( str_replace( '_', ' ', $rtype ) ) ); ?></span>
                <span class="d2-badge">Location: <?php echo esc_html( $rloc ); ?></span>
                <span class="d2-badge">Blockers: <?php echo esc_html( $rblockers ); ?></span>
                <?php $fid = (int) get_post_meta( $latest->ID, 'facilitator_id', true ); if ( $fid ) { $fu = get_userdata( $fid ); echo '<span class="d2-badge gold">Facilitator: ' . esc_html( $fu ? $fu->display_name : '#' . $fid ) . '</span>'; } ?>
            </div>
            <div class="d2-progress" style="margin-top:0"><div class="d2-progress-fill" id="d2-rec-progress-fill" style="width:0%"></div></div>
            <div id="d2-rec-stepper"></div>
            <div id="d2-rec-checklist"></div>
            <div class="rec-actions">
                <button id="d2-rec-advance" class="btn-gold" style="display:none">Advance to Next Step →</button>
                <button id="d2-rec-approve" style="display:none">Approve Plan (Owner)</button>
                <a id="d2-rec-pdf" href="#" target="_blank">📄 Download PDF Report</a>
            </div>
            <div id="d2-rec-invite">
                <div class="rec-invite-title">Invite Facilitator (co-drive this recovery)</div>
                <div class="rec-invite-row">
                    <input type="email" id="d2-rec-invite-email" placeholder="facilitator@email.com">
                    <input type="text" id="d2-rec-invite-name" placeholder="Name (optional)">
                    <button id="d2-rec-invite-btn">Invite</button>
                </div>
                <div id="d2-rec-invite-msg"></div>
            </div>
            <div id="d2-rec-msg"></div>
            <p class="rec-note">Works for all building types in Ghana — residential, commercial, mixed-use, renovation & interior. Your facilitator sees the same workspace and can co-drive with you.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ TIMELINE TAB ═══ -->
<div class="d2-tab-content<?php echo $is_recovery ? '' : ' active'; ?>" data-tab="timeline">
    <div class="d2-section">
        <div class="d2-section-head">
            <h3>14-Stage Timeline</h3>
            <span class="d2-badge teal"><?php echo $stats['progress']; ?>% Complete</span>
        </div>
        <div class="d2-section-body">
            <div class="d2-progress" style="margin-top:0"><div class="d2-progress-fill" style="width:<?php echo $stats['progress']; ?>%"></div></div>
            <div class="d2-timeline" id="d2-timeline">
                <?php
                $current_stage = get_post_meta($latest->ID, 'project_stage', true) ?: 'Discovery';
                $found = false;
                $milestones_all = get_posts(array('post_type'=>'project_milestone','posts_per_page'=>-1,'meta_query'=>array(array('key'=>'project_id','value'=>$latest->ID,'compare'=>'='))));
                $m_by_stage = array();
                foreach ($milestones_all as $m) { $s = get_post_meta($m->ID,'milestone_stage',true); $m_by_stage[$s][] = $m; }
                foreach ($all_stages as $i => $s) {
                    if ($s === $current_stage) $found = true;
                    $cls = $found ? ($s === $current_stage ? 'current' : 'done') : '';
                    $line_cls = $found && $s !== $current_stage ? ' done' : '';
                    $miles_in = isset($m_by_stage[$s]) ? $m_by_stage[$s] : array();
                    $m_count = count($miles_in);
                    $m_done = 0;
                    foreach ($miles_in as $m) { if (get_post_meta($m->ID,'milestone_status',true)==='completed') $m_done++; }
                ?>
                <div class="d2-tl-stage">
                    <div class="d2-tl-line<?php echo $line_cls; ?>"></div>
                    <div class="d2-tl-dot <?php echo $cls; ?>" title="<?php echo esc_attr($stages[$s]); ?>"></div>
                    <div class="d2-tl-label <?php echo $cls; ?>"><?php echo esc_html($stages[$s]); ?></div>
                    <?php if ($m_count > 0) : ?>
                        <div style="font-size:.55rem;color:var(--mist);margin-top:.15rem"><?php echo $m_done; ?>/<?php echo $m_count; ?></div>
                    <?php endif; ?>
                </div>
                <?php } /* end foreach all_stages */ ?>
            </div>

            <?php if (!empty($milestones_all)) : ?>
            <div style="margin-top:1.5rem">
                <h4 style="font-size:.9rem;margin-bottom:.75rem">Milestones</h4>
                <?php foreach ($milestones_all as $m) :
                    $m_status = get_post_meta($m->ID,'milestone_status',true);
                    $m_target = get_post_meta($m->ID,'target_date',true);
                    $m_cls = $m_status === 'completed' ? 'green' : ($m_status === 'overdue' ? 'red' : 'orange');
                ?>
                <div style="display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid var(--border-light);font-size:.85rem">
                    <span class="d2-badge <?php echo $m_cls; ?>"><?php echo esc_html(ucfirst($m_status)); ?></span>
                    <span style="flex:1;font-weight:500"><?php echo esc_html($m->post_title); ?></span>
                    <?php if ($m_target) : ?><span style="font-size:.75rem;color:var(--mist)"><?php echo esc_html($m_target); ?></span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══ DOCUMENTS TAB ═══ -->
<div class="d2-tab-content" data-tab="documents">
    <div class="d2-grid">
        <div class="d2-section" style="margin-bottom:0">
            <div class="d2-section-head">
                <h3>Document Vault</h3>
                <span class="d2-badge teal"><?php echo $doc_count; ?> files</span>
            </div>
            <div class="d2-section-body" id="d2-docs-list">
                <?php
                $docs = get_posts(array('post_type'=>'project_document','posts_per_page'=>-1,'meta_query'=>array(array('key'=>'project_id','value'=>$latest->ID,'compare'=>'=')),'orderby'=>'date','order'=>'DESC'));
                if (empty($docs)) :
                ?>
                <div class="d2-empty" style="padding:2rem"><div class="d2-empty-icon">&#128193;</div><p>No documents uploaded yet</p></div>
                <?php else :
                    $doc_types = function_exists('gabochie_value_document_types') ? gabochie_value_document_types() : array();
                    $icon_map = array('site_plan'=>'plan','floor_plan'=>'plan','elevation'=>'plan','section'=>'plan','3d_render'=>'photo','structural_calc'=>'perm','permit'=>'perm','contract'=>'fin','invoice'=>'fin','progress_photo'=>'photo','inspection_report'=>'perm');
                    foreach ($docs as $d) :
                        $dt = get_post_meta($d->ID,'doc_type',true);
                        $icon_cls = $icon_map[$dt] ?? 'other';
                        $uploader = get_userdata(get_post_meta($d->ID,'uploaded_by',true));
                ?>
                <div class="d2-doc-item">
                    <div class="d2-doc-icon <?php echo $icon_cls; ?>"><?php echo strtoupper(substr($dt,0,2)); ?></div>
                    <div style="flex:1;min-width:0">
                        <div class="d2-doc-title"><?php echo esc_html($d->post_title); ?></div>
                        <div class="d2-doc-meta"><?php echo esc_html($doc_types[$dt] ?? 'Other'); ?> &middot; <?php echo $uploader ? esc_html($uploader->display_name) : 'Unknown'; ?> &middot; <?php echo esc_html($d->post_date); ?></div>
                    </div>
                    <?php if ($url = get_post_meta($d->ID,'file_url',true)) : ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" style="font-size:.75rem;color:var(--teal);text-decoration:none;font-weight:500">View</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="d2-section" style="margin-bottom:0">
            <div class="d2-section-head">
                <h3>Document Coverage</h3>
                <?php
                $all_doc_types = function_exists('gabochie_value_document_types') ? gabochie_value_document_types() : array();
                $uploaded_types = array();
                foreach ($docs as $d) { $t = get_post_meta($d->ID,'doc_type',true); if ($t) $uploaded_types[$t] = true; }
                $coverage = count($all_doc_types) > 0 ? round(((count($uploaded_types) / count($all_doc_types)) * 100)) : 0;
                ?>
                <span class="d2-badge <?php echo $coverage >= 80 ? 'green' : ($coverage >= 50 ? 'orange' : 'red'); ?>"><?php echo $coverage; ?>%</span>
            </div>
            <div class="d2-section-body">
                <div class="d2-progress" style="margin-top:0"><div class="d2-progress-fill" style="width:<?php echo $coverage; ?>%"></div></div>
                <?php
                $missing = array();
                foreach ($all_doc_types as $key => $label) {
                    if (!isset($uploaded_types[$key])) $missing[] = array('type'=>$key,'label'=>$label);
                }
                if (!empty($missing)) : ?>
                <div style="margin-top:1rem">
                    <div style="font-size:.82rem;font-weight:600;margin-bottom:.5rem;color:var(--charcoal)">Missing Documents</div>
                    <?php foreach ($missing as $gap) : ?>
                    <div class="d2-gap"><div class="d2-gap-dot"></div><span class="d2-gap-label"><?php echo esc_html($gap['label']); ?></span></div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div style="text-align:center;padding:2rem 0;color:#27AE60;font-size:.9rem">&#10003; All document types covered</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MESSAGES TAB ═══ -->
<div class="d2-tab-content" data-tab="messages">
    <div class="d2-section">
        <div class="d2-section-head">
            <h3>Project Messages</h3>
            <span class="d2-badge teal"><?php echo $msg_stats['total']; ?> messages</span>
        </div>
        <div class="d2-section-body" id="d2-messages-list" style="max-height:400px;overflow-y:auto">
            <?php
            $messages = get_posts(array('post_type'=>'project_message','posts_per_page'=>20,'meta_query'=>array(array('key'=>'project_id','value'=>$latest->ID,'compare'=>'=')),'orderby'=>'date','order'=>'ASC'));
            if (empty($messages)) :
            ?>
            <div class="d2-empty" style="padding:2rem"><div class="d2-empty-icon">&#128172;</div><p>No messages yet — start a conversation with your design team.</p></div>
            <?php else : foreach ($messages as $msg) :
                $author = get_userdata($msg->post_author);
                $is_client = (bool) get_post_meta($msg->ID,'is_from_client',true);
                $is_system = (bool) get_post_meta($msg->ID,'is_system_note',true);
                $is_read = (bool) get_post_meta($msg->ID,'is_read',true);
                $unread = !$is_read && !$is_client;
                $avatar_cls = $is_system ? 'system' : '';
                $initial = $is_system ? 'S' : ($author ? strtoupper(substr($author->display_name,0,1)) : '?');
            ?>
            <div class="d2-msg" data-id="<?php echo $msg->ID; ?>">
                <div class="d2-msg-avatar <?php echo $avatar_cls; ?>"><?php echo $initial; ?></div>
                <div class="d2-msg-body">
                    <div class="d2-msg-sender"><?php echo $is_system ? 'System' : ($author ? esc_html($author->display_name) : 'Unknown'); ?>
                        <?php if ($is_client) : ?><span style="font-size:.65rem;color:var(--mist);font-weight:400;margin-left:.3rem">(you)</span><?php endif; ?>
                    </div>
                    <div class="d2-msg-text"><?php echo nl2br(esc_html($msg->post_content)); ?></div>
                    <div class="d2-msg-time"><?php echo human_time_diff(strtotime($msg->post_date), current_time('timestamp')); ?> ago</div>
                </div>
                <?php if ($unread) : ?><div class="d2-msg-unread" title="Unread"></div><?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="d2-msg-input">
            <input type="text" id="d2-msg-field" placeholder="Type a message..." maxlength="2000">
            <button id="d2-msg-send" onclick="d2SendMessage()">Send</button>
        </div>
    </div>
</div>

<!-- ═══ AUDIT TAB ═══ -->
<div class="d2-tab-content" data-tab="audit">
    <div class="d2-section">
        <div class="d2-section-head">
            <h3>Activity Log</h3>
            <span class="d2-badge teal">Last 50 entries</span>
        </div>
        <div class="d2-section-body">
            <?php
            $audit = get_posts(array('post_type'=>'project_audit_log','posts_per_page'=>50,'meta_query'=>array(array('key'=>'project_id','value'=>$latest->ID,'compare'=>'=')),'orderby'=>'date','order'=>'DESC'));
            if (empty($audit)) :
            ?>
            <div class="d2-empty" style="padding:2rem"><div class="d2-empty-icon">&#128209;</div><p>No activity recorded yet.</p></div>
            <?php else : foreach ($audit as $a) :
                $at = get_post_meta($a->ID,'action_type',true);
                $detail = get_post_meta($a->ID,'action_detail',true);
                $actor = get_userdata(get_post_meta($a->ID,'acted_by',true));
                $dot_cls = 'created';
                if (strpos($at,'update') !== false) $dot_cls = 'updated';
                if (strpos($at,'delete') !== false) $dot_cls = 'deleted';
                if (strpos($at,'message') !== false) $dot_cls = 'message';
                if (strpos($at,'phase') !== false) $dot_cls = 'phase';
                if (strpos($at,'document') !== false) $dot_cls = 'document';
                if (strpos($at,'cost') !== false) $dot_cls = 'cost';
            ?>
            <div class="d2-audit-item">
                <div class="d2-audit-dot <?php echo $dot_cls; ?>"></div>
                <div style="flex:1">
                    <div class="d2-audit-text"><strong><?php echo esc_html(ucfirst(str_replace('_',' ',$at))); ?></strong><?php if ($detail) echo ' — ' . esc_html($detail); ?></div>
                    <div class="d2-audit-time"><?php echo $actor ? esc_html($actor->display_name) : 'System'; ?> &middot; <?php echo human_time_diff(strtotime($a->post_date), current_time('timestamp')); ?> ago</div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- ═══ COSTS TAB ═══ -->
<div class="d2-tab-content" data-tab="costs">
    <div class="d2-section">
        <div class="d2-section-head">
            <h3>Cost Summary</h3>
            <?php
            $costs_all = get_posts(array('post_type'=>'project_cost_item','posts_per_page'=>-1,'meta_query'=>array(array('key'=>'project_id','value'=>$latest->ID,'compare'=>'='))));
            $cat_totals = array();
            $paid_total = 0;
            foreach ($costs_all as $c) {
                $cat = get_post_meta($c->ID,'category',true) ?: 'other';
                $amt = (float) get_post_meta($c->ID,'amount',true);
                $cat_totals[$cat] = ($cat_totals[$cat] ?? 0) + $amt;
                if (get_post_meta($c->ID,'cost_status',true) === 'paid') $paid_total += $amt;
            }
            $grand = array_sum($cat_totals);
            ?>
            <span class="d2-badge gold"><?php echo esc_html($stats['currency'] . ' ' . number_format($grand)); ?> total</span>
        </div>
        <div class="d2-section-body">
            <?php if ($grand > 0) : ?>
            <div class="d2-cost-bar">
                <?php
                $colors = array('land'=>'#0D5C52','design'=>'#2980B9','permits'=>'#27AE60','site_prep'=>'#E67E22','foundation'=>'#8E44AD','finishes'=>'#C9A96E','m&e'=>'#C0392B','exterior'=>'#1ABC9C','furniture'=>'#34495E','contingency'=>'#95A5A6','other'=>'#BDC3C7');
                foreach ($cat_totals as $cat => $amt) : ?>
                <div class="d2-cost-seg" style="width:<?php echo $grand > 0 ? round(($amt/$grand)*100) : 0; ?>%;background:<?php echo $colors[$cat] ?? '#BDC3C7'; ?>" title="<?php echo esc_attr(($stats['currency'] ?? 'GHS') . ' ' . number_format($amt)); ?>"></div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-top:.75rem;font-size:.78rem">
                <?php foreach ($cat_totals as $cat => $amt) : ?>
                <div style="display:flex;align-items:center;gap:.35rem">
                    <div style="width:10px;height:10px;border-radius:2px;background:<?php echo $colors[$cat] ?? '#BDC3C7'; ?>"></div>
                    <span style="color:var(--mist)"><?php echo esc_html(ucwords(str_replace('_',' ',$cat))); ?>:</span>
                    <strong><?php echo esc_html(number_format($amt)); ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($costs_all)) : ?>
            <div style="margin-top:1.5rem">
                <h4 style="font-size:.85rem;margin-bottom:.5rem">Cost Items</h4>
                <?php foreach ($costs_all as $c) :
                    $c_status = get_post_meta($c->ID,'cost_status',true);
                    $c_cls = $c_status === 'paid' ? 'green' : ($c_status === 'disputed' ? 'red' : 'orange');
                ?>
                <div style="display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid var(--border-light);font-size:.85rem">
                    <span class="d2-badge <?php echo $c_cls; ?>"><?php echo esc_html(ucfirst($c_status)); ?></span>
                    <span style="flex:1;font-weight:500"><?php echo esc_html($c->post_title); ?></span>
                    <span style="font-weight:600"><?php echo esc_html(number_format((float) get_post_meta($c->ID,'amount',true))); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <div class="d2-empty" style="padding:2rem"><div class="d2-empty-icon">&#128176;</div><p>No cost items recorded yet.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; /* /latest project */ ?>

</div><!-- /d2-wrap -->

<script>
(function(){
    /* Tab switching */
    document.querySelectorAll('.d2-tab').forEach(function(tab){
        tab.addEventListener('click', function(){
            var target = this.getAttribute('data-tab');
            document.querySelectorAll('.d2-tab').forEach(function(t){ t.classList.remove('active'); });
            document.querySelectorAll('.d2-tab-content').forEach(function(c){ c.classList.remove('active'); });
            this.classList.add('active');
            document.querySelector('.d2-tab-content[data-tab="'+target+'"]').classList.add('active');
        });
    });

    /* Auto-scroll messages */
    var msgList = document.getElementById('d2-messages-list');
    if (msgList) msgList.scrollTop = msgList.scrollHeight;

    /* ─── READINESS SCORE ─── */
    var app = document.getElementById('d2-app');
    var pid = app ? app.getAttribute('data-project') : 0;
    var rest = app ? app.getAttribute('data-rest') : '';
    var wpNonce = app ? app.getAttribute('data-nonce') : '';
    if (!pid || !rest) return;

    function grColor(score) {
        if (score >= 70) return '#27AE60';
        if (score >= 40) return '#F39C12';
        return '#E74C3C';
    }
    function grStatus(score) {
        if (score >= 70) return 'green';
        if (score >= 40) return 'amber';
        return 'red';
    }

    fetch(rest + 'projects/' + pid + '/readiness', {
        headers: { 'X-WP-Nonce': wpNonce }
    }).then(function(r){ return r.json(); }).then(function(d) {
        if (!d.ok) return;

        /* Overall badge */
        var badge = document.getElementById('gr-overall-badge');
        badge.textContent = d.overall + '/100';
        badge.className = 'd2-badge ' + grStatus(d.overall);

        /* Score ring */
        var color = grColor(d.overall);
        var circ = 2 * 45;
        var offset = circ - (d.overall / 100) * circ;
        document.getElementById('gr-ring').innerHTML =
            '<svg width="120" height="120" viewBox="0 0 100 100">' +
            '<circle cx="50" cy="50" r="45" fill="none" stroke="#E8E8E4" stroke-width="8"/>' +
            '<circle cx="50" cy="50" r="45" fill="none" stroke="' + color + '" stroke-width="8" ' +
            'stroke-dasharray="' + circ + '" stroke-dashoffset="' + offset + '" ' +
            'stroke-linecap="round" transform="rotate(-90 50 50)" ' +
            'style="transition:stroke-dashoffset 1s ease-out"/>' +
            '<text x="50" y="48" text-anchor="middle" font-size="22" font-weight="700" fill="' + color + '">' + d.overall + '</text>' +
            '<text x="50" y="62" text-anchor="middle" font-size="8" fill="#888">/ 100</text></svg>';

        /* Dimension bars */
        var dimsHtml = '';
        var dims = d.dimensions;
        var keys = Object.keys(dims);
        for (var i = 0; i < keys.length; i++) {
            var k = keys[i];
            var dim = dims[k];
            var c = grColor(dim.score);
            dimsHtml += '<div class="gr-dim-bar">' +
                '<div class="gr-dim-bar-label"><span>' + dim.label + '</span><span style="color:' + c + '">' + dim.score + '%</span></div>' +
                '<div class="gr-dim-bar-track"><div class="gr-dim-bar-fill" style="width:' + dim.score + '%;background:' + c + '"></div></div></div>';
        }
        document.getElementById('gr-dimensions').innerHTML = dimsHtml;

        /* Strengths */
        var sHtml = '';
        for (var j = 0; j < d.strengths.length; j++) {
            sHtml += '<div class="gr-strength-item"><span class="gr-strength-dot"></span>' + d.strengths[j] + '</div>';
        }
        document.getElementById('gr-strengths').innerHTML = sHtml || '<div style="font-size:.78rem;color:var(--mist)">No strengths identified yet</div>';

        /* Weaknesses */
        var wHtml = '';
        for (var m = 0; m < d.weaknesses.length; m++) {
            wHtml += '<div class="gr-gap-item"><span class="gr-gap-dot"></span>' + d.weaknesses[m] + '</div>';
        }
        document.getElementById('gr-weaknesses').innerHTML = wHtml || '<div style="font-size:.78rem;color:var(--mist)">No gaps identified yet</div>';

        /* Upgrade prompt */
        if (d.upgrade_prompt) {
            document.getElementById('gr-upgrade').style.display = 'block';
        }
    });
})();

function d2SendMessage(){
    var field = document.getElementById('d2-msg-field');
    var btn = document.getElementById('d2-msg-send');
    var content = field.value.trim();
    if (!content) return;
    btn.disabled = true;
    var app = document.getElementById('d2-app');
    var pid = app.getAttribute('data-project');
    var rest = app.getAttribute('data-rest');
    var nonce = app.getAttribute('data-nonce');

    fetch(rest + 'workspace/' + pid + '/messages', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        body: JSON.stringify({ content: content })
    }).then(function(r){ return r.json(); }).then(function(data){
        if (data.ok) {
            field.value = '';
            location.reload();
        } else {
            alert('Send failed: ' + (data.error || 'Unknown'));
        }
        btn.disabled = false;
    }).catch(function(e){
        alert('Network error');
        btn.disabled = false;
    });
}

/* Enter to send */
document.getElementById('d2-msg-field')?.addEventListener('keydown', function(e){
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); d2SendMessage(); }
});

/* ─── RECOVERY WORKSPACE (All Building Types, Ghana) ─── */
(function(){
    var app = document.getElementById('d2-app');
    if(!app) return;
    var pid = app.getAttribute('data-project');
    var rest = app.getAttribute('data-rest');
    var nonce = app.getAttribute('data-nonce');
    var recTab = document.getElementById('d2-recovery-tab');
    if(!recTab || !pid || pid==='0') return;

    function api(path, opts){
        opts = opts||{};
        opts.headers = opts.headers||{};
        opts.headers['X-WP-Nonce'] = nonce;
        opts.headers['Content-Type'] = 'application/json';
        return fetch(rest + path, opts).then(function(r){ return r.json().then(function(d){ return {ok:r.ok, data:d}; }); });
    }

    function render(data){
        var steps = data.steps || {};
        var stepKeys = Object.keys(steps);
        var current = data.current_step || 'triage';
        var checklist = data.checklist || [];
        var badge = document.getElementById('d2-rec-progress-badge');
        var fill = document.getElementById('d2-rec-progress-fill');
        if(badge) badge.textContent = data.progress + '% · ' + current;
        if(fill) fill.style.width = data.progress + '%';
        // stepper
        var stepper = document.getElementById('d2-rec-stepper');
        if(stepper){
            var html='';
            var curIdx = stepKeys.indexOf(current);
            stepKeys.forEach(function(k, i){
                var s = steps[k];
                var cls = i < curIdx ? 'done' : (i===curIdx ? 'current' : 'upcoming');
                html += '<div class="rec-step '+cls+'"><div class="rec-step-label">'+s.label+'</div><div class="rec-step-desc">'+s.desc+'</div></div>';
            });
            stepper.innerHTML = html;
        }
        // checklist
        var cl = document.getElementById('d2-rec-checklist');
        if(cl){
            if(!checklist.length){
                cl.innerHTML = '<div style="padding:1rem;color:var(--mist);font-size:0.85rem">No checklist yet — your facilitator will seed it.</div>';
            } else {
                var chtml = '<div class="rec-invite-title">Your Recovery Checklist</div>';
                checklist.forEach(function(it){
                    var done = it.status==='done';
                    chtml += '<label class="rec-check'+(done?' is-done':'')+'"><input type="checkbox" data-id="'+it.id+'" '+(done?'checked':'')+'><span class="rec-check-label">'+it.label+'</span><span class="rec-check-tag">'+(it.doc_type||'')+'</span></label>';
                });
                cl.innerHTML = chtml;
                cl.querySelectorAll('input[type=checkbox]').forEach(function(cb){
                    cb.addEventListener('change', function(){
                        var itemId = this.getAttribute('data-id');
                        var status = this.checked ? 'done' : 'pending';
                        api('recovery/'+pid+'/checklist', {method:'POST', body: JSON.stringify({item_id:itemId, status:status})}).then(function(res){
                            if(!res.ok){ alert(res.data.message||'Update failed'); cb.checked = !cb.checked; }
                        });
                    });
                });
            }
        }
        var adv = document.getElementById('d2-rec-advance');
        var appr = document.getElementById('d2-rec-approve');
        var msg = document.getElementById('d2-rec-msg');
        if(adv){
            var nextIdx = stepKeys.indexOf(current)+1;
            if(nextIdx < stepKeys.length){
                adv.style.display = 'inline-block';
                adv.textContent = 'Advance to ' + steps[stepKeys[nextIdx]].label + ' →';
                adv.onclick = function(){
                    adv.disabled=true; adv.textContent='Advancing…';
                    api('recovery/'+pid+'/advance', {method:'POST', body: JSON.stringify({to: stepKeys[nextIdx]})}).then(function(res){
                        if(res.ok){ render(res.data); if(msg) msg.textContent='Moved to '+steps[stepKeys[nextIdx]].label; }
                        else { if(msg) { msg.textContent=res.data.message||'Cannot advance'; msg.style.color='#b02323'; } adv.disabled=false; adv.textContent='Advance to '+steps[stepKeys[nextIdx]].label+' →'; }
                    });
                };
            } else { adv.style.display='none'; }
        }
        if(appr){
            if(current==='plan' && !data.plan_approved){
                appr.style.display='inline-block';
                appr.onclick = function(){
                    appr.disabled=true;
                    api('recovery/'+pid+'/approve-plan', {method:'POST', body:'{}'}).then(function(res){
                        if(res.ok){ appr.style.display='none'; if(msg) msg.textContent='Plan approved — you can now advance to Execution.'; }
                        else { appr.disabled=false; }
                    });
                };
            } else { appr.style.display='none'; }
        }
    }

    api('recovery/'+pid).then(function(res){ if(res.ok) render(res.data); else { var m=document.getElementById('d2-rec-msg'); if(m) m.textContent='Recovery workspace not provisioned yet — complete the Diagnostic purchase to unlock.'; } });

    // PDF link
    var pdfLink = document.getElementById('d2-rec-pdf');
    if(pdfLink) pdfLink.href = rest + 'recovery/' + pid + '/pdf?_wpnonce=' + nonce;

    // Invite handler
    var inviteBtn = document.getElementById('d2-rec-invite-btn');
    if(inviteBtn){
        inviteBtn.addEventListener('click', function(){
            var emailEl = document.getElementById('d2-rec-invite-email');
            var nameEl = document.getElementById('d2-rec-invite-name');
            var msgEl = document.getElementById('d2-rec-invite-msg');
            var email = emailEl ? emailEl.value.trim() : '';
            var name = nameEl ? nameEl.value.trim() : '';
            if(!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){ if(msgEl){ msgEl.textContent='Valid email required.'; msgEl.style.color='#b02323'; } return; }
            inviteBtn.disabled=true; inviteBtn.textContent='Inviting…';
            api('recovery/'+pid+'/invite', {method:'POST', body: JSON.stringify({email:email, name:name})}).then(function(res){
                if(res.ok){ if(msgEl){ msgEl.textContent='Invited '+res.data.email+' — they can now co-drive.'; msgEl.style.color='#0D5C52'; } if(emailEl) emailEl.value=''; if(nameEl) nameEl.value=''; }
                else { if(msgEl){ msgEl.textContent=res.data.message||'Invite failed'; msgEl.style.color='#b02323'; } }
                inviteBtn.disabled=false; inviteBtn.textContent='Invite';
            });
        });
    }
})();
</script>

<?php get_footer(); ?>
