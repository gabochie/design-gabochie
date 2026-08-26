<?php
/**
 * Client Portal — REST API (Phase A Workspace)
 * @package Gabochie_Value
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    $ns = 'gabochie/v1';

    /* ─── PROJECT WORKSPACE ─── */
    register_rest_route($ns, '/workspace/(?P<id>\d+)', array(
        array(
            'methods'             => 'GET',
            'callback'            => 'gabochie_api_workspace_get',
            'permission_callback' => 'gabochie_api_client_access',
            'args' => array('id' => array('validate_callback' => 'is_numeric')),
        ),
        array(
            'methods'             => 'POST',
            'callback'            => 'gabochie_api_workspace_update',
            'permission_callback' => 'gabochie_api_client_access',
            'args' => array('id' => array('validate_callback' => 'is_numeric')),
        ),
    ));

    /* ─── TIMELINE (stages + milestones) ─── */
    register_rest_route($ns, '/workspace/(?P<id>\d+)/timeline', array(
        array(
            'methods'             => 'GET',
            'callback'            => 'gabochie_api_timeline_get',
            'permission_callback' => 'gabochie_api_client_access',
        ),
        array(
            'methods'             => 'POST',
            'callback'            => 'gabochie_api_timeline_update_stage',
            'permission_callback' => 'gabochie_api_client_access',
        ),
    ));

    /* ─── MILESTONES ─── */
    register_rest_route($ns, '/workspace/(?P<id>\d+)/milestones', array(
        array(
            'methods'             => 'GET',
            'callback'            => 'gabochie_api_milestones_list',
            'permission_callback' => 'gabochie_api_client_access',
        ),
        array(
            'methods'             => 'POST',
            'callback'            => 'gabochie_api_milestone_create',
            'permission_callback' => 'gabochie_api_client_access',
        ),
    ));

    register_rest_route($ns, '/milestone/(?P<mid>\d+)', array(
        array(
            'methods'             => 'POST',
            'callback'            => 'gabochie_api_milestone_update',
            'permission_callback' => 'gabochie_api_client_access',
        ),
        array(
            'methods'             => 'DELETE',
            'callback'            => 'gabochie_api_milestone_delete',
            'permission_callback' => 'gabochie_api_client_access',
        ),
    ));

    /* ─── DOCUMENTS ─── */
    register_rest_route($ns, '/workspace/(?P<id>\d+)/documents', array(
        array(
            'methods'             => 'GET',
            'callback'            => 'gabochie_api_documents_list',
            'permission_callback' => 'gabochie_api_client_access',
        ),
        array(
            'methods'             => 'POST',
            'callback'            => 'gabochie_api_document_create',
            'permission_callback' => 'gabochie_api_client_access',
        ),
    ));

    register_rest_route($ns, '/document/(?P<did>\d+)', array(
        array(
            'methods'             => 'DELETE',
            'callback'            => 'gabochie_api_document_delete',
            'permission_callback' => 'gabochie_api_client_access',
        ),
    ));

    register_rest_route($ns, '/workspace/(?P<id>\d+)/documents/gaps', array(
        'methods'             => 'GET',
        'callback'            => 'gabochie_api_document_gaps',
        'permission_callback' => 'gabochie_api_client_access',
    ));

    /* ─── MESSAGES ─── */
    register_rest_route($ns, '/workspace/(?P<id>\d+)/messages', array(
        array(
            'methods'             => 'GET',
            'callback'            => 'gabochie_api_messages_list',
            'permission_callback' => 'gabochie_api_client_access',
        ),
        array(
            'methods'             => 'POST',
            'callback'            => 'gabochie_api_message_send',
            'permission_callback' => 'gabochie_api_client_access',
        ),
    ));

    register_rest_route($ns, '/message/(?P<messid>\d+)/read', array(
        'methods'             => 'POST',
        'callback'            => 'gabochie_api_message_mark_read',
        'permission_callback' => 'gabochie_api_client_access',
    ));

    /* ─── AUDIT TRAIL ─── */
    register_rest_route($ns, '/workspace/(?P<id>\d+)/audit', array(
        'methods'             => 'GET',
        'callback'            => 'gabochie_api_audit_list',
        'permission_callback' => 'gabochie_api_client_access',
    ));

    /* ─── COST ITEMS ─── */
    register_rest_route($ns, '/workspace/(?P<id>\d+)/costs', array(
        array(
            'methods'             => 'GET',
            'callback'            => 'gabochie_api_costs_list',
            'permission_callback' => 'gabochie_api_client_access',
        ),
        array(
            'methods'             => 'POST',
            'callback'            => 'gabochie_api_cost_create',
            'permission_callback' => 'gabochie_api_client_access',
        ),
    ));

    register_rest_route($ns, '/cost/(?P<cid>\d+)', array(
        array(
            'methods'             => 'POST',
            'callback'            => 'gabochie_api_cost_update',
            'permission_callback' => 'gabochie_api_client_access',
        ),
        array(
            'methods'             => 'DELETE',
            'callback'            => 'gabochie_api_cost_delete',
            'permission_callback' => 'gabochie_api_client_access',
        ),
    ));

    /* ─── DASHBOARD AGGREGATE ─── */
    register_rest_route($ns, '/dashboard', array(
        'methods'             => 'GET',
        'callback'            => 'gabochie_api_dashboard',
        'permission_callback' => 'gabochie_api_client_access',
    ));
});

