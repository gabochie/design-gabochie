<?php
/**
 * Gabochie Command Center — funding agent.
 *
 * Supervised-autonomy funding pipeline for Gabochie Design:
 *   Tier 1 (autonomous):     deadline monitoring + alerts (tasks + email),
 *                            funder-facing metrics snapshot.
 *   Tier 2 (autonomous + review): AI-drafted application materials that stay
 *                            in "review" until a human approves them.
 *   Tier 3 (human only):     sending anything to a funder. The agent never
 *                            sends; it builds copy-ready drafts the operator
 *                            sends themselves.
 *
 * Data lives in WordPress options (no schema churn):
 *   - gabochie_funding_opportunities  list of funding programs
 *   - gabochie_funding_metrics        snapshot for funder-facing reporting
 *   - gabochie_funding_drafts         generated materials awaiting review
 *   - gabochie_funding_settings       notify_emails + last_refresh
 *   - gabochie_funding_alerts         dedup map so 72h/24h/overdue fire once
 *   - gabochie_funding_log            last 40 agent events
 *
 * @package Gabochie_Command_Center
 */

defined( 'ABSPATH' ) || exit;

/**
 * Funding agent: pipeline, deadlines, metrics, application drafts.
 */
class Gabochie_CC_Funding {

	/** @var string */
	const OPT_OPPS = 'gabochie_funding_opportunities';

	/** @var string */
	const OPT_METRICS = 'gabochie_funding_metrics';

	/** @var string */
	const OPT_DRAFTS = 'gabochie_funding_drafts';

	/** @var string */
	const OPT_SETTINGS = 'gabochie_funding_settings';

	/** @var string */
	const OPT_ALERTS = 'gabochie_funding_alerts';

	/** @var string */
	const OPT_LOG = 'gabochie_funding_log';

	/** @var string */
	const CRON = 'gabochie_cc_funding_daily';

	/** @var string */
	const NONCE = 'gabochie_cc_funding_nonce';

	/** @var string */
	const ACTION_SAVE = 'gabochie_cc_funding_save';

	/** @var string */
	const ACTION_DELETE = 'gabochie_cc_funding_delete';

	/** @var string */
	const ACTION_REFRESH = 'gabochie_cc_funding_refresh';

	/** @var string */
	const ACTION_GENERATE = 'gabochie_cc_funding_generate';

	/** @var string */
	const ACTION_DRAFT = 'gabochie_cc_funding_draft_action';

	/** @var string */
	const ACTION_SEED = 'gabochie_cc_funding_seed';

	/** @var array Application material template types. */
	const MATERIAL_TYPES = array(
		'one_pager'          => 'One-pager / opportunity memo',
		'outreach_email'     => 'Outreach email to funder',
		'application_answer' => 'Application short answer',
		'follow_up'          => 'Follow-up email',
	);

	/**
	 * Hook everything.
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'schedule' ) );
		add_action( 'init', array( __CLASS__, 'ensure_cron' ) );
		add_action( self::CRON, array( __CLASS__, 'on_daily_cron' ) );
		add_filter( 'gabochie_cc_digest_lines', array( __CLASS__, 'digest_lines' ), 10, 2 );

		add_action( 'admin_post_' . self::ACTION_SAVE, array( __CLASS__, 'admin_save' ) );
		add_action( 'admin_post_' . self::ACTION_DELETE, array( __CLASS__, 'admin_delete' ) );
		add_action( 'admin_post_' . self::ACTION_REFRESH, array( __CLASS__, 'admin_refresh' ) );
		add_action( 'admin_post_' . self::ACTION_GENERATE, array( __CLASS__, 'admin_generate' ) );
		add_action( 'admin_post_' . self::ACTION_DRAFT, array( __CLASS__, 'admin_draft_action' ) );
		add_action( 'admin_post_' . self::ACTION_SEED, array( __CLASS__, 'admin_seed' ) );
	}

	/**
	 * Add a daily schedule for the funding agent.
	 *
	 * @param array $schedules Cron schedules.
	 * @return array
	 */
	public static function schedule( $schedules ) {
		$schedules['gabochie_cc_daily'] = array(
			'interval' => DAY_IN_SECONDS,
			'display'  => 'Daily (Funding Agent)',
		);
		return $schedules;
	}

	/**
	 * Schedule the daily funding run (idempotent, self-healing).
	 */
	public static function ensure_cron() {
		if ( wp_next_scheduled( self::CRON ) ) {
			return;
		}
		$now      = current_time( 'timestamp' );
		$tz       = wp_timezone();
		$today_07 = new DateTimeImmutable( 'today 07:00', $tz );
		$ts       = (int) $today_07->getTimestamp();
		if ( $ts <= $now ) {
			$ts = (int) $today_07->modify( '+1 day' )->getTimestamp();
		}
		wp_schedule_event( $ts, 'gabochie_cc_daily', self::CRON );
	}

	/**
	 * Daily run: deadline check + metrics refresh.
	 */
	public static function on_daily_cron() {
		self::log( 'Daily run started.' );
		$tasks = self::check_deadlines();
		$m     = self::metrics_snapshot( true );
		self::log( 'Daily run finished — ' . count( $tasks ) . ' new deadline task(s), snapshot stored.' );
	}

	/**
	 * Append one funding line to the RevenueOps digest.
	 *
	 * @param array $lines Digest lines.
	 * @param array $kpi   KPI snapshot (unused; read cached metrics).
	 * @return array
	 */
	public static function digest_lines( $lines, $kpi ) {
		$m       = self::metrics_snapshot();
		$lines[] = '';
		$lines[] = 'Funding: ' . (int) $m['applying'] . ' applying · ' . (int) $m['deadlines_30d'] . ' deadline(s) in 30d · ' . (int) $m['drafts_pending'] . ' material(s) awaiting review.';
		return $lines;
	}

	/**
	 * Opportunities list (option, keyed by slug).
	 *
	 * @return array
	 */
	public static function get_opportunities() {
		$opps = get_option( self::OPT_OPPS, array() );
		return is_array( $opps ) ? $opps : array();
	}

	/**
	 * Single opportunity.
	 *
	 * @param string $slug Opportunity slug.
	 * @return array|null
	 */
	public static function get_opportunity( $slug ) {
		$opps = self::get_opportunities();
		return isset( $opps[ $slug ] ) ? $opps[ $slug ] : null;
	}

	/**
	 * Persist the opportunity list.
	 *
	 * @param array $opps Full list.
	 */
	public static function set_opportunities( $opps ) {
		update_option( self::OPT_OPPS, $opps, false );
	}

	/**
	 * Create or update an opportunity.
	 *
	 * @param array  $data Raw POST-ish data.
	 * @param string $slug Existing slug to edit ('' = new).
	 * @return array|WP_Error array{slug,opp} or error.
	 */
	public static function save_opportunity( $data, $slug = '' ) {
		$name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		if ( '' === $name ) {
			return new WP_Error( 'gcc_funding_no_name', 'Program name is required.' );
		}
		$opps     = self::get_opportunities();
		$new_slug = sanitize_title( $name );
		if ( '' === $slug || ! isset( $opps[ $slug ] ) ) {
			$slug = $new_slug;
			$base = $slug;
			$i    = 2;
			while ( isset( $opps[ $slug ] ) ) {
				$slug = $base . '-' . $i;
				++$i;
			}
		}

		$deadline = isset( $data['deadline'] ) ? sanitize_text_field( $data['deadline'] ) : '';
		$deadline = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $deadline ) ? $deadline : '';

