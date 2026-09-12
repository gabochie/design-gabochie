<?php
/**
 * Template Name: Development Quiz
 * Interactive 5-question quiz that feeds into Development Readiness Score.
 * Built on the shared onboarding body (onb-* skeleton) so it matches the
 * other assessment pages: hero → wizard card → stepper → steps → nav.
 *
 * @package Gabochie_Value
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$quiz_data = array(
	'building' => array(
		'label'   => 'What are you building?',
		'hint'    => 'Different project types have different readiness requirements.',
		'options' => array(
			'Residential'   => '&#127968;',
			'Commercial'    => '&#127970;',
			'Mixed Use'     => '&#127961;',
			'Institutional' => '&#127963;',
			'Industrial'    => '&#127981;',
			'Infrastructure'=> '&#128739;',
		),
	),
	'location' => array(
		'label'   => 'Where is the project?',
		'hint'    => 'Location affects permits, costs, and timelines.',
		'options' => array(
			'Accra'           => '&#128205;',
			'Kumasi'          => '&#128205;',
			'Takoradi'        => '&#128205;',
			'Other Ghana City'=> '&#128205;',
			'Outside Ghana'   => '&#127757;',
		),
	),
	'budget' => array(
		'label'   => 'What is your budget range?',
		'hint'    => 'Budget determines project scope and complexity.',
		'options' => array(
			'Under ₵100K' => '&#128176;',
			'₵100K–₵500K' => '&#128176;',
			'₵500K–₵1M'   => '&#128176;',
			'₵1M–₵5M'     => '&#128176;',
			'₵5M+'        => '&#128142;',
		),
	),
	'timeline' => array(
		'label'   => 'What is your timeline?',
		'hint'    => 'Realistic timelines depend on project complexity.',
		'options' => array(
			'3–6 Months'  => '&#9889;',
			'6–12 Months' => '&#128197;',
			'1–2 Years'   => '&#128467;',
			'2+ Years'    => '&#128937;',
		),
	),
	'land' => array(
		'label'   => 'Do you have land?',
		'hint'    => 'Land ownership status affects project timeline.',
		'options' => array(
			'Yes — Titled'     => '&#9989;',
			'Yes — Not Titled' => '&#9888;&#65039;',
			'No — Looking'     => '&#128269;',
			'No — Not Yet'     => '&#9203;',
		),
	),
);

$calculator_url = home_url( '/building-cost-calculator/' );
$signup_url     = home_url( '/login/' );
?>

<style>
/* Scoped quiz styles on top of the onboarding.css layout. */
.gv-qz-step .onb-chip { padding: 0.8rem 1rem; }
.gv-qz-opt-emoji { margin-right: 0.55rem; font-size: 1.05rem; line-height: 1; }
.gv-qz-result { text-align: center; }
.gv-qz-tier { display: inline-block; padding: 0.4rem 1.1rem; border-radius: 999px; font-size: 0.74rem; letter-spacing: 0.13em; text-transform: uppercase; font-weight: 600; font-family: 'Jost', sans-serif; margin-bottom: 1rem; }
.gv-qz-high { background: var(--teal-light, #e8f5f0); color: var(--teal, #0d5c52); border: 1px solid var(--border, #b8ddd4); }
.gv-qz-med { background: #FFF3E0; color: #E67E22; border: 1px solid #f5d9a8; }
.gv-qz-low { background: #FDECEA; color: #C25E3A; border: 1px solid #f5c6c2; }
.gv-qz-desc { color: var(--mist, #5f6359); font-size: 0.95rem; line-height: 1.7; max-width: 540px; margin: 0 auto 1.5rem; }
.gv-qz-breakdown { background: var(--cream, #f6f1e8); border: 1px solid var(--border-light, #e5e2da); border-radius: 14px; padding: 1.25rem 1.5rem; margin: 1.5rem auto 0; max-width: 480px; text-align: left; }
.gv-qz-breakdown h4 { font-size: 0.72rem; letter-spacing: 0.16em; text-transform: uppercase; color: var(--gold, #b08d3f); margin: 0 0 0.75rem; }
.gv-qz-row { display: flex; justify-content: space-between; gap: 1rem; padding: 0.5rem 0; border-bottom: 1px solid var(--border-light, #e5e2da); font-size: 0.85rem; }
.gv-qz-row:last-child { border-bottom: none; }
.gv-qz-row span:first-child { color: var(--mist, #5f6359); }
.gv-qz-row span:last-child { font-weight: 600; color: var(--charcoal, #1c2b28); text-align: right; }
</style>

<main id="main" class="onb-main">

<!-- ========== HERO ========== -->
<section class="onb-hero">
	<div class="container">
	<div class="breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / Development Quiz</div>
	<h1 class="onb-hero-h1">What's your development <em>readiness?</em></h1>
	<p class="onb-hero-sub">Answer 5 quick questions and get an instant readiness assessment for your project in Ghana &mdash; free, no sign-up required.</p>
	</div>
</section>

<!-- ========== WIZARD ========== -->
<section class="onb-wizard" id="qzWizard">
	<div class="container">
	<div class="onb-card">

		<!-- Progress bar -->
		<div class="onb-progress" id="qzProgress">
			<div class="onb-p-step active" data-p="1"><span class="op-num">1</span><span class="op-label">Building</span></div>
			<span class="op-dash"></span>
			<div class="onb-p-step" data-p="2"><span class="op-num">2</span><span class="op-label">Location</span></div>
			<span class="op-dash"></span>
			<div class="onb-p-step" data-p="3"><span class="op-num">3</span><span class="op-label">Budget</span></div>
			<span class="op-dash"></span>
			<div class="onb-p-step" data-p="4"><span class="op-num">4</span><span class="op-label">Timeline</span></div>
			<span class="op-dash"></span>
			<div class="onb-p-step" data-p="5"><span class="op-num">5</span><span class="op-label">Land</span></div>
		</div>

		<div class="onb-body">

		<?php
		$qz_idx = 0;
		foreach ( $quiz_data as $qz_key => $qz_q ) :
			$qz_idx++;
			$qz_active = 1 === $qz_idx ? ' active' : '';
			?>
			<div class="onb-step gv-qz-step<?php echo esc_attr( $qz_active ); ?>" data-step="<?php echo (int) $qz_idx; ?>">
			<h2 class="onb-q"><?php echo esc_html( $qz_q['label'] ); ?></h2>
			<p class="onb-hint"><?php echo esc_html( $qz_q['hint'] ); ?></p>
			<div class="onb-chip-grid" data-qz="<?php echo esc_attr( $qz_key ); ?>">
			<?php foreach ( $qz_q['options'] as $qz_val => $qz_emoji ) : ?>
				<button type="button" class="onb-chip gv-qz-opt" data-val="<?php echo esc_attr( $qz_val ); ?>">
				<span class="gv-qz-opt-emoji"><?php echo $qz_emoji; // phpcs:ignore WordPress.Security.EscapeOutput -- static emoji entites. ?></span>
				<span><?php echo esc_html( $qz_val ); ?></span>
				</button>
			<?php endforeach; ?>
			</div>
			</div>
			<?php
		endforeach;
		?>

		</div><!-- /onb-body -->

		<!-- ===== RESULT ===== -->
		<div class="onb-body gv-qz-result" id="qzResult" style="display:none">
		<div class="gv-qz-tier" id="qzBadge">Calculating&hellip;</div>
		<h2 class="onb-q" id="qzTitle"></h2>
		<p class="gv-qz-desc" id="qzDesc"></p>

		<div class="gv-qz-breakdown" id="qzBreakdown"></div>

		<div class="onb-profile-ctas">
			<a class="btn-gold" href="<?php echo esc_url( $signup_url ); ?>">Get Your Full Readiness Score &mdash; Free</a>
			<a class="btn-ghost" href="<?php echo esc_url( $calculator_url ); ?>">Estimate Build Cost</a>
			<button type="button" class="btn-ghost" id="qzRetake">Retake Quiz</button>
		</div>
		</div>

		<!-- ===== NAV ===== -->
		<div class="onb-nav" id="qzNav">
			<button class="btn-ghost" id="qzBack" type="button">Back</button>
			<span class="onb-err" id="qzErr"></span>
			<button class="btn-solid" id="qzNext" type="button">Continue</button>
		</div>

	</div><!-- /onb-card -->
	</div>
</section>

</main>

<script>
(function () {
	'use strict';
	var TOTAL = <?php echo (int) count( $quiz_data ); ?>;
	var answers = {};
	var current = 1;

	var $  = function (s) { return document.querySelector(s); };
	var $$ = function (s) { return Array.prototype.slice.call(document.querySelectorAll(s)); };

	var steps   = $$('.gv-qz-step');
	var pSteps  = $$('.onb-p-step');
	var errEl   = $('#qzErr');
	var navBar  = $('#qzNav');
	var nextBtn = $('#qzNext');
	var backBtn = $('#qzBack');
	var result  = $('#qzResult');

	function fail(msg) {
	errEl.textContent = msg;
	errEl.style.display = 'block';
	}

	function showStep(n) {
	current = n;
	steps.forEach(function (s) { s.classList.toggle('active', +s.getAttribute('data-step') === n); });
	pSteps.forEach(function (p) {
		var idx = +p.getAttribute('data-p');
		p.classList.toggle('active', idx === n);
		p.classList.toggle('done', idx < n);
	});
	errEl.textContent = '';
	errEl.style.display = 'none';
	if (nextBtn) nextBtn.textContent = n === TOTAL ? 'See My Readiness' : 'Continue';
	}

	/* ---- Chip selection (single-select per question) ---- */
	$$('.gv-qz-opt').forEach(function (chip) {
	chip.addEventListener('click', function () {
		var grid = chip.closest('.onb-chip-grid');
		if (grid) {
		$$('.onb-chip', grid).forEach(function (c) { c.classList.remove('sel'); });
		}
		chip.classList.add('sel');
		answers[grid.getAttribute('data-qz')] = chip.getAttribute('data-val');
	});
	});

	/* ---- Scoring (matches prior quiz) ---- */
	function calcScore() {
	var s = 50;
	if (answers.budget === 'Under \u20B5100K') s -= 10;
	else if (answers.budget === '\u20B5500K\u2013\u20B51M') s += 8;
	else if (answers.budget === '\u20B51M\u2013\u20B55M') s += 15;
	else if (answers.budget === '\u20B55M+') s += 20;
	if (answers.land === 'Yes \u2014 Titled') s += 15;
	else if (answers.land === 'Yes \u2014 Not Titled') s += 5;
	else if (answers.land === 'No \u2014 Looking') s -= 5;
	else if (answers.land === 'No \u2014 Not Yet') s -= 10;
	if (answers.timeline === '3\u20136 Months') s += 10;
	else if (answers.timeline === '6\u201312 Months') s += 5;
	else if (answers.timeline === '2+ Years') s -= 5;
	if (answers.location === 'Accra') s += 5;
	else if (answers.location === 'Outside Ghana') s -= 5;
	if (answers.building === 'Residential') s += 5;
	else if (answers.building === 'Infrastructure') s -= 5;
	return Math.max(10, Math.min(95, s));
	}

	function showResult() {
	var score = calcScore();
	var badge = $('#qzBadge');
	var title = $('#qzTitle');
	var desc  = $('#qzDesc');

	if (score >= 70) {
		badge.className = 'gv-qz-tier gv-qz-high';
		badge.textContent = score + '% \u2014 High Readiness';
		title.innerHTML = 'You\u2019re ready to <em>build.</em>';
		desc.textContent = 'Your project profile suggests strong readiness. You have the key ingredients for a successful development in Ghana. Sign up to get your detailed Development Readiness Score with actionable next steps.';
	} else if (score >= 40) {
		badge.className = 'gv-qz-tier gv-qz-med';
		badge.textContent = score + '% \u2014 Medium Readiness';
		title.innerHTML = 'You\u2019re <em>getting close.</em>';
		desc.textContent = 'You have potential but there are gaps to address before breaking ground. Sign up to identify exactly what to fix first and track your progress over time.';
	} else {
		badge.className = 'gv-qz-tier gv-qz-low';
		badge.textContent = score + '% \u2014 Early Stage';
		title.innerHTML = 'You need a <em>plan.</em>';
		desc.textContent = 'Your project needs more groundwork before construction. That\u2019s normal \u2014 most Ghanaian projects stall at this stage. Our platform gives you the roadmap to move forward.';
	}

	var labels = { building: 'Project Type', location: 'Location', budget: 'Budget', timeline: 'Timeline', land: 'Land Status' };
	var order  = ['building', 'location', 'budget', 'timeline', 'land'];
	var html   = '<h4>Your Answers</h4>';
	order.forEach(function (k) {
		html += '<div class="gv-qz-row"><span>' + labels[k] + '</span><span>' + (answers[k] || '\u2014') + '</span></div>';
	});
	$('#qzBreakdown').innerHTML = html;

	steps.forEach(function (s) { s.classList.remove('active'); });
	$('#qzProgress').style.display = 'none';
	navBar.style.display = 'none';
	result.style.display = 'block';
	result.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	function reset() {
	answers = {};
	current = 1;
	steps.forEach(function (s, i) { s.classList.toggle('active', i === 0); });
	pSteps.forEach(function (p) {
		p.classList.toggle('active', +p.getAttribute('data-p') === 1);
		p.classList.toggle('done', false);
	});
	$$('.gv-qz-opt.sel').forEach(function (c) { c.classList.remove('sel'); });
	$('#qzProgress').style.display = '';
	result.style.display = 'none';
	navBar.style.display = '';
	nextBtn.textContent = 'Continue';
	window.scrollTo({ top: $('#qzWizard').offsetTop - 20, behavior: 'smooth' });
	}

	nextBtn.addEventListener('click', function () {
	var stepEl = document.querySelector('.gv-qz-step[data-step="' + current + '"]');
	var grid   = stepEl ? stepEl.querySelector('.onb-chip-grid') : null;
	if (grid) {
		var picked = grid.querySelector('.onb-chip.sel');
		if (!picked) { fail('Please choose an option to continue.'); return; }
	}
	if (current < TOTAL) { showStep(current + 1); return; }
	showResult();
	});

	backBtn.addEventListener('click', function () {
	if (current > 1) showStep(current - 1);
	});

	$('#qzRetake').addEventListener('click', reset);

	showStep(1);
})();
</script>

<?php get_footer(); ?>