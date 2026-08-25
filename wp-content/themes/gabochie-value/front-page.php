<?php
/**
 * Front page template — all VDAC homepage sections
 * IDs (wizard/FAQ/reveal) preserved 1:1 with assets/js/main.js
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
<section class="hero" id="top">
  <div class="hero-left">
    <div class="hero-location"><span class="hero-location-dot"></span> Sakumono &middot; Tema &mdash; Ghana</div>
    <h1 class="hero-h1">Your building journey does not have to be <em>confusing.</em></h1>
    <div class="hero-h1-dim">Value-Driven Development Facilitation</div>
    <div class="hero-rule"></div>
    <p class="hero-sub">Land, an idea, drawings, or a half-built project &mdash; we help you make the right decision at every stage, from first idea to completed project and beyond. No jargon. No pressure.</p>
    <div class="hero-actions">
      <a class="btn-gold" href="<?php echo esc_url($clarity); ?>">Get Clarity</a>
      <a class="btn-ghost-w" href="<?php echo esc_url(gabochie_value_section_url('journey')); ?>">Explore the journey</a>
    </div>
    <div class="hero-note">Prefer to talk? Call or WhatsApp <?php echo esc_html($phone); ?></div>
    <?php if (!is_user_logged_in()) : ?>
    <div class="hero-note" style="margin-top:0.5rem;font-size:0.78rem"><a href="<?php echo esc_url(home_url('/login/')); ?>" style="color:var(--gold);text-decoration:underline">Already a client? Sign in to your dashboard</a></div>
    <?php else : ?>
    <div class="hero-note" style="margin-top:0.5rem;font-size:0.78rem"><a href="<?php echo esc_url(home_url('/dashboard/')); ?>" style="color:var(--gold);text-decoration:underline">Go to your dashboard</a></div>
    <?php endif; ?>
    <div class="hero-stats">
      <div><div class="stat-n">50<sup>+</sup></div><div class="stat-l">Projects guided from idea to handover</div></div>
      <div><div class="stat-n">10</div><div class="stat-l">Stages on your building journey</div></div>
      <div><div class="stat-n">100%</div><div class="stat-l">Transparent, documented process</div></div>
    </div>
  </div>
  <div class="hero-art">
    <img src="<?php echo esc_url(GABOCHIE_VALUE_URI . '/assets/images/hero.jpg'); ?>" alt="Gabochie Design architectural project" width="540" height="440" fetchpriority="high">
    <div class="hero-art-overlay">
      <span>Development advisory &middot; Architecture practice</span>
      <strong>Gabochie Design</strong>
    </div>
  </div>
</section>

<!-- ========== TICKER ========== -->
<div class="ticker" aria-hidden="true">
  <div class="ticker-inner">
    <span class="t-item">Value-Driven Development</span>
    <span class="t-item">Feasibility First</span>
    <span class="t-item">Cost Transparency</span>
    <span class="t-item">Join at Any Stage</span>
    <span class="t-item">Diaspora-Friendly</span>
    <span class="t-item">Site &amp; Development Intelligence</span>
    <span class="t-item">Development That Creates Value</span>
    <span class="t-item">Sakumono &middot; Tema &middot; Ghana</span>
    <span class="t-item">Value-Driven Development</span>
    <span class="t-item">Feasibility First</span>
    <span class="t-item">Cost Transparency</span>
    <span class="t-item">Join at Any Stage</span>
    <span class="t-item">Diaspora-Friendly</span>
    <span class="t-item">Site &amp; Development Intelligence</span>
    <span class="t-item">Development That Creates Value</span>
    <span class="t-item">Sakumono &middot; Tema &middot; Ghana</span>
  </div>
</div>

<!-- ========== ALONE ========== -->
<section class="alone" id="why">
  <div class="container">
    <div class="sec-ey"><span>Why clients need us</span></div>
    <h2 class="sec-h2 reveal">You don't have to figure this out <em>alone.</em></h2>
    <p class="sec-lead reveal">Building is not one decision. It's a chain of decisions &mdash; and a mistake early in the chain becomes expensive later.</p>
    <p class="sec-lead reveal">Most people navigate land, use, vision, design, cost, approval, contractor and construction with no one who has seen the whole chain. We have. We help you make better decisions before problems become expensive.</p>
    <div class="strip-wrap reveal">
      <div class="strip">
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Land</div></div>
        <span class="strip-arrow">&rsaquo;</span>
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Use</div></div>
        <span class="strip-arrow">&rsaquo;</span>
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Vision</div></div>
        <span class="strip-arrow">&rsaquo;</span>
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Design</div></div>
        <span class="strip-arrow">&rsaquo;</span>
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Cost</div></div>
        <span class="strip-arrow">&rsaquo;</span>
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Approval</div></div>
        <span class="strip-arrow">&rsaquo;</span>
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Contractor</div></div>
        <span class="strip-arrow">&rsaquo;</span>
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Construction</div></div>
        <span class="strip-arrow">&rsaquo;</span>
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Quality</div></div>
        <span class="strip-arrow">&rsaquo;</span>
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Handover</div></div>
        <span class="strip-arrow">&rsaquo;</span>
        <div class="strip-node"><div class="s-dot"></div><div class="s-label">Long-Term Value</div></div>
      </div>
    </div>
    <div class="hero-actions" style="margin-top:2.5rem">
      <a class="btn-solid" href="<?php echo esc_url($clarity); ?>">Get Clarity</a>
      <span class="reassure-line">No pressure. No jargon. You'll leave with a direction.</span>
    </div>
  </div>
</section>

<?php get_template_part('inc/template-parts/wizard'); ?>

<!-- ========== VALUE ========== -->
<section class="value" id="value">
  <div class="container">
    <div class="sec-ey"><span>Our value proposition</span></div>
    <h2 class="sec-h2 reveal">Value-driven is a <em>discipline,</em> not a slogan.</h2>
    <p class="sec-lead reveal">We don't simply ask, "What should we design?" We ask: what should you build, why should you build it, what should it cost &mdash; and how do we help you get the best possible outcome?</p>
    <div class="value-pillars">
      <div class="vp reveal"><div class="vp-n">01</div><h3>Build the right thing</h3><p>Feasibility, site and brief come first &mdash; so you never pay to build the wrong project.</p></div>
      <div class="vp reveal reveal-delay-1"><div class="vp-n">02</div><h3>Control cost honestly</h3><p>Cost intelligence is part of the design conversation from the first sketch, not a surprise at the end.</p></div>
      <div class="vp reveal reveal-delay-2"><div class="vp-n">03</div><h3>Protect your interest</h3><p>A client-first decision framework, documented governance and one accountable team from brief to handover.</p></div>
    </div>
    <div class="value-quote reveal">
      <blockquote>Making better use of your capital &mdash; building the right thing, avoiding preventable mistakes, and protecting the long-term value of the property.</blockquote>
      <div>
        <p>Better decisions happen when design, cost, feasibility and delivery sit under one roof. That's what we do &mdash; whether you're in Accra or abroad.</p>
        <a class="btn-ghost-w" href="<?php echo esc_url($clarity); ?>" style="margin-top:1.5rem;display:inline-block">Get Clarity</a>
      </div>
    </div>
  </div>
</section>

<!-- ========== JOURNEY ========== -->
<section class="journey" id="journey">
  <div class="container">
    <div class="sec-ey"><span>Your building journey</span></div>
    <h2 class="sec-h2 reveal">You can join the journey <em>anywhere.</em></h2>
    <p class="sec-lead reveal">You don't have to start at the beginning. If you're already somewhere in the journey, we'll meet you there.</p>
    <div class="stage-track">
      <div class="stage-row">
        <article class="stage"><div class="st-n">01</div><h3 class="st-t">Dream</h3><dl><dt>What clients usually need</dt><dd>Turn a vague ambition into something real.</dd><dt>How we help</dt><dd>Dream &amp; Vision Clarity &mdash; structured discovery, brief, value definition.</dd><dt>Typical next decision</dt><dd>Decide what to build, and why.</dd></dl><a class="st-cta" href="<?php echo esc_url($clarity); ?>">Get Clarity</a></article>
        <article class="stage"><div class="st-n">02</div><h3 class="st-t">Explore</h3><dl><dt>What clients usually need</dt><dd>Understand what the site and idea can support.</dd><dt>How we help</dt><dd>Site &amp; Development Potential &mdash; constraints, opportunities, yield.</dd><dt>Typical next decision</dt><dd>Confirm the development route.</dd></dl><a class="st-cta" href="<?php echo esc_url($clarity); ?>">Get Clarity</a></article>
        <article class="stage"><div class="st-n">03</div><h3 class="st-t">Validate</h3><dl><dt>What clients usually need</dt><dd>Know if the project is worth starting.</dd><dt>How we help</dt><dd>Feasibility &amp; Project Viability &mdash; costs, returns, risk.</dd><dt>Typical next decision</dt><dd>Set the budget and proceed or stop.</dd></dl><a class="st-cta" href="<?php echo esc_url($clarity); ?>">Get Clarity</a></article>
        <article class="stage"><div class="st-n">04</div><h3 class="st-t">Plan</h3><dl><dt>What clients usually need</dt><dd>A roadmap that sequences decisions and money.</dd><dt>How we help</dt><dd>Development Strategy &mdash; phased programme, governance.</dd><dt>Typical next decision</dt><dd>Approve the development plan.</dd></dl><a class="st-cta" href="<?php echo esc_url($clarity); ?>">Get Clarity</a></article>
        <article class="stage"><div class="st-n">05</div><h3 class="st-t">Design</h3><dl><dt>What clients usually need</dt><dd>Drawings that are buildable and right-sized.</dd><dt>How we help</dt><dd>Architectural Design &mdash; concept to detailed design, BIM.</dd><dt>Typical next decision</dt><dd>Approve the design for pricing.</dd></dl><a class="st-cta" href="<?php echo esc_url($clarity); ?>">Get Clarity</a></article>
        <article class="stage"><div class="st-n">06</div><h3 class="st-t">Price</h3><dl><dt>What clients usually need</dt><dd>A credible, honest cost picture.</dd><dt>How we help</dt><dd>Cost &amp; Value Intelligence &mdash; estimates, value engineering.</dd><dt>Typical next decision</dt><dd>Lock the budget and procurement route.</dd></dl><a class="st-cta" href="<?php echo esc_url($clarity); ?>">Get Clarity</a></article>
        <article class="stage"><div class="st-n">07</div><h3 class="st-t">Approve</h3><dl><dt>What clients usually need</dt><dd>Permits and approvals without surprises.</dd><dt>How we help</dt><dd>Planning &amp; Approval Support &mdash; documents, submissions.</dd><dt>Typical next decision</dt><dd>Receive approvals and start procurement.</dd></dl><a class="st-cta" href="<?php echo esc_url($clarity); ?>">Get Clarity</a></article>
        <article class="stage"><div class="st-n">08</div><h3 class="st-t">Build</h3><dl><dt>What clients usually need</dt><dd>A build that matches the drawings and budget.</dd><dt>How we help</dt><dd>Project Delivery &amp; Construction Administration.</dd><dt>Typical next decision</dt><dd>Appoint contractor and begin construction.</dd></dl><a class="st-cta" href="<?php echo esc_url($clarity); ?>">Get Clarity</a></article>
        <article class="stage"><div class="st-n">09</div><h3 class="st-t">Handover</h3><dl><dt>What clients usually need</dt><dd>A complete, commissioned, documented project.</dd><dt>How we help</dt><dd>Handover &amp; Commissioning &mdash; snagging, manuals, as-builts.</dd><dt>Typical next decision</dt><dd>Take keys with confidence.</dd></dl><a class="st-cta" href="<?php echo esc_url($clarity); ?>">Get Clarity</a></article>
        <article class="stage"><div class="st-n">10</div><h3 class="st-t">Optimize</h3><dl><dt>What clients usually need</dt><dd>Keep the asset performing over time.</dd><dt>How we help</dt><dd>Asset Review, Renovation &amp; Expansion &mdash; long-term value.</dd><dt>Typical next decision</dt><dd>Plan the next cycle.</dd></dl><a class="st-cta" href="<?php echo esc_url($clarity); ?>">Get Clarity</a></article>
      </div>
    </div>
    <div class="journey-banner reveal">
      <p>You don't have to start at the beginning. <em>We'll meet you where you are.</em></p>
      <a class="btn-gold" href="<?php echo esc_url($clarity); ?>">Get Clarity</a>
    </div>
  </div>
</section>

<!-- ========== SERVICES ========== -->
<section class="services" id="services">
  <div class="container">
    <div class="sec-ey"><span>Services</span></div>
    <h2 class="sec-h2 reveal">Four phases. <em>Fourteen ways to move forward.</em></h2>
    <p class="sec-lead reveal">Built around your problems, not conventional disciplines. Enter at any stage.</p>

    <!-- PHASE 01 -->
    <div class="phase reveal">
      <div class="phase-head"><span class="ph-n">Phase 01</span><span class="ph-line"></span><h3>Clarify &amp; Define</h3></div>
      <div class="svc-grid">
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">01</div><h3 class="svc-title">Dream &amp; Vision Clarity</h3><p class="svc-desc">We structure what you actually want &mdash; before anything is drawn.</p><span class="svc-when">An idea, no brief</span><span class="svc-cta">Get started &rarr;</span></a>
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">02</div><h3 class="svc-title">Site &amp; Development Potential</h3><p class="svc-desc">What your land can honestly support, and what it's worth developing into.</p><span class="svc-when">You have land</span><span class="svc-cta">Get started &rarr;</span></a>
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">03</div><h3 class="svc-title">Feasibility &amp; Project Viability</h3><p class="svc-desc">Cost, return and risk tested before you spend real money.</p><span class="svc-when">Before you commit</span><span class="svc-cta">Get started &rarr;</span></a>
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">04</div><h3 class="svc-title">Development Strategy</h3><p class="svc-desc">A sequenced plan that aligns your money, timeline and goals.</p><span class="svc-when">Multiple decisions ahead</span><span class="svc-cta">Get started &rarr;</span></a>
      </div>
    </div>

    <!-- PHASE 02 -->
    <div class="phase reveal">
      <div class="phase-head"><span class="ph-n">Phase 02</span><span class="ph-line"></span><h3>Design &amp; Document</h3></div>
      <div class="svc-grid">
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">05</div><h3 class="svc-title">Architectural Design</h3><p class="svc-desc">Right-sized, buildable architecture &mdash; concept to detailed drawings.</p><span class="svc-when">Ready to design</span><span class="svc-cta">Get started &rarr;</span></a>
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">06</div><h3 class="svc-title">BIM &amp; Digital Project Information</h3><p class="svc-desc">Coordinated digital documentation that reduces site surprises.</p><span class="svc-when">Drawings matter</span><span class="svc-cta">Get started &rarr;</span></a>
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">07</div><h3 class="svc-title">Cost &amp; Value Intelligence</h3><p class="svc-desc">Honest cost estimates and value engineering from day one.</p><span class="svc-when">Budget is a concern</span><span class="svc-cta">Get started &rarr;</span></a>
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">08</div><h3 class="svc-title">Planning &amp; Approval Support</h3><p class="svc-desc">Permit strategy, drawings and submissions that get through.</p><span class="svc-when">Approvals are due</span><span class="svc-cta">Get started &rarr;</span></a>
      </div>
    </div>

    <!-- PHASE 03 -->
    <div class="phase reveal">
      <div class="phase-head"><span class="ph-n">Phase 03</span><span class="ph-line"></span><h3>Procure &amp; Deliver</h3></div>
      <div class="svc-grid">
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">09</div><h3 class="svc-title">Procurement &amp; Contractor Selection</h3><p class="svc-desc">A fair, documented process to choose the right contractor.</p><span class="svc-when">Picking a builder</span><span class="svc-cta">Get started &rarr;</span></a>
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">10</div><h3 class="svc-title">Project Delivery &amp; Construction Administration</h3><p class="svc-desc">Construction oversight that keeps the build honest.</p><span class="svc-when">Building now</span><span class="svc-cta">Get started &rarr;</span></a>
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">11</div><h3 class="svc-title">Owner's Representation</h3><p class="svc-desc">Your eyes and voice on site &mdash; even from abroad.</p><span class="svc-when">You can't be there</span><span class="svc-cta">Get started &rarr;</span></a>
      </div>
    </div>

    <!-- PHASE 04 -->
    <div class="phase reveal">
      <div class="phase-head"><span class="ph-n">Phase 04</span><span class="ph-line"></span><h3>Recover &amp; Optimize</h3></div>
      <div class="svc-grid">
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">12</div><h3 class="svc-title">Project Recovery / Unsticking</h3><p class="svc-desc">Diagnose what's wrong, fix what can be saved.</p><span class="svc-when">Project is stuck</span><span class="svc-cta">Get started &rarr;</span></a>
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">13</div><h3 class="svc-title">Handover &amp; Commissioning</h3><p class="svc-desc">Snagging, manuals and as-built documents done properly.</p><span class="svc-when">Build is finishing</span><span class="svc-cta">Get started &rarr;</span></a>
        <a class="svc" href="<?php echo esc_url($clarity); ?>"><div class="svc-n">14</div><h3 class="svc-title">Asset Review, Renovation &amp; Expansion</h3><p class="svc-desc">Protect and grow the value of what you already have.</p><span class="svc-when">Extending or upgrading</span><span class="svc-cta">Get started &rarr;</span></a>
      </div>
    </div>

  </div>
</section>

</main>

<!-- ========== TRUST ========== -->
<section class="trust" id="trust">
  <div class="container">
    <div class="sec-ey"><span>Why clients trust us</span></div>
    <h2 class="sec-h2 reveal">Professional practice. <em>Transparent process.</em></h2>
    <p class="sec-lead reveal">Trust comes from clarity, documentation and a process you can see &mdash; not from claims.</p>
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
  </div>
</section>

<!-- ========== TESTIMONIALS ========== -->
<section class="testimonials" id="testimonials">
  <div class="container">
    <div class="sec-ey"><span>What clients say</span></div>
    <h2 class="sec-h2 reveal">Real words from <em>real clients.</em></h2>
    <div class="test-grid">
      <div class="test-card reveal">
        <div class="test-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
        <blockquote>&ldquo;They caught a costing error that would have added &cent;80,000 to our build. The feasibility stage alone paid for their fee three times over.&rdquo;</blockquote>
        <div class="test-author">
          <div class="test-name">Kwame A.</div>
          <div class="test-role">Residential Client &middot; Tema</div>
        </div>
      </div>
      <div class="test-card reveal reveal-delay-1">
        <div class="test-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
        <blockquote>&ldquo;I'm in London and my contractor was in Kumasi. Gabochie Design was my eyes on site &mdash; weekly photo reports, cost tracking, and honest updates. I never felt out of the loop.&rdquo;</blockquote>
        <div class="test-author">
          <div class="test-name">Ama D.</div>
          <div class="test-role">Diaspora Client &middot; London, UK</div>
        </div>
      </div>
      <div class="test-card reveal reveal-delay-2">
        <div class="test-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
        <blockquote>&ldquo;Our project was stuck for 8 months. They diagnosed the issue in two weeks and we were back on site within a month. Saved us from starting over completely.&rdquo;</blockquote>
        <div class="test-author">
          <div class="test-name">Kofi M.</div>
          <div class="test-role">Project Recovery &middot; Accra</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== PROJECTS ========== -->
<section class="projects" id="projects">
  <div class="container">
    <div class="sec-ey"><span>Selected work</span></div>
    <h2 class="sec-h2 reveal">Proof over promises.</h2>
    <p class="sec-lead reveal">Real projects, documented honestly. Case studies are added as they are completed and cleared by clients.</p>
    <div class="proj-grid">
      <div class="proj-card reveal">
        <div class="proj-img">
          <span class="proj-pill">Case study &mdash; in progress</span>
          <img src="https://mir-s3-cdn-cf.behance.net/projects/max_808/e43b66146111057.Y3JvcCw5NzksNzY2LDg3LDA.png" alt="EL-Gibbor Courts 2.0 — residential project" width="640" height="480" loading="lazy">
        </div>
        <div class="proj-body">
          <div class="proj-meta">Residential</div>
          <div class="proj-title">EL-Gibbor Courts 2.0</div>
        </div>
      </div>
      <div class="proj-card reveal reveal-delay-1">
        <div class="proj-img">
          <span class="proj-pill">Case study &mdash; in progress</span>
          <img src="https://mir-s3-cdn-cf.behance.net/projects/max_808/df0d0d141919197.Y3JvcCwxMzgwLDEwODAsMzMxLDA.png" alt="Fuel Station — commercial project" width="640" height="480" loading="lazy">
        </div>
        <div class="proj-body">
          <div class="proj-meta">Commercial</div>
          <div class="proj-title">Fuel Station</div>
        </div>
      </div>
      <div class="proj-card reveal reveal-delay-2">
        <div class="proj-img">
          <span class="proj-pill">Case study &mdash; in progress</span>
          <img src="https://mir-s3-cdn-cf.behance.net/projects/max_808/79e8fc172466769.Y3JvcCwxMzgwLDEwODAsMjcwLDA.png" alt="Lamptey Apartments — commercial and mixed-use project" width="640" height="480" loading="lazy">
        </div>
        <div class="proj-body">
          <div class="proj-meta">Commercial &amp; Mixed-Use</div>
          <div class="proj-title">Lamptey Apartments</div>
        </div>
      </div>
    </div>
    <div class="proj-cta reveal">
      <a class="btn-ghost" href="<?php echo esc_url($clarity); ?>">Discuss your project &rarr;</a>
    </div>
  </div>
</section>

<!-- ========== DIASPORA ========== -->
<section class="diaspora" id="diaspora">
  <div class="container">
    <div class="sec-ey"><span>For the Ghanaian diaspora</span></div>
    <h2 class="sec-h2 reveal">Distance shouldn't mean <em>losing control</em> of your project.</h2>
    <p class="sec-lead reveal">You're investing from abroad, time is limited, and the contractor is a world away. We give you one trusted point of contact on the ground &mdash; and professional reporting that keeps you in control of every decision.</p>
    <div class="dia-grid">
      <div class="dia-text">
        <div class="dia-feats">
          <div class="dia-f reveal">
            <div class="dia-f-ico"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div><div class="dia-f-title">One trusted point of contact</div><div class="dia-f-desc">A single accountable architect who knows your project end to end.</div></div>
          </div>
          <div class="dia-f reveal reveal-delay-1">
            <div class="dia-f-ico"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
            <div><div class="dia-f-title">Professional reporting</div><div class="dia-f-desc">Written updates, photo documentation and decision-ready briefs &mdash; on your schedule.</div></div>
          </div>
          <div class="dia-f reveal reveal-delay-2">
            <div class="dia-f-ico"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <div><div class="dia-f-title">Site verification &amp; representation</div><div class="dia-f-desc">Your eyes and voice on site: quality checks, progress verification, contractor meetings.</div></div>
          </div>
          <div class="dia-f reveal reveal-delay-3">
            <div class="dia-f-ico"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
            <div><div class="dia-f-title">Ghana-specific guidance</div><div class="dia-f-desc">Land, approvals, permits and construction practice &mdash; handled without you needing to learn it.</div></div>
          </div>
        </div>
        <div class="dia-cta">
          <a class="btn-gold" href="<?php echo esc_url($wa_base . 'Hello%20Gabochie%20Design%20%E2%80%94%20I%27m%20abroad%20and%20want%20to%20plan%20my%20Ghana%20project.'); ?>" target="_blank" rel="noopener">Plan Your Ghana Project From Anywhere</a>
        </div>
      </div>
      <div class="dia-map reveal">
        <img src="https://images.unsplash.com/photo-1573164713988-8665fc963095?auto=format&fit=crop&w=900&q=80" alt="Architect reviewing a project progress report on a laptop" width="680" height="560" loading="lazy">
      </div>
    </div>
  </div>
</section>

<!-- ========== RECOVERY ========== -->
<section class="recovery" id="recovery">
  <div class="container">
    <div class="sec-ey"><span>Project recovery</span></div>
    <h2 class="sec-h2 reveal">Already stuck? Don't start over. <em>Get clarity.</em></h2>
    <p class="sec-lead reveal">Stalled designs, exploded budgets, contractor problems, abandoned sites. Before you scrap anything, get a diagnosis.</p>
    <div class="recov-grid">
      <div class="recov-left">
        <h3 class="rec-title">Most stuck projects can be rescued &mdash; and completed for less than starting over.</h3>
        <p>We audit what exists, what's at risk, and what it will take to finish well. You get a straight answer and a decision you can act on.</p>
        <ul class="recov-list">
          <li>Design has stalled</li>
          <li>Budget has exploded</li>
          <li>Contractor problems</li>
          <li>Unclear scope</li>
          <li>Planning problems</li>
          <li>Quality concerns</li>
          <li>Project abandoned</li>
          <li>No visibility from abroad</li>
        </ul>
        <a class="btn-recovery" href="<?php echo esc_url($wa_base . 'Hello%20Gabochie%20Design%20%E2%80%94%20I%20have%20a%20stalled%20project%20and%20need%20a%20diagnostic.'); ?>" target="_blank" rel="noopener">Diagnose My Project</a>
      </div>
      <div class="recov-right reveal">
        <h3>One honest diagnosis. No obligation to continue.</h3>
        <p>A structured review of your drawings, contracts, site and spend &mdash; followed by a clear report: what's wrong, what it costs to fix, and whether continuing beats starting over. The diagnostic is a paid consultation &mdash; fixed fee, clear scope.</p>
      </div>
    </div>
  </div>
</section>

<!-- ========== RESOURCES ========== -->
<section class="resources" id="resources">
  <div class="container">
    <div class="sec-ey"><span>Resources</span></div>
    <h2 class="sec-h2 reveal">Help before you commit.</h2>
    <p class="sec-lead reveal">Practical guides for homeowners, investors and the diaspora &mdash; honest, no-strings resources to help you make better decisions.</p>
    <div class="res-grid">
      <div class="res-card res-featured reveal">
        <div class="r-tag">Free guide</div>
        <h3>The Ghana Building Project Readiness Checklist</h3>
        <p>The 40-point checklist we walk every project through &mdash; before a single cedi is committed.</p>
        <form class="guide-form" id="resForm" novalidate>
          <input type="email" id="resEmail" placeholder="Your email" required aria-label="Email for the readiness checklist">
          <input type="text" name="website" id="resWebsite" tabindex="-1" autocomplete="off" aria-hidden="true" class="gd-hp" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
          <input type="hidden" name="source" value="homepage">
          <button type="submit">Send the guide</button>
        </form>
        <div class="guide-ok" id="resGuideOk">Got it &mdash; the checklist is on its way to your inbox.</div>
        <div class="guide-err" id="resGuideErr" role="alert"></div>
      </div>
      <div class="res-card reveal reveal-delay-1">
        <div class="r-tag">For the diaspora</div>
        <h3>The Diaspora Guide to Building in Ghana</h3>
        <p>How to stay in control of a build from abroad: reporting, site visits, procurement and trust.</p>
        <form class="guide-form notify-form" novalidate>
          <input type="email" placeholder="Your email" required aria-label="Email for diaspora guide">
          <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="gd-hp" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
          <input type="hidden" name="source" value="diaspora-guide">
          <button type="submit">Notify me</button>
        </form>
        <div class="guide-ok" style="display:none">We'll let you know when it's ready.</div>
      </div>
      <div class="res-card reveal reveal-delay-2">
        <div class="r-tag">Before you build</div>
        <h3>10 Costly Mistakes to Avoid Before Building in Ghana</h3>
        <p>The decisions that quietly add cost &mdash; and how to avoid them.</p>
        <form class="guide-form notify-form" novalidate>
          <input type="email" placeholder="Your email" required aria-label="Email for costly mistakes guide">
          <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="gd-hp" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
          <input type="hidden" name="source" value="mistakes-guide">
          <button type="submit">Notify me</button>
        </form>
        <div class="guide-ok" style="display:none">We'll let you know when it's ready.</div>
      </div>
      <div class="res-card reveal reveal-delay-3">
        <div class="r-tag">Budgeting</div>
        <h3>The Ghana Construction Budget Planning Guide</h3>
        <p>How to build a budget that survives contact with reality.</p>
        <form class="guide-form notify-form" novalidate>
          <input type="email" placeholder="Your email" required aria-label="Email for budget guide">
          <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="gd-hp" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
          <input type="hidden" name="source" value="budget-guide">
          <button type="submit">Notify me</button>
        </form>
        <div class="guide-ok" style="display:none">We'll let you know when it's ready.</div>
      </div>
    </div>
  </div>
</section>

<!-- ========== FAQ ========== -->
<section class="faq" id="faq">
  <div class="container">
    <div class="sec-ey"><span>Frequently asked</span></div>
    <h2 class="sec-h2 reveal">Questions we hear often.</h2>
    <div class="faq-list">
      <div class="faq-item">
        <button class="faq-q" aria-expanded="false">I don't know what service I need. <span class="fq-ico">+</span></button>
        <div class="faq-a"><p>You don't need to know &mdash; that's part of our job. The wizard above asks you five questions and recommends the smallest useful next step. If you prefer, call or WhatsApp us directly and we'll guide you from there.</p></div>
      </div>
      <div class="faq-item">
        <button class="faq-q" aria-expanded="false">I'm not in Ghana. Can you still help me? <span class="fq-ico">+</span></button>
        <div class="faq-a"><p>Yes &mdash; a large part of our work supports the Ghanaian diaspora. You get one trusted point of contact, documented reporting and an owner's representative who is your eyes and voice on the ground. Distance shouldn't mean losing control.</p></div>
      </div>
      <div class="faq-item">
        <button class="faq-q" aria-expanded="false">Do you design and build? <span class="fq-ico">+</span></button>
        <div class="faq-a"><p>We are a development facilitation and architectural consultancy, not a contractor. We help you determine what to build, whether it's viable, how it should be designed and costed &mdash; and we coordinate and protect your interest across procurement and construction.</p></div>
      </div>
      <div class="faq-item">
        <button class="faq-q" aria-expanded="false">My project has already started &mdash; can you step in? <span class="fq-ico">+</span></button>
        <div class="faq-a"><p>In most cases, yes. Start with a Project Recovery Diagnostic: we review what exists, what's at risk and what it will take to finish well &mdash; then you decide with real information.</p></div>
      </div>
      <div class="faq-item">
        <button class="faq-q" aria-expanded="false">How does pricing work? <span class="fq-ico">+</span></button>
        <div class="faq-a"><p>Every engagement starts with a clear scope and a fixed proposal &mdash; you'll always know what you're paying for and what you'll receive. Consultations are the first step; the diagnosis of your situation comes before any further commitment.</p></div>
      </div>
      <div class="faq-item">
        <button class="faq-q" aria-expanded="false">What does "value-driven" actually mean? <span class="fq-ico">+</span></button>
        <div class="faq-a"><p>It means we measure our work by the value it protects and creates for you &mdash; better use of capital, fewer preventable mistakes, controlled cost and a property that keeps its value over time. We don't simply ask what to design; we ask what you should build, why, and what it should cost.</p></div>
      </div>
    </div>
    <div class="faq-cta reveal">
      <p>Still have questions?</p>
      <div class="faq-cta-btns">
        <a class="btn-gold" href="<?php echo esc_url($wa_base . 'Hello%20Gabochie%20Design%20%E2%80%94%20I%20have%20a%20question.'); ?>" target="_blank" rel="noopener">WhatsApp Us</a>
        <a class="btn-ghost" href="tel:<?php echo esc_attr($phone_tel); ?>">Call <?php echo esc_html($phone); ?></a>
      </div>
    </div>
  </div>
</section>

<!-- ========== INLINE PROJECT FORM ========== -->
<section class="project-form" id="project-form">
  <div class="container">
    <div class="pf-grid">
      <div class="pf-text">
        <div class="sec-ey"><span>Tell us about your project</span></div>
        <h2 class="sec-h2 reveal">Not ready for the wizard? <em>Start with a message.</em></h2>
        <p class="sec-lead reveal">Drop your details below and we'll get back to you within one working day with a clear next step.</p>
      </div>
      <div class="pf-form-wrap reveal">
        <form class="pf-form" id="projectForm" novalidate>
          <div class="wiz-field">
            <label for="pfName2">Full name <span class="req">*</span></label>
            <input type="text" id="pfName2" name="name" autocomplete="name" required>
          </div>
          <div class="wiz-field">
            <label for="pfEmail2">Email <span class="req">*</span></label>
            <input type="email" id="pfEmail2" name="email" autocomplete="email" required>
          </div>
          <div class="wiz-field">
            <label for="pfPhone2">Phone / WhatsApp</label>
            <input type="tel" id="pfPhone2" name="phone" autocomplete="tel">
          </div>
          <div class="wiz-field">
            <label for="pfMessage2">Tell us about your project</label>
            <textarea id="pfMessage2" name="message" rows="3" placeholder="What are you building? Where is it? What stage are you at?"></textarea>
          </div>
          <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="gd-hp" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
          <input type="hidden" name="source" value="homepage-form">
          <button type="submit" class="btn-gold">Send Message</button>
        </form>
        <div class="guide-ok" id="pfOk" style="display:none;text-align:center;padding:1.5rem">Got it &mdash; we'll be in touch within one working day.</div>
      </div>
    </div>
  </div>
</section>

<?php
get_template_part('inc/template-parts/cta-band', null, array(
    'image'   => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?auto=format&fit=crop&w=1200&q=80',
    'alt'     => 'Gabochie Design consultant ready to help',
    'eyebrow' => 'Take the first step',
    'title'   => 'Not sure what you need? <em>Start there.</em>',
    'sub'     => 'Answer five questions in the wizard and leave with a direction &mdash; or talk to us directly. Either way, no pressure and no jargon.',
    'actions' => array(
        array('class' => 'btn-gold', 'href' => $clarity, 'text' => 'Get Clarity'),
        array('class' => 'whatsapp-btn', 'href' => $wa_base . 'Hello%20Gabochie%20Design%20%E2%80%94%20from%20your%20website.', 'text' => 'WhatsApp Us', 'icon' => 'whatsapp'),
        array('class' => 'btn-ghost-w', 'href' => 'tel:' . $phone_tel, 'text' => 'Call ' . $phone),
    ),
));

get_footer();
?>
