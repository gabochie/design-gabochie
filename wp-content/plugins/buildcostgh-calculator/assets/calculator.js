document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('bcgh_calc');
  if (!btn) return;
  btn.addEventListener('click', function () {
    var sqm = document.getElementById('bcgh_sqm').value;
    var loc = document.getElementById('bcgh_location').value;
    var qual = document.getElementById('bcgh_quality').value;
    var url = BCGH.restUrl + 'estimate?sqm=' + encodeURIComponent(sqm) +
              '&location=' + encodeURIComponent(loc) + '&quality=' + encodeURIComponent(qual);

    document.getElementById('bcgh_result').innerHTML = '<em>Calculating\u2026</em>';
    fetch(url).then(function (r) { return r.json(); }).then(function (d) {
      var out = document.getElementById('bcgh_result');
      out.innerHTML =
        '<div class="bcgh-bands">' +
          '<div class="bcgh-mini"><div class="k">Low</div><div class="v">GHS ' + Math.round(d.low_total/1000) + 'k</div></div>' +
          '<div class="bcgh-mini mid"><div class="k">Central</div><div class="v">GHS ' + Math.round(d.central_total/1000) + 'k</div></div>' +
          '<div class="bcgh-mini"><div class="k">High</div><div class="v">GHS ' + Math.round(d.high_total/1000) + 'k</div></div>' +
        '</div>' +
        '<div class="bcgh-note">GHS ' + d.per_sqm + ' / sqm \u00b7 anchor ' + d.inputs_used.anchor_date +
        ' \u00b7 cement GHS ' + d.inputs_used.cement_price + '/bag \u00b7 FX ' + d.inputs_used.fx_rate +
        ' \u00b7 labor input ' + d.inputs_used.labor_inflation_pct + '%/yr (unverified until a QS confirms it)</div>';
    }).catch(function () {
      document.getElementById('bcgh_result').innerHTML = '<em>Could not reach the estimate service \u2014 check the site\'s REST API is reachable.</em>';
    });
  });
});

