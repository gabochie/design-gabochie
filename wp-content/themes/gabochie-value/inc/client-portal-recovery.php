<?php
/**
 * Client Portal — Project Recovery Workspace (MVP)
 * Single solid product: paid Recovery Diagnostic unlocks a step-by-step
 * workspace where owners take charge; facilitators can co-drive.
 *
 * @package Gabochie_Value
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ────────────────────────────────────────────
   1. STATUS ENUM + HELPERS
   ──────────────────────────────────────────── */

function gabochie_recovery_statuses() {
	return array(
		'diagnosed'   => 'Diagnosed',
		'triage'      => 'Triage',
		'evidence'    => 'Evidence Gathering',
		'plan'        => 'Recovery Plan',
		'executing'   => 'Executing',
		'verification'=> 'Verification',
		'resolved'    => 'Resolved',
		'reconsider'  => 'Reconsider',
	);
}

function gabochie_recovery_steps() {
	return array(
		'triage'       => array( 'label' => 'Triage & Scope',      'desc' => 'Confirm what is stuck and what is still viable.' ),
		'evidence'     => array( 'label' => 'Evidence',            'desc' => 'Upload drawings, contracts, photos, permits.' ),
		'plan'         => array( 'label' => 'Recovery Plan',       'desc' => 'Agree the fix, cost and sequence.' ),
		'executing'    => array( 'label' => 'Execute',             'desc' => 'Track milestones and costs as work resumes.' ),
		'verification' => array( 'label' => 'Verify & Handover',   'desc' => 'Inspect, snag and close out.' ),
	);
}

function gabochie_recovery_is_active( $project_id ) {
	$status = get_post_meta( $project_id, 'project_status', true );
	return in_array( $status, array( 'recovery_diagnostic', 'recovery_active', 'recovery_verification', 'recovery_resolved' ), true )
		|| '' !== get_post_meta( $project_id, 'recovery_status', true );
}

function gabochie_recovery_current_step( $project_id ) {
	$s = get_post_meta( $project_id, 'recovery_status', true );
	if ( '' === $s || 'diagnosed' === $s ) {
		return 'triage';
	}
	if ( isset( gabochie_recovery_steps()[ $s ] ) ) {
		return $s;
	}
	// Map legacy / terminal states.
	if ( 'resolved' === $s || 'reconsider' === $s ) {
		return 'verification';
	}
	return 'triage';
}

/* ────────────────────────────────────────────
   2. META REGISTRATION
   ──────────────────────────────────────────── */
add_action( 'init', function () {
	$fields = array(
		'recovery_status'         => array( 'type' => 'string', 'default' => '' ),
		'recovery_checklist'      => array( 'type' => 'string', 'default' => '' ),
		'recovery_score'          => array( 'type' => 'number', 'default' => 0 ),
		'facilitator_id'          => array( 'type' => 'number', 'default' => 0 ),
		'recovery_provisioned_at' => array( 'type' => 'string', 'default' => '' ),
		'recovery_paid_order_id'  => array( 'type' => 'number', 'default' => 0 ),
		'recovery_plan_approved'  => array( 'type' => 'string', 'default' => '' ),
	);
	foreach ( $fields as $key => $cfg ) {
		register_post_meta(
			'client_project',
			$key,
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => $cfg['type'],
				'default'       => $cfg['default'],
				'auth_callback' => '__return_true',
			)
		);
	}
} );

/* ────────────────────────────────────────────
   3. FACILITATOR ROLE
   ──────────────────────────────────────────── */
add_action( 'init', function () {
	if ( get_option( 'gabochie_recovery_role_seeded' ) ) {
		return;
	}
	$role = get_role( 'project_facilitator' );
	if ( ! $role ) {
		$subscriber = get_role( 'subscriber' );
		$caps       = $subscriber ? $subscriber->capabilities : array( 'read' => true );
		add_role( 'project_facilitator', 'Project Facilitator', $caps );
		$role = get_role( 'project_facilitator' );
	}
	if ( $role ) {
		$role->add_cap( 'read' );
		$role->add_cap( 'manage_recovery' );
	}
	update_option( 'gabochie_recovery_role_seeded', true );
} );

/**
 * Check if user can access recovery project (owner, facilitator, admin).
 *
 * @param int $project_id Project ID.
 * @param int $user_id    User ID.
 * @return bool
 */
