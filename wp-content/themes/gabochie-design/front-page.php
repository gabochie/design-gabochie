<?php
/**
 * Front Page Template
 * @package Gabochie_Design
 */
get_header();

$hero_title = get_theme_mod('hero_title', 'AI-Powered Architecture for Ghana & Diaspora');
$hero_sub = get_theme_mod('hero_subtitle', 'Where Ghanaian spatial wisdom meets cutting-edge AI. We design homes that honor your roots while embracing the future.');
$hero_img = get_theme_mod('hero_image', 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=900&q=80');
?>

<main id="main-content" tabindex="-1">

<!-- HERO -->
<section class="hero">
    <div class="hero-img-pane">
        <div class="hero-main-img" style="background-image:url('<?php echo esc_url($hero_img); ?>')"></div>
        <div class="hero-img-overlay">
            <span>Featured Project</span>
            <strong>Modern Villa, East Legon</strong>
        </div>
    </div>
    <div class="hero-left">
        <div class="hero-location">
            <span class="hero-location-dot"></span>
            Sakumono, Tema — Ghana
        </div>
        <h1 class="hero-h1"><?php echo esc_html($hero_title); ?></h1>
        <div class="hero-h1-dim">Residential Design</div>
        <div class="hero-rule"></div>
        <p class="hero-sub"><?php echo esc_html($hero_sub); ?></p>
        <div class="hero-actions">
            <a href="<?php echo esc_url(home_url('/building-cost-calculator/')); ?>" class="btn-gold">Start Your Project</a>
            <a href="#services" class="btn-ghost-w">Explore Services</a>
        </div>
        <div class="hero-stats">
            <div>
                <div class="stat-n"><?php echo esc_html(get_theme_mod('hero_stat1_num', '120+')); ?></div>
                <div class="stat-l"><?php echo esc_html(get_theme_mod('hero_stat1_label', 'Projects Completed')); ?></div>
            </div>
            <div>
                <div class="stat-n"><?php echo esc_html(get_theme_mod('hero_stat2_num', '4')); ?></div>
                <div class="stat-l"><?php echo esc_html(get_theme_mod('hero_stat2_label', 'Countries Served')); ?></div>
            </div>
            <div>
                <div class="stat-n"><?php echo esc_html(get_theme_mod('hero_stat3_num', '48h')); ?></div>
                <div class="stat-l"><?php echo esc_html(get_theme_mod('hero_stat3_label', 'First Concept')); ?></div>
            </div>
        </div>
    </div>
    <div class="hero-scroll">
        <span>Scroll</span>
        <div class="hero-scroll-line"></div>
    </div>
</section>

<!-- TICKER -->
<div class="ticker">
    <div class="ticker-inner">
        <?php
        $ticker_items = array('Architectural Design', 'Interior Design', '3D Visualization', 'Project Management', 'AI Consultation', 'Diaspora Support', 'Cost Calculator', 'Remote Building', 'Ghana Real Estate');
        $doubled = array_merge($ticker_items, $ticker_items);
        foreach ($doubled as $item) :
            echo '<span class="t-item">' . esc_html($item) . '</span>';
        endforeach;
        ?>
    </div>
</div>

<!-- SERVICES -->
<section class="services" id="services">
    <div class="container">
        <div class="svc-intro">
            <div class="reveal">
                <div class="sec-ey">What We Do</div>
                <h2 class="sec-h2">AI-Augmented <em>Architecture</em> Services</h2>
            </div>
            <p class="reveal reveal-delay-1">From concept to completion, our intelligent agents and expert architects deliver world-class designs in record time. Every service is powered by AI and refined by human expertise.</p>
        </div>
        <div class="svc-grid">
            <?php
            $services = gabochie_get_services();
            $service_icons = array(
                '<svg viewBox="0 0 24 24"><path d="M3 21h18M3 7v14M21 7v14M6 21V10M10 21V10M14 21V10M18 21V10M12 3l9 4H3l9-4z"/></svg>',
                '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
                '<svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
                '<svg viewBox="0 0 24 24"><path d="M12 20V10M18 20V4M6 20v-4"/></svg>',
                '<svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>',
                '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
            );
            $service_descs = array(
                'AI-generated floor plans optimized for Ghana\'s climate, culture, and building codes. Refined by expert architects.',
                'Smart space planning and material selection that balances modern comfort with Ghanaian aesthetics.',
                'Photorealistic renders and virtual walkthroughs delivered in 48 hours. See your home before it\'s built.',
                'Real-time progress tracking with AI oversight. Weekly reports, milestone verification, and cost control.',
                'Instant estimates using real Ghana market data. Multiple currencies, regional adjustments, detailed breakdowns.',
                'End-to-end remote project management for international clients. Currency conversion, virtual walkthroughs, escrow.',
            );
            foreach ($services as $idx => $svc) :
            ?>
            <div class="svc reveal reveal-delay-<?php echo min($idx + 1, 4); ?>">
                <div class="svc-n"><?php echo esc_html($svc['num']); ?></div>
                <div class="svc-ico"><?php echo $service_icons[$idx]; ?></div>
                <div class="svc-title"><?php echo esc_html($svc['title']); ?></div>
                <div class="svc-desc"><?php echo esc_html($service_descs[$idx]); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PROCESS -->
<section class="process" id="process">
    <div class="container">
        <div class="reveal">
            <div class="sec-ey">How We Work</div>
            <h2 class="sec-h2">From Vision to Reality in <em>5 Steps</em></h2>
        </div>
        <div class="proc-wrap">
            <div class="steps">
                <?php
                $steps = gabochie_get_process_steps();
                foreach ($steps as $idx => $step) :
                ?>
                <div class="step reveal reveal-delay-<?php echo min($idx + 1, 4); ?>">
                    <div class="step-n"><?php echo esc_html($step['num']); ?></div>
                    <div>
                        <div class="step-title"><?php echo esc_html($step['title']); ?></div>
                        <div class="step-desc"><?php echo esc_html($step['desc']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="proc-img reveal-left">
                <img src="https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=600&q=80" alt="Design Process" loading="lazy">
                <div class="proc-img-cap">
                    <span>AI + Human Expertise</span>
                    <p>Technology amplified by 10+ years of Ghanaian architectural wisdom</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DIASPORA -->
<section class="diaspora" id="diaspora">
    <div class="container">
        <div class="reveal">
            <div class="sec-ey">For The Diaspora</div>
            <h2 class="sec-h2">Build Back Home <em>From Anywhere</em></h2>
        </div>
        <div class="dia-grid">
            <div class="dia-img-stack reveal-left">
                <div class="dia-img"><img src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=600&q=80" alt="Ghana Home" loading="lazy"></div>
                <div class="dia-img"><img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=600&q=80" alt="Modern Design" loading="lazy"></div>
            </div>
            <div class="dia-text">
                <p class="reveal">Whether you're in London, New York, or Toronto — we make building in Ghana seamless. Our AI-powered diaspora management system handles everything from currency conversion to virtual site visits.</p>
                <div class="dia-feats">
                    <?php
                    $dia_feats = array(
                        array('title' => 'Multi-Currency Pricing', 'desc' => 'USD, GBP, EUR, GHS — transparent costs in your currency'),
                        array('title' => 'Virtual Walkthroughs', 'desc' => '3D tours of your project in real-time from anywhere'),
                        array('title' => 'AI Progress Reports', 'desc' => 'Weekly AI-generated updates with photos and milestones'),
                        array('title' => 'Secure Escrow', 'desc' => 'Milestone-based payments protected until work is verified'),
                    );
                    foreach ($dia_feats as $idx => $feat) :
                    ?>
                    <div class="dia-f reveal reveal-delay-<?php echo min($idx + 1, 4); ?>">
                        <div class="dia-f-ico"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
                        <div>
                            <div class="dia-f-title"><?php echo esc_html($feat['title']); ?></div>
                            <div class="dia-f-desc"><?php echo esc_html($feat['desc']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- AI FEATURES -->
<section class="ai-sec" id="ai">
    <div class="container">
        <div class="reveal">
            <div class="sec-ey">AI Technology</div>
            <h2 class="sec-h2">Intelligent Agents <em>Powering</em> Your Build</h2>
        </div>
        <div class="ai-layout">
            <div class="ai-left">
                <?php
                $ai_features = array(
                    array('title' => 'Architect AI', 'desc' => 'Generates floor plans optimized for Ghana\'s climate, building codes, and cultural preferences.'),
                    array('title' => 'Cost Calculator AI', 'desc' => 'Real-time cost estimates using live Ghana market data across multiple currencies.'),
                    array('title' => 'Diaspora Manager AI', 'desc' => 'Handles international communication, timelines, and currency conversion for remote clients.'),
                    array('title' => 'Visualization AI', 'desc' => 'Creates photorealistic 3D renders and virtual walkthroughs in 48 hours.'),
                    array('title' => 'Project Tracker AI', 'desc' => 'Monitors construction progress, flags issues, and generates weekly reports.'),
                );
                foreach ($ai_features as $idx => $ai) :
                ?>
                <div class="ai-card reveal reveal-delay-<?php echo min($idx + 1, 4); ?>">
                    <div class="ai-card-ico"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
                    <div>
                        <div class="ai-card-title"><?php echo esc_html($ai['title']); ?></div>
                        <div class="ai-card-desc"><?php echo esc_html($ai['desc']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="ai-right reveal-right">
                <h3>Where <em>Intelligence</em> Meets Architecture</h3>
                <p>Our AI doesn't replace human architects — it supercharges them. Every design is generated by AI for speed and accuracy, then refined by our expert team for artistry and cultural sensitivity.</p>
                <p>The result? Designs that would take months traditionally, delivered in days — with the quality and attention to detail that only comes from combining technology with human expertise.</p>
                <a href="<?php echo esc_url(home_url('/about/')); ?>" class="btn-gold">Meet Our AI Team</a>
            </div>
        </div>
    </div>
</section>

<!-- BLOG -->
<section class="blog-sec" id="blog">
    <div class="container">
        <div class="blog-head">
            <div class="reveal">
                <div class="sec-ey">Insights</div>
                <h2 class="sec-h2">From the <em>Blog</em></h2>
            </div>
            <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="blog-all">View All Articles</a>
        </div>
        <div class="blog-grid">
            <?php
            $blog_posts = new WP_Query(array('posts_per_page' => 3, 'post_status' => 'publish'));
            if ($blog_posts->have_posts()) :
                $blog_idx = 0;
                while ($blog_posts->have_posts()) : $blog_posts->the_post();
                    $cat = get_the_category();
                    $cat_name = !empty($cat) ? $cat[0]->name : 'Tips';
                    $blog_idx++;
            ?>
            <a href="<?php the_permalink(); ?>" class="blog-card reveal reveal-delay-<?php echo min($blog_idx, 3); ?>" aria-label="<?php the_title_attribute(); ?>">
                <div class="blog-img">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php echo get_the_post_thumbnail(get_the_ID(), 'gabochie-card', array('alt' => get_the_title())); ?>
                    <?php else : ?>
                        <img src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&q=80" alt="<?php the_title_attribute(); ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="blog-img-overlay"></div>
                    <span class="blog-cat-pill"><?php echo esc_html($cat_name); ?></span>
                </div>
                <div class="blog-body">
                    <div class="blog-title"><?php the_title(); ?></div>
                    <div class="blog-meta"><?php echo get_the_date('M d, Y'); ?> <span>·</span> <?php echo esc_html(get_post_meta(get_the_ID(), '_gabochie_read_time', true) ?: '8'); ?> min read</div>
                </div>
            </a>
            <?php
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pricing" id="pricing">
    <div class="container">
        <div class="section-header reveal">
            <div class="sec-ey">Pricing</div>
            <h2 class="sec-h2">Transparent <em>Packages</em></h2>
        </div>
        <div class="currency-toggle-wrap">
            <div class="currency-toggle" id="pricing-currency-toggle">
                <button class="currency-btn active" data-currency="USD">USD $</button>
                <button class="currency-btn" data-currency="GHS">GHS GH₵</button>
            </div>
            <div id="live-rate-indicator" class="live-rate"></div>
        </div>
        <div class="price-grid">
            <?php
            $ghs_rate = 15.8;
            $plans = gabochie_get_pricing_plans();
            foreach ($plans as $pidx => $plan) :
                $ghs = round($plan['usd'] * $ghs_rate);
            ?>
            <div class="price-card reveal reveal-delay-<?php echo min($pidx + 1, 3); ?> <?php echo $plan['featured'] ? 'featured' : ''; ?>" data-usd="<?php echo $plan['usd']; ?>" data-ghs="<?php echo $ghs; ?>">
                <?php if ($plan['badge']) : ?><span class="price-badge"><?php echo esc_html($plan['badge']); ?></span><?php endif; ?>
                <div class="price-tier-label"><?php echo esc_html($plan['tier']); ?></div>
                <div class="price-amt"><span class="price-cur" data-usd="$" data-ghs="GH₵">$</span><span class="price-num"><?php echo number_format($plan['usd']); ?></span></div>
                <div class="price-per"><?php echo esc_html($plan['per']); ?></div>
                <div class="price-div"></div>
                <ul class="price-ul">
                    <?php foreach ($plan['features'] as $f) : ?>
                        <li><?php echo esc_html($f); ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="price-btn">Get Started</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA BAND -->
<div class="cta-band reveal-scale">
    <div class="cta-img">
        <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=900&q=80" alt="Build in Ghana" loading="lazy">
    </div>
    <div class="cta-content">
        <h2>Ready to Build Your <em>Dream Home</em> in Ghana?</h2>
        <p class="cta-sub">Book a free 30-minute consultation. No commitment, any time zone.</p>
        <a href="mailto:<?php echo esc_attr(get_theme_mod('company_email', 'admin@gabochiedesign.com')); ?>?subject=Book%20Consultation" class="btn-ivory">Book Free Consultation</a>
    </div>
</div>

</main>

<?php get_footer(); ?>
