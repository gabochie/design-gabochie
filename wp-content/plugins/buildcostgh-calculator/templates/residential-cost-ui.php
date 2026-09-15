<div class="bcgh-rcv2">
  <div class="bcgh-rcv2-header">
    <div class="bcgh-rcv2-h-left">
      <span class="bcgh-rcv2-h-icon" id="bcgh-rcv2-header-icon">&#127968;</span>
      <div>
        <div class="bcgh-rcv2-h-title" id="bcgh-rcv2-header-title">Gabochie Estimator</div>
        <div class="bcgh-rcv2-h-sub" id="bcgh-rcv2-header-sub">Ghana Building Cost Estimation Engine</div>
      </div>
    </div>
    <div class="bcgh-rcv2-badge">GHC RATES</div>
  </div>

  <div class="bcgh-rcv2-body" id="bcgh-rcv2-body">
    <div class="bcgh-rcv2-fields">
      <div class="bcgh-rcv2-cat-toggle">
        <button type="button" class="bcgh-rcv2-cat-btn bcgh-rcv2-cat-active" data-category="residential" id="bcgh-rcv2-cat-res">&#127968; Residential</button>
        <button type="button" class="bcgh-rcv2-cat-btn" data-category="non-residential" id="bcgh-rcv2-cat-nonres">&#127963; Non-Residential</button>
      </div>

      <div class="bcgh-rcv2-row" style="flex-direction:column;gap:3px;">
        <label class="bcgh-rcv2-lbl" for="rc-project">Project name</label>
        <input type="text" class="bcgh-rcv2-inp bcgh-rcv2-inp-wide" id="rc-project" value="My Project" placeholder="Enter project name">
      </div>
      <div class="bcgh-rcv2-row">
        <div class="bcgh-rcv2-f"><label class="bcgh-rcv2-lbl" for="rc-location">Location</label><select class="bcgh-rcv2-inp" id="rc-location"><option value="accra">Accra</option><option value="tema">Tema</option><option value="kumasi">Kumasi</option><option value="takoradi">Takoradi</option><option value="cape-coast">Cape Coast</option><option value="tamale">Tamale</option><option value="other-urban" selected>Other Urban</option><option value="rural">Rural</option></select></div>
      </div>
      <div class="bcgh-rcv2-row">
        <div class="bcgh-rcv2-f"><label class="bcgh-rcv2-lbl" for="rc-type">Building Type</label><select class="bcgh-rcv2-inp" id="rc-type"></select></div>
        <div class="bcgh-rcv2-f"><label class="bcgh-rcv2-lbl" for="rc-area">Floor Area (m&sup2;)</label><input type="number" class="bcgh-rcv2-inp" id="rc-area" min="10" max="100000" value="250"><span class="bcgh-rcv2-unit">m&sup2;</span></div>
        <div class="bcgh-rcv2-f bcgh-rcv2-f-sm"><label class="bcgh-rcv2-lbl" for="rc-floors">Storeys</label><select class="bcgh-rcv2-inp" id="rc-floors"><option value="1">1 fl</option><option value="2" selected>2 fl</option><option value="3">3 fl</option><option value="4">4 fl</option><option value="5">5 fl</option><option value="6">6 fl</option><option value="10">10 fl</option><option value="15">15 fl</option><option value="20">20 fl</option></select></div>
      </div>
      <div class="bcgh-rcv2-row">
        <div class="bcgh-rcv2-f"><label class="bcgh-rcv2-lbl" for="rc-finish">Finish Standard</label><select class="bcgh-rcv2-inp" id="rc-finish"></select></div>
        <div class="bcgh-rcv2-f"><label class="bcgh-rcv2-lbl" for="rc-roof">Roof Type</label><select class="bcgh-rcv2-inp" id="rc-roof"></select></div>
        <div class="bcgh-rcv2-f bcgh-rcv2-f-sm"><label class="bcgh-rcv2-lbl" for="rc-height">Height</label><select class="bcgh-rcv2-inp" id="rc-height"><option value="2.4">2.4m</option><option value="2.7" selected>2.7m</option><option value="3.0">3.0m</option><option value="3.3">3.3m</option><option value="3.6">3.6m</option><option value="4.0">4.0m</option></select></div>
      </div>

      <div class="bcgh-rcv2-advanced">
        <div class="bcgh-rcv2-adv-h" id="bcgh-rcv2-adv-toggle">&#9660; Amenities &amp; Advanced</div>
        <div class="bcgh-rcv2-adv-body" id="bcgh-rcv2-adv-body" style="display:none;">
          <label class="bcgh-rcv2-lbl" id="bcgh-rcv2-extras-label">Extras &amp; Amenities</label>
          <div class="bcgh-rcv2-adv-checks" id="bcgh-rcv2-extras-container"></div>
          <div class="bcgh-rcv2-row" style="margin-top:6px;">
            <div class="bcgh-rcv2-f"><label class="bcgh-rcv2-lbl" for="rc-professional">Professional</label><select class="bcgh-rcv2-inp" id="rc-professional"><option value="all">Arch + Eng + QS</option><option value="basic">Arch + Eng</option><option value="full">Full Team + PM</option><option value="none">Exclude</option></select></div>
            <div class="bcgh-rcv2-f"><label class="bcgh-rcv2-lbl" for="rc-contingency">Contingency</label><select class="bcgh-rcv2-inp" id="rc-contingency"><option value="5">5%</option><option value="10" selected>10%</option><option value="15">15%</option><option value="20">20%</option></select></div>
          </div>
        </div>
      </div>

      <button type="button" class="bcgh-rcv2-calc" id="bcgh-rcv2-calc">&#128202; Estimate Cost</button>
    </div>

    <div class="bcgh-rcv2-results" id="bcgh-rcv2-results" style="display:none;">
      <div class="bcgh-rcv2-r-top">
        <div class="bcgh-rcv2-r-total">
          <div class="bcgh-rcv2-r-label">Total Estimated Cost</div>
          <div class="bcgh-rcv2-r-amount" id="rcv2-total-ghs"></div>
          <div class="bcgh-rcv2-r-usd" id="rcv2-total-usd"></div>
        </div>
        <div class="bcgh-rcv2-r-stats">
          <div class="bcgh-rcv2-r-stat"><span class="bcgh-rcv2-rs-l">Cost / m&sup2;</span><span class="bcgh-rcv2-rs-v" id="rcv2-sqm"></span></div>
          <div class="bcgh-rcv2-r-stat"><span class="bcgh-rcv2-rs-l">Floor Area</span><span class="bcgh-rcv2-rs-v" id="rcv2-area"></span></div>
          <div class="bcgh-rcv2-r-stat"><span class="bcgh-rcv2-rs-l">Location</span><span class="bcgh-rcv2-rs-v" id="rcv2-loc"></span></div>
        </div>
      </div>
      <div class="bcgh-rcv2-r-breakdown" id="rcv2-breakdown"></div>
      <div class="bcgh-rcv2-r-summary" id="rcv2-summary"></div>
      <div class="bcgh-rcv2-r-foot"><strong>&#9432;</strong> Advisory estimate based on Ghana market rates. Verify with professionals &mdash; questions? Call Gideon at <a href="tel:+233268248970">+233 26 824 8970</a>.</div>
    </div>
  </div>
</div>