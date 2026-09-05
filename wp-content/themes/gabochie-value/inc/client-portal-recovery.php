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
	$cur_rs = get_post_meta( $project_id, 'recovery_status', true );
	if ( '' === $cur_rs || 'diagnosed' === $cur_rs ) {
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

	// Gate: evidence -> plan requires at least 1 doc + Readiness >40 (all building types, Ghana).
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
		if ( function_exists( 'gabochie_value_readiness_calculate' ) ) {
			$readiness = gabochie_value_readiness_calculate( $id );
			$overall   = is_array( $readiness ) ? (int) ( $readiness['overall'] ?? 0 ) : (int) get_post_meta( $id, 'readiness_overall', true );
			if ( $overall < 40 && ! $is_admin_or_facilitator ) {
				return new WP_Error( 'blocked', 'Readiness score is ' . $overall . '/100 — reach 40+ (fill Evidence checklist + docs) before planning. Applies to all building types in Ghana. Current: Triage/Evidence.', array( 'status' => 400 ) );
			}
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

/* ────────────────────────────────────────────
   7. ADMIN RECOVERY BOARD — columns, filters, assign
   ──────────────────────────────────────────── */
add_filter( 'manage_client_project_posts_columns', function ( $cols ) {
	$cols['recovery_status'] = 'Recovery';
	$cols['building_type']   = 'Building Type';
	$cols['facilitator']     = 'Facilitator';
	return $cols;
} );

add_action( 'manage_client_project_posts_custom_column', function ( $col, $post_id ) {
	if ( 'recovery_status' === $col ) {
		$rs = get_post_meta( $post_id, 'recovery_status', true );
		$ps = get_post_meta( $post_id, 'project_status', true );
		if ( '' === $rs && 'recovery_diagnostic' === $ps ) {
			$rs = 'diagnosed';
		}
		if ( '' === $rs ) {
			echo '<span style="color:#999">—</span>';
		} else {
			$map = gabochie_recovery_statuses();
			echo esc_html( $map[ $rs ] ?? ucwords( str_replace( '_', ' ', $rs ) ) );
		}
	}
	if ( 'building_type' === $col ) {
		$t = get_post_meta( $post_id, 'project_type', true ) ?: get_post_meta( $post_id, 'property_type', true ) ?: '—';
		echo esc_html( ucwords( str_replace( '_', ' ', $t ) ) );
	}
	if ( 'facilitator' === $col ) {
		$fid = (int) get_post_meta( $post_id, 'facilitator_id', true );
		if ( $fid ) {
			$u = get_userdata( $fid );
			echo esc_html( $u ? $u->display_name : '#' . $fid );
		} else {
			echo '<span style="color:#999">Unassigned</span>';
		}
	}
}, 10, 2 );

add_action( 'restrict_manage_posts', function ( $post_type ) {
	if ( 'client_project' !== $post_type ) {
		return;
	}
	$current = isset( $_GET['recovery_status'] ) ? sanitize_key( $_GET['recovery_status'] ) : '';
	echo '<select name="recovery_status"><option value="">All Recovery</option>';
	foreach ( gabochie_recovery_statuses() as $k => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $current, $k, false ), esc_html( $label ) );
	}
	echo '</select>';
	$bcurrent = isset( $_GET['building_type'] ) ? sanitize_key( $_GET['building_type'] ) : '';
	$types = array( 'residential' => 'Residential', 'commercial' => 'Commercial', 'mixed' => 'Mixed', 'renovation' => 'Renovation', 'interior' => 'Interior' );
	echo '<select name="building_type"><option value="">All Types</option>';
	foreach ( $types as $k => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $bcurrent, $k, false ), esc_html( $label ) );
	}
	echo '</select>';
} );

add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() || 'client_project' !== $q->get( 'post_type' ) ) {
		return;
	}
	$rs = isset( $_GET['recovery_status'] ) ? sanitize_key( $_GET['recovery_status'] ) : '';
	if ( '' !== $rs ) {
		$meta = $q->get( 'meta_query' ) ?: array();
		$meta[] = array( 'key' => 'recovery_status', 'value' => $rs, 'compare' => '=' );
		$q->set( 'meta_query', $meta );
	}
	$bt = isset( $_GET['building_type'] ) ? sanitize_key( $_GET['building_type'] ) : '';
	if ( '' !== $bt ) {
		$meta = $q->get( 'meta_query' ) ?: array();
		$meta[] = array( 'key' => 'project_type', 'value' => $bt, 'compare' => '=' );
		$q->set( 'meta_query', $meta );
	}
} );

