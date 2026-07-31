<div class="ghbc-fr-container ghbc-fs-container">
  <div class="ghbc-fs-header">
    <span class="ghbc-chat-icon">&#128293;</span>
    <span class="ghbc-chat-title">Fire Rating Calculator</span>
  </div>
  <div class="ghbc-fs-form" id="ghbc-fr-form">
    <p style="color:#666;font-size:14px;">Calculate required fire resistance ratings for building elements per Part 8.</p>
    <div class="ghbc-fs-grid">
      <label>Occupancy Group
        <select class="ghbc-fs-input" id="fr-occupancy">
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
            <option value="I-1">I-1 (Institutional)</option>
            <option value="I-2">I-2 (Institutional)</option>
            <option value="I-3">I-3 (Institutional)</option>
          </optgroup>
          <optgroup label="Mercantile">
            <option value="M">M (Retail / Shops)</option>
          </optgroup>
          <optgroup label="Residential">
            <option value="R-1">R-1 (Hotels)</option>
            <option value="R-2">R-2 (Apartments)</option>
            <option value="R-3">R-3 (Dwellings)</option>
            <option value="R-4">R-4 (Residential care)</option>
          </optgroup>
          <optgroup label="Storage / Utility">
            <option value="S-1">S-1 (Storage moderate)</option>
            <option value="S-2">S-2 (Storage low hazard)</option>
            <option value="U">U (Utility)</option>
          </optgroup>
        </select>
      </label>
      <label>Building Height
        <select class="ghbc-fs-input" id="fr-height">
          <option value="up to 2 storeys">Up to 2 storeys</option>
          <option value="3 to 4 storeys">3 to 4 storeys</option>
          <option value="5 to 6 storeys">5 to 6 storeys</option>
          <option value="7+ storeys">7+ storeys</option>
        </select>
      </label>
      <label>Element Type
        <select class="ghbc-fs-input" id="fr-element">
          <option value="structural_frame">Structural Frame</option>
          <option value="bearing_walls">Bearing Walls</option>
          <option value="floors">Floors</option>
          <option value="roofs">Roofs</option>
        </select>
      </label>
    </div>
    <div class="ghbc-fs-checks">
      <label><input type="checkbox" id="fr-sprinklered"> Building has automatic sprinkler system throughout</label>
    </div>
    <button class="ghbc-btn" id="ghbc-fr-check">Calculate Fire Rating</button>
  </div>
  <div class="ghbc-fs-result" id="ghbc-fr-result" style="display:none;"></div>
</div>
