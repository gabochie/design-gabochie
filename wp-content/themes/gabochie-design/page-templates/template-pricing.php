<?php
/**
 * Template Name: Pricing
 * @package Gabochie_Design
 */
get_header();
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Pricing</div>
        <h1>Transparent <em>Pricing</em></h1>
        <p>Every package includes AI-powered design, expert architectural oversight, and delivery in your preferred currency.</p>
    </div>
</section>

<!-- PRICING TOGGLE + CARDS -->
<section class="pricing-section">
    <div class="container">
        <div class="currency-toggle-wrap">
            <div class="currency-toggle" id="pricing-currency-toggle">
                <button class="currency-btn active" data-currency="USD" type="button">USD $</button>
                <button class="currency-btn" data-currency="GHS" type="button">GHS GH₵</button>
            </div>
            <div id="live-rate-indicator" class="live-rate"></div>
        </div>
        <div class="price-grid">
            <?php
            $ghs_rate = 15.8;
            $plans = gabochie_get_pricing_plans();
            foreach ($plans as $plan) :
                $ghs = round($plan['usd'] * $ghs_rate);
            ?>
            <div class="price-card <?php echo $plan['featured'] ? 'featured' : ''; ?>" data-usd="<?php echo $plan['usd']; ?>" data-ghs="<?php echo $ghs; ?>">
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

<!-- ADDITIONAL SERVICES -->
<section class="addon-section">
    <div class="container addon-container">
        <div class="section-header">
            <div class="sec-ey">Add-On Services</div>
            <h2 class="sec-h2">Extend Your <em>Package</em></h2>
        </div>
        <div class="addon-grid">
            <div class="addon-card">
                <div class="addon-card-title">Interior Design</div>
                <div class="price-amt"><span class="price-cur" data-usd="$" data-ghs="GH₵">$</span><span class="price-num add-on-price" data-usd="1800"><?php echo number_format(1800); ?></span></div>
                <div class="price-per">one-time</div>
                <div class="addon-card-desc">
                    Full interior design package including space planning, material selection, mood boards, and detailed specifications.
                </div>
            </div>
            <div class="addon-card">
                <div class="addon-card-title">Project Management</div>
                <div class="addon-pm-price">8–12%</div>
                <div class="price-per">of total build cost</div>
                <div class="addon-card-desc">
                    End-to-end construction oversight with weekly AI reports, milestone verification, and contractor management.
                </div>
            </div>
            <div class="addon-card">
                <div class="addon-card-title">Landscape Design</div>
                <div class="price-amt"><span class="price-cur" data-usd="$" data-ghs="GH₵">$</span><span class="price-num add-on-price" data-usd="1200"><?php echo number_format(1200); ?></span></div>
                <div class="price-per">one-time</div>
                <div class="addon-card-desc">
                    Outdoor spaces, gardens, driveways, and boundary walls designed to complement your home.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WHAT'S INCLUDED TABLE -->
<section class="compare-section">
    <div class="container compare-container">
        <div class="section-header">
            <div class="sec-ey">Compare</div>
            <h2 class="sec-h2">What's <em>Included</em></h2>
        </div>
        <div class="compare-table-wrap">
            <table class="compare-table">
                <thead>
                    <tr class="compare-thead tr">
                        <th class="compare-th">Feature</th>
                        <th class="compare-th-center">Concept</th>
                        <th class="compare-th-center compare-th-highlight">Full Design</th>
                        <th class="compare-th-center">Complete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $features = gabochie_get_pricing_comparison();
                    foreach ($features as $row) :
                    ?>
                    <tr class="compare-tbody tr">
                        <td class="compare-td"><?php echo $row['name']; ?></td>
                        <td class="compare-td-center">
                            <?php if ($row['c'] === true) : ?><span class="compare-check">&#10003;</span>
                            <?php elseif ($row['c'] === false) : ?><span class="compare-dash">—</span>
                            <?php else : ?><span class="compare-text"><?php echo $row['c']; ?></span><?php endif; ?>
                        </td>
                        <td class="compare-td-center compare-td-highlight">
                            <?php if ($row['f'] === true) : ?><span class="compare-check">&#10003;</span>
                            <?php elseif ($row['f'] === false) : ?><span class="compare-dash">—</span>
                            <?php else : ?><span class="compare-text"><?php echo $row['f']; ?></span><?php endif; ?>
                        </td>
                        <td class="compare-td-center">
                            <?php if ($row['co'] === true) : ?><span class="compare-check">&#10003;</span>
                            <?php elseif ($row['co'] === false) : ?><span class="compare-dash">—</span>
                            <?php else : ?><span class="compare-text"><?php echo $row['co']; ?></span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- COST CALCULATOR TEASER -->
<section class="cta-teal-band">
    <div class="container cta-teal-inner">
        <h2>Need a Custom Estimate?</h2>
        <p>Use our AI Cost Calculator for a detailed breakdown based on your specific project requirements, location, and preferred finish level.</p>
        <a href="<?php echo esc_url(home_url('/building-cost-calculator/')); ?>" class="btn-gold">Open Cost Calculator</a>
    </div>
</section>

<!-- FAQ -->
<section class="faq-section">
    <div class="container faq-container">
        <div class="section-header">
            <div class="sec-ey">FAQ</div>
            <h2 class="sec-h2">Pricing <em>Questions</em></h2>
        </div>

        <div class="faq-item">
            <h3 class="faq-q">How much does it cost to build a house in Ghana?</h3>
            <p class="faq-a">Construction costs in Ghana range from <strong>$350–$900 per sqm</strong> depending on design style and finish level. A standard 3-bedroom home typically costs $45,000–$80,000, while a premium 4-bedroom home ranges from $80,000–$150,000.</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">What currencies do you accept?</h3>
            <p class="faq-a">We accept <strong>USD and GHS</strong>. Use the toggle above to switch currencies. Exchange rates are fetched live from the European Central Bank daily — you always see the current market rate.</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">What's the difference between Concept and Full Design?</h3>
            <p class="faq-a">The <strong>Concept</strong> package gives you AI-generated floor plans and basic 3D renders — perfect for exploring ideas. The <strong>Full Design</strong> package includes everything needed to start construction: architectural drawings, structural plans, material specs, and a bill of quantities.</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">Can I upgrade later?</h3>
            <p class="faq-a">Yes. Many clients start with the Concept package, then upgrade to Full Design once they're happy with the direction. You only pay the difference.</p>
        </div>
        <div class="faq-item">
            <h3 class="faq-q">Do you offer payment plans?</h3>
            <p class="faq-a">Yes. We offer milestone-based payment plans for the Full Design and Complete packages. Typically: 40% upfront, 30% at design review, 30% on delivery.</p>
        </div>
    </div>
</section>

<?php get_footer(); ?>
