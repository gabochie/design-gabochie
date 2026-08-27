<?php
/**
 * Template Name: Development Quiz
 * Interactive 5-question quiz that feeds into Development Readiness Score
 * @package Gabochie_Value
 */

get_header();

$quiz_data = [
    'building' => [
        'label'  => 'What are you building?',
        'icon'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/></svg>',
        'hint'   => 'Different project types have different readiness requirements',
        'options' => ['Residential' => '🏠', 'Commercial' => '🏢', 'Mixed Use' => '🏗️', 'Institutional' => '🏛️', 'Industrial' => '🏭', 'Infrastructure' => '🛣️'],
    ],
    'location' => [
        'label'  => 'Where is the project?',
        'icon'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>',
        'hint'   => 'Location affects permits, costs, and timelines',
        'options' => ['Accra' => '📍', 'Kumasi' => '📍', 'Takoradi' => '📍', 'Other Ghana City' => '📍', 'Outside Ghana' => '🌍'],
    ],
    'budget' => [
        'label'  => 'What is your budget range?',
        'icon'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M8 9.5c0-1.38 1.79-2.5 4-2.5s4 1.12 4 2.5-1.79 2.5-4 2.5-4 1.12-4 2.5 1.79 2.5 4 2.5"/></svg>',
        'hint'   => 'Budget determines project scope and complexity',
        'options' => ['Under ₵100K' => '💰', '₵100K–₵500K' => '💰💰', '₵500K–₵1M' => '💰💰💰', '₵1M–₵5M' => '💰💰💰💰', '₵5M+' => '💎'],
    ],
    'timeline' => [
        'label'  => 'What is your timeline?',
        'icon'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'hint'   => 'Realistic timelines depend on project complexity',
        'options' => ['3–6 Months' => '⚡', '6–12 Months' => '📅', '1–2 Years' => '📆', '2+ Years' => '🗓️'],
    ],
    'land' => [
        'label'  => 'Do you have land?',
        'icon'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 21h18M3 21V8l9-5 9 5v13"/><line x1="9" y1="21" x2="9" y2="14h6v7"/></svg>',
        'hint'   => 'Land ownership status affects project timeline',
        'options' => ['Yes — Titled' => '✅', 'Yes — Not Titled' => '⚠️', 'No — Looking' => '🔍', 'No — Not Yet' => '⏸️'],
    ],
];

$calculator_url = home_url('/build-cost-calculator/');
$signup_url     = home_url('/login/?action=register');
?>

<style>
/* ── QUIZ TOKENS ── */
:root {
    --q-teal: #0D5C52;
    --q-teal-light: #E8F5F0;
    --q-gold: #C9A96E;
    --q-cream: #FAF7F2;
    --q-charcoal: #1C2B28;
    --q-mist: #6B7C78;
    --q-red: #D14F4F;
    --q-green: #27AE60;
    --q-border: #E2E8E5;
    --q-card-shadow: 0 2px 8px rgba(28,43,40,0.06);
}

.qz-page {
    background: var(--q-cream);
    min-height: 100vh;
    padding: 24px 16px 48px;
    font-family: 'Jost', sans-serif;
    color: var(--q-charcoal);
}

.qz-container { max-width: 640px; margin: 0 auto; }

/* ── HERO ── */
.qz-hero { text-align: center; margin-bottom: 32px; }
.qz-hero-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 56px; height: 56px; border-radius: 16px;
    background: var(--q-teal); color: #fff; margin-bottom: 16px;
}
.qz-hero-icon svg { width: 28px; height: 28px; }
.qz-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem; font-weight: 700; line-height: 1.15;
    margin: 0 0 8px; color: var(--q-charcoal);
}
.qz-hero h1 em { font-style: normal; color: var(--q-teal); }
.qz-hero p { font-size: 0.9rem; color: var(--q-mist); margin: 0; line-height: 1.5; }

/* ── PROGRESS ── */
.qz-progress { margin-bottom: 28px; }
.qz-progress-top {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 6px;
}
.qz-progress-label { font-size: 0.75rem; color: var(--q-mist); font-weight: 500; }
.qz-progress-count { font-size: 0.75rem; font-weight: 700; color: var(--q-teal); }
.qz-progress-bar {
    height: 6px; background: var(--q-border); border-radius: 3px; overflow: hidden;
}
.qz-progress-fill {
    height: 100%; background: var(--q-teal); border-radius: 3px;
    transition: width 0.4s cubic-bezier(0.4,0,0.2,1);
}