function gabochie_recovery_can_access( $project_id, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return false;
	}
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}
	if ( in_array( 'administrator', (array) $user->roles, true ) ) {
		return true;
	}
	$project = get_post( $project_id );
	if ( ! $project || 'client_project' !== $project->post_type ) {
		return false;
	}
	if ( (int) $project->post_author === (int) $user_id ) {
		return true;
	}
	$fid = (int) get_post_meta( $project_id, 'facilitator_id', true );
	if ( $fid && $fid === (int) $user_id ) {
		return true;
	}
	if ( in_array( 'project_facilitator', (array) $user->roles, true ) ) {
		// Facilitators assigned explicitly only — no open access.
		return false;
	}
	return false;
}

/* ────────────────────────────────────────────
   4. CHECKLIST TEMPLATES (blocker → tasks)
   ──────────────────────────────────────────── */
function gabochie_recovery_templates() {
	// Covers all building types in Ghana — tasks are type-agnostic, doc_type guides evidence.
	return array(
		'design'      => array(
			array( 'label' => 'Upload current drawings & brief (all building types)', 'doc_type' => 'floor_plan' ),
			array( 'label' => 'Record design gaps and missing info', 'doc_type' => '' ),
		),
		'budget'      => array(
			array( 'label' => 'Upload spend records & invoices', 'doc_type' => 'invoice' ),
			array( 'label' => 'Estimate cost to complete (GHS)', 'doc_type' => '' ),
		),
		'contractor'  => array(
			array( 'label' => 'Upload contractor agreement & correspondence', 'doc_type' => 'contract' ),
			array( 'label' => 'Document defects / disputes with photos', 'doc_type' => 'progress_photo' ),
		),
		'land'        => array(
			array( 'label' => 'Upload title / indenture and site plan', 'doc_type' => 'site_plan' ),
			array( 'label' => 'Confirm land dispute status', 'doc_type' => '' ),
		),
		'permits'     => array(
			array( 'label' => 'Upload permit filings & responses', 'doc_type' => 'permit' ),
			array( 'label' => 'Check approval prerequisites (Ghana)', 'doc_type' => '' ),
		),
		'scope'       => array(
			array( 'label' => 'Define scope and deliverables (by building type)', 'doc_type' => '' ),
			array( 'label' => 'Upload any existing scope doc', 'doc_type' => 'other' ),
		),
		'abandoned'   => array(
			array( 'label' => 'Upload site photos (current state)', 'doc_type' => 'progress_photo' ),
			array( 'label' => 'Record last activity date & risks', 'doc_type' => '' ),
		),
		'visibility'  => array(
			array( 'label' => 'Set reporting cadence (owner / facilitator)', 'doc_type' => '' ),
			array( 'label' => 'Upload diaspora contact & access notes', 'doc_type' => 'other' ),
		),
	);
}

function gabochie_recovery_default_steps_for_blockers( $blockers ) {
	$templates = gabochie_recovery_templates();
	$tasks     = array();
	$seen      = array();

	// Always include triage core.
	$tasks[] = array( 'id' => 'triage_confirm', 'label' => 'Confirm diagnostic tier & recovery intent', 'status' => 'pending', 'doc_type' => '', 'blocker' => '' );

	foreach ( (array) $blockers as $b ) {
		$b = sanitize_key( $b );
		if ( ! isset( $templates[ $b ] ) ) {
			continue;
		}
		foreach ( $templates[ $b ] as $t ) {
			$key = sanitize_title( $t['label'] );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$tasks[]      = array(
				'id'       => $key,
				'label'    => $t['label'],
				'status'   => 'pending',
				'doc_type' => $t['doc_type'],
				'blocker'  => $b,
			);
		}
	}

	// Cap to 10 items.
	return array_slice( $tasks, 0, 10 );
}

/* ────────────────────────────────────────────
   5. PROVISIONING — free diagnostic → paid workspace
   ──────────────────────────────────────────── */

/**
 * Seed recovery checklist + milestones for a project.
 *
 * @param int   $project_id Project ID.
 * @param array $blockers   Blocker slugs.
 * @return void
 */