// Metabox: assign facilitator
add_action( 'add_meta_boxes_client_project', function () {
	add_meta_box( 'gabochie_recovery_assign', 'Recovery — Facilitator', 'gabochie_recovery_assign_box', 'client_project', 'side', 'high' );
} );

function gabochie_recovery_assign_box( $post ) {
	$fid = (int) get_post_meta( $post->ID, 'facilitator_id', true );
	$rs  = get_post_meta( $post->ID, 'recovery_status', true );
	wp_nonce_field( 'gabochie_recovery_assign', 'gabochie_recovery_assign_nonce' );
	echo '<p><label>Recovery Status</label><br><select name="gabochie_recovery_status" style="width:100%">';
	echo '<option value="">—</option>';
	foreach ( gabochie_recovery_statuses() as $k => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $rs, $k, false ), esc_html( $label ) );
	}
	echo '</select></p>';
	echo '<p><label>Facilitator (user ID or email)</label><br><input type="text" name="gabochie_facilitator" value="' . esc_attr( $fid ? $fid : '' ) . '" placeholder="12 or name@email.com" style="width:100%"></p>';
	if ( $fid ) {
		$u = get_userdata( $fid );
		if ( $u ) {
			echo '<p style="font-size:0.82rem;color:#555">Assigned: ' . esc_html( $u->display_name . ' (' . $u->user_email . ')' ) . '</p>';
		}
	}
	echo '<p style="font-size:0.76rem;color:#777">All building types in Ghana — reassignment is logged.</p>';
}

add_action( 'save_post_client_project', function ( $post_id ) {
	if ( ! isset( $_POST['gabochie_recovery_assign_nonce'] ) || ! wp_verify_nonce( $_POST['gabochie_recovery_assign_nonce'], 'gabochie_recovery_assign' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['gabochie_recovery_status'] ) ) {
		$rs = sanitize_key( $_POST['gabochie_recovery_status'] );
		if ( '' === $rs || isset( gabochie_recovery_statuses()[ $rs ] ) ) {
			update_post_meta( $post_id, 'recovery_status', $rs );
		}
	}
	if ( isset( $_POST['gabochie_facilitator'] ) ) {
		$raw = sanitize_text_field( $_POST['gabochie_facilitator'] );
		$fid = 0;
		if ( '' !== $raw ) {
			if ( is_email( $raw ) ) {
				$u = get_user_by( 'email', $raw );
				$fid = $u ? $u->ID : 0;
			} elseif ( is_numeric( $raw ) ) {
				$fid = (int) $raw;
			}
		}
		$old = (int) get_post_meta( $post_id, 'facilitator_id', true );
		if ( $fid !== $old ) {
			update_post_meta( $post_id, 'facilitator_id', $fid );
			if ( function_exists( 'gabochie_log_audit' ) ) {
				gabochie_log_audit( $post_id, 'facilitator_assigned', $fid ? ( get_userdata( $fid )->user_login ?? $fid ) : 'unassigned' );
			}
		}
	}
}, 10, 1 );

/* ────────────────────────────────────────────
   8. FACILITATOR INVITE (dashboard, by email)
   ──────────────────────────────────────────── */
add_action( 'rest_api_init', function () {
	register_rest_route(
		'gabochie/v1',
		'/recovery/(?P<id>\d+)/invite',
		array(
			'methods'             => 'POST',
			'callback'            => 'gabochie_api_recovery_invite',
			'permission_callback' => 'gabochie_api_recovery_access',
		)
	);
} );

function gabochie_api_recovery_invite( $request ) {
	$id   = (int) $request->get_param( 'id' );
	$body = json_decode( $request->get_body(), true );
	$email = sanitize_email( $body['email'] ?? '' );
	$name  = sanitize_text_field( $body['name'] ?? '' );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'invalid', 'Valid email required', array( 'status' => 400 ) );
	}
	$user = get_user_by( 'email', $email );
	if ( ! $user ) {
		$login = sanitize_user( current( explode( '@', $email ) ), true );
		$base  = $login;
		$i = 1;
		while ( username_exists( $login ) ) {
			$login = $base . $i;
			++$i;
		}
		$uid = wp_create_user( $login, wp_generate_password( 16, false ), $email );
		if ( is_wp_error( $uid ) ) {
			return new WP_Error( 'create_failed', $uid->get_error_message(), array( 'status' => 500 ) );
		}
		wp_update_user( array( 'ID' => $uid, 'display_name' => $name ?: $login, 'role' => 'project_facilitator' ) );
		$user = get_userdata( $uid );
		wp_new_user_notification( $uid, null, 'both' );
	} else {
		// Ensure role.
		if ( ! in_array( 'project_facilitator', (array) $user->roles, true ) && ! in_array( 'administrator', (array) $user->roles, true ) ) {
			$user->add_role( 'project_facilitator' );
		}
	}
	update_post_meta( $id, 'facilitator_id', $user->ID );
	if ( function_exists( 'gabochie_log_audit' ) ) {
		gabochie_log_audit( $id, 'facilitator_invited', $user->user_email );
	}
	// Notify facilitator.
	wp_mail(
		$user->user_email,
		'You have been invited as facilitator — ' . get_bloginfo( 'name' ),
		"Hi " . ( $user->display_name ?: $user->user_login ) . ",\n\n" .
		"You have been invited to facilitate recovery for project: " . get_the_title( $id ) . " (#" . $id . ")\n" .
		"Open: " . esc_url( home_url( '/dashboard/?project=' . $id . '#recovery' ) ) . "\n\n" .
		"All building types in Ghana are covered — residential, commercial, mixed-use, renovation & interior.\n" .
		"— " . get_bloginfo( 'name' ),
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);
	return rest_ensure_response( array( 'ok' => true, 'facilitator_id' => $user->ID, 'email' => $user->user_email ) );
}

