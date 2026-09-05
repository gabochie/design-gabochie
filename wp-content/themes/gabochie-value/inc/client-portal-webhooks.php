<?php
/**
 * Client Portal — Webhooks & Action Hooks
 * @package Gabochie_Value
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_nopriv_gabochie_capture_lead', 'gabochie_value_handle_lead_capture' );
add_action( 'wp_ajax_gabochie_capture_lead', 'gabochie_value_handle_lead_capture' );
function gabochie_value_handle_lead_capture() {
	check_ajax_referer( 'gabochie_lead_nonce', 'nonce' );

	$email   = sanitize_email( $_POST['email'] ?? '' );
	$name    = sanitize_text_field( $_POST['name'] ?? '' );
	$phone   = sanitize_text_field( $_POST['phone'] ?? '' );
	$service = sanitize_text_field( $_POST['service'] ?? '' );

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Valid email required.' ), 400 );
	}

	$uid = gabochie_value_ensure_user( $email, $name );
	if ( is_wp_error( $uid ) ) {
		wp_send_json_error( array( 'message' => $uid->get_error_message() ), 500 );
	}

	if ( $phone ) {
		update_user_meta( $uid, 'gabochie_phone', $phone );
	}

	$pid = wp_insert_post(
		array(
			'post_type'   => 'client_project',
			'post_title'  => ( $name ?: $email ) . ' — ' . ( $service ?: 'New Inquiry' ),
			'post_author' => $uid,
			'post_status' => 'publish',
			'meta_input'  => array(
				'project_status' => 'inquiry',
				'project_stage'  => 'Discovery',
				'service_type'   => $service,
				'client_phone'   => $phone,
			),
		)
	);

	gabochie_value_log_agent_action(
		'lead_captured',
		array(
			'user_id' => $uid,
			'email'   => $email,
			'name'    => $name,
			'service' => $service,
			'project' => $pid ?: 0,
		)
	);

	// Route the lead into the lead hub (FluentCRM pipeline).
	do_action(
		'gabochie/lead/captured',
		$email,
		array(
			'list'   => 'homepage_lead',
			'source' => ( '' !== $service ) ? $service : 'capture-form',
			'name'   => $name,
			'phone'  => $phone,
		)
	);

	wp_send_json_success(
		array(
			'user_id'    => $uid,
			'project_id' => $pid ?: 0,
			'message'    => $pid ? 'Account created! Check your email for login credentials.' : 'Account created.',
		)
	);
}

function gabochie_value_log_agent_action( $action, $data = array() ) {
	$log   = get_option( 'gabochie_agent_log', array() );
	$log[] = array(
		'action' => $action,
		'data'   => $data,
		'time'   => current_time( 'mysql' ),
	);
	if ( count( $log ) > 500 ) {
		$log = array_slice( $log, -500 );
	}
	update_option( 'gabochie_agent_log', $log );
}

/* ---- Onboarding: create account + project in one call ---- */
add_action( 'wp_ajax_nopriv_gabochie_onb_create', 'gabochie_value_onboarding_create' );
function gabochie_value_onboarding_create() {
	check_ajax_referer( 'gabochie_onb_nonce', 'nonce' );

	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email = sanitize_email( $_POST['email'] ?? '' );
	$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

	if ( ! $name ) {
		wp_send_json_success(
			array(
				'success' => false,
				'message' => 'Please enter your name.',
			)
		);
		exit;
	}
	if ( ! is_email( $email ) ) {
		wp_send_json_success(
			array(
				'success' => false,
				'message' => 'A valid email address is required.',
			)
		);
		exit;
	}

	/* Existing user → log them in, ensure they have Free Lead, redirect to dashboard */
	$existing = get_user_by( 'email', $email );
	if ( $existing ) {
		wp_set_current_user( $existing->ID );
		wp_set_auth_cookie( $existing->ID, true );
		/* Ensure existing user has Free Lead membership */
		if ( function_exists( 'pmpro_changeMembershipLevel' ) && ! pmpro_hasMembershipLevel( null, $existing->ID ) ) {
			global $wpdb;
			$free_level = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}pmpro_membership_levels WHERE name = 'Free Lead' LIMIT 1" );
			if ( $free_level ) {
				pmpro_changeMembershipLevel( (int) $free_level, $existing->ID );
			}
		}
		wp_send_json_success(
			array(
				'message'       => 'Welcome back! Logging you in.',
				'dashboard_url' => esc_url( home_url( '/dashboard/' ) ),
			)
		);
		exit;
	}

	/* Create user */
	$uid = gabochie_value_ensure_user( $email, $name );
	if ( is_wp_error( $uid ) ) {
		wp_send_json_success(
			array(
				'success' => false,
				'message' => $uid->get_error_message(),
			)
		);
		exit;
	}

	/* Log the user in immediately so they land on the dashboard */
	wp_set_current_user( $uid );
	wp_set_auth_cookie( $uid, true );

	if ( $phone ) {
		update_user_meta( $uid, 'gabochie_phone', $phone );
	}

	// Route the onboarding sign-up into the lead hub (FluentCRM pipeline).
	do_action(
		'gabochie/lead/captured',
		$email,
		array(
			'list'   => 'onboarding',
			'source' => 'onboarding',
			'name'   => $name,
			'phone'  => $phone,
		)
	);

	/* Sanitize all onboarding fields */
	$goal              = sanitize_text_field( wp_unslash( $_POST['goal'] ?? '' ) );
	$property_type     = sanitize_text_field( wp_unslash( $_POST['property_type'] ?? '' ) );
	$land_status       = sanitize_text_field( wp_unslash( $_POST['land_status'] ?? '' ) );
	$land_location     = sanitize_text_field( wp_unslash( $_POST['land_location'] ?? '' ) );
	$development_scale = sanitize_text_field( wp_unslash( $_POST['development_scale'] ?? '' ) );
	$budget_range      = sanitize_text_field( wp_unslash( $_POST['budget_range'] ?? '' ) );
	$timeline          = sanitize_text_field( wp_unslash( $_POST['timeline'] ?? '' ) );
	$location_based    = sanitize_text_field( wp_unslash( $_POST['location_based'] ?? '' ) );
	$project_desc      = sanitize_textarea_field( wp_unslash( $_POST['project_description'] ?? '' ) );

	/* Build a descriptive project title */
	$goal_labels = array(
		'build_home'        => 'Residential Build',
		'develop_income'    => 'Income Development',
		'have_land'         => 'Land Development',
		'have_capital'      => 'Capital Deployment',
		'unfinished'        => 'Project Recovery',
		'renovate'          => 'Renovation',
		'commercial'        => 'Commercial Development',
		'attract_investors' => 'Investor-Ready Project',
		'invest'            => 'Investment Opportunity',
		'professional'      => 'Professional Engagement',
		'organisation'      => 'Organisation Project',
		'notsure'           => 'Development Consultation',
	);
	$goal_label  = $goal_labels[ $goal ] ?? 'Development Inquiry';
	$loc_label   = $land_location ? ' (' . str_replace( '-', ' ', $land_location ) . ')' : '';
	$post_title  = $name . ' — ' . $goal_label . $loc_label;

	$pid = wp_insert_post(
		array(
			'post_type'   => 'client_project',
			'post_title'  => $post_title,
			'post_author' => $uid,
			'post_status' => 'publish',
			'meta_input'  => array(
				'project_status'      => 'new',
				'project_stage'       => 'Discovery',
				'dev_goal'            => $goal,
				'property_type'       => $property_type,
				'land_status'         => $land_status,
				'land_location'       => $land_location,
				'development_scale'   => $development_scale,
				'budget_range'        => $budget_range,
				'timeline'            => $timeline,
				'location_based'      => $location_based,
				'project_description' => $project_desc,
				'client_phone'        => $phone,
			),
		)
	);

	gabochie_value_log_agent_action(
		'onboarding_complete',
		array(
			'user_id' => $uid,
			'email'   => $email,
			'name'    => $name,
			'goal'    => $goal,
			'budget'  => $budget_range,
			'project' => $pid ?: 0,
		)
	);

	/* Send welcome email */
	if ( $pid ) {
		$site_name = get_bloginfo( 'name' );
		wp_mail(
			$email,
			'Your Development Opportunity — ' . $site_name,
			"Hi {$name},\n\n" .
			"Welcome to {$site_name}. Your Development Opportunity profile has been created.\n\n" .
			"Your project goal: {$goal_label}\n" .
			"Project ID: #{$pid}\n\n" .
			"Log in to view your project and recommended next steps:\n" .
			esc_url( home_url( '/login/' ) ) . "\n\n" .
			'— The ' . $site_name . ' Team',
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);
	}

	wp_send_json_success(
		array(
			'user_id'       => $uid,
			'project_id'    => $pid ?: 0,
			'message'       => 'Account and project created successfully.',
			'dashboard_url' => esc_url( home_url( '/dashboard/' ) ),
		)
	);
}

