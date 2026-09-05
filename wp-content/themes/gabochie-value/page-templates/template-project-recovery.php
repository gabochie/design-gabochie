<?php
/**
 * Template Name: Project Recovery
 *
 * Dedicated recovery funnel: a short diagnostic mini-flow (blockers → stage →
 * spend → goal → contact) that returns an honest, deterministic readout of
 * whether a stuck project is worth finishing — then routes the lead into the
 * hub (user + client_project + Free Lead + gabochie/lead/captured event).
 *
 * @package Gabochie_Value
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$whatsapp = gabochie_value_whatsapp();
$wa_base  = 'https://wa.me/' . $whatsapp . '?text=';

get_header();
?>

<style>
/* Scoped recovery-wizard styles on top of the onboarding.css layout. */
.gv-rec-err { color:#b02323; font-size:0.8rem; margin-top:0.7rem; font-family:'Jost',sans-serif; }
.gv-rec-result { padding:0.5rem 0 0.2rem; }
.gv-rec-tier { display:inline-block; padding:0.35rem 0.9rem; border-radius:999px; font-size:0.72rem; letter-spacing:0.13em; text-transform:uppercase; font-weight:600; color:#06350F; background:var(--gold,#b08d3f); font-family:'Jost',sans-serif; }
.gv-rec-tier.gv-t-finish { background:#2ea368; color:#fff; }
.gv-rec-tier.gv-t-assess { background:#e0a93b; color:#06350F; }
.gv-rec-tier.gv-t-reconsider { background:#c98a2e; color:#fff; }
.gv-rec-result h2 { font-size:1.7rem; margin:0.9rem 0 0.5rem; line-height:1.25; }
.gv-rec-result p { color:var(--mist,#5f6359); font-size:0.95rem; line-height:1.65; margin-bottom:1rem; }
.gv-rec-received { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.6rem; margin:1.1rem 0 1.4rem; }
.gv-rec-received div { background:var(--soft,#f2efe9); border:1px solid var(--border-light,#e5e2da); border-radius:10px; padding:0.7rem 0.9rem; }
.gv-rec-received span { display:block; font-size:0.68rem; letter-spacing:0.1em; text-transform:uppercase; color:var(--mist,#5f6359); margin-bottom:0.2rem; font-family:'Jost',sans-serif; }
.gv-rec-received strong { font-size:0.85rem; font-weight:600; line-height:1.35; display:block; }
.gv-rec-ctas { display:flex; flex-wrap:wrap; gap:0.8rem; margin:1.2rem 0 0.6rem; }
.gv-rec-ctas .btn-solid, .gv-rec-ctas .btn-ghost { min-height:44px; }
.gv-rec-note { font-size:0.78rem; color:var(--mist,#5f6359); margin-top:0.9rem; }
.gv-rec-hp { position:absolute !important; left:-9999px !important; top:-9999px !important; width:1px !important; height:1px !important; opacity:0 !important; }
@media (max-width:600px){
	.gv-rec-received { grid-template-columns:1fr 1fr; }
}
</style>

<main id="main" class="onb-main">

<!-- ========== HERO ========== -->
<section class="onb-hero">
	<div class="container">
	<div class="breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / Project Recovery</div>
	<h1 class="onb-hero-h1">Stuck project? <em>Don't start over.</em> Get a diagnosis.</h1>
	<p class="onb-hero-sub">Answer a few questions and we'll tell you, honestly, whether your project is worth finishing — what's wrong, what it costs to fix, and whether continuing beats starting over.</p>
	</div>
</section>

<!-- ========== WIZARD ========== -->
<section class="onb-wizard" id="gvRecWizard">
	<div class="container">
	<div class="onb-card">

		<!-- Progress bar (mirrors onboarding stepper) -->
		<div class="onb-progress" id="gvRecProgress" aria-hidden="true">
			<div class="onb-p-step active" data-p="1"><span class="op-num">1</span><span class="op-label">Blockers</span></div>
			<span class="op-dash"></span>
			<div class="onb-p-step" data-p="2"><span class="op-num">2</span><span class="op-label">Stage</span></div>
			<span class="op-dash"></span>
			<div class="onb-p-step" data-p="3"><span class="op-num">3</span><span class="op-label">Spend</span></div>
			<span class="op-dash"></span>
			<div class="onb-p-step" data-p="4"><span class="op-num">4</span><span class="op-label">Goal</span></div>
			<span class="op-dash"></span>
			<div class="onb-p-step" data-p="5"><span class="op-num">5</span><span class="op-label">Contact</span></div>
		</div>

		<form id="gvRecForm" novalidate>
		<div class="onb-body">
		<input type="text" name="gabochie_website" class="gv-rec-hp" tabindex="-1" autocomplete="off" aria-hidden="true" value="" />

		<!-- ===== STEP 1: Blockers ===== -->
		<div class="onb-step gv-rec-step" data-step="1">
			<h2 class="onb-q">What is blocking your <em>project?</em></h2>
			<p class="onb-hint">Select all that apply — there's no wrong answer.</p>
			<div class="onb-chip-grid gv-rec-chip2" id="gvBBlockers">
			<button type="button" class="onb-chip" data-val="design">Design has stalled</button>
			<button type="button" class="onb-chip" data-val="budget">Budget exploded / funds ran out</button>
			<button type="button" class="onb-chip" data-val="contractor">Contractor problems</button>
			<button type="button" class="onb-chip" data-val="land">Land / title dispute</button>
			<button type="button" class="onb-chip" data-val="permits">Permits / approvals stuck</button>
			<button type="button" class="onb-chip" data-val="scope">Unclear scope</button>
			<button type="button" class="onb-chip" data-val="abandoned">The site was abandoned</button>
			<button type="button" class="onb-chip" data-val="visibility">No visibility from abroad</button>
			</div>
			<div class="onb-field onb-field-full" style="margin-top:1rem">
			<label>Tell us what happened (optional)</label>
			<textarea id="gvRecDescInput" rows="3" placeholder="Describe the stall, who's involved, any disputes&hellip;"></textarea>
			</div>
		</div>

		<!-- ===== STEP 2: Stage reached ===== -->
		<div class="onb-step gv-rec-step" data-step="2">
			<h2 class="onb-q">How far did the <em>build</em> get?</h2>
			<p class="onb-hint">Choose the furthest stage reached.</p>
			<div class="onb-chip-grid cols-2 gv-rec-chip2" id="gvBStage">
			<button type="button" class="onb-chip" data-val="design">Stalled at design / nothing built</button>
			<button type="button" class="onb-chip" data-val="foundation">Foundation built</button>
			<button type="button" class="onb-chip" data-val="walls">Walls up</button>
			<button type="button" class="onb-chip" data-val="lintel">Lintel / roof level</button>
			<button type="button" class="onb-chip" data-val="rough_in">Rough-in started (services)</button>
			<button type="button" class="onb-chip" data-val="finishing">Finishing started</button>
			</div>
		</div>

		<!-- ===== STEP 3: Spend ===== -->
		<div class="onb-step gv-rec-step" data-step="3">
			<h2 class="onb-q">What has the project <em>cost</em> so far?</h2>
			<p class="onb-hint">An approximate total is fine — it stays private.</p>
			<div class="onb-chip-grid cols-2 gv-rec-chip2" id="gvBSpend">
			<button type="button" class="onb-chip" data-val="under_100k">Under GHS 100,000</button>
			<button type="button" class="onb-chip" data-val="100k_500k">GHS 100K – 500K</button>
			<button type="button" class="onb-chip" data-val="500k_1m">GHS 500K – 1M</button>
			<button type="button" class="onb-chip" data-val="1m_3m">GHS 1M – 3M</button>
			<button type="button" class="onb-chip" data-val="3m_plus">GHS 3M+</button>
			<button type="button" class="onb-chip" data-val="not_sure">Not sure</button>
			</div>
		</div>

		<!-- ===== STEP 4: Goal ===== -->
		<div class="onb-step gv-rec-step" data-step="4">
			<h2 class="onb-q">What do you want to <em>do now?</em></h2>
			<p class="onb-hint">Be honest — it changes the recommendation.</p>
			<div class="onb-chip-grid cols-2 gv-rec-chip2" id="gvBGoal">
			<button type="button" class="onb-chip" data-val="finish">Finish the build (or vacate / sell)</button>
			<button type="button" class="onb-chip" data-val="restart">Reset / restart properly</button>
			<button type="button" class="onb-chip" data-val="repair">Fix defects on what exists</button>
			<button type="button" class="onb-chip" data-val="assess">Get an honest assessment first</button>
			</div>
		</div>

		<!-- ===== STEP 5: Contact ===== -->
		<div class="onb-step gv-rec-step" data-step="5">
			<h2 class="onb-q">Where do we send your <em>diagnosis?</em></h2>
			<p class="onb-hint">Free. No card required. Your diagnosis lands in your inbox.</p>
			<div class="onb-fields onb-account-fields">
			<div class="onb-field">
				<label for="gvRecName">Full name <span class="req">*</span></label>
				<input type="text" id="gvRecName" name="name" autocomplete="name" required placeholder="Your name">
			</div>
			<div class="onb-field">
				<label for="gvRecEmail">Email address <span class="req">*</span></label>
				<input type="email" id="gvRecEmail" name="email" autocomplete="email" required placeholder="you@example.com">
			</div>
			<div class="onb-field">
				<label for="gvRecPhone">Phone (optional)</label>
				<input type="tel" id="gvRecPhone" name="phone" autocomplete="tel" placeholder="+233 ...">
			</div>
			<div class="onb-field">
				<label>Where are you based? <span class="req">*</span></label>
				<div class="onb-chip-grid cols-3">
				<button type="button" class="onb-chip" data-field="location_based" data-val="in_ghana">In Ghana</button>
				<button type="button" class="onb-chip" data-field="location_based" data-val="diaspora">Abroad (Diaspora)</button>
				<button type="button" class="onb-chip" data-field="location_based" data-val="prefer_not">Prefer not to say</button>
				</div>
			</div>
			</div>
		</div>
		</div><!-- /onb-body -->

		<!-- ===== NAV ===== -->
		<div class="onb-nav" id="gvRecNav">
			<button class="btn-ghost" id="gvRecBack" type="button">Back</button>
			<span class="onb-err gv-rec-err" id="gvRecErr"></span>
			<button class="btn-solid" id="gvRecNext" type="button">Continue</button>
		</div>
		</form>

		<!-- ===== RESULT ===== -->
		<div class="gv-rec-result" id="gvRecResult" style="display:none">
		<span class="gv-rec-tier" id="gvRecTier"></span>
		<h2 id="gvRecHeadline"></h2>
		<p id="gvRecSummary"></p>
		<div class="gv-rec-received" id="gvRecReceived"></div>
		<p>Your diagnosis reference: <strong id="gvRecRef"></strong> — it's been saved to your project profile.</p>
		<div class="gv-rec-ctas">
			<a class="btn-solid" href="<?php echo esc_url( gabochie_value_recovery_product_url() ); ?>">Book the Recovery Diagnostic</a>
			<a class="btn-ghost" href="<?php echo esc_url( home_url( '/membership-levels/' ) ); ?>">See Membership Plans</a>
		</div>
		<p class="gv-rec-note">Most stuck projects can be rescued &mdash; and often finished for less than starting over. The Recovery Diagnostic is a paid consultation: fixed fee, clear scope, no obligation to continue.</p>
		</div>

	</div><!-- /onb-card -->
	</div>
</section>

</main>

<?php
/* ============================================
	Recovery wizard JS (self-contained; REST, no nonce)
	============================================ */
?>
<script>
(function () {
	'use strict';
	var ENDPOINT = '<?php echo esc_url_raw( home_url( '/wp-json/gabochie/v1/recovery-diagnose' ) ); ?>';
	var STEP_MAX = 5;
	var state = {
	blockers: [],
	stage: '', spend: '', goal: '',
	description: '', name: '', email: '', phone: '', location_based: ''
	};

	var $  = function (s) { return document.querySelector(s); };
	var $$ = function (s) { return Array.prototype.slice.call(document.querySelectorAll(s)); };

	var progress = $('#gvRecProgress');
	var err      = $('#gvRecErr');
	var nav      = $('#gvRecNav');
	var form     = $('#gvRecForm');
	var result   = $('#gvRecResult');
	var current  = 1;

	function refresh() {
	var pSteps = $$('.onb-p-step', progress);
	pSteps.forEach(function (p) {
		var idx = +p.getAttribute('data-p');
		p.classList.toggle('active', idx === current);
		p.classList.toggle('done', idx < current);
	});
	err.textContent = '';
	err.style.display = 'none';
	}

	function show(step) {
	$$('.gv-rec-step').forEach(function (s) { s.classList.remove('active'); });
	var el = document.querySelector('.gv-rec-step[data-step="' + step + '"]');
	if (el) el.classList.add('active');
	current = step;
	refresh();
	if (step === STEP_MAX) $('#gvRecNext').textContent = 'Get My Diagnosis';
	else $('#gvRecNext').textContent = 'Continue';
	}

	function fail(msg) {
	err.textContent = msg;
	err.style.display = 'block';
	window.scrollTo({ top: $('#gvRecWizard').offsetTop - 20, behavior: 'smooth' });
	}

	function pick(el, bucket) {
	var h = el;
	if (!h) return;
	h.classList.toggle('sel');
	var val = h.getAttribute('data-val') || '';
	if (h.getAttribute('data-field')) {
		state[h.getAttribute('data-field')] = val;
		$$('#' + h.parentElement.id + ' .onb-chip.sel').forEach(function (c) {
		if (c !== h) c.classList.remove('sel');
		});
		return;
	}
	var idx = state[bucket].indexOf(val);
	if (idx >= 0) state[bucket].splice(idx, 1);
	else state[bucket].push(val);
	}

	function bindChips() {
	$$('.gv-rec-step .onb-chip').forEach(function (c) {
		var parent = c.parentElement.id;
		c.addEventListener('click', function () {
		if (parent === 'gvBBlockers') pick(c, 'blockers');
		else if (parent === 'gvBStage') { pickOne(c, 'stage'); }
		else if (parent === 'gvBSpend') { pickOne(c, 'spend'); }
		else if (parent === 'gvBGoal') { pickOne(c, 'goal'); }
		else { pick(c, parent); }
		});
	});
	}

	function pickOne(c, key) {
	$$('#' + c.parentElement.id + ' .onb-chip').forEach(function (x) { x.classList.remove('sel'); });
	c.classList.add('sel');
	state[key] = c.getAttribute('data-val') || '';
	}

	function validate(step) {
	if (step === 1) {
		if (!state.blockers.length) { fail('Please select at least one blocker.'); return false; }
		state.description = ($('#gvRecDescInput').value || '').trim().slice(0, 2000);
	}
	if (step === 2 && !state.stage) { fail('Please choose how far the build got.'); return false; }
	if (step === 3 && !state.spend) { fail('Please choose the approximate spend.'); return false; }
	if (step === 4 && !state.goal) { fail('Please choose what you want to do now.'); return false; }
	if (step === 5) {
		state.name  = ($('#gvRecName').value || '').trim();
		state.email = ($('#gvRecEmail').value || '').trim();
		state.phone = ($('#gvRecPhone').value || '').trim();
		if (!state.name) { fail('Please enter your name.'); return false; }
		if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(state.email)) { fail('Please enter a valid email address.'); return false; }
		if (!state.location_based) { fail('Please tell us where you are based.'); return false; }
	}
	return true;
	}

	$('#gvRecNext').addEventListener('click', function () {
	if (!validate(current)) return;
	if (current < STEP_MAX) { show(current + 1); return; }
	var btn = this;
	btn.disabled = true;
	btn.textContent = 'Diagnosing\u2026';
	var hp = form.querySelector('[name=gabochie_website]');
	var payload = {
		blockers: state.blockers, stage: state.stage, spend: state.spend, goal: state.goal,
		description: state.description, name: state.name, email: state.email,
		phone: state.phone, location_based: state.location_based,
		gabochie_website: (hp ? hp.value : '') || ''
	};
	fetch(ENDPOINT, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify(payload)
	}).then(function (r) {
		return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, data: d }; });
	}).then(function (res) {
		if (res.ok && res.data && res.data.success) {
		renderResult(res.data);
		} else {
		btn.disabled = false;
		btn.textContent = 'Get My Diagnosis';
		var msg = (res.data && res.data.message) || 'Something went wrong — please try again.';
		fail(msg);
		}
	}).catch(function () {
		btn.disabled = false;
		btn.textContent = 'Get My Diagnosis';
		fail('Could not reach the server. Please try again or contact us at +233 26 824 8970.');
	});
	});

	$('#gvRecBack').addEventListener('click', function () {
	if (current > 1) show(current - 1);
	});

	function renderResult(d) {
	var o = d.outcome || {};
	var tier = $('#gvRecTier');
	tier.textContent = o.tier_label || 'Diagnosis';
	tier.className = 'gv-rec-tier';
	if (o.tier === 'finish') tier.classList.add('gv-t-finish');
	else if (o.tier === 'assess') tier.classList.add('gv-t-assess');
	else tier.classList.add('gv-t-reconsider');
	$('#gvRecHeadline').textContent = o.headline || 'Your diagnosis.';
	$('#gvRecSummary').textContent = o.summary || '';
	$('#gvRecRef').textContent = d.project_id ? '#' + d.project_id : 'sent to your inbox';
	var rec = $('#gvRecReceived');
	rec.innerHTML =
		'<div><span>Blocker</span><strong>' + (state.blockers.length ? state.blockers.join(', ') : '—') + '</strong></div>' +
		'<div><span>Stage</span><strong>' + (o.stage_label || '—') + '</strong></div>' +
		'<div><span>Spend</span><strong>' + (o.spend_label || '—') + '</strong></div>' +
		'<div><span>Goal</span><strong>' + (o.goal_label || '—') + '</strong></div>';
	form.style.display = 'none';
	nav.style.display = 'none';
	progress.style.display = 'none';
	result.style.display = 'block';
	result.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	bindChips();
	show(1);
})();
</script>

<?php get_footer(); ?>