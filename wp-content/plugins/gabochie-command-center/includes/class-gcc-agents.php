<?php
/**
 * Gabochie Command Center — crews & agents registry.
 *
 * The operating model: four crews (Marketing, Sales, After-Sales, RevenueOps)
 * own the funnel from social strategy to the dollar. Every agent has a tool
 * surface, a human gate, and a live-derived state colour so the operator sees
 * exactly who is on-mission, who is blocked, and why.
 *
 * @package Gabochie_Command_Center
 */

defined( 'ABSPATH' ) || exit;

/**
 * Crews + agents registry with live-aware states.
 */
class Gabochie_CC_Agents {

	/**
	 * Crew definitions.
	 *
	 * @return array
	 */
	public static function crews() {
		return array(
			'marketing' => array(
				'name' => 'Marketing Crew',
				'goal' => 'Fill the top of the funnel (target ~900 leads/mo).',
			),
			'sales' => array(
				'name' => 'Sales Crew',
				'goal' => 'Qualify fast and close ₵399/₵1,299 + high-ticket (target ~18 paid/mo, 2 engagements).',
			),
			'aftersales' => array(
				'name' => 'After-Sales Crew',
				'goal' => 'Onboard, retain and uptick — the LTV engine behind MRR.',
			),
			'revops' => array(
				'name' => 'RevenueOps Crew',
				'goal' => 'KPI truth, pipeline audits, payments rails and orchestration.',
			),
		);
	}

