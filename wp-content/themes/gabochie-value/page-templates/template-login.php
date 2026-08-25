<?php
/**
 * Template Name: Client Login
 * @package Gabochie_Value
 */

if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard/'));
    exit;
}

get_header();
$show_register = isset($_GET['action']) && $_GET['action'] === 'register';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / <?php echo $show_register ? 'Create Account' : 'Client Login'; ?></div>
        <h1>Client <em>Portal</em></h1>
        <p>Access your project dashboard, construction tools, and real-time updates.</p>
    </div>
</section>

<section style="padding:4rem 2rem 6rem">
    <div style="max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:start">

        <!-- Left: Login / Register Forms -->
        <div>
            <div style="background:var(--cream);padding:2.5rem;border-radius:var(--r-lg);border:1px solid var(--border-light)">

                <?php if ($show_register) : ?>

                <h3 style="font-size:1.5rem;margin-bottom:0.5rem">Create Free Account</h3>
                <p style="font-size:0.9rem;color:var(--mist);margin-bottom:2rem">Get access to construction tools, cost calculators, and project dashboard — no card needed.</p>
                <?php echo do_shortcode('[gabochie_register_form]'); ?>
                <p style="text-align:center;margin-top:1rem"><a href="<?php echo esc_url(remove_query_arg('action')); ?>" style="color:var(--teal);text-decoration:underline;font-size:0.85rem">Already have an account? Log in</a></p>

                <?php else : ?>

                <h3 style="font-size:1.5rem;margin-bottom:0.5rem">Welcome Back</h3>
                <p style="font-size:0.9rem;color:var(--mist);margin-bottom:2rem">Sign in to your account.</p>

                <?php if (isset($_GET['login']) && $_GET['login'] === 'failed') : ?>
                    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:0.75rem 1rem;border-radius:var(--r-sm);margin-bottom:1.5rem;font-size:0.85rem">Invalid email or password. Please try again.</div>
                <?php endif; ?>

                <?php echo do_shortcode('[pmpro_login]'); ?>

                <p style="text-align:center;margin-top:1rem"><a href="<?php echo esc_url(add_query_arg('action', 'register')); ?>" style="color:var(--teal);text-decoration:underline;font-size:0.85rem">Don't have an account? Create one free</a></p>

                <?php endif; ?>
            </div>

            <!-- Trust Signals -->
            <div style="display:flex;gap:2rem;margin-top:2rem;padding:0 0.5rem">
                <div style="flex:1;text-align:center">
                    <div style="font-size:1.5rem;font-weight:700;color:var(--teal);margin-bottom:0.15rem">50+</div>
                    <div style="font-size:0.75rem;color:var(--mist);letter-spacing:0.05em">Projects Completed</div>
                </div>
                <div style="flex:1;text-align:center">
                    <div style="font-size:1.5rem;font-weight:700;color:var(--teal);margin-bottom:0.15rem">98%</div>
                    <div style="font-size:0.75rem;color:var(--mist);letter-spacing:0.05em">Client Satisfaction</div>
                </div>
                <div style="flex:1;text-align:center">
                    <div style="font-size:1.5rem;font-weight:700;color:var(--teal);margin-bottom:0.15rem">24h</div>
                    <div style="font-size:0.75rem;color:var(--mist);letter-spacing:0.05em">Response Time</div>
                </div>
            </div>
        </div>

        <!-- Right: Conversion Panel -->
        <div>
            <div style="background:var(--ivory);padding:2.5rem;border-radius:var(--r-lg);border:1px solid var(--border-light)">
                <h3 style="font-size:1.5rem;margin-bottom:0.5rem">Start Your Project</h3>
                <p style="font-size:0.9rem;color:var(--mist);margin-bottom:1.5rem">Tell us about your vision and we'll create your private project portal — free consultation.</p>

                <form action="<?php echo esc_url(home_url('/contact/')); ?>" method="GET" style="margin-bottom:2rem">
                    <div style="margin-bottom:1rem">
                        <label style="display:block;font-size:0.75rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--charcoal);margin-bottom:0.4rem;font-weight:500">Full Name</label>
                        <input type="text" name="name" placeholder="Your name" style="width:100%;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:var(--r-sm);font-family:'Jost',sans-serif;font-size:0.95rem;background:var(--ivory)">
                    </div>
                    <div style="margin-bottom:1rem">
                        <label style="display:block;font-size:0.75rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--charcoal);margin-bottom:0.4rem;font-weight:500">Email Address</label>
                        <input type="email" name="email" placeholder="you@example.com" required style="width:100%;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:var(--r-sm);font-family:'Jost',sans-serif;font-size:0.95rem;background:var(--ivory)">
                    </div>
                    <div style="margin-bottom:1.5rem">
                        <label style="display:block;font-size:0.75rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--charcoal);margin-bottom:0.4rem;font-weight:500">Project Type</label>
                        <select style="width:100%;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:var(--r-sm);font-family:'Jost',sans-serif;font-size:0.95rem;background:var(--ivory);color:var(--charcoal)">
                            <option value="">Select a service...</option>
                            <option value="building">Building Design</option>
                            <option value="interior">Interior Design</option>
                            <option value="planning">Space Planning</option>
                            <option value="renovation">Renovation & Remodeling</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <button type="submit" style="width:100%;padding:0.9rem 2rem;background:var(--gold);color:var(--teal-dark);border:none;border-radius:var(--r-sm);font-family:'Jost',sans-serif;font-size:0.85rem;font-weight:600;cursor:pointer;transition:opacity 0.2s">Get Your Free Consultation</button>
                </form>

                <div style="border-top:1px solid var(--border-light);padding-top:1.5rem">
                    <h4 style="font-size:0.9rem;margin-bottom:1rem">What Our Clients Say</h4>
                    <div style="font-size:0.85rem;color:var(--mist);line-height:1.7;font-style:italic;margin-bottom:0.75rem">"The team transformed our vision into something beyond what we imagined. The portal made it so easy to track progress."</div>
                    <div style="display:flex;align-items:center;gap:0.75rem">
                        <div style="width:36px;height:36px;background:var(--teal);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--ivory);font-size:0.85rem;font-weight:500">AK</div>
                        <div><div style="font-size:0.8rem;font-weight:500">Amara K.</div><div style="font-size:0.7rem;color:var(--mist)">Lagos, Nigeria</div></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php get_footer(); ?>
