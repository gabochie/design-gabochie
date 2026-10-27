<?php
/**
 * Template Name: Services Page
 * @package Gabochie_Design
 */
get_header(); ?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Services</div>
        <h1>Our <em>Services</em></h1>
        <p>AI-augmented architecture services from concept to completion.</p>
    </div>
</section>

<section style="padding:6rem 2rem;background:var(--cream)">
    <div class="container">
        <div class="svc-grid">
            <?php
            $services = array(
                array('num' => '01', 'title' => 'Architectural Design', 'desc' => 'AI-generated floor plans optimized for Ghana\'s climate, culture, and building codes. Every design is reviewed and refined by our expert architects.'),
                array('num' => '02', 'title' => 'Interior Design', 'desc' => 'Smart space planning and material selection that balances modern comfort with Ghanaian aesthetics.'),
                array('num' => '03', 'title' => '3D Visualization', 'desc' => 'Photorealistic renders and virtual walkthroughs delivered in 48 hours. See your home before it\'s built.'),
                array('num' => '04', 'title' => 'Project Management', 'desc' => 'Real-time progress tracking with AI oversight. Weekly reports, milestone verification, and cost control.'),
                array('num' => '05', 'title' => 'AI Cost Calculator', 'desc' => 'Instant estimates using real Ghana market data. Multiple currencies, regional adjustments, detailed breakdowns.'),
                array('num' => '06', 'title' => 'Diaspora Support', 'desc' => 'End-to-end remote project management for international clients. Currency conversion, virtual walkthroughs, escrow.'),
            );
            foreach ($services as $svc) :
            ?>
            <div class="svc">
                <div class="svc-n"><?php echo esc_html($svc['num']); ?></div>
                <div class="svc-title"><?php echo esc_html($svc['title']); ?></div>
                <div class="svc-desc"><?php echo esc_html($svc['desc']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Ready to start your <em>project</em>?</h2>
        <p>Book a free 30-minute consultation. No commitment, any time zone.</p>
        <a href="mailto:<?php echo esc_attr(get_theme_mod('company_email', 'admin@design.gabochie.com')); ?>?subject=Book%20Consultation" class="btn-gold">Book Free Consultation</a>
    </div>
</section>

<?php get_footer(); ?>