		$action_date = isset( $data['next_action_date'] ) ? sanitize_text_field( $data['next_action_date'] ) : '';
		$action_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $action_date ) ? $action_date : '';

		$status = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'researching';
		if ( ! in_array( $status, array( 'researching', 'qualifying', 'applying', 'submitted', 'funded', 'rejected', 'stale' ), true ) ) {
			$status = 'researching';
		}
		$funder = isset( $data['funder_type'] ) ? sanitize_key( $data['funder_type'] ) : 'other';
		if ( ! in_array( $funder, array( 'accelerator', 'grant', 'vc', 'incubator', 'government', 'competition', 'other' ), true ) ) {
			$funder = 'other';
		}
		$priority = isset( $data['priority'] ) ? (int) $data['priority'] : 3;
		$priority = max( 1, min( 5, $priority ) );

		$opp = array(
			'name'               => $name,
			'slug'               => $slug,
			'funder_type'        => $funder,
			'status'             => $status,
			'deadline'           => $deadline,
			'amount_range'       => isset( $data['amount_range'] ) ? sanitize_text_field( $data['amount_range'] ) : '',
			'equity_requirement' => isset( $data['equity_requirement'] ) ? sanitize_text_field( $data['equity_requirement'] ) : '',
			'application_url'    => isset( $data['application_url'] ) ? esc_url_raw( $data['application_url'] ) : '',
			'contact_email'      => isset( $data['contact_email'] ) ? sanitize_email( $data['contact_email'] ) : '',
			'next_action'        => isset( $data['next_action'] ) ? sanitize_text_field( $data['next_action'] ) : '',
			'next_action_date'   => $action_date,
			'notes'              => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
			'priority'           => $priority,
			'updated'            => current_time( 'mysql' ),
		);
		if ( ! isset( $opps[ $slug ]['created'] ) ) {
			$opp['created'] = current_time( 'mysql' );
		} else {
			$opp['created'] = $opps[ $slug ]['created'];
		}
		$opps[ $slug ] = $opp;
		uasort( $opps, array( __CLASS__, 'cmp_priority' ) );
		self::set_opportunities( $opps );
		self::log( 'Saved opportunity "' . $name . '" (' . $slug . ').' );
		return array(
			'slug' => $slug,
			'opp'  => $opp,
		);
	}

	/**
	 * Delete an opportunity.
	 *
	 * @param string $slug Slug.
	 * @return bool
	 */
	public static function delete_opportunity( $slug ) {
		$opps = self::get_opportunities();
		if ( ! isset( $opps[ $slug ] ) ) {
			return false;
		}
		unset( $opps[ $slug ] );
		self::set_opportunities( $opps );
		self::log( 'Deleted opportunity "' . $slug . '".' );
		return true;
	}

	/**
	 * Sort: higher priority first, then nearer deadlines.
	 *
	 * @param array $a Opportunity.
	 * @param array $b Opportunity.
	 * @return int
	 */
	public static function cmp_priority( $a, $b ) {
		$pa = (int) self::priority( $a );
		$pb = (int) self::priority( $b );
		if ( $pa !== $pb ) {
			return $pa > $pb ? -1 : 1;
		}
		$da = self::deadline_ts( $a );
		$db = self::deadline_ts( $b );
		if ( $da && $db ) {
			return $da < $db ? -1 : 1;
		}
		return strcmp( (string) $a['name'], (string) $b['name'] );
	}

	/**
	 * Auto priority (deadline pressure + status).
	 *
	 * @param array $o Opportunity.
	 * @return int
	 */
	public static function priority( $o ) {
		$p = isset( $o['priority'] ) ? (int) $o['priority'] : 3;
		$p = max( 1, min( 5, $p ) );
		if ( ! empty( $o['deadline'] ) ) {
			$ts = self::deadline_ts( $o );
			if ( $ts ) {
				$days = (int) floor( ( $ts - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );
				if ( $days < 0 ) {
					$p = ( 5 > $p ) ? 5 : $p;
				} elseif ( $days <= 2 ) {
					$p = ( 5 > $p ) ? 5 : $p;
				} elseif ( $days <= 7 ) {
					$p = ( 4 > $p ) ? 4 : $p;
				}
			}
		}
		if ( in_array( $o['status'], array( 'funded', 'rejected', 'stale' ), true ) ) {
			$p = min( $p, 1 );
		}
		return $p;
	}

	/**
	 * Deadline as timestamp (end of day), 0 if none.
	 *
	 * @param array $o Opportunity.
	 * @return int
	 */
	public static function deadline_ts( $o ) {
		if ( empty( $o['deadline'] ) ) {
			return 0;
		}
		$ts = strtotime( $o['deadline'] . ' 23:59:59', current_time( 'timestamp' ) );
		return $ts ? (int) $ts : 0;
	}

	/**
	 * Human deadline state.
	 *
	 * @param array $o Opportunity.
	 * @return string none|overdue|due24|due3|soon|later
	 */
	public static function deadline_status( $o ) {
		$ts = self::deadline_ts( $o );
		if ( ! $ts ) {
			return 'none';
		}
		$diff = $ts - current_time( 'timestamp' );
		if ( $diff < 0 ) {
			return 'overdue';
		}
		if ( $diff <= DAY_IN_SECONDS ) {
			return 'due24';
		}
		if ( $diff <= 3 * DAY_IN_SECONDS ) {
			return 'due3';
		}
		if ( $diff <= 7 * DAY_IN_SECONDS ) {
			return 'soon';
		}
		return 'later';
	}

	/**
	 * Tier-1 deadline agent: scan active opportunities and raise tasks + emails.
	 *
	 * @param bool $force Ignore dedup map (manual run).
	 * @return array New task summaries.
	 */
	public static function check_deadlines( $force = false ) {
		$opps   = self::get_opportunities();
		$alerts = get_option( self::OPT_ALERTS, array() );
		if ( ! is_array( $alerts ) ) {
			$alerts = array();
		}
		$new  = array();
		$send = array();

		foreach ( $opps as $slug => $o ) {
			if ( empty( $o['deadline'] ) ) {
				continue;
			}
			if ( in_array( $o['status'], array( 'funded', 'rejected', 'stale' ), true ) ) {
				continue;
			}
			$ts = self::deadline_ts( $o );
			if ( ! $ts ) {
				continue;
			}
			$diff = $ts - current_time( 'timestamp' );
			$key  = $slug . '|' . $o['deadline'];

			if ( $diff > DAY_IN_SECONDS && $diff <= 3 * DAY_IN_SECONDS && ( $force || empty( $alerts[ $key ]['d3'] ) ) ) {
				$name = $o['name'];
				$sum  = 'Deadline in <3 days: ' . $name . ( ! empty( $o['next_action'] ) ? ' — ' . $o['next_action'] : '' );
				self::add_funding_task( 'deadline', $sum, 'Deadline ' . $o['deadline'] . ' | ' . ( ! empty( $o['application_url'] ) ? $o['application_url'] : 'no URL on file' ), 0, 'warning' );
				$alerts[ $key ]['d3'] = time();
				$new[]                = $sum;
			}
			if ( $diff > 0 && $diff <= DAY_IN_SECONDS && ( $force || empty( $alerts[ $key ]['d1'] ) ) ) {
				$name = $o['name'];
				$sum  = 'Deadline in <24h: ' . $name . ( ! empty( $o['next_action'] ) ? ' — ' . $o['next_action'] : '' );
				self::add_funding_task( 'deadline', $sum, 'Deadline ' . $o['deadline'] . '. Decide: submit, reprioritise, or mark stale.', 0, 'error' );
				$alerts[ $key ]['d1'] = time();
				$new[]                = $sum;
				$send[]               = $sum;
			}
			if ( $diff <= 0 && ( $force || empty( $alerts[ $key ]['overdue'] ) ) ) {
				$name = $o['name'];
				$sum  = 'PAST DEADLINE: ' . $name . ( ! empty( $o['next_action'] ) ? ' — ' . $o['next_action'] : '' );
				self::add_funding_task( 'deadline', $sum, 'Deadline was ' . $o['deadline'] . '. Update status (submitted/rejected/stale).', 0, 'error' );
				$alerts[ $key ]['overdue'] = time();
				$new[]                     = $sum;
				$send[]                    = $sum;
			}
		}

		update_option( self::OPT_ALERTS, array_slice( $alerts, -120, 120, true ), false );
		if ( $send ) {
			self::mail( 'Funding deadlines need you', self::mail_lines( $send ) );
		}
		return $new;
	}

	/**
	 * Add a funding task to the orchestrator task log (guarded).
	 *
	 * @param string $kind     Task kind (deadline|review|metrics).
	 * @param string $summary  Short line.
	 * @param string $meta     Context.
	 * @param int    $user_id  User id.
	 * @param string $severity info|warning|error.
	 * @return int Task id or 0.
	 */
	public static function add_funding_task( $kind, $summary, $meta = '', $user_id = 0, $severity = 'info' ) {
		if ( ! class_exists( 'Gabochie_CC_Orchestrator' ) ) {
			return 0;
		}
		return Gabochie_CC_Orchestrator::add_task( 'funding_agent', $kind, 'funding_agent', $summary, $meta, $user_id, $severity );
	}

	/**
	 * Metrics snapshot for funder-facing reporting (cached 4h).
	 *
	 * @param bool $fresh Force recompute.
	 * @return array
	 */
	public static function metrics_snapshot( $fresh = false ) {
		$opt   = get_option( self::OPT_METRICS, array() );
		$stale = empty( $opt ) || empty( $opt['time'] ) || ( (int) $opt['time'] < ( time() - 4 * HOUR_IN_SECONDS ) );
		if ( ! $fresh && ! $stale ) {
			return $opt;
		}
		$kpi = array();
		if ( class_exists( 'Gabochie_CC_Funnel' ) ) {
			$kpi = Gabochie_CC_Funnel::metrics( $fresh );
			if ( ! is_array( $kpi ) ) {
				$kpi = array();
			}
		}

		$opps         = self::get_opportunities();
		$now          = current_time( 'timestamp' );
		$open         = 0;
		$applying     = 0;
		$deadlines_30 = 0;
		foreach ( $opps as $o ) {
			if ( ! in_array( $o['status'], array( 'funded', 'rejected', 'stale' ), true ) ) {
				++$open;
			}
			if ( in_array( $o['status'], array( 'qualifying', 'applying' ), true ) ) {
				++$applying;
			}
			$ts = self::deadline_ts( $o );
			if ( $ts && ( $ts - $now ) <= 30 * DAY_IN_SECONDS ) {
				++$deadlines_30;
			}
		}
		$drafts  = self::get_drafts();
		$pending = 0;
		foreach ( $drafts as $d ) {
			if ( 'draft' === $d['status'] ) {
				++$pending;
			}
		}

		$snapshot = array(
			'time'           => time(),
			'kpi'            => $kpi,
			'pipeline_total' => $open,
			'applying'       => $applying,
			'deadlines_30d'  => $deadlines_30,
			'drafts_pending' => $pending,
			'drafts_total'   => count( $drafts ),
		);
		update_option( self::OPT_METRICS, $snapshot, false );
		return $snapshot;
	}

	/**
	 * Key real metrics as marker strings (so QA can confirm the draft cites them).
	 *
	 * @return array
	 */
	public static function metrics_markers() {
		$m  = self::metrics_snapshot();
		$k  = isset( $m['kpi'] ) && is_array( $m['kpi'] ) ? $m['kpi'] : array();
		$mk = array();
		foreach ( array( 'revenue_total', 'mrr', 'leads_total', 'leads_30d', 'members_paid', 'engaged_30d', 'qualified' ) as $f ) {
			if ( isset( $k[ $f ] ) ) {
				$mk[] = (string) $k[ $f ];
			}
		}
		$mk[] = (string) $m['deadlines_30d'];
		$mk[] = (string) $m['drafts_pending'];
		return array_values( array_unique( $mk ) );
	}

	/**
	 * Funder-facing metrics report (plain text).
	 *
	 * @return string
	 */
	public static function report_text() {
		$m     = self::metrics_snapshot();
		$k     = isset( $m['kpi'] ) && is_array( $m['kpi'] ) ? $m['kpi'] : array();
		$lines = array(
			'GABOCHIE DESIGN — FUNDER METRICS SNAPSHOT',
			'Generated: ' . gmdate( 'Y-m-d H:i e', (int) $m['time'] ),
			'',
			'Revenue to date: ' . ( isset( $k['revenue_total'] ) ? '₵' . number_format( (float) $k['revenue_total'] ) : 'n/a' ),
			'MRR: ' . ( isset( $k['mrr'] ) ? '₵' . number_format( (float) $k['mrr'] ) : 'n/a' ) . ' /mo',
			'Active paid clients: ' . ( isset( $k['members_paid'] ) ? (int) $k['members_paid'] : 'n/a' ),
			'',
			'Captured pipeline (all-time): ' . ( isset( $k['leads_total'] ) ? (int) $k['leads_total'] : 'n/a' ),
			'Qualified: ' . ( isset( $k['qualified'] ) ? (int) $k['qualified'] : 'n/a' ),
			'Engaged last 30 days: ' . ( isset( $k['engaged_30d'] ) ? (int) $k['engaged_30d'] : 'n/a' ),
			'',
			'Funding pipeline: ' . (int) $m['pipeline_total'] . ' open · ' . (int) $m['applying'] . ' applying · ' . (int) $m['deadlines_30d'] . ' deadline(s) in 30d',
			'Materials awaiting review: ' . (int) $m['drafts_pending'],
			'',
			'Targets (GH₵1M/yr): ₵83,333 MRR · ~18 new paid/mo · ~900 leads/mo · 2 high-ticket/mo',
		);
		return implode( "\n", $lines );
	}

	/**
	 * Curated static context every prompt can use as ground truth.
	 *
	 * @return string
	 */
	public static function context_excerpt() {
		$to = (string) get_option( 'siteurl', '' );
		return wp_strip_all_tags(
			(string) apply_filters(
				'gabochie_cc_funding_context',
				"Gabochie Design is the Development Intelligence Platform for Ghana — 'make development understandable, measurable, investable.' "
				. 'Founder: Gideon Borketey Abochie (' . get_bloginfo( 'admin_email' ) . '). Site: ' . $to . '. '
				. 'Products (Ghana Cedis): Annual Advisory ₵2,400/yr; Premium Service ₵4,800/yr; Project Recovery Diagnostic ₵1,500 one-time; Free Discovery level. '
				. 'Positioning: 14 services, <60s estimate, 100% transparency. Ghana housing deficit ~1.8-2M units; most private builds stall (thesis: ~60%). '
				. 'Distribution: automated blog (44+ posts, Sep ' . gmdate( 'Y' ) . ' Price Board), community Q&A, onboarding quiz, thesis lead-magnet PDF (free download). '
				. 'Payments are manual via Airtel Money today with online checkout one gateway activation away. Context applies live metrics from the Command Center snapshot — never invent numbers.'
			)
		);
	}

	/**
	 * Generate an AI draft for an opportunity (Tier 2, review-gated).
	 *
	 * @param string $slug         Opportunity slug.
	 * @param string $type         One of MATERIAL_TYPES keys.
	 * @param array  $extra        Optional {question, recipient_name, recipient_email}.
	 * @return array|WP_Error Draft row or error.
	 */
	public static function generate_material( $slug, $type, $extra = array() ) {
		if ( ! isset( self::MATERIAL_TYPES[ $type ] ) ) {
			return new WP_Error( 'gcc_funding_bad_type', 'Unknown material type.' );
		}
		$opp = self::get_opportunity( $slug );
		if ( ! $opp ) {
			return new WP_Error( 'gcc_funding_no_opp', 'Opportunity not found.' );
		}
		if ( ! class_exists( 'Gab_Auto_AI' ) ) {
			return new WP_Error( 'gcc_funding_no_ai', 'Gab_Auto_AI is not available — enable gabochie-auto-blog or configure an AI provider.' );
		}

		$metrics       = self::report_text();
		$context       = self::context_excerpt();
		$deadline_note = ! empty( $opp['deadline'] )
			? 'Application deadline: ' . $opp['deadline'] . ' (site timezone).'
			: 'No hard deadline on file — frames the ask as time-sensitive qualitatively.';

		$opp_block = 'Opportunity: ' . $opp['name'] . ' (' . $opp['funder_type'] . ', status: ' . $opp['status'] . ")\n"
			. 'Amount/equity note: ' . ( $opp['amount_range'] ? $opp['amount_range'] : 'n/a' ) . ( $opp['equity_requirement'] ? ' · equity ' . $opp['equity_requirement'] : '' ) . "\n"
			. $deadline_note . "\n"
			. ( $opp['application_url'] ? 'URL: ' . $opp['application_url'] . "\n" : '' )
			. ( $opp['notes'] ? 'Notes: ' . $opp['notes'] . "\n" : '' );

		$system = "You are Gabochie Design's funding strategist. You produce concise, professional, fact-based materials for grant and accelerator applications. "
			. 'You NEVER invent metrics, revenue, users, or traction. The only numbers you may cite come from the provided METRICS SNAPSHOT and CONTEXT. '
			. 'Write in the first person as Gideon Borketey Abochie, founder. Ghana references and Ghana Cedis (GHS/₵) are correct. '
			. 'Output plain text formatted for email or a document (headings with #, bullets with -). No marketing filler, no emoji, no placeholders like [X] or TODO.';

		$prompt = 'CONTEXT (ground truth):' . "\n" . $context . "\n\n"
			. 'METRICS SNAPSHOT (the only figures you may use):' . "\n" . $metrics . "\n\n"
			. 'OPPORTUNITY:' . "\n" . $opp_block;

		switch ( $type ) {
			case 'outreach_email':
				$who     = ! empty( $extra['recipient_name'] ) ? ' ' . sanitize_text_field( $extra['recipient_name'] ) : '';
				$prompt .= "\n\nTASK: Write a short outreach email" . $who . ' pitching Gabochie Design for ' . $opp['name']
					. '. Start with a concrete subject line, then 3 short paragraphs: why this funder, what Gabochie does and one real traction marker, and a specific, low-friction ask (30-minute intro call). Sign off as Gideon. Keep under 220 words.';
				break;
			case 'application_answer':
				$q       = ! empty( $extra['question'] ) ? sanitize_textarea_field( $extra['question'] ) : '';
				$prompt .= "\n\nTASK: Write an application short answer (~120-180 words) for " . $opp['name'];
				$prompt .= $q ? ' answering this question: "' . $q . '"' : '. If no question is available, write a compelling "Why our startup, why now" narrative.';
				$prompt .= ' Use only the provided metrics. End with a forward-looking line.';
				break;
			case 'follow_up':
				$prompt .= "\n\nTASK: Write a polite follow-up email (~90-120 words) for " . $opp['name']
					. ' referencing an earlier conversation/application and reiterating one real traction marker from the snapshot. Ask nothing more than a short reply. Sign off as Gideon.';
				break;
			case 'one_pager':
			default:
				$prompt .= "\n\nTASK: Write a one-page opportunity memo (~230-280 words) for " . $opp['name']
					. ': (1) who Gabochie is, (2) what it does for Ghanaian development, (3) traction with the real metrics, (4) the ask and why now, (5) how funds will be used. Headings + bullets. Use only the provided metrics.';
				break;
		}

		$draft_text = '';
		$provider   = '';
		set_time_limit( 180 );
		$draft_text = (string) Gab_Auto_AI::instance()->complete( $system, $prompt );
		$provider   = (string) Gab_Auto_AI::instance()->last_provider();
		$draft_text = trim( $draft_text );

		$qa       = self::qa_material( $draft_text, $type );
		$drafts   = self::get_drafts();
		$drafts[] = array(
			'id'       => uniqid( 'gdf', true ),
			'slug'     => $slug,
			'type'     => $type,
			'created'  => current_time( 'mysql' ),
			'content'  => $draft_text,
			'status'   => 'draft',
			'qa'       => $qa,
			'provider' => $provider,
			'extra'    => array(
				'question'       => isset( $extra['question'] ) ? sanitize_textarea_field( $extra['question'] ) : '',
				'recipient_name' => isset( $extra['recipient_name'] ) ? sanitize_text_field( $extra['recipient_name'] ) : '',
			),
		);
		$drafts   = array_slice( $drafts, -25, 25, true );
		update_option( self::OPT_DRAFTS, $drafts, false );

		self::add_funding_task(
			'review',
			'New ' . self::MATERIAL_TYPES[ $type ] . ' for ' . $opp['name'] . ' — review &amp; approve',
			'QA pass: ' . ( $qa['pass'] ? 'yes' : 'no' ) . ( $qa['reasons'] ? ' | ' . implode( '; ', $qa['reasons'] ) : '' ),
			0,
			$qa['pass'] ? 'info' : 'warning'
		);
		self::log( 'Generated "' . self::MATERIAL_TYPES[ $type ] . '" for ' . $opp['name'] . ' (provider ' . ( $provider ? $provider : 'unknown' ) . ').' );

		return end( $drafts );
	}

	/**
	 * QA gate: no empty text, no provider failure marker, real metric cited.
	 *
	 * @param string $text Draft.
	 * @param string $type Material type.
	 * @return array{pass:bool,reasons:array,words:int}
	 */
	public static function qa_material( $text, $type ) {
		$reasons = array();
		$words   = str_word_count( wp_strip_all_tags( $text ) );
		if ( '' === trim( $text ) ) {
			$reasons[] = 'empty draft';
		}
		if ( false !== stripos( $text, 'All LLM providers failed' ) || false !== stripos( $text, 'provider failed' ) ) {
			$reasons[] = 'provider failure text in draft';
		}
		if ( false !== stripos( $text, '[X]' ) || false !== stripos( $text, '[INSERT' ) || false !== stripos( $text, 'TODO' ) ) {
			$reasons[] = 'placeholder token found';
		}
		if ( 'follow_up' !== $type && $words < 60 ) {
			$reasons[] = 'too short (' . $words . ' words)';
		}
		$markers = self::metrics_markers();
		$has     = false;
		foreach ( $markers as $mk ) {
			if ( '' !== $mk && false !== strpos( $text, $mk ) ) {
				$has = true;
				break;
			}
		}
		if ( 'follow_up' !== $type && ! $has ) {
			$reasons[] = 'no real metric cited';
		}
		return array(
			'pass'    => empty( $reasons ),
			'reasons' => $reasons,
			'words'   => $words,
		);
	}

	/**
	 * Drafts list.
	 *
	 * @return array
	 */
	public static function get_drafts() {
		$d = get_option( self::OPT_DRAFTS, array() );
		return is_array( $d ) ? $d : array();
	}

	/**
	 * Draft actions (human gate): approve / reject / delete.
	 *
	 * @param string $id    Draft id.
	 * @param string $action approve|reject|delete.
	 * @return bool
	 */
	public static function draft_action( $id, $action ) {
		$drafts = self::get_drafts();
		foreach ( $drafts as &$d ) {
			if ( $d['id'] === $id ) {
				if ( 'approve' === $action ) {
					$d['status'] = 'approved';
					self::log( 'Approved material #' . substr( $id, 0, 8 ) . ' — ready for the human to send.' );
				} elseif ( 'reject' === $action ) {
					$d['status'] = 'rejected';
					self::log( 'Rejected material #' . substr( $id, 0, 8 ) . '.' );
				} elseif ( 'delete' === $action ) {
					update_option(
						self::OPT_DRAFTS,
						array_values(
							array_filter(
								$drafts,
								static function ( $x ) use ( $id ) {
									return $x['id'] !== $id;
								}
							)
						),
						false
					);
					return true;
				} else {
					return false;
				}
				update_option( self::OPT_DRAFTS, $drafts, false );
				return true;
			}
		}
		return false;
	}

	/**
	 * App log writer (last 40).
	 *
	 * @param string $msg Message.
	 */
	public static function log( $msg ) {
		$log = get_option( self::OPT_LOG, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$log[] = array(
			't' => current_time( 'mysql' ),
			'm' => substr( $msg, 0, 300 ),
		);
		update_option( self::OPT_LOG, array_slice( $log, -40, 40, true ), false );
	}

	/**
	 * Log rows (latest first).
	 *
	 * @return array
	 */
	public static function log_rows() {
		$log = get_option( self::OPT_LOG, array() );
		return is_array( $log ) ? array_reverse( $log ) : array();
	}

	/**
	 * Build a plain-text email body from lines.
	 *
	 * @param array $lines Lines.
	 * @return string
	 */
	public static function mail_lines( $lines ) {
		return "Gabochie Design — Funding Agent\n\n" . implode( "\n", $lines ) . "\n\nDashboard: " . admin_url( 'admin.php?page=gabochie-command-center&tab=funding' );
	}

	/**
	 * Email the operator (admin-email + optional overrides).
	 *
	 * @param string $subject Subject.
	 * @param string $body    Body.
	 */
	public static function mail( $subject, $body ) {
		$set = get_option( self::OPT_SETTINGS, array() );
		$to  = ! empty( $set['notify_emails'] ) ? array_map( 'trim', explode( "\n", $set['notify_emails'] ) ) : array( (string) get_option( 'admin_email', '' ) );
		$to  = array_values( array_filter( $to, 'is_email' ) );
		if ( empty( $to ) ) {
			return;
		}
		foreach ( $to as $one ) {
			wp_mail( $one, '[Gabochie Funding] ' . $subject, $body );
		}
	}

	/**
	 * Default seed opportunities (mirrors the funding pack program matrix).
	 *
	 * @return array
	 */
	public static function seed_opportunities() {
		$mk   = static function ( $name, $funder, $status, $deadline, $amount, $equity, $url, $next, $next_date, $notes, $priority ) {
			return array(
				'name'               => $name,
				'funder_type'        => $funder,
				'status'             => $status,
				'deadline'           => $deadline,
				'amount_range'       => $amount,
				'equity_requirement' => $equity,
				'application_url'    => $url,
				'contact_email'      => '',
				'next_action'        => $next,
				'next_action_date'   => $next_date,
				'notes'              => $notes,
				'priority'           => $priority,
			);
		};
		$now  = current_time( 'mysql' );
		$seed = array(
			'yc-w27'                                 => $mk(
				'YC W27 (Y Combinator)',
				'accelerator',
				'qualifying',
				'2026-11-02',
				'$125k for 7%',
				'7%',
				'https://www.ycombinator.com/apply/',
				'Complete full application',
				'2026-10-20',
				'Decision Dec 11. Apply only if >10 paying clients by late Oct. Batch application deadline Nov 2.',
				2
			),
			'askya-ai-growth-platform'               => $mk(
				'Askya AI Growth Platform',
				'accelerator',
				'researching',
				'2026-12-04',
				'Zero-equity',
				'0%',
				'',
				'Research eligibility + sign up',
				'2026-10-01',
				'Program runs Oct 26-Dec 4 2026. AI-focused cohort; high fit for the voice agent + AI stack.',
				2
			),
			'mest-ai-startup-program'                => $mk(
				'MEST AI Startup Program',
				'accelerator',
				'researching',
				'2027-06-01',
				'US$100k pre-seed',
				'TBD',
				'',
				'Watch for cohort application window',
				'2027-03-01',
				'Pan-African, AI-focused; mid-2027 cohort. Founder training + investment.',
				3
			),
			'google-for-startups-accelerator-africa' => $mk(
				'Google for Startups Accelerator Africa',
				'accelerator',
				'researching',
				'',
				'AWS-style credits + mentorship',
				'0%',
				'https://startup.google.com/accelerator/africa/',
				'Watch for next cohort announcement',
				'',
				'Rolling cohorts with AI track. Free Cloud credits + advisors.',
				3
			),
			'aws-activate'                           => $mk(
				'AWS Activate',
				'grant',
				'researching',
				'',
				'$5k-$200k AWS credits',
				'0%',
				'https://aws.amazon.com/activate/',
				'Apply via AWS Activate portal',
				'',
				'Credits, not cash. Free tier + mentorship. Requires startup validation.',
				4
			),
			'microsoft-founders-hub'                 => $mk(
				'Microsoft Founders Hub',
				'grant',
				'researching',
				'',
				'$150k+ Azure credits + OpenAI access',
				'0%',
				'https://foundershub.startups.microsoft.com',
				'Create profile + verify startup',
				'',
				'Rolling. Azure + OpenAI credits very relevant to the AI stack.',
				4
			),
			'google-cloud-start'                     => $mk(
				'Google Cloud Start',
				'grant',
				'researching',
				'',
				'$2k credits',
				'0%',
				'',
				'Apply for Cloud credits',
				'',
				'Complements Google for Startups if accepted to both.',
				5
			),
			'gift-ep-agency-vctf'                    => $mk(
				'GETP + VCTF (Ghana gov funding channels)',
				'government',
				'researching',
				'',
				'Grant/co-investment (variable)',
				'TBD',
				'',
				'Check 2026/27 application windows',
				'',
				'Ghana government: Ghana Innovation Fund, Venture Capital Trust Fund channels. Local credibility win.',
				3
			),
			'ffv-capital'                            => $mk(
				'FFV Capital (Ghana)',
				'vc',
				'researching',
				'',
				'Early-stage equity',
				'TBD',
				'',
				'Send outreach email per funding pack',
				'2026-10-01',
				'Ghana-focused fund. Direct email outreach recommended by the funding pack.',
				3
			),
			'village-capital'                        => $mk(
				'Village Capital',
				'accelerator',
				'researching',
				'',
				'Non-dilutive + network',
				'0%',
				'',
				'Watch for Ghana/Africa cohorts',
				'',
				'Ecosystem track, peer-selected investments.',
				4
			),
			'impact-hub-accra'                       => $mk(
				'Impact Hub Accra',
				'incubator',
				'researching',
				'',
				'Programmes + network',
				'0%',
				'',
				'Engage local programmes',
				'',
				'Local incubator; network + deal-flow value.',
				4
			),
			'tony-elumelu-2027'                      => $mk(
				'Tony Elumelu Foundation 2027',
				'competition',
				'researching',
				'',
				'US$5k seed + $5k mentoring matched',
				'0%',
				'https://www.tefconnect.com/',
				'Prepare application (window opens ~Dec)',
				'2026-12-01',
				'Pan-African, no equity. Strong brand + training.',
				3
			),
		);
		foreach ( $seed as $slug => &$s ) {
			$s['slug']    = $slug;
			$s['created'] = $now;
			$s['updated'] = $now;
		}
		return $seed;
	}

	/**
	 * Seed defaults (idempotent — only adds missing slugs).
	 */
	public static function seed() {
		$opps  = self::get_opportunities();
		$seed  = self::seed_opportunities();
		$added = 0;
		foreach ( $seed as $slug => $s ) {
			if ( isset( $opps[ $slug ] ) ) {
				continue;
			}
			$opps[ $slug ] = $s;
			++$added;
		}
		uasort( $opps, array( __CLASS__, 'cmp_priority' ) );
		self::set_opportunities( $opps );
		self::log( 'Seeded ' . $added . ' default funding opportunities.' );
		return $added;
	}

	/**
	 * ---- Admin handlers (nonce-guarded, human-only) -------------------
	 */

	/**
	 * Save opportunity (POST).
	 */
	public static function admin_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( self::NONCE );
		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$res  = self::save_opportunity( $_POST, $slug );
		$to   = 'admin.php?page=gabochie-command-center&tab=funding';
		if ( is_wp_error( $res ) ) {
			$to .= '&fmsg=' . rawurlencode( $res->get_error_message() );
		} else {
			$to .= '&edit=' . rawurlencode( $res['slug'] ) . '&fmsg=' . rawurlencode( 'Saved.' );
		}
		wp_safe_redirect( admin_url( $to ) );
		exit;
	}

	/**
	 * Delete opportunity (GET).
	 */
	public static function admin_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( self::NONCE );
		$slug = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';
		self::delete_opportunity( $slug );
		wp_safe_redirect( admin_url( 'admin.php?page=gabochie-command-center&tab=funding&fmsg=' . rawurlencode( 'Deleted.' ) ) );
		exit;
	}

	/**
	 * Manual refresh: deadlines + metrics (GET).
	 */
	public static function admin_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( self::NONCE );
		$tasks = self::check_deadlines( true );
		self::metrics_snapshot( true );
		wp_safe_redirect( admin_url( 'admin.php?page=gabochie-command-center&tab=funding&fmsg=' . rawurlencode( 'Refreshed — ' . count( $tasks ) . ' deadline task(s).' ) ) );
		exit;
	}

	/**
	 * Generate material (POST).
	 */
	public static function admin_generate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( self::NONCE );
		$slug  = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$type  = isset( $_POST['mat_type'] ) ? sanitize_key( wp_unslash( $_POST['mat_type'] ) ) : 'one_pager';
		$extra = array(
			'question'       => isset( $_POST['question'] ) ? sanitize_textarea_field( wp_unslash( $_POST['question'] ) ) : '',
			'recipient_name' => isset( $_POST['recipient_name'] ) ? sanitize_text_field( wp_unslash( $_POST['recipient_name'] ) ) : '',
		);
		$res   = self::generate_material( $slug, $type, $extra );
		$to    = 'admin.php?page=gabochie-command-center&tab=funding&view=drafts';
		if ( is_wp_error( $res ) ) {
			$to .= '&fmsg=' . rawurlencode( $res->get_error_message() );
		} else {
			$to .= '&fmsg=' . rawurlencode( 'Draft ready for review.' );
		}
		wp_safe_redirect( admin_url( $to ) );
		exit;
	}

	/**
	 * Draft approve/reject/delete (GET).
	 */
	public static function admin_draft_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( self::NONCE );
		$id     = isset( $_GET['did'] ) ? sanitize_text_field( wp_unslash( $_GET['did'] ) ) : '';
		$action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';
		self::draft_action( $id, $action );
		wp_safe_redirect( admin_url( 'admin.php?page=gabochie-command-center&tab=funding&view=drafts&fmsg=' . rawurlencode( 'Draft ' . $action . 'd.' ) ) );
		exit;
	}

	/**
	 * Re-seed defaults (GET).
	 */
	public static function admin_seed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( self::NONCE );
		$added = self::seed();
		wp_safe_redirect( admin_url( 'admin.php?page=gabochie-command-center&tab=funding&fmsg=' . rawurlencode( 'Seeded ' . $added . ' defaults.' ) ) );
		exit;
	}

	/**
	 * ---- Admin rendering ----------------------------------------------
	 */

	/**
	 * Funding tab HTML.
	 */
	public static function render_funding_tab() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$view  = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'overview';
		$views = array(
			'overview'      => array( 'Pipeline', array( __CLASS__, 'render_overview' ) ),
			'opportunities' => array( 'Opportunities', array( __CLASS__, 'render_opportunities' ) ),
			'drafts'        => array( 'Materials', array( __CLASS__, 'render_drafts' ) ),
		);
		if ( ! isset( $views[ $view ] ) ) {
			$view = 'overview';
		}
		$nonce = wp_create_nonce( self::NONCE );
		$fmsg  = isset( $_GET['fmsg'] ) ? sanitize_text_field( wp_unslash( $_GET['fmsg'] ) ) : '';
		$base  = admin_url( 'admin.php?page=gabochie-command-center&tab=funding' );
		?>
		<h2>Funding Agent <span class="dashicons dashicons-money-alt" style="color:#2271b1;"></span>
			<a class="button button-primary button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION_REFRESH ), self::NONCE ) ); ?>">Run deadline check now</a>
			<a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION_SEED ), self::NONCE ) ); ?>" onclick="return confirm('Add any missing default opportunities?');">Re-seed defaults</a>
			<span style="font-size:12px;color:#50575e;font-weight:400;">Supervised autonomy — the agent tracks and drafts; only a human sends.</span>
		</h2>
		<?php
		if ( $fmsg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $fmsg ) . '</p></div>';
		}
		?>
		<nav class="nav-tab-wrapper">
			<?php
			foreach ( $views as $key => $t ) {
				$cls = $view === $key ? ' nav-tab-active' : '';
				echo '<a href="' . esc_url( $base . '&view=' . $key ) . '" class="nav-tab' . esc_attr( $cls ) . '">' . esc_html( $t[0] ) . '</a>';
			}
			?>
		</nav>
		<?php
		call_user_func( $views[ $view ][1], $nonce );
	}

	/**
	 * Pipeline overview sub-view.
	 *
	 * @param string $nonce Nonce.
	 */
	public static function render_overview( $nonce ) {
		$m      = self::metrics_snapshot();
		$opps   = self::get_opportunities();
		$drafts = self::get_drafts();
		$cards  = array(
			array( 'Open pipeline', (string) (int) $m['pipeline_total'], '#2271b1' ),
			array( 'Applying now', (string) (int) $m['applying'], '#0a7d32' ),
			array( 'Deadlines ≤30d', (string) (int) $m['deadlines_30d'], '#dba617' ),
			array( 'Materials awaiting review', (string) (int) $m['drafts_pending'], '#d63638' ),
		);
		echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-top:14px;">';
		foreach ( $cards as $c ) {
			echo '<div style="border:1px solid #c3c4c7;border-top:4px solid ' . esc_attr( $c[2] ) . ';background:#fff;border-radius:6px;padding:14px;">'
				. '<div style="color:#8c8f94;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">' . esc_html( $c[0] ) . '</div>'
				. '<div style="font-size:24px;font-weight:700;margin:4px 0;">' . esc_html( $c[1] ) . '</div></div>';
		}
		echo '</div>';

		echo '<p style="margin:16px 0 4px;"><strong>Funder-facing metrics snapshot</strong> <span class="description">(from Command Center KPIs + pipeline)</span></p>';
		echo '<pre style="background:#0d1117;color:#c9d1d9;padding:12px;border-radius:6px;max-width:760px;overflow:auto;font-size:12px;">' . esc_html( self::report_text() ) . '</pre>';

		$ai = class_exists( 'Gabochie_Command_Center' ) ? Gabochie_Command_Center::ai_state() : array();
		if ( ! empty( $ai['valid'] ) ) {
			echo '<div class="notice notice-success" style="max-width:760px;"><p>AI drafting is armed («' . esc_html( $ai['provider'] ) . '», ' . (int) $ai['fallbacks'] . ' fallback(s)). Generate materials from the Opportunities view.</p></div>';
		} else {
			echo '<div class="notice notice-error" style="max-width:760px;"><p>AI provider is not configured — check GhBC / Auto-Blog AI settings before generating materials.</p></div>';
		}

		echo '<h3>Next deadlines</h3>';
		$soon = array();
		foreach ( $opps as $o ) {
			$ds = self::deadline_status( $o );
			$ts = self::deadline_ts( $o );
			if ( ! $ts ) {
				continue;
			}
			$soon[] = array(
				'opp'   => $o,
				'fmt'   => gmdate( 'Y-m-d', $ts ),
				'days'  => (int) ceil( ( $ts - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ),
				'state' => $ds,
			);
		}
		usort(
			$soon,
			static function ( $a, $b ) {
				return $a['days'] <=> $b['days'];
			}
		);
		if ( empty( $soon ) ) {
			echo '<p class="description">No dated deadlines yet.</p>';
		} else {
			echo '<table class="widefat striped" style="max-width:760px;"><thead><tr><th>Program</th><th>Deadline</th><th>In</th><th>Status</th></tr></thead><tbody>';
			foreach ( array_slice( $soon, 0, 12 ) as $s ) {
				$badge = array(
					'overdue' => array( '#d63638', 'PAST' ),
					'due24'   => array( '#d63638', '<24h' ),
					'due3'    => array( '#dba617', '<3d' ),
					'soon'    => array( '#dba617', '<7d' ),
					'later'   => array( '#8c8f94', 'later' ),
					'none'    => array( '#8c8f94', '-' ),
				);
				$b     = isset( $badge[ $s['state'] ] ) ? $badge[ $s['state'] ] : $badge['none'];
				echo '<tr><td><strong>' . esc_html( $s['opp']['name'] ) . '</strong></td>'
					. '<td>' . esc_html( $s['fmt'] ) . '</td>'
					. '<td>' . ( $s['days'] < 0 ? absint( $s['days'] ) . 'd ago' : absint( $s['days'] ) . 'd' ) . '</td>'
					. '<td><span style="background:' . esc_attr( $b[0] ) . ';color:#fff;font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;">' . esc_html( $b[1] ) . '</span></td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h3>Agent log</h3>';
		$rows = self::log_rows();
		if ( empty( $rows ) ) {
			echo '<p class="description">Nothing logged yet.</p>';
		} else {
			echo '<pre style="background:#f6f7f7;border:1px solid #c3c4c7;padding:10px;border-radius:6px;max-height:220px;overflow:auto;font-size:12px;max-width:760px;">';
			foreach ( array_slice( $rows, 0, 20 ) as $r ) {
				echo esc_html( $r['t'] . '  ' . $r['m'] ) . "\n";
			}
			echo '</pre>';
		}
	}

	/**
	 * Opportunities CRUD sub-view.
	 *
	 * @param string $nonce Nonce.
	 */
	public static function render_opportunities( $nonce ) {
		$opps    = self::get_opportunities();
		$edit    = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( $_GET['edit'] ) ) : '';
		$editing = $edit && isset( $opps[ $edit ] ) ? $opps[ $edit ] : null;

		echo '<p><a class="button"' . ( $editing ? ' href="' . esc_url( admin_url( 'admin.php?page=gabochie-command-center&tab=funding&view=opportunities' ) ) . '"' : '' ) . '>' . ( $editing ? 'Cancel edit' : 'Add opportunity' ) . '</a></p>';

		echo '<table class="widefat striped" style="max-width:1200px;"><thead><tr>'
			. '<th>Program</th><th>Type</th><th>Status</th><th>Deadline</th><th>Amount / Equity</th><th>Next action</th><th>Priority</th><th>Actions</th>'
			. '</tr></thead><tbody>';
		foreach ( $opps as $slug => $o ) {
			$ds       = self::deadline_status( $o );
			$badge    = array(
				'overdue' => array( '#d63638', 'PAST' ),
				'due24'   => array( '#d63638', '<24h' ),
				'due3'    => array( '#dba617', '<3d' ),
				'soon'    => array( '#dba617', '<7d' ),
				'later'   => array( '#8c8f94', 'later' ),
				'none'    => array( '#8c8f94', '-' ),
			);
			$b        = isset( $badge[ $ds ] ) ? $badge[ $ds ] : $badge['none'];
			$dl       = ! empty( $o['deadline'] ) ? esc_html( $o['deadline'] ) . ' <span style="background:' . esc_attr( $b[0] ) . ';color:#fff;font-size:10px;padding:1px 6px;border-radius:99px;">' . esc_html( $b[1] ) . '</span>' : '<span class="description">n/a</span>';
			$edit_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION_DELETE . '&slug=' . urlencode( $slug ) ), self::NONCE );
			echo '<tr><td><strong>' . esc_html( $o['name'] ) . '</strong>'
				. ( ! empty( $o['application_url'] ) ? '<div><a href="' . esc_url( $o['application_url'] ) . '" target="_blank" rel="noopener">' . esc_html( $o['application_url'] ) . '</a></div>' : '' )
				. '</td>'
				. '<td>' . esc_html( $o['funder_type'] ) . '</td>'
				. '<td>' . esc_html( $o['status'] ) . '</td>'
				. '<td>' . wp_kses_post( $dl ) . '</td>'
				. '<td>' . esc_html( $o['amount_range'] ) . ( $o['equity_requirement'] ? ' <span class="description">· ' . esc_html( $o['equity_requirement'] ) . '</span>' : '' ) . '</td>'
				. '<td>' . esc_html( $o['next_action'] ) . ( $o['next_action_date'] ? ' <span class="description">(' . esc_html( $o['next_action_date'] ) . ')</span>' : '' ) . '</td>'
				. '<td>' . (int) self::priority( $o ) . '</td>'
				. '<td><a class="button button-small" href="' . esc_url( admin_url( 'admin.php?page=gabochie-command-center&tab=funding&view=opportunities&edit=' . urlencode( $slug ) ) ) . '">Edit</a> '
				. '<a class="button button-small" href="' . esc_url( $edit_url ) . '" onclick="return confirm(\'Delete this opportunity?\');">Delete</a></td></tr>';
		}
		echo '</tbody></table>';

		self::render_opportunity_form( $editing, $nonce );
		self::render_generate_form( $opps, $nonce );
	}

	/**
	 * Add/edit opportunity form.
	 *
	 * @param array|null $editing Opportunity being edited (null = new).
	 * @param string     $nonce   Nonce.
	 */
	public static function render_opportunity_form( $editing, $nonce ) {
		$o = $editing ? $editing : array(
			'name'               => '',
			'funder_type'        => 'other',
			'status'             => 'researching',
			'deadline'           => '',
			'amount_range'       => '',
			'equity_requirement' => '',
			'application_url'    => '',
			'contact_email'      => '',
			'next_action'        => '',
			'next_action_date'   => '',
			'notes'              => '',
			'priority'           => 3,
		);
		?>
		<h3><?php echo $editing ? 'Edit opportunity' : 'Add opportunity'; ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:860px;">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_SAVE ); ?>">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
			<input type="hidden" name="slug" value="<?php echo esc_attr( $editing ? $o['slug'] : '' ); ?>">
			<table class="form-table" role="presentation">
				<tr><th><label for="gf-name">Program name</label></th><td><input id="gf-name" class="regular-text" type="text" name="name" value="<?php echo esc_attr( $o['name'] ); ?>" required></td></tr>
				<tr><th><label for="gf-funder">Funder type</label></th><td>
					<select id="gf-funder" name="funder_type">
						<?php
						$types = array( 'accelerator', 'grant', 'vc', 'incubator', 'government', 'competition', 'other' );
						foreach ( $types as $t ) {
							echo '<option value="' . esc_attr( $t ) . '"' . selected( $o['funder_type'], $t, false ) . '>' . esc_html( $t ) . '</option>';
						}
						?>
					</select>
				</td></tr>
				<tr><th><label for="gf-status">Status</label></th><td>
					<select id="gf-status" name="status">
						<?php
						$stats = array( 'researching', 'qualifying', 'applying', 'submitted', 'funded', 'rejected', 'stale' );
						foreach ( $stats as $s ) {
							echo '<option value="' . esc_attr( $s ) . '"' . selected( $o['status'], $s, false ) . '>' . esc_html( $s ) . '</option>';
						}
						?>
					</select>
				</td></tr>
				<tr><th><label for="gf-deadline">Deadline (YYYY-MM-DD)</label></th><td><input id="gf-deadline" type="date" name="deadline" value="<?php echo esc_attr( $o['deadline'] ); ?>"></td></tr>
				<tr><th><label for="gf-amount">Amount range</label></th><td><input id="gf-amount" class="regular-text" type="text" name="amount_range" value="<?php echo esc_attr( $o['amount_range'] ); ?>" placeholder="e.g. US$100k pre-seed"></td></tr>
				<tr><th><label for="gf-equity">Equity</label></th><td><input id="gf-equity" class="regular-text" type="text" name="equity_requirement" value="<?php echo esc_attr( $o['equity_requirement'] ); ?>" placeholder="e.g. 7% or 0%"></td></tr>
				<tr><th><label for="gf-url">Application URL</label></th><td><input id="gf-url" class="regular-text" type="url" name="application_url" value="<?php echo esc_attr( $o['application_url'] ); ?>"></td></tr>
				<tr><th><label for="gf-email">Contact email</label></th><td><input id="gf-email" class="regular-text" type="email" name="contact_email" value="<?php echo esc_attr( $o['contact_email'] ); ?>"></td></tr>
				<tr><th><label for="gf-next">Next action</label></th><td><input id="gf-next" class="regular-text" type="text" name="next_action" value="<?php echo esc_attr( $o['next_action'] ); ?>"></td></tr>
				<tr><th><label for="gf-nextdate">Next action date</label></th><td><input id="gf-nextdate" type="date" name="next_action_date" value="<?php echo esc_attr( $o['next_action_date'] ); ?>"></td></tr>
				<tr><th><label for="gf-priority">Priority (1 low – 5 high)</label></th><td><input id="gf-priority" type="number" min="1" max="5" name="priority" value="<?php echo esc_attr( $o['priority'] ); ?>"></td></tr>
				<tr><th><label for="gf-notes">Notes</label></th><td><textarea id="gf-notes" class="large-text" rows="3" name="notes"><?php echo esc_textarea( $o['notes'] ); ?></textarea></td></tr>
			</table>
			<p><button class="button button-primary" type="submit"><?php echo $editing ? 'Update' : 'Save'; ?></button></p>
		</form>
		<?php
	}

	/**
	 * Draft composer (Tier 2).
	 *
	 * @param array  $opps  Opportunities.
	 * @param string $nonce Nonce.
	 */
	public static function render_generate_form( $opps, $nonce ) {
		?>
		<h3>Generate application material <span class="description">(review-gated — you approve before sending)</span></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:860px;">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_GENERATE ); ?>">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
			<table class="form-table" role="presentation">
				<tr><th><label for="gf-slug">Opportunity</label></th><td>
					<select id="gf-slug" name="slug">
						<?php
						foreach ( $opps as $slug => $o ) {
							echo '<option value="' . esc_attr( $slug ) . '">' . esc_html( $o['name'] . ' (' . $o['status'] . ')' ) . '</option>';
						}
						?>
					</select>
				</td></tr>
				<tr><th><label for="gf-type">Material type</label></th><td>
					<select id="gf-type" name="mat_type">
						<?php
						foreach ( self::MATERIAL_TYPES as $k => $l ) {
							echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</option>';
						}
						?>
					</select>
				</td></tr>
				<tr><th><label for="gf-recipient">Recipient name <span class="description">(email drafts)</span></label></th><td><input id="gf-recipient" class="regular-text" type="text" name="recipient_name"></td></tr>
				<tr><th><label for="gf-question">Specific question <span class="description">(application answers)</span></label></th><td><textarea id="gf-question" class="large-text" rows="2" name="question"></textarea></td></tr>
			</table>
			<p><button class="button button-primary" type="submit">Generate draft</button></p>
		</form>
		<?php
	}

	/**
	 * Drafts sub-view (human gate).
	 *
	 * @param string $nonce Nonce.
	 */
	public static function render_drafts( $nonce ) {
		$drafts = array_reverse( self::get_drafts() );
		if ( empty( $drafts ) ) {
			echo '<div class="notice notice-info"><p>No generated materials yet. Draft one from the Opportunities view.</p></div>';
			return;
		}
		foreach ( $drafts as $d ) {
			$opp         = self::get_opportunity( $d['slug'] );
			$oppname     = $opp ? $opp['name'] : $d['slug'];
			$type        = isset( self::MATERIAL_TYPES[ $d['type'] ] ) ? self::MATERIAL_TYPES[ $d['type'] ] : $d['type'];
			$st          = 'draft' === $d['status'] ? 'draft' : $d['status'];
			$badge       = array(
				'draft'    => array( '#dba617', 'AWAITING REVIEW' ),
				'approved' => array( '#0a7d32', 'APPROVED' ),
				'rejected' => array( '#d63638', 'REJECTED' ),
			);
			$b           = isset( $badge[ $st ] ) ? $badge[ $st ] : $badge['draft'];
			$approve_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION_DRAFT . '&did=' . rawurlencode( $d['id'] ) . '&sub=approve' ), self::NONCE );
			$reject_url  = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION_DRAFT . '&did=' . rawurlencode( $d['id'] ) . '&sub=reject' ), self::NONCE );
			$delete_url  = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION_DRAFT . '&did=' . rawurlencode( $d['id'] ) . '&sub=delete' ), self::NONCE );
			$qa          = $d['qa'];
			echo '<div style="border:1px solid #c3c4c7;background:#fff;border-radius:6px;padding:14px;margin-bottom:14px;max-width:980px;">'
				. '<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">'
				. '<strong>' . esc_html( $type ) . '</strong>'
				. '<span style="color:#50575e;">for ' . esc_html( $oppname ) . '</span>'
				. '<span style="background:' . esc_attr( $b[0] ) . ';color:#fff;font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;">' . esc_html( $b[1] ) . '</span>'
				. '<span class="description" style="margin-left:auto;">' . esc_html( gmdate( 'Y-m-d H:i', strtotime( $d['created'] ) ) ) . ( $d['provider'] ? ' · ' . esc_html( $d['provider'] ) : '' ) . '</span>'
				. '</div>'
				. ( ! empty( $d['extra']['question'] ) ? '<p class="description">Q: ' . esc_html( $d['extra']['question'] ) . '</p>' : '' )
				. '<div style="margin:10px 0;font-size:12px;">QA: <strong>' . ( ! empty( $qa['pass'] ) ? 'PASS' : 'FAIL' ) . '</strong> · ' . (int) $qa['words'] . ' words'
				. ( ! empty( $qa['reasons'] ) ? ' · <span style="color:#d63638;">' . esc_html( implode( '; ', $qa['reasons'] ) ) . '</span>' : '' ) . '</div>'
				. '<textarea rows="8" readonly class="large-text" style="font-family:monospace;font-size:12px;">' . esc_textarea( $d['content'] ) . '</textarea>'
				. '<p style="margin:8px 0 0;">'
				. '<button class="button button-small gfv-copy" data-target="tsa-' . esc_attr( substr( md5( $d['id'] ), 0, 8 ) ) . '">Copy to clipboard</button> ';
			if ( 'draft' === $d['status'] ) {
				echo '<a class="button button-small button-primary" href="' . esc_url( $approve_url ) . '">Approve</a> '
					. '<a class="button button-small" href="' . esc_url( $reject_url ) . '">Reject</a> ';
			}
			echo '<a class="button button-small" href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'Delete this draft?\');">Delete</a>'
				. '</p></div>';
		}
		echo '<style>';   // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
		?>
		</style><script>
		(function ($) {
			$(document).on('click', '.gfv-copy', function () {
				var ta = $('#tsa-' + $(this).data('target')).first()[0];
				if (!ta) return;
				ta.select();
				try { document.execCommand('copy'); } catch (e) {}
				$(this).text('Copied');
			});
		})(jQuery);
		</script>
		<?php
	}
}

Gabochie_CC_Funding::init();