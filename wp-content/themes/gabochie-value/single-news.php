<?php
/**
 * Single news item template (gabochie-news-portal). Renders the sourced
 * headline brief plus the editor-approved "Gabochie take" gold box, related
 * items and the recovery CTA.
 *
 * @package Gabochie_Value
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$clarity = gabochie_value_clarity_url();

get_header();
?>

<main id="main" class="gv-news-single">

<?php
while ( have_posts() ) :
	the_post();

	$source_name = (string) get_post_meta( get_the_ID(), '_gnp_source_name', true );
	$source_url  = (string) get_post_meta( get_the_ID(), '_gnp_source_url', true );
	$take        = '';
	if ( class_exists( 'Gabochie_News_AI' ) ) {
		$take = Gabochie_News_AI::instance()->approved_take( (int) get_the_ID() );
	}
		$terms     = get_the_terms( get_the_ID(), 'news_category' );
	$news_term = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	?>

	<!-- ========== HERO ========== -->
	<section class="page-hero">
		<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> /
			<a href="<?php echo esc_url( home_url( '/news/' ) ); ?>">News</a>
			<?php
			if ( $news_term ) :
				?>
				/ <a href="<?php echo esc_url( get_term_link( $news_term ) ); ?>"><?php echo esc_html( $news_term->name ); ?></a><?php endif; ?>
		</div>
		<div class="page-hero-meta">
			<span><?php echo esc_html( get_the_date() ); ?></span>
			<?php if ( '' !== $source_name ) : ?>
			<span class="ph-dot" aria-hidden="true"></span>
			<span>via <strong><?php echo esc_html( $source_name ); ?></strong></span>
			<?php endif; ?>
		</div>
		<h1><?php the_title(); ?></h1>
		<?php if ( has_excerpt() ) : ?>
		<p><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
		</div>
	</section>

	<!-- ========== BODY ========== -->
	<section class="post-body">
		<div class="container gv-news-brief">
		<div class="entry-content">
			<?php the_content(); ?>
			<?php if ( '' !== $source_url ) : ?>
			<p style="margin-top:20px;font-size:14px;color:#6b7681;">Full story: <a href="<?php echo esc_url( $source_url ); ?>" rel="noopener nofollow" target="_blank"><?php echo esc_html( $source_name ); ?></a> &uarr;</p>
			<?php endif; ?>
		</div>

		<?php if ( '' !== $take ) : ?>
		<div class="gv-news-take" style="margin-top:28px;background:#FDF9F0;border:1px solid #E8D5A8;border-left:5px solid #C9A96E;border-radius:12px;padding:22px 24px;">
			<div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A8843C;margin:0 0 10px 0;">The Gabochie take</div>
			<div style="font-size:15px;line-height:1.7;color:#1C2B28;max-width:760px;"><?php echo wp_kses_post( $take ); ?></div>
		</div>
		<?php endif; ?>

		<div class="post-share" style="margin-top:34px;">
			<span class="post-share-label">Share this brief</span>
			<div class="post-share-btns">
			<a class="share-btn share-fb" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_url( get_permalink() ); ?>" target="_blank" rel="noopener" aria-label="Share on Facebook"><svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
			<a class="share-btn share-x" href="https://twitter.com/intent/tweet?url=<?php echo esc_url( get_permalink() ); ?>&text=<?php echo esc_attr( get_the_title() ); ?>" target="_blank" rel="noopener" aria-label="Share on X"><svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
			<a class="share-btn share-wa" href="https://wa.me/?text=<?php echo esc_attr( get_the_title() . ' ' . get_permalink() ); ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp"><svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg></a>
			</div>
		</div>

		<!-- CTA -->
		<div class="post-inline-cta">
			<h3>Want the full picture before you build?</h3>
			<p>Read the take, then get a tailored, cost-aware recommendation in five minutes.</p>
			<div class="post-inline-cta-btns">
			<a class="btn-gold" href="<?php echo esc_url( $clarity ); ?>">Start Here</a>
			<a class="btn-ghost" href="<?php echo esc_url( home_url( '/project-recovery/' ) ); ?>">Project Recovery</a>
			</div>
		</div>

		<nav class="post-nav" aria-label="News navigation">
			<div class="post-nav-prev"><?php previous_post_link( '%link', '&larr; %title', true, '', 'news_category' ); ?></div>
			<div class="post-nav-next"><?php next_post_link( '%link', '%title &rarr;', true, '', 'news_category' ); ?></div>
		</nav>
		</div>
	</section>

	<?php
	// Related in the same category.
	$related = new WP_Query(
		array(
			'post_type'      => 'news',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'post__not_in'   => array( get_the_ID() ),
			'no_found_rows'  => true,
			'tax_query'      => $news_term
				? array(
					array(
						'taxonomy' => 'news_category',
						'field'    => 'term_id',
						'terms'    => (int) $news_term->term_id,
					),
				)
				: array(),
		)
	);
	if ( $related->have_posts() ) :
		?>
		<section class="gv-news-related" style="padding:44px 0 60px 0;background:#F4F1E8;">
			<div class="container" style="max-width:1120px;margin:0 auto;padding:0 22px;">
			<div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A8843C;margin:0 0 6px 0;">More from this lane</div>
			<h2 style="font-family:Georgia,'Playfair Display',serif;font-size:28px;color:#072E28;margin:0 0 20px 0;">Related briefs</h2>
			<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
				<?php
				while ( $related->have_posts() ) :
					$related->the_post();
					$rsrc = (string) get_post_meta( get_the_ID(), '_gnp_source_name', true );
					?>
				<a href="<?php the_permalink(); ?>" style="display:block;background:#fff;border:1px solid #e4dfd0;border-radius:12px;padding:18px;text-decoration:none;color:#1C2B28;">
					<p style="font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#A8843C;margin:0 0 6px 0;"><?php echo esc_html( get_the_date() ); ?> <?php echo '' !== $rsrc ? '&middot; ' . esc_html( $rsrc ) : ''; ?></p>
					<h3 style="font-size:16px;line-height:1.35;margin:0;"><?php the_title(); ?></h3>
				</a>
				<?php endwhile; ?>
			</div>
			</div>
		</section>
		<?php
	endif;
	wp_reset_postdata();
	?>

<?php endwhile; ?>
</main>

<?php
get_footer();
