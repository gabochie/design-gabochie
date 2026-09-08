<?php
// phpcs:disable WordPress.Files.FileName -- WP template hierarchy requires the underscore taxonomy slug (taxonomy-news_category.php).
/**
 * News category archive template (gabochie-news-portal).
 *
 * @package Gabochie_Value
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$news_term = get_queried_object();

get_header();
?>

<main id="main" class="gv-news-archive">

	<section class="page-hero">
		<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> /
			<a href="<?php echo esc_url( home_url( '/news/' ) ); ?>">News</a> /
			<?php echo esc_html( $news_term->name ); ?>
		</div>
		<h1><?php echo esc_html( $news_term->name ); ?></h1>
		<p>Curated built-environment headlines from Ghana <?php echo esc_html( strtolower( $news_term->name ) ); ?> news, each with a Gabochie take.</p>
		</div>
	</section>

	<section class="gv-news-archive-body" style="padding:44px 0 70px 0;">
		<div class="container" style="max-width:1120px;margin:0 auto;padding:0 22px;">
		<?php if ( have_posts() ) : ?>
		<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:36px;">
			<?php
			while ( have_posts() ) :
				the_post();
				$src = (string) get_post_meta( get_the_ID(), '_gnp_source_name', true );
				?>
			<a href="<?php the_permalink(); ?>" style="display:block;background:#fff;border:1px solid #e4dfd0;border-radius:12px;padding:20px;text-decoration:none;color:#1C2B28;">
				<p style="font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#A8843C;margin:0 0 8px 0;"><?php echo esc_html( get_the_date() ); ?> <?php echo '' !== $src ? '&middot; ' . esc_html( $src ) : ''; ?></p>
				<h2 style="font-size:17px;line-height:1.4;margin:0 0 8px 0;"><?php the_title(); ?></h2>
				<p style="font-size:14px;line-height:1.55;color:#40504b;margin:0 0 10px 0;"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18, '…' ) ); ?></p>
				<span style="font-size:13px;color:#0A4840;font-weight:600;">Read the brief &rsaquo;</span>
			</a>
			<?php endwhile; ?>
		</div>
		<div class="pagination" style="margin-top:20px;">
			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 2,
					'prev_text' => '&larr; Newer',
					'next_text' => 'Older &rarr;',
				)
			);
			?>
		</div>
		<?php else : ?>
		<p style="color:#40504b;font-size:15px;">No headlines in this lane yet — the desk curates daily.</p>
		<?php endif; ?>
		</div>
	</section>

</main>

<?php
get_footer();