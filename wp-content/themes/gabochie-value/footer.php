<?php
/**
 * Footer template — redesigned 4-column layout
 * @package Gabochie_Value
 */
$phone      = gabochie_value_phone();
$phone_tel  = gabochie_value_phone_tel();
$email      = gabochie_value_email();
$whatsapp   = gabochie_value_whatsapp();
$socials    = gabochie_value_social_links();
$social_svgs = array(
    'facebook' => '<svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
    'x'        => '<svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
    'behance'  => '<svg viewBox="0 0 24 24"><text x="4" y="18" font-family="Georgia,serif" font-size="16" font-weight="700" fill="currentColor">Be</text></svg>',
    'linkedin' => '<svg viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4V9h4v1.5A6 6 0 0 1 16 8z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>',
    'tiktok'   => '<svg viewBox="0 0 24 24"><path d="M9 12a4 4 0 1 0 4 4V4c.5 2.5 2 4 5 4.5" stroke="currentColor" fill="none" stroke-width="1.8" stroke-linecap="round"/></svg>',
);
?>
<!-- ========== FOOTER ========== -->
<footer>
  <div class="foot-grid">

    <!-- Brand + Trust -->
    <div class="foot-brand">
      <a class="logo-link" href="<?php echo esc_url(home_url('/')); ?>">
        <svg class="logo-hex" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><polygon points="26,4 47,15 47,37 26,48 5,37 5,15" fill="#072E28" stroke="#C9A96E" stroke-width="2"/><text x="26" y="29" text-anchor="middle" font-family="Georgia,serif" font-size="17" font-weight="700" fill="#C9A96E">GD</text></svg>
        <span class="logo-text"><span class="logo-name">GABOCHIE DESIGN</span><span class="logo-tag">Design. Value. Transformation.</span></span>
      </a>
      <p>From first idea to completed project and beyond &mdash; we help you make better decisions at every stage of your building journey.</p>
      <div class="foot-trust">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4"/><path d="M12 2l7 4v5c0 5.25-3.5 9.74-7 11-3.5-1.26-7-5.75-7-11V6l7-4z"/></svg>
        <span>Registered &middot; Ghana</span>
      </div>
      <div class="foot-social">
        <?php foreach ($socials as $name => $url) : $svg = $social_svgs[$name] ?? ''; ?>
        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr(ucfirst($name)); ?>"><?php echo $svg; ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Services -->
    <div class="foot-col">
      <p class="foot-heading">Services</p>
      <ul>
        <li><a href="<?php echo esc_url(gabochie_value_section_url('services')); ?>">Clarity &amp; Feasibility</a></li>
        <li><a href="<?php echo esc_url(gabochie_value_section_url('services')); ?>">Building Design</a></li>
        <li><a href="<?php echo esc_url(gabochie_value_section_url('services')); ?>">Cost &amp; Value Intelligence</a></li>
        <li><a href="<?php echo esc_url(gabochie_value_section_url('services')); ?>">Procurement &amp; Delivery</a></li>
        <li><a href="<?php echo esc_url(gabochie_value_section_url('recovery')); ?>">Project Recovery</a></li>
      </ul>
    </div>

    <!-- Company -->
    <div class="foot-col">
      <p class="foot-heading">Company</p>
      <ul>
        <li><a href="<?php echo esc_url(home_url('/about/')); ?>">About Us</a></li>
        <li><a href="<?php echo esc_url(home_url('/development-quiz/')); ?>">Development Readiness Quiz</a></li>
        <li><a href="<?php echo esc_url(home_url('/building-cost-calculator/')); ?>">Build Cost Calculator</a></li>
        <li><a href="<?php echo esc_url(home_url('/thesis/')); ?>">Thesis</a></li>
        <li><a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a></li>
        <li><a href="<?php echo esc_url(home_url('/community/')); ?>">Community</a></li>
        <li><a href="<?php echo esc_url(home_url('/client-login/')); ?>">Client Portal</a></li>
        <li><a href="<?php echo esc_url(gabochie_value_section_url('projects')); ?>">Projects</a></li>
        <li><a href="<?php echo esc_url(gabochie_value_section_url('journey')); ?>">Building Journey</a></li>
        <li><a href="<?php echo esc_url(gabochie_value_section_url('diaspora')); ?>">For Diaspora</a></li>
        <li><a href="<?php echo esc_url(gabochie_value_section_url('resources')); ?>">Resources</a></li>
      </ul>
    </div>

    <!-- Contact -->
    <div class="foot-col">
      <p class="foot-heading">Contact</p>
      <ul>
        <li class="foot-contact">
          <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <a href="tel:<?php echo esc_attr($phone_tel); ?>"><?php echo esc_html($phone); ?></a>
        </li>
        <li class="foot-contact">
          <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <a href="#" data-email="<?php echo esc_attr($email); ?>" onclick="this.href='mailto:'+this.dataset.email;return true;"><?php echo esc_html($email); ?></a>
        </li>
        <li class="foot-contact">
          <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <span>P.O. Box SK 2125, Sakumono, Tema &mdash; Ghana</span>
        </li>
      </ul>
    </div>

  </div>

  <!-- Newsletter signup -->
  <div class="foot-newsletter">
    <div class="foot-newsletter-inner">
      <span class="foot-newsletter-label">Get practical building insights &mdash; no spam, unsubscribe anytime.</span>
      <form class="foot-newsletter-form" novalidate>
        <input type="email" placeholder="Your email" required aria-label="Email for newsletter">
        <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="gd-hp" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
        <input type="hidden" name="source" value="footer-newsletter">
        <button type="submit">Subscribe</button>
      </form>
      <div class="foot-newsletter-ok" style="display:none">Subscribed &mdash; welcome aboard.</div>
    </div>
  </div>

  <!-- Bottom bar: copyright + legal links -->
  <div class="foot-bottom">
    <span>&copy; <span id="year"></span> Gabochie Design &mdash; Value-Driven Development Facilitation. All rights reserved.</span>
    <div class="foot-legal">
      <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy</a>
      <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>">Terms</a>
      <a href="<?php echo esc_url(home_url('/cookie-policy/')); ?>">Cookies</a>
      <a href="<?php echo esc_url(home_url('/refund-policy/')); ?>">Refund</a>
    </div>
  </div>
</footer>

<!-- ========== FLOATING BUTTONS ========== -->
<a class="whatsapp-fab" href="https://wa.me/<?php echo esc_attr($whatsapp); ?>?text=Hello%20Gabochie%20Design%20%E2%80%94%20from%20your%20website." target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
  <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
</a>
<button class="back-to-top" id="backTop" aria-label="Back to top">&uarr;</button>

<?php wp_footer(); ?>
</body>
</html>
