<?php
/**
 * Gabochie Community - public frontend: [gabochie_community] shortcode,
 * ask form AJAX handler, single-question template routing.
 *
 * @package Gabochie_Community
 */

defined( 'ABSPATH' ) || exit;

final class Gabochie_Community_Frontend {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'gabochie_community', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_gabochie_ask', array( $this, 'ajax_ask' ) );
		add_action( 'wp_ajax_nopriv_gabochie_ask', array( $this, 'ajax_ask' ) );
		add_filter( 'template_include', array( $this, 'question_template' ) );
		add_action( 'wp_head', array( $this, 'output_qa_schema' ), 2 );
	}

	/* ---------------------------------------------------------------------
	 * AEO: QAPage structured data on single questions
	 * ------------------------------------------------------------------- */

	/**
	 * Emit QAPage + Question + Answers JSON-LD so answer engines can cite
	 * community Q&A. Approved comments become answers (first = accepted).
	 */
	public function output_qa_schema() {
		if ( ! is_singular( Gabochie_Community_Post_Types::CPT ) ) {
			return;
		}
		$post = get_queried_object();
		if ( ! $post || 'publish' !== $post->post_status ) {
			return;
		}

		$answers = get_comments(
			array(
				'post_id' => $post->ID,
				'status'  => 'approve',
				'number'  => 10,
			)
		);

		$asker = get_post_meta( $post->ID, '_gabochie_asker_name', true );
		$name  = $asker ? $asker : 'A community member';

		$question = array(
			'@type'         => 'Question',
			'name'          => wp_strip_all_tags( $post->post_title ),
			'text'          => wp_strip_all_tags( $post->post_content ),
			'answerCount'   => count( $answers ),
			'datePublished' => get_the_date( 'c', $post ),
			'author'        => array(
				'@type' => 'Person',
				'name'  => $name,
			),
		);

		if ( ! empty( $answers ) ) {
			$accepted = array_shift( $answers );
			$question['acceptedAnswer'] = array(
				'@type'       => 'Answer',
				'text'        => wp_strip_all_tags( get_comment_text( $accepted ) ),
				'datePublished' => get_comment_date( 'c', $accepted ),
				'author'      => array(
					'@type' => 'Person',
					'name'  => get_comment_author( $accepted ),
				),
			);
			if ( ! empty( $answers ) ) {
				$question['suggestedAnswer'] = array();
				foreach ( $answers as $answer ) {
					$question['suggestedAnswer'][] = array(
						'@type'       => 'Answer',
						'text'        => wp_strip_all_tags( get_comment_text( $answer ) ),
						'datePublished' => get_comment_date( 'c', $answer ),
						'author'      => array(
							'@type' => 'Person',
							'name'  => get_comment_author( $answer ),
						),
					);
				}
			}
		}

		$data = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'QAPage',
			'mainEntity' => $question,
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/* ---------------------------------------------------------------------
	 * Assets (only on pages using the shortcode or a question single)
	 * ------------------------------------------------------------------- */

	public function enqueue_assets() {
		global $post;
		if ( is_admin() || ! is_a( $post, 'WP_Post' ) ) {
			return;
		}
		$on_page    = has_shortcode( $post->post_content, 'gabochie_community' );
		$on_question = is_singular( Gabochie_Community_Post_Types::CPT );
		if ( ! $on_page && ! $on_question ) {
			return;
		}

		wp_enqueue_style( 'gabochie-community', GABOCHIE_COMMUNITY_URL . 'assets/css/community.css', array(), GABOCHIE_COMMUNITY_VERSION );
		wp_enqueue_script( 'gabochie-community', GABOCHIE_COMMUNITY_URL . 'assets/js/community.js', array(), GABOCHIE_COMMUNITY_VERSION, true );
		wp_localize_script(
			'gabochie-community',
			'gabochie_community',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'gabochie_ask' ),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Shortcode: community page (stage chips + question list + ask form)
	 * ------------------------------------------------------------------- */

	public function render() {
		$cpt      = Gabochie_Community_Post_Types::CPT;
		$tax      = Gabochie_Community_Post_Types::TAX;
		$stages   = Gabochie_Community_Post_Types::STAGES;
		$active   = isset( $_GET['stage'] ) ? sanitize_key( $_GET['stage'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$paged    = max( 1, isset( $_GET['gq'] ) ? absint( $_GET['gq'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification

		$args = array(
			'post_type'      => $cpt,
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'comment_status' => 'open',
		);
		if ( $active && array_key_exists( $active, $stages ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $tax,
					'field'    => 'slug',
					'terms'    => $active,
				),
			);
		}

		$query = new WP_Query( $args );
		$base  = remove_query_arg( array( 'stage', 'gq' ) );

		ob_start();
		?>
		<div class="gqc-wrap">
			<section class="gqc-head">
				<div class="gqc-head-copy">
					<h2>Ask the Gabochie Community</h2>
					<p>Real questions from the building journey &mdash; answered by the Gabochie Design team. Pick a stage, browse what others asked, or post your own question.</p>
				</div>
				<a class="btn-gold gqc-ask-cta" href="#gqc-ask">Ask a question</a>
			</section>

			<nav class="gqc-chips" aria-label="Filter by journey stage">
				<a href="<?php echo esc_url( $base ); ?>" class="gqc-chip <?php echo $active ? '' : 'is-active'; ?>">All</a>
				<?php foreach ( $stages as $slug => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'stage', $slug, $base ) ); ?>" class="gqc-chip <?php echo $active === $slug ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<?php if ( $query->have_posts() ) : ?>
				<div class="gqc-list">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$terms      = get_the_terms( get_the_ID(), $tax );
						$stage_name = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
						$answers    = get_comments_number();
						$asker      = get_post_meta( get_the_ID(), '_gabochie_asker_name', true );
						?>
						<article class="gqc-card">
							<div class="gqc-stats" aria-label="<?php echo esc_attr( sprintf( _n( '%s answer', '%s answers', $answers, 'gabochie-community' ), number_format_i18n( $answers ) ) ); ?>">
								<span class="gqc-stats-num"><?php echo esc_html( number_format_i18n( $answers ) ); ?></span>
								<span class="gqc-stats-label"><?php echo esc_html( _n( 'answer', 'answers', $answers, 'gabochie-community' ) ); ?></span>
							</div>
							<div class="gqc-card-body">
								<?php if ( $stage_name ) : ?><span class="gqc-stage"><?php echo esc_html( $stage_name ); ?></span><?php endif; ?>
								<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<p><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: wp_strip_all_tags( get_the_content() ), 30 ) ); ?></p>
								<div class="gqc-card-meta">
									<span><?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) . ' ago' ); ?></span>
									<?php if ( $asker ) : ?><span class="gqc-card-asker">by <?php echo esc_html( $asker ); ?></span><?php endif; ?>
								</div>
							</div>
						</article>
					<?php endwhile; ?>
				</div>

				<?php
				$pagination = paginate_links(
					array(
						'base'    => add_query_arg( 'gq', '%#%', $base ),
						'format'  => '',
						'current' => $paged,
						'total'   => $query->max_num_pages,
						'type'    => 'list',
					)
				);
				if ( $pagination ) {
					echo '<nav class="gqc-pagination">' . wp_kses_post( $pagination ) . '</nav>';
				}
				?>
			<?php else : ?>
				<div class="gqc-empty">
					<svg class="gqc-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
					<h3>No questions yet<?php echo $active ? ' in this stage' : ''; ?></h3>
					<p>The team answers every question with practical, Ghana-specific guidance &mdash; ask the first one.</p>
					<a class="btn-gold" href="#gqc-ask">Ask a question</a>
				</div>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>

			<?php $this->render_ask_form(); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ---------------------------------------------------------------------
	 * Ask form (name, email, stage, question)
	 * ------------------------------------------------------------------- */

	private function render_ask_form() {
		$stages = Gabochie_Community_Post_Types::STAGES;
		?>
		<section class="gqc-ask" id="gqc-ask">
			<h2>Ask your question</h2>
			<p class="gqc-ask-note">Share where you are in your building journey and the team will reply with practical, Ghana-specific guidance.</p>
			<form class="gqc-form" id="gqc-ask-form" novalidate>
				<input type="hidden" name="hp" value="" aria-hidden="true" tabindex="-1" autocomplete="off">
				<div class="gqc-form-row">
					<div class="gqc-field">
						<label for="gqc-name">Your name</label>
						<input type="text" id="gqc-name" name="name" required maxlength="80" placeholder="e.g. Kwame Mensah">
					</div>
					<div class="gqc-field">
						<label for="gqc-email">Email (never shown publicly)</label>
						<input type="email" id="gqc-email" name="email" required maxlength="120" placeholder="you@example.com">
					</div>
				</div>
				<div class="gqc-field">
					<label for="gqc-stage">Where are you in your journey?</label>
					<select id="gqc-stage" name="stage" required>
						<?php foreach ( $stages as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="gqc-field">
					<label for="gqc-title">Question title</label>
					<input type="text" id="gqc-title" name="title" required maxlength="120" placeholder="e.g. How much does a feasibility study cost in Accra?">
				</div>
				<div class="gqc-field">
					<label for="gqc-body">Your question</label>
					<textarea id="gqc-body" name="body" required rows="5" maxlength="2000" placeholder="Give enough detail for a useful answer..."></textarea>
				</div>
				<div class="gqc-form-actions">
					<button type="submit" class="gqc-submit btn-gold">Post question</button>
					<span class="gqc-form-msg" role="status" aria-live="polite"></span>
				</div>
			</form>
		</section>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * AJAX submit
	 * ------------------------------------------------------------------- */

	public function ajax_ask() {
		check_ajax_referer( 'gabochie_ask', 'nonce' );

		// Honeypot: real users never see this field.
		if ( ! empty( $_POST['hp'] ) ) {
			wp_send_json_error( array( 'message' => 'Submission rejected.' ) );
		}

		$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$stage = sanitize_key( $_POST['stage'] ?? '' );
		$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
		$body  = sanitize_textarea_field( wp_unslash( $_POST['body'] ?? '' ) );

		if ( '' === $name || ! is_email( $email ) || '' === $title || '' === $body ) {
			wp_send_json_error( array( 'message' => 'Please fill in your name, a valid email, a title and your question.' ) );
		}
		if ( ! array_key_exists( $stage, Gabochie_Community_Post_Types::STAGES ) ) {
			$stage = 'getting-started';
		}

		$classification = Gabochie_Community_Classifier::instance()->classify( $title, $body, $stage );

		$post_id = wp_insert_post(
			array(
				'post_type'      => Gabochie_Community_Post_Types::CPT,
				'post_status'    => 'publish',
				'post_title'     => $title,
				'post_content'   => $body,
				'post_author'    => 0,
				'comment_status' => 'open',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Could not save your question. Please try again.' ) );
		}

		wp_set_object_terms( $post_id, array( $classification['stage'] ), Gabochie_Community_Post_Types::TAX );
		update_post_meta( $post_id, '_gabochie_asker_name', $name );
		update_post_meta( $post_id, '_gabochie_asker_email', $email );
		update_post_meta( $post_id, '_gabochie_stage_source', $classification['source'] );
		update_post_meta( $post_id, '_gabochie_lead_score', $classification['lead'] ? 1 : 0 );
		update_post_meta( $post_id, '_gabochie_lead_reason', $classification['lead_reason'] );

		// Route the asker into the lead hub so they reach FluentCRM too.
		do_action(
			'gabochie/lead/captured',
			$email,
			array(
				'list'        => 'community',
				'source'      => 'community-ask',
				'name'        => $name,
				'stage'       => $stage,
				'lead'        => $classification['lead'] ? 1 : 0,
				'lead_reason' => $classification['lead_reason'],
			)
		);

		wp_send_json_success(
			array(
				'url'     => get_permalink( $post_id ),
				'stage'   => $classification['stage'],
				'lead'    => (bool) $classification['lead'],
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Single question template (Q&A page with answers via comments)
	 * ------------------------------------------------------------------- */

	public function question_template( $template ) {
		if ( is_singular( Gabochie_Community_Post_Types::CPT ) ) {
			$custom = GABOCHIE_COMMUNITY_PATH . 'templates/single-question.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}
		return $template;
	}
}

Gabochie_Community_Frontend::instance();