function gabochie_recovery_seed( $project_id, $blockers = array() ) {
	$existing = get_post_meta( $project_id, 'recovery_checklist', true );
	if ( '' !== $existing && '[]' !== $existing ) {
		$decoded = json_decode( $existing, true );
		if ( is_array( $decoded ) && count( $decoded ) > 0 ) {
			return;
		}
	}
	$checklist = gabochie_recovery_default_steps_for_blockers( $blockers );
	if ( empty( $checklist ) ) {
		$checklist = array(
			array( 'id' => 'triage_confirm', 'label' => 'Confirm diagnostic tier & recovery intent', 'status' => 'pending', 'doc_type' => '', 'blocker' => '' ),
			array( 'id' => 'upload-photos', 'label' => 'Upload site photos (current state)', 'status' => 'pending', 'doc_type' => 'progress_photo', 'blocker' => 'abandoned' ),
			array( 'id' => 'upload-drawings', 'label' => 'Upload current drawings & brief', 'status' => 'pending', 'doc_type' => 'floor_plan', 'blocker' => 'design' ),
		);
	}
	update_post_meta( $project_id, 'recovery_checklist', wp_json_encode( $checklist ) );

	// Seed milestones for each recovery step if none exist.
	$existing_miles = get_posts(
		array(
			'post_type'      => 'project_milestone',
			'posts_per_page' => 1,
			'meta_query'     => array( array( 'key' => 'project_id', 'value' => $project_id, 'compare' => '=' ) ),
			'fields'         => 'ids',
		)
	);
	if ( empty( $existing_miles ) ) {
		foreach ( gabochie_recovery_steps() as $slug => $step ) {
			$mid = wp_insert_post(
				array(
					'post_type'   => 'project_milestone',
					'post_title'  => $step['label'],
					'post_status' => 'publish',
				)
			);
			if ( $mid && ! is_wp_error( $mid ) ) {
				update_post_meta( $mid, 'project_id', $project_id );
				update_post_meta( $mid, 'milestone_stage', 'Recovery: ' . $step['label'] );
				update_post_meta( $mid, 'milestone_status', 'pending' );
			}
		}
	}
}

/**
 * Provision recovery workspace after diagnostic purchase.
 *
 * @param int $order_id Order ID.
 * @param int $project_id Project ID to provision (0 = auto-find latest diagnostic for order user).
 * @return int Project ID provisioned or 0.
 */
function gabochie_recovery_provision( $order_id, $project_id = 0 ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return 0;
	}
	$user_id = $order->get_customer_id();
	if ( ! $user_id ) {
		$email = $order->get_billing_email();
		$user  = $email ? get_user_by( 'email', $email ) : null;
		$user_id = $user ? $user->ID : 0;
	}
	if ( ! $user_id ) {
		return 0;
	}

	if ( ! $project_id ) {
		$projects = get_posts(
			array(
				'post_type'      => 'client_project',
				'author'         => $user_id,
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array( 'key' => 'project_status', 'value' => array( 'recovery_diagnostic', 'recovery_active' ), 'compare' => 'IN' ),
				),
			)
		);
		if ( empty( $projects ) ) {
			$projects = get_posts(
				array(
					'post_type'      => 'client_project',
					'author'         => $user_id,
					'posts_per_page' => 1,
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);
		}
		$project_id = ! empty( $projects ) ? $projects[0]->ID : 0;
	}

	if ( ! $project_id ) {
		return 0;
	}

	$current = get_post_meta( $project_id, 'project_status', true );
	if ( 'recovery_active' === $current ) {
		return $project_id;
	}

	$blockers_raw = get_post_meta( $project_id, 'rec_blockers', true );
	$blockers     = $blockers_raw ? array_map( 'trim', explode( ',', $blockers_raw ) ) : array();

	update_post_meta( $project_id, 'project_status', 'recovery_active' );
	if ( '' === get_post_meta( $project_id, 'recovery_status', true ) ) {
		update_post_meta( $project_id, 'recovery_status', 'triage' );
	}
	update_post_meta( $project_id, 'recovery_paid_order_id', $order_id );
	update_post_meta( $project_id, 'recovery_provisioned_at', current_time( 'mysql' ) );

	gabochie_recovery_seed( $project_id, $blockers );

	if ( function_exists( 'gabochie_log_audit' ) ) {
		gabochie_log_audit( $project_id, 'recovery_provisioned', 'Order #' . $order_id . ' — workspace active' );
	}

	// Email owner.
	$user = get_userdata( $user_id );
	if ( $user && $user->user_email ) {
		$site_name = get_bloginfo( 'name' );
		$dash_url  = home_url( '/dashboard/?project=' . $project_id . '#recovery' );
		wp_mail(
			$user->user_email,
			'Your Recovery Workspace is ready — ' . $site_name,
			"Hi " . ( $user->display_name ?: $user->user_login ) . ",\n\n" .
			"Your Project Recovery Diagnostic is confirmed. Your step-by-step Recovery Workspace is now active.\n\n" .
			"Open your workspace:\n" . esc_url( $dash_url ) . "\n\n" .
			"Pick up at Triage — work through Evidence → Plan → Execution with your facilitator.\n" .
			"— The " . $site_name . " Team",
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);
	}

	return $project_id;
}

