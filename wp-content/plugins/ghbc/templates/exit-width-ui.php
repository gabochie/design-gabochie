<div class="ghbc-ew-container">
  <div class="ghbc-fs-header">
    <span class="ghbc-chat-icon">&#128297;</span>
    <span class="ghbc-chat-title">Exit Width Calculator</span>
  </div>
  <div class="ghbc-fs-form" id="ghbc-ew-form">
    <p style="color:#666;font-size:14px;">Calculate required exit widths based on occupant load and exit type per Part 8.</p>
    <div class="ghbc-fs-grid">
      <label>Occupant Load (number of people)
        <input type="number" class="ghbc-fs-input" id="ew-occupants" min="1" max="5000" value="100">
      </label>
      <label>Exit Component Type
        <select class="ghbc-fs-input" id="ew-type">
          <option value="stairs">Stairs (0.2 in/mm per person)</option>
          <option value="doors">Doors (0.15 in/mm per person)</option>
          <option value="corridors">Corridors (0.1 in/mm per person)</option>
        </select>
      </label>
    </div>
    <button class="ghbc-btn" id="ghbc-ew-check">Calculate Exit Width</button>
  </div>
  <div class="ghbc-fs-result" id="ghbc-ew-result" style="display:none;"></div>
</div>
