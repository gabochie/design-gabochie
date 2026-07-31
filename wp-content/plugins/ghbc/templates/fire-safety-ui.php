<div class="ghbc-fs-container">
  <div class="ghbc-fs-header">
    <span class="ghbc-chat-icon">&#128293;</span>
    <span class="ghbc-chat-title">Fire Safety Design Assistant</span>
  </div>
  <div class="ghbc-fs-form" id="ghbc-fs-form">
    <p style="color:#666;font-size:14px;">Get fire resistance ratings, sprinkler requirements, and travel distances based on occupancy.</p>
    <div class="ghbc-fs-grid">
      <label>Occupancy Group
        <select class="ghbc-fs-input" id="fs-occupancy">
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
            <option value="F-1">F-1 (Factory moderate)</option>
            <option value="F-2">F-2 (Factory low hazard)</option>
          </optgroup>
          <optgroup label="High Hazard">
            <option value="H">H (High Hazard)</option>
          </optgroup>
          <optgroup label="Institutional">
            <option value="I-1">I-1 (Assisted living)</option>
            <option value="I-2">I-2 (Hospitals)</option>
            <option value="I-3">I-3 (Detention)</option>
          </optgroup>
          <optgroup label="Mercantile">
            <option value="M">M (Retail / Shops)</option>
          </optgroup>
          <optgroup label="Residential">
            <option value="R-1">R-1 (Hotels)</option>
            <option value="R-2">R-2 (Apartments)</option>
            <option value="R-3">R-3 (Single family)</option>
            <option value="R-4">R-4 (Small care)</option>
          </optgroup>
          <optgroup label="Storage">
            <option value="S-1">S-1 (Storage moderate)</option>
            <option value="S-2">S-2 (Storage low hazard)</option>
          </optgroup>
          <optgroup label="Utility">
            <option value="U">U (Utility / Misc)</option>
          </optgroup>
        </select>
      </label>
      <label>Building Height
        <select class="ghbc-fs-input" id="fs-height">
          <option value="up to 2 storeys">Up to 2 storeys</option>
          <option value="3 to 4 storeys">3 to 4 storeys</option>
          <option value="5 to 6 storeys">5 to 6 storeys</option>
          <option value="7+ storeys">7+ storeys</option>
        </select>
      </label>
      <label>Total Floor Area (m&sup2;)
        <input type="number" class="ghbc-fs-input" id="fs-area" min="1" value="500">
      </label>
      <label>Number of Storeys
        <input type="number" class="ghbc-fs-input" id="fs-storeys" min="1" max="50" value="2">
      </label>
    </div>
    <div class="ghbc-fs-checks">
      <label><input type="checkbox" id="fs-sprinklered"> Building has automatic sprinkler system</label>
      <label><input type="checkbox" id="fs-basement"> Building has a basement</label>
    </div>
    <button class="ghbc-btn" id="ghbc-fs-check">Check Fire Safety Requirements</button>
  </div>
  <div class="ghbc-fs-result" id="ghbc-fs-result" style="display:none;"></div>
</div>
