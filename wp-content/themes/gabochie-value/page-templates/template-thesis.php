<?php
/**
 * Template Name: Thesis — Development Execution Gap
 * Lead magnet landing page with email gate.
 * @package Gabochie_Value
 * @version 1.4.0
 */

get_header();

$is_allowed = isset($_GET['dl'])
    || (is_user_logged_in() && get_user_meta(get_current_user_id(), 'gabochie_thesis_downloaded', true));
?>

<style>
/* ══════════════════════════════════════════════
   THESIS LEAD MAGNET PAGE  (gv-thesis- prefix)
   ══════════════════════════════════════════════ */
:root {
    --gv-teal:#0D5C52; --gv-teal-deep:#0A4840; --gv-gold:#C9A96E;
    --gv-cream:#FAF7F2; --gv-charcoal:#1C2B28;
    --gv-mist:#6B7C78; --gv-border:#E2E8E5; --gv-border-light:#EFE9DF;
}

.gv-thesis { background: var(--gv-cream); }

/* ── Promise / stat cards ── */
.gv-thesis-promise { max-width: 1040px; margin: 0 auto 3rem; }
.gv-thesis-promise-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
.gv-thesis-stat {
    background: #fff; border: 1px solid var(--gv-border-light); border-radius: 14px;
    padding: 1.6rem 1.5rem; text-align: center;
    transition: transform .25s ease, box-shadow .25s ease;
}
.gv-thesis-stat:hover { transform: translateY(-4px); box-shadow: 0 14px 34px rgba(13,92,82,0.09); }
.gv-thesis-stat-big {
    font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 600;
    color: var(--gv-teal); line-height: 1.1; margin-bottom: 0.5rem;
}
.gv-thesis-stat-big em { font-style: italic; color: var(--gv-gold); }
.gv-thesis-stat p { font-size: 0.84rem; color: var(--gv-mist); line-height: 1.6; margin: 0; }

/* ── Teaser ── */
.gv-thesis-teaser { max-width: 760px; margin: 0 auto 2.5rem; }
.gv-thesis-teaser h2 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 500;
    color: var(--gv-charcoal); margin: 0 0 0.75rem;
}
.gv-thesis-teaser h2 em { font-style: italic; color: var(--gv-gold); }
.gv-thesis-teaser p { font-size: 0.98rem; color: var(--gv-mist); line-height: 1.75; margin: 0 0 1rem; }
.gv-thesis-learn { list-style: none; margin: 0; padding: 0; }
.gv-thesis-learn li {
    display: flex; gap: 0.65rem; align-items: flex-start;
    font-size: 0.92rem; color: var(--gv-charcoal); line-height: 1.6; padding: 0.4rem 0;
}
.gv-thesis-learn li::before {
    content: '✓'; color: var(--gv-gold); font-weight: 700; font-size: 1rem; line-height: 1.4;
}

