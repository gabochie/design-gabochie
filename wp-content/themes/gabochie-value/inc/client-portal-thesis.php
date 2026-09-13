<?php
/**
 * Thesis Lead Magnet — email gate + delivery (PDF attachment via gabochie-value PDF engine)
 * @package Gabochie_Value
 * @version 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared helper — get an existing user by email or create one.
 * Fixes the previously-dangling gabochie_value_ensure_user() calls
 * (lead capture + onboarding handlers now have a concrete definition).
 *
 * @param string $email
 * @param string $name
 * @return int|WP_Error User ID on success, WP_Error on failure.
 */
if ( ! function_exists( 'gabochie_value_ensure_user' ) ) {
	function gabochie_value_ensure_user( $email, $name = '' ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'A valid email address is required.' );
		}

		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			if ( $name ) {
				$existing->display_name = $name;
				wp_update_user(
					array(
						'ID'           => $existing->ID,
						'display_name' => $name,
					)
				);
			}
			return (int) $existing->ID;
		}

		$username = sanitize_user( current( explode( '@', $email ) ), true );
		if ( username_exists( $username ) ) {
			$username .= '_' . wp_rand( 100, 999 );
		}

		$display = $name;
		if ( ! $display ) {
			$display = ucwords( str_replace( array( '.', '_', '-' ), ' ', current( explode( '@', $email ) ) ) );
		}

		$uid = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'display_name' => $display,
				'first_name'   => $name,
				'user_pass'    => wp_generate_password( 16, true ),
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $uid ) ) {
			return $uid;
		}
		return (int) $uid;
	}
}

/* ---- Thesis download (email gate) ---- */
add_action( 'wp_ajax_nopriv_gabochie_thesis_download', 'gabochie_value_handle_thesis_download' );
add_action( 'wp_ajax_gabochie_thesis_download', 'gabochie_value_handle_thesis_download' );
function gabochie_value_handle_thesis_download() {
	check_ajax_referer( 'gabochie_thesis_nonce', 'nonce' );

	/* Honeypot — bots fill the hidden "website" field, humans never see it */
	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success( array( 'message' => 'Thanks for your interest. Check your inbox.' ) );
	}

	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'A valid email address is required.' ), 400 );
	}

	$uid = gabochie_value_ensure_user( $email, $name );
	if ( is_wp_error( $uid ) ) {
		wp_send_json_error( array( 'message' => $uid->get_error_message() ), 500 );
	}

	$first = ! get_user_meta( $uid, 'gabochie_thesis_downloaded', true );
	update_user_meta( $uid, 'gabochie_thesis_downloaded', current_time( 'mysql' ) );
	if ( $first ) {
		update_user_meta( $uid, 'gabochie_thesis_first_download', current_time( 'mysql' ) );
	}

	/* Ensure the lead has Free Lead membership (they entered the funnel) */
	if ( function_exists( 'pmpro_changeMembershipLevel' ) && ! pmpro_hasMembershipLevel( null, $uid ) ) {
		global $wpdb;
		$free = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}pmpro_membership_levels WHERE name = 'Free Discovery' LIMIT 1" );
		if ( $free ) {
			pmpro_changeMembershipLevel( (int) $free, $uid );
		}
	}

	/* Founding-wave reserve (idempotent per user) + personalised PDF */
	$founder = gabochie_value_thesis_founding_reserve( (int) $uid );
	$pdf     = gabochie_value_thesis_write_pdf( (int) $founder['number'] );

	gabochie_value_log_agent_action(
		'thesis_download',
		array(
			'user_id'     => $uid,
			'email'       => $email,
			'name'        => $name,
			'first'       => $first,
			'founding_no' => (int) $founder['number'],
		)
	);

	/* Route the thesis reader into the lead hub (FluentCRM pipeline). */
	do_action(
		'gabochie/lead/captured',
		$email,
		array(
			'list'   => 'thesis',
			'source' => 'thesis-download',
			'name'   => $name,
		)
	);

	/* Deliver thesis PDF + funnel next step */
	$thesis_url    = home_url( '/thesis/?dl=1' );
	$quiz_url      = home_url( '/development-quiz/' );
	$calc_url      = home_url( '/building-cost-calculator/' );
	$levels_url    = home_url( '/membership-levels/' );
	$community_url = home_url( '/community/' );
	$site_name     = get_bloginfo( 'name' );

	$subject = 'Your thesis is ready — founding reader #' . $founder['number'] . ' · ' . $site_name;
	$message = 'Hi ' . ( '' !== $name ? $name : 'there' ) . ",\n\n"
		. "Your copy of the thesis — Ghana's Development Execution Gap: Why 60% of Projects Fail Before They Start — is attached. You can also read it online here:\n"
		. $thesis_url . "\n\n";
	if ( (int) $founder['number'] > 0 ) {
		$message .= 'You are founding reader #' . $founder['number'] . ". Your founding place on the paid Development Intelligence system is reserved — move before the general wave and you lock the founding rate.\n\n";
	}
	$message .= "The four steps that matter next (each is free):\n"
		. "1. Get your free Development Readiness Score — 8 dimensions, five minutes:\n"
		. $quiz_url . "\n"
		. "2. Estimate your build cost, Ghana-adjusted:\n"
		. $calc_url . "\n"
		. "3. See the plans that fit your stage — Free Discovery, Annual Advisory:\n"
		. $levels_url . "\n"
		. "4. Ask the community building in Ghana right now:\n"
		. $community_url . "\n\n"
		. '— The ' . $site_name . ' Team';

	$attachments = array();
	if ( is_string( $pdf ) && '' !== $pdf ) {
		$attachments[] = $pdf;
	}

	wp_mail( $email, $subject, $message, array( 'Content-Type: text/plain; charset=UTF-8' ), $attachments );

	/* The send happens inside wp_mail (Brevo or core), so the temp file can go now */
	if ( is_string( $pdf ) && '' !== $pdf && function_exists( 'wp_delete_file' ) ) {
		wp_delete_file( $pdf );
	}

	wp_send_json_success(
		array(
			'message' => 'Check your inbox — your thesis PDF is on its way.',
		)
	);
}

/* ---- Auto-create the /thesis/ page with seeded content ---- */
add_action(
	'init',
	function () {
		if ( get_option( 'gabochie_value_thesis_page_created' ) ) {
			return;
		}

		$page = get_page_by_path( 'thesis', OBJECT, 'page' );
		if ( ! $page || ! isset( $page->ID ) ) {
			wp_insert_post(
				array(
					'post_title'   => 'The Development Execution Gap',
					'post_name'    => 'thesis',
					'post_content' => gabochie_value_thesis_content(),
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'meta_input'   => array( '_wp_page_template' => 'page-templates/template-thesis.php' ),
				)
			);
		}

		update_option( 'gabochie_value_thesis_page_created', true );
	}
);
