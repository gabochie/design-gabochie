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
			<div class="onb-field onb-field-full">
				<label>Where are you based? <span class="req">*</span></label>
				<div class="onb-chip-grid cols-3" id="gvBLocation">
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
		<div class="gv-rec-next">
			<div class="gv-rec-next-title">What happens next</div>
			<ol>
				<li><strong>Book the Diagnostic (GHS 1,500)</strong> — fixed fee, clear scope.</li>
				<li><strong>Your workspace opens</strong> — Triage → Evidence → Plan → Execution → Verification.</li>
				<li><strong>A facilitator co-drives</strong> — same checklist, docs, costs and messages.</li>
			</ol>
		</div>
		<div class="gv-rec-ctas" id="gvRecCtas">
			<a class="btn-solid" id="gvRecCtaPrimary" href="<?php echo esc_url( gabochie_value_recovery_product_url() ); ?>">Book the Recovery Diagnostic — Unlock Workspace</a>
			<a class="btn-ghost" href="<?php echo esc_url( home_url( '/membership-levels/' ) ); ?>">See Membership Plans</a>
		</div>
		<p class="gv-rec-note">All building types in Ghana — residential, commercial, mixed-use, renovation & interior. No obligation beyond the diagnostic.</p>
		</div>

	</div><!-- /onb-card -->
	</div>
</section>

<!-- ========== HOW RECOVERY WORKS ========== -->
<section class="alone" id="how-it-works">
	<div class="container">
		<div class="sec-ey"><span>How recovery works</span></div>
		<h2 class="sec-h2 reveal">One workspace. <em>Five steps out.</em></h2>
		<p class="sec-lead reveal">The diagnostic above is step one. Booking it opens your step-by-step Recovery Workspace — you drive, a facilitator co-drives.</p>
		<div class="strip-wrap reveal">
			<div class="strip">
				<div class="strip-node start"><div class="s-dot"></div><div class="s-label">Triage</div></div>
				<span class="strip-arrow">&rsaquo;</span>
				<div class="strip-node"><div class="s-dot"></div><div class="s-label">Evidence</div></div>
				<span class="strip-arrow">&rsaquo;</span>
				<div class="strip-node"><div class="s-dot"></div><div class="s-label">Plan</div></div>
				<span class="strip-arrow">&rsaquo;</span>
				<div class="strip-node"><div class="s-dot"></div><div class="s-label">Execute</div></div>
				<span class="strip-arrow">&rsaquo;</span>
				<div class="strip-node end"><div class="s-dot"></div><div class="s-label">Verify</div></div>
			</div>
		</div>
	</div>
</section>

<!-- ========== TRUST ========== -->
<section class="trust" id="trust">
	<div class="container">
		<div class="sec-ey"><span>Why clients trust the process</span></div>
		<h2 class="sec-h2 reveal">An honest answer <em>before</em> more spend.</h2>
		<div class="trust-grid">
			<div class="trust-item reveal">
				<div class="t-ico"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
				<h3>Finish vs restart, with numbers</h3>
				<p>Your diagnosis weighs money committed and structure in the ground against the blockers — so the continue-or-restart call is grounded, not guessed.</p>
			</div>
			<div class="trust-item reveal reveal-delay-1">
				<div class="t-ico"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
				<h3>Evidence before advice</h3>
				<p>Drawings, contracts, photos and permits are gathered first. No plan is written until the evidence — and your Readiness Score — supports it.</p>
			</div>
			<div class="trust-item reveal reveal-delay-2">
				<div class="t-ico"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
				<h3>Fixed fee, defined scope</h3>
				<p>The Recovery Diagnostic is GHS 1,500 — one fee, clear deliverables, no obligation to continue. Every building type in Ghana is covered.</p>
			</div>
		</div>
	</div>
</section>

<!-- ========== FAQ ========== -->
<section class="faq" id="faq">
	<div class="container">
		<div class="sec-ey"><span>Frequently asked</span></div>
		<h2 class="sec-h2 reveal">Recovery questions, answered.</h2>
		<div class="faq-list">
			<div class="faq-item">
				<button class="faq-q" aria-expanded="false">Is my project too far gone to rescue? <span class="fq-ico">+</span></button>
				<div class="faq-a"><p>Usually not. If there is structure in the ground and the land position is sound, finishing typically costs less than starting over. The free diagnosis above gives you an honest first read; the paid Diagnostic confirms it with evidence.</p></div>
			</div>
			<div class="faq-item">
				<button class="faq-q" aria-expanded="false">What does the GHS 1,500 Diagnostic include? <span class="fq-ico">+</span></button>
				<div class="faq-a"><p>A structured review of your drawings, contracts, site photos and spend, a written report (what's wrong, what it costs to fix, continue vs restart), and your step-by-step Recovery Workspace with a facilitator assigned.</p></div>
			</div>
			<div class="faq-item">
				<button class="faq-q" aria-expanded="false">I'm abroad. Can I run a recovery remotely? <span class="fq-ico">+</span></button>
				<div class="faq-a"><p>Yes — the workspace is built for it: photo evidence, documented reporting and a facilitator as your eyes on site. Choose "Abroad (Diaspora)" in the wizard so reporting is set up accordingly.</p></div>
			</div>
			<div class="faq-item">
				<button class="faq-q" aria-expanded="false">Do I have to continue with Gabochie after the Diagnostic? <span class="fq-ico">+</span></button>
				<div class="faq-a"><p>No. The Diagnostic is a standalone, fixed-scope consultation. If you continue into Execution, milestones and costs are tracked in the same workspace.</p></div>
			</div>
		</div>
		<div class="faq-cta reveal">
			<p>Still stuck on whether to start?</p>
			<div class="faq-cta-btns">
				<a class="btn-gold" href="#gvRecWizard">Run the Free Diagnosis</a>
				<a class="btn-ghost" href="<?php echo esc_url( gabochie_value_recovery_product_url() ); ?>">Book the Diagnostic</a>
			</div>
		</div>
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