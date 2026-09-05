<?php
/**
 * WooCommerce content-single-product override.
 *
 * Premium product page in the gabochie-value design system. This is the
 * payment entry point (PMPro checkout redirects to the linked product page),
 * so it carries its own hero + trust + add-to-cart form.
 *
 * @package Gabochie_Value
 */

global $product;

?>
<style>
/* ══════════════════════════════════════════════
	PRODUCT PAGE  (gv-sp- prefix)
	Layout only — tokens/buttons/heroes/badges live in
	assets/css/userflow.css (design guide canonical).
	══════════════════════════════════════════════ */
.gv-sp { background: var(--gv-cream); padding-bottom: 4rem; }

/* Hero */
.gv-sp-hero { background: var(--gv-teal-deep); position:relative; overflow:hidden; padding: 4rem 0 3.2rem; }
.gv-sp-hero::before { content:''; position:absolute; right:-120px; top:-120px; width:420px; height:420px; border:1px solid rgba(201,169,110,0.25); border-radius:50%; }
.gv-sp-hero::after { content:''; position:absolute; right:40px; bottom:-160px; width:320px; height:320px; border:1px solid rgba(201,169,110,0.18); border-radius:50%; }
.gv-sp-hero .container { position:relative; z-index:1; }
.gv-sp-hero .breadcrumb { color: rgba(250,247,242,0.7); }
.gv-sp-hero .breadcrumb a { color: var(--gv-gold); }
.gv-sp-hero h1 { font-family:'Playfair Display',serif; font-size: clamp(2rem,4.5vw,3rem); color:var(--gv-cream); margin:1rem 0 0.8rem; font-weight:600; line-height:1.15; }
.gv-sp-hero h1 em { font-style:italic; color:var(--gv-gold); }
.gv-sp-hero p { color: rgba(250,247,242,0.85); max-width:620px; font-size:1.02rem; line-height:1.7; }

