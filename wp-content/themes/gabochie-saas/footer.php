</main>

<div class="newsletter-bar">
  <div class="newsletter-inner">
    <div>
      <div class="nl-label">Stay Ahead</div>
      <div class="nl-text">Get Ghana construction intelligence &amp; new tool releases monthly.</div>
    </div>
    <form class="newsletter-form" action="#" method="post">
      <input type="email" placeholder="you@firm.com" required>
      <button type="submit">Subscribe</button>
    </form>
  </div>
</div>

<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="fb1">GABOCHIE SAAS</div>
      <div class="fb2">The intelligence layer for Ghana's built environment. AI-powered tools for architects, developers, and construction professionals.</div>
    </div>
    <div class="footer-col">
      <h4>Platform</h4>
      <a href="/tools/">All Tools</a>
      <a href="/pricing/">Pricing</a>
      <a href="/dashboard/">My Workspace</a>
    </div>
    <div class="footer-col">
      <h4>Company</h4>
      <a href="https://design.gabochie.com">Gabochie Design</a>
      <a href="/about/">About</a>
      <a href="/contact/">Contact</a>
    </div>
    <div class="footer-col">
      <h4>Resources</h4>
      <a href="/blog/">Blog</a>
      <a href="/docs/">Documentation</a>
      <a href="/privacy/">Privacy Policy</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> Gabochie Design Studio &middot; Accra &middot; London &middot; Toronto</p>
    <p>Built for Ghana AEC Professionals</p>
  </div>
</footer>

</div>

<script>
document.getElementById('navToggle')?.addEventListener('click', function(){
  document.getElementById('navCollapse').classList.toggle('open');
});
</script>

<?php wp_footer(); ?>
</body>
</html>
