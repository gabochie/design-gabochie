<?php
if (!defined('ABSPATH')) exit;

class Gabochie_Revenue {

    public static function render_page() {
        if (!current_user_can('manage_options')) return;

        $today = current_time('d');
        $month = current_time('n');
        $day_of_week = current_time('w');
        $week_num = ceil($today / 7);
        $days_in_month = date('t', strtotime(current_time('Y-m-01')));
        $day_of_year = date('z', strtotime(current_time('Y-m-d'))) + 1;

        $revenue_data = get_option('gabochie_revenue_log', array());
        $total_revenue = array_sum(array_column($revenue_data, 'amount'));
        $mtd_revenue = self::get_mtd_revenue($revenue_data);
        $pipeline = get_option('gabochie_revenue_pipeline', array());

        ?>
        <div class="wrap gabochie-hub-wrap">
            <div class="gh-header">
                <div class="gh-brand">
                    <svg width="40" height="40" viewBox="0 0 52 52" fill="none">
                        <polygon points="26,3 49,15 49,37 26,49 3,37 3,15" fill="none" stroke="#C9A96E" stroke-width="1.5"/>
                        <text x="26" y="28" text-anchor="middle" font-family="serif" font-size="16" font-weight="700" fill="#0D5C52">GD</text>
                    </svg>
                    <div>
                        <h1>Mega Revenue Strategy</h1>
                        <p>Aug — Dec 2026 | 5-Month Sprint to First $50K</p>
                    </div>
                </div>
            </div>

            <!-- Revenue Scoreboard -->
            <div class="gh-grid">
                <div class="gh-card">
                    <div class="gh-card-icon" style="background:#E8F4F2;color:#0D5C52">&#128176;</div>
                    <div class="gh-card-body">
                        <div class="gh-card-label">Total Revenue</div>
                        <div class="gh-card-value">$<?php echo number_format($total_revenue); ?></div>
                        <div class="gh-card-sub">Since Aug 1, 2026</div>
                    </div>
                </div>
                <div class="gh-card">
                    <div class="gh-card-icon" style="background:#FBF5E8;color:#C9A96E">&#128200;</div>
                    <div class="gh-card-body">
                        <div class="gh-card-label">This Month</div>
                        <div class="gh-card-value">$<?php echo number_format($mtd_revenue); ?></div>
                        <div class="gh-card-sub">Target: $<?php echo number_format(self::get_monthly_target($month)); ?></div>
                    </div>
                </div>
                <div class="gh-card">
                    <div class="gh-card-icon" style="background:#E8F4F2;color:#0D5C52">&#127919;</div>
                    <div class="gh-card-body">
                        <div class="gh-card-label">Pipeline Value</div>
                        <div class="gh-card-value">$<?php echo number_format(self::get_pipeline_value($pipeline)); ?></div>
                        <div class="gh-card-sub"><?php echo count($pipeline); ?> active deals</div>
                    </div>
                </div>
                <div class="gh-card">
                    <div class="gh-card-icon" style="background:#FBF5E8;color:#C9A96E">&#9200;</div>
                    <div class="gh-card-body">
                        <div class="gh-card-label">Days Remaining</div>
                        <div class="gh-card-value"><?php echo self::days_remaining(); ?></div>
                        <div class="gh-card-sub">Until Dec 31, 2026</div>
                    </div>
                </div>
            </div>

            <!-- STRATEGIC OVERVIEW -->
            <div class="gh-section">
                <h2>&#128161; B2B vs B2C Strategy</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div style="background:#f0faf8;border-left:4px solid #0D5C52;padding:16px;border-radius:4px;">
                        <h3 style="margin:0 0 8px;color:#0D5C52;">B2B — Revenue Engine (70% focus)</h3>
                        <p style="margin:0 0 8px;font-size:13px;color:#555;">Recurring revenue, higher tickets, predictable cash flow.</p>
                        <ul style="margin:0;padding-left:18px;font-size:13px;color:#333;">
                            <li><strong>Contractors</strong> (2,500+ in Ghana) — Need designs, BOQ, permits. Recurring.</li>
                            <li><strong>Real estate developers</strong> — Ongoing design + PM retainers.</li>
                            <li><strong>Material suppliers</strong> — Need leads/data. Referral commissions.</li>
                            <li><strong>Banks & mortgagecos</strong> — Need cost estimates. API/white-label.</li>
                            <li><strong>Government/NGOs</strong> — 1.8M housing deficit. Tenders.</li>
                        </ul>
                    </div>
                    <div style="background:#fffbf0;border-left:4px solid #C9A96E;padding:16px;border-radius:4px;">
                        <h3 style="margin:0 0 8px;color:#C9A96E;">B2C — Marketing Funnel (30% focus)</h3>
                        <p style="margin:0 0 8px;font-size:13px;color:#555;">Brand building, case studies, lead gen for B2B.</p>
                        <ul style="margin:0;padding-left:18px;font-size:13px;color:#333;">
                            <li><strong>Diaspora building in Ghana</strong> — $7.8B remittances, Q4 peaks.</li>
                            <li><strong>Local homeowners</strong> — Housing deficit drives demand.</li>
                            <li><strong>Real estate investors</strong> — Feasibility studies needed.</li>
                            <li><strong>First-time builders</strong> — Fear + confusion = willingness to pay.</li>
                        </ul>
                    </div>
                </div>
                <div style="background:#f8f8f8;padding:12px 16px;border-radius:4px;margin-top:12px;font-size:13px;">
                    <strong>Verdict:</strong> Focus on B2B for revenue (contractors + developers), use B2C as marketing engine. Every B2C client = case study + testimonial + referral to B2B network. The diaspora Q4 money surge (Oct-Dec) is your biggest B2C opportunity. Contractor partnerships are your biggest B2B opportunity.
                </div>
            </div>

            <!-- SEASONALITY CALENDAR -->
            <div class="gh-section">
                <h2>&#127777; Seasonality & Timing</h2>
                <table class="widefat gh-log-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Weather</th>
                            <th>Construction Activity</th>
                            <th>Diaspora Money Flow</th>
                            <th>Revenue Focus</th>
                            <th>Target</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="<?php echo $month == 8 ? 'background:#E8F4F2;font-weight:bold;' : ''; ?>">
                            <td><strong>AUG</strong></td>
                            <td>Minor rainy season ending</td>
                            <td>Slow — planning mode</td>
                            <td>Low — summer holidays</td>
                            <td>Build systems, partnerships, content</td>
                            <td>$1,500</td>
                        </tr>
                        <tr style="<?php echo $month == 9 ? 'background:#E8F4F2;font-weight:bold;' : ''; ?>">
                            <td><strong>SEP</strong></td>
                            <td>Minor rains (lighter)</td>
                            <td>Moderate — design work continues</td>
                            <td>Rising — back to work</td>
                            <td>First clients, AdSense, productized services</td>
                            <td>$5,000</td>
                        </tr>
                        <tr style="<?php echo $month == 10 ? 'background:#E8F4F2;font-weight:bold;' : ''; ?>">
                            <td><strong>OCT</strong></td>
                            <td>Rains tapering</td>
                            <td>Ramping up</td>
                            <td>High — Christmas planning</td>
                            <td>Diaspora outreach, contractor partnerships</td>
                            <td>$12,000</td>
                        </tr>
                        <tr style="<?php echo $month == 11 ? 'background:#E8F4F2;font-weight:bold;' : ''; ?>">
                            <td><strong>NOV</strong></td>
                            <td>Dry season begins</td>
                            <td>Peak — building starts</td>
                            <td>Very High — pre-Christmas</td>
                            <td>Full sales mode, enterprise deals</td>
                            <td>$20,000</td>
                        </tr>
                        <tr style="<?php echo $month == 12 ? 'background:#E8F4F2;font-weight:bold;' : ''; ?>">
                            <td><strong>DEC</strong></td>
                            <td>Dry — harmattan begins</td>
                            <td>Maximum activity</td>
                            <td>Peak — diaspora visiting, decisions</td>
                            <td>Close deals, upsell, retain</td>
                            <td>$30,000</td>
                        </tr>
                    </tbody>
                </table>
                <div style="background:#fff3cd;padding:10px 14px;border-radius:4px;margin-top:10px;font-size:13px;">
                    <strong>&#9888;&#65039; Key Insight:</strong> Ghana remittances hit $7.8B in 2025 Q4 alone. Diaspora money flows peak Oct-Dec (Christmas). Most diaspora Ghanaians make building decisions when they visit in December. Your sales pipeline must be FULL by October to close in Nov-Dec. The window is NOW.
                </div>
            </div>

            <!-- REVENUE STREAMS -->
            <div class="gh-section">
                <h2>&#128176; Revenue Streams & Projections</h2>
                <table class="widefat gh-log-table">
                    <thead>
                        <tr>
                            <th>Stream</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Aug-Sep</th>
                            <th>Oct</th>
                            <th>Nov</th>
                            <th>Dec</th>
                            <th>5-Mo Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Design Services (B2C/Diaspora)</strong></td>
                            <td>One-time</td>
                            <td>$800-$5,000</td>
                            <td>$1,600</td>
                            <td>$5,000</td>
                            <td>$10,000</td>
                            <td>$15,000</td>
                            <td><strong>$31,600</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Contractor Design Packages (B2B)</strong></td>
                            <td>Per-project</td>
                            <td>$500-$2,000</td>
                            <td>$0</td>
                            <td>$2,000</td>
                            <td>$6,000</td>
                            <td>$8,000</td>
                            <td><strong>$16,000</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Project Management Retainer</strong></td>
                            <td>Monthly</td>
                            <td>$500-$1,500/mo</td>
                            <td>$0</td>
                            <td>$1,000</td>
                            <td>$2,000</td>
                            <td>$3,000</td>
                            <td><strong>$6,000</strong></td>
                        </tr>
                        <tr>
                            <td><strong>BuildCostGH Premium Reports</strong></td>
                            <td>One-time</td>
                            <td>$10-$50</td>
                            <td>$200</td>
                            <td>$500</td>
                            <td>$1,000</td>
                            <td>$1,500</td>
                            <td><strong>$3,200</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Google AdSense</strong></td>
                            <td>Recurring</td>
                            <td>$50-$500/mo</td>
                            <td>$50</td>
                            <td>$100</td>
                            <td>$200</td>
                            <td>$300</td>
                            <td><strong>$650</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Digital Products (Plans, Templates)</strong></td>
                            <td>Passive</td>
                            <td>$20-$200</td>
                            <td>$300</td>
                            <td>$800</td>
                            <td>$1,500</td>
                            <td>$2,000</td>
                            <td><strong>$4,600</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Membership Subscriptions</strong></td>
                            <td>Monthly</td>
                            <td>$10-$35/mo</td>
                            <td>$0</td>
                            <td>$100</td>
                            <td>$300</td>
                            <td>$500</td>
                            <td><strong>$900</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Material Supplier Referrals (B2B)</strong></td>
                            <td>Commission</td>
                            <td>3-8%</td>
                            <td>$0</td>
                            <td>$500</td>
                            <td>$1,500</td>
                            <td>$2,500</td>
                            <td><strong>$4,500</strong></td>
                        </tr>
                        <tr style="background:#E8F4F2;font-weight:bold;">
                            <td><strong>TOTAL</strong></td>
                            <td></td>
                            <td></td>
                            <td><strong>$2,150</strong></td>
                            <td><strong>$10,000</strong></td>
                            <td><strong>$22,500</strong></td>
                            <td><strong>$32,800</strong></td>
                            <td><strong style="color:#0D5C52;font-size:16px;">$67,450</strong></td>
                        </tr>
                    </tbody>
                </table>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px;">
                    <div style="background:#f8f8f8;padding:12px;border-radius:4px;text-align:center;">
                        <div style="font-size:11px;color:#666;text-transform:uppercase;">Conservative</div>
                        <div style="font-size:20px;font-weight:bold;color:#d63638;">$35,000</div>
                    </div>
                    <div style="background:#E8F4F2;padding:12px;border-radius:4px;text-align:center;border:2px solid #0D5C52;">
                        <div style="font-size:11px;color:#0D5C52;text-transform:uppercase;">Realistic Target</div>
                        <div style="font-size:20px;font-weight:bold;color:#0D5C52;">$67,450</div>
                    </div>
                    <div style="background:#FBF5E8;padding:12px;border-radius:4px;text-align:center;">
                        <div style="font-size:11px;color:#C9A96E;text-transform:uppercase;">Aggressive</div>
                        <div style="font-size:20px;font-weight:bold;color:#C9A96E;">$120,000</div>
                    </div>
                </div>
            </div>

            <!-- LEVERAGE FRAMEWORK -->
            <div class="gh-section">
                <h2>&#9878; The 5 Levers of Revenue</h2>
                <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;">
                    <div style="background:#E8F4F2;padding:14px;border-radius:4px;text-align:center;">
                        <div style="font-size:24px;">&#128170;</div>
                        <div style="font-size:12px;font-weight:bold;color:#0D5C52;margin:6px 0;">YOUR LABOR</div>
                        <div style="font-size:11px;color:#555;">Design, sales, strategy. 8-12 hrs/day. AI multiplies your output 10x.</div>
                        <div style="margin-top:8px;font-size:11px;color:#0D5C52;font-weight:bold;">$0 cost, 100% margin</div>
                    </div>
                    <div style="background:#fffbf0;padding:14px;border-radius:4px;text-align:center;">
                        <div style="font-size:24px;">&#128101;</div>
                        <div style="font-size:12px;font-weight:bold;color:#C9A96E;margin:6px 0;">OTHERS' LABOR</div>
                        <div style="font-size:11px;color:#555;">Hire VA ($200/mo), freelance draftsmen, outsourced renders.</div>
                        <div style="margin-top:8px;font-size:11px;color:#C9A96E;font-weight:bold;">$500-1,500/mo cost</div>
                    </div>
                    <div style="background:#f8f0fa;padding:14px;border-radius:4px;text-align:center;">
                        <div style="font-size:24px;">&#128176;</div>
                        <div style="font-size:12px;font-weight:bold;color:#7B2D8E;margin:6px 0;">OTHERS' MONEY</div>
                        <div style="font-size:11px;color:#555;">Client deposits fund operations. 50% upfront on all projects.</div>
                        <div style="margin-top:8px;font-size:11px;color:#7B2D8E;font-weight:bold;">0% cost, client-funded</div>
                    </div>
                    <div style="background:#f0f8ff;padding:14px;border-radius:4px;text-align:center;">
                        <div style="font-size:24px;">&#128218;</div>
                        <div style="font-size:12px;font-weight:bold;color:#1A5276;margin:6px 0;">KNOWLEDGE</div>
                        <div style="font-size:11px;color:#555;">Architecture expertise + AI skills. Content = authority = trust = sales.</div>
                        <div style="margin-top:8px;font-size:11px;color:#1A5276;font-weight:bold;">Monetize expertise directly</div>
                    </div>
                    <div style="background:#f0faf0;padding:14px;border-radius:4px;text-align:center;">
                        <div style="font-size:24px;">&#129302;</div>
                        <div style="font-size:12px;font-weight:bold;color:#0D5C52;margin:6px 0;">SYSTEMS</div>
                        <div style="font-size:11px;color:#555;">AI tools, n8n workflows, automated proposals, chatbot.</div>
                        <div style="margin-top:8px;font-size:11px;color:#0D5C52;font-weight:bold;">Scale without scaling headcount</div>
                    </div>
                </div>
            </div>

            <!-- DAILY ACTION PLAN -->
            <div class="gh-section">
                <h2>&#128197; Today's Action Plan — Day <?php echo $day_of_year; ?> of 365</h2>
                <?php $actions = self::get_daily_actions($month, $today, $day_of_week); ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <h3 style="margin:0 0 10px;color:#0D5C52;">&#127919; Revenue Actions (Do First)</h3>
                        <?php foreach ($actions['revenue'] as $i => $action): ?>
                        <div class="gh-rec-item" style="border-left:3px solid #d63638;margin-bottom:6px;">
                            <span class="gh-rec-icon">&#9312;&#803;<?php echo $i + 1; ?></span>
                            <div class="gh-rec-body">
                                <strong><?php echo esc_html($action['title']); ?></strong>
                                <small><?php echo esc_html($action['desc']); ?></small>
                            </div>
                            <span class="gh-rec-badge" style="background:<?php echo $action['urgent'] ? '#d63638' : '#C9A96E'; ?>;"><?php echo $action['est']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <h3 style="margin:0 0 10px;color:#C9A96E;">&#128736; System Building (Do Second)</h3>
                        <?php foreach ($actions['system'] as $i => $action): ?>
                        <div class="gh-rec-item" style="border-left:3px solid #C9A96E;margin-bottom:6px;">
                            <span class="gh-rec-icon">&#9313;&#803;<?php echo $i + 1; ?></span>
                            <div class="gh-rec-body">
                                <strong><?php echo esc_html($action['title']); ?></strong>
                                <small><?php echo esc_html($action['desc']); ?></small>
                            </div>
                            <span class="gh-rec-badge" style="background:#C9A96E;"><?php echo $action['est']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <h3 style="margin:0 0 10px;color:#0D5C52;">&#128214; Content & Marketing (Do Third)</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
                        <?php foreach ($actions['content'] as $action): ?>
                        <div style="background:#f8f8f8;padding:10px 12px;border-radius:4px;font-size:12px;">
                            <strong><?php echo esc_html($action['title']); ?></strong><br>
                            <span style="color:#666;"><?php echo esc_html($action['desc']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- LEADERSHIP RULES -->
            <div class="gh-section">
                <h2>&#128220; Revenue Rules</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div style="background:#fff3cd;padding:14px;border-radius:4px;">
                        <strong>&#128176; OPM Rule</strong>
                        <p style="font-size:12px;margin:6px 0 0;">Every project = 50% deposit upfront. Client funds the work. Never float client projects from your pocket. Deposits cover VA costs, tools, and renders.</p>
                    </div>
                    <div style="background:#d4edda;padding:14px;border-radius:4px;">
                        <strong>&#9201; Time Rule</strong>
                        <p style="font-size:12px;margin:6px 0 0;">Revenue-generating activities FIRST (8-10am). System building SECOND (10am-12pm). Content LAST (afternoon). Never flip this order.</p>
                    </div>
                    <div style="background:#d1ecf1;padding:14px;border-radius:4px;">
                        <strong>&#128640; Speed Rule</strong>
                        <p style="font-size:12px;margin:6px 0 0;">Concept designs in 48 hours (AI). Full plans in 7 days. Proposals in 2 hours. Speed is your unfair advantage over traditional firms.</p>
                    </div>
                    <div style="background:#f8d7da;padding:14px;border-radius:4px;">
                        <strong>&#128200; B2B Rule</strong>
                        <p style="font-size:12px;margin:6px 0 0;">1 contractor partnership = 5-10 projects/year. 1 developer retainer = $1,000-3,000/mo recurring. B2B > B2C for revenue stability.</p>
                    </div>
                    <div style="background:#e2d9f3;padding:14px;border-radius:4px;">
                        <strong>&#127760; Diaspora Rule</strong>
                        <p style="font-size:12px;margin:6px 0 0;">Diaspora clients pay in USD/GBP. Premium pricing. They need trust = content + case studies + testimonials. Invest in portfolio NOW.</p>
                    </div>
                    <div style="background:#d4edda;padding:14px;border-radius:4px;">
                        <strong>&#129302; AI Rule</strong>
                        <p style="font-size:12px;margin:6px 0 0;">AI does 80% of design work. You do 20% (client relations, creative direction, quality control). This means you can handle 3x more projects.</p>
                    </div>
                </div>
            </div>

            <!-- REVENUE TRACKER -->
            <div class="gh-section">
                <h2>&#128202; Revenue Tracker</h2>
                <table class="widefat gh-log-table">
                    <thead>
                        <tr><th>Date</th><th>Client / Source</th><th>Stream</th><th>Amount</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($revenue_data)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#999;padding:20px;">No revenue logged yet. Start earning!</td></tr>
                    <?php else: ?>
                        <?php foreach (array_reverse($revenue_data) as $entry): ?>
                        <tr>
                            <td><?php echo esc_html($entry['date']); ?></td>
                            <td><?php echo esc_html($entry['client']); ?></td>
                            <td><?php echo esc_html($entry['stream']); ?></td>
                            <td><strong>$<?php echo number_format($entry['amount']); ?></strong></td>
                            <td><span class="gh-badge gh-badge-<?php echo $entry['status'] === 'paid' ? 'success' : 'pending'; ?>"><?php echo esc_html($entry['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private static function get_monthly_target($month) {
        $targets = array(8 => 1500, 9 => 5000, 10 => 12000, 11 => 20000, 12 => 30000);
        return isset($targets[$month]) ? $targets[$month] : 0;
    }

    private static function days_remaining() {
        $end = strtotime('2026-12-31');
        $now = strtotime(current_time('Y-m-d'));
        return max(0, round(($end - $now) / 86400));
    }

    private static function get_mtd_revenue($data) {
        $month = current_time('Y-m');
        $total = 0;
        foreach ($data as $entry) {
            if (strpos($entry['date'], $month) === 0) {
                $total += $entry['amount'];
            }
        }
        return $total;
    }

    private static function get_pipeline_value($pipeline) {
        $total = 0;
        foreach ($pipeline as $deal) {
            $total += isset($deal['value']) ? $deal['value'] : 0;
        }
        return $total;
    }

    private static function get_daily_actions($month, $today, $day_of_week) {
        $actions = array(
            'revenue' => array(),
            'system' => array(),
            'content' => array(),
        );

        // MONTH-SPECIFIC REVENUE ACTIONS
        if ($month == 8) {
            // AUGUST: Foundation month
            $actions['revenue'] = array(
                array('title' => 'Contact 5 contractors on WhatsApp', 'desc' => 'Offer free design consultation. Target: Obuasi, Kumasi, Tema contractor groups.', 'est' => '30min', 'urgent' => true),
                array('title' => 'DM 10 diaspora architecture pages on Instagram', 'desc' => 'Ghanaian accounts in UK/US. Offer free BuildCostGH report as icebreaker.', 'est' => '20min', 'urgent' => true),
                array('title' => 'Post "How much does it cost to build in Ghana" on LinkedIn', 'desc' => 'Use pillar post content. Tag 3 construction companies. Include calculator link.', 'est' => '15min', 'urgent' => false),
                array('title' => 'Apply for Google AdSense', 'desc' => '41 posts, good SEO. You qualify. Apply today.', 'est' => '10min', 'urgent' => true),
                array('title' => 'Create Fiverr/Upwork profile for AI design services', 'desc' => 'List: "AI-Powered 48-Hour Concept Design for Ghana Homes". Price: $200-500.', 'est' => '45min', 'urgent' => false),
            );
            $actions['system'] = array(
                array('title' => 'Set up Paystack payment links', 'desc' => 'Create payment links for each service tier. $800, $2,500, $5,000.', 'est' => '30min', 'urgent' => true),
                array('title' => 'Create proposal template (Canva/GDocs)', 'desc' => 'Standard proposal: problem, solution, pricing, timeline, CTA.', 'est' => '1hr', 'urgent' => false),
                array('title' => 'Build contractor partnership one-pager', 'desc' => 'PDF explaining partnership: you design, they build. Revenue split.', 'est' => '45min', 'urgent' => false),
            );
            $actions['content'] = array(
                array('title' => 'Write 1 blog post', 'desc' => 'Target long-tail keyword for SEO traffic.'),
                array('title' => 'Post 1 Instagram Reel', 'desc' => 'AI design timelapse or before/after.'),
                array('title' => 'Share 1 LinkedIn post', 'desc' => 'Industry insight or project update.'),
            );
        } elseif ($month == 9) {
            // SEPTEMBER: First revenue month
            $actions['revenue'] = array(
                array('title' => 'Follow up with all August leads', 'desc' => 'Every contractor, every DM, every inquiry. Close or disqualify.', 'est' => '30min', 'urgent' => true),
                array('title' => 'Launch "House Design in 7 Days" productized service', 'desc' => 'Fixed price, fixed scope, fixed timeline. Market on all channels.', 'est' => '1hr', 'urgent' => true),
                array('title' => 'Pitch 3 real estate developers for retainer', 'desc' => 'Offer: monthly design support + AI cost estimates. $500-1,500/mo.', 'est' => '45min', 'urgent' => true),
                array('title' => 'Create Paystack payment page for premium reports', 'desc' => '$10-50 per BuildCostGH PDF report. Automated delivery.', 'est' => '30min', 'urgent' => false),
                array('title' => 'Contact 3 building material shops for partnership', 'desc' => 'Refer clients to them, they refer clients to you. Commission model.', 'est' => '20min', 'urgent' => false),
            );
            $actions['system'] = array(
                array('title' => 'Set up email welcome sequence (5 emails)', 'desc' => 'Capture leads from calculator, nurture to paid services.', 'est' => '2hr', 'urgent' => true),
                array('title' => 'Create WhatsApp Business catalog', 'desc' => 'List all services with prices. Auto-reply with catalog.', 'est' => '30min', 'urgent' => false),
            );
            $actions['content'] = array(
                array('title' => 'Write 2 blog posts', 'desc' => 'SEO-optimized, targeting diaspora keywords.'),
                array('title' => 'Post 2 Instagram Reels', 'desc' => 'Show AI design process, cost comparisons.'),
                array('title' => 'Share 1 LinkedIn case study', 'desc' => 'Even a concept project counts as a case study.'),
            );
        } elseif ($month == 10) {
            // OCTOBER: Acceleration
            $actions['revenue'] = array(
                array('title' => 'Diaspora outreach blitz — 20 DMs/day', 'desc' => 'Instagram, Facebook groups, WhatsApp diaspora groups. "Building in Ghana?" hook.', 'est' => '1hr', 'urgent' => true),
                array('title' => 'Close 2 contractor partnerships', 'desc' => 'Sign agreements. You design, they build. Revenue share.', 'est' => '1hr', 'urgent' => true),
                array('title' => 'Pitch "Building Permit Package" to 5 clients', 'desc' => 'Full design + permit for fixed fee. High value, high margin.', 'est' => '30min', 'urgent' => true),
                array('title' => 'Launch membership waitlist', 'desc' => '"Join the waitlist for Pro tier." Capture emails for Nov launch.', 'est' => '30min', 'urgent' => false),
                array('title' => 'Create 3 house plan bundles for sale', 'desc' => '3-bed modern, 4-bed classic, duplex. $50-200 each.', 'est' => '2hr', 'urgent' => false),
            );
            $actions['system'] = array(
                array('title' => 'Set up n8n lead qualification workflow', 'desc' => 'Auto-categorize inquiries: hot/warm/cold. Auto-respond.', 'est' => '2hr', 'urgent' => false),
                array('title' => 'Create automated proposal generator', 'desc' => 'Input: project details. Output: branded PDF proposal.', 'est' => '3hr', 'urgent' => true),
            );
            $actions['content'] = array(
                array('title' => 'Write 2 blog posts', 'desc' => 'Target "building in Ghana from UK/US" keywords.'),
                array('title' => 'Post 3 Instagram Reels', 'desc' => 'Diaspora-focused: "Your dream home in Ghana for $X".'),
                array('title' => 'Start YouTube channel', 'desc' => 'First video: "How I Design Ghana Homes with AI".'),
            );
        } elseif ($month == 11) {
            // NOVEMBER: Peak season
            $actions['revenue'] = array(
                array('title' => 'Close ALL pipeline deals', 'desc' => 'Call every warm lead. "Dry season is here, time to build." Urgency.', 'est' => '2hr', 'urgent' => true),
                array('title' => 'Launch membership tiers', 'desc' => 'Professional ($35/mo), Enterprise ($99/mo). Open enrollment.', 'est' => '1hr', 'urgent' => true),
                array('title' => 'Upsell existing clients to full package', 'desc' => 'Concept clients -> Full Design. Full Design -> Complete.', 'est' => '1hr', 'urgent' => true),
                array('title' => 'Pitch 2 developers for large project', 'desc' => 'Target: estate developers, church buildings, school projects.', 'est' => '45min', 'urgent' => true),
                array('title' => 'Create "New Year, New Home" campaign', 'desc' => 'Pre-sell Jan-Mar 2027 design slots. 20% early bird discount.', 'est' => '30min', 'urgent' => false),
            );
            $actions['system'] = array(
                array('title' => 'Automate invoice + payment reminders', 'desc' => 'Auto-send reminders at Day 3, Day 7, Day 14.', 'est' => '1hr', 'urgent' => false),
                array('title' => 'Set up client portal', 'desc' => 'Basic WordPress page where clients track their project.', 'est' => '2hr', 'urgent' => false),
            );
            $actions['content'] = array(
                array('title' => 'Write 2 blog posts', 'desc' => 'Target "dry season building in Ghana" keywords.'),
                array('title' => 'Post daily Instagram stories', 'desc' => 'Show projects, behind-the-scenes, testimonials.'),
                array('title' => 'LinkedIn: 1 post/day', 'desc' => 'Establish authority. Share insights daily.'),
            );
        } elseif ($month == 12) {
            // DECEMBER: Maximum capture
            $actions['revenue'] = array(
                array('title' => 'Close December diaspora visitors', 'desc' => 'Every visitor = potential $2,500-5,000 client. Schedule consultations NOW.', 'est' => '3hr', 'urgent' => true),
                array('title' => 'Launch "2027 Early Bird" pricing', 'desc' => 'Lock in 2026 prices for Jan-Mar projects. Create urgency.', 'est' => '1hr', 'urgent' => true),
                array('title' => 'Collect testimonials from all completed projects', 'desc' => 'Video testimonials are gold. Offer discount for next project.', 'est' => '1hr', 'urgent' => true),
                array('title' => 'Final push: DM all unconverted leads', 'desc' => '"Last chance to start building in dry season." Scarcity.', 'est' => '1hr', 'urgent' => true),
                array('title' => 'Plan Q1 2027 pricing increase', 'desc' => 'Raise prices 15-20% for Jan. Announce now.', 'est' => '30min', 'urgent' => false),
            );
            $actions['system'] = array(
                array('title' => 'Annual review: what worked, what didnt', 'desc' => 'Document everything. Build playbook for 2027.', 'est' => '2hr', 'urgent' => false),
                array('title' => 'Plan 2027 product roadmap', 'desc' => 'SaaS extraction, academy launch, API.', 'est' => '2hr', 'urgent' => false),
            );
            $actions['content'] = array(
                array('title' => 'Write "Year in Review" post', 'desc' => 'Showcase projects, growth, impact. Build trust.'),
                array('title' => 'Post holiday content', 'desc' => '"Merry Christmas from your future home in Ghana".'),
                array('title' => 'Create 2027 content calendar', 'desc' => 'Plan 52 weeks of content in advance.'),
            );
        }

        // DAY-OF-WEEK ADJUSTMENTS
        if ($day_of_week == 0) { // Sunday — rest + plan
            $actions['revenue'] = array(
                array('title' => 'Weekly review: pipeline, revenue, next week plan', 'desc' => 'Review all deals. Plan next week priorities.', 'est' => '1hr', 'urgent' => true),
            );
            $actions['system'] = array(
                array('title' => 'Plan next week content calendar', 'desc' => 'Schedule all posts in advance.', 'est' => '1hr', 'urgent' => false),
            );
        } elseif ($day_of_week == 6) { // Saturday — networking
            array_unshift($actions['revenue'], array(
                'title' => 'Attend 1 construction meetup or WhatsApp group',
                'desc' => 'Network with contractors, developers, suppliers. Build partnerships.',
                'est' => '2hr',
                'urgent' => false
            ));
        }

        return $actions;
    }
}