	/**
	 * Agent registry (id, crew, name, role, tools, gate).
	 *
	 * @return array
	 */
	public static function agents() {
		return array(
			/* ------------------------------------------------------- Marketing */
			array(
				'id'    => 'social_strategist',
				'crew'  => 'marketing',
				'name'  => 'Social Strategist',
				'role'  => 'Calendar + IG/FB/TikTok/WhatsApp flywheel, UTM-tagged to funnels',
				'tools' => array( 'content calendar', 'WhatsApp outreach', 'UTM capture → Lead Hub' ),
				'gate'  => 'Approve every post',
			),
			array(
				'id'    => 'seo_agent',
				'crew'  => 'marketing',
				'name'  => 'SEO Agent',
				'role'  => 'Money-cluster content via rank-gap → content pipeline → publish',
				'tools' => array( 'gabochie-rank-gap', 'gabochie-content-pipeline', 'authority-builder' ),
				'gate'  => 'Approve draft before publish',
			),
			array(
				'id'    => 'nurture_agent',
				'crew'  => 'marketing',
				'name'  => 'Nurture Agent',
				'role'  => 'FluentCRM sequences: thesis → checklist → readiness score → level attempt',
				'tools' => array( 'FluentCRM', 'Brevo (gabochie-email)', 'Lead Hub tags' ),
				'gate'  => 'No gate — notify-only until sequences approved',
			),
			array(
				'id'    => 'paid_agent',
				'crew'  => 'marketing',
				'name'  => 'Paid Agent',
				'role'  => 'Google AdWords top-up on building-costs Ghana terms (after conversion proven)',
				'tools' => array( 'Google Ads', 'CAC/LTV model' ),
				'gate'  => 'Approve every campaign + budget',
			),

			/* ---------------------------------------------------------- Sales */
			array(
				'id'    => 'sdr',
				'crew'  => 'sales',
				'name'  => 'SDR',
				'role'  => 'Score inbound (journey/blocker/budget/readiness), draft first touch',
				'tools' => array( 'Lead Hub data', 'readiness score', 'GhBC draft' ),
				'gate'  => 'Soft — notify only',
			),
			array(
				'id'    => 'closer',
				'crew'  => 'sales',
				'name'  => 'Closer',
				'role'  => 'Feasibility/design proposals + quotes · high-ticket engagements',
				'tools' => array( 'GhBC', 'project intake', 'Deliverable templates' ),
				'gate'  => 'Approve every quote & payout',
			),
			array(
				'id'    => 'membership_converter',
				'crew'  => 'sales',
				'name'  => 'Membership Converter',
				'role'  => '8-dimension score + thesis readers → Active Client ₵399',
				'tools' => array( 'PMPro funnel', 'FluentCRM', 'Readiness quiz' ),
				'gate'  => 'Notify on attempts',
			),

			/* ------------------------------------------------------ After-sales */
			array(
				'id'    => 'cs_support',
				'crew'  => 'aftersales',
				'name'  => 'CS Support',
				'role'  => 'Community + voice + email triage, self-serve answers',
				'tools' => array( 'gabochie-community', 'voice agent', 'knowledge base' ),
				'gate'  => 'Escalation to human',
			),
			array(
				'id'    => 'retention',
				'crew'  => 'aftersales',
				'name'  => 'Success & Retention',
				'role'  => 'Onboarding checklist, check-ins, churn-risk, win-back',
				'tools' => array( 'FluentCRM', 'PMPro start/end dates', 'order history' ),
				'gate'  => 'Escalation on at-risk clients',
			),
			array(
				'id'    => 'upsell',
				'crew'  => 'aftersales',
				'name'  => 'Upsell Agent',
				'role'  => 'Active Client → Premium (₵1,299) → high-ticket engagement',
				'tools' => array( 'membership level mix', 'engagement catalogue', 'GhBC proposal' ),
				'gate'  => 'Approve every upsell offer',
			),

			/* -------------------------------------------------------- RevenueOps */
			array(
				'id'    => 'revenue_controller',
				'crew'  => 'revops',
				'name'  => 'Revenue Controller',
				'role'  => 'MRR, signups, churn, LTV, CAC vs revenue-model targets · daily ops brief',
				'tools' => array( 'Gabochie_CC_Funnel', 'ops digest email', 'KPI board' ),
				'gate'  => 'Money movement',
			),
			array(
				'id'    => 'payments_agent',
				'crew'  => 'revops',
				'name'  => 'Payments Agent',
				'role'  => 'Gateway state, failed renewals, payout rails (Moolre/ExpressPay/Paystack)',
				'tools' => array( 'PMPro gateway', 'WooCommerce', 'gateway logs' ),
				'gate'  => 'Money movement',
			),
			array(
				'id'    => 'pipeline_auditor',
				'crew'  => 'revops',
				'name'  => 'Pipeline Auditor',
				'role'  => 'Hop-rate leaks, anomaly alerts (e.g. source spike / drop)',
				'tools' => array( 'Funnel rows', 'Lead Hub stats', 'anomaly thresholds' ),
				'gate'  => 'Alert only',
			),
			array(
				'id'    => 'orchestrator',
				'crew'  => 'revops',
				'name'  => 'Orchestrator',
				'role'  => 'Event bus: wakes the right agent per trigger, owns escalation + gates',
				'tools' => array( 'gabochie/lead/captured', 'pmpro', 'woocommerce', 'task log' ),
				'gate'  => 'Exceptions & money',
			),
		);
	}

