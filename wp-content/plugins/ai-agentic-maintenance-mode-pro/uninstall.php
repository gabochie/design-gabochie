<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aimmp_leads" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aimmp_conversations" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aimmp_knowledge_chunks" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aimmp_quotations" );

delete_option( 'aimmp_settings' );