/* Hook: WooCommerce order completed → provision if it contains recovery product. */
add_action( 'woocommerce_order_status_completed', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$has_recovery = false;
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}
		if ( 'project-recovery-diagnostic' === $product->get_slug() ) {
			$has_recovery = true;
			break;
		}
		// Fallback: check bridge for level 4.
		$map = (array) get_option( '_pmprowoo_product_levels', array() );
		$pid = $product->get_id();
		if ( isset( $map[ $pid ] ) ) {
			foreach ( (array) $map[ $pid ] as $lvl ) {
				if ( 4 === (int) $lvl ) {
					$has_recovery = true;
					break 2;
				}
			}
		}
	}
	if ( $has_recovery ) {
		gabochie_recovery_provision( $order_id );
	}
}, 10, 1 );

/* Also provision immediately on BACS auto-complete (orders go straight to completed). */
add_action( 'woocommerce_payment_complete', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	if ( 'bacs' !== $order->get_payment_method() && 'gabochie_defi' !== $order->get_payment_method() ) {
		return;
	}
	$has_recovery = false;
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( $product && 'project-recovery-diagnostic' === $product->get_slug() ) {
			$has_recovery = true;
			break;
		}
	}
	if ( $has_recovery ) {
		// Delay slightly to ensure order meta is saved.
		wp_schedule_single_event( time() + 5, 'gabochie_recovery_provision_event', array( $order_id ) );
	}
} );

add_action( 'gabochie_recovery_provision_event', function ( $order_id ) {
	gabochie_recovery_provision( $order_id );
} );

/* ────────────────────────────────────────────
   6. REST — Recovery stepper
   ──────────────────────────────────────────── */
add_action( 'rest_api_init', function () {
	$ns = 'gabochie/v1';

	register_rest_route(
		$ns,
		'/recovery/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'gabochie_api_recovery_get',
			'permission_callback' => 'gabochie_api_recovery_access',
		)
	);

	register_rest_route(
		$ns,
		'/recovery/(?P<id>\d+)/advance',
		array(
			'methods'             => 'POST',
			'callback'            => 'gabochie_api_recovery_advance',
			'permission_callback' => 'gabochie_api_recovery_access',
		)
	);

	register_rest_route(
		$ns,
		'/recovery/(?P<id>\d+)/checklist',
		array(
			'methods'             => 'POST',
			'callback'            => 'gabochie_api_recovery_checklist',
			'permission_callback' => 'gabochie_api_recovery_access',
		)
	);

	register_rest_route(
		$ns,
		'/recovery/(?P<id>\d+)/approve-plan',
		array(
			'methods'             => 'POST',
			'callback'            => 'gabochie_api_recovery_approve_plan',
			'permission_callback' => 'gabochie_api_recovery_owner_only',
		)
	);
} );

function gabochie_api_recovery_access( $request ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$id  = (int) $request->get_param( 'id' );
	$uid = get_current_user_id();
	if ( gabochie_recovery_can_access( $id, $uid ) ) {
		return true;
	}
	// Fallback to standard workspace access (owner/admin).
	if ( function_exists( 'gabochie_api_client_access' ) ) {
		return gabochie_api_client_access( $request );
	}
	return false;
}

function gabochie_api_recovery_owner_only( $request ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$id      = (int) $request->get_param( 'id' );
	$project = get_post( $id );
	if ( ! $project ) {
		return false;
	}
	$uid = get_current_user_id();
	if ( (int) $project->post_author === $uid ) {
		return true;
	}
	$user = wp_get_current_user();
	if ( in_array( 'administrator', (array) $user->roles, true ) ) {
		return true;
	}
	return false;
}

