<?php
/**
 * Template Name: Trust & Verify
 * Meet-the-principal page — verify me, see what you get before you pay,
 * and choose how you want to pay (online or talk-first stages).
 * @package Gabochie_Value
 */

get_header();

$phone    = gabochie_value_phone();
$phone_tel = gabochie_value_phone_tel();
$whatsapp = gabochie_value_whatsapp();

$wa_hello    = rawurlencode( 'Hello, I found Gabochie Design and I have questions about my building project.' );
$wa_diag     = rawurlencode( 'Hello, I would like to talk about the Recovery Diagnostic before I pay anything.' );
$wa_advisory = rawurlencode( 'Hello, I would like to know more about the Annual Advisory engagement.' );
$wa_build    = rawurlencode( 'Hello, I want to talk about my building project and what it would cost.' );

$wa_base = 'https://wa.me/' . $whatsapp . '?text=';

$url_product   = home_url( '/product/project-recovery-diagnostic/' );
$url_members   = home_url( '/membership-levels/' );
$url_contact   = home_url( '/contact/' );
$url_quiz      = home_url( '/development-quiz/' );
$url_board     = home_url( '/building-cost-board/' );
$url_theme     = home_url( '/thesis/' );
$url_recovery  = home_url( '/project-recovery/' );
?>

<style>
/* ═══════════════════════════════════════════════════════
   TRUST & VERIFY PAGE  (gv-tr- prefix)
   Self-contained — matches the design system tokens.
   ═══════════════════════════════════════════════════════ */
.gv-tr { background: var(--gv-cream); color: var(--gv-charcoal); }

.gv-tr-shell { max-width: 1120px; margin: 0 auto; padding: 3.5rem 1.25rem 4rem; }
.gv-tr-wide   { max-width: 1120px; }
.gv-tr-narrow { max-width: 800px; }

/* ── Section eyebrows + headings ── */
.gv-tr-ey {
	display: inline-flex; align-items: center; gap: 0.6rem;
	font-size: 0.66rem; letter-spacing: 0.24em; text-transform: uppercase;
	color: var(--gv-gold); font-weight: 600; margin-bottom: 1rem;
}
.gv-tr-ey::before { content: ''; width: 22px; height: 1px; background: var(--gv-gold); opacity: 0.7; }
.gv-tr-h2 {
	font-family: 'Playfair Display', serif; font-size: clamp(1.7rem, 3.2vw, 2.3rem);
	font-weight: 500; color: var(--gv-charcoal); margin: 0 0 1.1rem; line-height: 1.2;
}
.gv-tr-h2 em { font-style: italic; color: var(--gv-gold); }
.gv-tr-lede { font-size: 1.02rem; color: var(--gv-mist); line-height: 1.8; margin: 0 0 2.5rem; max-width: 720px; }

/* ── Meet the principal ── */
.gv-tr-meet { display: grid; grid-template-columns: 300px 1fr; gap: 3rem; align-items: start; }
.gv-tr-avatar {
	width: 300px; height: 340px; border-radius: 18px; overflow: hidden;
	background: linear-gradient(160deg, var(--gv-teal-deep), var(--gv-teal));
	display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.9rem;
	color: var(--gv-cream); text-align: center; padding: 1.5rem;
	border: 1px solid var(--gv-border-light);
}
.gv-tr-avatar-mono {
	width: 96px; height: 96px; border-radius: 50%;
	background: rgba(250,247,242,0.12); border: 1.5px solid var(--gv-gold);
	display: flex; align-items: center; justify-content: center;
	font-family: 'Playfair Display', serif; font-size: 2rem; color: var(--gv-gold);
}
.gv-tr-avatar p { font-size: 0.85rem; line-height: 1.6; color: rgba(250,247,242,0.75); margin: 0; }
.gv-tr-avatar .gv-tr-avatar-tag {
	font-size: 0.62rem; letter-spacing: 0.18em; text-transform: uppercase;
	color: var(--gv-gold); border: 1px solid rgba(201,169,110,0.4); padding: 0.3rem 0.8rem; border-radius: 999px;
}
.gv-tr-bio p { font-size: 0.98rem; color: var(--gv-charcoal); line-height: 1.85; margin: 0 0 1.15rem; }
.gv-tr-bio p.gv-tr-bio-mut { color: var(--gv-mist); }

