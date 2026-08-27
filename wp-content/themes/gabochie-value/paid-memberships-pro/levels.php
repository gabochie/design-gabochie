<?php
/**
 * Membership Levels page override.
 * @package Gabochie_Value
 */
get_header('shop');
?>
<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Membership Levels</div>
        <h1>Choose Your <em>Membership</em></h1>
        <p>Select the level that fits your project. Upgrade anytime as your needs grow.</p>
    </div>
</section>

<section class="mem-section">
    <div class="mem-container">
        <div class="mem-levels-grid">
            <?php
            global $pmpro_levels;
            if (!empty($pmpro_levels)) :
                $i = 0;
                foreach ($pmpro_levels as $level) :
                    $is_featured = ($level->id === 2); // Active Client is featured
                    $price = pmpro_formatPrice($level->initial_payment);
                    $features = array();
                    if (!empty($level->description)) {
                        $features = array_filter(array_map('trim', explode("\n", $level->description)));
                    }
            ?>
            <div class="mem-level-card<?php echo $is_featured ? ' featured' : ''; ?>">
                <?php if ($is_featured) : ?>
                    <div class="mem-level-badge">Most Popular</div>
                <?php endif; ?>

                <div class="mem-level-icon">
                    <?php if ($level->id === 1) : ?>
                        <svg viewBox="0 0 48 48" fill="none" stroke="var(--teal)" stroke-width="2"><circle cx="24" cy="24" r="18"/><path d="M24 16v16M16 24h16"/></svg>
                    <?php elseif ($level->id === 2) : ?>
                        <svg viewBox="0 0 48 48" fill="none" stroke="var(--gold)" stroke-width="2"><rect x="8" y="6" width="32" height="36" rx="3"/><line x1="14" y1="14" x2="34" y2="14"/><line x1="14" y1="22" x2="28" y2="22"/><path d="M14 30h20M14 36h12"/></svg>
                    <?php else : ?>
                        <svg viewBox="0 0 48 48" fill="none" stroke="var(--teal)" stroke-width="2"><polygon points="24,4 44,18 44,40 4,40 4,18"/><circle cx="24" cy="24" r="8"/></svg>
                    <?php endif; ?>
                </div>

                <h3 class="mem-level-name"><?php echo esc_html($level->name); ?></h3>

                <div class="mem-level-price">
                    <?php echo $level->initial_payment > 0 ? $price : 'Free'; ?>
                </div>
                <?php if ($level->initial_payment > 0) : ?>
                    <div class="mem-level-period">one-time payment</div>
                <?php endif; ?>

                <ul class="mem-level-features">
                    <?php if ($level->id === 1) : ?>
                        <li>Project portal access</li>
                        <li>Development Readiness Score</li>
                        <li>AI consultation tools</li>
                        <li>Community Q&A access</li>
                    <?php elseif ($level->id === 2) : ?>
                        <li>Everything in Free Lead</li>
                        <li>Full project dashboard</li>
                        <li>Document vault & messaging</li>
                        <li>Design review & feedback</li>
                        <li>Priority support</li>
                    <?php else : ?>
                        <li>Everything in Active Client</li>
                        <li>Interior design concepts</li>
                        <li>Project management tools</li>
                        <li>Cost tracking & analytics</li>
                        <li>Dedicated account manager</li>
                        <li>Priority scheduling</li>
                    <?php endif; ?>
                </ul>

                <a class="mem-level-cta" href="<?php echo esc_url(home_url('/membership-checkout/?pmpro_level=' . $level->id)); ?>">
                    <?php echo $level->initial_payment > 0 ? 'Get Started' : 'Join Free'; ?>
                </a>
            </div>
            <?php
                $i++;
                endforeach;
            else :
            ?>
            <div class="mem-empty-state" style="grid-column: 1/-1;">
                <p>No membership levels available yet. Please check back soon.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php get_footer('shop');
