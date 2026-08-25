<?php
/**
 * Template Name: About
 * VDAC about.html port — story + principles + per-page CTA band
 * @package Gabochie_Value
 */
get_header();

$clarity   = gabochie_value_clarity_url();
$phone     = gabochie_value_phone();
$phone_tel = gabochie_value_phone_tel();
$whatsapp  = gabochie_value_whatsapp();
$wa_base   = 'https://wa.me/' . $whatsapp . '?text=';
?>

<main id="main">

<!-- ========== HERO ========== -->
<section class="hero hero--solo" id="top">
  <div class="hero-left">
    <div class="hero-location"><span class="hero-location-dot"></span> Sakumono &middot; Tema &mdash; Ghana</div>
    <h1 class="hero-h1">The consultancy behind <em>the promise.</em></h1>
    <div class="hero-h1-dim">Built on trust, evidence and long-term thinking</div>
    <div class="hero-rule"></div>
    <p class="hero-sub">Gabochie Design is a development facilitation and design consultancy that believes the best projects begin with honest answers &mdash; not beautiful drawings. We help clients build the right thing, control cost honestly, and protect their interest from brief to handover.</p>
    <div class="hero-actions">
      <a class="btn-gold" href="<?php echo esc_url($clarity); ?>">Start Here</a>
      <a class="btn-ghost-w" href="<?php echo esc_url(gabochie_value_section_url('services')); ?>">See what we do</a>
    </div>
    <div class="hero-note">Prefer to talk? Call or WhatsApp <?php echo esc_html($phone); ?></div>
  </div>
</section>

<!-- ========== STORY ========== -->
<section class="value" id="story">
  <div class="container">
    <div class="sec-ey"><span>Our story</span></div>
    <h2 class="sec-h2 reveal">Why we exist.</h2>
    <p class="sec-lead reveal">We started with a simple observation: too many building projects in Ghana fail for reasons that were never about the drawings. They fail on bad briefs, missing feasibility, uncontrolled cost and broken trust.</p>
    <div class="value-pillars">
      <div class="vp reveal"><div class="vp-n">01</div><h3>From ideas to outcomes</h3><p>Our work runs from the first sketch to the last handover &mdash; and beyond. Design is one chapter of a longer story.</p></div>
      <div class="vp reveal reveal-delay-1"><div class="vp-n">02</div><h3>Grounded in Ghana</h3><p>We work where our clients build: Sakumono, Tema, Accra and across Ghana &mdash; with a special focus on the diaspora.</p></div>
      <div class="vp reveal reveal-delay-2"><div class="vp-n">03</div><h3>Value is the discipline</h3><p>Every recommendation is tested against one question: does this protect or create value for the client? If not, it doesn't survive.</p></div>
    </div>
    <div class="value-quote reveal">
      <blockquote>We don't simply ask, "What should we design?" We ask: what should you build, why should you build it, what should it cost &mdash; and how do we help you get the best possible outcome?</blockquote>
      <div>
        <p>Better decisions happen when design, cost, feasibility and delivery sit under one roof. That's what we do &mdash; whether you're in Accra or abroad.</p>
        <a class="btn-ghost-w" href="<?php echo esc_url($clarity); ?>" style="margin-top:1.5rem;display:inline-block">Start Here</a>
      </div>
    </div>
  </div>
</section>

<!-- ========== PRINCIPLES ========== -->
<section class="trust" id="principles">
  <div class="container">
    <div class="sec-ey"><span>What we stand for</span></div>
    <h2 class="sec-h2 reveal">Three promises we <em>keep on paper.</em></h2>
    <p class="sec-lead reveal">Not slogans &mdash; commitments that shape how we work, what we document and how you experience every engagement.</p>
    <div class="trust-grid">
      <div class="trust-item reveal">
        <div class="t-ico"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <h3>Accountable from brief to handover</h3>
        <p>Every deliverable is documented, reviewed and signed. You own what we produce &mdash; no vague promises, no missing records.</p>
      </div>
      <div class="trust-item reveal reveal-delay-1">
        <div class="t-ico"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
        <h3>Total transparency</h3>
        <p>You always know what's happening, what's next and what it costs &mdash; whether you're in Accra or abroad. No information asymmetry.</p>
      </div>
      <div class="trust-item reveal reveal-delay-2">
        <div class="t-ico"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <h3>Cost-aware by design</h3>
        <p>Feasibility and cost intelligence sit inside the design conversation from the first sketch &mdash; not as an afterthought when the money is gone.</p>
      </div>
    </div>
    <div class="trust-note reveal"><strong>About our case studies:</strong> we are documenting real projects and client references, and will publish them as they are cleared. Until then, we let the process and the documentation speak.</div>
  </div>
</section>

<?php
get_template_part('inc/template-parts/cta-band', null, array(
    'image'   => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=1200&q=80',
    'alt'     => 'Gabochie Design principal',
    'eyebrow' => 'Take the first step',
    'title'   => 'Let\'s test what we <em>stand for.</em>',
    'sub'     => 'Bring us your idea, your stalled project or your questions. You\'ll leave with a direction &mdash; and real information, whether or not you work with us.',
    'actions' => array(
        array('class' => 'btn-gold', 'href' => $clarity, 'text' => 'Start Here'),
        array('class' => 'whatsapp-btn', 'href' => $wa_base . 'Hello%20Gabochie%20Design%20%E2%80%94%20from%20your%20website.', 'text' => 'WhatsApp Us', 'icon' => 'whatsapp'),
        array('class' => 'btn-ghost-w', 'href' => 'tel:' . $phone_tel, 'text' => 'Call ' . $phone),
    ),
));

get_footer();
?>
