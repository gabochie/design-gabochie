<?php
/**
 * SEO / LLMO / AEO — dynamic llms.txt, post breadcrumbs, auto TOC
 * @package Gabochie_Value
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================
	LLMO: dynamic llms.txt (always-current AI front door)
	============================================ */

/**
 * Serve /llms.txt from PHP so the catalog always matches live content and
 * uses the correct host per environment. Yoast's static file is disabled
 * server-side; its writer is also redirected to a non-web path as a guard.
 */
function gabochie_value_maybe_serve_llmstxt() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
	if ( null === $path || rtrim( $path, '/' ) !== '/llms.txt' ) {
		return;
	}
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex', true );
	echo gabochie_value_build_llmstxt(); // phpcs:ignore WordPress.Security.EscapeOutput -- plain-text markdown document; HTML escaping would corrupt it.
	exit;
}
add_action( 'init', 'gabochie_value_maybe_serve_llmstxt', 1 );

/** Keep Yoast from ever re-writing a static llms.txt over our dynamic route. */
function gabochie_value_llmstxt_redirect_yoast_path( $path ) {
	return sys_get_temp_dir() . '/yoast-llms.txt';
}
add_filter( 'wpseo_llmstxt_filesystem_path', 'gabochie_value_llmstxt_redirect_yoast_path' );

/** Escape markdown-significant characters for llms.txt. */
function gabochie_value_llmstxt_escape( $text ) {
	$text = str_replace( '\\', '\\\\', $text );
	$text = str_replace( array( '[', ']', '(', ')' ), array( '\\[', '\\]', '\\(', '\\)' ), $text );
	return $text;
}

/** Extractive 200-char description of a post for llms.txt. */
function gabochie_value_llmstxt_post_description( $post ) {
	$content = wp_strip_all_tags( do_shortcode( $post->post_content ) );
	$content = trim( preg_replace( '/\s+/', ' ', $content ) );
	if ( $content === '' ) {
		return '';
	}
	if ( function_exists( 'mb_substr' ) ) {
		$cut = mb_substr( $content, 0, 200 );
	} else {
		$cut = substr( $content, 0, 200 );
	}
	if ( strlen( $content ) > 200 ) {
		$space = function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );
		if ( $space > 120 ) {
			$cut = function_exists( 'mb_substr' ) ? mb_substr( $cut, 0, $space ) : substr( $cut, 0, $space );
		}
		$cut .= '...';
	}
	return $cut;
}