/* ═══════════════ AUTH ═══════════════ */
function gabochie_api_client_access($request) {
    if (!is_user_logged_in()) return false;
    $user = wp_get_current_user();
    if (in_array('administrator', $user->roles)) return true;
    $id = $request->get_param('id');
    if (!$id) return true;
    $project = get_post($id);
    return $project && $project->post_type === 'client_project' && (int) $project->post_author === $user->ID;
}

/* ═══════════════ WORKSPACE ═══════════════ */
function gabochie_api_workspace_get($request) {
    $id = (int) $request->get_param('id');
    $project = get_post($id);
    if (!$project || $project->post_type !== 'client_project') {
        return new WP_REST_Response(array('error' => 'Project not found'), 404);
    }
    $meta = array();
    $keys = array('project_status','project_stage','project_budget','project_currency','project_location',
        'next_milestone','next_milestone_date','service_type','client_phone','referral_source',
        'dev_goal','property_type','land_status','land_location','development_scale','budget_range',
        'timeline','involvement','location_based','project_description',
        'project_type','target_users','programme','site_info','assumptions','decisions_log',
        'current_phase','phase_history');
    foreach ($keys as $k) $meta[$k] = get_post_meta($id, $k, true);
    return wp_json_encode(array(
        'id'       => $id,
        'title'    => $project->post_title,
        'author'   => (int) $project->post_author,
        'created'  => $project->post_date,
        'modified' => $project->post_modified,
        'meta'     => $meta,
    ));
}

function gabochie_api_workspace_update($request) {
    $id = (int) $request->get_param('id');
    $body = json_decode($request->get_body(), true);
    if (empty($body)) return new WP_REST_Response(array('error' => 'No data'), 400);
    $writable = array('title','project_status','project_stage','project_budget','project_currency',
        'project_location','next_milestone','next_milestone_date','service_type','client_phone',
        'referral_source','dev_goal','property_type','land_status','land_location','development_scale',
        'budget_range','timeline','involvement','location_based','project_description',
        'project_type','target_users','programme','site_info','assumptions','decisions_log',
        'current_phase');
    $updated = array();
    foreach ($body as $k => $v) {
        if (in_array($k, $writable)) {
            if ($k === 'title') {
                wp_update_post(array('ID' => $id, 'post_title' => sanitize_text_field($v)));
            } else {
                update_post_meta($id, $k, is_string($v) ? sanitize_textarea_field($v) : $v);
                $updated[] = $k;
            }
        }
    }
    /* Track phase transition if current_phase changed */
    if (isset($body['current_phase'])) {
        gabochie_transition_phase($id, $body['current_phase']);
    }
    gabochie_log_audit($id, 'workspace_updated', 'Fields: ' . implode(', ', $updated));
    return wp_json_encode(array('ok' => true, 'updated' => $updated));
}

