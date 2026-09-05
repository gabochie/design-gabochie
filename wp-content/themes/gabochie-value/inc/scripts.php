<?php
/**
 * Scripts & Styles — enqueuing
 * @package Gabochie_Value
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gabochie_value_scripts() {
	// Preconnect + preload for Google Fonts (critical path)
	add_action(
		'wp_head',
		function () {
			echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
			echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
			// Preload critical font files to avoid FOIT on above-the-fold text
			echo '<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;1,400&family=Jost:wght@300;400;500&display=swap">' . "\n";
		},
		1
	);

	// Brand fonts (gabochiedesign.com typography) — font-display:swap prevents FOIT
	wp_enqueue_style(
		'gabochie-value-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap',
		array(),
		null
	);

	// Main styles
	wp_enqueue_style(
		'gabochie-value-main',
		GABOCHIE_VALUE_URI . '/assets/css/styles.css',
		array( 'gabochie-value-fonts' ),
		GABOCHIE_VALUE_VERSION
	);

	// Membership / portal styles (loaded on all pages)
	wp_enqueue_style(
		'gabochie-membership',
		GABOCHIE_VALUE_URI . '/assets/css/membership.css',
		array( 'gabochie-value-main' ),
		GABOCHIE_VALUE_VERSION
	);

	// Userflow layer: canonical heroes/buttons/cards/recovery/checkout (all pages)
	wp_enqueue_style(
		'gabochie-userflow',
		GABOCHIE_VALUE_URI . '/assets/css/userflow.css',
		array( 'gabochie-membership' ),
		GABOCHIE_VALUE_VERSION
	);

	// Main script (footer, no jQuery dependency)
	wp_enqueue_script(
		'gabochie-value-main',
		GABOCHIE_VALUE_URI . '/assets/js/main.js',
		array(),
		GABOCHIE_VALUE_VERSION,
		true
	);

	wp_localize_script(
		'gabochie-value-main',
		'gabochieValue',
		array(
			'homeUrl'  => home_url( '/' ),
			'whatsapp' => gabochie_value_whatsapp(),
		)
	);

	/* ---- Onboarding page assets ---- */
	if ( is_page_template( 'page-templates/template-onboarding.php' ) ) {
		wp_enqueue_style(
			'gabochie-onb',
			GABOCHIE_VALUE_URI . '/assets/css/onboarding.css',
			array( 'gabochie-value-main' ),
			GABOCHIE_VALUE_VERSION
		);
		wp_enqueue_script(
			'gabochie-onb',
			GABOCHIE_VALUE_URI . '/assets/js/onboarding.js',
			array(),
			GABOCHIE_VALUE_VERSION,
			true
		);
		wp_localize_script(
			'gabochie-onb',
			'gabochieOnb',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'gabochie_onb_nonce' ),
				'loginUrl' => esc_url( home_url( '/login/' ) ),
			)
		);
	}

	/* ---- Project Recovery page assets ---- */
	if ( is_page_template( 'page-templates/template-project-recovery.php' ) ) {
		wp_enqueue_style(
			'gabochie-onb',
			GABOCHIE_VALUE_URI . '/assets/css/onboarding.css',
			array( 'gabochie-value-main' ),
			GABOCHIE_VALUE_VERSION
		);
	}

	/* ---- Development Quiz page assets ---- */
	if ( is_page_template( 'page-templates/template-quiz.php' ) ) {
		wp_enqueue_style(
			'gabochie-onb',
			GABOCHIE_VALUE_URI . '/assets/css/onboarding.css',
			array( 'gabochie-value-main' ),
			GABOCHIE_VALUE_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'gabochie_value_scripts', 99 );
