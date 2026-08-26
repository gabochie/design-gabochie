<?php
/**
 * Client Portal — Custom Post Types & Meta (Phase A Extended)
 * @package Gabochie_Value
 */

if (!defined('ABSPATH')) exit;

/* ─── POST TYPES ─── */
add_action('init', function () {
    register_post_type('client_project', array(
        'labels' => array(
            'name'          => 'Client Projects',
            'singular_name' => 'Client Project',
            'add_new_item'  => 'New Project',
            'edit_item'     => 'Edit Project',
        ),
        'public'            => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_rest'      => true,
        'rest_base'         => 'client-projects',
        'menu_icon'         => 'dashicons-building',
        'supports'          => array('title', 'author', 'comments'),
        'capability_type'   => 'post',
        'map_meta_cap'      => true,
    ));

    register_post_type('project_message', array(
        'labels' => array(
            'name'          => 'Project Messages',
            'singular_name' => 'Project Message',
        ),
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => false,
        'show_in_rest'      => true,
        'rest_base'         => 'project-messages',
        'supports'          => array('title', 'editor', 'author'),
    ));

    register_post_type('project_document', array(
        'labels' => array(
            'name'          => 'Project Documents',
            'singular_name' => 'Project Document',
        ),
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => false,
        'show_in_rest'      => true,
        'rest_base'         => 'project-documents',
        'supports'          => array('title', 'author'),
    ));

    register_post_type('project_milestone', array(
        'labels' => array(
            'name'          => 'Project Milestones',
            'singular_name' => 'Project Milestone',
        ),
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => false,
        'show_in_rest'      => true,
        'rest_base'         => 'project-milestones',
        'supports'          => array('title', 'editor'),
    ));

    register_post_type('project_cost_item', array(
        'labels' => array(
            'name'          => 'Cost Items',
            'singular_name' => 'Cost Item',
        ),
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => false,
        'show_in_rest'      => true,
        'rest_base'         => 'project-cost-items',
        'supports'          => array('title'),
    ));

    register_post_type('project_audit_log', array(
        'labels' => array(
            'name'          => 'Audit Trail',
            'singular_name' => 'Audit Entry',
        ),
        'public'            => false,
        'show_ui'           => false,
        'show_in_rest'      => true,
        'rest_base'         => 'project-audit-log',
        'supports'          => array('title'),
    ));
});

/* ─── META FIELDS — client_project ─── */
add_action('init', function () {
    $meta_fields = array(
        /* Original fields */
        'project_status'       => array('type' => 'string',  'default' => 'inquiry'),
        'project_stage'        => array('type' => 'string',  'default' => 'Discovery'),
        'project_budget'       => array('type' => 'number',  'default' => 0),
        'project_currency'     => array('type' => 'string',  'default' => 'USD'),
        'project_location'     => array('type' => 'string',  'default' => ''),
        'next_milestone'       => array('type' => 'string',  'default' => ''),
        'next_milestone_date'  => array('type' => 'string',  'default' => ''),
        'service_type'         => array('type' => 'string',  'default' => 'building'),
        'client_phone'         => array('type' => 'string',  'default' => ''),
        'referral_source'      => array('type' => 'string',  'default' => ''),
        /* Onboarding fields */
        'dev_goal'             => array('type' => 'string',  'default' => ''),
        'property_type'        => array('type' => 'string',  'default' => ''),
        'land_status'          => array('type' => 'string',  'default' => ''),
        'land_location'        => array('type' => 'string',  'default' => ''),
        'development_scale'    => array('type' => 'string',  'default' => ''),
        'budget_range'         => array('type' => 'string',  'default' => ''),
        'timeline'             => array('type' => 'string',  'default' => ''),
        'involvement'          => array('type' => 'string',  'default' => ''),
        'location_based'       => array('type' => 'string',  'default' => ''),
        'project_description'  => array('type' => 'string',  'default' => ''),
        /* Phase A — Workspace fields */
        'project_type'         => array('type' => 'string',  'default' => ''),   /* residential | commercial | mixed | renovation | interior */
        'target_users'         => array('type' => 'string',  'default' => ''),   /* owner-occupier | tenant | investor | mixed */
        'programme'            => array('type' => 'string',  'default' => ''),   /* JSON: {duration_months, phases:{phase:months}} */
        'site_info'            => array('type' => 'string',  'default' => ''),   /* JSON: {size_sqm, zoning, topography, access, utilities} */
        'assumptions'          => array('type' => 'string',  'default' => ''),   /* JSON array of assumption strings */
        'decisions_log'        => array('type' => 'string',  'default' => ''),   /* JSON array of {date,decision,by,note} */
        'current_phase'        => array('type' => 'string',  'default' => 'inquiry'), /* mirrors project_status but for workspace phases */
        'phase_history'        => array('type' => 'string',  'default' => ''),   /* JSON array of {phase,entered_at,exited_at,by} */
    );

    foreach ($meta_fields as $key => $config) {
        register_post_meta('client_project', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $config['type'],
            'default'       => $config['default'],
            'auth_callback' => '__return_true',
        ));
    }

    /* project_message meta */
    register_post_meta('project_message', 'project_id', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'integer',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_message', 'is_from_client', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'boolean',
        'default'       => true,
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_message', 'is_system_note', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'boolean',
        'default'       => false,
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_message', 'is_read', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'boolean',
        'default'       => false,
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_message', 'parent_message_id', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'integer',
        'default'       => 0,
        'auth_callback' => '__return_true',
    ));

    /* project_document meta */
    register_post_meta('project_document', 'project_id', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'integer',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_document', 'file_url', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_document', 'doc_type', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'default'       => 'other',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_document', 'file_size', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'integer',
        'default'       => 0,
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_document', 'uploaded_by', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'integer',
        'default'       => 0,
        'auth_callback' => '__return_true',
    ));

    /* project_milestone meta */
    register_post_meta('project_milestone', 'project_id', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'integer',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_milestone', 'target_date', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_milestone', 'completed_date', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'default'       => '',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_milestone', 'milestone_status', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'default'       => 'pending', /* pending | in_progress | completed | overdue */
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_milestone', 'milestone_stage', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'default'       => '',
        'auth_callback' => '__return_true',
    ));

    /* project_cost_item meta */
    register_post_meta('project_cost_item', 'project_id', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'integer',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_cost_item', 'amount', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'number',
        'default'       => 0,
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_cost_item', 'category', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'default'       => 'other',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_cost_item', 'cost_status', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'default'       => 'estimated', /* estimated | approved | paid | disputed */
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_cost_item', 'cost_date', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'auth_callback' => '__return_true',
    ));

    /* project_audit_log meta */
    register_post_meta('project_audit_log', 'project_id', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'integer',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_audit_log', 'action_type', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_audit_log', 'action_detail', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'default'       => '',
        'auth_callback' => '__return_true',
    ));
    register_post_meta('project_audit_log', 'acted_by', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'integer',
        'default'       => 0,
        'auth_callback' => '__return_true',
    ));
});