/* Main layout */
.gv-sp-main { margin-top: -2.4rem; position: relative; z-index: 2; }
.gv-sp-card {
	background:#fff; border:1px solid var(--gv-border-light); border-radius:18px;
	box-shadow:0 18px 40px rgba(13,92,82,0.10);
	display:grid; grid-template-columns: 1fr 1fr; overflow:hidden;
}
.gv-sp-left { padding: 2.4rem 2.6rem; }
.gv-sp-right {
	background: linear-gradient(180deg,#FDFAF4,#FFFDF8); padding: 2.4rem 2.6rem;
	border-left: 1px solid var(--gv-border-light);
}
.gv-sp-tier { display:inline-block; padding:0.3rem 0.85rem; border-radius:999px; font-size:0.62rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; background:#E8F5F0; color:var(--gv-teal); margin-bottom:1.4rem; font-family:'Jost',sans-serif; }
.gv-sp-label { font-size:0.7rem; font-weight:700; letter-spacing:0.2em; text-transform:uppercase; color:#9a7b3f; margin:0 0 0.6rem; font-family:'Jost',sans-serif; }
.gv-sp-title { font-family:'Playfair Display',serif; font-size: clamp(1.7rem,3vw,2.2rem); color:var(--gv-charcoal); margin:0 0 1.1rem; line-height:1.2; }
.gv-sp-desc, .gv-sp-long p, .gv-sp-long li { color:var(--gv-mist); font-size:0.94rem; line-height:1.7; }
.gv-sp-desc { margin-bottom:1.4rem; }
.gv-sp-ico {
	width:60px; height:60px; border-radius:16px; display:flex; align-items:center; justify-content:center;
	background:#E8F5F0; color:var(--gv-teal); margin-bottom:1.5rem;
}
.gv-sp-ico svg { width:30px; height:30px; }

/* Right column — the buy panel */
.gv-sp-price { font-family:'Playfair Display',serif; font-size:2.6rem; font-weight:700; color:var(--gv-teal-deep); line-height:1; }
.gv-sp-period { font-size:0.82rem; color:var(--gv-mist); margin-top:0.4rem; letter-spacing:0.03em; }
.gv-sp-buy { margin:1.8rem 0 1.5rem; }
.gv-sp-buy .single_add_to_cart_button {
	width:100%; background:var(--gv-gold); color:var(--gv-teal-deep); border:none; cursor:pointer;
	padding:1.05rem 1.5rem; border-radius:12px; font-family:'Jost',sans-serif; font-size:0.9rem;
	font-weight:700; letter-spacing:0.1em; text-transform:uppercase; transition:all .2s ease;
}
.gv-sp-buy .single_add_to_cart_button:hover { background:#b8955e; transform:translateY(-1px); box-shadow:0 8px 20px rgba(201,169,110,0.35); }
.gv-sp-buy .quantity { display:none; }
.gv-sp-buy .woocommerce-variation-add-to-cart { display:block; }
.gv-sp-secure {
	display:flex; align-items:center; justify-content:center; gap:0.5rem;
	font-size:0.74rem; color:var(--gv-mist); margin-top:1rem;
}
.gv-sp-secure svg { width:15px; height:15px; color:var(--gv-gold); }
.gv-sp-feats { list-style:none; margin:0; padding:1.3rem 0 0; border-top:1px solid var(--gv-border-light); }
.gv-sp-feats li { display:flex; align-items:flex-start; gap:0.6rem; font-size:0.84rem; color:var(--gv-charcoal); padding:0.34rem 0; line-height:1.5; }
.gv-sp-feats li svg { width:16px; height:16px; color:var(--gv-gold); flex-shrink:0; margin-top:2px; }
.gv-sp-trust { display:grid; grid-template-columns:1fr 1fr; gap:0.9rem; margin-top:1.5rem; border-top:1px solid var(--gv-border-light); padding-top:1.3rem; }
.gv-sp-trust-item { display:flex; align-items:flex-start; gap:0.6rem; }
.gv-sp-trust-item svg { width:18px; height:18px; color:var(--gv-gold); flex-shrink:0; margin-top:1px; }
.gv-sp-trust-item span { font-size:0.76rem; color:var(--gv-mist); line-height:1.45; }

/* Long description section */
.gv-sp-long { max-width:820px; margin:2.6rem auto 0; padding:0 1.2rem; }
.gv-sp-long h2 { font-family:'Playfair Display',serif; font-size:1.6rem; color:var(--gv-charcoal); margin:0 0 1rem; }
.gv-sp-long h2 em { font-style:italic; color:var(--gv-gold); }
.gv-sp-long ul { padding-left:1.2rem; }
.gv-sp-long li { margin-bottom:0.4rem; }

/* CTA band */
.gv-sp-final { margin-top:3rem; background:var(--gv-teal); border-radius:20px; padding:2.8rem 2.4rem; text-align:center; }
.gv-sp-final h2 { font-family:'Playfair Display',serif; font-size:clamp(1.5rem,3vw,2rem); color:#fff; margin:0 0 0.7rem; }
.gv-sp-final h2 em { font-style:italic; color:var(--gv-gold); }
.gv-sp-final p { color:rgba(250,247,242,0.85); max-width:520px; margin:0 auto 1.6rem; line-height:1.65; }
.gv-sp-final .btn-gold { display:inline-block; }

/* Responsive */
@media (max-width: 860px) {
	.gv-sp-card { grid-template-columns:1fr; }
	.gv-sp-right { border-left:0; border-top:1px solid var(--gv-border-light); }
	.gv-sp-left, .gv-sp-right { padding:1.8rem 1.5rem; }
}
</style>

<main class="gv-sp">

<?php do_action( 'woocommerce_before_single_product' ); ?>

<!-- HERO -->
<?php
$is_member = false;
$level_map = (array) get_option( '_pmprowoo_product_levels', array() );
if ( isset( $level_map[ $product->get_id() ] ) && function_exists( 'pmpro_getLevel' ) ) {
	$is_member = true;
}
$period = 'one-time payment';
if ( $is_member ) {
	$level = pmpro_getLevel( is_array( $level_map[ $product->get_id() ] ) ? (int) reset( $level_map[ $product->get_id() ] ) : (int) $level_map[ $product->get_id() ] );
	if ( $level && (float) $level->billing_amount > 0 ) {
		$period = 'per ' . strtolower( $level->cycle_period );
	}
}
?>
<?php $is_recovery_product = 'project-recovery-diagnostic' === $product->get_slug(); ?>
<section class="gv-sp-hero">
	<div class="container">
		<div class="breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <a href="<?php echo esc_url( get_post_type_archive_link( 'product' ) ? get_post_type_archive_link( 'product' ) : home_url( '/shop/' ) ); ?>">Shop</a> / <?php echo esc_html( $product->get_name() ); ?></div>
		<h1><?php echo esc_html( $product->get_name() ); ?> — <em><?php echo $is_recovery_product ? 'unlocks your recovery workspace.' : 'fixed fee, clear scope.'; ?></em></h1>
		<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ), 30 ) ); ?><?php if ( $is_recovery_product ) echo ' — Covers all building types in Ghana: residential, commercial, mixed-use, renovation & interior. Step-by-step workspace with facilitator co-drive.'; ?></p>
	</div>
</section>

<!-- BUY CARD -->
<section class="gv-sp-main"><div class="container">
	<div class="gv-sp-card">

		<div class="gv-sp-left">
			<div class="gv-sp-label">What you get</div>
			<div class="gv-sp-ico"><svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 22h6l2.4-3.6h3.2L28 22h6v6h-6l-2.4 3.6h-3.2L20 28h-6z"/><circle cx="24" cy="25" r="7"/></svg></div>
			<h2 class="gv-sp-title"><?php echo esc_html( $product->get_name() ); ?></h2>
			<p class="gv-sp-desc"><?php echo esc_html( wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : wp_trim_words( $product->get_description(), 40 ) ) ); ?></p>

			<ul class="gv-sp-feats">
				<?php
				$feats = array();
				if ( $is_member && $level && ! empty( $level->description ) ) {
					foreach ( preg_split( '/[\r\n]+/', $level->description ) as $ln ) {
						$ln = trim( (string) $ln );
						if ( $ln !== '' ) {
							$feats[] = $ln; }
					}
				}
				if ( empty( $feats ) ) {
					foreach ( preg_split( '/[\r\n]+/', $product->get_description() ) as $ln ) {
						$ln = ltrim( trim( (string) $ln ), '-*' );
						if ( $ln !== '' && strpos( strtolower( $ln ), 'this is a ' ) !== 0 ) {
							$feats[] = $ln; }
					}
				}
				foreach ( array_slice( $feats, 0, 6 ) as $f ) {
					if ( strpos( $f, 'what is wrong' ) === 0 || strpos( $f, 'what it costs' ) === 0 || strpos( $f, 'whether continuing' ) === 0 ) {
						continue; /* those are the report's own bullets */
					}
					echo '<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="4,12 10,18 20,6"/></svg>' . esc_html( wp_strip_all_tags( $f ) ) . '</li>';
				}
				?>
			</ul>
		</div>

		<div class="gv-sp-right">
			<div class="gv-sp-tier"><?php echo $is_member ? 'Membership' : 'Service'; ?></div>
			<div class="gv-sp-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
			<div class="gv-sp-period"><?php echo esc_html( $period ); ?> &middot; billed by Gabochie Design in GHS</div>

			<div class="gv-sp-buy">
				<?php woocommerce_template_single_add_to_cart(); ?>
				<div class="gv-sp-secure">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
					<span>Secure checkout via Ghana payment gateways</span>
				</div>
			</div>

			<div class="gv-sp-trust">
				<div class="gv-sp-trust-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.5 5 5.5.8-4 3.9.9 5.5L12 14.8 7.1 17.2l.9-5.5L4 7.8 9.5 7z"/></svg><span>No obligation to continue</span></div>
				<div class="gv-sp-trust-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span>Delivered on a defined turnaround</span></div>
			</div>
		</div>

	</div>
</div></section>

<!-- LONG DESCRIPTION -->
<?php if ( ! empty( $product->get_description() ) && ! $is_member ) : ?>
<section class="gv-sp-long"><div class="container">
	<h2>About this <em>offer</em></h2>
	<div class="gv-sp-long">
		<?php echo wp_kses_post( wpautop( $product->get_description() ) ); ?>
	</div>
</div></section>
<?php endif; ?>

<!-- CTA -->
<section class="gv-sp-final"><div class="container">
	<h2>Not sure this is for you? <em>Diagnose first — free.</em></h2>
	<p>Answer a few questions and get an honest readout of whether your project is worth finishing. Free, no card required.</p>
	<a class="btn-gold" href="<?php echo esc_url( gabochie_value_recovery_url() ); ?>">Diagnose My Project — Free</a>
</div></section>

<?php do_action( 'woocommerce_after_single_product' ); ?>

</main>