<?php
/**
 * Template helpers — phone, WhatsApp, section links, socials, JSON-LD
 * @package Gabochie_Value
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Display phone: '+233 26 824 8970' */
function gabochie_value_phone() {
	return get_theme_mod( 'company_phone', '+233 26 824 8970' );
}

/** Digits for tel: links: '+233268248970' */
function gabochie_value_phone_tel() {
	return preg_replace( '/[^0-9+]/', '', gabochie_value_phone() );
}

/** WhatsApp number without '+': '233268248970' */
function gabochie_value_whatsapp() {
	return get_theme_mod( 'whatsapp_number', '233268248970' );
}

/** Company email */
function gabochie_value_email() {
	return get_theme_mod( 'company_email', 'info@gabochiedesign.com' );
}

/** Absolute URL to a front-page section: gabochie_value_section_url('clarity') -> https://site/#clarity */
function gabochie_value_section_url( $anchor ) {
	return home_url( '/#' . sanitize_title( $anchor ) );
}

/**
 * "Get Clarity" target: on the front page scroll to its wizard; on any other
 * page go to the Contact page wizard when that page exists, else the front
 * page wizard. Matches VDAC's per-page clarity links.
 */
function gabochie_value_clarity_url() {
	if ( is_front_page() ) {
		return home_url( '/#clarity' );
	}
	$contact = get_page_by_path( 'contact' );
	if ( $contact && $contact->ID ) {
		return get_permalink( $contact ) . '#clarity';
	}
	return home_url( '/#clarity' );
}

/** "Diagnose My Project" target: the dedicated Project Recovery page. */
function gabochie_value_recovery_url() {
	$page = get_page_by_path( 'project-recovery', OBJECT, 'page' );
	if ( $page && $page->ID && 'trash' !== $page->post_status ) {
		return get_permalink( $page );
	}
	return home_url( '/project-recovery/' );
}

/** The paid Recovery Diagnostic product page (fallback: /shop/). */
function gabochie_value_recovery_product_url() {
	$prod = get_page_by_path( 'project-recovery-diagnostic', OBJECT, 'product' );
	if ( $prod && $prod->ID && 'publish' === $prod->post_status ) {
		return get_permalink( $prod );
	}
	$shop = (int) get_option( 'woocommerce_shop_page_id', 0 );
	if ( $shop ) {
		return get_permalink( $shop );
	}
	return home_url( '/shop/' );
}

/** Social network handle => URL (VDAC brand links) */
function gabochie_value_social_links() {
	return array(
		'facebook' => 'https://web.facebook.com/GabochieDesign',
		'x'        => 'https://x.com/gabochiedesign',
		'behance'  => 'https://www.behance.net/gabochiedesign',
		'linkedin' => 'https://www.linkedin.com/company/gabochiedesign',
		'tiktok'   => 'https://www.tiktok.com/@gabochiedesign',
	);
}

/** JSON-LD ProfessionalService (front page only) */
function gabochie_value_json_ld() {
	if ( ! is_front_page() ) {
		return;
	}
	$data = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'ProfessionalService',
		'name'        => 'Gabochie Design',
		'slogan'      => 'Value-Driven Development Facilitation',
		'description' => 'A value-driven development facilitation and design consultancy serving homeowners, investors, developers and the Ghanaian diaspora — from feasibility through design, cost, approvals, procurement, delivery and beyond.',
		'url'         => home_url( '/' ),
		'telephone'   => '+' . gabochie_value_whatsapp(),
		'address'     => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => 'Sakumono, Tema',
			'addressCountry'  => 'GH',
		),
		'sameAs'      => array_values( gabochie_value_social_links() ),
		'areaServed'  => array( 'Ghana', 'Diaspora' ),
		'knowsAbout'  => array( 'Development facilitation', 'Feasibility', 'Cost intelligence', 'Building design', 'Construction', 'Property development', "Owner's representation" ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'gabochie_value_json_ld', 1 );

/* ============================================
	AEO: FAQPage schema for posts with an FAQ section
	============================================ */

/**
 * Extract Q&A pairs from a "Frequently Asked Questions" section in post content.
 * Expects the conventional structure: an <h2>/<h3> heading whose text mentions
 * "FAQ"/"frequently asked", followed by <h3> question + <p> answer pairs.
 */
function gabochie_value_extract_faqs( $content ) {
	if ( empty( $content ) ) {
		return array();
	}
	$faqs = array();
	$dom  = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<meta charset="UTF-8"><div>' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();

	$xpath = new DOMXPath( $dom );
	$nodes = $xpath->query( '//h2|//h3' );
	if ( ! $nodes ) {
		return $faqs;
	}

	$in_faq   = false;
	$question = null;
	foreach ( $nodes as $node ) {
		$text = trim( preg_replace( '/\s+/', ' ', $node->textContent ) );

		if ( $node->nodeName === 'h2' ) {
			if ( $in_faq ) {
				break; // FAQ section ended by the next h2
			}
			if ( preg_match( '/frequently\s*asked|faq/i', $text ) ) {
				$in_faq = true;
				continue;
			}
		}

		if ( $in_faq && $node->nodeName === 'h3' ) {
			$question = $text;
			$answer   = '';
			$sib      = $node->nextSibling;
			while ( $sib ) {
				if ( $sib->nodeType === XML_ELEMENT_NODE ) {
					if ( $sib->nodeName === 'p' ) {
						$answer = trim( preg_replace( '/\s+/', ' ', $sib->textContent ) );
						break;
					}
					if ( in_array( $sib->nodeName, array( 'h2', 'h3', 'table', 'ul', 'ol' ), true ) ) {
						break;
					}
				}
				$sib = $sib->nextSibling;
			}
			if ( $question !== null && $question !== '' && $answer !== '' ) {
				$faqs[] = array(
					'q' => $question,
					'a' => $answer,
				);
			}
			$question = null;
		}
	}
	return $faqs;
}

