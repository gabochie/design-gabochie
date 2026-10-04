<?php
/**
 * Gabochie CRM — public helpers.
 *
 * Programmatic entry points for capture points that hand a contact to the CRM
 * without knowing the class internals.
 *
 * @package Gabochie_Crm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Upsert a contact into the in-house store.
 *
 * @param  string $email Contact email.
 * @param  array  $data  Contact context (first_name, last_name, phone, status,
 *                       contact_type, list_key, source, tags, utm_*).
 * @return int Row id on success, 0 on failure.
 */
function gabochie_crm_upsert( $email, array $data = array() ) {
	return Gabochie_Crm::upsert_contact( $email, $data );
}

/**
 * All subscribed contacts (email + name parts + phone).
 *
 * @return array<int,array{email:string,first_name:string,last_name:string,phone:string}>
 */
function gabochie_crm_subscribers() {
	return Gabochie_Crm::subscribers();
}

/**
 * Flip one contact's status.
 *
 * @param  string $email  Contact email.
 * @param  string $status subscribed|unsubscribed|pending.
 * @return bool
 */
function gabochie_crm_set_status( $email, $status ) {
	return Gabochie_Crm::set_status( $email, $status );
}

/**
 * Record engagement (newsletter open/click, login) on a contact.
 *
 * @param  string $email Contact email.
 * @return bool
 */
function gabochie_crm_stamp_engagement( $email ) {
	return Gabochie_Crm::stamp_engagement( $email );
}

/**
 * Search / list contacts with filters and pagination.
 *
 * @param  array $args Query arguments (search, status, source, list_key, tag,
 *                     page, per_page, orderby, order).
 * @return array{items:array,total:int}
 */
function gabochie_crm_search_contacts( array $args = array() ) {
	return Gabochie_Crm::search_contacts( $args );
}

/**
 * Hard-delete a contact row by id.
 *
 * @param  int $id Contact row id.
 * @return bool
 */
function gabochie_crm_delete_contact( $id ) {
	return Gabochie_Crm::delete_contact( $id );
}

/**
 * Merge-update an existing contact row by id.
 *
 * @param  int   $id   Contact row id.
 * @param  array $data Optional fields (only provided fields are written).
 * @return bool
 */
function gabochie_crm_update_contact( $id, array $data = array() ) {
	return Gabochie_Crm::update_contact( $id, $data );
}

/**
 * Completed-order totals per billing email, keyed by lowercase email.
 *
 * @return array
 */
function gabochie_crm_order_stats() {
	if ( ! class_exists( 'Gabochie_Crm_Purchases' ) ) {
		return array();
	}
	return Gabochie_Crm_Purchases::order_stats();
}

/**
 * Customers for the admin view: contacts joined to completed-order totals.
 *
 * @param array $args search, per_page, page.
 * @return array{total:int,items:array}
 */
function gabochie_crm_customers( array $args = array() ) {
	if ( ! class_exists( 'Gabochie_Crm_Purchases' ) ) {
		return array(
			'total' => 0,
			'items' => array(),
		);
	}
	return Gabochie_Crm_Purchases::customers( $args );
}

/**
 * Manually capture a completed order as a customer (backfill / repair).
 *
 * @param int $order_id WooCommerce order id.
 * @return void
 */
function gabochie_crm_capture_order( $order_id ) {
	if ( class_exists( 'Gabochie_Crm_Purchases' ) ) {
		Gabochie_Crm_Purchases::on_order_completed( $order_id );
	}
}
