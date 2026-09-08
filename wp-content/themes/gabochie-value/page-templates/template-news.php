<?php
/**
 * Template Name: News Hub
 *
 * Landing page for the Gabochie News Portal: ticker of the latest headlines,
 * a live feed grid, the seven news categories, a newsletter gate and the
 * recovery CTA. Assigned to /news/ by the gabochie-news-portal plugin on
 * activation.
 *
 * @package Gabochie_Value
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$news_cpt = 'news';
$latest   = new WP_Query(
	array(
		'post_type'      => $news_cpt,
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'no_found_rows'  => true,
	)
);

$ticker = new WP_Query(
	array(
		'post_type'      => $news_cpt,
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'no_found_rows'  => true,
	)
);

get_header();
?>

<main id="main" class="gv-news">

<style>
.gv-news{--gv-teal:#0A4840;--gv-teal-deep:#072E28;--gv-gold:#C9A96E;--gv-ivory:#FDFCF9;--gv-ink:#1C2B28;}
.gv-news{background:var(--gv-ivory);color:var(--gv-ink);}
.gv-news .container{max-width:1120px;margin:0 auto;padding:0 22px;}
.gv-news .n-h1{font-family:Georgia,'Playfair Display',serif;font-size:clamp(30px,4.4vw,48px);line-height:1.12;color:var(--gv-teal-deep);margin:0 0 14px 0;}
.gv-news .n-sub{font-size:17px;line-height:1.6;color:#40504b;max-width:680px;margin:0 0 26px 0;}

/* ticker */
.gv-news .n-ticker{border-top:1px solid #e2ddce;border-bottom:1px solid #e2ddce;padding:10px 0;margin:0 0 34px 0;display:flex;flex-wrap:wrap;gap:6px 22px;}
.gv-news .n-ticker b{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--gv-gold);white-space:nowrap;padding-top:2px;}
.gv-news .n-ticker a{font-size:14px;color:var(--gv-ink);text-decoration:none;}
.gv-news .n-ticker a:hover{color:var(--gv-teal);text-decoration:underline;}

