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
 * @param string $email Contact email.
 * @param array  $data  Contact context (first_name, last_name, phone, status,
 *                      contact_type, list_key, source, tags, utm_*).
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
 * @param string $email  Contact email.
 * @param string $status subscribed|unsubscribed|pending.
 * @return bool
 */
function gabochie_crm_set_status( $email, $status ) {
	return Gabochie_Crm::set_status( $email, $status );
}
