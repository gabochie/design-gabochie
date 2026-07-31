<?php
/**
 * Class BOQ_Suite_CPT
 * Registers Custom Post Types and Custom Taxonomies.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BOQ_Suite_CPT {

    public static function register_cpts() {
        // 1. BOQ Project Templates
        register_post_type('boq_project', array(
            'labels' => array(
                'name'               => __('BOQ Projects', 'boq-construction-suite'),
                'singular_name'      => __('BOQ Project', 'boq-construction-suite'),
                'add_new_item'       => __('Add New BOQ Project', 'boq-construction-suite'),
                'edit_item'          => __('Edit BOQ Project', 'boq-construction-suite'),
            ),
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => 'buildsuite-pro',
            'supports'           => array('title', 'editor', 'custom-fields'),
            'show_in_rest'       => true,
        ));

        // 2. Material Rates Database
        register_post_type('boq_material', array(
            'labels' => array(
                'name'               => __('Unit Rates & Materials', 'boq-construction-suite'),
                'singular_name'      => __('Material Rate', 'boq-construction-suite'),
                'add_new_item'       => __('Add Material Rate', 'boq-construction-suite'),
                'edit_item'          => __('Edit Material Rate', 'boq-construction-suite'),
            ),
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => 'buildsuite-pro',
            'supports'           => array('title', 'custom-fields'),
            'show_in_rest'       => true,
        ));

        // 3. Client Invoices
        register_post_type('qs_invoice', array(
            'labels' => array(
                'name'               => __('QS Invoices', 'boq-construction-suite'),
                'singular_name'      => __('QS Invoice', 'boq-construction-suite'),
                'add_new_item'       => __('Create QS Invoice', 'boq-construction-suite'),
                'edit_item'          => __('Edit Invoice', 'boq-construction-suite'),
            ),
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => 'buildsuite-pro',
            'supports'           => array('title', 'editor', 'custom-fields'),
            'show_in_rest'       => true,
        ));

        // 4. Client Submissions & Leads
        register_post_type('boq_lead', array(
            'labels' => array(
                'name'               => __('Leads & Submissions', 'boq-construction-suite'),
                'singular_name'      => __('Lead Submission', 'boq-construction-suite'),
            ),
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => 'buildsuite-pro',
            'supports'           => array('title', 'custom-fields'),
            'show_in_rest'       => true,
        ));
    }

    public static function register_taxonomies() {
        register_taxonomy('project_location', array('boq_project', 'boq_material', 'attachment'), array(
            'labels' => array(
                'name'          => __('Project Locations', 'boq-construction-suite'),
                'singular_name' => __('Location', 'boq-construction-suite'),
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
        ));
    }
}