/* ─── HELPERS ─── */
function gabochie_value_project_statuses() {
    return array(
        'inquiry'       => 'Inquiry',
        'consultation'  => 'Consultation',
        'designing'     => 'Designing',
        'review'        => 'Under Review',
        'approved'      => 'Approved',
        'building'      => 'Construction',
        'completed'     => 'Completed',
        'on_hold'       => 'On Hold',
    );
}

function gabochie_value_project_stages() {
    return array(
        'Discovery'              => 'Discovery Call',
        'AI Concept'             => 'AI Concept Generation',
        'Refinement'             => 'Design Refinement',
        'Full Plans'             => 'Full Plans & Drawings',
        'Permitting'             => 'Permitting',
        'Procurement'            => 'Procurement',
        'Construction'           => 'Construction',
        'Site Prep'              => 'Site Preparation',
        'Foundation'             => 'Foundation',
        'Superstructure'         => 'Superstructure',
        'Finishes'               => 'Finishes & Fixtures',
        'M&E'                    => 'Mechanical & Electrical',
        'Inspection'             => 'Final Inspection',
        'Handover'               => 'Handover',
    );
}

function gabochie_value_project_types() {
    return array(
        'residential'  => 'Residential',
        'commercial'   => 'Commercial',
        'mixed'        => 'Mixed Use',
        'renovation'   => 'Renovation',
        'interior'     => 'Interior Design',
    );
}

function gabochie_value_document_types() {
    return array(
        'site_plan'        => 'Site Plan',
        'floor_plan'       => 'Floor Plan',
        'elevation'        => 'Elevation Drawing',
        'section'          => 'Section Drawing',
        '3d_render'        => '3D Render / Visualization',
        'structural_calc'  => 'Structural Calculations',
        'permit'           => 'Building Permit',
        'contract'         => 'Contract / Agreement',
        'invoice'          => 'Invoice / Payment',
        'progress_photo'   => 'Progress Photo',
        'inspection_report'=> 'Inspection Report',
        'other'            => 'Other Document',
    );
}

function gabochie_value_cost_categories() {
    return array(
        'land'           => 'Land Acquisition',
        'design'         => 'Design & Professional Fees',
        'permits'        => 'Permits & Approvals',
        'site_prep'      => 'Site Preparation',
        'foundation'     => 'Foundation & Structure',
        'finishes'       => 'Finishes & Fixtures',
        'm&e'            => 'Mechanical & Electrical',
        'exterior'       => 'Exterior & Landscaping',
        'furniture'      => 'Furniture & Equipment',
        'contingency'    => 'Contingency',
        'other'          => 'Other',
    );
}

