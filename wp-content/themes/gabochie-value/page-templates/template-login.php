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

<section class="mem-section">
    <div class="mem-login-wrap">

        <!-- Left: Login / Register Forms -->
        <div>
            <div class="mem-login-card">

                <?php if ($show_register) : ?>

                <h3>Create Free Account</h3>
                <p class="mem-subtitle">Get access to construction tools, cost calculators, and project dashboard — no card needed.</p>
                <?php echo do_shortcode('[gabochie_register_form]'); ?>
                <p class="mem-login-link"><a href="<?php echo esc_url(remove_query_arg('action')); ?>">Already have an account? Log in</a></p>

                <?php else : ?>

                <h3>Welcome Back</h3>
                <p class="mem-subtitle">Sign in to your account.</p>

                <?php if (isset($_GET['login']) && $_GET['login'] === 'failed') : ?>
                    <div class="mem-alert mem-alert-error">Invalid email or password. Please try again.</div>
                <?php endif; ?>

                <?php if (isset($_GET['account']) && $_GET['account'] === 'exists') : ?>
                    <div class="mem-alert mem-alert-success">An account with this email already exists. Please sign in below.</div>
                <?php endif; ?>

                <?php echo do_shortcode('[pmpro_login]'); ?>

                <p class="mem-login-link"><a href="<?php echo esc_url(add_query_arg('action', 'register')); ?>">Don't have an account? Create one free</a></p>

                <?php endif; ?>
            </div>

            <!-- Trust Signals -->
            <div class="mem-trust-row">
                <div class="mem-trust-stat">
                    <div class="mem-trust-stat-n">50+</div>
                    <div class="mem-trust-stat-l">Projects Completed</div>
                </div>
                <div class="mem-trust-stat">
                    <div class="mem-trust-stat-n">98%</div>
                    <div class="mem-trust-stat-l">Client Satisfaction</div>
                </div>
                <div class="mem-trust-stat">
                    <div class="mem-trust-stat-n">24h</div>
                    <div class="mem-trust-stat-l">Response Time</div>
                </div>
            </div>
        </div>

        <!-- Right: Conversion Panel -->
        <div>
            <div class="mem-convert-card">
                <h3>Start Your Project</h3>
                <p class="mem-subtitle">Tell us about your vision and we'll create your private project portal — free consultation.</p>

                <form action="<?php echo esc_url(home_url('/contact/')); ?>" method="GET">
                    <div class="mem-form-group">
                        <label class="mem-form-label" for="conv-name">Full Name</label>
                        <input type="text" id="conv-name" name="name" placeholder="Your name" class="mem-form-input">
                    </div>
                    <div class="mem-form-group">
                        <label class="mem-form-label" for="conv-email">Email Address</label>
                        <input type="email" id="conv-email" name="email" placeholder="you@example.com" required class="mem-form-input">
                    </div>
                    <div class="mem-form-group">
                        <label class="mem-form-label" for="conv-type">Project Type</label>
                        <select id="conv-type" name="type" class="mem-form-input">
                            <option value="">Select a service...</option>
                            <option value="building">Building Design</option>
                            <option value="interior">Interior Design</option>
                            <option value="planning">Space Planning</option>
                            <option value="renovation">Renovation & Remodeling</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <button type="submit" class="mem-convert-btn">Get Your Free Consultation</button>
                </form>

                <div class="mem-convert-quote">
                    <h4>What Our Clients Say</h4>
                    <blockquote>"The team transformed our vision into something beyond what we imagined. The portal made it so easy to track progress."</blockquote>
                    <div class="mem-convert-author">
                        <div class="mem-convert-avatar">AK</div>
                        <div>
                            <div class="mem-convert-name">Amara K.</div>
                            <div class="mem-convert-loc">Lagos, Nigeria</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php get_footer(); ?>
