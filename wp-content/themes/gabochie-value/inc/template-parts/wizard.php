<?php
/**
 * Template part — the five-question wizard (Get Clarity)
 * IDs preserved 1:1 with assets/js/main.js. Shared by front-page.php
 * and template-contact.php.
 * @package Gabochie_Value
 */
if (!defined('ABSPATH')) exit;
?>
<!-- ========== WIZARD / GET CLARITY ========== -->
<section class="wizard" id="clarity">
  <div class="container">
    <div class="wiz-head">
      <div class="sec-ey"><span>Start here</span></div>
      <h2 class="sec-h2 reveal">Answer five questions. <em>Leave with a direction.</em></h2>
      <p class="wiz-hint reveal">No long forms, no jargon. Tell us where you are and what's in your way &mdash; we'll recommend your next step and send you the project guide.</p>
    </div>
    <div class="wiz reveal">
      <div class="wiz-progress" id="wizProgress">
        <div class="wiz-p-step active"><span class="p-num">1</span><span class="p-label">Situation</span></div>
        <span class="p-dash"></span>
        <div class="wiz-p-step"><span class="p-num">2</span><span class="p-label">Stopping you</span></div>
        <span class="p-dash"></span>
        <div class="wiz-p-step"><span class="p-num">3</span><span class="p-label">About you</span></div>
        <span class="p-dash"></span>
        <div class="wiz-p-step"><span class="p-num">4</span><span class="p-label">Next step</span></div>
        <span class="p-dash"></span>
        <div class="wiz-p-step"><span class="p-num">5</span><span class="p-label">Done</span></div>
      </div>
      <div class="wiz-body">

        <!-- STEP 1: Journey -->
        <div class="wiz-step active" data-step="1">
          <h3 class="wiz-q">Where are you in your building journey?</h3>
          <p class="wiz-hint">Choose the closest match. There's no wrong answer.</p>
          <div class="choice-grid" id="journeyChoices">
            <button type="button" class="choice" data-journey="idea">
              <span class="c-n">A</span><span class="c-t">I have an idea</span><span class="c-d">An ambition, no land yet</span>
            </button>
            <button type="button" class="choice" data-journey="land">
              <span class="c-n">B</span><span class="c-t">I have land</span><span class="c-d">Land, no clear plan yet</span>
            </button>
            <button type="button" class="choice" data-journey="exploring">
              <span class="c-n">C</span><span class="c-t">I'm exploring what to build</span><span class="c-d">Not sure what the site or idea can support</span>
            </button>
            <button type="button" class="choice" data-journey="viability">
              <span class="c-n">D</span><span class="c-t">I need to know if it's viable</span><span class="c-d">Feasibility before anything is committed</span>
            </button>
            <button type="button" class="choice" data-journey="design">
              <span class="c-n">E</span><span class="c-t">I need architectural design</span><span class="c-d">Drawings, brief, concept</span>
            </button>
            <button type="button" class="choice" data-journey="drawings">
              <span class="c-n">F</span><span class="c-t">I already have drawings</span><span class="c-d">Need review, cost check or next steps</span>
            </button>
            <button type="button" class="choice" data-journey="ready">
              <span class="c-n">G</span><span class="c-t">I'm ready to build</span><span class="c-d">Need a contractor and procurement process</span>
            </button>
            <button type="button" class="choice" data-journey="building">
              <span class="c-n">H</span><span class="c-t">I'm already building</span><span class="c-d">Construction is underway, need support</span>
            </button>
            <button type="button" class="choice" data-journey="stuck">
              <span class="c-n">I</span><span class="c-t">My project is stuck</span><span class="c-d">Stalled, over budget, or off track</span>
            </button>
            <button type="button" class="choice" data-journey="renovate">
              <span class="c-n">J</span><span class="c-t">I want to renovate or expand</span><span class="c-d">Existing building, new requirements</span>
            </button>
            <button type="button" class="choice" data-journey="investing">
              <span class="c-n">K</span><span class="c-t">I'm investing or developing</span><span class="c-d">Development project, returns matter</span>
            </button>
            <button type="button" class="choice notsure" data-journey="notsure">
              <span class="c-n">L</span><span class="c-t">I'm not sure</span><span class="c-d">That's exactly where we can help.</span>
            </button>
          </div>
        </div>

        <!-- STEP 2: Blockers -->
        <div class="wiz-step" data-step="2">
          <h3 class="wiz-q">What's stopping your project from moving forward?</h3>
          <p class="wiz-hint">Pick the biggest blocker &mdash; we'll speak directly to that.</p>
          <div class="wiz-opt-group" id="blockerChoices">
            <button type="button" class="wiz-opt" data-blocker="dont-know-build">I don't know what to build</button>
            <button type="button" class="wiz-opt" data-blocker="feasibility">I don't know if it's feasible</button>
            <button type="button" class="wiz-opt" data-blocker="cost">I don't know what it will cost</button>
            <button type="button" class="wiz-opt" data-blocker="design">I need a better design</button>
            <button type="button" class="wiz-opt" data-blocker="approvals">I need planning or approval guidance</button>
            <button type="button" class="wiz-opt" data-blocker="contractor">I don't know how to select a contractor</button>
            <button type="button" class="wiz-opt" data-blocker="worried">Construction has started but I'm worried</button>
            <button type="button" class="wiz-opt" data-blocker="cost-control">Costs are getting out of control</button>
            <button type="button" class="wiz-opt" data-blocker="stuck">My project is stuck</button>
            <button type="button" class="wiz-opt" data-blocker="abroad">I live abroad and need someone I can trust</button>
            <button type="button" class="wiz-opt" data-blocker="coordination">I need help coordinating professionals</button>
            <button type="button" class="wiz-opt" data-blocker="renovate">I want to renovate or expand</button>
            <button type="button" class="wiz-opt" data-blocker="notsure">I'm not sure</button>
          </div>
        </div>

        <!-- STEP 3: Profile -->
        <div class="wiz-step" data-step="3">
          <h3 class="wiz-q">Almost there &mdash; a few details so your next step is actually useful.</h3>
          <p class="wiz-hint">We only ask what we need to give you a tailored recommendation.</p>
          <div id="profileFields">
            <div class="wiz-field">
              <label for="pfName">Full name <span class="req">*</span></label>
              <input type="text" id="pfName" name="name" autocomplete="name" required>
            </div>
            <div class="wiz-field">
              <label for="pfEmail">Email <span class="req">*</span></label>
              <input type="email" id="pfEmail" name="email" autocomplete="email" required>
            </div>
            <div class="wiz-field">
              <label for="pfPhone">WhatsApp / phone</label>
              <input type="tel" id="pfPhone" name="phone" autocomplete="tel">
            </div>
            <div class="wiz-field">
              <label for="pfLocation">Where are you based?</label>
              <select id="pfLocation" name="location">
                <option value="">Select&hellip;</option>
                <option>In Ghana</option>
                <option>Abroad (diaspora)</option>
                <option>Prefer not to say</option>
              </select>
            </div>
            <div class="wiz-field">
              <label for="pfType">What are you building (or planning)?</label>
              <select id="pfType" name="type">
                <option value="">Select&hellip;</option>
                <option>Residential home</option>
                <option>Commercial</option>
                <option>Mixed-use</option>
                <option>Land &amp; development</option>
                <option>Renovation or expansion</option>
                <option>Not sure yet</option>
              </select>
            </div>
            <div class="wiz-field">
              <label for="pfTimeline">When do you want to move?</label>
              <select id="pfTimeline" name="timeline">
                <option value="">Select&hellip;</option>
                <option>Just exploring</option>
                <option>Within 6 months</option>
                <option>6&ndash;12 months</option>
                <option>12+ months</option>
                <option>Not sure</option>
              </select>
            </div>
          </div>
        </div>

        <!-- STEP 4: Recommendation -->
        <div class="wiz-step" data-step="4">
          <div class="rec-ey">Based on what you've told us</div>
          <h3 class="wiz-q" id="recTitle">Project Clarity &amp; Feasibility Consultation</h3>
          <div class="rec-grid">
            <div class="rec-col"><h4>Why this is recommended</h4><p id="recWhy"></p></div>
            <div class="rec-col"><h4>What will happen</h4><p id="recWhat"></p></div>
            <div class="rec-col"><h4>What you'll leave with</h4><p id="recLeave"></p></div>
          </div>
          <div class="rec-ctas">
            <a class="btn-gold" id="recBook" href="#" target="_blank" rel="noopener">Request Your Consultation</a>
            <button class="btn-ghost" id="restart" type="button">Start over</button>
          </div>
          <div class="rec-guide">
            <p>Not ready to book? Email me the project guide.</p>
            <form class="guide-form" id="guideForm" novalidate>
              <input type="email" id="guideEmail" placeholder="Your email" required aria-label="Email for the project guide">
              <input type="text" name="website" id="guideWebsite" tabindex="-1" autocomplete="off" aria-hidden="true" class="gd-hp" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
              <input type="hidden" name="source" value="wizard">
              <button type="submit">Send the guide</button>
            </form>
            <div class="guide-ok" id="guideOk">Got it &mdash; the guide is on its way to your inbox.</div>
            <div class="guide-err" id="guideErr" role="alert"></div>
          </div>
        </div>

        <!-- STEP 5: Success -->
        <div class="wiz-step" data-step="5">
          <div class="success-panel">
            <div class="success-mark">&#10003;</div>
            <h3>You're set.</h3>
            <p>We've saved your details and prepared your consultation request. Send it on WhatsApp &mdash; one of our team will respond within one working day.</p>
            <div class="rec-ctas" style="justify-content:center">
              <a class="btn-gold" id="waDone" href="#" target="_blank" rel="noopener">Open WhatsApp</a>
              <a class="btn-ghost-w" href="<?php echo esc_url(gabochie_value_section_url('top')); ?>" style="margin-top:0.5rem">Back to top</a>
            </div>
          </div>
        </div>

      </div><!-- /wiz-body -->
      <div class="wiz-validate">
        <button class="btn-ghost" id="wizBack" type="button">Back</button>
        <span class="wiz-err" id="wizErr"></span>
        <button class="btn-solid" id="wizNext" type="button">Continue</button>
      </div>
    </div>
  </div>
</section>
