<?php
/**
 * Agentic — Custom Post Types & Meta
 * @package Gabochie_Design
 */

if (!defined('ABSPATH')) exit;

/* ============================================
   CLIENT PROJECT CPT
   ============================================ */
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
});

/* ============================================
   PROJECT META FIELDS (stored in postmeta)
   ============================================ */
add_action('init', function () {
    $meta_fields = array(
        'project_status'       => array('type' => 'string',  'default' => 'inquiry'),
        'project_stage'        => array('type' => 'string',  'default' => 'Discovery'),
        'project_budget'       => array('type' => 'number',  'default' => 0),
        'project_currency'     => array('type' => 'string',  'default' => 'USD'),
        'project_location'     => array('type' => 'string',  'default' => ''),
        'next_milestone'       => array('type' => 'string',  'default' => ''),
        'next_milestone_date'  => array('type' => 'string',  'default' => ''),
        'service_type'         => array('type' => 'string',  'default' => 'architectural'),
        'client_phone'         => array('type' => 'string',  'default' => ''),
        'referral_source'      => array('type' => 'string',  'default' => ''),
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

    // Message meta
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

    // Document meta
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
});

/* ============================================
   STATUS CONSTANTS
   ============================================ */
function gabochie_project_statuses() {
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

function gabochie_project_stages() {
    return array(
        'Discovery'              => 'Discovery Call',
        'AI Concept'             => 'AI Concept Generation',
        'Refinement'             => 'Design Refinement',
        'Full Plans'             => 'Full Plans & Drawings',
        'Permitting'             => 'Permitting',
        'Construction'           => 'Construction',
        'Interior'               => 'Interior Design',
        'Handover'               => 'Handover',
    );
}

/* ============================================
   DASHBOARD SHORTCODE for dynamic data
   ============================================ */
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
    $statuses = gabochie_project_statuses();
    $stages   = gabochie_project_stages();
    return json_encode(array(
        'project_title' => $p->post_title,
        'status'        => $statuses[$status] ?? $status,
        'stage'         => $stages[$stage] ?? $stage,
        'next_milestone' => $milestone,
        'milestone_date' => $mdate,
    ));
});
