/**
 * Onboarding wizard — step navigation, conditional logic, AJAX project creation.
 * @package Gabochie_Value
 */
(function () {
  'use strict';

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  var AJAX_URL = (window.gabochieOnb && window.gabochieOnb.ajaxUrl) || '/wp-admin/admin-ajax.php';
  var NONCE    = (window.gabochieOnb && window.gabochieOnb.nonce) || '';
  var LOGIN_URL = (window.gabochieOnb && window.gabochieOnb.loginUrl) || '/client-login/';

  /* ---- Labels for display ---- */
  var GOAL_LABELS = {
    build_home:       'Build a Home',
    develop_income:   'Develop Property for Income',
    have_land:        'Have Land',
    have_capital:     'Have Capital to Deploy',
    unfinished:       'Unfinished Project',
    renovate:         'Renovate or Expand',
    commercial:       'Commercial Development',
    attract_investors:'Attract Investors',
    invest:           'Invest in a Project',
    professional:     'Professional',
    organisation:     'Organisation',
    notsure:          'Not Sure Yet'
  };

  var TYPE_LABELS = {
    single_family:       'Single-Family House',
    apartment:           'Apartment',
    townhouse:           'Townhouse',
    compound:            'Compound House',
    gated_community:     'Gated Community',
    serviced_apartments: 'Serviced Apartments',
    student_accommodation:'Student Accommodation',
    affordable_housing:  'Affordable Housing',
    office:              'Office',
    retail:              'Retail / Shop',
    restaurant:          'Restaurant',
    hotel:               'Hotel / Hospitality',
    warehouse:           'Warehouse',
    mixed_use:           'Mixed-Use',
    industrial:          'Industrial / Factory',
    religious:           'Religious Facility',
    school:              'School / Educational',
    renovation:          'Renovation / Extension',
    other:               'Other'
  };

  var LOCATION_LABELS = {
    'greater-accra': 'Greater Accra', ashanti: 'Ashanti', western: 'Western',
    central: 'Central', eastern: 'Eastern', northern: 'Northern',
    volta: 'Volta', 'upper-east': 'Upper East', 'upper-west': 'Upper West',
    'other-ghana': 'Other (Ghana)', abroad: 'Outside Ghana', undecided: 'Not decided'
  };

  var SCALE_LABELS = { small: 'Small (1 unit)', medium: 'Medium (2-10 units)', large: 'Large (10+ units)', unsure: 'Not sure' };

  var BUDGET_LABELS = {
    under_500k: 'Under GHS 500K', '500k_1m': 'GHS 500K - 1M', '1m_3m': 'GHS 1M - 3M',
    '3m_5m': 'GHS 3M - 5M', '5m_10m': 'GHS 5M - 10M', '10m_plus': 'GHS 10M+',
    exploring: 'Just exploring'
  };

  var TIMELINE_LABELS = {
    exploring: 'Just exploring', '3_months': 'Within 3 months', '6_months': '3-6 months',
    '12_months': '6-12 months', '12_plus': '12+ months', not_sure: 'Not sure'
  };

  var LOCATION_BASED_LABELS = { in_ghana: 'In Ghana', diaspora: 'Abroad (Diaspora)', prefer_not: 'Prefer not to say' };

  /* ---- State ---- */
  var state = {
    step: 1,
    goal: '',
    property_type: '',
    land_status: '',
    land_location: '',
    development_scale: '',
    budget_range: '',
    timeline: '',
    involvement: '',
    location_based: '',
    project_description: '',
    name: '',
    email: '',
    phone: ''
  };

  /* ---- DOM refs ---- */
  var steps   = $$('.onb-step');
  var pSteps  = $$('.onb-p-step');
  var errEl   = $('#onbErr');
  var navBar  = $('#onbNav');
  var nextBtn = $('#onbNext');
  var backBtn = $('#onbBack');

  /* ---- Step navigation ---- */
  function showStep(n) {
    state.step = n;
    steps.forEach(function (s) { s.classList.toggle('active', +s.getAttribute('data-step') === n); });
    pSteps.forEach(function (p) {
      var idx = +p.getAttribute('data-p');
      p.classList.toggle('active', idx === n);
      p.classList.toggle('done', idx < n);
    });
    if (navBar) navBar.style.display = n >= 4 ? 'none' : 'flex';
    if (errEl) errEl.style.display = 'none';
    /* Update button text */
    if (nextBtn) {
      nextBtn.textContent = n === 3 ? 'See My Opportunity' : 'Continue';
    }
    /* Scroll to top of wizard */
    var card = $('.onb-card');
    if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function showError(msg) {
    /* Show in the step 4 inline error if visible, otherwise in the nav bar */
    var stepErr = $('#onbStepErr');
    if (stepErr) { stepErr.textContent = msg; stepErr.style.display = 'block'; }
    if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
  }

  /* ---- Chip selection ---- */
  function initChips() {
    $$('.onb-chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        var field = chip.getAttribute('data-field');
        var val   = chip.getAttribute('data-val');
        /* Deselect siblings in same field group */
        var group = chip.closest('.onb-chip-grid');
        if (group) {
          $$('.onb-chip', group).forEach(function (c) { c.classList.remove('sel'); });
        }
        chip.classList.add('sel');
        state[field] = val;
      });
    });
  }

  /* ---- Goal selection ---- */
  function initGoals() {
    $$('.onb-goal').forEach(function (btn) {
      btn.addEventListener('click', function () {
        $$('.onb-goal').forEach(function (b) { b.classList.remove('sel'); });
        btn.classList.add('sel');
        state.goal = btn.getAttribute('data-goal');
      });
    });
  }

  /* ---- Conditional field visibility ---- */
  function updateFieldVisibility() {
    var g = state.goal;
    var showPropertyType = ['build_home', 'develop_income', 'have_land', 'commercial', 'unfinished', 'renovate', 'attract_investors', 'notsure'].indexOf(g) !== -1;
    var showLandStatus   = ['build_home', 'develop_income', 'have_land', 'commercial', 'renovate', 'attract_investors', 'notsure'].indexOf(g) !== -1;
    var showLandLocation = showLandStatus;
    var showScale        = ['develop_income', 'have_land', 'have_capital', 'commercial', 'attract_investors', 'invest', 'organisation', 'notsure'].indexOf(g) !== -1;

    var fp = $('#fieldPropertyType'); if (fp) fp.style.display = showPropertyType ? '' : 'none';
    var fl = $('#fieldLandStatus');   if (fl) fl.style.display = showLandStatus ? '' : 'none';
    var fll = $('#fieldLandLocation'); if (fll) fll.style.display = showLandLocation ? '' : 'none';
    var fs = $('#fieldScale');        if (fs) fs.style.display = showScale ? '' : 'none';

    /* Hide irrelevant property chips based on goal */
    if (showPropertyType) {
      var residential = ['single_family', 'apartment', 'townhouse', 'compound', 'gated_community', 'serviced_apartments', 'student_accommodation', 'affordable_housing'];
      var commercial  = ['office', 'retail', 'restaurant', 'hotel', 'warehouse', 'mixed_use', 'industrial'];
      var other       = ['religious', 'school', 'renovation', 'other'];
      var relevant;
      if (g === 'build_home' || g === 'have_land' || g === 'unfinished' || g === 'renovate') {
        relevant = residential.concat(other);
      } else if (g === 'commercial') {
        relevant = commercial.concat(other);
      } else {
        relevant = residential.concat(commercial).concat(other);
      }
      $$('.onb-chip[data-field="property_type"]').forEach(function (c) {
        var v = c.getAttribute('data-val');
        c.style.display = relevant.indexOf(v) !== -1 ? '' : 'none';
      });
    }
  }

  /* ---- Validation ---- */
  function validateStep() {
    if (state.step === 1) {
      if (!state.goal) { showError('Choose the option that best describes your situation.'); return false; }
      updateFieldVisibility();
      return true;
    }
    if (state.step === 2) {
      if (!state.budget_range) { showError('Please select a budget range — "Just exploring" works too.'); return false; }
      return true;
    }
    if (state.step === 3) {
      var name  = ($('#onbName') || {}).value || '';
      var email = ($('#onbEmail') || {}).value || '';
      if (!name.trim()) { showError('Please enter your name.'); return false; }
      if (!email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('Please enter a valid email address.'); return false; }
      state.name  = name.trim();
      state.email = email.trim();
      state.phone = (($('#onbPhone') || {}).value || '').trim();
      /* Read location_based from chips if not set */
      if (!state.location_based) {
        var sel = $('.onb-chip[data-field="location_based"].sel');
        if (sel) state.location_based = sel.getAttribute('data-val');
      }
      return true;
    }
    return true;
  }

  /* ---- Readiness Score ---- */
  function calcReadiness() {
    var score = 0;
    var max = 100;
    var strengths = 0;
    var gaps = 0;

    /* Goal clarity (20 pts) */
    if (state.goal && state.goal !== 'notsure') { score += 20; strengths++; }
    else { gaps++; }

    /* Property type (15 pts) */
    if (state.property_type) { score += 15; strengths++; }
    else { gaps++; }

    /* Land status (15 pts) */
    if (state.land_status && state.land_status !== 'not_applicable') {
      score += 10;
      if (state.land_status === 'own_land') { score += 5; strengths++; }
      else { gaps++; }
    } else { gaps++; }

    /* Budget (20 pts) */
    if (state.budget_range && state.budget_range !== 'exploring') {
      score += 20; strengths++;
    } else { gaps++; }

    /* Timeline (10 pts) */
    if (state.timeline && state.timeline !== 'exploring' && state.timeline !== 'not_sure') {
      score += 10; strengths++;
    } else { gaps++; }

    /* Location (10 pts) */
    if (state.land_location && state.land_location !== 'undecided') {
      score += 10; strengths++;
    } else { gaps++; }

    /* Scale (10 pts) */
    if (state.development_scale && state.development_scale !== 'unsure') {
      score += 10; strengths++;
    } else { gaps++; }

    return { score: Math.min(score, max), strengths: strengths, gaps: gaps };
  }

  /* ---- Next Steps Generator ---- */
  function getRecommendedSteps() {
    var steps = [];
    var g = state.goal;

    if (!state.land_status || state.land_status === 'no_land') {
      steps.push({ icon: '&#127758;', title: 'Secure Land', desc: 'Define your location requirements and evaluate options before committing.' });
    }
    if (!state.property_type || g === 'notsure') {
      steps.push({ icon: '&#128736;', title: 'Development Clarity Session', desc: 'We\'ll help you define what to build, why, and for whom.' });
    }
    if (!state.budget_range || state.budget_range === 'exploring') {
      steps.push({ icon: '&#128176;', title: 'Budget Framework', desc: 'Get a realistic cost picture before any money is committed.' });
    }
    if (state.goal === 'have_land' || state.goal === 'build_home' || state.goal === 'develop_income' || state.goal === 'commercial') {
      steps.push({ icon: '&#128202;', title: 'Feasibility Study', desc: 'Test your project\'s viability — costs, returns, and risk — before proceeding.' });
    }
    if (state.goal === 'attract_investors' || state.goal === 'have_capital' || state.goal === 'invest') {
      steps.push({ icon: '&#128196;', title: 'Investor-Ready Development Plan', desc: 'Generate a professional development pack with financial projections.' });
    }
    if (state.goal === 'unfinished') {
      steps.push({ icon: '&#128737;', title: 'Project Recovery Diagnostic', desc: 'Diagnose what\'s wrong, what it costs to fix, and whether to continue or restart.' });
    }
    if (state.goal === 'renovate') {
      steps.push({ icon: '&#129520;', title: 'Renovation Feasibility', desc: 'Assess the existing structure, potential, and cost before you commit.' });
    }

    /* Always add these */
    steps.push({ icon: '&#128196;', title: 'Cost & Value Intelligence', desc: 'Get an honest cost estimate with our CostSmart engine — area, finishes, location, all factored in.' });
    steps.push({ icon: '&#129302;', title: 'AI Development Copilot', desc: 'Ask your AI assistant: "Can I afford this?" "What should I build?" "What\'s missing?"' });

    return steps.slice(0, 5);
  }

  /* ---- Render Profile (Step 4) ---- */
  function renderProfile() {
    var r = calcReadiness();

    /* Title */
    var title = GOAL_LABELS[state.goal] || 'Your Project';
    var el = $('#profileTitle'); if (el) el.textContent = title;

    /* Score ring */
    var arc  = $('#scoreArc');
    var num  = $('#scoreNum');
    var circ = 326.7;
    if (arc) arc.style.strokeDashoffset = circ - (circ * r.score / 100);
    if (num) num.textContent = r.score;

    /* Strengths / gaps */
    var stEl = $('#scoreStrengths');
    var gaEl = $('#scoreGaps');
    if (stEl) stEl.textContent = r.strengths + ' strength' + (r.strengths !== 1 ? 's' : '') + ' identified';
    if (gaEl) gaEl.textContent = r.gaps + ' area' + (r.gaps !== 1 ? 's' : '') + ' to develop';

    /* Profile grid */
    var pfGoal = $('#pfGoal');       if (pfGoal) pfGoal.textContent = GOAL_LABELS[state.goal] || '--';
    var pfType = $('#pfType');       if (pfType) pfType.textContent = TYPE_LABELS[state.property_type] || state.property_type || '--';
    var pfLoc  = $('#pfLocation');   if (pfLoc) pfLoc.textContent = LOCATION_LABELS[state.land_location] || state.land_location || '--';
    var pfBud  = $('#pfBudget');     if (pfBud) pfBud.textContent = BUDGET_LABELS[state.budget_range] || '--';
    var pfScl  = $('#pfScale');      if (pfScl) pfScl.textContent = SCALE_LABELS[state.development_scale] || '--';
    var pfTl   = $('#pfTimeline');   if (pfTl) pfTl.textContent = TIMELINE_LABELS[state.timeline] || '--';

    /* Next steps */
    var list = $('#nextStepsList');
    if (list) {
      var recs = getRecommendedSteps();
      list.innerHTML = recs.map(function (r) {
        return '<div class="onb-rec-item"><div class="ori-icon">' + r.icon + '</div><div><div class="ori-title">' + r.title + '</div><div class="ori-desc">' + r.desc + '</div></div></div>';
      }).join('');
    }
  }

  /* ---- AJAX: Create Account + Project ---- */
  function createProject() {
    var btn = $('#onbCreateBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Creating your project...'; }

    var data = new FormData();
    data.append('action', 'gabochie_onb_create');
    data.append('nonce', NONCE);
    data.append('name', state.name);
    data.append('email', state.email);
    data.append('phone', state.phone);
    data.append('goal', state.goal);
    data.append('property_type', state.property_type);
    data.append('land_status', state.land_status);
    data.append('land_location', state.land_location);
    data.append('development_scale', state.development_scale);
    data.append('budget_range', state.budget_range);
    data.append('timeline', state.timeline);
    data.append('location_based', state.location_based);
    data.append('project_description', state.project_description);

    fetch(AJAX_URL, { method: 'POST', body: data })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          /* Track */
          if (window.Gabochie && window.Gabochie.track) {
            window.Gabochie.track('gd_onb_complete', { goal: state.goal, budget: state.budget_range });
          }
          /* Show success state briefly, then redirect to dashboard */
          var panel = $('#onbProfilePanel');
          var success = $('#onbSuccess');
          var nav = $('#onbNav');
          if (panel) {
            $$('.onb-profile-header, .onb-score-card, .onb-profile-grid, .onb-next-steps, .onb-profile-ctas', panel).forEach(function (el) { el.style.display = 'none'; });
          }
          if (success) success.style.display = 'block';
          if (nav) nav.style.display = 'none';
          /* Auto-redirect to dashboard after 2 seconds */
          var dashUrl = (res.data && res.data.dashboard_url) || '/dashboard/';
          setTimeout(function () { window.location.href = dashUrl; }, 2000);
        } else {
          if (btn) { btn.disabled = false; btn.textContent = 'Create My Free Account & Project'; }
          /* 409 = email already exists → redirect to login */
          if (res.data && res.data.message && res.data.message.indexOf('already exists') !== -1) {
            var loginBase = (window.gabochieOnb && window.gabochieOnb.loginUrl) || '/login/';
            window.location.href = loginBase + '?account=exists';
            return;
          }
          showError((res.data && res.data.message) || 'Something went wrong. Please try again.');
        }
      })
      .catch(function () {
        if (btn) { btn.disabled = false; btn.textContent = 'Create My Free Account & Project'; }
        showError('Network error. Please check your connection and try again.');
      });
  }

  /* ---- Init ---- */
  function init() {
    initGoals();
    initChips();

    /* Description textarea */
    var descEl = $('#onbDescription');
    if (descEl) {
      descEl.addEventListener('input', function () { state.project_description = descEl.value; });
    }

    /* Next button */
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        if (!validateStep()) return;
        if (state.step === 3) {
          renderProfile();
        }
        showStep(state.step + 1);
      });
    }

    /* Back button */
    if (backBtn) {
      backBtn.addEventListener('click', function () {
        if (state.step > 1) showStep(state.step - 1);
      });
    }

    /* Create project button */
    var createBtn = $('#onbCreateBtn');
    if (createBtn) {
      createBtn.addEventListener('click', function () { createProject(); });
    }

    /* Keyboard nav */
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && state.step < 4) {
        e.preventDefault();
        if (nextBtn) nextBtn.click();
      }
    });

    showStep(1);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
