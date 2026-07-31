<?php
/**
 * 404 Error Page Template
 * @package Gabochie_Design
 */
get_header();
?>

<div class="page-hero" style="min-height:60vh;display:flex;align-items:center;justify-content:center;text-align:center;background:var(--cream);">
    <div style="max-width:680px;padding:2rem;">
        <div style="font-size:120px;font-weight:700;color:var(--teal);line-height:1;margin-bottom:0.5rem;font-family:'Playfair Display',serif;">404</div>
        <h1 style="font-size:2rem;margin-bottom:1rem;color:var(--charcoal);font-family:'Playfair Display',serif;">Page Not Found</h1>
        <p style="font-size:1.1rem;color:#555;margin-bottom:2rem;line-height:1.6;">The page you're looking for doesn't exist or has been moved. Let's get you back on track.</p>
        
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" style="display:flex;gap:0;max-width:480px;margin:0 auto 2rem;">
            <input type="search" name="s" placeholder="Search our site..." aria-label="Search" style="flex:1;padding:14px 18px;border:2px solid var(--teal);border-right:none;border-radius:8px 0 0 8px;font-size:1rem;font-family:'Jost',sans-serif;">
            <button type="submit" style="padding:14px 24px;background:var(--teal);color:#fff;border:2px solid var(--teal);border-radius:0 8px 8px 0;font-weight:600;cursor:pointer;font-family:'Jost',sans-serif;">Search</button>
        </form>

        <div style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:center;margin-bottom:2.5rem;">
            <a href="<?php echo esc_url(home_url('/')); ?>" style="display:inline-flex;align-items:center;gap:0.5rem;padding:12px 24px;background:var(--teal);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-family:'Jost',sans-serif;transition:transform 0.2s;">
                &#127968; Back to Home
            </a>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" style="display:inline-flex;align-items:center;gap:0.5rem;padding:12px 24px;background:var(--gold);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-family:'Jost',sans-serif;transition:transform 0.2s;">
                &#128222; Contact Us
            </a>
            <a href="<?php echo esc_url(home_url('/blog/')); ?>" style="display:inline-flex;align-items:center;gap:0.5rem;padding:12px 24px;background:#fff;color:var(--teal);border:2px solid var(--teal);border-radius:8px;text-decoration:none;font-weight:600;font-family:'Jost',sans-serif;transition:transform 0.2s;">
                &#128196; Read Our Blog
            </a>
        </div>

        <div style="border-top:1px solid #ddd;padding-top:1.5rem;">
            <p style="font-weight:600;margin-bottom:0.75rem;color:var(--charcoal);">Popular Pages</p>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;justify-content:center;">
                <a href="<?php echo esc_url(home_url('/about/')); ?>" style="color:var(--teal);text-decoration:none;font-size:0.9rem;">About</a>
                <span style="color:#ccc;">|</span>
                <a href="<?php echo esc_url(home_url('/services/')); ?>" style="color:var(--teal);text-decoration:none;font-size:0.9rem;">Services</a>
                <span style="color:#ccc;">|</span>
                <a href="<?php echo esc_url(home_url('/portfolio/')); ?>" style="color:var(--teal);text-decoration:none;font-size:0.9rem;">Portfolio</a>
                <span style="color:#ccc;">|</span>
                <a href="<?php echo esc_url(home_url('/pricing/')); ?>" style="color:var(--teal);text-decoration:none;font-size:0.9rem;">Pricing</a>
                <span style="color:#ccc;">|</span>
                <a href="<?php echo esc_url(home_url('/faq/')); ?>" style="color:var(--teal);text-decoration:none;font-size:0.9rem;">FAQ</a>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>