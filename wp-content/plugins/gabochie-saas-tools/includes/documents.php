<?php
defined('ABSPATH') || exit;

function gs_migrate_old_documents($user_id) {
    $migrated = get_user_meta($user_id, '_gs_docs_migrated', true);
    if ($migrated) return;
    $old_docs = get_user_meta($user_id, '_gs_documents', true);
    if (!is_array($old_docs) || empty($old_docs)) {
        update_user_meta($user_id, '_gs_docs_migrated', true);
        return;
    }
    foreach ($old_docs as $doc) {
        $post_id = wp_insert_post(array(
            'post_title'   => $doc['title'] ?? 'Untitled Document',
            'post_content' => $doc['output'] ?? $doc['summary'] ?? '',
            'post_status'  => 'publish',
            'post_type'    => 'saas_document',
            'post_author'  => $user_id,
            'meta_input'   => array(
                '_doc_type'   => $doc['type'] ?? '',
                '_doc_summary' => $doc['summary'] ?? '',
                '_doc_fields'  => $doc['fields'] ?? '',
                '_doc_status'  => $doc['status'] ?? 'draft',
                '_doc_tool_id' => $doc['tool_id'] ?? '',
            ),
        ));
        if ($post_id && !is_wp_error($post_id) && !empty($doc['created'])) {
            wp_update_post(array('ID' => $post_id, 'post_date' => $doc['created']));
        }
    }
    update_user_meta($user_id, '_gs_docs_migrated', true);
}

function gs_get_documents($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    gs_migrate_old_documents($user_id);
    $posts = get_posts(array(
        'post_type'      => 'saas_document',
        'author'         => $user_id,
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    $docs = array();
    foreach ($posts as $p) {
        $docs[] = array(
            'id'      => $p->ID,
            'title'   => $p->post_title,
            'output'  => $p->post_content,
            'summary' => get_post_meta($p->ID, '_doc_summary', true),
            'type'    => get_post_meta($p->ID, '_doc_type', true),
            'fields'  => get_post_meta($p->ID, '_doc_fields', true),
            'status'  => get_post_meta($p->ID, '_doc_status', true),
            'tool_id' => get_post_meta($p->ID, '_doc_tool_id', true),
            'created' => $p->post_date,
        );
    }
    return $docs;
}

function gs_get_document($id, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $p = get_post((int) $id);
    if (!$p || $p->post_type !== 'saas_document' || (int) $p->post_author !== (int) $user_id) return null;
    return array(
        'id'      => $p->ID,
        'title'   => $p->post_title,
        'output'  => $p->post_content,
        'summary' => get_post_meta($p->ID, '_doc_summary', true),
        'type'    => get_post_meta($p->ID, '_doc_type', true),
        'fields'  => get_post_meta($p->ID, '_doc_fields', true),
        'status'  => get_post_meta($p->ID, '_doc_status', true),
        'tool_id' => get_post_meta($p->ID, '_doc_tool_id', true),
        'created' => $p->post_date,
    );
}

function gs_save_document($data, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $post_id = wp_insert_post(array(
        'post_title'   => $data['title'] ?? 'Untitled Document',
        'post_content' => $data['output'] ?? '',
        'post_status'  => 'publish',
        'post_type'    => 'saas_document',
        'post_author'  => $user_id,
        'meta_input'   => array(
            '_doc_type'   => $data['type'] ?? '',
            '_doc_summary' => $data['summary'] ?? '',
            '_doc_fields'  => $data['fields'] ?? '',
            '_doc_status'  => $data['status'] ?? 'draft',
            '_doc_tool_id' => $data['tool_id'] ?? '',
        ),
    ));
    return $post_id;
}

function gs_delete_document($id, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $p = get_post((int) $id);
    if (!$p || $p->post_type !== 'saas_document' || (int) $p->post_author !== (int) $user_id) return;
    wp_trash_post((int) $id);
}

function gs_seed_demo_documents($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $existing = get_posts(array(
        'post_type'      => 'saas_document',
        'author'         => $user_id,
        'posts_per_page' => 1,
    ));
    if (!empty($existing)) return;
    $demos = array(
        array('title' => 'Design Proposal — Villa at East Legon', 'type' => 'Design Proposal', 'status' => 'final', 'summary' => '4-bedroom luxury villa, 450m², premium finish', 'output' => ''),
        array('title' => 'Feasibility Report — 16-Unit Dev\'t, Tema', 'type' => 'Feasibility Report', 'status' => 'draft', 'summary' => 'Mixed-use development, 16 units, Phase 1 analysis', 'output' => ''),
        array('title' => 'Cost Estimate — Office Block, Airport City', 'type' => 'Cost Estimate', 'status' => 'final', 'summary' => '5-storey office, G+4, standard finish, 2,800m²', 'output' => ''),
        array('title' => 'ROI Analysis — Apartment Purchase, Kumasi', 'type' => 'ROI Analysis', 'status' => 'final', 'summary' => '8-unit apartment building, gross yield 11.2%', 'output' => ''),
        array('title' => 'JV Term Sheet — Landowner-Developer Deal', 'type' => 'JV Term Sheet', 'status' => 'final', 'summary' => 'Equity split 40/60, profit share waterfall', 'output' => ''),
    );
    foreach ($demos as $d) { gs_save_document($d, $user_id); }
}
