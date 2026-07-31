(function() {
  'use strict';

  var app = document.getElementById('rc-app');
  if (!app) return;

  var btn = document.getElementById('rc-calc-btn');
  var resultsEl = document.getElementById('rc-result');
  var welcomeEl = document.getElementById('rc-welcome');
  var loaderEl = document.getElementById('rc-loader');
  var totalGhs = document.getElementById('rc-total-ghs');
  var totalUsd = document.getElementById('rc-total-usd');
  var rhMeta = document.getElementById('rc-rh-meta');
  var breakdownEl = document.getElementById('rc-breakdown');
  var saveBtn = document.getElementById('rc-save-btn');
  var dlBtn = document.getElementById('rc-dl-btn');
  var recalcBtn = document.getElementById('rc-recalc-btn');
  var savedList = document.getElementById('rc-saved-list');
  var nonce = gsData ? gsData.nonce : '';
  var ajaxUrl = gsData ? gsData.ajaxUrl : '';
  var lastData = null;

  function getFields() {
    return {
      building_type: document.getElementById('rc_type').value,
      region: document.getElementById('rc_region').value,
      floors: document.getElementById('rc_floors').value,
      area: document.getElementById('rc_area').value,
      finishing: document.getElementById('rc_finish').value,
    };
  }

  function showLoader() {
    welcomeEl.style.display = 'none';
    resultsEl.style.display = 'none';
    loaderEl.style.display = 'block';
    btn.disabled = true;
    btn.textContent = 'Calculating…';
  }

  function hideLoader() {
    loaderEl.style.display = 'none';
    btn.disabled = false;
    btn.textContent = 'Calculate Cost →';
  }

  function showError(msg) {
    welcomeEl.style.display = 'none';
    resultsEl.style.display = 'block';
    resultsEl.innerHTML = '<div class="rc-error">' + msg + '</div>';
  }

  function formatGHS(n) {
    return 'GH₵ ' + Number(n).toLocaleString('en-US');
  }

  function formatUSD(n) {
    return '$ ' + Number(n).toLocaleString('en-US');
  }

  function renderResults(data) {
    resultsEl.style.display = 'block';
    totalGhs.textContent = formatGHS(data.totals.ghs);
    totalUsd.textContent = '≈ ' + formatUSD(data.totals.usd) + ' · ' + formatGHS(data.totals.per_sqm) + '/m²';

    rhMeta.innerHTML =
      data.inputs.finishing + ' ' + data.inputs.building_type +
      ' · ' + data.inputs.floors + ' floor(s) · ' +
      data.inputs.area + ' m² · ' + data.inputs.region;

    var html = '<div class="rc-bd-title">Cost Breakdown</div>';
    data.categories.forEach(function(cat) {
      html += '<div class="rc-bd-row">' +
        '<div class="rc-bd-head">' +
          '<span class="rc-bd-label">' + cat.label + '</span>' +
          '<span class="rc-bd-pct">' + cat.pct + '%</span>' +
          '<span class="rc-bd-amt">' + formatGHS(cat.ghs) + '</span>' +
        '</div>' +
        '<div class="rc-bd-bar-wrap"><div class="rc-bd-bar" style="width:' + cat.pct + '%"></div></div>' +
        '<div class="rc-bd-desc">' + cat.desc + '</div>' +
      '</div>';
    });
    breakdownEl.innerHTML = html;
    lastData = data;
  }

  function formatDate() {
    var d = new Date();
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
  }

  function downloadPdf() {
    if (!lastData) return;
    var f = lastData.inputs;
    document.getElementById('rc-print-date').textContent = formatDate();
    document.getElementById('rc-print-inputs').innerHTML =
      '<table><tr><td>Building Type</td><td>' + f.building_type + '</td></tr>' +
      '<tr><td>Region</td><td>' + f.region + '</td></tr>' +
      '<tr><td>Floors</td><td>' + f.floors + '</td></tr>' +
      '<tr><td>Floor Area</td><td>' + f.area + ' m²</td></tr>' +
      '<tr><td>Finishing</td><td>' + f.finishing + '</td></tr></table>';
    document.getElementById('rc-print-total').innerHTML =
      '<div class="rc-pt-label">Total Estimated Cost</div>' +
      '<div class="rc-pt-amount">' + formatGHS(lastData.totals.ghs) + '</div>' +
      '<div class="rc-pt-sub">≈ ' + formatUSD(lastData.totals.usd) + ' · ' + formatGHS(lastData.totals.per_sqm) + '/m²</div>';
    var html = '<table><thead><tr><th>Category</th><th>%</th><th>Amount (GHS)</th><th>Amount (USD)</th></tr></thead><tbody>';
    lastData.categories.forEach(function(cat) {
      html += '<tr><td>' + cat.label + '</td><td>' + cat.pct + '%</td><td>' + formatGHS(cat.ghs) + '</td><td>' + formatUSD(cat.usd) + '</td></tr>';
    });
    html += '</tbody></table>';
    document.getElementById('rc-print-breakdown').innerHTML = html;
    window.print();
  }

  function calculate() {
    var fields = getFields();
    var formData = new FormData();
    formData.append('action', 'gs_calculate');
    formData.append('tool_id', 'res-cost');
    formData.append('nonce', nonce);
    formData.append('building_type', fields.building_type);
    formData.append('region', fields.region);
    formData.append('floors', fields.floors);
    formData.append('area', fields.area);
    formData.append('finishing', fields.finishing);

    showLoader();

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.onload = function() {
      hideLoader();
      if (xhr.status !== 200) { showError('Server error. Please try again.'); return; }
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          renderResults(resp.data);
        } else {
          showError(resp.data && resp.data.message ? resp.data.message : 'Calculation failed.');
        }
      } catch(e) {
        showError('Invalid response from server.');
      }
    };
    xhr.onerror = function() { hideLoader(); showError('Network error. Please check your connection.'); };
    xhr.send(formData);
  }

  function saveToDocs() {
    var totalEl = document.getElementById('rc-total-ghs');
    if (!totalEl || !totalEl.textContent) return;
    var label = 'Cost Estimate — ' + getFields().building_type + ', ' + getFields().region;
    var summary = 'Residential Cost Estimate: ' + totalEl.textContent;
    var formData = new FormData();
    formData.append('action', 'gs_save_doc');
    formData.append('nonce', nonce);
    formData.append('title', label);
    formData.append('summary', summary);
    formData.append('type', 'Cost Estimate');

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl, true);
    xhr.onload = function() {
      saveBtn.disabled = false;
      saveBtn.textContent = '💾 Saved ✓';
      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success) {
            addSavedItem(resp.data.id, label);
          }
        } catch(e) {}
      }
    };
    xhr.send(formData);
  }

  function addSavedItem(id, label) {
    var empty = savedList.querySelector('.rc-saved-empty');
    if (empty) empty.remove();
    var item = document.createElement('div');
    item.className = 'rc-saved-item';
    item.innerHTML = '<div class="rc-si-title">' + label + '</div><div class="rc-si-meta">Saved to Documents</div>';
    savedList.insertBefore(item, savedList.firstChild);
  }

  btn.addEventListener('click', calculate);

  recalcBtn.addEventListener('click', function() {
    resultsEl.style.display = 'none';
    welcomeEl.style.display = 'block';
  });

  saveBtn.addEventListener('click', saveToDocs);
  dlBtn.addEventListener('click', downloadPdf);

  document.querySelectorAll('.rc-input').forEach(function(el) {
    el.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') calculate();
    });
  });
})();