function gabochie_api_recovery_get( $request ) {
	$id      = (int) $request->get_param( 'id' );
	$project = get_post( $id );
	if ( ! $project || 'client_project' !== $project->post_type ) {
		return new WP_Error( 'not_found', 'Project not found', array( 'status' => 404 ) );
	}

	$status    = get_post_meta( $id, 'recovery_status', true ) ?: 'diagnosed';
	$checklist = json_decode( get_post_meta( $id, 'recovery_checklist', true ), true );
	if ( ! is_array( $checklist ) ) {
		$checklist = array();
	}
	$blockers_raw = get_post_meta( $id, 'rec_blockers', true );
	$steps        = gabochie_recovery_steps();
	$current_step = gabochie_recovery_current_step( $id );
	$order_id     = (int) get_post_meta( $id, 'recovery_paid_order_id', true );
	$provisioned  = get_post_meta( $id, 'recovery_provisioned_at', true );
	$facilitator  = (int) get_post_meta( $id, 'facilitator_id', true );
	$plan_approved = get_post_meta( $id, 'recovery_plan_approved', true );

	// Coverage for evidence gate.
	$docs     = get_posts(
		array(
			'post_type'      => 'project_document',
			'posts_per_page' => -1,
			'meta_query'     => array( array( 'key' => 'project_id', 'value' => $id, 'compare' => '=' ) ),
			'fields'         => 'ids',
		)
	);
	$uploaded = array();
	foreach ( $docs as $d ) {
		$t = get_post_meta( $d, 'doc_type', true );
		if ( $t ) {
			$uploaded[ $t ] = true;
		}
	}

	$progress = 0;
	$step_keys = array_keys( $steps );
	$current_idx = array_search( $current_step, $step_keys, true );
	if ( false !== $current_idx ) {
		$progress = round( ( $current_idx / max( 1, count( $step_keys ) - 1 ) ) * 100 );
	}
	if ( 'resolved' === $status ) {
		$progress = 100;
	}

	return rest_ensure_response(
		array(
			'project_id'     => $id,
			'title'          => $project->post_title,
			'recovery_status'=> $status,
			'current_step'   => $current_step,
			'progress'       => $progress,
			'steps'          => $steps,
			'checklist'      => $checklist,
			'blockers'       => $blockers_raw,
			'provisioned'    => $provisioned,
			'paid_order_id'  => $order_id,
			'facilitator_id' => $facilitator,
			'plan_approved'  => $plan_approved,
			'coverage'       => array(
				'uploaded_types' => array_keys( $uploaded ),
				'doc_count'      => count( $docs ),
			),
		)
	);
}

function gabochie_api_recovery_advance( $request ) {
	$id   = (int) $request->get_param( 'id' );
	$body = json_decode( $request->get_body(), true );
	$to   = sanitize_key( $body['to'] ?? '' );

	$current = gabochie_recovery_current_step( $id );
	$steps   = array_keys( gabochie_recovery_steps() );

	// Allow: next sequential step only, unless admin/facilitator forces.
	$current_idx = array_search( $current, $steps, true );
	$to_idx      = array_search( $to, $steps, true );

	if ( false === $to_idx ) {
		return new WP_Error( 'invalid', 'Invalid step', array( 'status' => 400 ) );
	}

	$user    = wp_get_current_user();
	$is_admin_or_facilitator = in_array( 'administrator', (array) $user->roles, true ) || (int) get_post_meta( $id, 'facilitator_id', true ) === (int) $user->ID;

	if ( ! $is_admin_or_facilitator && $to_idx !== $current_idx + 1 ) {
		return new WP_Error( 'forbidden', 'Move to next step only', array( 'status' => 403 ) );
	}

	// Gate: evidence -> plan requires at least 1 doc OR 40% coverage proxy.
	if ( 'plan' === $to && 'evidence' === $current ) {
		$docs = get_posts(
			array(
				'post_type'      => 'project_document',
				'posts_per_page' => -1,
				'meta_query'     => array( array( 'key' => 'project_id', 'value' => $id, 'compare' => '=' ) ),
				'fields'         => 'ids',
			)
		);
		if ( count( $docs ) < 1 ) {
			return new WP_Error( 'blocked', 'Upload at least one evidence document before planning.', array( 'status' => 400 ) );
		}
	}

	// Gate: plan -> executing requires owner approval.
	if ( 'executing' === $to && 'plan' === $current ) {
		$approved = get_post_meta( $id, 'recovery_plan_approved', true );
		if ( 'yes' !== $approved && ! $is_admin_or_facilitator ) {
			return new WP_Error( 'blocked', 'Owner must approve the recovery plan first.', array( 'status' => 400 ) );
		}
	}

	update_post_meta( $id, 'recovery_status', $to );
	if ( function_exists( 'gabochie_log_audit' ) ) {
		gabochie_log_audit( $id, 'recovery_advance', $current . ' → ' . $to );
	}

	// Keep project_status in sync for dashboard filter.
	if ( 'resolved' === $to ) {
		update_post_meta( $id, 'project_status', 'recovery_resolved' );
	} elseif ( in_array( $to, array( 'executing', 'verification' ), true ) ) {
		update_post_meta( $id, 'project_status', 'recovery_active' );
	}

	return gabochie_api_recovery_get( $request );
}

