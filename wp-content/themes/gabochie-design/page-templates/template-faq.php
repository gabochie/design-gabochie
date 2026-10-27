<?php
/**
 * Template Name: FAQ Page
 * @package Gabochie_Design
 */
get_header();
$faq_items = get_option('gabochie_faq_items', array());
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / FAQ</div>
        <h1>Frequently Asked <em>Questions</em></h1>
        <p>Everything you need to know about building in Ghana with Gabochie Design.</p>
    </div>
</section>

<section style="padding:6rem 2rem;background:var(--cream)">
    <div class="container" style="max-width:780px">
        <?php if (!empty($faq_items)) : ?>
            <?php foreach ($faq_items as $i => $faq) : ?>
            <div style="border-bottom:1px solid var(--border-light);padding:1.75rem 0" class="faq-item">
                <button onclick="this.parentElement.classList.toggle('open');this.querySelector('.faq-arrow').textContent=this.parentElement.classList.contains('open')?'\u2212':'\u002B'" style="width:100%;display:flex;justify-content:space-between;align-items:center;background:none;border:none;cursor:pointer;text-align:left;padding:0">
                    <span style="font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--charcoal)"><?php echo esc_html($faq['q']); ?></span>
                    <span class="faq-arrow" style="font-size:1.5rem;color:var(--gold);flex-shrink:0;margin-left:1rem">+</span>
                </button>
                <div style="max-height:0;overflow:hidden;transition:max-height 0.3s ease" class="faq-answer">
                    <p style="padding-top:1rem;font-size:0.95rem;line-height:1.9;color:var(--mist)"><?php echo wp_kses_post($faq['a']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p style="text-align:center;color:var(--mist)">FAQ content will appear here after running the Content Importer.</p>
        <?php endif; ?>
    </div>
</section>

<script>
document.querySelectorAll('.faq-item').forEach(function(item) {
    var answer = item.querySelector('.faq-answer');
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (item.classList.contains('open')) {
                answer.style.maxHeight = answer.scrollHeight + 'px';
            } else {
                answer.style.maxHeight = '0';
            }
        });
    });
    observer.observe(item, { attributes: true, attributeFilter: ['class'] });
});
</script>

<section class="cta-section">
    <div class="container">
        <h2>Still have <em>questions</em>?</h2>
        <p>Book a free 30-minute consultation. No commitment, any time zone.</p>
        <a href="mailto:<?php echo esc_attr(get_theme_mod('company_email', 'admin@design.gabochie.com')); ?>?subject=Book%20Consultation" class="btn-gold">Book Free Consultation</a>
    </div>
</section>

<?php get_footer(); ?>