	/**
	 * State (colour + label + note) for one agent id.
	 *
	 * @param string $id Agent id.
	 * @return array
	 */
	public static function state_for( $id ) {
		$pay = Gabochie_Command_Center::payments_state();
		$ai  = Gabochie_Command_Center::ai_state();
		$kpi = Gabochie_CC_Funnel::metrics();
		$ok  = array( '#46b450', 'On mission', '' );
		$sb  = array( '#8c8f94', 'Standby', 'Waiting for activation / credentials.' );
		$blk = array( '#d63638', 'Blocked', 'A hard dependency is missing.' );

		$is_active = static function ( $slug ) {
			$active = (array) get_option( 'active_plugins', array() );
			foreach ( $active as $plugin ) {
				if ( false !== stripos( $plugin, $slug ) ) {
					return true;
				}
			}
			return false;
		};

		switch ( $id ) {
			case 'social_strategist':
			case 'paid_agent':
				return $sb;

			case 'seo_agent':
				return $is_active( 'gabochie-content-pipeline' ) && $is_active( 'gabochie-rank-gap' )
					? $ok
					: array( '#dba617', 'Setup', 'Content/SEO plugins not both active.' );

			case 'nurture_agent':
				return $is_active( 'fluent-crm' ) && ! empty( get_option( 'gabochie_email_api_key', '' ) )
					? $ok
					: array( '#dba617', 'Setup', 'FluentCRM active but Brevo key missing.' );

			case 'sdr':
			case 'membership_converter':
				return (int) $kpi['leads_total'] > 0 ? $ok : $sb;

			case 'closer':
				if ( 'sandbox' === $pay['expresspay_env'] || 'sandbox' === $pay['pmpro_env'] || empty( $pay['pmpro_gateway'] ) ) {
					return array( '#dba617', 'Ready (manual)', 'High-ticket can be invoiced manually — recurring needs live gateway.' );
				}
				return $ok;

			case 'cs_support':
				return ( $is_active( 'gabochie-community' ) || $is_active( 'gabochie-voice-agent' ) )
					? $ok
					: array( '#dba617', 'Setup', 'Enable community or voice surfaces.' );

			case 'retention':
				return (int) $kpi['members_paid'] > 0 ? $ok : $sb;

			case 'upsell':
				return (int) $kpi['members_paid'] >= 3 ? $ok : $sb;

			case 'revenue_controller':
			case 'pipeline_auditor':
				return $ok;

			case 'payments_agent':
				if ( 'sandbox' === $pay['expresspay_env'] || 'sandbox' === $pay['pmpro_env'] || empty( $pay['pmpro_gateway'] ) ) {
					return array( '#d63638', 'Blocked', 'Gateway creds not live — checkout cannot charge.' );
				}
				return $ok;

			case 'orchestrator':
				return $is_active( 'fluent-crm' ) ? $ok : array( '#dba617', 'Degraded', 'FluentCRM inactive — events buffered in backlog.' );

			default:
				return $ai['valid'] ? $ok : $blk;
		}
	}

	/**
	 * HTML: crew headers + agent cards, one grid per crew.
	 *
	 * @return string
	 */
	public static function crews_html() {
		$crews = self::crews();
		$out   = '';
		foreach ( $crews as $key => $crew ) {
			$out .= '<h3>' . esc_html( $crew['name'] ) . ' <span style="font-weight:400;color:#8c8f94;font-size:12px;">' . esc_html( $crew['goal'] ) . '</span></h3>';
			$out .= '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;">';
			foreach ( self::agents() as $agent ) {
				if ( $agent['crew'] !== $key ) {
					continue;
				}
				list( $color, $label, $note ) = self::state_for( $agent['id'] );
				$out .= '<div style="border:1px solid #c3c4c7;border-top:4px solid ' . $color . ';background:#fff;border-radius:6px;padding:14px;">'
					. '<div style="display:flex;align-items:center;gap:10px;"><div style="flex:1;"><strong style="font-size:15px;">' . esc_html( $agent['name'] ) . '</strong>'
					. '<div style="color:#50575e;font-size:12px;">' . esc_html( $agent['role'] ) . '</div></div>'
					. '<span style="background:' . $color . ';color:#fff;font-size:10px;font-weight:600;text-transform:uppercase;padding:2px 8px;border-radius:99px;white-space:nowrap;">' . esc_html( $label ) . '</span></div>'
					. '<p style="margin:10px 0 4px;color:#3c434a;font-size:13px;">' . esc_html( implode( ' · ', $agent['tools'] ) ) . '</p>'
					. '<div style="font-size:11px;color:#8c8f94;">Human gate: ' . esc_html( $agent['gate'] ) . '</div>'
					. ( '' !== $note ? '<div style="font-size:11px;color:#d63638;margin-top:4px;">' . esc_html( $note ) . '</div>' : '' )
					. '</div>';
			}
			$out .= '</div>';
		}
		return $out;
	}
}