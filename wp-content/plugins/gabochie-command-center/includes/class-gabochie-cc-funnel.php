<?php
/**
 * Gabochie Command Center — funnel & revenue engine.
 *
 * Computes the live funnel (Captured → Qualified → Engaged → Enrolled →
 * Paid → Retained → Won) from the Gabochie CRM table + PMPro + WooCommerce
 * data, snapshots KPIs, and compares every stage to the GH₵1M/yr revenue-model
 * targets so the agents (and the operator) always know the gap to the number.
 *
 * @package Gabochie_Command_Center
 */

defined( 'ABSPATH' ) || exit;

/**
 * Funnel + KPI computations for the command center.
 */
class Gabochie_CC_Funnel {

	/** @var string */
	const OPT_KPI = 'gabochie_cc_kpis';

	/** @var int Freshness window in seconds before a recompute on read. */
	const SNAPSHOT_TTL = 4 * HOUR_IN_SECONDS;

	/**
	 * GH₵1M/yr planning targets (rev model, 2026-08-30).
	 *
	 * @return array
	 */
	public static function targets() {
		return array(
			'leads_per_month'       => 900,
			'paid_per_month'        => 18,
			'mrr'                   => 83333.0, // ₵1M ÷ 12.
			'high_ticket_per_month' => 2,
			'lead_to_paid'          => 0.02,    // 2 % baseline conversion.
		);
	}

	/**
	 * Shopping-basket getter.
	 *
	 * @param array  $arr Array to read from.
	 * @param string $key Key to read.
	 * @param mixed  $def Default when missing.
	 * @return mixed
	 */
	public static function val( $arr, $key, $def = 0 ) {
		return isset( $arr[ $key ] ) ? $arr[ $key ] : $def;
	}

	/**
	 * Does a table exist?
	 *
	 * @param string $table Full table name.
	 * @return bool
	 */
	public static function table_exists( $table ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		return null !== $found;
	}

	/**
	 * Live funnel + revenue KPI snapshot.
	 *
	 * @param bool $fresh Force a recompute (skips snapshot cache).
	 * @return array
	 */
	public static function metrics( $fresh = false ) {
		global $wpdb;

		$snap  = get_option( self::OPT_KPI, array() );
		$stale = empty( $snap['time'] ) || ( time() - (int) self::val( $snap, 'time', 0 ) ) > self::SNAPSHOT_TTL;
		if ( ! $fresh && ! $stale && is_array( $snap ) && ! empty( $snap['leads_total'] ) ) {
			return $snap;
		}

		$p   = $wpdb->prefix;
		$kpi = array(
			'time'           => time(),
			'leads_total'    => 0,
			'leads_30d'      => 0,
			'qualified'      => 0,
			'engaged_30d'    => 0,
			'members_free'   => 0,
			'members_paid'   => 0,
			'mrr'            => 0.0,
			'revenue_total'  => 0.0,
			'paid_30d'       => 0,
			'orders_30d'     => 0,
			'retained_60d'   => 0,
			'liquid_mrr_day' => 0.0,
		);

		$crm = $p . 'gab_contacts';
		if ( self::table_exists( $crm ) ) {
			$kpi['leads_total'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(DISTINCT id) FROM %i WHERE status = %s AND (list_key LIKE %s OR source <> %s)',
					$crm,
					'subscribed',
					'leadhub-%',
					''
				)
			);

			$kpi['leads_30d'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(DISTINCT id) FROM %i WHERE status = %s AND (list_key LIKE %s OR source <> %s) AND created_at >= %s',
					$crm,
					'subscribed',
					'leadhub-%',
					'',
					gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
				)
			);

