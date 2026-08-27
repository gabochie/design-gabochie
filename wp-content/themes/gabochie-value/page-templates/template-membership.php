<?php
/**
 * Template Name: Membership Levels
 * Professional membership levels page — hero, level cards, FAQ, CTA.
 * @package Gabochie_Value
 */

get_header();

$phone   = gabochie_value_phone();
$whatsapp = gabochie_value_whatsapp();

if (function_exists('pmpro_getAllLevels')) {
    $gv_levels = pmpro_getAllLevels(false, true);
} else {
    $gv_levels = array();
}

/* Curated benefit lists (balanced with level descriptions) */
$gv_benefits = array(
    1 => array('Private project portal & document vault', 'Development Readiness Score', 'AI consultation tools (Ama)', 'Community Q&A access', 'Free initial consultation'),
    2 => array('Everything in Free Lead', 'Full project dashboard', 'Document vault & secure messaging', 'Design review & feedback rounds', 'Cost tracking & budget tools', 'Priority support'),
    3 => array('Everything in Active Client', 'Interior design concepts', 'Dedicated project management', 'Cost analytics & reporting', 'Direct line to your account manager', 'Priority scheduling'),
);

$gv_icons = array(
    1 => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2"><circle cx="24" cy="24" r="18"/><path d="M12 24h24M24 12v24"/></svg>',
    2 => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2"><rect x="8" y="6" width="32" height="36" rx="3"/><line x1="14" y1="14" x2="34" y2="14"/><line x1="14" y1="22" x2="28" y2="22"/><path d="M14 30h20M14 36h12"/></svg>',
    3 => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2"><polygon points="24,4 44,18 44,40 4,40 4,18"/><circle cx="24" cy="24" r="8"/></svg>',
);

$gv_badges = array(
    1 => 'Free Lead',
    2 => 'Active Client',
    3 => 'Premium Service',
);

function gv_level_price($l) {
    if ($l->billing_amount > 0 && !empty($l->billing_cycle)) {
        return pmpro_formatPrice($l->billing_amount);
    }
    if ($l->initial_payment > 0) {
        return pmpro_formatPrice($l->initial_payment);
    }
    return 'Free';
}

function gv_level_period($l) {
    if ($l->billing_amount > 0 && !empty($l->billing_cycle)) {
        $unit = !empty($l->billing_cycle_unit) ? $l->billing_cycle_unit : 'Month';
        return 'per ' . strtolower($unit) . ($l->billing_cycle > 1 ? 's' : '');
    }
    if ($l->initial_payment > 0) {
        return 'one-time payment';
    }
    return 'no card required';
}
?>

<style>
/* ══════════════════════════════════════════════
   MEMBERSHIP LEVELS PAGE  (gv-mem- prefix)
   Self-contained page styles — matches design system
   ══════════════════════════════════════════════ */
:root {
    --gv-teal:#0D5C52; --gv-teal-deep:#0A4840; --gv-gold:#C9A96E;
    --gv-cream:#FAF7F2; --gv-ivory:#F4EFE6; --gv-charcoal:#1C2B28;
    --gv-mist:#6B7C78; --gv-border:#E2E8E5; --gv-border-light:#EFE9DF;
}

.gv-mem { background: var(--gv-cream); }

/* PMPro 3.x adds `pmpro-levels` as a BODY class; membership.css's `.pmpro-levels`
   grid (meant for the legacy wrapper) would turn <body> into a 3-col grid and
   shrink-wrap our <main>. Reset it here at higher specificity. */
body.pmpro-levels { display: block; }

/* ── Intro ── */
.gv-mem-intro {
    max-width: 720px;
    margin: 0 auto 2.75rem;
    text-align: center;
}
.gv-mem-intro h2 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.1rem);
    font-weight: 500; color: var(--gv-charcoal);
    margin: 0 0 0.75rem;
}
.gv-mem-intro h2 em { font-style: italic; color: var(--gv-gold); }
.gv-mem-intro p { font-size: 1rem; color: var(--gv-mist); line-height: 1.7; margin: 0; }

/* ── Billing tag switcher hint ── */
.gv-mem-note {
    display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    font-size: 0.82rem; color: var(--gv-mist); text-align: center;
    margin-top: 1.25rem;
}
.gv-mem-note svg { width: 15px; height: 15px; color: var(--gv-gold); flex-shrink: 0; }