/* ═══════════════ TIMELINE ═══════════════ */
function gabochie_api_timeline_get($request) {
    $id = (int) $request->get_param('id');
    $all_stages = array_keys(gabochie_value_project_stages());
    $current    = get_post_meta($id, 'project_stage', true) ?: 'Discovery';
    $history    = json_decode(get_post_meta($id, 'phase_history', true), true) ?: array();
    $milestones = get_posts(array(
        'post_type'      => 'project_milestone',
        'posts_per_page' => -1,
        'meta_query'     => array(array('key' => 'project_id', 'value' => $id, 'compare' => '=')),
        'orderby'        => 'date',
        'order'          => 'ASC',
    ));
    $stages = array();
    $found_current = false;
    foreach ($all_stages as $s) {
        $stage_milestones = array();
        foreach ($milestones as $m) {
            if (get_post_meta($m->ID, 'milestone_stage', true) === $s) {
                $stage_milestones[] = array(
                    'id'          => $m->ID,
                    'title'       => $m->post_title,
                    'target_date' => get_post_meta($m->ID, 'target_date', true),
                    'completed'   => get_post_meta($m->ID, 'completed_date', true),
                    'status'      => get_post_meta($m->ID, 'milestone_status', true),
                );
            }
        }
        $phase_entry = null;
        foreach ($history as $h) {
            if ($h['phase'] === $s) { $phase_entry = $h; break; }
        }
        if ($s === $current) $found_current = true;
        $stages[] = array(
            'name'       => $s,
            'label'      => gabochie_value_project_stages()[$s] ?? $s,
            'status'     => $found_current ? ($s === $current ? 'current' : 'completed') : 'upcoming',
            'entered_at' => $phase_entry['entered_at'] ?? '',
            'exited_at'  => $phase_entry['exited_at'] ?? '',
            'milestones' => $stage_milestones,
        );
    }
    $completed_count = 0;
    foreach ($stages as $s) { if ($s['status'] === 'completed') $completed_count++; }
    return wp_json_encode(array(
        'stages'    => $stages,
        'current'   => $current,
        'progress'  => round(($completed_count / count($all_stages)) * 100),
    ));
}

function gabochie_api_timeline_update_stage($request) {
    $id   = (int) $request->get_param('id');
    $body = json_decode($request->get_body(), true);
    $stage = sanitize_text_field($body['stage'] ?? '');
    $valid = array_keys(gabochie_value_project_stages());
    if (!in_array($stage, $valid)) {
        return new WP_REST_Response(array('error' => 'Invalid stage'), 400);
    }
    gabochie_transition_phase($id, $stage);
    return wp_json_encode(array('ok' => true, 'stage' => $stage));
}

/* ═══════════════ MILESTONES ═══════════════ */
function gabochie_api_milestones_list($request) {
    $id = (int) $request->get_param('id');
    $posts = get_posts(array(
        'post_type'      => 'project_milestone',
        'posts_per_page' => -1,
        'meta_query'     => array(array('key' => 'project_id', 'value' => $id, 'compare' => '=')),
        'orderby'        => 'date',
        'order'          => 'ASC',
    ));
    $items = array();
    foreach ($posts as $p) {
        $items[] = array(
            'id'          => $p->ID,
            'title'       => $p->post_title,
            'description' => $p->post_content,
            'target_date' => get_post_meta($p->ID, 'target_date', true),
            'completed'   => get_post_meta($p->ID, 'completed_date', true),
            'status'      => get_post_meta($p->ID, 'milestone_status', true),
            'stage'       => get_post_meta($p->ID, 'milestone_stage', true),
        );
    }
    return wp_json_encode($items);
}

function gabochie_api_milestone_create($request) {
    $id   = (int) $request->get_param('id');
    $body = json_decode($request->get_body(), true);
    $title = sanitize_text_field($body['title'] ?? '');
    if (!$title) return new WP_REST_Response(array('error' => 'Title required'), 400);
    $post_id = wp_insert_post(array(
        'post_type'   => 'project_milestone',
        'post_title'  => $title,
        'post_content'=> sanitize_textarea_field($body['description'] ?? ''),
        'post_status' => 'publish',
    ));
    if (is_wp_error($post_id)) return new WP_REST_Response(array('error' => 'Create failed'), 500);
    update_post_meta($post_id, 'project_id', $id);
    update_post_meta($post_id, 'target_date', sanitize_text_field($body['target_date'] ?? ''));
    update_post_meta($post_id, 'milestone_status', sanitize_text_field($body['status'] ?? 'pending'));
    update_post_meta($post_id, 'milestone_stage', sanitize_text_field($body['stage'] ?? ''));
    gabochie_log_audit($id, 'milestone_created', $title);
    return wp_json_encode(array('ok' => true, 'id' => $post_id));
}

