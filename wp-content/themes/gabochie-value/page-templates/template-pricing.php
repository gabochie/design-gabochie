<?php
/**
 * Template Name: Pricing
 * Professional pricing page — hero + clean card layout
 * @package Gabochie_Value
 */
get_header();

$clarity   = gabochie_value_clarity_url();
$phone     = gabochie_value_phone();
?>

<main id="main">

<!-- ========== HERO ========== -->
<section class="hero hero--solo" id="top">
  <div class="hero-left">
    <div class="hero-location"><span class="hero-location-dot"></span> Sakumono &middot; Tema &mdash; Ghana</div>
    <h1 class="hero-h1">Transparent <em>Pricing</em></h1>
    <div class="hero-h1-dim">Honest, upfront costs for architecture, design, and construction</div>
    <div class="hero-rule"></div>
    <p class="hero-sub">Our fees are structured to provide clarity and value at every stage of your project in Ghana.</p>
    <div class="hero-actions">
      <a class="btn-gold" href="<?php echo esc_url($clarity); ?>">Get Started</a>
      <a class="btn-ghost-w" href="#pricing-plans">View Plans</a>
    </div>
    <div class="hero-note">Prefer to talk? Call or WhatsApp <?php echo esc_html($phone); ?></div>
  </div>
</section>

<!-- ========== PRICING PLANS ========== -->
<section class="pricing-section" id="pricing-plans">
  <div class="container">
    <div class="pricing-header">
      <h2 class="pricing-title">Choose the Right Package</h2>
      <p class="pricing-subtitle">From concept to completion — pick the level of support your project needs.</p>
    </div>

    <div class="pricing-grid">

      <!-- Concept Design -->
      <div class="pricing-card">
        <div class="pricing-card-icon">
          <svg viewBox="0 0 48 48" fill="none" stroke="var(--teal)" stroke-width="2"><rect x="8" y="6" width="32" height="36" rx="3"/><line x1="14" y1="14" x2="34" y2="14"/><line x1="14" y1="22" x2="28" y2="22"/><path d="M14 30h20M14 36h12"/></svg>
        </div>
        <h3 class="pricing-card-name">Concept Design</h3>
        <div class="pricing-card-price">From GH&#8373;15,000</div>
        <ul class="pricing-card-features">
          <li>Schematic design development</li>
          <li>Site analysis and feasibility</li>
          <li>Initial 3D visualisation</li>
          <li>Two rounds of revisions</li>
        </ul>
        <a class="pricing-card-btn" href="/contact/">Get Started</a>
      </div>

      <!-- Full Design Package -->
      <div class="pricing-card pricing-card--featured">
        <div class="pricing-card-badge">Most Popular</div>
        <div class="pricing-card-icon">
          <svg viewBox="0 0 48 48" fill="none" stroke="var(--gold)" stroke-width="2"><rect x="6" y="8" width="36" height="32" rx="3"/><line x1="6" y1="18" x2="42" y2="18"/><line x1="20" y1="8" x2="20" y2="40"/><circle cx="32" cy="29" r="6"/><path d="M30 29l2 2 3-3"/></svg>
        </div>
        <h3 class="pricing-card-name">Full Design Package</h3>
        <div class="pricing-card-price">From GH&#8373;45,000</div>
        <ul class="pricing-card-features">
          <li>Complete architectural drawings</li>
          <li>Structural engineering plans</li>
          <li>Interior design concepts</li>
          <li>Full 3D visualisation package</li>
          <li>Construction documentation</li>
          <li>Contractor tender support</li>
        </ul>
        <a class="pricing-card-btn pricing-card-btn--gold" href="/contact/">Get Started</a>
      </div>

      <!-- Project Management -->
      <div class="pricing-card">
        <div class="pricing-card-icon">
          <svg viewBox="0 0 48 48" fill="none" stroke="var(--teal)" stroke-width="2"><circle cx="24" cy="24" r="18"/><path d="M24 14v10l7 5"/></svg>
        </div>
        <h3 class="pricing-card-name">Project Management</h3>
        <div class="pricing-card-price">8–12% of build cost</div>
        <ul class="pricing-card-features">
          <li>End-to-end construction oversight</li>
          <li>Contractor quality monitoring</li>
          <li>Budget and timeline management</li>
          <li>Regular progress reports</li>
          <li>Material procurement support</li>
        </ul>
        <a class="pricing-card-btn" href="/contact/">Get Started</a>
      </div>

    </div>
  </div>
</section>

<!-- ========== AI CONSULTATION ========== -->
<section class="pricing-cta">
  <div class="container">
    <div class="pricing-cta-inner">
      <h2>AI Consultation</h2>
      <p class="pricing-cta-sub">Get instant expert guidance on your Ghana building project.</p>
      <div class="pricing-cta-price">Free <span>initial consultation</span></div>
      <p>Our AI-powered consultation tool helps you plan, budget, and navigate the complexities of building in Ghana. Start with a free consultation, then upgrade to detailed project planning.</p>
      <a class="pricing-cta-btn" href="/ai-consultation/">Start Free Consultation</a>
    </div>
  </div>
</section>

</main>

<?php get_footer(); ?>