/**
 * Inject an FAQPage node into Yoast's schema graph for posts that contain a
 * FAQ section, so answer engines get machine-readable Q&A.
 */
function gabochie_value_faq_schema_graph( $graph ) {
	if ( ! is_singular( 'post' ) ) {
		return $graph;
	}
	$post = get_queried_object();
	if ( ! $post || empty( $post->post_content ) ) {
		return $graph;
	}
	$faqs = gabochie_value_extract_faqs( $post->post_content );
	if ( count( $faqs ) < 2 ) {
		return $graph;
	}
	$main_entity = array();
	foreach ( $faqs as $faq ) {
		$main_entity[] = array(
			'@type'          => 'Question',
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}
	$graph[] = array(
		'@type'      => 'FAQPage',
		'@id'        => get_permalink( $post ) . '#faqpage',
		'url'        => get_permalink( $post ),
		'mainEntity' => $main_entity,
	);
	return $graph;
}
add_filter( 'wpseo_schema_graph', 'gabochie_value_faq_schema_graph', 20 );

/* ============================================
	AEO: meta-description fallback for placeholder Yoast descriptions
	============================================ */

function gabochie_value_is_placeholder_metadesc( $metadesc, $title = '' ) {
	$metadesc = trim( $metadesc );
	if ( $metadesc === '' ) {
		return true;
	}
	if ( preg_match( '/^Discover how .+ unlocks/i', $metadesc ) ) {
		return true;
	}
	if ( preg_match( '/^Everything you need to know about/i', $metadesc ) ) {
		return true;
	}
	if ( preg_match( '/Proven strategies, cost breakdowns/i', $metadesc ) ) {
		return true;
	}
	// Marketing-template tells: auto-generated metas (yoast-smart-autofixer
	// template fallbacks) always embed the post title AND a buzzword phrase.
	$buzz = '/(expert strategies|actionable insights|actionable steps|zero fluff|exact playbook|proven strategies|strategies experts use|costly mistakes|comprehensive guidance|industry standards|industry expertise|strategic frameworks|expert hacks|quick wins|expert advice|complete guide to|this guide covers everything)/i';
	if ( preg_match( $buzz, $metadesc ) ) {
		if ( $title !== '' && stripos( $metadesc, $title ) === false ) {
			return false;
		}
		return true;
	}
	return false;
}

function gabochie_value_yoast_metadesc_fallback( $metadesc ) {
	if ( ! is_singular() ) {
		return $metadesc;
	}
	$post  = get_queried_object();
	$title = ( $post && ! empty( $post->post_title ) ) ? $post->post_title : '';
	if ( $metadesc && ! gabochie_value_is_placeholder_metadesc( $metadesc, $title ) ) {
		return $metadesc;
	}
	if ( ! $post || empty( $post->post_content ) ) {
		return $metadesc;
	}
	$content = preg_replace( '/\[[^\]]+\]/', '', $post->post_content ); // strip shortcodes
	$content = wp_strip_all_tags( $content );
	$content = trim( preg_replace( '/\s+/', ' ', $content ) );
	if ( $content === '' ) {
		return $metadesc;
	}
	$fallback = function_exists( 'mb_substr' ) ? mb_substr( $content, 0, 158 ) : substr( $content, 0, 158 );
	if ( $fallback === '' ) {
		return $metadesc;
	}
	return $fallback;
}
add_filter( 'wpseo_metadesc', 'gabochie_value_yoast_metadesc_fallback', 20 );

/* ============================================
	AEO: richer link descriptions in Yoast's llms.txt
	============================================ */

function gabochie_value_llmstxt_link_description( $description, $post_id, $post_type ) {
	if ( $description && strlen( $description ) > 40 ) {
		return $description;
	}
	$post = get_post( $post_id );
	if ( ! $post || empty( $post->post_content ) ) {
		return $description;
	}
	$content = wp_strip_all_tags( do_shortcode( $post->post_content ) );
	$content = trim( preg_replace( '/\s+/', ' ', $content ) );
	if ( $content === '' ) {
		return $description;
	}
	$trimmed = function_exists( 'mb_substr' ) ? mb_substr( $content, 0, 200 ) : substr( $content, 0, 200 );
	return $trimmed . '…';
}
add_filter( 'wpseo_llmstxt_link_description', 'gabochie_value_llmstxt_link_description', 10, 3 );