/* ─── AUDIT TRAIL HELPER ─── */
function gabochie_log_audit($project_id, $action_type, $detail = '', $user_id = 0) {
    if (!$project_id || !$action_type) return;
    if (!$user_id) $user_id = get_current_user_id();
    $entry = wp_insert_post(array(
        'post_type'   => 'project_audit_log',
        'post_title'  => $action_type,
        'post_status' => 'publish',
    ));
    if ($entry && !is_wp_error($entry)) {
        update_post_meta($entry, 'project_id', (int) $project_id);
        update_post_meta($entry, 'action_type', sanitize_text_field($action_type));
        update_post_meta($entry, 'action_detail', sanitize_textarea_field($detail));
        update_post_meta($entry, 'acted_by', (int) $user_id);
    }
    /* Enforce 500-entry cap per project */
    $all = get_posts(array(
        'post_type'      => 'project_audit_log',
        'posts_per_page' => -1,
        'meta_query'     => array(array('key' => 'project_id', 'value' => $project_id, 'compare' => '=')),
        'fields'         => 'ids',
        'orderby'        => 'date',
        'order'          => 'ASC',
    ));
    if (count($all) > 500) {
        $to_delete = array_slice($all, 0, count($all) - 500);
        foreach ($to_delete as $id) wp_delete_post($id, true);
    }
}

/* ─── PHASE TRANSITION HELPER ─── */
function gabochie_transition_phase($project_id, $new_phase, $user_id = 0) {
    $old_phase = get_post_meta($project_id, 'current_phase', true);
    if ($old_phase === $new_phase) return;
    $history = json_decode(get_post_meta($project_id, 'phase_history', true), true);
    if (!is_array($history)) $history = array();
    /* Close old phase */
    if (!empty($history)) {
        $last = count($history) - 1;
        $history[$last]['exited_at'] = current_time('mysql');
    }
    $history[] = array(
        'phase'      => $new_phase,
        'entered_at' => current_time('mysql'),
        'exited_at'  => '',
        'by'         => $user_id ?: get_current_user_id(),
    );
    update_post_meta($project_id, 'current_phase', $new_phase);
    update_post_meta($project_id, 'phase_history', wp_json_encode($history));
    update_post_meta($project_id, 'project_stage', $new_phase);
    gabochie_log_audit($project_id, 'phase_transition', $old_phase . ' → ' . $new_phase, $user_id);
}

/* ─── SHORTCODE ─── */
add_shortcode('gabochie_project_data', function ($atts) {
    if (!is_user_logged_in()) return '';
    $user = wp_get_current_user();
    $projects = get_posts(array(
        'post_type'      => 'client_project',
        'author'         => $user->ID,
        'posts_per_page' => 1,
        'meta_key'       => 'project_status',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    if (empty($projects)) return '';
    $p = $projects[0];
    $status = get_post_meta($p->ID, 'project_status', true);
    $stage  = get_post_meta($p->ID, 'project_stage', true);
    $milestone = get_post_meta($p->ID, 'next_milestone', true);
    $mdate  = get_post_meta($p->ID, 'next_milestone_date', true);
    $statuses = gabochie_value_project_statuses();
    $stages   = gabochie_value_project_stages();
    return wp_json_encode(array(
        'project_title'  => $p->post_title,
        'status'         => $statuses[$status] ?? $status,
        'stage'          => $stages[$stage] ?? $stage,
        'next_milestone' => $milestone,
        'milestone_date' => $mdate,
    ));
});

/* ─── AUTO-LOG ON PROJECT SAVE ─── */
add_action('save_post_client_project', function ($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if ($update) {
        gabochie_log_audit($post_id, 'project_updated', 'Project record updated');
    } else {
        gabochie_log_audit($post_id, 'project_created', 'Project created: ' . $post->post_title);
    }
}, 10, 3);

/* ─── AUTO-LOG ON DOCUMENT UPLOAD ─── */
add_action('save_post_project_document', function ($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if (!$update) {
        $project_id = get_post_meta($post_id, 'project_id', true);
        $doc_type   = get_post_meta($post_id, 'doc_type', true);
        if ($project_id) {
            gabochie_log_audit($project_id, 'document_uploaded', $post->post_title . ' (' . ($doc_type ?: 'other') . ')');
        }
    }
}, 10, 3);

/* ─── AUTO-LOG ON MESSAGE ─── */
add_action('save_post_project_message', function ($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if (!$update) {
        $project_id = get_post_meta($post_id, 'project_id', true);
        $is_system  = get_post_meta($post_id, 'is_system_note', true);
        if ($project_id) {
            gabochie_log_audit($project_id, 'message_sent', $is_system ? 'System note' : 'Message: ' . wp_trim_words($post->post_content, 8));
        }
    }
}, 10, 3);
