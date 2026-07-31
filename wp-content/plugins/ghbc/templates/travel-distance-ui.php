<div class="ghbc-td-container">
  <div class="ghbc-fs-header">
    <span class="ghbc-chat-icon">&#128200;</span>
    <span class="ghbc-chat-title">Travel Distance Calculator</span>
  </div>
  <div class="ghbc-fs-form" id="ghbc-td-form">
    <p style="color:#666;font-size:14px;">Calculate maximum allowable travel distances based on occupancy and sprinkler status per Part 8.</p>
    <div class="ghbc-fs-grid">
      <label>Occupancy Group
        <select class="ghbc-fs-input" id="td-occupancy">
          <optgroup label="Assembly">
            <option value="A-1">A-1 (Theatres / Performance)</option>
            <option value="A-2">A-2 (Restaurants / Nightclubs)</option>
            <option value="A-3">A-3 (Churches / Auditoriums)</option>
          </optgroup>
          <optgroup label="Business">
            <option value="B" selected>B (Business / Office)</option>
          </optgroup>
          <optgroup label="Educational">
            <option value="E">E (Educational / Schools)</option>
          </optgroup>
          <optgroup label="Factory / Industrial">
            <option value="F">F (Factory / Industrial)</option>
          </optgroup>
          <optgroup label="High Hazard">
            <option value="H">H (High Hazard)</option>
          </optgroup>
          <optgroup label="Institutional">
            <option value="I">I (Institutional)</option>
          </optgroup>
          <optgroup label="Mercantile">
            <option value="M">M (Retail / Shops)</option>
          </optgroup>
          <optgroup label="Residential">
            <option value="R-1">R-1 (Hotels)</option>
            <option value="R-2">R-2 (Apartments)</option>
          </optgroup>
          <optgroup label="Storage">
            <option value="S-1">S-1 (Storage moderate)</option>
            <option value="S-2">S-2 (Storage low hazard)</option>
          </optgroup>
        </select>
      </label>
    </div>
    <div class="ghbc-fs-checks">
      <label><input type="checkbox" id="td-sprinklered"> Building has automatic sprinkler system</label>
    </div>
    <button class="ghbc-btn" id="ghbc-td-check">Calculate Travel Distance</button>
  </div>
  <div class="ghbc-fs-result" id="ghbc-td-result" style="display:none;"></div>
</div>