add_action( 'wp_ajax_gabochie_agent_update', 'gabochie_value_ajax_agent_update' );
function gabochie_value_ajax_agent_update() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
	}

	$action = sanitize_text_field( $_POST['agent_action'] ?? '' );
	$pid    = (int) ( $_POST['project_id'] ?? 0 );
	$value  = sanitize_text_field( $_POST['value'] ?? '' );

	if ( ! $pid || ! $action ) {
		wp_send_json_error( array( 'message' => 'project_id and agent_action required' ), 400 );
	}

	switch ( $action ) {
		case 'update_status':
			update_post_meta( $pid, 'project_status', $value );
			break;
		case 'update_stage':
			update_post_meta( $pid, 'project_stage', $value );
			break;
		case 'set_milestone':
			update_post_meta( $pid, 'next_milestone', $value );
			if ( ! empty( $_POST['milestone_date'] ) ) {
				update_post_meta( $pid, 'next_milestone_date', sanitize_text_field( $_POST['milestone_date'] ) );
			}
			break;
		case 'add_note':
			$note = wp_kses_post( $_POST['note'] ?? '' );
			if ( $note ) {
				$mid = wp_insert_post(
					array(
						'post_type'    => 'project_message',
						'post_title'   => 'System Note — ' . get_the_title( $pid ),
						'post_content' => $note,
						'post_status'  => 'publish',
					)
				);
				if ( $mid ) {
					update_post_meta( $mid, 'project_id', $pid );
					update_post_meta( $mid, 'is_from_client', false );
				}
			}
			break;
		default:
			wp_send_json_error( array( 'message' => 'Unknown action' ), 400 );
	}

	gabochie_value_log_agent_action(
		$action,
		array(
			'project_id' => $pid,
			'value'      => $value,
		)
	);
	wp_send_json_success( array( 'message' => 'OK' ) );
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'gabochie/v1',
			'/agent-log',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					if ( ! current_user_can( 'manage_options' ) ) {
						return new WP_Error( 'forbidden', 'Admins only', array( 'status' => 403 ) );
					}
					return array( 'log' => get_option( 'gabochie_agent_log', array() ) );
				},
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'gabochie/v1',
			'/recovery-diagnose',
			array(
				'methods'             => 'POST',
				'callback'            => function ( $request ) {
					$raw   = $request->get_json_params();
					$name  = sanitize_text_field( wp_unslash( $raw['name'] ?? '' ) );
					$email = sanitize_email( wp_unslash( $raw['email'] ?? '' ) );
					$phone = sanitize_text_field( wp_unslash( $raw['phone'] ?? '' ) );
					$stage = sanitize_text_field( wp_unslash( $raw['stage'] ?? '' ) );
					$spend = sanitize_text_field( wp_unslash( $raw['spend'] ?? '' ) );
					$goal  = sanitize_text_field( wp_unslash( $raw['goal'] ?? '' ) );
					$location_based = sanitize_text_field( wp_unslash( $raw['location_based'] ?? '' ) );
					$desc  = sanitize_textarea_field( wp_unslash( $raw['description'] ?? '' ) );
					$website = sanitize_text_field( wp_unslash( $raw['gabochie_website'] ?? '' ) );

					/* Honeypot — bots fill this hidden field. */
					if ( '' !== $website ) {
						return new WP_Error( 'rejected', 'Submission rejected.', array( 'status' => 400 ) );
					}

					$blockers = array_filter( array_map( 'sanitize_text_field', wp_unslash( (array) ( $raw['blockers'] ?? array() ) ) ) );

					if ( '' === $email || ! is_email( $email ) ) {
						return new WP_Error( 'invalid', 'A valid email address is required.', array( 'status' => 400 ) );
					}
					if ( '' === $name ) {
						return new WP_Error( 'invalid', 'Please enter your name.', array( 'status' => 400 ) );
					}

					$outcome = gabochie_value_recovery_outcome( $blockers, $stage, $spend, $goal );

					$uid = gabochie_value_ensure_user( $email, $name );
					if ( is_wp_error( $uid ) ) {
						return new WP_Error( 'user', $uid->get_error_message(), array( 'status' => 500 ) );
					}

					if ( $phone ) {
						update_user_meta( $uid, 'gabochie_phone', $phone );
					}

					/* Ensure Free Lead membership (CRM pipeline entry) */
					if ( function_exists( 'pmpro_changeMembershipLevel' ) && ! pmpro_hasMembershipLevel( null, $uid ) ) {
						global $wpdb;
						$free_level = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}pmpro_membership_levels WHERE name = 'Free Lead' LIMIT 1" );
						if ( $free_level ) {
							pmpro_changeMembershipLevel( (int) $free_level, $uid );
						}
					}

					$pid = wp_insert_post(
						array(
							'post_type'   => 'client_project',
							'post_title'  => $name . ' — Project Recovery Diagnostic',
							'post_author' => $uid,
							'post_status' => 'publish',
							'meta_input'  => array(
								'project_status'        => 'recovery_diagnostic',
								'project_stage'         => 'Recovery',
								'service_type'          => 'Project Recovery Diagnostic',
								'rec_blockers'          => implode( ', ', $blockers ),
								'rec_stage'             => $stage,
								'rec_spend'             => $spend,
								'rec_goal'              => $goal,
								'rec_outcome'           => $outcome['tier'],
								'rec_score'             => $outcome['score'],
								'rec_description'       => $desc,
								'wizard_location_based' => $location_based,
								'client_phone'          => $phone,
							),
						)
					);

					gabochie_value_log_agent_action(
						'recovery_diagnose',
						array(
							'user_id'  => $uid,
							'email'    => $email,
							'name'     => $name,
							'phone'    => $phone,
							'blockers' => $blockers,
							'stage'    => $stage,
							'spend'    => $spend,
							'goal'     => $goal,
'outcome'  => $outcome['tier'],
'score'    => $outcome['score'],
'project'  => $pid ? $pid : 0,
						)
					);

					/* Route the recovery lead into the lead hub (FluentCRM pipeline). */
					do_action(
						'gabochie/lead/captured',
						$email,
						array(
							'list'   => 'project_recovery',
							'source' => 'project-recovery',
							'name'   => $name,
							'phone'  => $phone,
						)
					);

					/* Conversion email with the diagnosis readout. */
					if ( $pid ) {
						$site_name = get_bloginfo( 'name' );
						wp_mail(
							$email,
							'Your Project Recovery Diagnosis — ' . $site_name,
							"Hi {$name},\n\n" .
							"Here is your Project Recovery Diagnosis from {$site_name}.\n\n" .
							$outcome['email_copy'] . "\n\n" .
							"Diagnostic reference: #{$pid}\n" .
							'What we received: ' . implode( ', ', $blockers ) . ' | ' . $outcome['stage_label'] . ' | ' . $outcome['spend_label'] . "\n\n" .
							"Next step:\n" .
							"Bring your drawings, contracts and photos to the structured Recovery Diagnostic (fixed fee, clear scope) and we'll tell you exactly what it costs to finish and whether continuing beats starting over.\n" .
							esc_url( home_url( '/contact/' ) ) . "\n\n" .
							'Or reply to this email directly — we respond to every project.\n' .
							'— The ' . $site_name . ' Team',
							array( 'Content-Type: text/plain; charset=UTF-8' )
						);
					}

					return array(
						'success'     => true,
						'project_id'  => $pid ? $pid : 0,
						'outcome'     => $outcome,
						'contact_url' => esc_url( home_url( '/contact/' ) ),
					);
				},
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'gabochie/v1',
			'/consultation-request',
			array(
				'methods'             => 'POST',
				'callback'            => function ( $request ) {
					$params = $request->get_json_params();
					$name    = sanitize_text_field( $params['name'] ?? '' );
					$email   = sanitize_email( $params['email'] ?? '' );
					$phone   = sanitize_text_field( $params['phone'] ?? '' );
					$journey = sanitize_text_field( $params['journey'] ?? '' );
					$blocker = sanitize_text_field( $params['blocker'] ?? '' );
					$location = sanitize_text_field( $params['location'] ?? '' );
					$type    = sanitize_text_field( $params['type'] ?? '' );
					$timeline = sanitize_text_field( $params['timeline'] ?? '' );

					if ( ! $name && ! $email ) {
						return new WP_Error( 'missing', 'Name or email required.', array( 'status' => 400 ) );
					}

					$uid = gabochie_value_ensure_user( $email ?: ( sanitize_title( $name ) . '@placeholder.local' ), $name );
					if ( is_wp_error( $uid ) ) {
						return new WP_Error( 'user', $uid->get_error_message(), array( 'status' => 500 ) );
					}

					if ( $phone ) {
						update_user_meta( $uid, 'gabochie_phone', $phone );
					}
					if ( $location ) {
						update_user_meta( $uid, 'gabochie_location', $location );
					}

					$title = ( $name ?: $email ) . ' — Consultation Request';
					$body  = "Journey: {$journey}\nBlocker: {$blocker}\n";
					if ( $location ) {
						$body .= "Location: {$location}\n";
					}
					if ( $type ) {
						$body .= "Project Type: {$type}\n";
					}
					if ( $timeline ) {
						$body .= "Timeline: {$timeline}\n";
					}
					if ( $phone ) {
						$body .= "Phone: {$phone}\n";
					}

					$pid = wp_insert_post(
						array(
							'post_type'    => 'client_project',
							'post_title'   => $title,
							'post_content' => $body,
							'post_author'  => $uid,
							'post_status'  => 'publish',
							'meta_input'   => array(
								'project_status'  => 'inquiry',
								'project_stage'   => 'Discovery',
								'service_type'    => 'Consultation Request',
								'client_phone'    => $phone,
								'wizard_journey'  => $journey,
								'wizard_blocker'  => $blocker,
								'wizard_location' => $location,
								'wizard_type'     => $type,
								'wizard_timeline' => $timeline,
							),
						)
					);

					gabochie_value_log_agent_action(
						'consultation_request',
						array(
							'user_id'  => $uid,
							'email'    => $email,
							'name'     => $name,
							'phone'    => $phone,
							'journey'  => $journey,
							'blocker'  => $blocker,
							'location' => $location,
							'type'     => $type,
							'timeline' => $timeline,
							'project'  => $pid ?: 0,
						)
					);

					return array(
						'success'    => true,
						'project_id' => $pid,
					);
				},
				'permission_callback' => '__return_true',
			)
		);
	}
);

