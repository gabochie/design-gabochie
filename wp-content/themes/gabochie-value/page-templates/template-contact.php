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
$whatsapp  = gabochie_value_whatsapp();
$wa_base   = 'https://wa.me/' . $whatsapp . '?text=';
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
      <a class="whatsapp-btn" href="<?php echo esc_url($wa_base . 'Hello%20Gabochie%20Design%20%E2%80%94%20from%20your%20website.'); ?>" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="#06350F"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
        WhatsApp Us
      </a>
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
        <h3>Call or WhatsApp</h3>
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