function gabochie_api_milestone_update($request) {
    $mid  = (int) $request->get_param('mid');
    $body = json_decode($request->get_body(), true);
    $project_id = get_post_meta($mid, 'project_id', true);
    if (isset($body['title'])) wp_update_post(array('ID' => $mid, 'post_title' => sanitize_text_field($body['title'])));
    if (isset($body['description'])) wp_update_post(array('ID' => $mid, 'post_content' => sanitize_textarea_field($body['description'])));
    if (isset($body['target_date'])) update_post_meta($mid, 'target_date', sanitize_text_field($body['target_date']));
    if (isset($body['status'])) {
        update_post_meta($mid, 'milestone_status', sanitize_text_field($body['status']));
        if ($body['status'] === 'completed') {
            update_post_meta($mid, 'completed_date', current_time('mysql'));
        }
    }
    if (isset($body['stage'])) update_post_meta($mid, 'milestone_stage', sanitize_text_field($body['stage']));
    gabochie_log_audit($project_id, 'milestone_updated', get_the_title($mid) . ': ' . json_encode(array_keys($body)));
    return wp_json_encode(array('ok' => true));
}

function gabochie_api_milestone_delete($request) {
    $mid = (int) $request->get_param('mid');
    $project_id = get_post_meta($mid, 'project_id', true);
    $title = get_the_title($mid);
    wp_delete_post($mid, true);
    gabochie_log_audit($project_id, 'milestone_deleted', $title);
    return wp_json_encode(array('ok' => true));
}