/* ── Cards ── */
.gv-mem-grid {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 1.75rem; max-width: 1120px; margin: 0 auto 3.5rem;
    align-items: stretch;
}
.gv-mem-card {
    background: #fff; border: 1px solid var(--gv-border-light);
    border-radius: 18px; padding: 2.25rem 2rem;
    display: flex; flex-direction: column;
    box-shadow: 0 2px 8px rgba(28,43,40,0.04);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    position: relative;
}
.gv-mem-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 18px 40px rgba(13,92,82,0.10);
    border-color: rgba(13,92,82,0.25);
}
.gv-mem-card--featured {
    border: 2px solid var(--gv-gold); background: linear-gradient(180deg, #FDFAF4, #FFFDF8);
}
.gv-mem-card--featured:hover { border-color: var(--gv-gold); }

.gv-mem-ribbon {
    position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
    background: var(--gv-gold); color: var(--gv-teal-deep);
    font-size: 0.62rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;
    padding: 0.35rem 1rem; border-radius: 999px; white-space: nowrap;
    box-shadow: 0 4px 12px rgba(201,169,110,0.35);
}

.gv-mem-tier { align-self: flex-start; margin-bottom: 1rem; }
.gv-mem-tier span {
    display: inline-block; padding: 0.3rem 0.8rem; border-radius: 999px;
    font-size: 0.62rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
}
.gv-mem-tier-free { background: #E8F5F0; color: var(--gv-teal); }
.gv-mem-tier-active { background: rgba(201,169,110,0.16); color: #9a7b3f; }
.gv-mem-tier-premium { background: var(--gv-teal-deep); color: var(--gv-cream); }

.gv-mem-ico {
    width: 54px; height: 54px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    background: #E8F5F0; color: var(--gv-teal); margin-bottom: 1.25rem;
}
.gv-mem-card--featured .gv-mem-ico { background: rgba(201,169,110,0.16); color: #9a7b3f; }
.gv-mem-ico svg { width: 26px; height: 26px; }

.gv-mem-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.45rem; font-weight: 600; color: var(--gv-charcoal);
    margin: 0 0 0.35rem;
}
.gv-mem-desc { font-size: 0.86rem; color: var(--gv-mist); line-height: 1.6; margin: 0 0 1.4rem; }

.gv-mem-price-row { display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 1.5rem; }
.gv-mem-price {
    font-family: 'Playfair Display', serif;
    font-size: 2.1rem; font-weight: 700; color: var(--gv-charcoal); line-height: 1;
}
.gv-mem-card--featured .gv-mem-price { color: var(--gv-teal-deep); }
.gv-mem-period { font-size: 0.82rem; color: var(--gv-mist); }

.gv-mem-feats { list-style: none; padding: 0; margin: 0 0 2rem; flex: 1; }
.gv-mem-feats li {
    position: relative; padding: 0.45rem 0 0.45rem 1.9rem;
    font-size: 0.9rem; color: var(--gv-charcoal); line-height: 1.45;
}
.gv-mem-feats li::before {
    content: ''; position: absolute; left: 0; top: 0.55rem;
    width: 20px; height: 20px; border-radius: 50%;
    background: rgba(13,92,82,0.08); color: var(--gv-teal);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.65rem; font-weight: 700;
}
.gv-mem-feats li::after {
    content: '✓'; position: absolute; left: 0; top: 0.55rem;
    width: 20px; height: 20px; line-height: 20px; text-align: center;
    color: var(--gv-teal); font-size: 0.65rem; font-weight: 700;
}
.gv-mem-card--featured .gv-mem-feats li::before { background: rgba(201,169,110,0.16); }
.gv-mem-card--featured .gv-mem-feats li::after { color: #9a7b3f; }

.gv-mem-btn {
    display: block; width: 100%; text-align: center;
    padding: 0.9rem 1.5rem; border-radius: 10px;
    background: var(--gv-teal); color: var(--gv-cream);
    font-family: 'Jost', sans-serif; font-size: 0.82rem; font-weight: 600;
    letter-spacing: 0.1em; text-transform: uppercase; text-decoration: none;
    transition: all .2s ease;
}
.gv-mem-btn:hover { background: var(--gv-teal-deep); color: var(--gv-cream); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(13,92,82,0.25); }
.gv-mem-card--featured .gv-mem-btn { background: var(--gv-gold); color: var(--gv-teal-deep); }
.gv-mem-card--featured .gv-mem-btn:hover { background: #b8955e; color: var(--gv-teal-deep); }
.gv-mem-btn-ghost { background: transparent; border: 1.5px solid rgba(13,92,82,0.35); color: var(--gv-teal); letter-spacing: 0.06em; margin-top: 0.6rem; font-size: 0.75rem; }
.gv-mem-btn-ghost:hover { background: #E8F5F0; color: var(--gv-teal); box-shadow: none; transform: none; }

/* ── Trust strip ── */
.gv-mem-trust {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem; max-width: 900px; margin: 0 auto 4rem; padding-top: 2.5rem;
    border-top: 1px solid var(--gv-border);
}
.gv-mem-trust-stat { text-align: center; }
.gv-mem-trust-n {
    font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700;
    color: var(--gv-teal); margin-bottom: 0.2rem;
}
.gv-mem-trust-l { font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--gv-mist); }

/* ── FAQ ── */
.gv-mem-faq { max-width: 800px; margin: 0 auto 4rem; }
.gv-mem-faq h3 {
    font-family: 'Playfair Display', serif; font-size: 1.7rem; font-weight: 500;
    color: var(--gv-charcoal); text-align: center; margin: 0 0 1.75rem;
}
.gv-mem-faq h3 em { font-style: italic; color: var(--gv-gold); }
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
.gv-mem-cta {
    background: linear-gradient(120deg, var(--gv-teal-deep), var(--gv-teal));
    border-radius: 18px; color: var(--gv-cream);
    max-width: 1000px; margin: 0 auto;
    padding: 3rem 2.5rem; text-align: center;
    position: relative; overflow: hidden;
}
.gv-mem-cta::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(600px 200px at 85% -20%, rgba(201,169,110,0.35), transparent 60%);
    pointer-events: none;
}
.gv-mem-cta h3 {
    position: relative; font-family: 'Playfair Display', serif;
    font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 500; margin: 0 0 0.6rem;
}
.gv-mem-cta p { position: relative; font-size: 0.95rem; color: rgba(250,247,242,0.75); line-height: 1.6; margin: 0 auto 1.75rem; max-width: 560px; }
.gv-mem-cta-actions { position: relative; display: flex; gap: 0.9rem; justify-content: center; flex-wrap: wrap; }
.gv-mem-cta-btn {
    display: inline-block; padding: 0.95rem 2rem; border-radius: 10px;
    background: var(--gv-gold); color: var(--gv-teal-deep);
    font-family: 'Jost', sans-serif; font-size: 0.84rem; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase; text-decoration: none;
    transition: all .2s ease;
}
.gv-mem-cta-btn:hover { background: #b8955e; transform: translateY(-1px); }
.gv-mem-cta-btn--ghost { background: transparent; border: 1.5px solid rgba(250,247,242,0.4); color: var(--gv-cream); }
.gv-mem-cta-btn--ghost:hover { background: rgba(250,247,242,0.08); }

/* ── Responsive ── */
@media (max-width: 900px) {
    .gv-mem-grid { grid-template-columns: 1fr; max-width: 480px; }
    .gv-mem-trust { grid-template-columns: repeat(3, 1fr); gap: 1rem; }
}
@media (max-width: 560px) {
    .gv-mem-trust { grid-template-columns: 1fr; gap: 1.25rem; }
    .gv-mem-card { padding: 1.75rem 1.4rem; }
}
</style>

<main class="gv-mem">

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Membership Levels</div>
        <h1>Choose Your <em>Membership</em></h1>
        <p>Every project starts with clarity. Pick the level that fits where you are — upgrade anytime as your goals grow.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container">

        <div class="gv-mem-intro">
            <h2>Simple Plans, <em>Serious Progress</em></h2>
            <p>Join as a free member to unlock your portal and readiness score — then level up when you're ready to move faster.</p>
            <div class="gv-mem-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8h2M12 12h2M12 16h2"/></svg>
                <span>Easily upgrade or change your plan at any time from your dashboard.</span>
            </div>
        </div>

        <?php if (!empty($gv_levels)) : ?>
        <div class="gv-mem-grid">
            <?php
            $gv_order = array();
            foreach ($gv_levels as $gv_l) {
                $gv_order[$gv_l->id] = $gv_l;
            }
            /* Present 1, 2, 3 by id; 2 featured */
            $gv_sequence = array(1, 2, 3);
            foreach ($gv_sequence as $gv_lid) :
                if (!isset($gv_order[$gv_lid])) continue;
                $gv_l = $gv_order[$gv_lid];
                $gv_f  = ($gv_lid === 2);
                $is_free = ((float)$gv_l->initial_payment <= 0 && (float)$gv_l->billing_amount <= 0);
            ?>
            <div class="gv-mem-card<?php echo $gv_f ? ' gv-mem-card--featured' : ''; ?>">
                <?php if ($gv_f) : ?><div class="gv-mem-ribbon">Most Popular</div><?php endif; ?>

                <div class="gv-mem-tier"><span class="gv-mem-tier-<?php echo $gv_lid === 1 ? 'free' : ($gv_lid === 2 ? 'active' : 'premium'); ?>"><?php echo esc_html($gv_badges[$gv_lid] ?? $gv_l->name); ?></span></div>

                <div class="gv-mem-ico"><?php echo $gv_icons[$gv_lid] ?? $gv_icons[1]; ?></div>

                <h2 class="gv-mem-name"><?php echo esc_html($gv_l->name); ?></h2>
                <p class="gv-mem-desc"><?php echo esc_html($gv_l->description); ?></p>

                <div class="gv-mem-price-row">
                    <div class="gv-mem-price"><?php echo esc_html(gv_level_price($gv_l)); ?></div>
                    <div class="gv-mem-period"><?php echo esc_html(gv_level_period($gv_l)); ?></div>
                </div>

                <ul class="gv-mem-feats">
                    <?php
                    $gv_fl = $gv_benefits[$gv_lid] ?? array();
                    if (empty($gv_fl)) {
                        $gv_fl = array_filter(array_map('trim', explode("\n", $gv_l->description)));
                    }
                    foreach ($gv_fl as $gv_feat) : ?>
                    <li><?php echo esc_html($gv_feat); ?></li>
                    <?php endforeach; ?>
                </ul>

                <a class="gv-mem-btn" href="<?php echo esc_url(home_url('/membership-checkout/?pmpro_level=' . $gv_lid)); ?>">
                    <?php echo $is_free ? 'Create Free Account' : 'Get Started'; ?>
                </a>
                <?php if (!$is_free) : ?>
                    <a class="gv-mem-btn gv-mem-btn-ghost" href="<?php echo esc_url(home_url('/contact/')); ?>">Book a Consultation First</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Trust strip -->
        <div class="gv-mem-trust">
            <div class="gv-mem-trust-stat">
                <div class="gv-mem-trust-n">50+</div>
                <div class="gv-mem-trust-l">Projects Guided</div>
            </div>
            <div class="gv-mem-trust-stat">
                <div class="gv-mem-trust-n">100%</div>
                <div class="gv-mem-trust-l">Transparent Pricing</div>
            </div>
            <div class="gv-mem-trust-stat">
                <div class="gv-mem-trust-n">24h</div>
                <div class="gv-mem-trust-l">Response Time</div>
            </div>
        </div>

        <?php else : ?>
        <div class="mem-empty-state">
            <h3>Levels Coming Soon</h3>
            <p>Our membership plans are being finalised. Please check back shortly.</p>
        </div>
        <?php endif; ?>

        <!-- FAQ -->
        <div class="gv-mem-faq">
            <h3>Membership <em>FAQ</em></h3>

            <div class="gv-faq-item">
                <button class="gv-faq-q" type="button"><span>Which plan is right for me?</span><span class="gv-faq-chev">▾</span></button>
                <div class="gv-faq-a"><div class="gv-faq-a-inner">Start with Free Lead — it gives you your private portal and Development Readiness Score at no cost. If you're actively building, Active Client unlocks the full dashboard, design collaboration, and budget tools. Premium Service is for clients who want managed delivery, interior concepts, and a dedicated account manager.</div></div>
            </div>

            <div class="gv-faq-item">
                <button class="gv-faq-q" type="button"><span>Can I upgrade or change my plan later?</span><span class="gv-faq-chev">▾</span></button>
                <div class="gv-faq-a"><div class="gv-faq-a-inner">Yes — change or upgrade your plan anytime from your membership account. Upgrades take effect immediately and there's no setup fee or long-term lock-in.</div></div>
            </div>

            <div class="gv-faq-item">
                <button class="gv-faq-q" type="button"><span>What payment methods do you accept?</span><span class="gv-faq-chev">▾</span></button>
                <div class="gv-faq-a"><div class="gv-faq-a-inner">We support secure online payments designed for Ghana — including mobile money and card payments. Your invoice is generated automatically and stored in your account.</div></div>
            </div>

            <div class="gv-faq-item">
                <button class="gv-faq-q" type="button"><span>Why do I need a membership for the portal?</span><span class="gv-faq-chev">▾</span></button>
                <div class="gv-faq-a"><div class="gv-faq-a-inner">Your membership personalises your Portal — it tracks your readiness score, stores your project documents, and lets our team serve you faster. The Free Lead tier keeps all of that accessible with no cost barrier to starting.</div></div>
            </div>
        </div>

        <!-- CTA -->
        <div class="gv-mem-cta">
            <h3>Not sure where your project starts?</h3>
            <p>Take the 2-minute Development Readiness Quiz and get a free, instant assessment of what your project needs first.</p>
            <div class="gv-mem-cta-actions">
                <a class="gv-mem-cta-btn" href="<?php echo esc_url(home_url('/development-quiz/')); ?>">Take the Readiness Quiz</a>
                <a class="gv-mem-cta-btn gv-mem-cta-btn--ghost" href="<?php echo esc_url(home_url('/contact/')); ?>">Talk to Our Team</a>
            </div>
        </div>

    </div>
</section>

</main>

<script>
(function () {
    var items = document.querySelectorAll('.gv-faq-item');
    items.forEach(function (it) {
        var q = it.querySelector('.gv-faq-q');
        var a = it.querySelector('.gv-faq-a');
        q.addEventListener('click', function () {
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