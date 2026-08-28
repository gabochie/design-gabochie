<?php
/**
 * Template Name: Contact
 * VDAC contact.html port — wizard + contact details + per-page CTA band
 * @package Gabochie_Value
 */
get_header();

$clarity   = gabochie_value_clarity_url();
$phone     = gabochie_value_phone();
$phone_tel = gabochie_value_phone_tel();
?>

<main id="main">

<!-- ========== HERO ========== -->
<section class="hero hero--solo" id="top">
  <div class="hero-left">
    <div class="hero-location"><span class="hero-location-dot"></span> Sakumono &middot; Tema &mdash; Ghana</div>
    <h1 class="hero-h1">Get clarity on <em>your project.</em></h1>
    <div class="hero-h1-dim">Five questions. One clear direction.</div>
    <div class="hero-rule"></div>
    <p class="hero-sub">Tell us where you are and what's in your way &mdash; we'll recommend your next step and send you the project guide. No long forms, no jargon, no pressure.</p>
    <div class="hero-actions">
      <a class="btn-gold" href="<?php echo esc_url($clarity); ?>">Start the wizard</a>
      <a class="btn-ghost-w" href="tel:<?php echo esc_attr($phone_tel); ?>">Call <?php echo esc_html($phone); ?></a>
    </div>
    <div class="hero-note">Prefer to talk? Call <?php echo esc_html($phone); ?></div>
  </div>
</section>

<?php get_template_part('inc/template-parts/wizard'); ?>

<!-- ========== CONTACT DETAILS ========== -->
<section class="trust" id="contact">
  <div class="container">
    <div class="sec-ey"><span>Or talk to us directly</span></div>
    <h2 class="sec-h2 reveal">One call is often enough <em>to find your footing.</em></h2>
    <p class="sec-lead reveal">No receptionist mazes, no pressure. Call, message or visit &mdash; and get a straight answer about your project.</p>
    <div class="trust-grid">
      <div class="trust-item reveal">
        <div class="t-ico"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
        <h3>Call Us</h3>
        <p><a class="c-link" href="tel:<?php echo esc_attr($phone_tel); ?>"><?php echo esc_html($phone); ?></a><br>Mon&ndash;Sat &middot; 9:00&ndash;18:00 GMT<br>Reply within one working day.</p>
      </div>
      <div class="trust-item reveal reveal-delay-1">
        <div class="t-ico"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
        <h3>Visit us</h3>
        <p>Sakumono, Tema &mdash; Ghana.<br>Meetings by appointment &mdash; <br>virtual visits always welcome.</p>
      </div>
      <div class="trust-item reveal reveal-delay-2">
        <div class="t-ico"><svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div>
        <h3>Send the wizard</h3>
        <p>Prefer writing? Complete the five questions above &mdash; it takes two minutes and sends everything to us in one structured message.</p>
      </div>
    </div>
    <div class="trust-note reveal"><strong>From anywhere:</strong> distance isn't a problem. A large part of our work supports the Ghanaian diaspora &mdash; you get one trusted point of contact and documented reporting, wherever you are.</div>
  </div>
</section>

<?php
get_template_part('inc/template-parts/cta-band', null, array(
    'image'   => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?auto=format&fit=crop&w=1200&q=80',
    'alt'     => 'Gabochie Design consultant ready to help',
    'eyebrow' => 'Not ready yet?',
    'title'   => 'Explore before you <em>reach out.</em>',
    'sub'     => 'Understand the journey, the services and the honest answers first &mdash; then come back when you\'re ready to move.',
    'actions' => array(
        array('class' => 'btn-gold', 'href' => gabochie_value_section_url('journey'), 'text' => 'Explore the journey'),
        array('class' => 'btn-ghost-w', 'href' => gabochie_value_section_url('resources'), 'text' => 'Read the guides'),
    ),
));

get_footer();
?>