/* ── Verify strip ── */
.gv-tr-verify {
	display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem;
	margin: 2.75rem 0 0; padding: 1.75rem; border-radius: 16px;
	background: #fff; border: 1px solid var(--gv-border-light);
}
.gv-tr-verify-item { display: flex; gap: 0.85rem; align-items: flex-start; }
.gv-tr-verify-ico {
	flex-shrink: 0; width: 40px; height: 40px; border-radius: 12px;
	background: #E8F5F0; color: var(--gv-teal);
	display: flex; align-items: center; justify-content: center;
}
.gv-tr-verify-ico svg { width: 19px; height: 19px; }
.gv-tr-verify-item b { display: block; font-size: 0.82rem; color: var(--gv-charcoal); margin-bottom: 0.15rem; }
.gv-tr-verify-item span { font-size: 0.78rem; color: var(--gv-mist); line-height: 1.5; display: block; }

/* ── What you get before you pay ── */
.gv-tr-see { background: var(--gv-ivory); border-radius: 18px; padding: 2.5rem; margin: 3.5rem 0 0; }
.gv-tr-see-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 2.5rem; align-items: center; }
.gv-tr-see-list { list-style: none; padding: 0; margin: 0 0 1.6rem; }
.gv-tr-see-list li {
	position: relative; padding: 0.55rem 0 0.55rem 2rem;
	font-size: 0.92rem; color: var(--gv-charcoal); line-height: 1.55;
}
.gv-tr-see-list li::before {
	content: '✓'; position: absolute; left: 0; top: 0.6rem;
	width: 22px; height: 22px; line-height: 22px; text-align: center;
	border-radius: 50%; background: rgba(201,169,110,0.18); color: #9a7b3f;
	font-size: 0.7rem; font-weight: 700;
}
.gv-tr-see-card {
	background: #fff; border: 1px solid var(--gv-border-light); border-radius: 14px;
	padding: 1.4rem 1.5rem; box-shadow: 0 2px 8px rgba(28,43,40,0.04);
}
.gv-tr-see-card .gv-tr-see-sample {
	font-size: 0.72rem; letter-spacing: 0.12em; text-transform: uppercase;
	color: var(--gv-teal); font-weight: 600; margin-bottom: 0.7rem;
}
.gv-tr-see-card p { font-size: 0.85rem; color: var(--gv-mist); line-height: 1.65; margin: 0; }
.gv-tr-btn-row { display: flex; gap: 0.8rem; flex-wrap: wrap; }

/* ── How we work ── */
.gv-tr-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin: 2rem 0 0; }
.gv-tr-step {
	background: #fff; border: 1px solid var(--gv-border-light); border-radius: 14px;
	padding: 1.5rem 1.4rem; position: relative;
}
.gv-tr-step-n {
	font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700;
	color: var(--gv-gold); margin-bottom: 0.5rem;
}
.gv-tr-step b { display: block; font-size: 0.92rem; color: var(--gv-charcoal); margin-bottom: 0.3rem; }
.gv-tr-step p { font-size: 0.83rem; color: var(--gv-mist); line-height: 1.6; margin: 0; }