/* ── QUESTION CARD ── */
.qz-card {
    background: #fff; border-radius: 16px; padding: 24px;
    box-shadow: var(--q-card-shadow); margin-bottom: 16px;
    display: none; /* toggled by JS */
}
.qz-card.qz-active { display: block; animation: qzFadeIn 0.3s ease; }

@keyframes qzFadeIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

.qz-q-header { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
.qz-q-icon {
    display: flex; align-items: center; justify-content: center;
    width: 36px; height: 36px; border-radius: 10px;
    background: var(--q-teal-light); color: var(--q-teal); flex-shrink: 0;
}
.qz-q-icon svg { width: 18px; height: 18px; }
.qz-q-label { font-size: 1rem; font-weight: 700; color: var(--q-charcoal); }
.qz-q-hint { font-size: 0.75rem; color: var(--q-mist); margin-bottom: 16px; padding-left: 48px; }

/* ── OPTIONS ── */
.qz-options { display: grid; gap: 8px; }
.qz-option {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 10px;
    border: 2px solid var(--q-border); background: #fff;
    cursor: pointer; transition: all 0.2s;
    font-size: 0.88rem; font-weight: 500;
}
.qz-option:hover { border-color: var(--q-teal); background: var(--q-teal-light); }
.qz-option.qz-selected {
    border-color: var(--q-teal); background: var(--q-teal-light);
    box-shadow: 0 0 0 1px var(--q-teal);
}
.qz-option-emoji { font-size: 1.1rem; flex-shrink: 0; width: 28px; text-align: center; }
.qz-option-text { flex: 1; }
.qz-option-check {
    width: 18px; height: 18px; border-radius: 50%; border: 2px solid var(--q-border);
    flex-shrink: 0; transition: all 0.2s; display: flex; align-items: center; justify-content: center;
}
.qz-option.qz-selected .qz-option-check {
    border-color: var(--q-teal); background: var(--q-teal); color: #fff;
}
.qz-option.qz-selected .qz-option-check::after { content: '✓'; font-size: 11px; font-weight: 700; }

/* ── NAV BUTTONS ── */
.qz-nav {
    display: flex; gap: 10px; margin-top: 20px;
}
.qz-btn {
    flex: 1; padding: 13px 16px; border: none; border-radius: 10px;
    font-size: 0.88rem; font-weight: 600; cursor: pointer;
    transition: all 0.2s; font-family: 'Jost', sans-serif;
}
.qz-btn-back {
    background: var(--q-border); color: var(--q-charcoal); max-width: 100px; flex: 0;
}
.qz-btn-back:hover { background: #d1dbd7; }
.qz-btn-next { background: var(--q-teal); color: #fff; }
.qz-btn-next:hover { background: #0b4e45; }
.qz-btn-next:disabled { background: #ccc; cursor: not-allowed; }

/* ── RESULTS ── */
.qz-result {
    display: none; text-align: center; padding: 32px 24px;
    background: #fff; border-radius: 16px; box-shadow: var(--q-card-shadow);
}
.qz-result.qz-active { display: block; animation: qzFadeIn 0.4s ease; }
.qz-result-icon { font-size: 3rem; margin-bottom: 12px; }
.qz-result h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem; margin: 0 0 8px;
}
.qz-result p { font-size: 0.88rem; color: var(--q-mist); line-height: 1.6; margin: 0 0 16px; }

.qz-score-badge {
    display: inline-block; padding: 8px 24px; border-radius: 20px;
    font-weight: 700; font-size: 0.95rem; margin-bottom: 20px;
}
.qz-score-low { background: #FDECEA; color: var(--q-red); border: 1px solid #f5c6c2; }
.qz-score-med { background: #FFF3E0; color: #E67E22; border: 1px solid #f5d9a8; }
.qz-score-high { background: var(--q-teal-light); color: var(--q-teal); border: 1px solid #b8ddd4; }

.qz-result-breakdown {
    text-align: left; background: var(--q-cream); border-radius: 10px;
    padding: 16px; margin: 16px 0; font-size: 0.8rem;
}
.qz-result-breakdown h4 { margin: 0 0 10px; font-size: 0.85rem; color: var(--q-charcoal); }
.qz-result-row {
    display: flex; justify-content: space-between; padding: 4px 0;
    border-bottom: 1px solid var(--q-border);
}
.qz-result-row:last-child { border-bottom: none; }
.qz-result-row span:first-child { color: var(--q-mist); }
.qz-result-row span:last-child { font-weight: 600; }

.qz-cta { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
.qz-cta a {
    display: block; padding: 14px 20px; border-radius: 10px;
    text-decoration: none; font-weight: 600; font-size: 0.9rem;
    transition: all 0.2s; font-family: 'Jost', sans-serif;
}
.qz-cta-primary { background: var(--q-teal); color: #fff; }
.qz-cta-primary:hover { background: #0b4e45; transform: translateY(-1px); }
.qz-cta-secondary { background: var(--q-gold); color: #fff; }
.qz-cta-secondary:hover { background: #b8955e; transform: translateY(-1px); }
.qz-cta-ghost {
    background: none; border: 2px solid var(--q-teal); color: var(--q-teal);
}
.qz-cta-ghost:hover { background: var(--q-teal-light); }

@media (max-width: 640px) {
    .qz-hero h1 { font-size: 1.4rem; }
    .qz-card, .qz-result { padding: 16px; }
    .qz-q-hint { padding-left: 0; }
}
</style>

<div class="qz-page">
<div class="qz-container">

    <!-- HERO -->
    <div class="qz-hero">
        <div class="qz-hero-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
        </div>
        <h1>What's Your Development <em>Readiness</em>?</h1>
        <p>Answer 5 quick questions. Get an instant readiness assessment for your project in Ghana.</p>
    </div>

    <!-- PROGRESS -->
    <div class="qz-progress">
        <div class="qz-progress-top">
            <span class="qz-progress-label">Question <span class="qz-progress-count" id="qz-count">1 of 5</span></span>
            <span class="qz-progress-label" id="qz-pct">0%</span>
        </div>
        <div class="qz-progress-bar"><div class="qz-progress-fill" id="qz-fill" style="width:0%"></div></div>
    </div>

    <!-- QUESTIONS -->
    <div id="qz-questions">
    <?php
    $idx = 0;
    foreach ($quiz_data as $key => $q) : ?>
        <div class="qz-card" data-q="<?php echo esc_attr($key); ?>" data-idx="<?php echo $idx; ?>">
            <div class="qz-q-header">
                <div class="qz-q-icon"><?php echo $q['icon']; ?></div>
                <div class="qz-q-label"><?php echo esc_html($q['label']); ?></div>
            </div>
            <div class="qz-q-hint"><?php echo esc_html($q['hint']); ?></div>
            <div class="qz-options">
            <?php foreach ($q['options'] as $val => $emoji) : ?>
                <div class="qz-option" data-value="<?php echo esc_attr($val); ?>" onclick="qzSelect(this, '<?php echo esc_js($key); ?>')">
                    <span class="qz-option-emoji"><?php echo $emoji; ?></span>
                    <span class="qz-option-text"><?php echo esc_html($val); ?></span>
                    <span class="qz-option-check"></span>
                </div>
            <?php endforeach; ?>
            </div>
            <div class="qz-nav">
                <?php if ($idx > 0) : ?>
                    <button class="qz-btn qz-btn-back" onclick="qzNav(-1)">← Back</button>
                <?php endif; ?>
                <button class="qz-btn qz-btn-next" id="qz-next-<?php echo $idx; ?>" onclick="qzNav(1)" disabled>Next →</button>
            </div>
        </div>
    <?php
        $idx++;
    endforeach;
    ?>
    </div>

    <!-- RESULTS -->
    <div class="qz-result" id="qz-result">
        <div class="qz-result-icon" id="qz-result-icon">📊</div>
        <h2 id="qz-result-title">Your Readiness Assessment</h2>
        <div class="qz-score-badge" id="qz-score-badge">Calculating...</div>
        <p id="qz-result-desc"></p>

        <div class="qz-result-breakdown" id="qz-breakdown"></div>

        <div class="qz-cta">
            <a class="qz-cta-primary" href="<?php echo esc_url($signup_url); ?>">Get Your Full Readiness Score — Free →</a>
            <a class="qz-cta-secondary" href="<?php echo esc_url($calculator_url); ?>">Estimate Build Cost →</a>
            <a class="qz-cta-ghost" onclick="qzReset()">Retake Quiz</a>
        </div>
    </div>

</div>
</div>

<script>
(function() {
    var current = 0;
    var total = <?php echo count($quiz_data); ?>;
    var answers = {};

    window.qzSelect = function(el, key) {
        var card = el.closest('.qz-card');
        card.querySelectorAll('.qz-option').forEach(function(o) { o.classList.remove('qz-selected'); });
        el.classList.add('qz-selected');
        answers[key] = el.getAttribute('data-value');
        var nextBtn = document.getElementById('qz-next-' + card.getAttribute('data-idx'));
        if (nextBtn) nextBtn.disabled = false;
    };

    window.qzNav = function(dir) {
        var cards = document.querySelectorAll('.qz-card');
        cards[current].classList.remove('qz-active');
        current += dir;
        if (current >= total) {
            qzShowResult();
            return;
        }
        cards[current].classList.add('qz-active');
        qzUpdateProgress();
    };

    function qzUpdateProgress() {
        var answered = Object.keys(answers).length;
        var pct = Math.round((answered / total) * 100);
        document.getElementById('qz-count').textContent = (current + 1) + ' of ' + total;
        document.getElementById('qz-pct').textContent = pct + '%';
        document.getElementById('qz-fill').style.width = pct + '%';
    }

    window.qzShowResult = function() {
        document.querySelectorAll('.qz-card').forEach(function(c) { c.classList.remove('qz-active'); });
        document.querySelector('.qz-progress').style.display = 'none';

        var score = qzCalcScore();
        var result = document.getElementById('qz-result');
        var badge = document.getElementById('qz-score-badge');
        var icon = document.getElementById('qz-result-icon');
        var title = document.getElementById('qz-result-title');
        var desc = document.getElementById('qz-result-desc');

        if (score >= 70) {
            badge.className = 'qz-score-badge qz-score-high';
            badge.textContent = score + '% — High Readiness';
            icon.textContent = '🚀';
            title.textContent = 'You\'re Ready to Build';
            desc.textContent = 'Your project profile suggests strong readiness. You have the key ingredients for a successful development in Ghana. Sign up to get your detailed Development Readiness Score with actionable next steps.';
        } else if (score >= 40) {
            badge.className = 'qz-score-badge qz-score-med';
            badge.textContent = score + '% — Medium Readiness';
            icon.textContent = '⚡';
            title.textContent = 'Getting Close';
            desc.textContent = 'You have potential but there are gaps to address before breaking ground. Sign up to identify exactly what to fix first and track your progress over time.';
        } else {
            badge.className = 'qz-score-badge qz-score-low';
            badge.textContent = score + '% — Early Stage';
            icon.textContent = '📋';
            title.textContent = 'You Need a Plan';
            desc.textContent = 'Your project needs more groundwork before construction. That\'s normal — most Ghanaian projects stall at this stage. Our platform gives you the roadmap to move forward.';
        }

        var breakdown = document.getElementById('qz-breakdown');
        var html = '<h4>Your Answers</h4>';
        var labels = {building:'Project Type',location:'Location',budget:'Budget',timeline:'Timeline',land:'Land Status'};
        var keys = ['building','location','budget','timeline','land'];
        keys.forEach(function(k) {
            html += '<div class="qz-result-row"><span>' + labels[k] + '</span><span>' + (answers[k] || '—') + '</span></div>';
        });
        breakdown.innerHTML = html;

        result.classList.add('qz-active');
        result.scrollIntoView({behavior:'smooth',block:'start'});
    };

    function qzCalcScore() {
        var s = 50; // base
        // Budget
        if (answers.budget === 'Under ₵100K') s -= 10;
        else if (answers.budget === '₵100K–₵500K') s += 0;
        else if (answers.budget === '₵500K–₵1M') s += 8;
        else if (answers.budget === '₵1M–₵5M') s += 15;
        else if (answers.budget === '₵5M+') s += 20;
        // Land
        if (answers.land === 'Yes — Titled') s += 15;
        else if (answers.land === 'Yes — Not Titled') s += 5;
        else if (answers.land === 'No — Looking') s -= 5;
        else if (answers.land === 'No — Not Yet') s -= 10;
        // Timeline
        if (answers.timeline === '3–6 Months') s += 10;
        else if (answers.timeline === '6–12 Months') s += 5;
        else if (answers.timeline === '2+ Years') s -= 5;
        // Location
        if (answers.location === 'Accra') s += 5;
        else if (answers.location === 'Outside Ghana') s -= 5;
        // Building type
        if (answers.building === 'Residential') s += 5;
        else if (answers.building === 'Infrastructure') s -= 5;
        return Math.max(10, Math.min(95, s));
    }

    window.qzReset = function() {
        current = 0;
        answers = {};
        document.getElementById('qz-result').classList.remove('qz-active');
        document.querySelector('.qz-progress').style.display = '';
        document.querySelectorAll('.qz-card').forEach(function(c, i) {
            c.classList.remove('qz-active');
            c.querySelectorAll('.qz-option').forEach(function(o) { o.classList.remove('qz-selected'); });
        });
        document.querySelectorAll('.qz-btn-next').forEach(function(b) { b.disabled = true; });
        document.querySelector('.qz-card').classList.add('qz-active');
        qzUpdateProgress();
        window.scrollTo({top: 0, behavior: 'smooth'});
    };

    // Show first question
    document.querySelector('.qz-card').classList.add('qz-active');
})();
</script>

<?php get_footer(); ?>