/* grid */
.gv-news .n-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin:0 0 40px 0;}
.gv-news .n-card{display:block;background:#fff;border:1px solid #e4dfd0;border-radius:14px;padding:22px;text-decoration:none;color:var(--gv-ink);transition:transform .16s ease,box-shadow .16s ease;}
.gv-news .n-card:hover{transform:translateY(-3px);box-shadow:0 10px 26px rgba(7,46,40,.10);}
.gv-news .n-meta{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--gv-gold);margin:0 0 8px 0;}
.gv-news .n-card h3{font-size:18px;line-height:1.35;margin:0 0 8px 0;}
.gv-news .n-card p{font-size:14px;line-height:1.55;color:#40504b;margin:0 0 12px 0;}
.gv-news .n-arr{font-size:13px;color:var(--gv-teal);font-weight:600;}

/* categories */
.gv-news .n-section-ey{margin:44px 0 6px 0;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--gv-gold);}
.gv-news .n-section-h2{font-family:Georgia,'Playfair Display',serif;font-size:30px;color:var(--gv-teal-deep);margin:0 0 22px 0;}
.gv-news .n-cats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:0 0 34px 0;}
.gv-news .n-cat{display:block;background:#fff;border:1px solid #e4dfd0;border-radius:12px;padding:18px;text-decoration:none;color:var(--gv-ink);}
.gv-news .n-cat:hover{border-color:var(--gv-gold);}
.gv-news .n-cat strong{display:block;font-size:15px;margin:0 0 4px 0;}
.gv-news .n-cat span{font-size:12px;color:#7a857f;}

/* gate + cta */
.gv-news .n-gate{background:var(--gv-teal);border-radius:16px;padding:32px;color:#fff;margin:0 0 36px 0;display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:center;}
.gv-news .n-gate h2{font-family:Georgia,'Playfair Display',serif;font-size:24px;margin:0 0 8px 0;color:#fff;}
.gv-news .n-gate p{font-size:14px;line-height:1.6;margin:0;color:#d8e4e0;}
.gv-news .n-gate .gab-nl-sub-fields{display:flex;flex-direction:column;gap:8px;}
.gv-news .n-gate .gab-nl-sub-label{font-size:12px;color:#d8e4e0;margin:0;}
.gv-news .n-gate .gab-nl-sub-input{flex:1;min-width:0;width:100%;box-sizing:border-box;padding:11px 13px;border:0;border-radius:8px;font-size:14px;color:#1C2B28;}
.gv-news .n-gate .gab-nl-sub-consent{display:flex;align-items:flex-start;gap:8px;font-size:12px;line-height:1.4;color:#d8e4e0;margin:4px 0 0 0;}
.gv-news .n-gate .gab-nl-sub-consent input{margin-top:2px;}
.gv-news .n-gate .gab-nl-sub-btn{border:0;border-radius:8px;background:var(--gv-gold);color:#072E28;font-weight:700;padding:11px 18px;cursor:pointer;font-size:14px;margin-top:10px;}
.gv-news .n-gate .gab-nl-sub-btn:disabled{opacity:.6;cursor:progress;}
.gv-news .n-gate .gab-nl-sub-msg{font-size:13px;margin:10px 0 0 0;min-height:20px;}
.gv-news .n-gate .gab-nl-sub-msg.ok{color:#d8f0e8;}
.gv-news .n-gate .gab-nl-sub-msg.err{color:#f3c9c0;}
.gv-news .n-gate .gv-nl-hp{position:absolute;left:-9999px;opacity:0;height:0;width:0;}

/* recovery band */
.gv-news .n-rec{background:#fff;border:1px solid #e4dfd0;border-radius:16px;padding:30px;margin:0 0 50px 0;display:flex;justify-content:space-between;align-items:center;gap:22px;flex-wrap:wrap;}
.gv-news .n-rec h3{font-family:Georgia,'Playfair Display',serif;font-size:22px;color:var(--gv-teal-deep);margin:0 0 6px 0;}
.gv-news .n-rec p{font-size:14px;line-height:1.6;color:#40504b;margin:0;max-width:640px;}
.gv-news .n-rec a{display:inline-block;background:var(--gv-teal);color:#fff;text-decoration:none;padding:13px 22px;border-radius:8px;font-weight:600;white-space:nowrap;}
.gv-news .n-rec a:hover{background:var(--gv-teal-deep);}

@media (max-width:900px){
	.gv-news .n-grid{grid-template-columns:1fr 1fr;}
	.gv-news .n-cats{grid-template-columns:repeat(2,1fr);}
	.gv-news .n-gate{grid-template-columns:1fr;}
}
@media (max-width:560px){
	.gv-news .n-grid{grid-template-columns:1fr;}
	.gv-news .n-cats{grid-template-columns:1fr;}
}
</style>

<!-- ========== HERO ========== -->
<section class="page-hero" style="background:var(--gv-teal-deep);color:#fff;">
	<div class="container" style="max-width:1120px;margin:0 auto;padding:56px 22px;">
		<div style="font-size:11px;letter-spacing:3px;text-transform:uppercase;color:var(--gv-gold);margin:0 0 12px 0;">Gabochie News Desk</div>
		<h1 class="n-h1" style="color:#fff;">The Ghana built-environment briefing.</h1>
		<p class="n-sub" style="color:#cfe0db;">Construction, property, land and policy headlines from Ghana — curated, sourced and given an honest "Gabochie take" on what each story means for the cost and timeline of your next build.</p>
	</div>
</section>

<section class="gv-news" style="padding:34px 0 10px 0;">
	<div class="container">
		<?php if ( $ticker->have_posts() ) : ?>
		<div class="n-ticker">
			<b>Latest</b>
			<?php
			while ( $ticker->have_posts() ) :
				$ticker->the_post();
				echo '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
			endwhile;
			wp_reset_postdata();
			?>
		</div>
		<?php endif; ?>

		<div class="n-section-ey">Fresh from the desk</div>
		<h2 class="n-section-h2">Latest headlines</h2>

		<?php if ( $latest->have_posts() ) : ?>
		<div class="n-grid">
			<?php
			while ( $latest->have_posts() ) :
				$latest->the_post();
				$src = (string) get_post_meta( get_the_ID(), '_gnp_source_name', true );
				$item_terms = get_the_terms( get_the_ID(), 'news_category' );
				?>
			<a class="n-card" href="<?php the_permalink(); ?>">
				<p class="n-meta">
					<?php echo esc_html( get_the_date() ); ?>
					<?php
					if ( '' !== $src ) :
						?>
						&middot; <?php echo esc_html( $src ); ?><?php endif; ?>
					<?php
					if ( $item_terms && ! is_wp_error( $item_terms ) ) :
						?>
						&middot; <?php echo esc_html( $item_terms[0]->name ); ?><?php endif; ?>
				</p>
				<h3><?php the_title(); ?></h3>
				<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>
				<span class="n-arr">Read the brief &rsaquo;</span>
			</a>
			<?php endwhile; ?>
		</div>
		<?php else : ?>
		<p style="color:#40504b;font-size:15px;">The desk is warming up — the first curated headlines will land here shortly.</p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>

		<div class="n-section-ey">Browse by lane</div>
		<h2 class="n-section-h2">News categories</h2>
		<div class="n-cats">
			<?php
			$terms = get_terms(
				array(
					'taxonomy'   => 'news_category',
					'hide_empty' => false,
				)
			);
			foreach ( $terms as $news_term ) {
				if ( is_wp_error( $news_term ) ) {
					continue;
				}
				$term_link = get_term_link( $news_term );
				$term_count = (int) $news_term->count;
				echo '<a class="n-cat" href="' . esc_url( $term_link ) . '"><strong>' . esc_html( $news_term->name ) . '</strong><span>' . esc_html( (string) $term_count ) . ' ' . ( 1 === $term_count ? 'headline' : 'headlines' ) . ' &rsaquo;</span></a>';
			}
			?>
		</div>

		<div class="n-gate">
			<div>
				<h2>Get the morning brief in your inbox.</h2>
				<p>One email a day — the headiest built-environment stories from Ghana, with our cost-aware take attached. No spam; unsubscribe anytime.</p>
			</div>
			<div>
				<?php if ( shortcode_exists( 'gabochie_subscribe' ) ) : ?>
					<?php echo do_shortcode( '[gabochie_subscribe]' ); ?>
				<?php else : ?>
					<p style="font-size:13px;color:#d8e4e0;">The subscribe form will appear here once the Gabochie Newsletter plugin is active.</p>
				<?php endif; ?>
			</div>
		</div>

		<div class="n-rec">
			<div>
				<h3>Already building? Know your next move.</h3>
				<p>If a project is stalled or over budget, the recovery library has the cost maths, and the GHS 1,500 Diagnostic turns it into a written, evidence-based verdict.</p>
			</div>
			<a href="<?php echo esc_url( gabochie_value_recovery_product_url() ); ?>">Book the Recovery Diagnostic</a>
		</div>
	</div>
</section>

</main>

<?php
get_footer();