// ===== BUILDCOST ESTIMATOR (Residential & Non-Residential) =====
jQuery(function($) {
  var r = $('.bcgh-rcv2');
  if (!r.length) return;

  // ---- Data Definitions ----
  var DATA = {
    residential: {
      types: [
        ['single-storey', 'Single Storey House'],
        ['multi-storey',  'Multi-Storey House'],
        ['luxury-villa',  'Luxury Villa'],
        ['apartment',     'Apartment Block'],
        ['townhouse',     'Townhouse Complex'],
        ['compound',      'Compound House'],
      ],
      finishes: [
        ['budget',   'Budget'],
        ['standard', 'Standard'],
        ['premium',  'Premium'],
        ['luxury',   'Luxury'],
      ],
      roofs: [
        ['metal', 'Metal Roof'],
        ['tile',  'Clay Tile'],
        ['slate', 'Slate Roof'],
        ['green', 'Green Roof'],
      ],
      extras: [
        ['carport',  'Carport'],
        ['swimming', 'Swimming Pool'],
        ['solar',    'Solar Panels'],
        ['garden',   'Landscaped Garden'],
        ['fence',    'Perimeter Fence'],
        ['borehole', 'Borehole'],
        ['generator','Generator Set'],
        ['kitchen',  'Built-in Kitchen'],
      ],
      icon: '\uD83C\uDFE0',
      title: 'Residential Cost Calculator',
      subtitle: 'Ghana Home Building Cost Estimation Engine',
      extrasLabel: 'Home Amenities & Extras',
    },
    'non-residential': {
      types: [
        ['commercial-office', 'Commercial Office'],
        ['retail',            'Retail / Shop'],
        ['industrial',        'Industrial / Factory'],
        ['warehouse',         'Warehouse / Logistics'],
        ['educational',       'School / Educational'],
        ['healthcare',        'Hospital / Healthcare'],
        ['mixed-use',         'Mixed-Use Development'],
        ['hospitality',       'Hotel / Hospitality'],
      ],
      finishes: [
        ['basic',     'Basic / Functional'],
        ['standard',  'Standard'],
        ['premium',   'Premium'],
        ['high-spec', 'High-Spec'],
      ],
      roofs: [
        ['metal', 'Metal Roof'],
        ['tile',  'Clay Tile'],
        ['flat',  'Flat / Concrete'],
        ['green', 'Green Roof'],
      ],
      extras: [
        ['parking',     'Parking Lot'],
        ['hvac',        'HVAC System'],
        ['fire-safety', 'Fire Suppression'],
        ['elevator',    'Elevator / Lift'],
        ['security',    'Security System'],
        ['landscaping', 'Landscaping'],
        ['backup-gen',  'Backup Generator'],
        ['data-cabling','Data Cabling'],
      ],
      icon: '\uD83C\uDFE2',
      title: 'Non-Residential Cost Calculator',
      subtitle: 'Ghana Commercial & Industrial Cost Estimation Engine',
      extrasLabel: 'Building Systems & Amenities',
    },
  };

  var currentCategory = 'residential';

  function populateCategory(cat) {
    var d = DATA[cat];
    currentCategory = cat;

    $('#bcgh-rcv2-header-icon').html(d.icon);
    $('#bcgh-rcv2-header-title').text(d.title);
    $('#bcgh-rcv2-header-sub').text(d.subtitle);
    $('#bcgh-rcv2-extras-label').text(d.extrasLabel);

    var typeSel = $('#rc-type').empty();
    $.each(d.types, function(i, t) {
      typeSel.append('<option value="' + t[0] + '">' + t[1] + '</option>');
    });

    var finishSel = $('#rc-finish').empty();
    $.each(d.finishes, function(i, f) {
      finishSel.append('<option value="' + f[0] + '"' + (f[0] === 'standard' ? ' selected' : '') + '>' + f[1] + '</option>');
    });

    var roofSel = $('#rc-roof').empty();
    $.each(d.roofs, function(i, r) {
      roofSel.append('<option value="' + r[0] + '"' + (r[0] === 'tile' ? ' selected' : '') + '>' + r[1] + '</option>');
    });

    var extrasCont = $('#bcgh-rcv2-extras-container').empty();
    $.each(d.extras, function(i, e) {
      var checked = (cat === 'residential' && e[0] === 'carport') ? ' checked' : '';
      extrasCont.append(
        '<label class="bcgh-rcv2-cb"><input type="checkbox" id="rc-' + e[0] + '"' + checked + '> ' + e[1] + '</label>'
      );
    });

    $('.bcgh-rcv2-cat-btn').removeClass('bcgh-rcv2-cat-active');
    $('#bcgh-rcv2-cat-' + (cat === 'residential' ? 'res' : 'nonres')).addClass('bcgh-rcv2-cat-active');
  }

  // ---- Category toggle ----
  $(document).on('click', '.bcgh-rcv2-cat-btn', function() {
    var cat = $(this).data('category');
    if (cat === currentCategory) return;
    populateCategory(cat);
    $('#bcgh-rcv2-results').hide();
  });

  // ---- Advanced toggle ----
  $('#bcgh-rcv2-adv-toggle').on('click', function() {
    var b = $('#bcgh-rcv2-adv-body');
    b.slideToggle(180);
    $(this).html(b.is(':visible') ? '\u25B2 Amenities &amp; Advanced' : '\u25BC Amenities &amp; Advanced');
  });

  // ---- Calculate ----
  $('#bcgh-rcv2-calc').on('click', function() {
    var btn = $(this);
    btn.prop('disabled', true).html('\u231B Estimating...');

    var extras = [];
    $('.bcgh-rcv2-cb input[type=checkbox]:checked').each(function() {
      extras.push($(this).attr('id').replace('rc-', ''));
    });

    $.post(BCGH_RC.ajax_url, {
      action: 'buildcostgh_rc',
      category: currentCategory,
      project: $('#rc-project').val(),
      location: $('#rc-location').val(),
      building: $('#rc-type').val(),
      area: $('#rc-area').val(),
      floors: $('#rc-floors').val(),
      height: $('#rc-height').val(),
      finish: $('#rc-finish').val(),
      roof: $('#rc-roof').val(),
      extras: extras,
      professional: $('#rc-professional').val(),
      contingency: $('#rc-contingency').val(),
      nonce: BCGH_RC.nonce
    }, function(res) {
      if (res.success) {
        var d = res.data;
        var fmt = function(n) { return 'GHS ' + Number(n).toLocaleString('en-US', {minimumFractionDigits:2,maximumFractionDigits:2}); };
        var fmtS = function(n) { return 'GHS ' + Number(n).toLocaleString('en-US', {maximumFractionDigits:0}); };

        $('#rcv2-total-ghs').text(fmt(d.grand_total));
        $('#rcv2-total-usd').text('~USD ' + Number(d.usd_total).toLocaleString('en-US'));
        $('#rcv2-sqm').text(fmtS(d.cost_per_sqm));
        $('#rcv2-area').text(d.area.toLocaleString() + ' m\u00b2 / ' + d.floors + ' fl');
        $('#rcv2-loc').text(d.location + ' x' + d.location_factor.toFixed(2));

        var bd = '';
        $.each(d.elements, function(i, e) {
          bd += '<div class="bcgh-rcv2-brow">';
          bd += '<div class="bcgh-rcv2-bl">' + e.name + '</div>';
          bd += '<div class="bcgh-rcv2-bbar"><div class="bcgh-rcv2-bfill" style="width:' + e.pct + '%;"></div></div>';
          bd += '<div class="bcgh-rcv2-bamt">' + fmtS(e.amount) + '</div>';
          bd += '</div>';
        });
        $('#rcv2-breakdown').html(bd);

        var sm = '<div class="bcgh-rcv2-srow"><span>Construction Cost</span><span>' + fmt(d.construction_cost) + '</span></div>';
        if (d.fees_total > 0) sm += '<div class="bcgh-rcv2-srow"><span>Professional Fees</span><span>' + fmt(d.fees_total) + '</span></div>';
        if (d.extras_total > 0) sm += '<div class="bcgh-rcv2-srow"><span>' + (currentCategory === 'residential' ? 'Amenities &amp; Extras' : 'Building Systems') + '</span><span>' + fmt(d.extras_total) + '</span></div>';
        sm += '<div class="bcgh-rcv2-srow"><span>Contingency (' + d.contingency_pct + '%)</span><span>' + fmt(d.contingency_amount) + '</span></div>';
        sm += '<div class="bcgh-rcv2-srow bcgh-rcv2-stotal"><span>Grand Total</span><span>' + fmt(d.grand_total) + '</span></div>';
        sm += '<div class="bcgh-rcv2-srow" style="font-size:10px;color:#6B8A84;border:none;"><span>USD Equivalent</span><span>~USD ' + Number(d.usd_total).toLocaleString('en-US') + '</span></div>';
        $('#rcv2-summary').html(sm);

        $('#bcgh-rcv2-results').fadeIn(300);
      } else {
        alert('Error: ' + (res.data.message || 'Calculation failed.'));
      }
      btn.prop('disabled', false).html('\uD83D\uDCCA Estimate Cost');
    }).fail(function() {
      btn.prop('disabled', false).html('\uD83D\uDCCA Estimate Cost');
      alert('Network error. Please check your connection and try again.');
    });
  });

  // ---- Init ----
  populateCategory('residential');
});