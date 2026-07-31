<div class="ghbc-sb-container">
  <div class="ghbc-sb-header">
    <span class="ghbc-chat-icon">&#127969;</span>
    <span class="ghbc-chat-title">Small Buildings Auto-Check (Part 35)</span>
  </div>
  <div class="ghbc-sb-form" id="ghbc-sb-form">
    <p style="color:#666;font-size:14px;">Check if your building qualifies for the simplified Part 35 requirements.</p>
    <div class="ghbc-sb-grid">
      <label>Storeys
        <input type="number" class="ghbc-sb-input" id="sb-storeys" min="1" max="10" value="1">
      </label>
      <label>Floor area (m&sup2;)
        <input type="number" class="ghbc-sb-input" id="sb-area" min="1" value="100">
      </label>
      <label>Height (m)
        <input type="number" class="ghbc-sb-input" id="sb-height" min="1" step="0.5" value="4">
      </label>
      <label>Occupancy type
        <select class="ghbc-sb-input" id="sb-occupancy">
          <option value="residential">Residential</option>
          <option value="retail">Retail / Shop</option>
          <option value="office">Office</option>
          <option value="other">Other</option>
        </select>
      </label>
    </div>
    <button class="ghbc-btn" id="ghbc-sb-check">Check Qualification</button>
  </div>
  <div class="ghbc-sb-result" id="ghbc-sb-result" style="display:none;"></div>
</div>
