<?php
/**
 * Module 2: Architect & QS Invoicing System
 */

if (!defined('ABSPATH')) {
    exit;
}

class BOQ_Module_QS_Invoicing {

    public static function create_invoice($client_name, $client_email, $project_title, $milestones, $currency = 'GHS') {
        $items = array();
        $total = 0;

        foreach ($milestones as $m) {
            $desc   = sanitize_text_field($m['description']);
            $amount = (float)$m['amount'];
            $items[] = array(
                'description' => $desc,
                'qty'         => 1,
                'unit'        => 'Stage',
                'rate'        => $amount,
                'amount'      => $amount,
            );
            $total += $amount;
        }

        $post_id = wp_insert_post(array(
            'post_title'   => 'INV-' . strtoupper(substr(md5(uniqid()), 0, 6)) . ' — ' . $project_title,
            'post_type'    => 'qs_invoice',
            'post_status'  => 'publish',
        ));

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_invoice_client_name', sanitize_text_field($client_name));
            update_post_meta($post_id, '_invoice_client_email', sanitize_email($client_email));
            update_post_meta($post_id, '_invoice_project_title', sanitize_text_field($project_title));
            update_post_meta($post_id, '_invoice_currency', sanitize_text_field($currency));
            update_post_meta($post_id, '_invoice_items', $items);
            update_post_meta($post_id, '_invoice_total', $total);
            update_post_meta($post_id, '_invoice_status', 'Pending');
            update_post_meta($post_id, '_invoice_date', date('Y-m-d'));
        }

        return array(
            'invoice_id' => $post_id,
            'number'     => get_the_title($post_id),
            'client'     => $client_name,
            'total'      => $total,
            'currency'   => $currency,
            'status'     => 'Pending',
        );
    }

    public static function get_client_invoices($email) {
        $args = array(
            'post_type'      => 'qs_invoice',
            'posts_per_page' => -1,
            'meta_key'       => '_invoice_client_email',
            'meta_value'     => sanitize_email($email),
        );

        $query = new WP_Query($args);
        $invoices = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $invoices[] = array(
                    'id'       => $id,
                    'title'    => get_the_title(),
                    'total'    => get_post_meta($id, '_invoice_total', true),
                    'currency' => get_post_meta($id, '_invoice_currency', true),
                    'status'   => get_post_meta($id, '_invoice_status', true),
                    'date'     => get_post_meta($id, '_invoice_date', true),
                );
            }
            wp_reset_postdata();
        }

        return $invoices;
    }
}
