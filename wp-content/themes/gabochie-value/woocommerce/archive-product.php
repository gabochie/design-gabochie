<?php
/**
 * Template Name: WooCommerce Shop Archive (override)
 *
 * Premium /shop/ page — product cards grouped by category in the
 * gabochie-value design system (teal / gold / cream).
 *
 * @package Gabochie_Value
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<style>
/* ══════════════════════════════════════════════
	SHOP PAGE  (gv-shop- prefix)
	Layout only — tokens/buttons/heroes/badges live in
	assets/css/userflow.css (design guide canonical).
	══════════════════════════════════════════════ */
.gv-shop { background: var(--gv-cream); padding-bottom: 4rem; }

/* Hero */
.gv-shop-hero { background: var(--gv-teal-deep); position:relative; overflow:hidden; padding: 4.5rem 0 4rem; }
.gv-shop-hero::before {
	content:''; position:absolute; right:-120px; top:-120px; width:420px; height:420px;
	border:1px solid rgba(201,169,110,0.25); border-radius:50%;
}
.gv-shop-hero::after {
	content:''; position:absolute; right:40px; bottom:-160px; width:320px; height:320px;
	border:1px solid rgba(201,169,110,0.18); border-radius:50%;
}
.gv-shop-hero .container { position:relative; z-index:1; }
.gv-shop-hero .breadcrumb { color: rgba(250,247,242,0.7); }
.gv-shop-hero .breadcrumb a { color: var(--gv-gold); }
.gv-shop-hero h1 {
	font-family:'Playfair Display',serif; font-size: clamp(2rem, 4.5vw, 3.2rem);
	color: var(--gv-cream); margin: 1rem 0 0.8rem; line-height:1.15; font-weight:600;
}
.gv-shop-hero h1 em { font-style: italic; color: var(--gv-gold); }
.gv-shop-hero p { color: rgba(250,247,242,0.82); max-width: 640px; font-size: 1.02rem; line-height: 1.7; }

/* Intro strip under hero */
.gv-shop-intro { padding: 2.8rem 0 0.5rem; }
.gv-shop-ey {
	color:#9a7b3f; font-size:0.72rem; font-weight:700; letter-spacing:0.24em;
	text-transform:uppercase; margin-bottom:0.7rem; font-family:'Jost',sans-serif;
}
.gv-shop-intro h2 { font-family:'Playfair Display',serif; font-size: clamp(1.6rem, 3vw, 2.1rem); color: var(--gv-charcoal); margin:0 0 0.9rem; }
.gv-shop-intro h2 em { font-style:italic; color: var(--gv-gold); }
.gv-shop-intro p { color: var(--gv-mist); max-width: 700px; line-height:1.7; }

/* Category groups */
.gv-shop-group { padding: 2.6rem 0 0.6rem; }
.gv-shop-group-title {
	display:flex; align-items:center; gap:1rem; margin-bottom:1.6rem;
}
.gv-shop-group-title h3 {
	font-family:'Playfair Display',serif; font-size:1.45rem; color:var(--gv-charcoal); margin:0; white-space:nowrap;
}
.gv-shop-group-title .line { flex:1; height:1px; background: var(--gv-border); }