/* ============================================
	Project Recovery — diagnostic outcome engine
	Deterministic scoring: sunk cost + physical progress
	vs blockers + goal. Honest, no fake hype.
	============================================ */
function gabochie_value_recovery_outcome( $blockers, $stage, $spend, $goal ) {
	$blockers = (array) $blockers;

	/* Money already committed → the case for finishing grows. */
	$sunk     = array(
		'under_100k' => 1,
		'100k_500k'  => 2,
		'500k_1m'    => 3,
		'1m_3m'      => 4,
		'3m_plus'    => 5,
		'not_sure'   => 2,
	);
	$sunk_pts = $sunk[ $spend ] ?? 1;

	/* Physical progress → structure is real, recovery is leverage. */
	$stage_pts = array(
		'design'     => 0,
		'foundation' => 2,
		'walls'      => 3,
		'lintel'     => 4,
		'rough_in'   => 4,
		'finishing'  => 3,
	);
	$stage_pts = $stage_pts[ $stage ] ?? 1;

	/* Blockers pushing toward restart vs. rescue. */
	$restart_hard = array( 'land', 'scope', 'design' );
	$rescue_soft  = array( 'budget', 'contractor', 'permits', 'abandoned', 'visibility' );

	$score = $sunk_pts + $stage_pts;
	if ( array_intersect( $rescue_soft, $blockers ) ) {
		++$score;
	}
	if ( array_intersect( $restart_hard, $blockers ) ) {
		--$score;
	}
	if ( 'restart' === $goal ) {
		--$score;
	}
	if ( 'finish' === $goal ) {
		++$score;
	}
	if ( 'repair' === $goal ) {
		$score += 2;
	}

	$labels = array(
		'stage' => array(
			'design'     => 'Stalled at design',
			'foundation' => 'Foundation built',
			'walls'      => 'Walls up',
			'lintel'     => 'Lintel / roof level',
			'rough_in'   => 'Rough-in started',
			'finishing'  => 'Finishing started',
		),
		'spend' => array(
			'under_100k' => 'Under GHS 100K committed',
			'100k_500k'  => 'GHS 100K–500K committed',
			'500k_1m'    => 'GHS 500K–1M committed',
			'1m_3m'      => 'GHS 1M–3M committed',
			'3m_plus'    => 'GHS 3M+ committed',
			'not_sure'   => 'Spend unclear',
		),
		'goal'  => array(
			'finish'  => 'Finish the build',
			'restart' => 'Restart properly',
			'repair'  => 'Repair defects',
			'assess'  => 'Get an honest assessment',
		),
	);

	$out = array(
		'score'       => $score,
		'stage_label' => $labels['stage'][ $stage ] ?? 'Build stage unclear',
		'spend_label' => $labels['spend'][ $spend ] ?? 'Spend unknown',
		'goal_label'  => $labels['goal'][ $goal ] ?? 'Goal unclear',
	);

	if ( $score >= 8 ) {
		$out['tier']       = 'finish';
		$out['tier_label'] = 'Highly recoverable';
		$out['headline']   = 'Your project looks highly recoverable.';
		$out['summary']    = 'You have real money and real structure in the ground — the strongest case for finishing instead of starting over. What you need now is a plan to get the remaining phase done without repeating the stop. Bring your drawings, contracts and site photos to the Recovery Diagnostic and we will confirm exactly what remains and what it costs.';
		$out['email_copy'] = 'Your project is HIGHLY RECOVERABLE. The structure and investment in the ground make it very likely that finishing will cost less than starting over. A structured Recovery Diagnostic will confirm what remains, what it costs, and how to avoid another stall.';
	} elseif ( $score >= 5 ) {
		$out['tier']       = 'assess';
		$out['tier_label'] = 'Needs a proper assessment';
		$out['headline']   = 'Your project needs a proper assessment before you spend another cedi.';
		$out['summary']    = 'You have mixed signals — some point to saving the project, others to deeper issues. The Recovery Diagnostic separates what is genuinely worth saving from what is quietly costing you. Fixed fee, clear scope, no obligation.';
		$out['email_copy'] = 'Your project NEEDS A PROPER ASSESSMENT. The signals are mixed: there is something worth saving, but committing more without a diagnosis risks compounding the problem. The structured Recovery Diagnostic will tell you what can be saved, what it costs to finish, and whether continuing beats restarting.';
	} else {
		$out['tier']       = 'reconsider';
		$out['tier_label'] = 'Worth reconsidering before more spend';
		$out['headline']   = 'Worth stepping back before you spend more.';
		$out['summary']    = 'Money committed so far is low relative to the blockers in the way — and that is good news. This is the cheapest possible moment to reset direction with a clear plan. A Recovery Diagnostic gives you the straight answer on continue, restart, or stop.';
		$out['email_copy'] = 'Your project is WORTH RECONSIDERING BEFORE MORE SPEND. The money committed is low relative to the problems blocking progress, which makes this the cheapest possible moment to reset. A structured Recovery Diagnostic will give you a straight answer on continuing, restarting, or stopping.';
	}

	return $out;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_page_template( 'page-templates/template-login.php' ) ) {
			return;
		}
		wp_add_inline_script(
			'gabochie-value-main',
			'
document.addEventListener("DOMContentLoaded", function() {
    var form = document.querySelector("section[style*=\'max-width:1100px\'] form[action*=\'/contact/\']");
    if (!form) return;
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        var data = new FormData(form);
        data.append("action", "gabochie_capture_lead");
        data.append("nonce", "' . wp_create_nonce( 'gabochie_lead_nonce' ) . '");
        var btn = form.querySelector("button[type=submit]");
        btn.textContent = "Creating your portal...";
        btn.disabled = true;
        fetch("' . admin_url( 'admin-ajax.php' ) . '", { method:"POST", body:data })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.success) {
                    form.innerHTML = "<div style=\'text-align:center;padding:2rem 0\'><div style=\'font-size:2rem;margin-bottom:1rem\'>✅</div><h3 style=\'margin-bottom:0.5rem\'>You\'re All Set!</h3><p style=\'color:var(--mist);font-size:0.9rem\'>" + res.data.message + "</p></div>";
                } else {
                    btn.textContent = "Get Your Free Consultation";
                    btn.disabled = false;
                    alert(res.data.message || "Something went wrong. Please try again.");
                }
            })
            .catch(function(){ btn.textContent = "Get Your Free Consultation"; btn.disabled = false; });
    });
});
    '
		);
	}
);
