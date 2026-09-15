<?php
/**
 * Single community question template (question + answers + answer form).
 *
 * @package Gabochie_Community
 */

defined( 'ABSPATH' ) || exit;

get_header();

$tax    = Gabochie_Community_Post_Types::TAX;
$terms  = get_the_terms( get_the_ID(), $tax );
$stage  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
$asker  = get_post_meta( get_the_ID(), '_gabochie_asker_name', true );
$is_lead = (int) get_post_meta( get_the_ID(), '_gabochie_lead_score', true );
?>

<main id="main" class="container gqc-single">
	<nav class="gqc-back">
		<a href="<?php echo esc_url( home_url( '/community/' ) ); ?>">&larr; Back to Community</a>
	</nav>

	<article <?php post_class( 'gqc-question' ); ?>>
		<header class="gqc-question-head">
			<?php if ( $stage ) : ?><span class="gqc-stage"><?php echo esc_html( $stage ); ?></span><?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<div class="gqc-question-meta">
				<span><?php echo $asker ? esc_html( $asker ) : 'A community member'; ?></span>
				<span><?php echo esc_html( get_the_date() ); ?></span>
				<?php if ( $is_lead && current_user_can( 'manage_options' ) ) : ?>
					<span class="gqc-lead-flag">Lead detected</span>
				<?php endif; ?>
			</div>
		</header>
		<div class="gqc-question-body">
			<?php the_content(); ?>
		</div>
	</article>

	<section class="gqc-answers">
		<?php
		$count   = get_comments_number();
		$answers = get_comments(
			array(
				'post_id' => get_the_ID(),
				'status'  => 'approve',
			)
		);
		?>
		<h2><?php echo esc_html( sprintf( _n( '%d answer', '%d answers', $count, 'gabochie-community' ), $count ) ); ?></h2>

		<?php if ( $answers ) : ?>
			<ol class="gqc-answer-list">
				<?php
				wp_list_comments(
					array(
						'style'       => 'ol',
						'format'      => 'html5',
						'callback'    => function ( $comment, $args, $depth ) {
							$c_author = get_comment_author( $comment );
							?>
							<li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'gqc-answer' ); ?>>
								<div class="gqc-answer-head">
									<strong><?php echo esc_html( $c_author ); ?></strong>
									<span><?php echo esc_html( get_comment_date( '', $comment ) ); ?></span>
								</div>
								<div class="gqc-answer-body"><?php comment_text( $comment ); ?></div>
							</li>
							<?php
						},
					),
					$answers
				);
				?>
			</ol>
		<?php else : ?>
			<p class="gqc-no-answers">No answers yet &mdash; the Gabochie team usually replies within a day or two. Need it faster? Call Gideon at <a href="tel:+233268248970">+233 26 824 8970</a>.</p>
		<?php endif; ?>

		<?php
		comment_form(
			array(
				'title_reply'          => 'Post your answer',
				'label_submit'         => 'Post answer',
				'comment_notes_before' => '',
				'comment_notes_after'  => '',
			)
		);
		?>
	</section>
</main>

<?php
get_footer();
