<?php
/* Template Name: Pricing */
get_header(); ?>

<section class="section" style="padding-top:3rem">
  <div class="text-center anim-up">
    <div class="sec-label">Plans &amp; Pricing</div>
    <h1 class="sec-title" style="font-size:clamp(1.8rem,3vw,2.4rem)">Choose Your Professional Tier</h1>
    <p class="sec-sub" style="margin:0.8rem auto 0;max-width:560px">Every tool is accessible on every plan. Higher tiers unlock unlimited usage, premium outputs, and team features.</p>
  </div>

  <div class="pricing-toggle anim-up-d1">
    <span>Monthly</span>
    <div class="toggle-switch" id="billingToggle"></div>
    <span>Annual <span class="save-badge">Save 20%</span></span>
  </div>

  <?php $tiers = gs_subscription_tiers(); ?>
  <div class="plans anim-up-d2">
    <?php foreach ($tiers as $key => $t) :
      $annual_price = round($t['price'] * 12 * 0.8 / 12);
    ?>
    <div class="plan <?php echo $t['popular'] ? 'pop' : ''; ?>">
      <div class="plan-name"><?php echo esc_html($t['name']); ?></div>
      <div class="plan-desc"><?php echo esc_html($t['desc']); ?></div>
      <div class="plan-price">
        <span class="mo-price">$<?php echo $t['price']; ?></span>
        <span class="yr-price" style="display:none">$<?php echo $annual_price; ?></span>
        <sub>/mo</sub>
      </div>
      <?php if ($t['ghs']) : ?>
        <div class="plan-ghs">
          <span class="mo-ghs">GH₵ <?php echo number_format($t['ghs']); ?>/mo</span>
          <span class="yr-ghs" style="display:none">GH₵ <?php echo number_format(round($t['ghs'] * 12 * 0.8 / 12)); ?>/mo</span>
        </div>
      <?php endif; ?>
      <ul class="plan-feats">
        <?php foreach ($t['features'] as $f) : ?>
        <li><?php echo esc_html($f); ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($key === 'free') : ?>
        <a href="<?php echo esc_url(wp_login_url()); ?>" class="plan-btn plan-btn-outline">Get Started Free</a>
      <?php elseif ($key === 'enterprise') : ?>
        <a href="/contact/" class="plan-btn plan-btn-outline">Contact Sales</a>
      <?php else : ?>
        <a href="/pricing/" class="plan-btn">Start 14-Day Trial</a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Comparison Table -->
  <div class="anim-up-d3">
    <h2 class="sec-title" style="text-align:center;margin:3.5rem 0 1.5rem">Full Feature Comparison</h2>
    <div class="compare-wrap">
      <table class="compare-table">
        <thead>
          <tr>
            <th style="width:200px">Feature</th>
            <th>Free</th>
            <th>Pro</th>
            <th>Enterprise</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rows = [
            ['Monthly tool uses', '3', 'Unlimited', 'Unlimited'],
            ['Cost calculators', '✓', '✓', '✓'],
            ['AI proposal generator', '2/mo', 'Unlimited', 'Unlimited'],
            ['PDF export', 'Watermarked', 'Clean', 'White-label'],
            ['DOCX / Excel export', '✗', '✓', '✓'],
            ['API access', '✗', '✓', '✓'],
            ['Team seats', '1', '3', 'Unlimited'],
            ['Ghana cost database', 'Static', 'Live updates', 'Live + custom'],
            ['Priority support', '✗', 'Email (24hr)', 'Dedicated manager'],
            ['SSO / SAML', '✗', '✗', '✓'],
            ['Custom AI training', '✗', '✗', '✓'],
            ['SLA guarantee', '✗', '✗', '✓'],
          ];
          foreach ($rows as $r) {
            echo '<tr><td>' . $r[0] . '</td>';
            for ($i = 1; $i <= 3; $i++) {
              $v = $r[$i];
              if ($v === '✓') echo '<td><span class="ck">✓</span></td>';
              elseif ($v === '✗') echo '<td><span class="cx">✗</span></td>';
              else echo '<td>' . $v . '</td>';
            }
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="cta-box">
    <div>
      <h2>Ready to Work Smarter?</h2>
      <p>Join 500+ Ghanaian AEC professionals already using Gabochie data to win more work.</p>
    </div>
    <div class="cta-btns">
      <a href="<?php echo esc_url(wp_login_url()); ?>" class="btn-primary-lg">Start Free →</a>
      <a href="/contact/" class="btn-ghost-lg">Book Demo</a>
    </div>
  </div>
</section>

<script>
document.getElementById('billingToggle').addEventListener('click', function(){
  this.classList.toggle('active');
  var isYearly = this.classList.contains('active');
  document.querySelectorAll('.mo-price').forEach(function(el){ el.style.display = isYearly ? 'none' : ''; });
  document.querySelectorAll('.yr-price').forEach(function(el){ el.style.display = isYearly ? '' : 'none'; });
  document.querySelectorAll('.mo-ghs').forEach(function(el){ el.style.display = isYearly ? 'none' : ''; });
  document.querySelectorAll('.yr-ghs').forEach(function(el){ el.style.display = isYearly ? '' : 'none'; });
});
</script>

<?php get_footer(); ?>
