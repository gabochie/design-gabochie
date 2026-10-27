<?php
/**
 * Template Name: About Page
 * @package Gabochie_Design
 */
get_header(); ?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / About</div>
        <h1>About <em>Us</em></h1>
        <p>Bridging Ghanaian culture with world-class design. We're on a mission to transform how the diaspora builds back home.</p>
    </div>
</section>

<section style="padding:6rem 2rem;background:var(--cream)">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center">
            <div>
                <h2 style="font-family:'Playfair Display',serif;font-size:clamp(2rem,3vw,2.5rem);margin-bottom:1.5rem">AI-Powered Architecture for the Diaspora</h2>
                <p style="font-size:1.05rem;color:var(--mist);margin-bottom:1.25rem">Gabochie Design combines Ghanaian spatial wisdom with cutting-edge AI to deliver world-class designs in record time.</p>
                <p style="font-size:1.05rem;color:var(--mist);margin-bottom:1.25rem">Our team of AI Agents and human architects work together to transform your vision into actionable plans — whether you're building in Accra or managing remotely from abroad.</p>
            </div>
            <div style="border-radius:var(--r);overflow:hidden;aspect-ratio:4/3">
                <img src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=600&fit=crop" alt="Gabochie Design Studio" style="width:100%;height:100%;object-fit:cover">
            </div>
        </div>
    </div>
</section>

<section style="padding:6rem 2rem;background:var(--ivory)">
    <div class="container">
        <div style="text-align:center;max-width:600px;margin:0 auto 3rem">
            <h2 style="font-size:clamp(2rem,3vw,2.5rem);margin-bottom:1rem">Our AI Team</h2>
            <p style="color:var(--mist)">Meet the intelligent agents that power your build</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:2rem">
            <?php
            $agents = array(
                array('emoji' => "\xF0\x9F\x8F\xE0", 'title' => 'Architect AI', 'desc' => 'Generates floor plans and 3D concepts based on your requirements and Ghanaian building norms.'),
                array('emoji' => "\xF0\x9F\x93\x90", 'title' => 'Cost Calculator AI', 'desc' => 'Provides instant cost estimates using real Ghana market data and regional adjustments.'),
                array('emoji' => "\xF0\x9F\x8C\x8D", 'title' => 'Diaspora Manager AI', 'desc' => 'Handles communication, timelines, and currency conversion for international clients.'),
            );
            foreach ($agents as $agent) :
            ?>
            <div style="background:var(--cream);padding:2rem;border-radius:var(--r);border:1px solid var(--border-light);text-align:center;transition:all 0.3s">
                <div style="width:64px;height:64px;background:var(--teal-light);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:1.75rem"><?php echo $agent['emoji']; ?></div>
                <h3 style="font-size:1.25rem;margin-bottom:0.75rem"><?php echo esc_html($agent['title']); ?></h3>
                <p style="font-size:0.95rem;color:var(--mist);line-height:1.7"><?php echo esc_html($agent['desc']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section style="padding:4rem 2rem;background:var(--teal)">
    <div class="container">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:2rem;text-align:center">
            <div><div style="font-family:'Playfair Display',serif;font-size:clamp(2rem,4vw,3rem);color:#FAF7F2;margin-bottom:0.25rem">120+</div><div style="font-size:0.75rem;letter-spacing:0.15em;text-transform:uppercase;color:var(--gold)">Projects Completed</div></div>
            <div><div style="font-family:'Playfair Display',serif;font-size:clamp(2rem,4vw,3rem);color:#FAF7F2;margin-bottom:0.25rem">4</div><div style="font-size:0.75rem;letter-spacing:0.15em;text-transform:uppercase;color:var(--gold)">Countries</div></div>
            <div><div style="font-family:'Playfair Display',serif;font-size:clamp(2rem,4vw,3rem);color:#FAF7F2;margin-bottom:0.25rem">48h</div><div style="font-size:0.75rem;letter-spacing:0.15em;text-transform:uppercase;color:var(--gold)">First Concept</div></div>
            <div><div style="font-family:'Playfair Display',serif;font-size:clamp(2rem,4vw,3rem);color:#FAF7F2;margin-bottom:0.25rem">10+</div><div style="font-size:0.75rem;letter-spacing:0.15em;text-transform:uppercase;color:var(--gold)">Years Experience</div></div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Ready to build something <em>beautiful</em> in Ghana?</h2>
        <p>No commitment &middot; 30 minutes &middot; Any time zone</p>
        <a href="mailto:<?php echo esc_attr(get_theme_mod('company_email', 'admin@design.gabochie.com')); ?>?subject=Book%20Consultation" class="btn-gold">Book Free Consultation</a>
    </div>
</section>

<?php get_footer(); ?>
