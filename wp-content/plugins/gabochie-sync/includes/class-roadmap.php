<?php
if (!defined('ABSPATH')) exit;

class Gabochie_Roadmap {

    private static $sprints = array(
        'sprint1' => array(
            'title' => 'Sprint 1 — Infrastructure & Local Dev',
            'days' => 'Phase 1 — DONE',
            'color' => '#C9A96E',
            'tasks' => array(
                's1_1' => 'Install XAMPP (Apache + MySQL) on local machine',
                's1_2' => 'Install WordPress locally at localhost/gabochie',
                's1_3' => 'Build custom "gabochie-design" theme (templates, assets, functions)',
                's1_4' => 'Create page templates: About, Services, Contact, Portfolio, FAQ, Calculator',
                's1_5' => 'Install Rank Math SEO on local dev',
                's1_6' => 'Install WPForms Lite on local dev',
                's1_7' => 'Build Gabochie Sync plugin (REST API push/pull, settings, diff)',
                's1_8' => 'Create Application Passwords on live for API auth',
                's1_9' => 'Configure Sync plugin settings (remote URL, username, API key)',
                's1_10' => 'Test push/pull content sync locally → live via REST API',
                's1_11' => 'Build Maintenance Mode plugin for live site',
                's1_12' => 'Deploy maintenance mode to live (activated during build)',
                's1_13' => 'Deploy theme + plugins to live via fixer script',
                's1_14' => 'Set Cloudflare DNS + SSL for design.gabochie.com',
                's1_15' => 'Fix template assignments on live (About, Services, Contact, Portfolio)',
                's1_16' => 'Create local dev content sync workflow (localhost → live)',
            ),
        ),
        'sprint2' => array(
            'title' => 'Sprint 2 — Content & Pages Build',
            'days' => 'Phase 2 — DONE',
            'color' => '#0D5C52',
            'tasks' => array(
                's2_1' => 'Build all 18 core pages locally (Home, About, Services, Contact, etc.)',
                's2_2' => 'Write content for 8 empty pages (cookie-policy, refund, pricing, diaspora, etc.)',
                's2_3' => 'Push all page content from local → live via REST API',
                's2_4' => 'Fix localhost references on 5 live pages (careers, services, etc.)',
                's2_5' => 'Create 21 blog posts with full content (600-1023 words each)',
                's2_6' => 'Expand 16 thin posts (was under 300 words) to 600+ words',
                's2_7' => 'Create 5 categories (Property, Building, Diaspora, Design, Legal)',
                's2_8' => 'Create 74 tags and assign to all posts',
                's2_9' => 'Download 21 contextual Unsplash images for featured images',
                's2_10' => 'Set featured images on all 21 posts (local + live)',
                's2_11' => 'Assign categories to all 21 posts on live',
                's2_12' => 'Assign tags to all 21 posts on live',
                's2_13' => 'Remove "Uncategorized" from all posts on live',
                's2_14' => 'Push expanded post content + images + cats + tags to live',
                's2_15' => 'Modify page.php — conditional hero (skip duplicate H1)',
                's2_16' => 'Deploy fixed page.php to live',
                's2_17' => 'Verify all 18 pages return 200 on live',
                's2_18' => 'Verify all 21 blog posts return 200 on live',
            ),
        ),
        'sprint3' => array(
            'title' => 'Sprint 3 — SEO & AdSense Readiness',
            'days' => 'Phase 3 — IN PROGRESS',
            'color' => '#7B61FF',
            'tasks' => array(
                's3_1' => 'Audit all meta titles + descriptions (Rank Math)',
                's3_2' => 'Add Open Graph images to all pages',
                's3_3' => 'Verify sitemap.xml submits correctly to Google Search Console',
                's3_4' => 'Submit site to Google Search Console + Bing Webmaster Tools',
                's3_5' => 'Install Site Kit by Google (GA4 + Search Console)',
                's3_6' => 'Add internal linking between blog posts',
                's3_7' => 'Add schema markup (LocalBusiness, FAQPage, Article)',
                's3_8' => 'Optimize all images (WebP conversion, lazy loading)',
                's3_9' => 'Run PageSpeed audit + fix render-blocking resources',
                's3_10' => 'Apply for Google AdSense',
                's3_11' => 'Create AdSense-ready ad placements (header, sidebar, in-article)',
                's3_12' => 'Write 5 SEO pillar posts',
                's3_13' => 'Set up Google Business Profile for Gabochie Design',
                's3_14' => 'List on 5+ local directories (Ghana business dirs)',
            ),
        ),
        'sprint4' => array(
            'title' => 'Sprint 4 — Client Membership & Payments',
            'days' => 'Phase 4 — DONE',
            'color' => '#4CAF50',
            'tasks' => array(
                's4_1' => 'Install Paid Memberships Pro (PMPro) plugin',
                's4_2' => 'Install WooCommerce + ExpressPay payment gateway',
                's4_3' => 'Install PMPro WooCommerce Add On (bridge)',
                's4_4' => 'Create PMPro membership levels: Free Lead (₵0), Active Client (₵2,500), Premium Service (₵5,000)',
                's4_5' => 'Generate all PMPro pages (Account, Billing, Cancel, Checkout, Confirmation, Invoice, Levels, Login)',
                's4_6' => 'Configure PMPro advanced settings (dashboard restriction, toolbar hide, search filters)',
                's4_7' => 'Configure ExpressPay sandbox credentials (Merchant ID + API Key)',
                's4_8' => 'Set up PMPro login redirect to /dashboard/ and dashboard protection',
                's4_9' => 'Create WooCommerce membership products: Active Client (₵2,500) + Premium Service (₵5,000)',
                's4_10' => 'Link WooCommerce products to PMPro levels via Membership tab',
                's4_11' => 'Set products to Virtual + Hidden catalog visibility',
                's4_12' => 'Test full checkout: product → cart → WooCommerce → ExpressPay sandbox → payment → membership granted',
                's4_13' => 'Verify membership account page shows correct level',
            ),
        ),
        'sprint4b' => array(
            'title' => 'Sprint 4b — Local → Live Migration',
            'days' => 'Phase 4b — ACTIVE',
            'color' => '#E67E22',
            'tasks' => array(
                's4b_1' => 'Rotate ExpressPay merchant API key in dashboard (was hardcoded in git)',
                's4b_2' => 'Create private GitHub repo (gabochie-wp-content) and push wp-content/',
                's4b_3' => 'Add GitHub Actions secrets: SSH_HOST, SSH_PORT, SSH_USERNAME, SSH_KEY, WP_PATH',
                's4b_4' => 'Enable SSH on cPanel and add deploy SSH public key',
                's4b_5' => 'Install WP-CLI on cPanel server',
                's4b_6' => 'Clone repo into ~/public_html/wp-content/ on cPanel',
                's4b_7' => 'Export local DB via All-in-One WP Migration (handles URL find/replace)',
                's4b_8' => 'Import DB on live via All-in-One WP Migration',
                's4b_9' => 'Verify site: pages, membership login, payment gateway, calculator all work',
                's4b_10' => 'Flush permalinks on live (Settings → Permalinks → Save)',
                's4b_11' => 'Test git push → auto-deploy via GitHub Actions',
                's4b_12' => 'Document new workflow: git for code, All-in-One for DB changes',
            ),
        ),
        'sprint5' => array(
            'title' => 'Sprint 5 — Micro SaaS Ecosystem (GhBC)',
            'days' => 'Phase 5 — MVP BUILD',
            'color' => '#7B61FF',
            'tasks' => array(
                's5_1' => 'Apply for Google Africa AI Lab with pitch deck (AI-Native OS for construction)',
                's5_2' => 'Digitize GIA Conditions of Engagement (fee scales, stages, dispute resolution)',
                's5_3' => 'Build GIA Fee Engine (sliding scale, partial service, pro-rata calculator)',
                's5_4' => 'Define pricing & gating per tool — DONE (Free 20/Pro 500/Firm unlimited tiers)',
                's5_5' => 'Build shared RAG engine — DONE (FULLTEXT on wp_posts, 1,361 chunks, LLM tool calling)',
            ),
        ),
        'ghbc_tools' => array(
            'title' => 'GhBC Agent Tools — Build Status',
            'days' => 'Active Build',
            'color' => '#E91E63',
            'tasks' => array(
                'gt_01' => 'Compliance Checker — LIVE: Chat UI at /ghbc-compliance/, agent loop with search_code + get_clause',
                'gt_02' => 'Small Buildings Auto-Check — LIVE: Part 35 qualification + simplified checklist',
                'gt_03' => 'Exam Prep Agent — LIVE: Randomized MCQs with scoring + clause citations',
                'gt_04' => 'Fire Safety Design Assistant — BUILDING NOW: Occupancy group → fire resistance, sprinklers, travel',
                'gt_05' => 'Permit-Ready Plan Review Agent — PENDING: Upload plans → auto-check against all 38 Parts',
                'gt_06' => 'Materials & Specifications Agent — PENDING: Code-compliant material lookup by Part/Section',
                'gt_07' => 'Green Building & Energy Advisor — PENDING: Part 14 energy efficiency + Part 37 green building',
                'gt_08' => 'Construction Safeguards Monitor — PENDING: Part 34 site safety inspection checklist',
            ),
        ),
        'sprint6' => array(
            'title' => 'Sprint 6 — GIA + Architect SaaS Suite',
            'days' => 'Phase 6 — UPCOMING',
            'color' => '#C9A96E',
            'tasks' => array(
                's6_1' => 'Build AI Cost Estimator (Gemini-powered, Ghana market rates)',
                's6_2' => 'Build AI Proposal Builder (GIA-compliant engagement letters)',
                's6_3' => 'Build AI Contract Generator (scope, stage payments, dispute clauses)',
                's6_4' => 'Build Permit Navigator (EPA, Fire, Lands Commission workflows)',
                's6_5' => 'Build AI Quantity Surveyor (plan upload → BOQ)',
                's6_6' => 'Create architect subscription tiers ($29 Starter / $79 Firm / $299 Enterprise)',
                's6_7' => 'Expand to Nigeria NIA fee scale (multi-jurisdiction compliance)',
                's6_8' => 'Expand to Kenya AAK + South Africa SACAP fee scales',
                's6_9' => 'Professional body licensing (GIA, NIA, AAK as distribution partners)',
                's6_10' => 'Build n8n automation workflows (lead qualification, nurture, follow-up)',
            ),
        ),
        'sprint7' => array(
            'title' => 'Sprint 7 — Scale & Community',
            'days' => 'Phase 7 — FUTURE',
            'color' => '#E55039',
            'tasks' => array(
                's7_1' => 'Launch community forum or Discord integration',
                's7_2' => 'Create "Academy Coming Soon" page with waitlist',
                's7_3' => 'Build AI Feasibility Analyzer',
                's7_4' => 'Optimize conversion funnels (heatmaps, A/B testing)',
                's7_5' => 'Start social media content machine',
                's7_6' => 'Plan aec.gabochie.com for Phase 2',
                's7_7' => 'Document all workflows for scaling',
                's7_8' => 'Build block theme (FSE) for production',
                's7_9' => 'Enable 2FA for all admin accounts',
                's7_10' => 'Configure security headers on cPanel',
            ),
        ),
    );