			$engaged = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(DISTINCT id) FROM %i WHERE status = %s AND (list_key LIKE %s OR source <> %s) AND last_engaged_at >= %s',
					$crm,
					'subscribed',
					'leadhub-%',
					'',
					gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
				)
			);
			$kpi['engaged_30d'] = is_numeric( $engaged ) ? (int) $engaged : 0;

			$kpi['qualified'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(DISTINCT id) FROM %i WHERE status = %s AND (phone <> %s OR source = %s OR tags LIKE %s)',
					$crm,
					'subscribed',
					'',
					'voice-agent',
					'%source:voice-agent%'
				)
			);
		}

		// PMPro memberships = Enrolled (Free Lead) / Paid (Active + Premium).
		$mu = $p . 'pmpro_memberships_users';
		if ( self::table_exists( $mu ) ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT membership_id, status, startdate, billing_amount FROM %i WHERE status = %s',
					$mu,
					'active'
				)
			);

			$paid_levels = array( 2, 3 );
			if ( is_array( $rows ) ) {
				$start_30d = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
				$start_60d = gmdate( 'Y-m-d H:i:s', strtotime( '-60 days' ) );
				foreach ( $rows as $row ) {
					$level = (int) $row->membership_id;
					if ( in_array( $level, $paid_levels, true ) ) {
						++$kpi['members_paid'];
						$kpi['mrr'] += (float) $row->billing_amount;
						if ( strtotime( $row->startdate ) > strtotime( $start_30d ) ) {
							++$kpi['paid_30d'];
						}
						if ( strtotime( $row->startdate ) < strtotime( $start_60d ) ) {
							++$kpi['retained_60d'];
						}
					} else {
						++$kpi['members_free'];
					}
				}
			}
		}

		// Revenue to date = PMPro completed orders (success) sum.
		$mo = $p . 'pmpro_membership_orders';
		if ( self::table_exists( $mo ) ) {
			$total                = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT SUM(total) FROM %i WHERE status = %s AND total > 0',
					$mo,
					'success'
				)
			);
			$kpi['revenue_total'] = (float) $total;

			$recent            = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE status = %s AND total > 0 AND timestamp >= %s',
					$mo,
					'success',
					gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
				)
			);
			$kpi['orders_30d'] = (int) $recent;
		}

		// WooCommerce completed orders (HPOS first, legacy fallback).
		$woo = $p . 'wc_orders';
		if ( self::table_exists( $woo ) ) {
			$woo_total             = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COALESCE(SUM(total_amount),0) FROM %i WHERE status IN (%s,%s)',
					$woo,
					'wc-completed',
					'wc-processing'
				)
			);
			$kpi['revenue_total'] += (float) $woo_total;
		}

		$kpi['liquid_mrr_day'] = round( $kpi['mrr'] / 30, 2 );

		update_option( self::OPT_KPI, $kpi, false );
		return $kpi;
	}

	/**
	 * Conversion rate between two counts.
	 *
	 * @param int  $from Denominator.
	 * @param int  $to   Numerator.
	 * @return float 0–100.
	 */
	public static function rate( $from, $to ) {
		if ( ! $from ) {
			return 0.0;
		}
		return round( 100 * $to / $from, 2 );
	}

	/**
	 * Funnel stages with conversions, for the board.
	 *
	 * @param array $kpi Computed metrics.
	 * @return array
	 */
	public static function funnel_rows( $kpi ) {
		$t            = self::targets();
		$leads_annual = (int) $kpi['leads_30d'] * 12;
		$paid_annual  = (int) $kpi['paid_30d'] * 12;
		return array(
			array(
				'stage' => 'Captured',
				'value' => (int) $kpi['leads_total'],
				'note'  => (int) $kpi['leads_30d'] . ' in last 30d · target ~' . $t['leads_per_month'] . '/mo',
				'pct'   => '',
			),
			array(
				'stage' => 'Qualified',
				'value' => (int) $kpi['qualified'],
				'note'  => 'phone or voice source captured',
				'pct'   => self::rate( (int) $kpi['leads_total'], (int) $kpi['qualified'] ) . '% of captured',
			),
			array(
				'stage' => 'Engaged',
				'value' => (int) $kpi['engaged_30d'],
				'note'  => 'opened or clicked an email',
				'pct'   => self::rate( (int) $kpi['leads_30d'], (int) $kpi['engaged_30d'] ) . '% of 30d',
			),
			array(
				'stage' => 'Enrolled (Free)',
				'value' => (int) $kpi['members_free'],
				'note'  => 'Free Lead memberships',
				'pct'   => '',
			),
			array(
				'stage' => 'Paid (Active+Premium)',
				'value' => (int) $kpi['members_paid'],
				'note'  => (int) $kpi['paid_30d'] . ' new in last 30d · target ~' . $t['paid_per_month'] . '/mo',
				'pct'   => self::rate( (int) $kpi['leads_30d'], (int) $kpi['paid_30d'] ) . '% of 30d leads',
			),
			array(
				'stage' => 'Retained 60d+',
				'value' => (int) $kpi['retained_60d'],
				'note'  => 'paid memberships older than 60 days',
				'pct'   => self::rate( (int) $kpi['members_paid'], (int) $kpi['retained_60d'] ) . '% of paid',
			),
			array(
				'stage' => 'Won (revenue)',
				'value' => '₵' . number_format( (float) $kpi['revenue_total'] ),
				'note'  => 'MRR ₵' . number_format( (float) $kpi['mrr'] ) . ' · ' . (int) $kpi['orders_30d'] . ' paid orders/30d',
				'pct'   => '',
			),
		);
	}

	/**
	 * KPI card set (top counters).
	 *
	 * @param array $kpi Computed metrics.
	 * @return string
	 */
	public static function kpi_cards_html( $kpi ) {
		$t       = self::targets();
		$gap_mrr = (float) $t['mrr'] - (float) $kpi['mrr'];
		$cards   = array(
			array(
				't' => 'MRR',
				'v' => '₵' . number_format( (float) $kpi['mrr'] ),
				's' => $gap_mrr > 0 ? '₵' . number_format( $gap_mrr ) . ' short of ₵' . number_format( $t['mrr'] ) . '/mo' : 'At / above monthly target',
				'c' => $gap_mrr > 0 ? '#dba617' : '#0a7d32',
			),
			array(
				't' => 'Active clients',
				'v' => (string) (int) $kpi['members_paid'],
				's' => 'target ≈ ' . round( $t['mrr'] / 399 ) . ' Active-only (or blend)',
				'c' => (int) $kpi['members_paid'] > 0 ? '#0a7d32' : '#8c8f94',
			),
			array(
				't' => 'Leads / 30d',
				'v' => (string) (int) $kpi['leads_30d'],
				's' => 'target ' . $t['leads_per_month'] . '/mo · annualised ' . ( (int) $kpi['leads_30d'] * 12 ),
				'c' => (int) $kpi['leads_30d'] >= 100 ? '#0a7d32' : '#dba617',
			),
			array(
				't' => 'Revenue to date',
				'v' => '₵' . number_format( (float) $kpi['revenue_total'] ),
				's' => '₵' . number_format( (float) $kpi['liquid_mrr_day'] ) . '/day liquidating MRR',
				'c' => '#2271b1',
			),
		);
		$out     = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px;">';
		foreach ( $cards as $c ) {
			$out .= '<div style="border:1px solid #c3c4c7;border-top:4px solid ' . $c['c'] . ';background:#fff;border-radius:6px;padding:14px;">'
				. '<div style="color:#8c8f94;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">' . esc_html( $c['t'] ) . '</div>'
				. '<div style="font-size:22px;font-weight:700;margin:4px 0;">' . esc_html( $c['v'] ) . '</div>'
				. '<div style="font-weight:600;color:' . $c['c'] . ';font-size:12px;">' . esc_html( $c['s'] ) . '</div></div>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * Funnel table (stages + conversions).
	 *
	 * @param array $kpi Computed metrics.
	 * @return string
	 */
	public static function funnel_table_html( $kpi ) {
		$out = '<table class="widefat striped" style="max-width:860px;"><thead><tr>'
			. '<th>Stage</th><th>Now</th><th>Conversion</th><th>Context</th></tr></thead><tbody>';
		foreach ( self::funnel_rows( $kpi ) as $row ) {
			$out .= '<tr><td><strong>' . esc_html( $row['stage'] ) . '</strong></td>'
				. '<td>' . esc_html( $row['value'] ) . '</td>'
				. '<td>' . esc_html( $row['pct'] ) . '</td>'
				. '<td style="color:#50575e;">' . esc_html( $row['note'] ) . '</td></tr>';
		}
		$out .= '</tbody></table>';
		return $out;
	}

	/**
	 * Gap analysis → ranked action items (auditors feed).
	 *
	 * @param array $kpi Computed metrics.
	 * @return array
	 */
	public static function actions( $kpi ) {
		$items = array();
		$pay   = Gabochie_Command_Center::payments_state();
		if ( 'sandbox' === $pay['expresspay_env'] || 'sandbox' === $pay['pmpro_env'] || empty( $pay['pmpro_gateway'] ) ) {
			$items[] = array(
				'severity' => 'error',
				'ice'      => 90,
				'title'    => 'Payments are not live — the machine cannot charge yet',
				'detail'   => 'ExpressPay ' . $pay['expresspay_env'] . ' / PMPro ' . $pay['pmpro_env'] . ' · gateway ' . ( $pay['pmpro_gateway'] ? $pay['pmpro_gateway'] : 'unset' ) . '. Configure live creds, then the Sales crew can close ₵399/₵1,299 + high-ticket.',
			);
		}

		$paid_annual = (int) $kpi['paid_30d'] * 12;
		if ( (int) $kpi['members_paid'] > 0 && (int) $kpi['paid_30d'] > 0 ) {
			$conv = self::rate( (int) $kpi['leads_30d'], (int) $kpi['paid_30d'] );
			$t    = self::targets();
			if ( $conv < $t['lead_to_paid'] * 100 ) {
				$items[] = array(
					'severity' => 'warning',
					'ice'      => 75,
					'title'    => 'Lead → paid conversion below target (' . $conv . '% vs ' . ( $t['lead_to_paid'] * 100 ) . '%)',
					'detail'   => 'Nurture + SDR qualify faster, or adjust the funnel. Each extra point of conversion ≈ ₵' . number_format( (int) $kpi['leads_30d'] * 0.01 * $t['mrr'] ) . '/mo.',
				);
			}
		} elseif ( (int) $kpi['leads_30d'] >= 30 && 0 === (int) $kpi['paid_30d'] ) {
			$items[] = array(
				'severity' => 'warning',
				'ice'      => 80,
				'title'    => 'Volume is arriving but nothing converts to paid',
				'detail'   => 'Check checkout path + gateway state; SDR should be reaching out within the hour of capture.',
			);
		}

		if ( (int) $kpi['leads_30d'] < 30 && 0 === (int) $kpi['leads_total'] ) {
			$items[] = array(
				'severity' => 'warning',
				'ice'      => 85,
				'title'    => 'Acquisition is cold — 0 leads captured',
				'detail'   => 'Marketing crew has no volume to work with. Social calendar + SEO money clusters are the priority.',
			);
		}

		$ai = Gabochie_Command_Center::ai_state();
		if ( ! $ai['valid'] ) {
			$items[] = array(
				'severity' => 'error',
				'ice'      => 70,
				'title'    => 'AI brain invalid — agents cannot draft intelligently',
				'detail'   => 'Provider "' . $ai['provider'] . '" is missing an API key. Add it in GhBC Settings → Test Connection.',
			);
		}

		usort(
			$items,
			static function ( $a, $b ) {
				return (int) $b['ice'] <=> (int) $a['ice'];
			}
		);

		return $items;
	}

	/**
	 * Ranked action list HTML.
	 *
	 * @param array $kpi Computed metrics.
	 * @return string
	 */
	public static function actions_html( $kpi ) {
		$sev  = array(
			'error'   => '#d63638',
			'warning' => '#dba617',
			'info'    => '#2271b1',
		);
		$out  = '<div style="max-width:860px;">';
		$list = self::actions( $kpi );
		if ( empty( $list ) ) {
			$out .= '<div class="notice notice-success"><p><strong>On target.</strong> No commanding actions right now.</p></div>';
		}
		foreach ( $list as $a ) {
			$c    = isset( $sev[ $a['severity'] ] ) ? $sev[ $a['severity'] ] : $sev['info'];
			$out .= '<div style="display:flex;gap:10px;padding:10px 12px;border:1px solid #c3c4c7;border-left:4px solid ' . $c . ';background:#fff;margin-bottom:8px;border-radius:4px;align-items:flex-start;">'
				. '<strong style="color:' . $c . ';white-space:nowrap;min-width:64px;">ICE ' . (int) $a['ice'] . '</strong>'
				. '<div><strong>' . esc_html( $a['title'] ) . '</strong><div style="color:#50575e;margin-top:2px;">' . esc_html( $a['detail'] ) . '</div></div></div>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * Revenue & Funnel dashboard tab.
	 */
	public static function render_revenue_tab() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$kpi = self::metrics();
		echo '<h2 style="display:flex;align-items:center;gap:12px;">Revenue &amp; Funnel · target GH₵1M/yr';
		echo '<button class="button button-small" onclick="location.href=\'' . esc_url( admin_url( 'admin.php?page=gabochie-command-center&tab=revenue&recalc=1' ) ) . '\'">Recompute</button></h2>';
		echo '<p class="description">Live counts from the Gabochie CRM (gab_contacts), PMPro and WooCommerce, compared to the revenue-model targets. Recompute forces a fresh snapshot.</p>';
		echo self::kpi_cards_html( $kpi ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<h2>Funnel</h2>';
		echo self::funnel_table_html( $kpi ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<h2>Commanding actions</h2>';
		echo self::actions_html( $kpi ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Handle ?recalc=1 force recompute.
	 */
	public static function maybe_recalc() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only freshness toggle.
		if ( isset( $_GET['recalc'] ) && '1' === $_GET['recalc'] ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::metrics( true );
		}
	}
}
