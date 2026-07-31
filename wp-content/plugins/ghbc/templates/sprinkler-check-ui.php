<div class="ghbc-sc-container ghbc-fs-container">
  <div class="ghbc-fs-header">
    <span class="ghbc-chat-icon">&#128167;</span>
    <span class="ghbc-chat-title">Sprinkler Requirement Check</span>
  </div>
  <div class="ghbc-fs-form" id="ghbc-sc-form">
    <p style="color:#666;font-size:14px;">Determine if an automatic sprinkler system is required per Part 8 of the GhBC.</p>
    <div class="ghbc-fs-grid">
      <label>Occupancy Group
        <select class="ghbc-fs-input" id="sc-occupancy">
          <option value="A">A (Assembly)</option>
          <option value="B" selected>B (Business / Office)</option>
          <option value="E">E (Educational / Schools)</option>
          <option value="F">F (Factory / Industrial)</option>
          <option value="H">H (High Hazard)</option>
          <option value="I">I (Institutional)</option>
          <option value="M">M (Retail / Shops)</option>
          <option value="R">R (Residential)</option>
          <option value="S">S (Storage)</option>
        </select>
      </label>
      <label>Total Floor Area (m&sup2;)
        <input type="number" class="ghbc-fs-input" id="sc-area" min="0" max="100000" value="500">
      </label>
      <label>Number of Storeys
        <input type="number" class="ghbc-fs-input" id="sc-storeys" min="1" max="50" value="2">
      </label>
    </div>
    <div class="ghbc-fs-checks">
      <label><input type="checkbox" id="sc-basement"> Building has a basement</label>
    </div>
    <button class="ghbc-btn" id="ghbc-sc-check">Check Sprinkler Requirement</button>
  </div>
  <div class="ghbc-fs-result" id="ghbc-sc-result" style="display:none;"></div>
</div>