/* ── Email gate ── */
.gv-thesis-gate {
    max-width: 640px; margin: 0 auto 3.5rem;
    background: linear-gradient(135deg, var(--gv-teal-deep), var(--gv-teal));
    border-radius: 18px; padding: 2.5rem 2.25rem; text-align: center;
    position: relative; overflow: hidden; color: var(--gv-cream);
    box-shadow: 0 24px 60px rgba(13,92,82,0.18);
}
.gv-thesis-gate::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(500px 180px at 20% -30%, rgba(201,169,110,0.4), transparent 60%);
    pointer-events: none;
}
.gv-thesis-gate > * { position: relative; }
.gv-thesis-gate h3 {
    font-family: 'Playfair Display', serif; font-size: clamp(1.35rem, 2.6vw, 1.75rem);
    font-weight: 500; margin: 0 0 0.6rem;
}
.gv-thesis-gate p { font-size: 0.92rem; color: rgba(250,247,242,0.78); line-height: 1.6; margin: 0 auto 1.5rem; max-width: 460px; }
.gv-thesis-form { display: flex; flex-direction: column; gap: 0.8rem; max-width: 420px; margin: 0 auto; }
.gv-thesis-form input[type=text],
.gv-thesis-form input[type=email] {
    width: 100%; padding: 0.9rem 1.1rem; border-radius: 10px;
    border: 1px solid rgba(250,247,242,0.35); background: rgba(250,247,242,0.12);
    color: var(--gv-cream); font-family: 'Jost', sans-serif; font-size: 0.95rem;
    outline: none; transition: border-color .2s ease, background .2s ease;
}
.gv-thesis-form input::placeholder { color: rgba(250,247,242,0.5); }
.gv-thesis-form input:focus { border-color: var(--gv-gold); background: rgba(250,247,242,0.16); }
.gv-thesis-form .gv-thesis-hp { position: absolute; left: -9999px; opacity: 0; height: 0; overflow: hidden; }
.gv-thesis-btn {
    padding: 0.95rem 1.6rem; border: none; border-radius: 10px; cursor: pointer;
    background: var(--gv-gold); color: var(--gv-teal-deep);
    font-family: 'Jost', sans-serif; font-size: 0.85rem; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    transition: all .2s ease;
}
.gv-thesis-btn:hover { background: #b8955e; transform: translateY(-1px); }
.gv-thesis-btn:disabled { opacity: 0.7; cursor: default; transform: none; }
.gv-thesis-gate-note { font-size: 0.74rem; color: rgba(250,247,242,0.55); margin: 1rem auto 0; }
.gv-thesis-gate-msg { font-size: 0.88rem; line-height: 1.6; margin: 1rem auto 0; display: none; }
.gv-thesis-gate-msg.is-err { color: #ffd9d9; }
.gv-thesis-gate-msg.is-ok { color: #d9f7e9; }

/* ── Thesis body ── */
.gv-thesis-body { max-width: 820px; margin: 0 auto 4rem; }
.gv-thesis-body.is-locked { display: none; }
.gv-thesis-body .gv-thesis-body-inner {
    background: #fff; border: 1px solid var(--gv-border-light); border-radius: 18px;
    padding: clamp(1.75rem, 4vw, 3.25rem);
    box-shadow: 0 18px 46px rgba(13,92,82,0.07);
}
.gv-thesis-body .gv-thesis-byline {
    font-size: 0.74rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--gv-gold); margin-bottom: 1.25rem;
}
.gv-thesis-body h2 {
    font-family: 'Playfair Display', serif; font-size: clamp(1.3rem, 2.6vw, 1.6rem);
    font-weight: 600; color: var(--gv-charcoal); margin: 2.2rem 0 0.75rem; line-height: 1.3;
}
.gv-thesis-body h2:first-child { margin-top: 0; }
.gv-thesis-body h2 em { font-style: italic; color: var(--gv-gold); }
.gv-thesis-body h3 {
    font-family: 'Playfair Display', serif; font-size: 1.05rem; font-weight: 600;
    color: var(--gv-teal); margin: 1.5rem 0 0.5rem;
}
.gv-thesis-body p { font-size: 0.97rem; color: #40534E; line-height: 1.85; margin: 0 0 1rem; }
.gv-thesis-body p:last-child { margin-bottom: 0; }
.gv-thesis-body strong { color: var(--gv-charcoal); }
.gv-thesis-body blockquote {
    margin: 1.5rem 0; padding: 1.1rem 1.4rem;
    border-left: 3px solid var(--gv-gold); background: var(--gv-cream);
    border-radius: 0 12px 12px 0; font-size: 0.95rem; color: var(--gv-teal-deep);
    font-style: italic; line-height: 1.7;
}
.gv-thesis-body ul, .gv-thesis-body ol { margin: 0 0 1rem 1.2rem; padding: 0; }
.gv-thesis-body li { font-size: 0.94rem; color: #40534E; line-height: 1.8; margin-bottom: 0.3rem; }
.gv-thesis-body a { color: var(--gv-teal); text-decoration: underline; }
.gv-thesis-body .wp-block-heading { margin: 2.2rem 0 0.75rem; }
.gv-thesis-body img { border-radius: 12px; }

/* ── Access note (returning) ── */
.gv-thesis-access {
    max-width: 640px; margin: 0 auto 3rem; text-align: center;
    background: #FFFDF8; border: 1px solid rgba(201,169,110,0.35);
    border-radius: 14px; padding: 1.1rem 1.5rem; font-size: 0.9rem; color: var(--gv-teal-deep);
}
.gv-thesis-access a { color: var(--gv-teal); text-decoration: underline; }

/* ── CTA band ── */
.gv-thesis-cta {
    max-width: 1000px; margin: 0 auto;
    background: linear-gradient(120deg, var(--gv-teal-deep), var(--gv-teal));
    border-radius: 18px; color: var(--gv-cream);
    padding: 3rem 2.5rem; text-align: center; position: relative; overflow: hidden;
}
.gv-thesis-cta::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(600px 200px at 85% -20%, rgba(201,169,110,0.35), transparent 60%);
    pointer-events: none;
}
.gv-thesis-cta h3 {
    position: relative; font-family: 'Playfair Display', serif;
    font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 500; margin: 0 0 0.6rem;
}
.gv-thesis-cta p { position: relative; font-size: 0.95rem; color: rgba(250,247,242,0.78); line-height: 1.6; margin: 0 auto 1.75rem; max-width: 560px; }
.gv-thesis-cta-actions { position: relative; display: flex; gap: 0.9rem; justify-content: center; flex-wrap: wrap; }
.gv-thesis-cta-btn {
    display: inline-block; padding: 0.95rem 2rem; border-radius: 10px;
    background: var(--gv-gold); color: var(--gv-teal-deep);
    font-family: 'Jost', sans-serif; font-size: 0.84rem; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase; text-decoration: none;
    transition: all .2s ease;
}
.gv-thesis-cta-btn:hover { background: #b8955e; color: var(--gv-teal-deep); transform: translateY(-1px); }
.gv-thesis-cta-btn--ghost { background: transparent; border: 1.5px solid rgba(250,247,242,0.4); color: var(--gv-cream); }
.gv-thesis-cta-btn--ghost:hover { background: rgba(250,247,242,0.08); color: var(--gv-cream); }

@media (max-width: 900px) {
    .gv-thesis-promise-grid { grid-template-columns: 1fr; max-width: 420px; margin: 0 auto; }
}
</style>

<main class="gv-thesis">

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Thesis</div>
        <h1>The Development <em>Execution Gap</em></h1>
        <p>Ghana is building at record pace — yet most projects never break ground. Here's why, and what it means for yours.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container">

        <!-- Promise / stat cards -->
        <div class="gv-thesis-promise">
            <div class="gv-thesis-promise-grid">
                <div class="gv-thesis-stat">
                    <div class="gv-thesis-stat-big">60<em>%</em></div>
                    <p>of development projects in Ghana stall before they start — not for lack of money, but lack of clarity.</p>
                </div>
                <div class="gv-thesis-stat">
                    <div class="gv-thesis-stat-big">#1</div>
                    <p>Ghana currently leads West Africa in construction growth — the activity is here, the execution isn't.</p>
                </div>
                <div class="gv-thesis-stat">
                    <div class="gv-thesis-stat-big">3<em>×</em></div>
                    <p>The real barrier is threefold: information asymmetry, coordination failure, and zero investor-grade visibility.</p>
                </div>
            </div>
        </div>

        <!-- Teaser -->
        <div class="gv-thesis-teaser">
            <h2>Why So Many Projects <em>Fail Before They Start</em></h2>
            <p>Every stalled project has the same quiet origin story: a landowner with capital and ambition, a market that demands housing, and no way to turn one into the other. This thesis shows you the exact gap between them — and the tools already closing it.</p>
            <ul class="gv-thesis-learn">
                <li>Why Ghana's construction boom is real — and where it leaks</li>
                <li>What the data says about the true barrier (it's not capital)</li>
                <li>The 14-stage delivery pipeline professional firms use — and you can too</li>
                <li>How a development intelligence platform changes the odds</li>
            </ul>
        </div>

        <?php if ($is_allowed) : ?>
            <div class="gv-thesis-access">You have access to the full thesis. Find your copy link anytime in your inbox — or <a href="<?php echo esc_url(home_url('/thesis/?dl=1')); ?>">re-open it here</a>.</div>
        <?php else : ?>
            <!-- Email gate -->
            <div class="gv-thesis-gate" id="gvThesisGate">
                <h3>Get the Full Thesis — Free</h3>
                <p>Enter your email and we'll send you the complete "Development Execution Gap" thesis, plus one practical step you can take on your own project this week.</p>
                <form class="gv-thesis-form" id="gvThesisForm" novalidate>
                    <input type="text" name="name" placeholder="Your name (optional)" autocomplete="name">
                    <input type="email" name="email" placeholder="you@example.com" autocomplete="email" required>
                    <div class="gv-thesis-hp"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <button type="submit" class="gv-thesis-btn" id="gvThesisBtn">Send Me the Thesis</button>
                </form>
                <p class="gv-thesis-gate-note">No spam. Unsubscribe anytime. Your Readiness Score stays free forever.</p>
                <div class="gv-thesis-gate-msg" id="gvThesisMsg" role="status"></div>
            </div>
        <?php endif; ?>

        <!-- Thesis body -->
        <div class="gv-thesis-body<?php echo $is_allowed ? '' : ' is-locked'; ?>" id="gvThesisBody">
            <div class="gv-thesis-body-inner">
                <div class="gv-thesis-byline">The Gabochie Design Thesis · 2026</div>
                <?php
                while (have_posts()) {
                    the_post();
                    the_content();
                }
                ?>
            </div>
        </div>

        <!-- CTA -->
        <div class="gv-thesis-cta">
            <h3>What's Your Readiness Score?</h3>
            <p>Eight dimensions. Five minutes. A clear picture of whether your project is ready to move — and exactly what's missing.</p>
            <div class="gv-thesis-cta-actions">
                <a class="gv-thesis-cta-btn" href="<?php echo esc_url(home_url('/development-quiz/')); ?>">Get Your Free Readiness Score</a>
                <a class="gv-thesis-cta-btn gv-thesis-cta-btn--ghost" href="<?php echo esc_url(home_url('/membership-levels/')); ?>">See the Membership Plans</a>
            </div>
        </div>

    </div>
</section>

</main>

<?php if (!$is_allowed) : ?>
<script>
(function () {
    var form  = document.getElementById('gvThesisForm');
    var btn   = document.getElementById('gvThesisBtn');
    var msg   = document.getElementById('gvThesisMsg');
    var body  = document.getElementById('gvThesisBody');
    var gate  = document.getElementById('gvThesisGate');
    if (!form) return;

    function show(type, text) {
        msg.textContent = text;
        msg.className = 'gv-thesis-gate-msg is-' + type;
        msg.style.display = 'block';
    }

    function reveal() {
        if (body) body.classList.remove('is-locked');
        if (gate) gate.style.display = 'none';
        if (body) body.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var email = form.querySelector('input[name=email]').value.trim();
        if (!/.+@.+\..+/.test(email)) {
            show('err', 'Please enter a valid email address.');
            return;
        }

        var data = new FormData();
        data.append('action', 'gabochie_thesis_download');
        data.append('nonce', '<?php echo esc_js(wp_create_nonce('gabochie_thesis_nonce')); ?>');
        data.append('email', email);
        data.append('name', form.querySelector('input[name=name]').value.trim());
        data.append('website', form.querySelector('input[name=website]').value);

        btn.textContent = 'Sending…';
        btn.disabled = true;
        msg.style.display = 'none';

        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: data })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    show('ok', res.data.message + ' Enjoy the thesis below.');
                    reveal();
                } else {
                    btn.textContent = 'Send Me the Thesis';
                    btn.disabled = false;
                    show('err', res.data && res.data.message ? res.data.message : 'Something went wrong. Please try again.');
                }
            })
            .catch(function () {
                btn.textContent = 'Send Me the Thesis';
                btn.disabled = false;
                show('err', 'Something went wrong. Please try again.');
            });
    });
})();
</script>
<?php endif; ?>

<?php get_footer(); ?>