<?php
/**
 * Gabochie Lead Hub — public helper.
 *
 * Programmatic entry point for capture points that want to hand a lead to the
 * hub without knowing the class internals.
 *
 * @package Gabochie_Lead_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Route a lead into the pipeline (Gabochie CRM when ready, else backlog).
 *
 * @param string $email Lead email.
 * @param array  $data  Lead context (list, source, name, phone, journey, blocker, utm_*).
 * @return bool
 */
function gabochie_lead_hub_capture( $email, array $data = array() ) {
	return Gabochie_Lead_Hub::instance()->capture( $email, $data );
}
