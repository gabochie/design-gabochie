<?php
/**
 * Gabochie CRM — uninstaller.
 *
 * Drops the contact table. Bounded, idempotent, WPCS-clean.
 *
 * @package Gabochie_Crm
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;
$table = $wpdb->prefix . 'gab_contacts';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );