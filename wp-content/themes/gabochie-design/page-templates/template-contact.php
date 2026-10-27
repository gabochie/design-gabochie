<?php
/**
 * Template Name: Contact Page
 * @package Gabochie_Design
 */
get_header();
$phone = get_theme_mod('company_phone', '+233 26 824 8970');
$email = get_theme_mod('company_email', 'admin@design.gabochie.com');
$address = get_theme_mod('company_address', "P.O.BOX DM SK 2125\nSakumono, Tema, Ghana");
$whatsapp = get_theme_mod('whatsapp_number', '233268248970');
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Contact</div>
        <h1>Get In <em>Touch</em></h1>
        <p>Ready to start your build? Reach out and let's turn your vision into reality.</p>
    </div>
</section>

<section style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;padding:6rem 2rem;align-items:start">
    <div class="container" style="display:contents">
        <div>
            <h2 style="font-size:clamp(2rem,3vw,2.5rem);margin-bottom:1.5rem">Let's Build Together</h2>
            <p style="color:var(--mist);margin-bottom:2.5rem;line-height:1.8">Whether you're planning a new home, renovating, or just exploring options — our team is ready to help. No commitment, just a conversation.</p>

            <div style="display:flex;gap:1rem;margin-bottom:1.75rem">
                <div style="width:48px;height:48px;background:var(--teal-light);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0">📍</div>
                <div><h4 style="font-size:0.9rem;margin-bottom:0.3rem">Office</h4><p style="font-size:0.9rem;color:var(--mist);line-height:1.6;white-space:pre-line"><?php echo esc_html($address); ?></p></div>
            </div>
            <div style="display:flex;gap:1rem;margin-bottom:1.75rem">
                <div style="width:48px;height:48px;background:var(--teal-light);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0">✉️</div>
                <div><h4 style="font-size:0.9rem;margin-bottom:0.3rem">Email</h4><p style="font-size:0.9rem;color:var(--mist)"><a href="mailto:<?php echo esc_attr($email); ?>" style="color:var(--teal)"><?php echo esc_html($email); ?></a></p></div>
            </div>
            <div style="display:flex;gap:1rem;margin-bottom:1.75rem">
                <div style="width:48px;height:48px;background:var(--teal-light);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0">📞</div>
                <div><h4 style="font-size:0.9rem;margin-bottom:0.3rem">Phone / WhatsApp</h4><p style="font-size:0.9rem;color:var(--mist)"><a href="https://wa.me/<?php echo esc_attr($whatsapp); ?>" style="color:var(--teal)"><?php echo esc_html($phone); ?></a></p></div>
            </div>
            <div style="display:flex;gap:1rem">
                <div style="width:48px;height:48px;background:var(--teal-light);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0">🕐</div>
                <div><h4 style="font-size:0.9rem;margin-bottom:0.3rem">Office Hours</h4><p style="font-size:0.9rem;color:var(--mist);line-height:1.6">Monday - Friday: 9:00 AM - 6:00 PM (GMT)<br>Saturday: 10:00 AM - 2:00 PM (GMT)<br>Sunday: Closed</p></div>
            </div>
        </div>

        <div style="background:var(--cream);padding:2.5rem;border-radius:var(--r-lg);border:1px solid var(--border-light)">
            <h3 style="font-size:1.5rem;margin-bottom:0.75rem">Send Us a Message</h3>
            <p style="font-size:0.9rem;color:var(--mist);margin-bottom:2rem">Fill in the form and we'll get back to you within 48 hours.</p>
            <form action="mailto:<?php echo esc_attr($email); ?>" method="POST" enctype="text/plain">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem">
                    <div><label style="display:block;font-size:0.75rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--charcoal);margin-bottom:0.4rem;font-weight:500">Full Name</label><input type="text" name="name" placeholder="Your name" required style="width:100%;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:var(--r-sm);font-family:'Jost',sans-serif;font-size:0.95rem;background:var(--ivory)"></div>
                    <div><label style="display:block;font-size:0.75rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--charcoal);margin-bottom:0.4rem;font-weight:500">Email Address</label><input type="email" name="email" placeholder="you@example.com" required style="width:100%;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:var(--r-sm);font-family:'Jost',sans-serif;font-size:0.95rem;background:var(--ivory)"></div>
                </div>
                <div style="margin-bottom:1.25rem">
                    <label style="display:block;font-size:0.75rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--charcoal);margin-bottom:0.4rem;font-weight:500">Service Interested In</label>
                    <select name="service" style="width:100%;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:var(--r-sm);font-family:'Jost',sans-serif;font-size:0.95rem;background:var(--ivory)">
                        <option value="">Select a service...</option>
                        <option value="Architectural Design">Architectural Design</option>
                        <option value="Interior Design">Interior Design</option>
                        <option value="3D Renders">3D Renders</option>
                        <option value="Project Management">Project Management</option>
                        <option value="AI Consultation">AI Consultation</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div style="margin-bottom:1.25rem">
                    <label style="display:block;font-size:0.75rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--charcoal);margin-bottom:0.4rem;font-weight:500">Message</label>
                    <textarea name="message" placeholder="Tell us about your project..." required style="width:100%;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:var(--r-sm);font-family:'Jost',sans-serif;font-size:0.95rem;background:var(--ivory);min-height:120px;resize:vertical"></textarea>
                </div>
                <button type="submit" class="btn-gold">Send Message</button>
            </form>
        </div>
    </div>
</section>

<?php get_footer(); ?>