/** Build the full llms.txt document. */
function gabochie_value_build_llmstxt() {
	$lines   = array();
	$lines[] = '# ' . get_bloginfo( 'name' ) . ': Building in Ghana — Design, Costs & Delivery Intelligence';
	$lines[] = '';
	$lines[] = '> Gabochie Design is a Ghana-based development facilitation, building design and cost intelligence consultancy serving homeowners, investors, developers and the Ghanaian diaspora. We cover what to build, what it costs, how it performs and what to do next — from land due diligence and permits through design, costing and project delivery.';
	$lines[] = '';
	$lines[] = 'Content focus: practical, data-backed guidance on building in Ghana — construction costs in GHS per square metre, land acquisition and due diligence, building permits, contractor selection, design, and remote project delivery for the diaspora building from the UK, USA, Canada and beyond.';
	$lines[] = '';

	/* Key pages / tools */
	$pages   = array(
		array( 'Home — development platform overview', home_url( '/' ) ),
		array( 'About — the consultancy behind the promise', home_url( '/about/' ) ),
		array( 'Services — design, cost intelligence, project delivery', home_url( '/#services' ) ),
		array( 'Building Cost Calculator — free GHS build estimate', home_url( '/building-cost-calculator/' ) ),
		array( 'The Execution Gap thesis — why 60% of Ghanaian projects stall', home_url( '/thesis/' ) ),
		array( 'Community Q&A — real Ghana building questions answered', home_url( '/community/' ) ),
		array( 'Onboarding wizard — start a project in five minutes', home_url( '/onboarding/' ) ),
		array( 'Membership levels — free to premium plans', home_url( '/membership-levels/' ) ),
		array( 'Blog — all guides', home_url( '/blog/' ) ),
		array( 'Contact', home_url( '/contact/' ) ),
	);
	$lines[] = '## Key Pages';
	foreach ( $pages as $p ) {
		$lines[] = '- [' . gabochie_value_llmstxt_escape( $p[0] ) . '](' . $p[1] . ')';
	}
	$lines[] = '';

	/* All published posts, newest first */
	$lines[] = '## Posts';
	$q       = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	foreach ( $q->posts as $post ) {
		$url   = get_permalink( $post );
		$title = get_the_title( $post );
		$desc  = gabochie_value_llmstxt_post_description( $post );
		$line  = '- [' . gabochie_value_llmstxt_escape( $title ) . '](' . $url . ')';
		if ( $desc !== '' ) {
			$line .= ': ' . gabochie_value_llmstxt_escape( $desc );
		}
		$lines[] = $line;
	}
	wp_reset_postdata();
	$lines[] = '';

	/* Categories */
	$cats = get_categories( array( 'hide_empty' => true ) );
	if ( ! empty( $cats ) ) {
		$lines[] = '## Categories';
		foreach ( $cats as $cat ) {
			$lines[] = '- [' . gabochie_value_llmstxt_escape( $cat->name ) . '](' . get_category_link( $cat ) . ')';
		}
		$lines[] = '';
	}

	/* Community questions (Q&A is prime answer-engine content) */
	$qq = new WP_Query(
		array(
			'post_type'      => 'gabochie_question',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	if ( ! empty( $qq->posts ) ) {
		$lines[] = '## Community Questions';
		foreach ( $qq->posts as $question ) {
			$lines[] = '- [' . gabochie_value_llmstxt_escape( get_the_title( $question ) ) . '](' . get_permalink( $question ) . ')';
		}
		$lines[] = '';
	}
	wp_reset_postdata();

	$lines[] = '## Optional';
	$lines[] = '- [Sitemap index](' . home_url( '/sitemap_index.xml' ) . ')';
	$lines[] = '';

	return implode( "\n", $lines );
}

/* ============================================
	AEO: visible breadcrumbs on single posts
	============================================ */

function gabochie_value_breadcrumbs() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$title = get_the_title();
	$cats  = get_the_category();
	$cat   = ! empty( $cats ) ? $cats[0] : null;

	echo '<nav class="gv-breadcrumbs" aria-label="Breadcrumb">';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '">Home</a>';
	echo '<span class="gv-bc-sep" aria-hidden="true">/</span>';
	echo '<a href="' . esc_url( home_url( '/blog/' ) ) . '">Blog</a>';
	if ( $cat ) {
		echo '<span class="gv-bc-sep" aria-hidden="true">/</span>';
		echo '<a href="' . esc_url( get_category_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a>';
	}
	echo '<span class="gv-bc-sep" aria-hidden="true">/</span>';
	echo '<span class="gv-bc-current" aria-current="page">' . esc_html( $title ) . '</span>';
	echo '</nav>';
}

/* ============================================
	AEO: auto table of contents with anchored H2s
	============================================ */

/**
 * Add ids to H2s and prepend an "On this page" list when a post has 3+ H2s.
 * Jump anchors help featured snippets and answer-engine extraction.
 */
function gabochie_value_toc_content( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$items = array();
	$used  = array();

	$content = preg_replace_callback(
		'/<h2([^>]*)>(.*?)<\/h2>/is',
		function ( $m ) use ( &$items, &$used ) {
			$text = trim( wp_strip_all_tags( $m[2] ) );
			if ( $text === '' ) {
				return $m[0];
			}
			$has_id = preg_match( '/id=["\']([^"\']+)["\']/', $m[1], $idm );
			if ( $has_id ) {
				$id = $idm[1];
			} else {
				$id = 'gv-' . sanitize_title( $text );
				$i  = 2;
				while ( in_array( $id, $used, true ) ) {
					$id = 'gv-' . sanitize_title( $text ) . '-' . $i;
					$i++;
				}
			}
			$used[]  = $id;
			$items[] = array(
				'id'   => $id,
				'text' => $text,
			);
			if ( $has_id ) {
				return $m[0];
			}
			return '<h2 id="' . esc_attr( $id ) . '"' . $m[1] . '>' . $m[2] . '</h2>';
		},
		$content
	);

	if ( count( $items ) < 3 ) {
		return $content;
	}

	$nav  = '<nav class="gv-toc" aria-label="On this page">';
	$nav .= '<p class="gv-toc-title">On this page</p>';
	$nav .= '<ol>';
	foreach ( $items as $item ) {
		$nav .= '<li><a href="#' . esc_attr( $item['id'] ) . '">' . esc_html( $item['text'] ) . '</a></li>';
	}
	$nav .= '</ol>';
	$nav .= '</nav>';

	return $nav . "\n" . $content;
}
add_filter( 'the_content', 'gabochie_value_toc_content', 20 );

/* ============================================
   AdSense: restrict to blog posts only
   ============================================ */

/**
 * Remove Site Kit's AdSense script on pages where ads shouldn't appear.
 * Ads are allowed only on single blog posts.
 */
function gabochie_value_restrict_adsense() {
	$ads_allowed = is_singular( 'post' );

	if ( ! $ads_allowed ) {
		ob_start(
			function ( $buffer ) {
				$buffer = preg_replace( '/<script[^>]*pagead2\.googlesyndication\.com[^>]*><\/script>/i', '', $buffer );
				$buffer = preg_replace( '/<meta[^>]*google-adsense-platform[^>]*>/i', '', $buffer );
				return $buffer;
			}
		);
	}
}
add_action( 'template_redirect', 'gabochie_value_restrict_adsense', 1 );
