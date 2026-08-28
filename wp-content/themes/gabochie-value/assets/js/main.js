(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* ---------- analytics hook ---------- */
  window.Gabochie = window.Gabochie || {};
  window.Gabochie.track = function (evt, data) {
    if (window.dataLayer) { try { window.dataLayer.push({ event: evt, gd: data || {} }); } catch (e) {} }
  };

  /* ---------- reading progress ---------- */
  var rp = $('#readingProgress');
  var onScroll = function () {
    var st = window.pageYOffset || document.documentElement.scrollTop;
    var h = document.documentElement.scrollHeight - window.innerHeight;
    if (rp) rp.style.width = (h > 0 ? (st / h) * 100 : 0) + '%';
    var bt = $('#backTop');
    if (bt) { bt.style.opacity = st > 600 ? '1' : ''; bt.style.visibility = st > 600 ? 'visible' : 'hidden'; }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- back to top ---------- */
  var bt = $('#backTop');
  if (bt) bt.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

  /* ---------- mobile menu ---------- */
  var tgl = $('#navToggle'), mm = $('#mobileMenu');
  function closeMenu() { if (mm) { mm.style.display = 'none'; } if (tgl) { tgl.setAttribute('aria-expanded', 'false'); tgl.classList.remove('open'); } document.body.style.overflow = ''; }
  if (tgl && mm) {
    tgl.addEventListener('click', function () {
      var open = mm.style.display === 'flex';
      mm.style.display = open ? 'none' : 'flex';
      tgl.setAttribute('aria-expanded', open ? 'false' : 'true');
      tgl.classList.toggle('open', !open);
      document.body.style.overflow = open ? '' : 'hidden';
    });
    $$('a', mm).forEach(function (a) { a.addEventListener('click', closeMenu); });
    window.addEventListener('resize', function () { if (window.innerWidth > 768) closeMenu(); });
  }

  /* ---------- dark mode ---------- */
  var dt = $('#darkToggle');
  if (dt) {
    dt.addEventListener('click', function () {
      document.body.classList.toggle('dark-mode');
      try { localStorage.setItem('gd_dark', document.body.classList.contains('dark-mode') ? '1' : '0'); } catch (e) {}
    });
    try { if (localStorage.getItem('gd_dark') === '1') document.body.classList.add('dark-mode'); } catch (e) {}
  }

  /* ---------- reveal ---------- */
  var io = window.IntersectionObserver ? new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }) : null;
  $$('.reveal, .reveal-left').forEach(function (el) { if (io) io.observe(el); else el.classList.add('in'); });

  /* ---------- FAQ accordion ---------- */
  $$('.faq-q').forEach(function (q) {
    q.addEventListener('click', function () {
      var item = q.parentElement, ans = $('.faq-a', item);
      var open = item.classList.toggle('open');
      q.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (ans) ans.style.maxHeight = open ? ans.scrollHeight + 'px' : '0px';
      $$('.faq-item.open').forEach(function (o) {
        if (o !== item) { o.classList.remove('open'); var a = $('.faq-a', o); if (a) a.style.maxHeight = '0px'; $('.faq-q', o).setAttribute('aria-expanded', 'false'); }
      });
    });
  });

  /* ---------- year ---------- */
  var y = $('#year'); if (y) y.textContent = new Date().getFullYear();

  /* ======================================================
     WIZARD
     ====================================================== */
  var WA = (window.gabochieValue && window.gabochieValue.whatsapp) || '233268248970';

  var JOURNEY = {
    idea:      { label: 'I have an idea', rec: 'clarity' },
    land:      { label: 'I have land', rec: 'clarity' },
    exploring: { label: 'I\'m exploring what to build', rec: 'clarity' },
    viability: { label: 'I need to know if it\'s viable', rec: 'clarity' },
    design:    { label: 'I need architectural design', rec: 'design' },
    drawings:  { label: 'I already have drawings', rec: 'review' },
    ready:     { label: 'I\'m ready to build', rec: 'procurement' },
    building:  { label: 'I\'m already building', rec: 'delivery' },
    stuck:     { label: 'My project is stuck', rec: 'recovery' },
    renovate:  { label: 'I want to renovate or expand', rec: 'renovate' },
    investing: { label: 'I\'m investing or developing', rec: 'strategy' },
    notsure:   { label: 'I\'m not sure', rec: 'clarity' }
  };

  var BLOCKERS = {
    'dont-know-build': { label: 'I don\'t know what to build', rec: 'clarity' },
    feasibility:       { label: 'I don\'t know if it\'s feasible', rec: 'clarity' },
    cost:              { label: 'I don\'t know what it will cost', rec: 'cost' },
    design:            { label: 'I need a better design', rec: 'design' },
    approvals:         { label: 'I need planning or approval guidance', rec: 'approvals' },
    contractor:        { label: 'I don\'t know how to select a contractor', rec: 'procurement' },
    worried:           { label: 'Construction has started but I\'m worried', rec: 'delivery' },
    'cost-control':    { label: 'Costs are getting out of control', rec: 'cost' },
    stuck:             { label: 'My project is stuck', rec: 'recovery' },
    abroad:            { label: 'I live abroad and need someone I can trust', rec: 'diaspora' },
    coordination:      { label: 'I need help coordinating professionals', rec: 'ownersrep' },
    renovate:          { label: 'I want to renovate or expand', rec: 'renovate' },
    notsure:           { label: 'I\'m not sure', rec: 'clarity' }
  };

  var REC = {
    clarity:    { title: 'Project Clarity & Feasibility Consultation', why: 'You\'re at the point where one good decision can save years and money. Before anything is drawn or committed, we test your idea, land and numbers.', what: 'A structured session covering your vision, site constraints, budget direction and development route — followed by a clear written recommendation.', leave: 'A clear direction, a budget framework and your next three steps — whether you proceed with us or not.' },
    design:     { title: 'Architectural Design Engagement', why: 'You need drawings that are buildable, right-sized and coordinated with cost from day one.', what: 'Concept to detailed design, developed with cost intelligence built in — not added after.', leave: 'A design you can price, approve and build with confidence — with full documentation.' },
    review:     { title: 'Design Review & Cost Check', why: 'Before you price or build, an independent review protects your money.', what: 'We review your existing drawings for buildability, waste, compliance and cost risk — then deliver a documented report.', leave: 'A clear review with specific fixes and a realistic cost picture.' },
    procurement:{ title: 'Procurement & Contractor Selection Support', why: 'Choosing a contractor on price alone is the most expensive mistake in construction.', what: 'A fair, documented selection process with scope, pricing evaluation and recommendation.', leave: 'A shortlist, a recommendation and a contract you understand.' },
    delivery:   { title: 'Project Delivery & Construction Administration', why: 'Once building starts, weekly decisions determine your final cost and quality.', what: 'Construction administration, site supervision, progress reporting and problem resolution.', leave: 'A build that matches your drawings, budget and timeline — documented from start to finish.' },
    recovery:   { title: 'Project Recovery Diagnostic', why: 'Most stalled projects can be rescued and completed for less than starting over.', what: 'A forensic diagnosis: what\'s wrong, what it will take to fix, and what it costs.', leave: 'A rescue plan with a realistic decision — continue or recalibrate.' },
    renovate:   { title: 'Renovation & Expansion Feasibility', why: 'Extending or upgrading is a new project hiding inside an old one.', what: 'We assess the existing structure, the potential and the cost — before you commit.', leave: 'A clear scope, budget and feasibility verdict.' },
    strategy:   { title: 'Development Strategy Consultation', why: 'As an investor, the sequence of decisions is where value is made or lost.', what: 'Site potential, use options, returns analysis and a phased development plan.', leave: 'A strategy you can fund, phase and execute.' },
    diaspora:   { title: 'Diaspora Owner\'s Representation', why: 'Distance shouldn\'t mean losing control of your project.', what: 'One trusted point of contact on the ground — site verification, reporting and decision support.', leave: 'Visibility and control over every cedi from anywhere in the world.' },
    cost:       { title: 'Cost & Value Intelligence Review', why: 'Cost surprises come from decisions made without cost input — not from the market.', what: 'An honest cost estimate and value-engineering pass on your project.', leave: 'A credible budget and options that protect value.' },
    approvals:  { title: 'Planning & Approval Support', why: 'Approvals fail on documentation and sequence, not on luck.', what: 'Permit strategy, drawings and submissions managed end to end.', leave: 'Your approvals — without the back-and-forth.' },
    ownersrep:  { title: 'Owner\'s Representation', why: 'You need someone whose only interest is yours.', what: 'We represent you across design, procurement and construction.', leave: 'Accountability, reporting and decisions made in your interest.' }
  };

  var state = { step: 1, journey: null, blocker: null, profile: {} };
  var steps = $$('.wiz-step');
  var pSteps = $$('.wiz-p-step');
  var err = $('#wizErr');
  var bar = $('.wiz-validate');
  var nextBtn = $('#wizNext');
  var backBtn = $('#wizBack');
  var restartBtn = $('#restart');

  function showStep(n) {
    state.step = n;
    steps.forEach(function (s) { s.classList.toggle('active', +s.getAttribute('data-step') === n); });
    pSteps.forEach(function (p, i) {
      var idx = i + 1;
      p.classList.toggle('active', idx === n);
      p.classList.toggle('done', idx < n);
    });
    if (bar) bar.style.display = (n >= 4) ? 'none' : 'flex';
    if (err) err.style.display = 'none';
    window.Gabochie.track('gd_wizard_step', { step: n });
    var el = $('#clarity');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function showError(msg) { if (err) { err.textContent = msg; err.style.display = 'block'; } }

  function validateStep() {
    if (state.step === 1) {
      if (!state.journey) { showError('Choose the closest match \u2014 or "I\'m not sure".'); return false; }
      return true;
    }
    if (state.step === 2) {
      if (!state.blocker) { showError('Pick the blocker that fits best \u2014 "I\'m not sure" works too.'); return false; }
      return true;
    }
    if (state.step === 3) {
      var name = ($('#pfName') || {}).value || '';
      var email = ($('#pfEmail') || {}).value || '';
      if (!name.trim()) { showError('Please enter your name.'); return false; }
      if (!email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('Please enter a valid email address.'); return false; }
      state.profile = {
        name: name.trim(),
        email: email.trim(),
        phone: (($('#pfPhone') || {}).value || '').trim(),
        location: (($('#pfLocation') || {}).value || '').trim(),
        type: (($('#pfType') || {}).value || '').trim(),
        timeline: (($('#pfTimeline') || {}).value || '').trim()
      };
      return true;
    }
    return true;
  }

  function buildRec() {
    var recKey = (JOURNEY[state.journey] || {}).rec || 'clarity';
    var rec = REC[recKey] || REC.clarity;
    var el = function (id) { return $(id); };
    if (el('#recTitle')) el('#recTitle').textContent = rec.title;
    if (el('#recWhy')) el('#recWhy').textContent = rec.why;
    if (el('#recWhat')) el('#recWhat').textContent = rec.what;
    if (el('#recLeave')) el('#recLeave').textContent = rec.leave;
  }

  function saveLead() {
    try {
      var leads = JSON.parse(localStorage.getItem('gd_leads') || '[]');
      leads.push({ ts: Date.now(), journey: state.journey, journeyLabel: (JOURNEY[state.journey] || {}).label, blocker: state.blocker, blockerLabel: (BLOCKERS[state.blocker] || {}).label, profile: state.profile });
      localStorage.setItem('gd_leads', JSON.stringify(leads));
    } catch (e) {}
    /* server-side: store full wizard profile as consultation request in dashboard */
    fetch('/wp-json/gabochie/v1/consultation-request', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: state.profile.name,
        email: state.profile.email,
        phone: state.profile.phone || '',
        journey: (JOURNEY[state.journey] || {}).label || '',
        blocker: (BLOCKERS[state.blocker] || {}).label || '',
        location: state.profile.location || '',
        type: state.profile.type || '',
        timeline: state.profile.timeline || ''
      })
    }).catch(function () {});
  }

  /* journey selections */
  $$('.choice', $('#journeyChoices')).forEach(function (btn) {
    btn.addEventListener('click', function () {
      $$('.choice', $('#journeyChoices')).forEach(function (b) { b.classList.remove('sel'); });
      btn.classList.add('sel');
      state.journey = btn.getAttribute('data-journey');
      setTimeout(function () { showStep(2); }, 350);
    });
  });

  /* blocker selections */
  $$('.wiz-opt', $('#blockerChoices')).forEach(function (btn) {
    btn.addEventListener('click', function () {
      $$('.wiz-opt', $('#blockerChoices')).forEach(function (b) { b.classList.remove('sel'); });
      btn.classList.add('sel');
      state.blocker = btn.getAttribute('data-blocker');
      setTimeout(function () { showStep(3); }, 350);
    });
  });

  /* next */
  if (nextBtn) nextBtn.addEventListener('click', function () {
    if (!validateStep()) return;
    if (state.step === 3) { buildRec(); saveLead(); window.Gabochie.track('gd_lead_captured', { journey: state.journey, blocker: state.blocker }); }
    showStep(state.step + 1);
  });

  /* back */
  if (backBtn) backBtn.addEventListener('click', function () {
    if (state.step > 1) showStep(state.step - 1);
  });

  /* restart */
  if (restartBtn) restartBtn.addEventListener('click', function () {
    state = { step: 1, journey: null, blocker: null, profile: {} };
    $$('.choice').forEach(function (b) { b.classList.remove('sel'); });
    $$('.wiz-opt').forEach(function (b) { b.classList.remove('sel'); });
    showStep(1);
  });

  /* guide delivery — POST to REST endpoint (email with PDF attachment) */
  function sendGuide(form, emailId, okId, errId, source, extra) {
    var email = (($(emailId) || {}).value || '').trim();
    var err = $(errId);
    var ok = $(okId);
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      if (err) { err.textContent = 'Please enter a valid email address.'; err.classList.add('show'); }
      return;
    }
    if (err) err.classList.remove('show');
    var hp = form.querySelector('[name=website]');
    var payload = { email: email, source: source, website: hp ? hp.value : '' };
    if (extra) for (var k in extra) if (extra.hasOwnProperty(k)) payload[k] = extra[k];
    var btn = form.querySelector('button[type=submit]');
    var btnText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Sending\u2026'; }
    var fail = function () {
      if (btn) { btn.disabled = false; btn.textContent = btnText; }
      if (err) { err.textContent = 'Something went wrong — please try again or contact us at +233 26 824 8970.'; err.classList.add('show'); }
    };
    fetch('/wp-json/gabochie/v1/guide-request', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (data) { return { ok: r.ok, data: data }; });
    }).then(function (res) {
      if (res.ok && res.data && res.data.success) {
        try {
          var guides = JSON.parse(localStorage.getItem('gd_guides') || '[]');
          guides.push({ ts: Date.now(), email: email, guide: 'readiness-checklist', source: source });
          localStorage.setItem('gd_guides', JSON.stringify(guides));
        } catch (e) {}
        window.Gabochie.track('gd_guide_request', { guide: 'readiness-checklist', source: source });
        if (ok) ok.classList.add('show');
        form.style.display = 'none';
      } else {
        fail();
      }
    }).catch(fail);
  }

  /* guide form (featured resource) */
  var resForm = $('#resForm');
  if (resForm) resForm.addEventListener('submit', function (e) {
    e.preventDefault();
    sendGuide(resForm, '#resEmail', '#resGuideOk', '#resGuideErr', 'homepage');
  });

  /* wizard guide form */
  var guideForm = $('#guideForm');
  if (guideForm) guideForm.addEventListener('submit', function (e) {
    e.preventDefault();
    sendGuide(guideForm, '#guideEmail', '#guideOk', '#guideErr', 'wizard', {
      journey: state.journey || '',
      blocker: state.blocker || ''
    });
  });

  /* notify-me forms (coming-soon resource cards) + footer newsletter */
  $$('.notify-form, .foot-newsletter-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = form.querySelector('input[type=email]');
      var email = (input ? input.value : '').trim();
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
      var hp = form.querySelector('[name=website]');
      var source = (form.querySelector('[name=source]') || {}).value || 'notify';
      var btn = form.querySelector('button[type=submit]');
      var btnText = btn ? btn.textContent : '';
      if (btn) { btn.disabled = true; btn.textContent = 'Sending\u2026'; }
      fetch('/wp-json/gabochie/v1/guide-request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, source: source, website: hp ? hp.value : '' })
      }).then(function (r) {
        return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, data: d }; });
      }).then(function (res) {
        if (res.ok && res.data && res.data.success) {
          var ok = form.parentElement.querySelector('.guide-ok, .foot-newsletter-ok');
          form.style.display = 'none';
          if (ok) ok.style.display = 'block';
        } else {
          if (btn) { btn.disabled = false; btn.textContent = btnText; }
        }
      }).catch(function () {
        if (btn) { btn.disabled = false; btn.textContent = btnText; }
      });
    });
  });

  /* homepage inline project form */
  var projectForm = $('#projectForm');
  if (projectForm) projectForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var name = (projectForm.querySelector('[name=name]') || {}).value || '';
    var email = (projectForm.querySelector('[name=email]') || {}).value || '';
    if (!name.trim() || !email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
    var phone = (projectForm.querySelector('[name=phone]') || {}).value || '';
    var message = (projectForm.querySelector('[name=message]') || {}).value || '';
    var hp = projectForm.querySelector('[name=website]');
    var btn = projectForm.querySelector('button[type=submit]');
    var btnText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Sending\u2026'; }
    fetch('/wp-json/gabochie/v1/guide-request', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email.trim(), source: 'homepage-form', name: name.trim(), phone: phone, message: message, website: hp ? hp.value : '' })
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, data: d }; });
    }).then(function (res) {
      if (res.ok && res.data && res.data.success) {
        var ok = $('#pfOk');
        projectForm.style.display = 'none';
        if (ok) ok.style.display = 'block';
      } else {
        if (btn) { btn.disabled = false; btn.textContent = btnText; }
      }
    }).catch(function () {
      if (btn) { btn.disabled = false; btn.textContent = btnText; }
    });
  });

  /* blog post email capture */
  $$('.post-capture-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = form.querySelector('input[type=email]');
      var email = (input ? input.value : '').trim();
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
      var hp = form.querySelector('[name=website]');
      var source = (form.querySelector('[name=source]') || {}).value || 'blog';
      var btn = form.querySelector('button[type=submit]');
      var btnText = btn ? btn.textContent : '';
      if (btn) { btn.disabled = true; btn.textContent = 'Sending\u2026'; }
      fetch('/wp-json/gabochie/v1/guide-request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, source: source, website: hp ? hp.value : '' })
      }).then(function (r) {
        return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, data: d }; });
      }).then(function (res) {
        if (res.ok && res.data && res.data.success) {
          var ok = form.parentElement.querySelector('.guide-ok');
          form.style.display = 'none';
          if (ok) ok.style.display = 'block';
        } else {
          if (btn) { btn.disabled = false; btn.textContent = btnText; }
        }
      }).catch(function () {
        if (btn) { btn.disabled = false; btn.textContent = btnText; }
      });
    });
  });

  /* ---------- exit-intent popup ---------- */
  (function () {
    if (localStorage.getItem('gd_popup_dismissed')) return;
    var shown = false;
    function showPopup() {
      if (shown || localStorage.getItem('gd_popup_dismissed')) return;
      shown = true;
      var overlay = document.createElement('div');
      overlay.className = 'exit-popup-overlay';
      overlay.innerHTML = '<div class="exit-popup">' +
        '<button class="exit-popup-close" aria-label="Close">&times;</button>' +
        '<div class="exit-popup-ey">Before you go</div>' +
        '<h3>Get your free Building Project Readiness Checklist</h3>' +
        '<p>The 40-point checklist we walk every project through &mdash; before a single cedi is committed.</p>' +
        '<form class="exit-popup-form" novalidate>' +
          '<input type="email" placeholder="Your email" required aria-label="Email for checklist">' +
          '<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="gd-hp" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">' +
          '<input type="hidden" name="source" value="exit-popup">' +
          '<button type="submit">Send me the checklist</button>' +
        '</form>' +
        '<div class="exit-popup-ok" style="display:none">Got it &mdash; check your inbox.</div>' +
        '<div class="exit-popup-alt">Or <a href="/#clarity">answer 5 questions</a> and leave with a direction.</div>' +
      '</div>';
      document.body.appendChild(overlay);
      requestAnimationFrame(function () { overlay.classList.add('show'); });
      overlay.querySelector('.exit-popup-close').addEventListener('click', function () {
        overlay.classList.remove('show');
        setTimeout(function () { overlay.remove(); }, 300);
        localStorage.setItem('gd_popup_dismissed', '1');
      });
      overlay.addEventListener('click', function (e) { if (e.target === overlay) {
        overlay.classList.remove('show');
        setTimeout(function () { overlay.remove(); }, 300);
        localStorage.setItem('gd_popup_dismissed', '1');
      }});
      var form = overlay.querySelector('.exit-popup-form');
      if (form) form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var email = (form.querySelector('input[type=email]') || {}).value || '';
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
        var hp = form.querySelector('[name=website]');
        var btn = form.querySelector('button[type=submit]');
        if (btn) { btn.disabled = true; btn.textContent = 'Sending\u2026'; }
        fetch('/wp-json/gabochie/v1/guide-request', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: email, source: 'exit-popup', website: hp ? hp.value : '' })
        }).then(function (r) {
          return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, data: d }; });
        }).then(function (res) {
          if (res.ok && res.data && res.data.success) {
            form.style.display = 'none';
            var ok = overlay.querySelector('.exit-popup-ok');
            if (ok) ok.style.display = 'block';
            localStorage.setItem('gd_popup_dismissed', '1');
          } else {
            if (btn) { btn.disabled = false; btn.textContent = 'Send me the checklist'; }
          }
        }).catch(function () {
          if (btn) { btn.disabled = false; btn.textContent = 'Send me the checklist'; }
        });
      });
    }
    document.addEventListener('mouseout', function (e) {
      if (e.clientY < 10) showPopup();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') showPopup();
    });
    setTimeout(function () {
      if (!shown && !localStorage.getItem('gd_popup_dismissed')) {
        var st = window.pageYOffset || document.documentElement.scrollTop;
        var h = document.documentElement.scrollHeight - window.innerHeight;
        if (h > 0 && st / h > 0.7) showPopup();
      }
    }, 45000);
  })();

  /* ---------- CTA analytics tracking ---------- */
  (function () {
    var ctaSelectors = [
      '.btn-gold', '.btn-ghost', '.btn-ghost-w', '.btn-solid',
      '.whatsapp-btn', '.whatsapp-fab', '.svc', '.share-btn',
      '.nav-cta', '.nav-portal', '.st-cta', '.btn-recovery',
      '.foot-col a', '.foot-legal a', '.foot-social a',
      '.proj-cta a', '.faq-cta-btns a', '.dia-cta a',
      '.rec-ctas a', '.hero-actions a', '.journey-banner a'
    ].join(', ');

    $$(ctaSelectors).forEach(function (el) {
      el.addEventListener('click', function () {
        var label = el.textContent.trim().substring(0, 60) || el.getAttribute('aria-label') || 'cta';
        var href = el.getAttribute('href') || '';
        var type = 'cta_click';
        if (href.indexOf('wa.me') !== -1) type = 'whatsapp_click';
        else if (href.indexOf('tel:') !== -1) type = 'phone_click';
        else if (el.classList.contains('share-btn')) type = 'share_click';
        else if (el.classList.contains('svc')) type = 'service_click';
        else if (el.classList.contains('whatsapp-fab')) type = 'whatsapp_fab_click';
        else if (el.classList.contains('nav-cta') || el.classList.contains('nav-portal')) type = 'nav_cta_click';
        else if (el.classList.contains('st-cta')) type = 'journey_stage_click';
        else if (el.closest('.foot-col') || el.closest('.foot-social')) type = 'footer_click';
        else if (el.closest('.faq-cta')) type = 'faq_cta_click';
        window.Gabochie.track(type, { label: label, href: href.substring(0, 120) });
      });
    });
  })();

  /* ---------- FAQ accordion tracking ---------- */
  (function () {
    $$('.faq-q').forEach(function (q) {
      q.addEventListener('click', function () {
        var item = q.parentElement;
        var isOpen = item.classList.contains('open');
        var question = q.textContent.trim().replace(/\s+/g, ' ').substring(0, 80);
        window.Gabochie.track('faq_interact', { question: question, action: isOpen ? 'open' : 'close' });
      });
    });
  })();

  /* ---------- scroll depth tracking ---------- */
  (function () {
    var thresholds = [25, 50, 75, 100];
    var fired = {};
    function check() {
      var st = window.pageYOffset || document.documentElement.scrollTop;
      var h = document.documentElement.scrollHeight - window.innerHeight;
      if (h <= 0) return;
      var pct = Math.round((st / h) * 100);
      for (var i = 0; i < thresholds.length; i++) {
        var t = thresholds[i];
        if (pct >= t && !fired[t]) {
          fired[t] = true;
          window.Gabochie.track('scroll_depth', { percent: t });
        }
      }
    }
    window.addEventListener('scroll', check, { passive: true });
    check();
  })();

  /* ---------- section visibility tracking ---------- */
  (function () {
    if (!window.IntersectionObserver) return;
    var sections = [
      { id: 'hero',     name: 'hero' },
      { id: 'why',      name: 'why_alone' },
      { id: 'clarity',  name: 'wizard' },
      { id: 'value',    name: 'value' },
      { id: 'journey',  name: 'journey' },
      { id: 'services', name: 'services' },
      { id: 'trust',    name: 'trust' },
      { id: 'testimonials', name: 'testimonials' },
      { id: 'projects', name: 'projects' },
      { id: 'diaspora', name: 'diaspora' },
      { id: 'recovery', name: 'recovery' },
      { id: 'resources', name: 'resources' },
      { id: 'faq',      name: 'faq' },
      { id: 'project-form', name: 'project_form' }
    ];
    var seen = {};
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        var id = e.target.id;
        if (seen[id]) return;
        seen[id] = true;
        var match = sections.filter(function (s) { return s.id === id; })[0];
        window.Gabochie.track('section_view', { section: match ? match.name : id });
      });
    }, { threshold: 0.15 });
    sections.forEach(function (s) {
      var el = document.getElementById(s.id);
      if (el) io.observe(el);
    });
  })();

  /* ---------- performance metrics (web-vitals) ---------- */
  (function () {
    function onEntry(list) {
      list.getEntries().forEach(function (entry) {
        if (entry.name === 'largest-contentful-paint') {
          window.Gabochie.track('perf_lcp', { ms: Math.round(entry.startTime) });
        }
        if (entry.name === 'first-input') {
          window.Gabochie.track('perf_inp', { ms: Math.round(entry.processingStart - entry.startTime) });
        }
      });
    }
    if (window.PerformanceObserver) {
      try {
        var lcpObs = new PerformanceObserver(onEntry);
        lcpObs.observe({ type: 'largest-contentful-paint', buffered: true });
      } catch (e) {}
      try {
        var inpObs = new PerformanceObserver(onEntry);
        inpObs.observe({ type: 'first-input', buffered: true });
      } catch (e) {}
      try {
        var clsObs = new PerformanceObserver(function (list) {
          var cls = 0;
          list.getEntries().forEach(function (entry) {
            if (!entry.hadRecentInput) cls += entry.value;
          });
          if (cls > 0) window.Gabochie.track('perf_cls', { value: Math.round(cls * 1000) });
        });
        clsObs.observe({ type: 'layout-shift', buffered: true });
      } catch (e) {}
    }
  })();

})();
