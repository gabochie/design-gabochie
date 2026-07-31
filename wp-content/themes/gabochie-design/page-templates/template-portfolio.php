<?php
/**
 * Template Name: Portfolio Page
 * @package Gabochie_Design
 */
get_header(); ?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Portfolio</div>
        <h1>Our <em>Portfolio</em></h1>
        <p>120+ projects completed across Ghana and the diaspora. AI-powered designs, human-refined excellence.</p>
    </div>
</section>

<section style="padding:6rem 2rem;background:var(--cream)">
    <div class="container">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px">
            <?php
            $projects = array(
                array('title' => 'RiverSide Residence', 'cat' => 'Residential', 'img' => 'https://mir-s3-cdn-cf.behance.net/projects/404/406416174338785.Y3JvcCwxMzgwLDEwODAsMjcwLDA.png', 'desc' => 'Luxury riverside living with modern Ghanaian aesthetics', 'url' => 'https://www.behance.net/gallery/174338785/RiverSide-Residence'),
                array('title' => 'Lamptey Apartments', 'cat' => 'Multi-Unit', 'img' => 'https://mir-s3-cdn-cf.behance.net/projects/404/79e8fc172466769.Y3JvcCwxMzgwLDEwODAsMjcwLDA.png', 'desc' => 'Contemporary apartment complex in Accra', 'url' => 'https://www.behance.net/gallery/172466769/Lamptey-Apartments'),
                array('title' => '4 Bedroom Residence', 'cat' => 'Residential', 'img' => 'https://mir-s3-cdn-cf.behance.net/projects/404/3b2d3e172395909.Y3JvcCwxMzgwLDEwODAsMjcwLDA.png', 'desc' => 'Spacious family home with open-plan living', 'url' => 'https://www.behance.net/gallery/172395909/4-Bedroom-Residence'),
                array('title' => '5 Bedroom Modern Residential', 'cat' => 'Residential', 'img' => 'https://mir-s3-cdn-cf.behance.net/projects/404/0a0a36167342533.Y3JvcCwxMzgwLDEwODAsMTM2LDA.png', 'desc' => 'Luxury modern home with smart design', 'url' => 'https://www.behance.net/gallery/167342533/5-Bedroom-Modern-Residential'),
                array('title' => 'EL-Gibbor Courts 2.0', 'cat' => 'Multi-Unit', 'img' => 'https://mir-s3-cdn-cf.behance.net/projects/404/e43b66146111057.Y3JvcCw5NzksNzY2LDg3LDA.png', 'desc' => 'Premium residential court development', 'url' => 'https://www.behance.net/gallery/146111057/EL-Gibbor-Courts-20'),
                array('title' => 'El-Gibbor Court, Dzorwulu', 'cat' => 'Multi-Unit', 'img' => 'https://mir-s3-cdn-cf.behance.net/projects/404/19d18e144906183.Y3JvcCwxMzgwLDEwODAsMjcwLDA.png', 'desc' => 'Elegant court-style residential living in Accra', 'url' => 'https://www.behance.net/gallery/144906183/El-Gibbor-Court-Dzorwulu-Accra-Ghana'),
                array('title' => 'Fuel Station 3D Project', 'cat' => 'Commercial', 'img' => 'https://mir-s3-cdn-cf.behance.net/projects/404/df0d0d141919197.Y3JvcCwxMzgwLDEwODAsMzMxLDA.png', 'desc' => 'Modern fuel station design with 3D visualization', 'url' => 'https://www.behance.net/gallery/141919197/Fuel-Station-Gas-Station-3D-Project'),
                array('title' => 'Mountain Residence', 'cat' => 'Residential', 'img' => 'https://mir-s3-cdn-cf.behance.net/projects/404/59b189133313883.Y3JvcCw5MjAsNzIwLDE4MCww.jpg', 'desc' => 'Hillside luxury home with panoramic views', 'url' => 'https://www.behance.net/gallery/133313883/Mountain-Residence'),
                array('title' => 'Bedroom Interior Design', 'cat' => 'Interior', 'img' => 'https://mir-s3-cdn-cf.behance.net/projects/404/0eb329144240691.Y3JvcCwxMzgwLDEwODAsMjcwLDA.png', 'desc' => 'Contemporary bedroom with bespoke furnishings', 'url' => 'https://www.behance.net/gallery/144240691/Bedroom-Interior-Design'),
            );
            foreach ($projects as $proj) :
            ?>
            <a href="<?php echo esc_url($proj['url']); ?>" target="_blank" rel="noopener noreferrer" class="blog-card" style="text-decoration:none;color:inherit">
                <div class="blog-img">
                    <img src="<?php echo esc_url($proj['img']); ?>" alt="<?php echo esc_attr($proj['title']); ?>">
                    <div class="blog-img-overlay"></div>
                    <span class="blog-cat-pill"><?php echo esc_html($proj['cat']); ?></span>
                </div>
                <div class="blog-body">
                    <div class="blog-title"><?php echo esc_html($proj['title']); ?> <span style="font-size:0.7em;opacity:0.6">&#8599;</span></div>
                    <div class="blog-meta"><?php echo esc_html($proj['desc']); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Want a project like <em>this</em>?</h2>
        <p>Book a free consultation to discuss your vision.</p>
        <a href="mailto:<?php echo esc_attr(get_theme_mod('company_email', 'admin@gabochiedesign.com')); ?>?subject=Book%20Consultation" class="btn-gold">Book Free Consultation</a>
    </div>
</section>

<?php get_footer(); ?>