    public static function render_page() {
        if (!current_user_can('manage_options')) return;

        if (isset($_POST['gabochie_roadmap_save']) && check_admin_referer('gabochie_roadmap_save')) {
            $progress = array();
            if (!empty($_POST['roadmap_checks'])) {
                foreach ($_POST['roadmap_checks'] as $key => $val) {
                    $progress[$key] = sanitize_text_field($val);
                }
            }
            update_option('gabochie_roadmap_progress', $progress);
            echo '<div class="notice notice-success"><p>Roadmap progress saved.</p></div>';
        }

        $progress = get_option('gabochie_roadmap_progress', array());
        $total_tasks = 0;
        foreach (self::$sprints as $sprint) {
            $total_tasks += count($sprint['tasks']);
        }
        $done_tasks = count(array_filter($progress));
        $pct = $total_tasks > 0 ? round(($done_tasks / $total_tasks) * 100) : 0;
        ?>
        <div class="wrap gabochie-roadmap-wrap">
            <div class="gr-header">
                <h1>Build Roadmap</h1>
                <p>Multi-Phase Agentic Build — Track every task. Your progress is saved to the database.</p>
            </div>

            <div class="gr-overall">
                <div class="gr-progress-wrap">
                    <div class="gr-progress-bar">
                        <div class="gr-progress-fill" style="width:<?php echo $pct; ?>%"></div>
                    </div>
                    <div class="gr-progress-info">
                        <span><strong><?php echo $done_tasks; ?></strong> of <strong><?php echo $total_tasks; ?></strong> tasks complete</span>
                        <span><strong><?php echo $pct; ?>%</strong></span>
                    </div>
                </div>
                <div class="gr-sprint-pills">
                    <?php foreach (self::$sprints as $key => $sprint) :
                        $s_done = 0;
                        $s_total = count($sprint['tasks']);
                        foreach ($sprint['tasks'] as $k => $v) {
                            if (!empty($progress[$k])) $s_done++;
                        }
                        $s_pct = $s_total > 0 ? round(($s_done / $s_total) * 100) : 0;
                    ?>
                    <a href="#<?php echo $key; ?>" class="gr-pill" style="border-color:<?php echo $sprint['color']; ?>">
                        <span class="gr-pill-pct" style="background:<?php echo $sprint['color']; ?>"><?php echo $s_pct; ?>%</span>
                        <span><?php echo $sprint['title']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="post">
                <?php wp_nonce_field('gabochie_roadmap_save'); ?>

                <?php foreach (self::$sprints as $sprint_key => $sprint) :
                    $s_done = 0;
                    $s_total = count($sprint['tasks']);
                    foreach ($sprint['tasks'] as $k => $v) {
                        if (!empty($progress[$k])) $s_done++;
                    }
                    $s_pct = $s_total > 0 ? round(($s_done / $s_total) * 100) : 0;
                ?>
                <div class="gr-sprint" id="<?php echo $sprint_key; ?>">
                    <div class="gr-sprint-header" style="border-left:4px solid <?php echo $sprint['color']; ?>">
                        <div>
                            <h2><?php echo $sprint['title']; ?></h2>
                            <span class="gr-sprint-days"><?php echo $sprint['days']; ?></span>
                        </div>
                        <div class="gr-sprint-stat">
                            <span class="gr-sprint-pct" style="color:<?php echo $sprint['color']; ?>"><?php echo $s_pct; ?>%</span>
                            <span class="gr-sprint-count"><?php echo $s_done; ?>/<?php echo $s_total; ?></span>
                        </div>
                    </div>
                    <ul class="gr-checklist">
                        <?php foreach ($sprint['tasks'] as $task_key => $task_text) :
                            $checked = !empty($progress[$task_key]);
                        ?>
                        <li class="<?php echo $checked ? 'gr-done' : ''; ?>">
                            <label>
                                <input type="checkbox" name="roadmap_checks[<?php echo esc_attr($task_key); ?>]" value="1" <?php checked($checked); ?> class="gr-cb" />
                                <span class="gr-check-display"></span>
                                <span class="gr-task-text"><?php echo $task_text; ?></span>
                            </label>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>

                <div class="gr-save-wrap">
                    <?php submit_button('Save Progress', 'primary', 'gabochie_roadmap_save'); ?>
                </div>
            </form>
        </div>
        <?php
    }
}