/* ═══════════════ DOCUMENTS ═══════════════ */
function gabochie_api_documents_list($request) {
    $id = (int) $request->get_param('id');
    $docs = get_posts(array(
        'post_type'      => 'project_document',
        'posts_per_page' => -1,
        'meta_query'     => array(array('key' => 'project_id', 'value' => $id, 'compare' => '=')),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    $items = array();
    foreach ($docs as $d) {
        $items[] = array(
            'id'         => $d->ID,
            'title'      => $d->post_title,
            'doc_type'   => get_post_meta($d->ID, 'doc_type', true),
            'file_url'   => get_post_meta($d->ID, 'file_url', true),
            'file_size'  => (int) get_post_meta($d->ID, 'file_size', true),
            'uploaded_by'=> (int) get_post_meta($d->ID, 'uploaded_by', true),
            'date'       => $d->post_date,
        );
    }
    return wp_json_encode($items);
}

function gabochie_api_document_create($request) {
    $id   = (int) $request->get_param('id');
    $body = json_decode($request->get_body(), true);
    $title = sanitize_text_field($body['title'] ?? '');
    $file_url = esc_url_raw($body['file_url'] ?? '');
    if (!$title || !$file_url) return new WP_REST_Response(array('error' => 'title and file_url required'), 400);
    $doc_type = sanitize_text_field($body['doc_type'] ?? 'other');
    $valid_types = array_keys(gabochie_value_document_types());
    if (!in_array($doc_type, $valid_types)) $doc_type = 'other';
    $post_id = wp_insert_post(array(
        'post_type'   => 'project_document',
        'post_title'  => $title,
        'post_status' => 'publish',
    ));
    if (is_wp_error($post_id)) return new WP_REST_Response(array('error' => 'Create failed'), 500);
    update_post_meta($post_id, 'project_id', $id);
    update_post_meta($post_id, 'file_url', $file_url);
    update_post_meta($post_id, 'doc_type', $doc_type);
    update_post_meta($post_id, 'file_size', (int) ($body['file_size'] ?? 0));
    update_post_meta($post_id, 'uploaded_by', get_current_user_id());
    return wp_json_encode(array('ok' => true, 'id' => $post_id));
}

function gabochie_api_document_delete($request) {
    $did = (int) $request->get_param('did');
    $project_id = get_post_meta($did, 'project_id', true);
    $title = get_the_title($did);
    wp_delete_post($did, true);
    gabochie_log_audit($project_id, 'document_deleted', $title);
    return wp_json_encode(array('ok' => true));
}

function gabochie_api_document_gaps($request) {
    $id = (int) $request->get_param('id');
    $docs = get_posts(array(
        'post_type'      => 'project_document',
        'posts_per_page' => -1,
        'meta_query'     => array(array('key' => 'project_id', 'value' => $id, 'compare' => '=')),
        'fields'         => 'ids',
    ));
    $uploaded = array();
    foreach ($docs as $d) {
        $t = get_post_meta($d, 'doc_type', true);
        if ($t) $uploaded[$t] = true;
    }
    $all_types = gabochie_value_document_types();
    $missing = array();
    foreach ($all_types as $key => $label) {
        if (!isset($uploaded[$key])) {
            $missing[] = array('type' => $key, 'label' => $label);
        }
    }
    return wp_json_encode(array(
        'uploaded_count' => count($docs),
        'total_types'    => count($all_types),
        'coverage'       => round(((count($all_types) - count($missing)) / count($all_types)) * 100),
        'missing'        => $missing,
    ));
}

/* ═══════════════ MESSAGES ═══════════════ */
function gabochie_api_messages_list($request) {
    $id = (int) $request->get_param('id');
    $page = (int) ($request->get_param('page') ?: 1);
    $per_page = 20;
    $posts = get_posts(array(
        'post_type'      => 'project_message',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'meta_query'     => array(array('key' => 'project_id', 'value' => $id, 'compare' => '=')),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    $total = (int) wp_count_posts('project_message')->publish;
    $items = array();
    foreach ($posts as $m) {
        $author = get_userdata($m->post_author);
        $items[] = array(
            'id'           => $m->ID,
            'content'      => $m->post_content,
            'author_name'  => $author ? $author->display_name : 'Unknown',
            'author_id'    => (int) $m->post_author,
            'is_client'    => (bool) get_post_meta($m->ID, 'is_from_client', true),
            'is_system'    => (bool) get_post_meta($m->ID, 'is_system_note', true),
            'is_read'      => (bool) get_post_meta($m->ID, 'is_read', true),
            'parent_id'    => (int) get_post_meta($m->ID, 'parent_message_id', true),
            'date'         => $m->post_date,
        );
    }
    return wp_json_encode(array('messages' => $items, 'total' => $total, 'page' => $page));
}

function gabochie_api_message_send($request) {
    $id   = (int) $request->get_param('id');
    $body = json_decode($request->get_body(), true);
    $content = sanitize_textarea_field($body['content'] ?? '');
    if (!$content) return new WP_REST_Response(array('error' => 'Content required'), 400);
    $is_client = !in_array('administrator', wp_get_current_user()->roles);
    $post_id = wp_insert_post(array(
        'post_type'    => 'project_message',
        'post_title'   => 'Message',
        'post_content' => $content,
        'post_author'  => get_current_user_id(),
        'post_status'  => 'publish',
    ));
    if (is_wp_error($post_id)) return new WP_REST_Response(array('error' => 'Send failed'), 500);
    update_post_meta($post_id, 'project_id', $id);
    update_post_meta($post_id, 'is_from_client', $is_client);
    update_post_meta($post_id, 'is_system_note', (bool) ($body['is_system'] ?? false));
    update_post_meta($post_id, 'parent_message_id', (int) ($body['parent_id'] ?? 0));
    return wp_json_encode(array('ok' => true, 'id' => $post_id));
}

function gabochie_api_message_mark_read($request) {
    $messid = (int) $request->get_param('messid');
    update_post_meta($messid, 'is_read', true);
    return wp_json_encode(array('ok' => true));
}

/* ═══════════════ AUDIT TRAIL ═══════════════ */
function gabochie_api_audit_list($request) {
    $id    = (int) $request->get_param('id');
    $limit = min((int) ($request->get_param('limit') ?: 50), 500);
    $posts = get_posts(array(
        'post_type'      => 'project_audit_log',
        'posts_per_page' => $limit,
        'meta_query'     => array(array('key' => 'project_id', 'value' => $id, 'compare' => '=')),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    $items = array();
    foreach ($posts as $p) {
        $actor = get_userdata(get_post_meta($p->ID, 'acted_by', true));
        $items[] = array(
            'id'      => $p->ID,
            'type'    => get_post_meta($p->ID, 'action_type', true),
            'detail'  => get_post_meta($p->ID, 'action_detail', true),
            'actor'   => $actor ? $actor->display_name : 'System',
            'date'    => $p->post_date,
        );
    }
    return wp_json_encode($items);
}

/* ═══════════════ COST ITEMS ═══════════════ */
function gabochie_api_costs_list($request) {
    $id = (int) $request->get_param('id');
    $posts = get_posts(array(
        'post_type'      => 'project_cost_item',
        'posts_per_page' => -1,
        'meta_query'     => array(array('key' => 'project_id', 'value' => $id, 'compare' => '=')),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    $total_estimated = 0;
    $total_actual = 0;
    $items = array();
    foreach ($posts as $p) {
        $amount = (float) get_post_meta($p->ID, 'amount', true);
        $status = get_post_meta($p->ID, 'cost_status', true);
        $total_estimated += $amount;
        if ($status === 'paid') $total_actual += $amount;
        $items[] = array(
            'id'       => $p->ID,
            'title'    => $p->post_title,
            'amount'   => $amount,
            'category' => get_post_meta($p->ID, 'category', true),
            'status'   => $status,
            'date'     => get_post_meta($p->ID, 'cost_date', true) ?: $p->post_date,
        );
    }
    return wp_json_encode(array(
        'items'           => $items,
        'total_estimated' => $total_estimated,
        'total_paid'      => $total_actual,
    ));
}

function gabochie_api_cost_create($request) {
    $id   = (int) $request->get_param('id');
    $body = json_decode($request->get_body(), true);
    $title = sanitize_text_field($body['title'] ?? '');
    if (!$title) return new WP_REST_Response(array('error' => 'Title required'), 400);
    $post_id = wp_insert_post(array(
        'post_type'   => 'project_cost_item',
        'post_title'  => $title,
        'post_status' => 'publish',
    ));
    if (is_wp_error($post_id)) return new WP_REST_Response(array('error' => 'Create failed'), 500);
    update_post_meta($post_id, 'project_id', $id);
    update_post_meta($post_id, 'amount', (float) ($body['amount'] ?? 0));
    update_post_meta($post_id, 'category', sanitize_text_field($body['category'] ?? 'other'));
    update_post_meta($post_id, 'cost_status', sanitize_text_field($body['status'] ?? 'estimated'));
    update_post_meta($post_id, 'cost_date', sanitize_text_field($body['date'] ?? ''));
    gabochie_log_audit($id, 'cost_created', $title . ': ' . ($body['amount'] ?? 0));
    return wp_json_encode(array('ok' => true, 'id' => $post_id));
}

function gabochie_api_cost_update($request) {
    $cid  = (int) $request->get_param('cid');
    $body = json_decode($request->get_body(), true);
    $project_id = get_post_meta($cid, 'project_id', true);
    if (isset($body['title'])) wp_update_post(array('ID' => $cid, 'post_title' => sanitize_text_field($body['title'])));
    if (isset($body['amount'])) update_post_meta($cid, 'amount', (float) $body['amount']);
    if (isset($body['category'])) update_post_meta($cid, 'category', sanitize_text_field($body['category']));
    if (isset($body['status'])) update_post_meta($cid, 'cost_status', sanitize_text_field($body['status']));
    if (isset($body['date'])) update_post_meta($cid, 'cost_date', sanitize_text_field($body['date']));
    gabochie_log_audit($project_id, 'cost_updated', get_the_title($cid) . ': ' . json_encode(array_keys($body)));
    return wp_json_encode(array('ok' => true));
}

function gabochie_api_cost_delete($request) {
    $cid = (int) $request->get_param('cid');
    $project_id = get_post_meta($cid, 'project_id', true);
    $title = get_the_title($cid);
    wp_delete_post($cid, true);
    gabochie_log_audit($project_id, 'cost_deleted', $title);
    return wp_json_encode(array('ok' => true));
}

/* ═══════════════ DASHBOARD ═══════════════ */
function gabochie_api_dashboard($request) {
    $user = wp_get_current_user();
    $is_admin = in_array('administrator', $user->roles);
    $args = array(
        'post_type'      => 'client_project',
        'posts_per_page' => $is_admin ? 20 : 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    if (!$is_admin) $args['author'] = $user->ID;
    $projects = get_posts($args);
    $results = array();
    foreach ($projects as $p) {
        $pid = $p->ID;
        $status = get_post_meta($pid, 'project_status', true);
        $stage  = get_post_meta($pid, 'project_stage', true);
        /* Milestones */
        $miles = get_posts(array(
            'post_type' => 'project_milestone', 'posts_per_page' => -1,
            'meta_query' => array(array('key' => 'project_id', 'value' => $pid, 'compare' => '=')),
        ));
        $completed_miles = 0;
        foreach ($miles as $m) {
            if (get_post_meta($m->ID, 'milestone_status', true) === 'completed') $completed_miles++;
        }
        /* Documents */
        $doc_count = count(get_posts(array(
            'post_type' => 'project_document', 'posts_per_page' => -1, 'fields' => 'ids',
            'meta_query' => array(array('key' => 'project_id', 'value' => $pid, 'compare' => '=')),
        )));
        /* Messages + unread */
        $msgs = get_posts(array(
            'post_type' => 'project_message', 'posts_per_page' => -1,
            'meta_query' => array(array('key' => 'project_id', 'value' => $pid, 'compare' => '=')),
        ));
        $unread = 0;
        foreach ($msgs as $m) {
            if (!get_post_meta($m->ID, 'is_read', true) && !get_post_meta($m->ID, 'is_from_client', true)) $unread++;
        }
        /* Costs */
        $costs = get_posts(array(
            'post_type' => 'project_cost_item', 'posts_per_page' => -1,
            'meta_query' => array(array('key' => 'project_id', 'value' => $pid, 'compare' => '=')),
        ));
        $total_cost = 0;
        foreach ($costs as $c) $total_cost += (float) get_post_meta($c->ID, 'amount', true);
        /* Recent audit */
        $audit = get_posts(array(
            'post_type' => 'project_audit_log', 'posts_per_page' => 5,
            'meta_query' => array(array('key' => 'project_id', 'value' => $pid, 'compare' => '=')),
            'orderby' => 'date', 'order' => 'DESC',
        ));
        $audit_items = array();
        foreach ($audit as $a) {
            $actor = get_userdata(get_post_meta($a->ID, 'acted_by', true));
            $audit_items[] = array(
                'type'   => get_post_meta($a->ID, 'action_type', true),
                'detail' => get_post_meta($a->ID, 'action_detail', true),
                'actor'  => $actor ? $actor->display_name : 'System',
                'date'   => $a->post_date,
            );
        }
        $all_stages = array_keys(gabochie_value_project_stages());
        $stage_index = array_search($stage, $all_stages);
        $results[] = array(
            'id'          => $pid,
            'title'       => $p->post_title,
            'status'      => $status,
            'status_label'=> gabochie_value_project_statuses()[$status] ?? $status,
            'stage'       => $stage,
            'stage_label' => gabochie_value_project_stages()[$stage] ?? $stage,
            'progress'    => $stage_index !== false ? round((($stage_index) / (count($all_stages) - 1)) * 100) : 0,
            'budget'      => (float) get_post_meta($pid, 'project_budget', true),
            'currency'    => get_post_meta($pid, 'project_currency', true) ?: 'GHS',
            'location'    => get_post_meta($pid, 'project_location', true),
            'milestones'  => array('total' => count($miles), 'completed' => $completed_miles),
            'documents'   => $doc_count,
            'messages'    => array('total' => count($msgs), 'unread' => $unread),
            'total_cost'  => $total_cost,
            'audit'       => $audit_items,
            'modified'    => $p->post_modified,
        );
    }
    return wp_json_encode($is_admin ? $results : ($results[0] ?? null));
}