/* ────────────────────────────────────────────
   9. RECOVERY PDF REPORT (branded)
   ──────────────────────────────────────────── */
add_action( 'rest_api_init', function () {
	register_rest_route(
		'gabochie/v1',
		'/recovery/(?P<id>\d+)/pdf',
		array(
			'methods'             => 'GET',
			'callback'            => 'gabochie_api_recovery_pdf',
			'permission_callback' => 'gabochie_api_recovery_access',
		)
	);
} );

function gabochie_api_recovery_pdf( $request ) {
	$id      = (int) $request->get_param( 'id' );
	$project = get_post( $id );
	if ( ! $project || 'client_project' !== $project->post_type ) {
		return new WP_Error( 'not_found', 'Project not found', array( 'status' => 404 ) );
	}
	$bytes = gabochie_recovery_build_pdf( $id );
	if ( empty( $bytes ) ) {
		return new WP_Error( 'pdf_failed', 'PDF generation failed', array( 'status' => 500 ) );
	}
	$filename = 'gabochie-recovery-' . $id . '-' . gmdate( 'Y-m-d' ) . '.pdf';
	// Direct PDF response.
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: inline; filename="' . $filename . '"' );
	header( 'Content-Length: ' . strlen( $bytes ) );
	echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

function gabochie_recovery_build_pdf( $project_id ) {
	$project = get_post( $project_id );
	if ( ! $project ) {
		return '';
	}
	// Prefer branded thesis engine if available.
	if ( class_exists( 'Gabochie_Thesis_Pdf' ) ) {
		try {
			$pdf = new Gabochie_Thesis_Pdf();
			$m   = 38;
			$cw  = 520;
			$pdf->new_page();
			$pdf->furniture( 'RECOVERY · GHANA' );
			$y = 58;
			$pdf->rect( $m, $y, 5, 5, 'gold' );
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
			$pdf->set_size( 7.6 );
			$pdf->set_color( 'gold' );
			$pdf->text( $m + 13, $y, 'REAL ESTATE DEVELOPMENT · PROJECT RECOVERY' );
			$y += 22;
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
			$pdf->set_size( 17 );
			$pdf->set_color( 'teal_deep' );
			$y = $pdf->paragraph( $m, $y, $project->post_title, $cw, 21 );
			$y += 6;
			$pdf->rect( $m, $y, 46, 3, 'gold' );
			$y += 18;
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
			$pdf->set_size( 9.2 );
			$pdf->set_color( 'ink' );
			$status = get_post_meta( $project_id, 'recovery_status', true ) ?: 'diagnosed';
			$type   = get_post_meta( $project_id, 'project_type', true ) ?: get_post_meta( $project_id, 'property_type', true ) ?: '—';
			$loc    = get_post_meta( $project_id, 'project_location', true ) ?: get_post_meta( $project_id, 'land_location', true ) ?: '—';
			$block  = get_post_meta( $project_id, 'rec_blockers', true ) ?: '—';
			$spend  = get_post_meta( $project_id, 'rec_spend', true ) ?: '—';
			$stage  = get_post_meta( $project_id, 'rec_stage', true ) ?: '—';
			$lines = array(
				'Recovery Status: ' . ucwords( str_replace( '_', ' ', $status ) ),
				'Building Type: ' . ucwords( str_replace( '_', ' ', $type ) ) . '  ·  Location: ' . $loc,
				'Blockers: ' . $block  . '  ·  Stage: ' . $stage . '  ·  Spend: ' . $spend,
				'Project ID: #' . $project_id . '  ·  Date: ' . gmdate( 'Y-m-d' ),
				'Coverage: All building types in Ghana — residential, commercial, mixed-use, renovation & interior.',
			);
			foreach ( $lines as $ln ) {
				$y = $pdf->paragraph( $m, $y, $ln, $cw, 13 );
				$y += 4;
			}
			$y += 8;
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
			$pdf->set_size( 11 );
			$pdf->set_color( 'teal_deep' );
			$pdf->text( $m, $y, 'Recovery Steps — Triage → Evidence → Plan → Execution → Verification' );
			$y += 10;
			$pdf->rect( $m, $y, $cw, 1, 'gold_lite' );
			$y += 12;
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
			$pdf->set_size( 8.6 );
			$pdf->set_color( 'ink' );
			$steps = gabochie_recovery_steps();
			foreach ( $steps as $slug => $st ) {
				$active = ( get_post_meta( $project_id, 'recovery_status', true ) === $slug ) ? ' ← current' : '';
				$y = $pdf->paragraph( $m, $y, '• ' . $st['label'] . ': ' . $st['desc'] . $active, $cw, 11 );
				$y += 3;
			}
			$y += 8;
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
			$pdf->set_size( 11 );
			$pdf->set_color( 'teal_deep' );
			$pdf->text( $m, $y, 'Checklist' );
			$y += 10;
			$pdf->rect( $m, $y, $cw, 1, 'gold_lite' );
			$y += 10;
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
			$pdf->set_size( 8.6 );
			$cl = json_decode( get_post_meta( $project_id, 'recovery_checklist', true ), true );
			if ( is_array( $cl ) && $cl ) {
				foreach ( $cl as $it ) {
					$mark = ( $it['status'] ?? 'pending' ) === 'done' ? '[x]' : '[ ]';
					$y = $pdf->paragraph( $m, $y, $mark . ' ' . $it['label'] . ( ! empty( $it['doc_type'] ) ? ' (' . $it['doc_type'] . ')' : '' ), $cw, 11 );
					$y += 2;
				}
			} else {
				$y = $pdf->paragraph( $m, $y, 'No checklist seeded yet.', $cw, 11 );
			}
			$y += 10;
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
			$pdf->set_size( 11 );
			$pdf->set_color( 'teal_deep' );
			$pdf->text( $m, $y, 'Costs' );
			$y += 10;
			$pdf->rect( $m, $y, $cw, 1, 'gold_lite' );
			$y += 10;
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
			$pdf->set_size( 8.6 );
			$costs = get_posts( array( 'post_type' => 'project_cost_item', 'posts_per_page' => -1, 'meta_query' => array( array( 'key' => 'project_id', 'value' => $project_id, 'compare' => '=' ) ) ) );
			if ( $costs ) {
				foreach ( $costs as $c ) {
					$amt = get_post_meta( $c->ID, 'amount', true );
					$cat = get_post_meta( $c->ID, 'category', true );
					$stt = get_post_meta( $c->ID, 'cost_status', true );
					$y = $pdf->paragraph( $m, $y, '• ' . $c->post_title . ' — ' . number_format( (float) $amt ) . ' GHS (' . $cat . ', ' . $stt . ')', $cw, 11 );
					$y += 2;
				}
			} else {
				$y = $pdf->paragraph( $m, $y, 'No cost items yet.', $cw, 11 );
			}
			return $pdf->output();
		} catch ( Exception $e ) {
			// Fall through to minimal PDF.
		}
	}
	// Minimal fallback PDF (single page, Helvetica).
	$title = sanitize_text_field( $project->post_title );
	$stream = "BT /F1 14 Tf 40 760 Td (" . str_replace( array( '(', ')', '\\' ), array( '\\(', '\\)', '\\\\' ), $title ) . ") Tj ET\n";
	$stream .= "BT /F1 9 Tf 40 740 Td (Recovery Report — All building types, Ghana) Tj ET\n";
	$stream .= "BT /F1 9 Tf 40 720 Td (Project #" . $project_id . " — " . gmdate( 'Y-m-d' ) . ") Tj ET\n";
	$len = strlen( $stream );
	$pdf  = "%PDF-1.4\n";
	$pdf .= "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
	$pdf .= "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
	$pdf .= "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> >> endobj\n";
	$pdf .= "4 0 obj << /Length $len >> stream\n$stream" . "endstream endobj\n";
	$pdf .= "xref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000056 00000 n \n0000000111 00000 n \n0000000302 00000 n \n";
	$pdf .= "trailer << /Size 5 /Root 1 0 R >>\nstartxref\n" . ( strlen( $pdf ) ) . "\n%%EOF";
	return $pdf;
}
