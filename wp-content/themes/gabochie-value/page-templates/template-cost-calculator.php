<?php
/**
 * Template Name: Building Cost Calculator
 * Professional cost calculator page — hero, estimator shell, methodology, CTA.
 * @package Gabochie_Value
 */

get_header();
?>

<style>
/* ══════════════════════════════════════════════
   BUILDING COST CALCULATOR PAGE  (gv-calc- prefix)
   Scoped restyle of the .bcgh-rcv2 estimator + page layout
   ══════════════════════════════════════════════ */
:root {
    --gv-teal:#0D5C52; --gv-teal-deep:#0A4840; --gv-gold:#C9A96E;
    --gv-cream:#FAF7F2; --gv-charcoal:#1C2B28;
    --gv-mist:#6B7C78; --gv-border:#E2E8E5; --gv-border-light:#EFE9DF;
}

.gv-calc { background: var(--gv-cream); }

/* ── Intro ── */
.gv-calc-intro { max-width: 760px; margin: 0 auto 2.5rem; text-align: center; }
.gv-calc-intro h2 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.1rem); font-weight: 500;
    color: var(--gv-charcoal); margin: 0 0 0.75rem;
}
.gv-calc-intro h2 em { font-style: italic; color: var(--gv-gold); }
.gv-calc-intro p { font-size: 1rem; color: var(--gv-mist); line-height: 1.7; margin: 0; }
.gv-calc-intro .gv-calc-tags { margin-top: 1.25rem; display: flex; gap: 0.6rem; justify-content: center; flex-wrap: wrap; }
.gv-calc-tag {
    font-size: 0.7rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase;
    padding: 0.4rem 0.9rem; border-radius: 999px;
    background: #E8F5F0; color: var(--gv-teal); border: 1px solid rgba(13,92,82,0.15);
}
.gv-calc-tag--gold { background: rgba(201,169,110,0.14); color: #9a7b3f; border-color: rgba(201,169,110,0.3); }

/* ── Estimator shell ── */
.gv-calc-shell { max-width: 1040px; margin: 0 auto 4rem; }
.gv-calc-shell .bcgh-rcv2 {
    max-width: 100%; margin: 0;
    border: 1px solid rgba(201,169,110,0.4);
    border-radius: 18px;
    box-shadow: 0 24px 60px rgba(13,92,82,0.12);
    background: #FDFCF9;
}
.gv-calc-shell .bcgh-rcv2-header {
    padding: 1.35rem 1.6rem;
    background: linear-gradient(120deg, #08302A, #0D5C52 70%, #11685D);
}
.gv-calc-shell .bcgh-rcv2-h-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.15rem; font-weight: 600; letter-spacing: 0.01em;
}
.gv-calc-shell .bcgh-rcv2-h-sub { font-size: 11.5px; opacity: 0.72; letter-spacing: 0.03em; }
.gv-calc-shell .bcgh-rcv2-badge {
    background: var(--gv-gold); color: #08302A;
    padding: 5px 14px; font-size: 9.5px; letter-spacing: 0.18em;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.gv-calc-shell .bcgh-rcv2-body { min-height: 0; max-height: none; }
.gv-calc-shell .bcgh-rcv2-fields { width: 372px; padding: 1.6rem 1.5rem; gap: 11px; border-right: 1px solid #E3ECE9; }
.gv-calc-shell .bcgh-rcv2-row { align-items: flex-end; gap: 9px; }
.gv-calc-shell .bcgh-rcv2-f-sm { flex: 0 0 96px; }
.gv-calc-shell .bcgh-rcv2-inp {
    padding: 10px 12px; font-size: 13.5px; border-radius: 8px;
    border: 1px solid #D9E4E0; background: #FBF9F4;
}
.gv-calc-shell .bcgh-rcv2-inp:focus { border-color: var(--gv-gold); box-shadow: 0 0 0 3px rgba(201,169,110,0.18); }
.gv-calc-shell .bcgh-rcv2-lbl {
    font-size: 10px; font-weight: 600; letter-spacing: 0.07em;
    color: #56706A; margin-bottom: 5px;
}
.gv-calc-shell .bcgh-rcv2-unit { right: 12px; font-size: 11.5px; color: #6B8A84; }
.gv-calc-shell .bcgh-rcv2-cat-toggle { border-radius: 10px; overflow: hidden; margin-bottom: 6px; }
.gv-calc-shell .bcgh-rcv2-cat-btn { padding: 10px 8px; font-size: 12.5px; }
.gv-calc-shell .bcgh-rcv2-adv-h { font-size: 12.5px; padding: 6px 0; }
.gv-calc-shell .bcgh-rcv2-adv-checks { grid-template-columns: 1fr 1fr; gap: 4px; }
.gv-calc-shell .bcgh-rcv2-cb { font-size: 12.5px; padding: 4px 0; }
.gv-calc-shell .bcgh-rcv2-calc {
    padding: 13px; border-radius: 10px; font-size: 14.5px; font-weight: 600;
    margin-top: 10px;
}
.gv-calc-shell .bcgh-rcv2-calc:hover { box-shadow: 0 6px 18px rgba(13,92,82,0.3); }
.gv-calc-shell .bcgh-rcv2-results { padding: 1.6rem 1.75rem; overflow-y: auto; }
.gv-calc-shell .bcgh-rcv2-r-top { gap: 16px; margin-bottom: 14px; }
.gv-calc-shell .bcgh-rcv2-r-label { font-size: 11px; margin-bottom: 4px; color: #56706A; }
.gv-calc-shell .bcgh-rcv2-r-amount { font-size: 2rem; color: var(--gv-teal-deep); }
.gv-calc-shell .bcgh-rcv2-r-usd { font-size: 12.5px; }
.gv-calc-shell .bcgh-rcv2-r-stat {
    background: #F3FAF7; border: 1px solid #DCECE7;
    border-radius: 10px; padding: 9px 13px; min-width: 84px;
}
.gv-calc-shell .bcgh-rcv2-rs-l { font-size: 9.5px; color: var(--gv-teal); }
.gv-calc-shell .bcgh-rcv2-rs-v { font-size: 14.5px; }
.gv-calc-shell .bcgh-rcv2-brow { grid-template-columns: 130px 1fr 74px; gap: 10px; margin-bottom: 6px; }
.gv-calc-shell .bcgh-rcv2-bl { font-size: 11.5px; }
.gv-calc-shell .bcgh-rcv2-bbar { height: 7px; border-radius: 4px; background: #ECF4F1; }
.gv-calc-shell .bcgh-rcv2-bfill { background: linear-gradient(90deg, var(--gv-teal), var(--gv-gold)); }
.gv-calc-shell .bcgh-rcv2-srow { font-size: 11.5px; padding: 4px 0; }
.gv-calc-shell .bcgh-rcv2-stotal { font-size: 15px; border-top: 2px solid var(--gv-gold); }
.gv-calc-shell .bcgh-rcv2-r-foot { font-size: 10.5px; }
@media (max-width: 700px) {
    .gv-calc-shell .bcgh-rcv2-fields { width: auto; }
    .gv-calc-shell .bcgh-rcv2-results { overflow-y: visible; }
}

/* ── How it works ── */
.gv-calc-how { max-width: 1040px; margin: 0 auto 4rem; }
.gv-calc-how-head { text-align: center; margin-bottom: 2.25rem; }
.gv-calc-how-head h3 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 500;
    color: var(--gv-charcoal); margin: 0 0 0.6rem;
}
.gv-calc-how-head h3 em { font-style: italic; color: var(--gv-gold); }
.gv-calc-how-head p { color: var(--gv-mist); font-size: 0.95rem; margin: 0; }
.gv-calc-how-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
.gv-calc-step {
    background: #fff; border: 1px solid var(--gv-border-light); border-radius: 14px;
    padding: 1.75rem 1.5rem; position: relative;
    transition: transform .25s ease, box-shadow .25s ease;
}
.gv-calc-step:hover { transform: translateY(-4px); box-shadow: 0 14px 34px rgba(13,92,82,0.09); }
.gv-calc-step-n {
    width: 38px; height: 38px; border-radius: 10px;
    background: var(--gv-teal); color: var(--gv-cream);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 600;
    margin-bottom: 1rem;
}
.gv-calc-step h4 {
    font-family: 'Playfair Display', serif; font-size: 1.05rem; font-weight: 600;
    color: var(--gv-charcoal); margin: 0 0 0.4rem;
}
.gv-calc-step p { font-size: 0.86rem; color: var(--gv-mist); line-height: 1.6; margin: 0; }

/* ── Transparency note ── */
.gv-calc-note {
    max-width: 1040px; margin: 0 auto 4rem;
    background: #FFFDF8; border: 1px solid rgba(201,169,110,0.3);
    border-radius: 14px; padding: 1.4rem 1.75rem;
    display: flex; gap: 1rem; align-items: flex-start;
}
.gv-calc-note svg { width: 22px; height: 22px; color: var(--gv-gold); flex-shrink: 0; margin-top: 2px; }
.gv-calc-note h4 { font-size: 0.95rem; color: var(--gv-charcoal); margin: 0 0 0.3rem; }
.gv-calc-note p { font-size: 0.85rem; color: var(--gv-mist); line-height: 1.7; margin: 0; }
.gv-calc-note p strong { color: var(--gv-teal); }

/* ── CTA band (reuse gv-mem-cta language) ── */
.gv-calc-cta {
    max-width: 1000px; margin: 0 auto;
    background: linear-gradient(120deg, var(--gv-teal-deep), var(--gv-teal));
    border-radius: 18px; color: var(--gv-cream);
    padding: 3rem 2.5rem; text-align: center; position: relative; overflow: hidden;
}
.gv-calc-cta::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(600px 200px at 15% -20%, rgba(201,169,110,0.35), transparent 60%);
    pointer-events: none;
}
.gv-calc-cta h3 {
    position: relative; font-family: 'Playfair Display', serif;
    font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 500; margin: 0 0 0.6rem;
}
.gv-calc-cta p { position: relative; font-size: 0.95rem; color: rgba(250,247,242,0.78); line-height: 1.6; margin: 0 auto 1.75rem; max-width: 560px; }
.gv-calc-cta-actions { position: relative; display: flex; gap: 0.9rem; justify-content: center; flex-wrap: wrap; }
.gv-calc-cta-btn {
    display: inline-block; padding: 0.95rem 2rem; border-radius: 10px;
    background: var(--gv-gold); color: var(--gv-teal-deep);
    font-family: 'Jost', sans-serif; font-size: 0.84rem; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase; text-decoration: none;
    transition: all .2s ease;
}
.gv-calc-cta-btn:hover { background: #b8955e; color: var(--gv-teal-deep); transform: translateY(-1px); }
.gv-calc-cta-btn--ghost { background: transparent; border: 1.5px solid rgba(250,247,242,0.4); color: var(--gv-cream); }
.gv-calc-cta-btn--ghost:hover { background: rgba(250,247,242,0.08); color: var(--gv-cream); }

@media (max-width: 900px) {
    .gv-calc-how-grid { grid-template-columns: 1fr; max-width: 480px; margin: 0 auto; }
}
</style>

<main class="gv-calc">

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Building Cost Calculator</div>
        <h1>Building <em>Cost Calculator</em></h1>
        <p>Estimate your construction cost in Ghana in under a minute — residential or commercial, with live market rates.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container">

        <div class="gv-calc-intro">
            <h2>Know Your Numbers <em>Before You Build</em></h2>
            <p>Most projects stall because the real cost never gets put on the table early enough. Get a realistic, transparent estimate — then take the next step with confidence.</p>
            <div class="gv-calc-tags">
                <span class="gv-calc-tag">Residential &amp; Commercial</span>
                <span class="gv-calc-tag gv-calc-tag--gold">Ghana Market Rates</span>
                <span class="gv-calc-tag">Instant Results</span>
            </div>
        </div>

        <div class="gv-calc-shell">
            <?php echo do_shortcode('[buildcostgh_rc]'); ?>
        </div>

        <!-- How it works -->
        <div class="gv-calc-how">
            <div class="gv-calc-how-head">
                <h3>How Your <em>Estimate</em> Is Built</h3>
                <p>Transparent methodology — no vague quotes, no surprises.</p>
            </div>
            <div class="gv-calc-how-grid">
                <div class="gv-calc-step">
                    <div class="gv-calc-step-n">1</div>
                    <h4>Describe Your Project</h4>
                    <p>Tell us the type of building, location, floor area, and finish standard. Land it close — you can fine-tune anytime.</p>
                </div>
                <div class="gv-calc-step">
                    <div class="gv-calc-step-n">2</div>
                    <h4>We Apply Live Rates</h4>
                    <p>We calculate against current Ghanaian construction rates — adjusted for region, storeys, roof, amenities, and professional fees.</p>
                </div>
                <div class="gv-calc-step">
                    <div class="gv-calc-step-n">3</div>
                    <h4>Get Your Breakdown</h4>
                    <p>Review a full cost breakdown by construction element, plus a contingency allowance — then talk to us to firm it up.</p>
                </div>
            </div>
        </div>

        <!-- Transparency -->
        <div class="gv-calc-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
            <div>
                <h4>Estimate vs. Professional Quote</h4>
                <p>This is an <strong>advisory estimate</strong> based on current Ghanaian market data, not a formal quotation. Materials and labour vary by site and specification — for a guaranteed price, pair this with a professional assessment from our team.</p>
            </div>
        </div>

        <!-- CTA -->
        <div class="gv-calc-cta">
            <h3>What's Next for Your Project?</h3>
            <p>Your number is only the beginning. Take the readiness quiz or get a professional verified estimate — free.</p>
            <div class="gv-calc-cta-actions">
                <a class="gv-calc-cta-btn" href="<?php echo esc_url(home_url('/login/?action=register')); ?>">Get A Verified Estimate — Free</a>
                <a class="gv-calc-cta-btn gv-calc-cta-btn--ghost" href="<?php echo esc_url(home_url('/development-quiz/')); ?>">Take the Readiness Quiz</a>
            </div>
        </div>

    </div>
</section>

</main>

<?php get_footer(); ?>