/* ── Two payment paths ── */
.gv-tr-pay { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 2rem 0 0; }
.gv-tr-pay-card {
	border-radius: 18px; padding: 2rem 1.9rem; display: flex; flex-direction: column;
	border: 1px solid var(--gv-border-light); background: #fff;
}
.gv-tr-pay-card--staged {
	border: 2px solid var(--gv-gold); background: linear-gradient(180deg, #FDFAF4, #FFFDF8);
}
.gv-tr-pay-card h3 {
	font-family: 'Playfair Display', serif; font-size: 1.35rem; font-weight: 600;
	color: var(--gv-charcoal); margin: 0 0 0.5rem;
}
.gv-tr-pay-card p { font-size: 0.9rem; color: var(--gv-mist); line-height: 1.7; margin: 0 0 1.1rem; }
.gv-tr-pay-price { font-size: 0.82rem; color: var(--gv-teal); margin-bottom: 0.9rem; }
.gv-tr-pay-price b { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: var(--gv-teal-deep); font-weight: 700; }
.gv-tr-pay-card--staged .gv-tr-pay-price b { color: #9a7b3f; }
.gv-tr-pay-list { list-style: none; padding: 0; margin: 0 0 1.5rem; flex: 1; }
.gv-tr-pay-list li {
	position: relative; padding: 0.45rem 0 0.45rem 1.7rem;
	font-size: 0.87rem; color: var(--gv-charcoal); line-height: 1.5;
}
.gv-tr-pay-list li::before {
	content: '✓'; position: absolute; left: 0; top: 0.5rem;
	width: 20px; height: 20px; line-height: 20px; text-align: center;
	border-radius: 50%; background: rgba(13,92,82,0.08); color: var(--gv-teal);
	font-size: 0.65rem; font-weight: 700;
}
.gv-tr-pay-card--staged .gv-tr-pay-list li::before { background: rgba(201,169,110,0.18); color: #9a7b3f; }

/* ── Buttons ── */
.gv-tr-btn {
	display: inline-block; padding: 0.85rem 1.6rem; border-radius: 10px;
	background: var(--gv-teal); color: var(--gv-cream);
	font-family: 'Jost', sans-serif; font-size: 0.8rem; font-weight: 600;
	letter-spacing: 0.08em; text-transform: uppercase; text-decoration: none;
	text-align: center; transition: all .2s ease; border: none; cursor: pointer;
}
.gv-tr-btn:hover { background: var(--gv-teal-deep); color: var(--gv-cream); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(13,92,82,0.25); }
.gv-tr-btn--gold { background: var(--gv-gold); color: var(--gv-teal-deep); }
.gv-tr-btn--gold:hover { background: #b8955e; color: var(--gv-teal-deep); }
.gv-tr-btn--ghost { background: transparent; border: 1.5px solid rgba(13,92,82,0.35); color: var(--gv-teal); }
.gv-tr-btn--ghost:hover { background: #E8F5F0; color: var(--gv-teal); box-shadow: none; transform: none; }
.gv-tr-btn--wa { background: #25D366; color: #073421; }
.gv-tr-btn--wa:hover { background: #1fb856; color: #073421; }
.gv-tr-btn--block { display: block; width: 100%; }

/* ── Offer cards (recommended model — swap block) ── */
.gv-tr-offers { margin: 3.5rem 0 0; }
.gv-tr-offer-note {
	font-size: 0.82rem; color: var(--gv-mist); text-align: center; margin: 0 0 1.75rem;
}
.gv-tr-offer-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
.gv-tr-offer {
	background: #fff; border: 1px solid var(--gv-border-light); border-radius: 16px;
	padding: 1.75rem 1.5rem; display: flex; flex-direction: column; position: relative;
	transition: transform .25s ease, box-shadow .25s ease;
}
.gv-tr-offer:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(13,92,82,0.10); }
.gv-tr-offer--pop {
	border: 2px solid var(--gv-gold); background: linear-gradient(180deg, #FDFAF4, #FFFDF8);
}
.gv-tr-ribbon {
	position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
	background: var(--gv-gold); color: var(--gv-teal-deep);
	font-size: 0.58rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
	padding: 0.3rem 0.9rem; border-radius: 999px; white-space: nowrap;
	box-shadow: 0 4px 12px rgba(201,169,110,0.35);
}
.gv-tr-offer-name { font-size: 0.9rem; font-weight: 700; color: var(--gv-teal-deep); margin-bottom: 0.15rem; }
.gv-tr-offer-price { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; color: var(--gv-charcoal); margin-bottom: 0.2rem; }
.gv-tr-offer--pop .gv-tr-offer-price { color: #9a7b3f; }
.gv-tr-offer-sub { font-size: 0.75rem; color: var(--gv-mist); margin-bottom: 1rem; }
.gv-tr-offer-feats { list-style: none; padding: 0; margin: 0 0 1.4rem; flex: 1; }
.gv-tr-offer-feats li {
	position: relative; padding: 0.4rem 0 0.4rem 1.5rem;
	font-size: 0.8rem; color: var(--gv-charcoal); line-height: 1.45;
}
.gv-tr-offer-feats li::before {
	content: '✓'; position: absolute; left: 0; top: 0.45rem;
	width: 18px; height: 18px; line-height: 18px; text-align: center;
	border-radius: 50%; background: rgba(13,92,82,0.08); color: var(--gv-teal);
	font-size: 0.6rem; font-weight: 700;
}
.gv-tr-offer--pop .gv-tr-offer-feats li::before { background: rgba(201,169,110,0.18); color: #9a7b3f; }
.gv-tr-offer-btn { margin-top: 0.4rem; }

/* ── FAQ ── */
.gv-tr-faq { margin: 3.5rem 0 0; padding: 2.5rem 0 0; border-top: 1px solid var(--gv-border); }
.gv-tr-faq-title { text-align: center; }
.gv-faq-item {
	background: #fff; border: 1px solid var(--gv-border-light);
	border-radius: 12px; margin-bottom: 0.75rem; overflow: hidden;
	transition: box-shadow .2s ease;
}
.gv-faq-item.open { box-shadow: 0 8px 24px rgba(13,92,82,0.08); }
.gv-faq-q {
	display: flex; align-items: center; justify-content: space-between; gap: 1rem;
	width: 100%; padding: 1.1rem 1.4rem; background: transparent; border: none;
	font-family: 'Jost', sans-serif; font-size: 0.95rem; font-weight: 600;
	color: var(--gv-charcoal); cursor: pointer; text-align: left;
}
.gv-faq-q .gv-faq-chev {
	flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%;
	background: #E8F5F0; color: var(--gv-teal);
	display: flex; align-items: center; justify-content: center;
	font-size: 0.8rem; transition: transform .25s ease;
}
.gv-faq-item.open .gv-faq-chev { transform: rotate(180deg); }
.gv-faq-a { max-height: 0; overflow: hidden; transition: max-height .3s ease; }
.gv-faq-a-inner { padding: 0 1.4rem 1.2rem; font-size: 0.9rem; color: var(--gv-mist); line-height: 1.7; }

/* ── CTA band ── */
.gv-tr-cta {
	background: linear-gradient(120deg, var(--gv-teal-deep), var(--gv-teal));
	border-radius: 18px; color: var(--gv-cream);
	padding: 3rem 2.5rem; text-align: center; margin-top: 3.5rem; position: relative; overflow: hidden;
}
.gv-tr-cta::after {
	content: ''; position: absolute; inset: 0;
	background: radial-gradient(600px 200px at 85% -20%, rgba(201,169,110,0.35), transparent 60%);
	pointer-events: none;
}
.gv-tr-cta h3 { position: relative; font-family: 'Playfair Display', serif; font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 500; margin: 0 0 0.6rem; }
.gv-tr-cta p { position: relative; font-size: 0.95rem; color: rgba(250,247,242,0.75); line-height: 1.6; margin: 0 auto 1.75rem; max-width: 560px; }
.gv-tr-cta-actions { position: relative; display: flex; gap: 0.9rem; justify-content: center; flex-wrap: wrap; }
.gv-tr-cta-actions .gv-tr-btn { min-width: 250px; margin: 0; }

/* ── Responsive ── */
@media (max-width: 980px) {
	.gv-tr-meet { grid-template-columns: 1fr; gap: 1.75rem; }
	.gv-tr-avatar { width: 100%; height: auto; min-height: 240px; }
	.gv-tr-see-grid { grid-template-columns: 1fr; }
	.gv-tr-verify { grid-template-columns: repeat(2, 1fr); }
	.gv-tr-steps { grid-template-columns: 1fr; }
	.gv-tr-pay { grid-template-columns: 1fr; }
	.gv-tr-offer-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 560px) {
	.gv-tr-verify { grid-template-columns: 1fr; }
	.gv-tr-offer-grid { grid-template-columns: 1fr; }
	.gv-tr-see { padding: 1.75rem; }
	.gv-tr-shell { padding: 2.5rem 1rem 3rem; }
}
</style>

<main class="gv-tr">

<section class="page-hero">
	<div class="container">
		<div class="breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / Meet the Principal</div>
		<h1>Before You Pay Me, <em>Meet Me</em></h1>
		<p>You don't know me yet — so you shouldn't send money into the air. Here is exactly who you're dealing with, what you get before you pay, and the ways you can pay small small until you trust the work.</p>
	</div>
</section>

<div class="gv-tr-shell">

	<!-- Meet the principal -->
	<section class="gv-tr-meet gv-tr-wide">
		<div class="gv-tr-avatar">
			<div class="gv-tr-avatar-mono">GA</div>
			<span class="gv-tr-avatar-tag">The Principal</span>
			<p>Gideon Abochie &mdash; I personally review and run every engagement. No hand-offs, no ghost team.</p>
		</div>
		<div class="gv-tr-bio">
			<span class="gv-tr-ey">Who I am</span>
			<h2 class="gv-tr-h2">Your building is my project, <em>not my side income</em></h2>
			<p>I run Gabochie Design as a founder-built practice: your project is looked at by the same person who answers your call. When a build is stalled, when documents are missing, when the money has run dry on half a structure &mdash; you need someone who has walked the site, not someone reading from a script.</p>
			<p>Before any of my advice costs you money, you will have talked to me directly. That is the rule I work by, and it is also your first protection against every scam story doing the rounds in Ghana construction right now.</p>
			<p class="gv-tr-bio-mut">What I ask from you in return: come with your numbers and documents, and be ready to make decisions. My job is to make your money go further &mdash; yours is to act.</p>
		</div>
	</section>

	<!-- Verify me -->
	<section class="gv-tr-verify gv-tr-wide" aria-label="Ways to verify me">
		<div class="gv-tr-verify-item">
			<div class="gv-tr-verify-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 2l7 4v5c0 5.25-3.5 9.74-7 11-3.5-1.26-7-5.75-7-11V6l7-4z"/><path d="M9 12l2 2 4-4"/></svg></div>
			<div><b>Registered business</b><span>Registered in Ghana. Any formal invoice you need, you get.</span></div>
		</div>
		<div class="gv-tr-verify-item">
			<div class="gv-tr-verify-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
			<div><b>Physical office</b><span>P.O. Box SK 2125, Sakumono, Tema. I am reachable, not a phone number ghost.</span></div>
		</div>
		<div class="gv-tr-verify-item">
			<div class="gv-tr-verify-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
			<div><b>Real numbers</b><span>Call <a href="tel:<?php echo esc_attr( $phone_tel ); ?>"><?php echo esc_html( $phone ); ?></a> or WhatsApp me. A person answers.</span></div>
		</div>
		<div class="gv-tr-verify-item">
			<div class="gv-tr-verify-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="6" y1="9" x2="10" y2="9"/><line x1="6" y1="12" x2="14" y2="12"/></svg></div>
			<div><b>Receipt for everything</b><span>Every payment gets a receipt &mdash; online or manual &mdash; and staged work protects you first.</span></div>
		</div>
	</section>

	<!-- What you get before you pay -->
	<section class="gv-tr-see gv-tr-wide">
		<div class="gv-tr-see-grid">
			<div>
				<span class="gv-tr-ey">Before you pay</span>
				<h2 class="gv-tr-h2">See what the <em>Recovery Diagnostic</em> actually contains</h2>
				<p class="gv-tr-lede">You are not buying a promise &mdash; you are buying a written, shareable verdict on your project. Here is everything inside the &#8373;1,500 Recovery Diagnostic:</p>
				<ul class="gv-tr-see-list">
					<li>What exists on your site today &mdash; walls, structure, roof, level of completion</li>
					<li>What is at risk &mdash; unapproved starts, missing documents, structural red flags</li>
					<li>What it will honestly cost to finish &mdash; with today's Ghana material rates</li>
					<li>Your decision, written out &mdash; finish it, pause it, or sell it as-is</li>
					<li>A call after the report to walk you through it and answer your questions</li>
				</ul>
				<div class="gv-tr-btn-row">
					<a class="gv-tr-btn" href="<?php echo esc_url( $url_product ); ?>">See the Full Report</a>
					<a class="gv-tr-btn gv-tr-btn--ghost" href="<?php echo esc_url( $url_recovery ); ?>">Read Free Recovery Guides</a>
				</div>
			</div>
			<div class="gv-tr-see-card">
				<span class="gv-tr-see-sample">Sample verdict line &mdash; exactly how findings are written</span>
				<p>"Your structure is 62% complete. The concrete work is sound but rust set in on exposed steel since dry season. Estimated cost to finish: &#8373;845,000 at current market rates &mdash; with a 3-month window before the rust becomes a structural repair instead of a cosmetic one."</p>
			</div>
		</div>
	</section>

	<!-- How we work -->
	<section class="gv-tr-wide">
		<span class="gv-tr-ey">How we work</span>
		<h2 class="gv-tr-h2">Five steps, no <em>surprises</em></h2>
		<div class="gv-tr-steps">
			<div class="gv-tr-step">
				<div class="gv-tr-step-n">01</div>
				<b>Reach me directly</b>
				<p>Call or WhatsApp me. Tell me your story in your own words &mdash; stalled build, new land, existing documents.</p>
			</div>
			<div class="gv-tr-step">
				<div class="gv-tr-step-n">02</div>
				<b>I confirm scope &amp; quote</b>
				<p>I tell you exactly what I will do and exactly what it costs before any payment. Nothing changes later.</p>
			</div>
			<div class="gv-tr-step">
				<div class="gv-tr-step-n">03</div>
				<b>You choose how to pay</b>
				<p>Online in one go, or talk to me first and pay in stages. Your call &mdash; both are receipted.</p>
			</div>
			<div class="gv-tr-step">
				<div class="gv-tr-step-n">04</div>
				<b>I do the work</b>
				<p>You see progress as it happens, not after a wait. You approve each stage before the next one begins.</p>
			</div>
			<div class="gv-tr-step">
				<div class="gv-tr-step-n">05</div>
				<b>You approve, I deliver</b>
				<p>You get the written report or deliverable, a call to explain it, and the next step mapped out for you.</p>
			</div>
			<div class="gv-tr-step">
				<div class="gv-tr-step-n">06</div>
				<b>No ghosting, ever</b>
				<p>After delivery, you can still reach me. The project does not end at the handover &mdash; it ends when you decide.</p>
			</div>
		</div>
	</section>

	<!-- Two payment paths -->
	<section class="gv-tr-wide">
		<span class="gv-tr-ey">Your options</span>
		<h2 class="gv-tr-h2">Pay online, or <em>talk to me first</em> &mdash; then pay small small</h2>
		<div class="gv-tr-pay">
			<div class="gv-tr-pay-card">
				<h3>Option A &mdash; Pay online</h3>
				<p>If you already know what you want, pay the full amount online and the work starts immediately.</p>
				<div class="gv-tr-pay-price">The Recovery Diagnostic &mdash; <b>&#8373;1,500</b> one-time</div>
				<ul class="gv-tr-pay-list">
					<li>Instant checkout, instant receipt</li>
					<li>Full written report of every section</li>
					<li>Same delivery promise as every client</li>
				</ul>
				<a class="gv-tr-btn gv-tr-btn--block" href="<?php echo esc_url( $url_product ); ?>">Pay Online Now</a>
			</div>
			<div class="gv-tr-pay-card gv-tr-pay-card--staged">
				<h3>Option B &mdash; Talk first, then pay in stages</h3>
				<p>Not sure yet? Message me. We talk about your project, I confirm the scope, and you pay in two stages &mdash; you only pay the balance after you approve what you've seen.</p>
				<div class="gv-tr-pay-price">Recovery Diagnostic &mdash; <b>&#8373;750</b> start + <b>&#8373;750</b> on delivery</div>
				<ul class="gv-tr-pay-list">
					<li>You approve each stage before paying the next</li>
					<li>Discover me and verify me before you commit</li>
					<li>Receipted at every stage, no cash-in-the-air</li>
				</ul>
				<a class="gv-tr-btn gv-tr-btn--gold gv-tr-btn--block" href="<?php echo esc_url( $wa_base . $wa_diag ); ?>" target="_blank" rel="noopener">Message Me on WhatsApp</a>
			</div>
		</div>
	</section>

	<!-- Offer cards — recommended model -->
	<section class="gv-tr-offers gv-tr-wide">
		<span class="gv-tr-ey">Ways to work with me</span>
		<h2 class="gv-tr-h2">Pricing that matches <em>how Ghana builds</em></h2>
		<p class="gv-tr-offer-note">No monthly subscriptions that quietly renew &mdash; pay for what you need, when you need it, small small.</p>
		<div class="gv-tr-offer-grid">
			<div class="gv-tr-offer">
				<div class="gv-tr-offer-name">Free Discovery</div>
				<div class="gv-tr-offer-price">&#8373;0</div>
				<div class="gv-tr-offer-sub">Start free. Always.</div>
				<ul class="gv-tr-offer-feats">
					<li>Development Readiness Quiz</li>
					<li>Build Cost Calculator estimate</li>
					<li>Free recovery guides &amp; price board</li>
					<li>Community Q&amp;A access</li>
				</ul>
				<a class="gv-tr-btn gv-tr-btn--ghost gv-tr-offer-btn" href="<?php echo esc_url( $url_quiz ); ?>">Start Free</a>
			</div>
			<div class="gv-tr-offer gv-tr-offer--pop">
				<div class="gv-tr-ribbon">Popular</div>
				<div class="gv-tr-offer-name">Recovery Diagnostic</div>
				<div class="gv-tr-offer-price">&#8373;1,500</div>
				<div class="gv-tr-offer-sub">Staged 50/50 if you talk to me first</div>
				<ul class="gv-tr-offer-feats">
					<li>Full written review of your project</li>
					<li>Cost-to-finish with today's rates</li>
					<li>Finish, pause, or sell verdict</li>
					<li>Follow-up call to walk you through</li>
				</ul>
				<a class="gv-tr-btn gv-tr-btn--gold gv-tr-offer-btn" href="<?php echo esc_url( $url_product ); ?>">See the Report First</a>
			</div>
			<div class="gv-tr-offer">
				<div class="gv-tr-offer-name">Annual Advisory</div>
				<div class="gv-tr-offer-price">&#8373;2,000/yr</div>
				<div class="gv-tr-offer-sub">Four quarterly deliverables &mdash; not a monthly renewal</div>
				<ul class="gv-tr-offer-feats">
					<li>Quarterly cost &amp; price-board review</li>
					<li>Project check-in every quarter</li>
					<li>Priority WhatsApp line to me</li>
					<li>Annual roadmap for your build</li>
				</ul>
				<a class="gv-tr-btn gv-tr-btn--wa gv-tr-offer-btn" href="<?php echo esc_url( $wa_base . $wa_advisory ); ?>" target="_blank" rel="noopener">Ask Me on WhatsApp</a>
			</div>
			<div class="gv-tr-offer">
				<div class="gv-tr-offer-name">Project Packages</div>
				<div class="gv-tr-offer-price">Fixed fee</div>
				<div class="gv-tr-offer-sub">Quoted per project, paid in milestones</div>
				<ul class="gv-tr-offer-feats">
					<li>Design &amp; permits &mdash; 30%</li>
					<li>Build-cost engineering &mdash; 40%</li>
					<li>Procurement &amp; delivery &mdash; 30%</li>
					<li>Milestone payments, stage approval</li>
				</ul>
				<a class="gv-tr-btn gv-tr-btn--ghost gv-tr-offer-btn" href="<?php echo esc_url( $wa_base . $wa_build ); ?>" target="_blank" rel="noopener">Get a Quote</a>
			</div>
		</div>
	</section>

	<!-- FAQ -->
	<section class="gv-tr-faq gv-tr-narrow">
		<h2 class="gv-tr-h2 gv-tr-faq-title">Before you ask &mdash; the <em>usual worries</em></h2>
		<div class="gv-faq-item">
			<button class="gv-faq-q" type="button"><span>How do I know you're not a scammer?</span><span class="gv-faq-chev">▾</span></button>
			<div class="gv-faq-a"><div class="gv-faq-a-inner">Verify me the same way you would verify any contractor: I answer my own phone and WhatsApp, I have a physical office in Sakumono, Tema (P.O. Box SK 2125), every payment gets a receipt, and nothing of yours starts until you have seen the scope in writing. You can also read the free recovery guides and the thesis I publish under my own name before spending anything.</div></div>
		</div>
		<div class="gv-faq-item">
			<button class="gv-faq-q" type="button"><span>Why would I pay &#8373;1,500 for a report?</span><span class="gv-faq-chev">▾</span></button>
			<div class="gv-faq-a"><div class="gv-faq-a-inner">Because a wrong decision costs far more. If your project is half-built, guessing wrong about what to finish or sell wastes hundreds of thousands of cedis. The diagnostic is a written, shareable verdict that tells you exactly what you have, what is at risk, and what finishing honestly costs &mdash; built on current Ghana material rates, not figures from the internet.</div></div>
		</div>
		<div class="gv-faq-item">
			<button class="gv-faq-q" type="button"><span>Do I have to pay before we even talk?</span><span class="gv-faq-chev">▾</span></button>
			<div class="gv-faq-a"><div class="gv-faq-a-inner">No. Ever. You can message me on WhatsApp first, explain your project, and I will confirm scope and price before you pay a cedi. If you choose the staged option, you pay &#8373;750 to start and the remaining &#8373;750 only after you approve what you've seen.</div></div>
		</div>
		<div class="gv-faq-item">
			<button class="gv-faq-q" type="button"><span>What if I have doubts after payment?</span><span class="gv-faq-chev">▾</span></button>
			<div class="gv-faq-a"><div class="gv-faq-a-inner">Reach me directly &mdash; I answer. The refund policy on every paid service is published at the bottom of every page. I would rather refund one client honestly than keep one unhappy customer.</div></div>
		</div>
		<div class="gv-faq-item">
			<button class="gv-faq-q" type="button"><span>Can I pay in dollars from abroad?</span><span class="gv-faq-chev">▾</span></button>
			<div class="gv-faq-a"><div class="gv-faq-a-inner">Yes &mdash; diaspora clients are a big part of this practice. The price is fixed in Ghana cedis, but we can agree on an equivalent foreign-currency payment and send you a receipted invoice that states the cedi value and the exchange rate used, so there is never a dispute about how much you paid.</div></div>
		</div>
	</section>

	<!-- CTA -->
	<section class="gv-tr-cta gv-tr-wide">
		<h3>Talk to me before you pay me &mdash; it's free, and it's how I work.</h3>
		<p>Message me on WhatsApp and tell me about your project. Even if you never buy anything, you'll know exactly where you stand.</p>
		<div class="gv-tr-cta-actions">
			<a class="gv-tr-btn gv-tr-btn--wa" href="<?php echo esc_url( $wa_base . $wa_hello ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
			<a class="gv-tr-btn gv-tr-btn--ghost" href="tel:<?php echo esc_attr( $phone_tel ); ?>">Call <?php echo esc_html( $phone ); ?></a>
		</div>
	</section>

</div>
</main>

<script>
(function () {
	var items = document.querySelectorAll('.gv-faq-item');
	items.forEach(function (it) {
		var a = it.querySelector('.gv-faq-a');
		it.querySelector('.gv-faq-q').addEventListener('click', function () {
			var open = it.classList.contains('open');
			items.forEach(function (o) {
				o.classList.remove('open');
				o.querySelector('.gv-faq-a').style.maxHeight = null;
			});
			if (!open) {
				it.classList.add('open');
				a.style.maxHeight = a.scrollHeight + 'px';
			}
		});
	});
})();
</script>

<?php get_footer(); ?>