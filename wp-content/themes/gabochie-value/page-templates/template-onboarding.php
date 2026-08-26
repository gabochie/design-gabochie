<?php
/**
 * Template Name: Development Onboarding
 *
 * Multi-step onboarding wizard: "What are you trying to accomplish?"
 * → project details → account creation → Development Opportunity profile.
 *
 * @package Gabochie_Value
 */

if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard/'));
    exit;
}

get_header();
$whatsapp = gabochie_value_whatsapp();
$wa_base  = 'https://wa.me/' . $whatsapp . '?text=';
?>

<main id="main" class="onb-main">

<!-- ========== HERO ========== -->
<section class="onb-hero">
  <div class="container">
    <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Start Your Development</div>
    <h1 class="onb-hero-h1">What are you trying to <em>accomplish?</em></h1>
    <p class="onb-hero-sub">Answer a few questions and we'll generate your first Development Opportunity profile &mdash; free, no card required.</p>
  </div>
</section>

<!-- ========== WIZARD ========== -->
<section class="onb-wizard" id="onbWizard">
  <div class="container">
    <div class="onb-card">

      <!-- Progress bar -->
      <div class="onb-progress" id="onbProgress">
        <div class="onb-p-step active" data-p="1"><span class="op-num">1</span><span class="op-label">Goal</span></div>
        <span class="op-dash"></span>
        <div class="onb-p-step" data-p="2"><span class="op-num">2</span><span class="op-label">Details</span></div>
        <span class="op-dash"></span>
        <div class="onb-p-step" data-p="3"><span class="op-num">3</span><span class="op-label">About You</span></div>
        <span class="op-dash"></span>
        <div class="onb-p-step" data-p="4"><span class="op-num">4</span><span class="op-label">Your Opportunity</span></div>
      </div>

      <div class="onb-body">

        <!-- ===== STEP 1: Goal ===== -->
        <div class="onb-step active" data-step="1">
          <h2 class="onb-q">What are you trying to accomplish?</h2>
          <p class="onb-hint">Choose the closest match. There's no wrong answer.</p>
          <div class="onb-goal-grid" id="goalGrid">
            <button type="button" class="onb-goal" data-goal="build_home">
              <span class="og-icon">&#127968;</span>
              <span class="og-title">I want to build a home</span>
              <span class="og-desc">Residential — single family, apartment, or compound</span>
            </button>
            <button type="button" class="onb-goal" data-goal="develop_income">
              <span class="og-icon">&#128200;</span>
              <span class="og-title">I want to develop property for income</span>
              <span class="og-desc">Rental, commercial, or mixed-use development</span>
            </button>
            <button type="button" class="onb-goal" data-goal="have_land">
              <span class="og-icon">&#127758;</span>
              <span class="og-title">I have land</span>
              <span class="og-desc">Land available, need to figure out what to build</span>
            </button>
            <button type="button" class="onb-goal" data-goal="have_capital">
              <span class="og-icon">&#128176;</span>
              <span class="og-title">I have capital to deploy</span>
              <span class="og-desc">Looking for a viable development opportunity</span>
            </button>
            <button type="button" class="onb-goal" data-goal="unfinished">
              <span class="og-icon">&#128679;</span>
              <span class="og-title">I have an unfinished project</span>
              <span class="og-desc">Stalled or incomplete — need to finish or recover</span>
            </button>
            <button type="button" class="onb-goal" data-goal="renovate">
              <span class="og-icon">&#128296;</span>
              <span class="og-title">I want to renovate or expand</span>
              <span class="og-desc">Existing building, new requirements</span>
            </button>
            <button type="button" class="onb-goal" data-goal="commercial">
              <span class="og-icon">&#127970;</span>
              <span class="og-title">I want to develop commercial property</span>
              <span class="og-desc">Office, retail, hotel, warehouse, or mixed-use</span>
            </button>
            <button type="button" class="onb-goal" data-goal="attract_investors">
              <span class="og-icon">&#129309;</span>
              <span class="og-title">I want to attract investors</span>
              <span class="og-desc">Need an investor-ready development plan</span>
            </button>
            <button type="button" class="onb-goal" data-goal="invest">
              <span class="og-icon">&#127757;</span>
              <span class="og-title">I want to invest in a project</span>
              <span class="og-desc">Looking for vetted development opportunities</span>
            </button>
            <button type="button" class="onb-goal" data-goal="professional">
              <span class="og-icon">&#128188;</span>
              <span class="og-title">I am a professional</span>
              <span class="og-desc">Architect, engineer, QS, contractor, or consultant</span>
            </button>
            <button type="button" class="onb-goal" data-goal="organisation">
              <span class="og-icon">&#127970;</span>
              <span class="og-title">I represent an organisation</span>
              <span class="og-desc">Church, NGO, cooperative, institution, or company</span>
            </button>
            <button type="button" class="onb-goal notsure" data-goal="notsure">
              <span class="og-icon">?</span>
              <span class="og-title">I'm not sure yet</span>
              <span class="og-desc">That's exactly where we can help</span>
            </button>
          </div>
        </div>

        <!-- ===== STEP 2: Project Details ===== -->
        <div class="onb-step" data-step="2">
          <h2 class="onb-q">Tell us about your <em>project</em></h2>
          <p class="onb-hint">The more we know, the better your Development Opportunity profile will be.</p>

          <div class="onb-fields">
            <!-- Property type (conditional) -->
            <div class="onb-field" id="fieldPropertyType">
              <label>What type of property?</label>
              <div class="onb-chip-grid" id="propertyChips">
                <button type="button" class="onb-chip" data-field="property_type" data-val="single_family">Single-Family House</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="apartment">Apartment</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="townhouse">Townhouse</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="compound">Compound House</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="gated_community">Gated Community</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="serviced_apartments">Serviced Apartments</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="student_accommodation">Student Accommodation</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="affordable_housing">Affordable Housing</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="office">Office</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="retail">Retail / Shop</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="restaurant">Restaurant</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="hotel">Hotel / Hospitality</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="warehouse">Warehouse</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="mixed_use">Mixed-Use</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="industrial">Industrial / Factory</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="religious">Religious Facility</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="school">School / Educational</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="renovation">Renovation / Extension</button>
                <button type="button" class="onb-chip" data-field="property_type" data-val="other">Other</button>
              </div>
            </div>

            <!-- Land status -->
            <div class="onb-field" id="fieldLandStatus">
              <label>Do you have land?</label>
              <div class="onb-chip-grid cols-3">
                <button type="button" class="onb-chip" data-field="land_status" data-val="own_land">Yes, I own land</button>
                <button type="button" class="onb-chip" data-field="land_status" data-val="buying_land">I'm acquiring land</button>
                <button type="button" class="onb-chip" data-field="land_status" data-val="no_land">No land yet</button>
                <button type="button" class="onb-chip" data-field="land_status" data-val="not_applicable">Not applicable</button>
              </div>
            </div>

            <!-- Land location -->
            <div class="onb-field" id="fieldLandLocation">
              <label>Where is the project located (or planned)?</label>
              <select id="onbLandLocation" data-field="land_location">
                <option value="">Select region&hellip;</option>
                <option value="greater-accra">Greater Accra</option>
                <option value="ashanti">Ashanti</option>
                <option value="western">Western</option>
                <option value="central">Central</option>
                <option value="eastern">Eastern</option>
                <option value="northern">Northern</option>
                <option value="volta">Volta</option>
                <option value="upper-east">Upper East</option>
                <option value="upper-west">Upper West</option>
                <option value="other-ghana">Other (Ghana)</option>
                <option value="abroad">Outside Ghana</option>
                <option value="undecided">Not decided yet</option>
              </select>
            </div>

            <!-- Development scale -->
            <div class="onb-field" id="fieldScale">
              <label>What scale are you thinking?</label>
              <div class="onb-chip-grid cols-3">
                <button type="button" class="onb-chip" data-field="development_scale" data-val="small">Small (1 unit / single building)</button>
                <button type="button" class="onb-chip" data-field="development_scale" data-val="medium">Medium (2&ndash;10 units)</button>
                <button type="button" class="onb-chip" data-field="development_scale" data-val="large">Large (10+ units)</button>
                <button type="button" class="onb-chip" data-field="development_scale" data-val="unsure">Not sure yet</button>
              </div>
            </div>

            <!-- Budget range -->
            <div class="onb-field" id="fieldBudget">
              <label>What's your approximate budget range? <span class="req">*</span></label>
              <div class="onb-chip-grid cols-3">
                <button type="button" class="onb-chip" data-field="budget_range" data-val="under_500k">Under GHS 500,000</button>
                <button type="button" class="onb-chip" data-field="budget_range" data-val="500k_1m">GHS 500K &ndash; 1M</button>
                <button type="button" class="onb-chip" data-field="budget_range" data-val="1m_3m">GHS 1M &ndash; 3M</button>
                <button type="button" class="onb-chip" data-field="budget_range" data-val="3m_5m">GHS 3M &ndash; 5M</button>
                <button type="button" class="onb-chip" data-field="budget_range" data-val="5m_10m">GHS 5M &ndash; 10M</button>
                <button type="button" class="onb-chip" data-field="budget_range" data-val="10m_plus">GHS 10M+</button>
                <button type="button" class="onb-chip" data-field="budget_range" data-val="exploring">Just exploring (no budget yet)</button>
              </div>
            </div>

            <!-- Timeline -->
            <div class="onb-field" id="fieldTimeline">
              <label>When do you want to move?</label>
              <div class="onb-chip-grid cols-3">
                <button type="button" class="onb-chip" data-field="timeline" data-val="exploring">Just exploring</button>
                <button type="button" class="onb-chip" data-field="timeline" data-val="3_months">Within 3 months</button>
                <button type="button" class="onb-chip" data-field="timeline" data-val="6_months">3&ndash;6 months</button>
                <button type="button" class="onb-chip" data-field="timeline" data-val="12_months">6&ndash;12 months</button>
                <button type="button" class="onb-chip" data-field="timeline" data-val="12_plus">12+ months</button>
                <button type="button" class="onb-chip" data-field="timeline" data-val="not_sure">Not sure</button>
              </div>
            </div>

            <!-- Project description -->
            <div class="onb-field onb-field-full">
              <label>Tell us more (optional)</label>
              <textarea id="onbDescription" rows="3" placeholder="Describe your vision, any challenges, or specific questions you have&hellip;"></textarea>
            </div>
          </div>
        </div>

        <!-- ===== STEP 3: About You / Account ===== -->
        <div class="onb-step" data-step="3">
          <h2 class="onb-q">Create your <em>free account</em></h2>
          <p class="onb-hint">We'll save your project and send you login details. No card required.</p>

          <div class="onb-fields onb-account-fields">
            <div class="onb-field">
              <label for="onbName">Full name <span class="req">*</span></label>
              <input type="text" id="onbName" name="name" autocomplete="name" required placeholder="Your name">
            </div>
            <div class="onb-field">
              <label for="onbEmail">Email address <span class="req">*</span></label>
              <input type="email" id="onbEmail" name="email" autocomplete="email" required placeholder="you@example.com">
            </div>
            <div class="onb-field">
              <label for="onbPhone">WhatsApp / phone</label>
              <input type="tel" id="onbPhone" name="phone" autocomplete="tel" placeholder="+233 ...">
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

        <!-- ===== STEP 4: Your Development Opportunity ===== -->
        <div class="onb-step" data-step="4">
          <div id="onbProfilePanel">
            <div class="onb-profile-header">
              <div class="onb-profile-ey">Your Development Opportunity</div>
              <h2 class="onb-profile-title" id="profileTitle">Your Project</h2>
              <p class="onb-profile-sub" id="profileSub">Here's what we've mapped out from your answers.</p>
            </div>

            <!-- Readiness Score -->
            <div class="onb-score-card">
              <div class="onb-score-ring">
                <svg viewBox="0 0 120 120">
                  <circle cx="60" cy="60" r="52" fill="none" stroke="var(--border-light)" stroke-width="8"/>
                  <circle cx="60" cy="60" r="52" fill="none" stroke="var(--gold)" stroke-width="8" stroke-dasharray="326.7" stroke-dashoffset="326.7" stroke-linecap="round" transform="rotate(-90 60 60)" id="scoreArc"/>
                </svg>
                <div class="onb-score-num" id="scoreNum">--</div>
                <div class="onb-score-label">Readiness</div>
              </div>
              <div class="onb-score-details" id="scoreDetails">
                <div class="osd-row"><span class="osd-dot green"></span> <span id="scoreStrengths"></span></div>
                <div class="osd-row"><span class="osd-dot amber"></span> <span id="scoreGaps"></span></div>
              </div>
            </div>

            <!-- Profile summary -->
            <div class="onb-profile-grid">
              <div class="onb-pf-item"><div class="opf-label">Goal</div><div class="opf-val" id="pfGoal">--</div></div>
              <div class="onb-pf-item"><div class="opf-label">Property Type</div><div class="opf-val" id="pfType">--</div></div>
              <div class="onb-pf-item"><div class="opf-label">Location</div><div class="opf-val" id="pfLocation">--</div></div>
              <div class="onb-pf-item"><div class="opf-label">Budget Range</div><div class="opf-val" id="pfBudget">--</div></div>
              <div class="onb-pf-item"><div class="opf-label">Scale</div><div class="opf-val" id="pfScale">--</div></div>
              <div class="onb-pf-item"><div class="opf-label">Timeline</div><div class="opf-val" id="pfTimeline">--</div></div>
            </div>

            <!-- Recommended next steps -->
            <div class="onb-next-steps">
              <h3>Recommended Next Steps</h3>
              <div id="nextStepsList"></div>
            </div>

            <!-- CTA -->
            <div class="onb-profile-ctas">
              <button type="button" class="btn-gold onb-create-btn" id="onbCreateBtn">Create My Free Account &amp; Project</button>
              <p class="onb-fine-print">By creating an account you agree to our terms. We'll never share your data.</p>
            </div>

            <!-- Success state (hidden) -->
            <div class="onb-success" id="onbSuccess" style="display:none">
              <div class="onb-success-mark">&#10003;</div>
              <h3>Your project is live.</h3>
              <p>We've created your account and saved your Development Opportunity. Check your email for login details.</p>
              <div class="onb-success-actions">
                <a class="btn-gold" href="<?php echo esc_url(home_url('/dashboard/')); ?>">Go to Dashboard</a>
                <a class="btn-ghost" href="<?php echo esc_url(home_url('/')); ?>">Back to Home</a>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /onb-body -->

      <!-- Navigation -->
      <div class="onb-nav" id="onbNav">
        <button class="btn-ghost" id="onbBack" type="button">Back</button>
        <span class="onb-err" id="onbErr"></span>
        <button class="btn-solid" id="onbNext" type="button">Continue</button>
      </div>

    </div><!-- /onb-card -->
  </div>
</section>

</main>

<?php get_footer(); ?>