/* Product grid + cards */
.gv-shop-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:1.6rem; }
.gv-shop-card {
	background:#fff; border:1px solid var(--gv-border-light); border-radius:18px;
	padding:1.9rem 1.7rem 1.7rem; display:flex; flex-direction:column; position:relative;
	transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.gv-shop-card:hover { transform:translateY(-4px); box-shadow:0 18px 40px rgba(13,92,82,0.10); border-color: rgba(13,92,82,0.25); }
.gv-shop-card--featured { border:2px solid var(--gv-gold); background: linear-gradient(180deg,#FDFAF4,#FFFDF8); }
.gv-shop-card--featured:hover { border-color: var(--gv-gold); }
.gv-shop-ribbon {
	position:absolute; top:-13px; left:50%; transform:translateX(-50%);
	background:var(--gv-gold); color:var(--gv-teal-deep); font-size:0.62rem; font-weight:700;
	letter-spacing:0.14em; text-transform:uppercase; padding:0.35rem 1rem; border-radius:999px;
	white-space:nowrap; box-shadow:0 4px 12px rgba(201,169,110,0.35);
}
.gv-shop-tier { align-self:flex-start; margin-bottom:1rem; }
.gv-shop-tier span {
	display:inline-block; padding:0.3rem 0.8rem; border-radius:999px;
	font-size:0.62rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase;
}
.gv-shop-tier-membership { background: rgba(201,169,110,0.16); color:#9a7b3f; }
.gv-shop-tier-service { background:#E8F5F0; color:var(--gv-teal); }
.gv-shop-ico {
	width:54px; height:54px; border-radius:14px; display:flex; align-items:center; justify-content:center;
	background:#E8F5F0; color:var(--gv-teal); margin-bottom:1.25rem;
}
.gv-shop-card--featured .gv-shop-ico { background: rgba(201,169,110,0.16); color:#9a7b3f; }
.gv-shop-ico svg { width:26px; height:26px; }
.gv-shop-name { font-family:'Playfair Display',serif; font-size:1.45rem; font-weight:600; color:var(--gv-charcoal); margin:0 0 0.45rem; }
.gv-shop-desc { font-size:0.9rem; color:var(--gv-mist); line-height:1.65; margin-bottom:1.2rem; }
.gv-shop-price-row { margin-bottom:1.2rem; }
.gv-shop-price { font-family:'Playfair Display',serif; font-size:2rem; font-weight:700; color:var(--gv-teal-deep); line-height:1; }
.gv-shop-period { font-size:0.76rem; color:var(--gv-mist); letter-spacing:0.04em; margin-top:0.35rem; }
.gv-shop-feats { list-style:none; margin:0 0 1.5rem; padding:0; border-top:1px solid var(--gv-border-light); padding-top:1.1rem; }
.gv-shop-feats li {
	display:flex; align-items:flex-start; gap:0.55rem; font-size:0.86rem; color:var(--gv-charcoal);
	padding:0.28rem 0; line-height:1.5;
}
.gv-shop-feats li svg { width:16px; height:16px; color:var(--gv-gold); flex-shrink:0; margin-top:2px; }
.gv-shop-cta {
	background:var(--gv-teal); color:var(--gv-cream); text-align:center; padding:0.95rem 1.4rem;
	border-radius:12px; font-family:'Jost',sans-serif; font-size:0.82rem; font-weight:600;
	letter-spacing:0.08em; text-transform:uppercase; text-decoration:none; transition:all .2s ease; margin-top:auto;
}
.gv-shop-cta:hover { background:var(--gv-teal-deep); color:var(--gv-cream); transform:translateY(-1px); box-shadow:0 6px 16px rgba(13,92,82,0.25); }
.gv-shop-card--featured .gv-shop-cta { background:var(--gv-gold); color:var(--gv-teal-deep); }
.gv-shop-card--featured .gv-shop-cta:hover { background:#b8955e; color:var(--gv-teal-deep); }
.gv-shop-learn {
	display:block; text-align:center; margin-top:0.8rem; font-size:0.74rem; color:var(--gv-mist);
	letter-spacing:0.05em; text-decoration:none;
}
.gv-shop-learn:hover { color:var(--gv-teal); }

/* Trust band */
.gv-shop-trust { margin:3.2rem 0 1rem; background:#fff; border:1px solid var(--gv-border-light); border-radius:18px; padding:1.8rem 2rem; }
.gv-shop-trust-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1.4rem; }
.gv-shop-trust-item { display:flex; align-items:flex-start; gap:0.75rem; }
.gv-shop-trust-item svg { width:22px; height:22px; color:var(--gv-gold); flex-shrink:0; margin-top:1px; }
.gv-shop-trust-item div strong { display:block; font-size:0.88rem; font-weight:600; color:var(--gv-charcoal); margin-bottom:0.2rem; font-family:'Jost',sans-serif; }
.gv-shop-trust-item div span { font-size:0.78rem; color:var(--gv-mist); line-height:1.5; display:block; }

/* FAQ */
.gv-shop-faq { padding:2.6rem 0 0.4rem; max-width:760px; }
.gv-shop-faq h2 { font-family:'Playfair Display',serif; font-size:1.6rem; color:var(--gv-charcoal); margin:0 0 1.2rem; }
.gv-shop-faq h2 em { font-style:italic; color:var(--gv-gold); }
.gv-shop-faq dt { font-weight:600; color:var(--gv-teal-deep); margin:1.1rem 0 0.3rem; font-size:0.95rem; }
.gv-shop-faq dd { color:var(--gv-mist); font-size:0.88rem; line-height:1.65; margin:0; }

/* CTA band */
.gv-shop-final { margin-top:3rem; background:var(--gv-teal); border-radius:20px; padding:3rem 2.4rem; text-align:center; }
.gv-shop-final h2 { font-family:'Playfair Display',serif; font-size:clamp(1.6rem,3vw,2.2rem); color:#fff; margin:0 0 0.7rem; }
.gv-shop-final h2 em { font-style:italic; color:var(--gv-gold); }
.gv-shop-final p { color:rgba(250,247,242,0.85); max-width:560px; margin:0 auto 1.7rem; line-height:1.65; }
.gv-shop-final .btn-gold { display:inline-block; }
.gv-shop-final .gv-shop-note { display:block; margin-top:1.1rem; font-size:0.76rem; color:rgba(250,247,242,0.6); }

/* Responsive */
@media (max-width: 900px) {
	.gv-shop-grid { grid-template-columns:1fr; max-width:480px; margin:0 auto; }
	.gv-shop-trust-grid { grid-template-columns:1fr 1fr; }
	.gv-shop-hero { padding:3.2rem 0 3rem; }
}
@media (max-width: 560px) {
	.gv-shop-card { padding:1.6rem 1.3rem; }
	.gv-shop-trust-grid { grid-template-columns:1fr; }
	.gv-shop-trust { padding:1.5rem 1.3rem; }
}
</style>

<main class="gv-shop">

<!-- HERO -->
<section class="gv-shop-hero">
	<div class="container">
		<div class="breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / Shop</div>
		<h1>Clarity you can <em>buy.</em></h1>
		<p>Development services and membership plans on a fixed, transparent fee. No hidden charges, no obligation to continue — pick what fits where you are.</p>
	</div>
</section>

<!-- INTRO -->
<section class="gv-shop-intro">
	<div class="container">
		<div class="gv-shop-ey">The Gabochie service catalogue</div>
		<h2>Choose your <em>next step</em></h2>
		<p>Every offer below is a fixed-fee service delivered by the Gabochie Design team. Start with the free diagnosis, or go straight to the plan that fits your stage.</p>
	</div>
</section>

<?php
/* ---------- Gather published products, grouped by category ---------- */
$shop_active_cat = '';
if ( is_product_category() ) {
	$cur             = get_queried_object();
	$shop_active_cat = ( $cur && isset( $cur->slug ) ) ? $cur->slug : '';
}
$shop_args = array(
	'post_type'      => 'product',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'menu_order date',
	'order'          => 'ASC',
);
if ( $shop_active_cat ) {
	$shop_args['tax_query'] = array(
		array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $shop_active_cat,
		),
	);
}
$shop_loop = new WP_Query( $shop_args );

/* category display order */
$cat_order = array( 'memberships', 'services' );
$cat_map   = array();
foreach ( $cat_order as $cslug ) {
	$t = get_term_by( 'slug', $cslug, 'product_cat' );
	if ( $t && ! is_wp_error( $t ) ) {
		$cat_map[ $cslug ] = array(
			'id'       => (int) $t->term_id,
			'name'     => $t->name,
			'products' => array(),
		);
	}
}
$cat_map['uncategorized'] = array(
	'id'       => 0,
	'name'     => 'Shop',
	'products' => array(),
);

if ( $shop_loop->have_posts() ) {
	while ( $shop_loop->have_posts() ) {
		$shop_loop->the_post();
		$pid     = get_the_ID();
		$product = wc_get_product( $pid );
		if ( ! $product ) {
			continue; }

		$cat_terms = get_the_terms( $pid, 'product_cat' );
		$placed    = false;
		if ( $cat_terms && ! is_wp_error( $cat_terms ) ) {
			foreach ( $cat_terms as $ct ) {
				foreach ( $cat_order as $cslug ) {
					if ( isset( $cat_map[ $cslug ] ) && $ct->slug === $cslug ) {
						$cat_map[ $cslug ]['products'][] = $product;
						$placed                          = true;
					}
				}
			}
		}
		if ( ! $placed ) {
			$cat_map['uncategorized']['products'][] = $product;
		}
	}
	wp_reset_postdata();

	/* icons + tier labels per category */
	$shop_icons        = array(
		'memberships' => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="8" y="12" width="32" height="26" rx="4"/><circle cx="16" cy="26" r="2.6" fill="currentColor" stroke="none"/><circle cx="24" cy="26" r="1.8" fill="currentColor" stroke="none"/><path d="M14 38l20-24"/></svg>',
		'services'    => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 22h6l2.4-3.6h3.2L28 22h6v6h-6l-2.4 3.6h-3.2L20 28h-6z"/><circle cx="24" cy="25" r="7"/></svg>',
	);
	$shop_default_icon = '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2"><circle cx="24" cy="24" r="18"/><path d="M12 24h24M24 12v24"/></svg>';

	$render_cats = $shop_active_cat ? array( $shop_active_cat ) : $cat_order;
	foreach ( $render_cats as $cslug ) {
		$group = $cat_map[ $cslug ];
		if ( empty( $group['products'] ) ) {
			continue; }

		echo '<section class="gv-shop-group"><div class="container">';
		echo '<div class="gv-shop-group-title"><h3>' . esc_html( $group['name'] ) . '</h3><span class="line"></span></div>';
		echo '<div class="gv-shop-grid">';

		$ix = 0;
		foreach ( $group['products'] as $product ) {
			++$ix;
			$is_featured = ( $cslug === 'memberships' && $product->get_slug() === 'active-client' );
			$tier        = ( $cslug === 'memberships' ) ? 'membership' : 'service';
			$icon        = isset( $shop_icons[ $cslug ] ) ? $shop_icons[ $cslug ] : $shop_default_icon;

			/* price + period: bridged memberships read from PMPro level; services one-time */
			$price_html = $product->get_price_html();
			$period     = 'one-time payment';
			$level_map  = (array) get_option( '_pmprowoo_product_levels', array() );
			if ( isset( $level_map[ $product->get_id() ] ) && function_exists( 'pmpro_getLevel' ) ) {
				$level = pmpro_getLevel( is_array( $level_map[ $product->get_id() ] ) ? (int) reset( $level_map[ $product->get_id() ] ) : (int) $level_map[ $product->get_id() ] );
				if ( $level && (float) $level->billing_amount > 0 ) {
					$period = 'per ' . strtolower( $level->cycle_period );
				}
			}

			printf(
				'<div class="gv-shop-card%s">%s<div class="gv-shop-tier"><span class="gv-shop-tier-%s">%s</span></div><div class="gv-shop-ico">%s</div><h2 class="gv-shop-name">%s</h2><p class="gv-shop-desc">%s</p><div class="gv-shop-price-row"><div class="gv-shop-price">%s</div><div class="gv-shop-period">%s</div></div>',
				$is_featured ? ' gv-shop-card--featured' : '',
				$is_featured ? '<div class="gv-shop-ribbon">Most Popular</div>' : '',
				esc_attr( $tier ),
				esc_html( $tier === 'membership' ? 'Membership' : 'Service' ),
				wp_kses_post( $icon ),
				esc_html( $product->get_name() ),
				esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ), 22 ) ),
				wp_kses_post( $price_html ),
				esc_html( $period )
			);

			/* feature list: for memberships pull PMPro level benefits; services use bullets from description */
			echo '<ul class="gv-shop-feats">';
			$feats = array();
			if ( isset( $level_map[ $product->get_id() ] ) && function_exists( 'pmpro_getLevel' ) ) {
				$level = pmpro_getLevel( is_array( $level_map[ $product->get_id() ] ) ? (int) reset( $level_map[ $product->get_id() ] ) : (int) $level_map[ $product->get_id() ] );
				if ( $level && ! empty( $level->description ) ) {
					foreach ( preg_split( '/[\r\n]+/', $level->description ) as $ln ) {
						$ln = trim( (string) $ln );
						if ( $ln !== '' ) {
							$feats[] = $ln; }
					}
				}
			}
			if ( empty( $feats ) ) {
				foreach ( preg_split( '/[\r\n]+/', $product->get_description() ) as $ln ) {
					$ln = ltrim( trim( (string) $ln ), '-*' );
					if ( $ln !== '' && strpos( strtolower( $ln ), 'this is a ' ) !== 0 ) {
						$feats[] = $ln; }
				}
			}
			$feats = array_slice( $feats, 0, 5 );
			foreach ( $feats as $f ) {
				echo '<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="4,12 10,18 20,6"/></svg>' . esc_html( wp_strip_all_tags( $f ) ) . '</li>';
			}
			echo '</ul>';

			$buy_link = $product->get_permalink();
			printf(
				'<a class="gv-shop-cta" href="%s">%s</a><a class="gv-shop-learn" href="%s">View details &amp; checkout &rarr;</a>',
				esc_url( $buy_link ),
				esc_html( $product->is_type( 'simple' ) ? 'Get Started' : 'View Offer' ),
				esc_url( $buy_link )
			);
			echo '</div>';
		}
		echo '</div></div></section>';
	}
} else {
	echo '<section class="gv-shop-group"><div class="container"><p style="color:var(--gv-mist)">The shop is being stocked — check back shortly.</p></div></section>';
}
?>

<!-- TRUST -->
<section class="gv-shop-trust"><div class="container">
	<div class="gv-shop-trust-grid">
		<div class="gv-shop-trust-item">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
			<div><strong>Secure checkout</strong><span>Paid through trusted Ghana payment gateways. No card stored without your consent.</span></div>
		</div>
		<div class="gv-shop-trust-item">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 3v6c0 5-3.5 8.5-8 11-4.5-2.5-8-6-8-11V5z"/><path d="M9 12l2 2 4-4"/></svg>
			<div><strong>Fixed, transparent fees</strong><span>One fee, clear scope. You know what you're buying before you commit a cedi.</span></div>
		</div>
		<div class="gv-shop-trust-item">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
			<div><strong>Delivered on a schedule</strong><span>Diagnostics and deliverables have a defined turnaround your team confirms before work begins.</span></div>
		</div>
		<div class="gv-shop-trust-item">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.5 5 5.5.8-4 3.9.9 5.5L12 14.8 7.1 17.2l.9-5.5L4 7.8 9.5 7z"/></svg>
			<div><strong>No obligation to continue</strong><span>Start with a free diagnosis — membership is always your choice, never a trap.</span></div>
		</div>
	</div>
</div></section>

<!-- FAQ -->
<section class="gv-shop-faq"><div class="container">
	<h2>Before you <em>buy</em></h2>
	<dl>
		<dt>Why are plans priced in GHS?</dt>
		<dd>Gabochie Design bills in Ghana cedis so every fee is transparent for local and diaspora clients alike — no exchange-rate surprises.</dd>
		<dt>What do I get with the Recovery Diagnostic?</dt>
		<dd>A structured review of your drawings, contracts, site and spend, ending in a written report: what's wrong, what it costs to fix, and whether continuing beats starting over.</dd>
		<dt>Can I upgrade or change plans later?</dt>
		<dd>Yes. Start with the Free Discovery membership, then step up to Annual Advisory when your project needs year-round support.</dd>
		<dt>What payment methods are accepted?</dt>
		<dd>Mobile money via <strong>Moolre Payments</strong> &mdash; MTN MoMo, Telecel Cash and AT Money &mdash; paid directly at checkout, with no account needed. Would rather settle by hand? Call <a href="tel:<?php echo esc_attr( gabochie_value_phone_tel() ); ?>"><?php echo esc_html( gabochie_value_phone() ); ?></a> and we&rsquo;ll confirm your order before you pay.</dd>
	</dl>
</div></section>

<!-- CTA -->
<section class="gv-shop-final"><div class="container">
	<h2>Not sure which offer fits? <em>Start free.</em></h2>
	<p>Answer a few questions and get your Development Readiness Score — a free first step, no card required.</p>
	<a class="btn-gold" href="<?php echo esc_url( home_url( '/onboarding/' ) ); ?>">Start Here — Free</a>
	<span class="gv-shop-note">Free Discovery · Annual Advisory — upgrade any time, no lock-in.</span>
	<p class="gv-shop-callfirst">Want a human first? Call <a href="tel:<?php echo esc_attr( gabochie_value_phone_tel() ); ?>"><?php echo esc_html( gabochie_value_phone() ); ?></a> and talk your project through with Gideon before you pay.</p>
</div></section>

</main>

<?php
get_footer();