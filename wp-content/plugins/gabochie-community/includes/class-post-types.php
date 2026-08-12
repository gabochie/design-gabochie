<?php
/**
 * Gabochie Community - post type, journey taxonomy, admin list helpers.
 *
 * @package Gabochie_Community
 */

defined( 'ABSPATH' ) || exit;

final class Gabochie_Community_Post_Types {

	const CPT = 'gabochie_question';
	const TAX = 'gabochie_journey';

	/** Journey stages: slug => label. Single source of truth. */
	const STAGES = array(
		'getting-started'  => 'Getting Started',
		'feasibility'      => 'Feasibility & Budget',
		'design'           => 'Design & Drafting',
		'cost-value'       => 'Cost & Value',
		'permits'          => 'Permits & Compliance',
		'construction'     => 'Construction & Delivery',
		'after-completion' => 'After Completion',
	);

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'lead_filter' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_lead_filter' ) );
	}

	/** Runs on plugin activation: register everything + flush rewrite rules. */
	public static function activate() {
		self::register();
		self::seed_terms();
		flush_rewrite_rules();
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'labels'       => array(
					'name'          => 'Community Questions',
					'singular_name' => 'Question',
					'add_new_item'  => 'Add New Question',
					'edit_item'     => 'Edit Question',
				),
				'public'       => true,
				'has_archive'  => false,
				'rewrite'      => array( 'slug' => 'question' ),
				'supports'     => array( 'title', 'editor', 'comments', 'custom-fields' ),
				'menu_icon'    => 'dashicons-format-chat',
				'show_in_rest' => false,
				'menu_position'=> 26,
			)
		);

		register_taxonomy(
			self::TAX,
			array( self::CPT ),
			array(
				'labels'       => array(
					'name'          => 'Journey Stages',
					'singular_name' => 'Journey Stage',
				),
				'public'       => true,
				'hierarchical' => true,
				'show_in_rest' => false,
				'rewrite'      => array( 'slug' => 'journey' ),
			)
		);

		// Seed terms lazily on first load (activation hook may be skipped by some deploy paths).
		if ( ! get_option( 'gabochie_community_terms_seeded' ) ) {
			self::seed_terms();
		}
	}

	/** Ensure every journey stage exists as a taxonomy term. Idempotent. */
	public static function seed_terms() {
		foreach ( self::STAGES as $slug => $label ) {
			if ( ! term_exists( $slug, self::TAX ) ) {
				wp_insert_term( $label, self::TAX, array( 'slug' => $slug ) );
			}
		}
		update_option( 'gabochie_community_terms_seeded', 1, false );
	}

	/* ---------------------------------------------------------------------
	 * Admin list columns: journey stage, answers, lead flag.
	 * ------------------------------------------------------------------- */

	public function columns( $cols ) {
		return array(
			'cb'          => $cols['cb'],
			'title'       => 'Question',
			'journey'     => 'Journey Stage',
			'answers'     => 'Answers',
			'lead'        => 'Lead?',
			'date'        => 'Date',
		);
	}

	public function column_content( $col, $post_id ) {
		if ( 'journey' === $col ) {
			$terms = get_the_terms( $post_id, self::TAX );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$names = array_map( static function ( $t ) { return $t->name; }, $terms );
				echo esc_html( implode( ', ', $names ) );
			} else {
				echo '<em>—</em>';
			}
		}

		if ( 'answers' === $col ) {
			echo (int) get_comments_number( $post_id );
		}

		if ( 'lead' === $col ) {
			$score  = (int) get_post_meta( $post_id, '_gabochie_lead_score', true );
			$reason = get_post_meta( $post_id, '_gabochie_lead_reason', true );
			if ( $score ) {
				echo '<span style="display:inline-block;background:#2e7d5b;color:#fff;border-radius:10px;padding:2px 9px;font-size:11px;font-weight:700;" title="' . esc_attr( $reason ) . '">LEAD</span>';
			} else {
				echo '<span style="color:#8a8f8d;">no</span>';
			}
		}
	}

	/** Dropdown in the posts list: filter to lead-flagged questions. */
	public function lead_filter() {
		if ( ( $GLOBALS['typenow'] ?? '' ) !== self::CPT ) {
			return;
		}
		$current = isset( $_GET['gqc_lead'] ) ? '1' : ''; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<select name="gqc_lead">
			<option value="">All questions</option>
			<option value="1" <?php selected( $current, '1' ); ?>>Leads only</option>
		</select>
		<?php
	}

	public function apply_lead_filter( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== self::CPT ) {
			return;
		}
		if ( ! empty( $_GET['gqc_lead'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$meta = $query->get( 'meta_query', array() );
			$meta[] = array( 'key' => '_gabochie_lead_score', 'value' => 1 );
			$query->set( 'meta_query', $meta );
		}
	}
}

Gabochie_Community_Post_Types::instance();
