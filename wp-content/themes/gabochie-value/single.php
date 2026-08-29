<?php
/**
 * Single post template
 * @package Gabochie_Value
 */
get_header();

$clarity    = gabochie_value_clarity_url();
$phone      = gabochie_value_phone();
$phone_tel  = gabochie_value_phone_tel();
$post_url   = get_permalink();
$post_title = get_the_title();
?>

<main id="main">

<?php
while ( have_posts() ) :
	the_post();
	?>

<!-- ========== POST HERO ========== -->
<section class="hero hero--solo" id="top">
	<div class="hero-left">
	<?php gabochie_value_breadcrumbs(); ?>
	<div class="hero-location"><span class="hero-location-dot"></span> <?php echo esc_html( get_the_date() ); ?>
		<?php $gabochie_author_id = get_post_field( 'post_author' ); ?>
		<?php if ( $gabochie_author_id ) : ?>
		&middot; By <span class="hero-author"><?php echo esc_html( get_the_author_meta( 'display_name', $gabochie_author_id ) ); ?></span>
		<?php endif; ?>
	</div>
	<h1 class="hero-h1"><?php the_title(); ?></h1>
	<div class="hero-rule"></div>
	<div class="hero-actions">
		<a class="btn-gold" href="<?php echo esc_url( $clarity ); ?>">Start Here</a>
	</div>
	<div class="hero-note">Prefer to talk? Call <?php echo esc_html( $phone ); ?></div>
	</div>
</section>

<!-- ========== CONTENT ========== -->
<section class="value" id="content">
	<div class="container">
	<?php if ( has_post_thumbnail() ) : ?>
	<div class="entry-thumb"><?php the_post_thumbnail( 'gabochie-value-hero' ); ?></div>
	<?php endif; ?>
	<div class="entry-content">
		<?php
		the_content();
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . __( 'Pages:', 'gabochie-value' ),
				'after'  => '</div>',
			)
		);
		?>
	</div>

	<!-- Social sharing -->
	<div class="post-share">
		<span class="post-share-label">Share this article</span>
		<div class="post-share-btns">
		<a class="share-btn share-fb" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_url( $post_url ); ?>" target="_blank" rel="noopener" aria-label="Share on Facebook"><svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
		<a class="share-btn share-x" href="https://twitter.com/intent/tweet?url=<?php echo esc_url( $post_url ); ?>&text=<?php echo esc_attr( $post_title ); ?>" target="_blank" rel="noopener" aria-label="Share on X"><svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
		<a class="share-btn share-li" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo esc_url( $post_url ); ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><svg viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4V9h4v1.5A6 6 0 0 1 16 8z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a>
		<a class="share-btn share-wa" href="https://wa.me/?text=<?php echo esc_attr( $post_title . ' ' . $post_url ); ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp"><svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg></a>
		</div>
	</div>

	<!-- Inline CTA -->
	<div class="post-inline-cta">
		<h3>Ready to move forward?</h3>
		<p>Get a tailored recommendation in five minutes &mdash; no jargon, no pressure.</p>
		<div class="post-inline-cta-btns">
		<a class="btn-gold" href="<?php echo esc_url( $clarity ); ?>">Start Here</a>
		<a class="btn-ghost" href="tel:<?php echo esc_attr( $phone_tel ); ?>">Call <?php echo esc_html( $phone ); ?></a>
		</div>
	</div>

	<!-- Email capture -->
	<div class="post-capture">
		<h4>Get practical building insights &mdash; no spam.</h4>
		<form class="guide-form post-capture-form" novalidate>
		<input type="email" placeholder="Your email" required aria-label="Email for building insights">
		<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="gd-hp" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
		<input type="hidden" name="source" value="blog-<?php echo esc_attr( get_post_field( 'post_name' ) ); ?>">
		<button type="submit">Subscribe</button>
		</form>
		<div class="guide-ok" style="display:none">Subscribed &mdash; welcome aboard.</div>
	</div>

	<div class="post-meta">
		<span><?php esc_html_e( 'Posted in', 'gabochie-value' ); ?> <?php the_category( ', ' ); ?></span>
		<?php
		if ( has_tag() ) :
			?>
			<span class="post-tags"><?php the_tags( '', ' ' ); ?></span><?php endif; ?>
	</div>
	<nav class="post-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'gabochie-value' ); ?>">
		<div class="post-nav-prev"><?php previous_post_link( '%link', '&larr; %title' ); ?></div>
		<div class="post-nav-next"><?php next_post_link( '%link', '%title &rarr;' ); ?></div>
	</nav>
	<?php comments_template(); ?>
	</div>
</section>

<?php endwhile; ?>

</main>

<?php get_footer(); ?>