function gabochie_api_recovery_checklist( $request ) {
	$id   = (int) $request->get_param( 'id' );
	$body = json_decode( $request->get_body(), true );
	$item_id = sanitize_title( $body['item_id'] ?? '' );
	$status  = sanitize_key( $body['status'] ?? '' ); // pending|done

	if ( '' === $item_id || ! in_array( $status, array( 'pending', 'done' ), true ) ) {
		return new WP_Error( 'invalid', 'item_id and status (pending|done) required', array( 'status' => 400 ) );
	}

	$raw       = get_post_meta( $id, 'recovery_checklist', true );
	$checklist = json_decode( $raw, true );
	if ( ! is_array( $checklist ) ) {
		$checklist = array();
	}

	$found = false;
	foreach ( $checklist as &$item ) {
		if ( $item['id'] === $item_id ) {
			$item['status'] = $status;
			$found          = true;
			break;
		}
	}
	unset( $item );

	if ( ! $found ) {
		return new WP_Error( 'not_found', 'Checklist item not found', array( 'status' => 404 ) );
	}

	update_post_meta( $id, 'recovery_checklist', wp_json_encode( $checklist ) );
	if ( function_exists( 'gabochie_log_audit' ) ) {
		gabochie_log_audit( $id, 'recovery_checklist', $item_id . ' → ' . $status );
	}

	return rest_ensure_response( array( 'ok' => true, 'checklist' => $checklist ) );
}

function gabochie_api_recovery_approve_plan( $request ) {
	$id = (int) $request->get_param( 'id' );
	update_post_meta( $id, 'recovery_plan_approved', 'yes' );
	if ( function_exists( 'gabochie_log_audit' ) ) {
		gabochie_log_audit( $id, 'recovery_plan_approved', 'Owner approved plan' );
	}
	return rest_ensure_response( array( 'ok' => true ) );
}

/* Assign facilitator endpoint (admin only) */
add_action( 'rest_api_init', function () {
	register_rest_route(
		'gabochie/v1',
		'/recovery/(?P<id>\d+)/facilitator',
		array(
			'methods'             => 'POST',
			'callback'            => function ( $request ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return new WP_Error( 'forbidden', 'Admins only', array( 'status' => 403 ) );
				}
				$id  = (int) $request->get_param( 'id' );
				$body = json_decode( $request->get_body(), true );
				$fid  = (int) ( $body['facilitator_id'] ?? 0 );
				if ( $fid ) {
					$user = get_userdata( $fid );
					if ( ! $user ) {
						return new WP_Error( 'not_found', 'User not found', array( 'status' => 404 ) );
					}
				}
				update_post_meta( $id, 'facilitator_id', $fid );
				if ( function_exists( 'gabochie_log_audit' ) ) {
					gabochie_log_audit( $id, 'facilitator_assigned', $fid ? get_userdata( $fid )->user_login : 'unassigned' );
				}
				return rest_ensure_response( array( 'ok' => true, 'facilitator_id' => $fid ) );
			},
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		)
	);
} );

/* Seed on diagnostic creation if recovery_status empty */
add_action( 'save_post_client_project', function ( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	$ps = get_post_meta( $post_id, 'project_status', true );
	if ( 'recovery_diagnostic' !== $ps ) {
		return;
	}
	if ( '' !== get_post_meta( $post_id, 'recovery_status', true ) ) {
		return;
	}
	update_post_meta( $post_id, 'recovery_status', 'diagnosed' );
	$blockers_raw = get_post_meta( $post_id, 'rec_blockers', true );
	$blockers     = $blockers_raw ? array_map( 'trim', explode( ',', $blockers_raw ) ) : array();
	gabochie_recovery_seed( $post_id, $blockers );
}, 10, 3 );
