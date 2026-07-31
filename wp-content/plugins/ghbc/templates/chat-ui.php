<div class="ghbc-chat-container" data-product="<?php echo esc_attr($product); ?>">
  <div class="ghbc-chat-header">
    <span class="ghbc-chat-icon">&#128269;</span>
    <span class="ghbc-chat-title">GhBC <?php echo $product === 'compliance' ? 'Compliance' : ($product === 'small_building' ? 'Small Buildings' : 'Assistant'); ?></span>
    <span class="ghbc-remaining" id="ghbc-remaining"></span>
  </div>
  <div class="ghbc-chat-messages" id="ghbc-messages">
    <div class="ghbc-message ghbc-bot">
      <div class="ghbc-avatar">&#129302;</div>
      <div class="ghbc-bubble">
        Ask me anything about the Ghana Building Code GS 1207:2018.
        <div class="ghbc-examples">
          Try: <button class="ghbc-example-btn" type="button">"Minimum ceiling height for a habitable room?"</button>
          <button class="ghbc-example-btn" type="button">"What fire rating for a 3-storey mixed-use building?"</button>
          <button class="ghbc-example-btn" type="button">"Maximum travel distance for Group B?"</button>
        </div>
      </div>
    </div>
  </div>
  <div class="ghbc-chat-input">
    <textarea class="ghbc-input" id="ghbc-input" rows="1" placeholder="Ask about the building code..."></textarea>
    <button class="ghbc-send" id="ghbc-send" title="Send">&#10148;</button>
  </div>
  <div class="ghbc-disclaimer">This is advisory. Always verify with a qualified professional.</div>
</div>
