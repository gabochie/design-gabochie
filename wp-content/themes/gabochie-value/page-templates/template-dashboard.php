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

<style>
/* ─── DASHBOARD V2 ─── */
.d2-wrap{max-width:1200px;margin:0 auto;padding:0 1.5rem 4rem}
.d2-hero{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin:2rem 0}
.d2-stat{background:var(--white);border-radius:12px;padding:1.25rem;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid var(--border-light)}
.d2-stat-label{font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;color:var(--mist);margin-bottom:.35rem}
.d2-stat-value{font-size:1.6rem;font-weight:700;color:var(--teal);font-family:'Playfair Display',serif}
.d2-stat-sub{font-size:.78rem;color:var(--mist);margin-top:.2rem}
.d2-section{background:var(--white);border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid var(--border-light);margin-bottom:1.5rem;overflow:hidden}
.d2-section-head{padding:1.25rem 1.5rem;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between}
.d2-section-head h3{font-size:1.05rem;color:var(--charcoal)}
.d2-section-body{padding:1.5rem}
.d2-badge{display:inline-block;padding:2px 10px;border-radius:8px;font-size:.68rem;font-weight:600}
.d2-badge.green{background:rgba(39,174,96,.1);color:#27AE60}
.d2-badge.orange{background:rgba(230,126,34,.1);color:#E67E22}
.d2-badge.red{background:rgba(192,57,43,.1);color:#C0392B}
.d2-badge.teal{background:rgba(13,92,82,.1);color:#0D5C52}
.d2-badge.gold{background:rgba(201,169,110,.15);color:#C9A96E}
.d2-badge.blue{background:rgba(41,128,185,.1);color:#2980B9}

/* Timeline */
.d2-timeline{display:flex;gap:0;overflow-x:auto;padding:1rem 0}
.d2-tl-stage{flex:0 0 auto;text-align:center;position:relative;min-width:80px}
.d2-tl-dot{width:14px;height:14px;border-radius:50%;border:2px solid var(--border-light);background:var(--white);margin:0 auto .4rem;position:relative;z-index:2;transition:all .2s}
.d2-tl-dot.done{background:var(--teal);border-color:var(--teal)}
.d2-tl-dot.current{background:var(--gold);border-color:var(--gold);box-shadow:0 0 0 4px rgba(201,169,110,.25);transform:scale(1.2)}
.d2-tl-line{position:absolute;top:6px;left:0;right:0;height:2px;background:var(--border-light);z-index:1}
.d2-tl-stage:first-child .d2-tl-line{left:50%}
.d2-tl-stage:last-child .d2-tl-line{right:50%}
.d2-tl-line.done{background:var(--teal)}
.d2-tl-label{font-size:.6rem;color:var(--mist);max-width:70px;line-height:1.2}
.d2-tl-label.current{color:var(--gold);font-weight:600}

/* Progress bar */
.d2-progress{height:6px;background:var(--cream-dark);border-radius:3px;overflow:hidden;margin:1rem 0}
.d2-progress-fill{height:100%;background:linear-gradient(90deg,var(--teal),var(--gold));border-radius:3px;transition:width .4s}

/* Two-col grid */
.d2-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
.d2-grid.three{grid-template-columns:1fr 1fr 1fr}

/* Documents */
.d2-doc-item{display:flex;align-items:center;gap:.75rem;padding:.75rem 0;border-bottom:1px solid var(--border-light)}
.d2-doc-item:last-child{border-bottom:none}
.d2-doc-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.d2-doc-icon.plan{background:rgba(13,92,82,.1);color:var(--teal)}
.d2-doc-icon.perm{background:rgba(39,174,96,.1);color:#27AE60}
.d2-doc-icon.fin{background:rgba(201,169,110,.15);color:#C9A96E}
.d2-doc-icon.photo{background:rgba(41,128,185,.1);color:#2980B9}
.d2-doc-icon.other{background:rgba(0,0,0,.05);color:#666}
.d2-doc-title{font-size:.85rem;font-weight:500}
.d2-doc-meta{font-size:.72rem;color:var(--mist)}

/* Messages */
.d2-msg{padding:.75rem 0;border-bottom:1px solid var(--border-light);display:flex;gap:.75rem}
.d2-msg:last-child{border-bottom:none}
.d2-msg-avatar{width:32px;height:32px;border-radius:50%;background:var(--teal);color:var(--white);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:600;flex-shrink:0}
.d2-msg-avatar.system{background:var(--gold)}
.d2-msg-body{flex:1;min-width:0}
.d2-msg-sender{font-size:.78rem;font-weight:600;color:var(--charcoal)}
.d2-msg-text{font-size:.82rem;color:#555;margin-top:.15rem;line-height:1.45}
.d2-msg-time{font-size:.65rem;color:var(--mist);margin-top:.2rem}
.d2-msg-unread{width:8px;height:8px;background:var(--gold);border-radius:50%;flex-shrink:0;margin-top:.4rem}

/* Audit */
.d2-audit-item{display:flex;gap:.6rem;padding:.5rem 0;font-size:.8rem;border-bottom:1px solid var(--border-light)}
.d2-audit-item:last-child{border-bottom:none}
.d2-audit-dot{width:8px;height:8px;border-radius:50%;background:var(--teal);margin-top:.35rem;flex-shrink:0}
.d2-audit-dot.created{background:#27AE60}
.d2-audit-dot.updated{background:var(--gold)}
.d2-audit-dot.deleted{background:#C0392B}
.d2-audit-dot.message{background:#2980B9}
.d2-audit-dot.phase{background:#8E44AD}
.d2-audit-dot.document{background:var(--teal)}
.d2-audit-dot.cost{background:#E67E22}
.d2-audit-text{color:#555;line-height:1.4}
.d2-audit-time{font-size:.68rem;color:var(--mist)}

/* Gap list */
.d2-gap{display:flex;align-items:center;gap:.5rem;padding:.5rem .75rem;background:rgba(230,126,34,.06);border-radius:8px;margin-bottom:.4rem;font-size:.8rem}
.d2-gap-dot{width:6px;height:6px;border-radius:50%;background:#E67E22;flex-shrink:0}
.d2-gap-label{font-weight:500;color:var(--charcoal)}

/* Empty state */
.d2-empty{text-align:center;padding:3rem 1rem;color:var(--mist)}
.d2-empty-icon{font-size:2.5rem;margin-bottom:.75rem;opacity:.3}
.d2-empty p{font-size:.85rem;margin-top:.3rem}

/* Message input */
.d2-msg-input{display:flex;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid var(--border-light)}
.d2-msg-input input{flex:1;border:1px solid var(--border-light);border-radius:8px;padding:.6rem 1rem;font-size:.85rem;font-family:'Jost',sans-serif;outline:none;transition:border .2s}
.d2-msg-input input:focus{border-color:var(--teal)}
.d2-msg-input button{background:var(--teal);color:var(--white);border:none;padding:.6rem 1.2rem;border-radius:8px;font-weight:600;font-size:.82rem;cursor:pointer;font-family:'Jost',sans-serif;transition:background .2s}
.d2-msg-input button:hover{background:var(--teal-light)}
.d2-msg-input button:disabled{opacity:.5;cursor:default}

/* Cost bar */
.d2-cost-bar{display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--cream-dark);margin:.75rem 0}
.d2-cost-seg{transition:width .3s}

/* Tabs */
.d2-tabs{display:flex;gap:0;border-bottom:2px solid var(--border-light);margin-bottom:1.5rem}
.d2-tab{padding:.6rem 1.2rem;font-size:.82rem;font-weight:500;cursor:pointer;color:var(--mist);border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s}
.d2-tab:hover{color:var(--charcoal)}
.d2-tab.active{color:var(--teal);border-bottom-color:var(--teal);font-weight:600}
.d2-tab-content{display:none}
.d2-tab-content.active{display:block}

@media(max-width:768px){
    .d2-hero{grid-template-columns:repeat(2,1fr)}
    .d2-grid{grid-template-columns:1fr}
    .d2-grid.three{grid-template-columns:1fr}
    .d2-timeline{flex-wrap:wrap;gap:.5rem}
}
@media(max-width:480px){
    .d2-hero{grid-template-columns:1fr}
}
</style>

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

<!-- ═══ TABS ═══ -->
<div class="d2-tabs">
    <div class="d2-tab active" data-tab="timeline">Timeline</div>
    <div class="d2-tab" data-tab="documents">Documents</div>
    <div class="d2-tab" data-tab="messages">Messages <?php if ($msg_stats['unread'] > 0) echo '<span style="background:var(--gold);color:var(--white);padding:1px 6px;border-radius:8px;font-size:.6rem;margin-left:4px">' . $msg_stats['unread'] . '</span>'; ?></div>
    <div class="d2-tab" data-tab="audit">Activity Log</div>
    <div class="d2-tab" data-tab="costs">Costs</div>
</div>

<!-- ═══ TIMELINE TAB ═══ -->
<div class="d2-tab-content active" data-tab="timeline">
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
document.getElementById('d2-msg-field').addEventListener('keydown', function(e){
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); d2SendMessage(); }
});
</script>

<?php get_footer(); ?>
