<?php
if (!defined('ABSPATH')) exit;

class Gabochie_Strategy {

    public static function render_page() {
        if (!current_user_can('manage_options')) return;

        // Handle strategy content save
        if (isset($_POST['gabochie_strategy_save']) && check_admin_referer('gabochie_strategy_save')) {
            update_option('gabochie_strategy_content', wp_unslash($_POST['strategy_content']));
            echo '<div class="notice notice-success"><p>Strategy saved.</p></div>';
        }

        $content = get_option('gabochie_strategy_content', self::get_default_strategy());
        ?>
        <div class="wrap gabochie-strategy-wrap">
            <div class="gs-header">
                <h1>Agentic Strategy</h1>
                <p>The Gabochie Design ecosystem blueprint — AI agents, revenue model, and execution plan.</p>
            </div>

            <!-- Nav tabs -->
            <div class="gs-nav-tabs">
                <button class="gs-tab active" data-tab="overview">Overview</button>
                <button class="gs-tab" data-tab="ecosystem">Ecosystem</button>
                <button class="gs-tab" data-tab="agents">AI Agents</button>
                <button class="gs-tab" data-tab="wordpress">WordPress Plan</button>
                <button class="gs-tab" data-tab="revenue">Revenue</button>
                <button class="gs-tab" data-tab="seo">Search Domination</button>
                <button class="gs-tab" data-tab="ops-agents">Ops Agents</button>
            </div>

            <!-- OVERVIEW -->
            <div class="gs-panel active" id="gs-overview">
                <div class="gs-two-col">
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Core Thesis</span>
                        <h3>Intelligence Over Drawings</h3>
                        <p>Competitors sell deliverables. We sell intelligence. While traditional firms sell drawings, renderings, and supervision, Gabochie Design sells AI-powered cost intelligence, automated permitting, structural analysis, and autonomous AI employees.</p>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">AI-First</span><span class="gs-tag gs-tag-teal">Subscription</span><span class="gs-tag gs-tag-teal">Scalable</span></div>
                    </div>
                    <div class="gs-card">
                        <span class="gs-badge gs-badge-teal">Architecture</span>
                        <h3>WordPress as Orchestration</h3>
                        <p>WordPress handles: marketing, landing pages, SEO, user accounts, memberships, payments, CRM, AI chat interfaces, and workflow orchestration. Heavy computation moves to external AI services.</p>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">Front Office</span><span class="gs-tag gs-tag-teal">Workflows</span></div>
                    </div>
                </div>

                <h2>System Architecture</h2>
                <div class="gs-arch">
                    <div class="gs-arch-layer gs-arch-client">
                        <strong>Client Layer — WordPress</strong>
                        <div class="gs-arch-items">
                            <span>Marketing Website</span><span>Landing Pages</span><span>Blog &amp; SEO</span>
                            <span>User Accounts</span><span>Memberships</span><span>Payments</span><span>AI Chat UI</span>
                        </div>
                    </div>
                    <div class="gs-arch-arrow">&#8595;</div>
                    <div class="gs-arch-layer gs-arch-workflow">
                        <strong>Workflow Layer — Google Apps Script + n8n</strong>
                        <div class="gs-arch-items">
                            <span>Email Automation</span><span>CRM Updates</span><span>Invoice Generation</span>
                            <span>WhatsApp Follow-up</span><span>Proposal Drafting</span><span>Scheduling</span>
                        </div>
                    </div>
                    <div class="gs-arch-arrow">&#8595;</div>
                    <div class="gs-arch-layer gs-arch-ai">
                        <strong>AI Layer — OpenAI / Gemini / Claude</strong>
                        <div class="gs-arch-items">
                            <span>Cost Estimation</span><span>Plan Analysis</span><span>Building Code Q&amp;A</span>
                            <span>BOQ Generation</span><span>Permit Guidance</span><span>Feasibility Study</span>
                        </div>
                    </div>
                    <div class="gs-arch-arrow">&#8595;</div>
                    <div class="gs-arch-layer gs-arch-data">
                        <strong>Data Layer — PostgreSQL / Supabase / Firestore</strong>
                        <div class="gs-arch-items">
                            <span>Material Prices</span><span>Project Data</span><span>User Profiles</span>
                            <span>Building Code DB</span><span>Cost Intelligence</span>
                        </div>
                    </div>
                    <div class="gs-arch-arrow">&#8595;</div>
                    <div class="gs-arch-layer gs-arch-infra">
                        <strong>Infrastructure — Cloudflare + Hosting</strong>
                        <div class="gs-arch-items">
                            <span>Cloudflare DNS</span><span>SSL/CDN</span><span>WAF</span>
                            <span>cPanel Hosting</span><span>Email</span>
                        </div>
                    </div>
                </div>

                <h2>Domain Strategy</h2>
                <div class="gs-domain-grid">
                    <div class="gs-domain"><strong>design.gabochie.com</strong><br><span>Brand, company, content, consulting, portfolio</span><br><span class="gs-status-live">Live — WordPress</span></div>
                    <div class="gs-domain"><strong>aec.gabochie.com</strong><br><span>AI platform for Architecture, Engineering &amp; Construction</span><br><span class="gs-status-planned">Phase 2</span></div>
                    <div class="gs-domain"><strong>academy.gabochie.com</strong><br><span>Training, certification, AI for AEC professionals</span><br><span class="gs-status-planned">Phase 3</span></div>
                    <div class="gs-domain"><strong>labs.gabochie.com</strong><br><span>Experimental AI tools, prototypes, beta features</span><br><span class="gs-status-future">Phase 3</span></div>
                    <div class="gs-domain"><strong>api.gabochie.com</strong><br><span>Developer APIs, integrations, webhooks</span><br><span class="gs-status-future">Phase 4</span></div>
                    <div class="gs-domain"><strong>docs.gabochie.com</strong><br><span>Documentation, guides, API reference</span><br><span class="gs-status-future">Phase 4</span></div>
                </div>
            </div>

            <!-- ECOSYSTEM -->
            <div class="gs-panel" id="gs-ecosystem">
                <h2>The 7 Pillars</h2>
                <div class="gs-grid-3">
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Build First</span>
                        <h3>&#129302; AI Tools</h3>
                        <p>Free lead magnets that become premium products</p>
                        <ul>
                            <li>&#11088; BuildCostGH AI — Cost intelligence platform</li>
                            <li>&#8594; AI Quantity Surveyor — Upload plan, get BOQ</li>
                            <li>&#8594; AI Building Advisor — Ghana Building Code Q&amp;A</li>
                            <li>&#8230; Material Price Intelligence Dashboard</li>
                        </ul>
                    </div>
                    <div class="gs-card">
                        <h3>&#128101; AI Employees</h3>
                        <p>Autonomous agents that work 24/7 — monthly subscription</p>
                        <p class="gs-desc">Rent AI employees: AI Architect, AI Structural Engineer, AI MEP Engineer, AI Permit Assistant, AI Receptionist, AI Sales Agent, AI Estimator, AI Proposal Writer, AI Site Reporter.</p>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">MRR Model</span><span class="gs-tag gs-tag-gold">High Value</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#128230; Productized Services</h3>
                        <p>Fixed outcomes, not hours. Predictable revenue.</p>
                        <p class="gs-desc">House Design in 7 Days. Building Cost Report in 24 Hours. Building Approval Package. AI Feasibility Study. Fixed price. Fixed scope. Fixed timeline.</p>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">Fixed Price</span><span class="gs-tag gs-tag-gold">Scalable</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#128196; Digital Products</h3>
                        <p>High-margin, sell-forever assets</p>
                        <p class="gs-desc">House plan bundles, construction checklists, BOQ templates, contracts, method statements, site safety manuals, pricing databases, prompt libraries, AI workflows.</p>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">High Margin</span><span class="gs-tag gs-tag-teal">Passive Income</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#128187; SaaS Products</h3>
                        <p>Extracted from WordPress when demand is proven</p>
                        <p class="gs-desc">Contractor CRM, Site Diary AI, Snagging App, Tender Assistant, Material Price Tracker, Construction Marketplace.</p>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">SaaS</span><span class="gs-tag gs-tag-gold">Phase 2+</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#127891; Academy</h3>
                        <p>Teach professionals AI skills for AEC</p>
                        <p class="gs-desc">AI for Architects, Engineers, Contractors, Quantity Surveyors. WordPress + Automation courses. AI Business Systems. Recurring membership model.</p>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">Membership</span><span class="gs-tag gs-tag-teal">Recurring</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#127757; Community</h3>
                        <p>Ghana's largest AI-in-Construction community</p>
                        <p class="gs-desc">Weekly AI tools, prompt library, construction updates, cost database, live workshops, networking. Monthly subscription for access.</p>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">Network Effect</span><span class="gs-tag gs-tag-gold">Moat</span></div>
                    </div>
                </div>
            </div>

            <!-- AI AGENTS -->
            <div class="gs-panel" id="gs-agents">
                <h2>The Agentic Pipeline</h2>
                <p class="gs-subtitle">A client uploads a plan. Six agents work. The customer gets a complete automated service.</p>
                <div class="gs-agent-flow">
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#128228;</span><strong>Client Uploads</strong><br><small>Plan, sketch, or brief</small></div>
                    <span class="gs-agent-arrow">&#8594;</span>
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#128269;</span><strong>Agent 1: Extractor</strong><br><small>AI extracts project details</small></div>
                    <span class="gs-agent-arrow">&#8594;</span>
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#128176;</span><strong>Agent 2: Estimator</strong><br><small>AI estimates costs</small></div>
                    <span class="gs-agent-arrow">&#8594;</span>
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#128203;</span><strong>Agent 3: Proposer</strong><br><small>AI prepares proposal</small></div>
                    <span class="gs-agent-arrow">&#8594;</span>
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#128197;</span><strong>Agent 4: Scheduler</strong><br><small>AI schedules consultation</small></div>
                    <span class="gs-agent-arrow">&#8594;</span>
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#128241;</span><strong>Agent 5: Follower</strong><br><small>AI follows up</small></div>
                    <span class="gs-agent-arrow">&#8594;</span>
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#129534;</span><strong>Agent 6: Closer</strong><br><small>CRM + invoice</small></div>
                </div>

                <h2>Phase 1: AI Tool Agents</h2>
                <div class="gs-grid-2">
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">&#11088; Flagship</span>
                        <h3>&#128200; BuildCostGH AI</h3>
                        <p>Ghana's cost intelligence platform</p>
                        <p class="gs-desc">Users input: Region, Land size, Floors, Finish quality, Roof type, Foundation, Structural system. Returns: Estimated cost, breakdown, materials, labour, duration, PDF report.</p>
                        <p class="gs-monetize">Monetization: Free lead magnet &#8594; Premium PDF reports &#8594; Monthly subscription &#8594; API</p>
                    </div>
                    <div class="gs-card">
                        <h3>&#128208; AI Quantity Surveyor</h3>
                        <p>Upload floor plan &#8594; Get BOQ</p>
                        <p class="gs-desc">Upload a floor plan. AI returns: Bill of Quantities, material quantities for concrete, blocks, paint, tiles, roofing, steel estimate.</p>
                    </div>
                    <div class="gs-card">
                        <h3>&#128214; AI Building Advisor</h3>
                        <p>Trained on Ghana Building Code</p>
                        <p class="gs-desc">ChatGPT trained on Ghana Building Code, National Building Regulations, Fire standards, Accessibility codes.</p>
                    </div>
                    <div class="gs-card">
                        <h3>&#128203; AI Permit Assistant</h3>
                        <p>Guides through EPA, Fire, Lands Commission</p>
                        <p class="gs-desc">Automatically generates checklists and required documents for EPA approval, Fire Service clearance, Lands Commission, District Assembly permits.</p>
                    </div>
                    <div class="gs-card">
                        <h3>&#127959;&#65039; AI Feasibility Analyzer</h3>
                        <p>Upload land &#8594; Get ROI analysis</p>
                        <p class="gs-desc">Best land use recommendation, ROI projection, apartment yield analysis, rental income estimation, construction cost forecast.</p>
                    </div>
                    <div class="gs-card">
                        <h3>&#127891; Academy Platform</h3>
                        <p>WordPress LMS for AI+AEC training</p>
                        <p class="gs-desc">AI for Architects, Engineers, Contractors, QS. WordPress + Automation courses. Use LearnDash or TutorLMS.</p>
                    </div>
                </div>

                <h2>Phase 2: AI Employees to Rent</h2>
                <div class="gs-grid-4">
                    <div class="gs-card gs-card-sm"><h4>&#127963;&#65039; AI Architect</h4><p>Upload sketch &#8594; Plans, rooms, 3D concept, budget</p></div>
                    <div class="gs-card gs-card-sm"><h4>&#127959;&#65039; AI Structural Engineer</h4><p>Upload drawing &#8594; Column layout, beams, foundations</p></div>
                    <div class="gs-card gs-card-sm"><h4>&#9889; AI MEP Engineer</h4><p>Plumbing, electrical, AC layouts and sizing</p></div>
                    <div class="gs-card gs-card-sm"><h4>&#128222; AI Receptionist</h4><p>24/7 client intake, qualification, routing</p></div>
                    <div class="gs-card gs-card-sm"><h4>&#128188; AI Sales Agent</h4><p>Follow-ups, proposals, closing sequences</p></div>
                    <div class="gs-card gs-card-sm"><h4>&#128200; AI Estimator</h4><p>Instant cost estimates from brief or plan</p></div>
                    <div class="gs-card gs-card-sm"><h4>&#9997;&#65039; AI Proposal Writer</h4><p>Auto-generate proposals and quotations</p></div>
                    <div class="gs-card gs-card-sm"><h4>&#128221; AI Site Reporter</h4><p>Daily site reports from photos + AI</p></div>
                </div>
            </div>

            <!-- OPERATIONAL AGENTS -->
            <div class="gs-panel" id="gs-ops-agents">
                <h2>Operational Agents — Running the Business</h2>
                <p class="gs-subtitle">These agents run Gabochie Design internally — research, content, SEO, and customer operations. Separate from the product agents above.</p>
                <div class="gs-grid-2">
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Always On</span>
                        <h3>&#128300; Research Agent</h3>
                        <p>Monitors Ghana construction market, material prices, regulatory changes, competitor moves, and diaspora trends. Feeds intelligence to all other agents.</p>
                        <ul>
                            <li>Weekly material price tracking (cement, steel, roofing)</li>
                            <li>Regulatory change alerts (EPA, Fire Service, District Assemblies)</li>
                            <li>Competitor content monitoring</li>
                            <li>Diaspora sentiment analysis (Reddit, Facebook groups)</li>
                            <li> feeds BuildCostGH pricing database</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">Market Intelligence</span><span class="gs-tag gs-tag-teal">Automated</span></div>
                    </div>
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Revenue Driver</span>
                        <h3>&#128269; SEO-GEO Agent</h3>
                        <p>Optimizes every piece of content for every search surface. Manages keyword tracking, schema updates, sitemap submissions, and AI citation monitoring.</p>
                        <ul>
                            <li>Daily keyword rank tracking (Google, Bing, Yahoo)</li>
                            <li>Weekly content optimization audits</li>
                            <li>Monthly schema markup updates</li>
                            <li>AI citation monitoring (ChatGPT, Perplexity, Gemini)</li>
                            <li>IndexNow ping for instant Bing indexing</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">Search Domination</span><span class="gs-tag gs-tag-teal">Automated</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#9997;&#65039; Content Agent</h3>
                        <p>Writes, optimizes, and publishes blog posts, pillar content, email sequences, social media copy, and AI tool documentation.</p>
                        <ul>
                            <li>Weekly blog posts (800-1,500 words, SEO-optimized)</li>
                            <li>Monthly pillar content (2,000-3,000 words)</li>
                            <li>Email newsletter copy (weekly)</li>
                            <li>Social media posts (3x/week across platforms)</li>
                            <li>AI tool updates and changelogs</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">Content Engine</span><span class="gs-tag gs-tag-teal">Scalable</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#128172; Customer Agent</h3>
                        <p>Handles lead qualification, appointment scheduling, follow-ups, onboarding sequences, and customer support via WhatsApp and web chat.</p>
                        <ul>
                            <li>24/7 lead capture and qualification</li>
                            <li>Automated appointment scheduling (Calendly integration)</li>
                            <li>3-touch follow-up sequence (Day 1, Day 3, Day 7)</li>
                            <li>Customer onboarding workflow</li>
                            <li>Support ticket routing and escalation</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">Customer Success</span><span class="gs-tag gs-tag-gold">Revenue</span></div>
                    </div>
                </div>

                <h2>Agent Orchestration</h2>
                <div class="gs-agent-flow">
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#128300;</span><strong>Research</strong><br><small>Market data in</small></div>
                    <span class="gs-agent-arrow">&#8594;</span>
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#128269;</span><strong>SEO-GEO</strong><br><small>Keywords + schema</small></div>
                    <span class="gs-agent-arrow">&#8594;</span>
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#9997;&#65039;</span><strong>Content</strong><br><small>Write + publish</small></div>
                    <span class="gs-agent-arrow">&#8594;</span>
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#128172;</span><strong>Customer</strong><br><small>Convert + retain</small></div>
                    <span class="gs-agent-arrow">&#8594;</span>
                    <div class="gs-agent-node"><span class="gs-agent-icon">&#128176;</span><strong>Revenue</strong><br><small>Grow MRR</small></div>
                </div>
            </div>

            <!-- WORDPRESS -->
            <div class="gs-panel" id="gs-wordpress">
                <h2>WordPress Plan — design.gabochie.com</h2>
                <p class="gs-subtitle">Block theme, minimal plugins, agentic integrations. WordPress as the front office.</p>

                <div class="gs-two-col">
                    <div class="gs-card">
                        <h3>&#128225; Current State</h3>
                        <table class="gs-info-table">
                            <tr><td>Domain</td><td>design.gabochie.com (Namecheap)</td></tr>
                            <tr><td>DNS</td><td>Cloudflare (SSL + CDN)</td></tr>
                            <tr><td>Hosting</td><td>Shared (cPanel)</td></tr>
                            <tr><td>WordPress</td><td>26 posts + 18 pages live</td></tr>
                            <tr><td>Theme</td><td>gabochie-design (custom classic)</td></tr>
                            <tr><td>Plugins</td><td>Rank Math, WPForms, Akismet, Gabochie Sync</td></tr>
                            <tr><td>Local Dev</td><td>XAMPP + WP-CLI + REST API sync</td></tr>
                            <tr><td>Blog Content</td><td>21 posts (600-1023 words each)</td></tr>
                            <tr><td>Budget</td><td>Under $500</td></tr>
                        </table>
                    </div>
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Target</span>
                        <h3>&#127919; Performance Targets</h3>
                        <div class="gs-perf-item"><span>PageSpeed Score</span><span>Target: &gt; 90</span></div>
                        <div class="gs-perf-item"><span>LCP</span><span>Target: &lt; 2.5s</span></div>
                        <div class="gs-perf-item"><span>CLS</span><span>Target: &lt; 0.1</span></div>
                        <div class="gs-perf-item"><span>INP</span><span>Target: &lt; 200ms</span></div>
                    </div>
                </div>

                <h3>Plugin Stack</h3>
                <table class="widefat gs-table">
                    <thead><tr><th>Category</th><th>Plugin</th><th>Purpose</th><th>Priority</th></tr></thead>
                    <tbody>
                        <tr><td>SEO</td><td>Rank Math (Free)</td><td>On-page SEO, sitemaps, schema, analytics</td><td><span class="gs-dot-inline gs-dot-green"></span> Essential</td></tr>
                        <tr><td>Security</td><td>Wordfence (Free)</td><td>Firewall, malware scanning, login security</td><td><span class="gs-dot-inline gs-dot-green"></span> Essential</td></tr>
                        <tr><td>Backup</td><td>UpdraftPlus (Free)</td><td>Automated backups to Google Drive/Dropbox</td><td><span class="gs-dot-inline gs-dot-green"></span> Essential</td></tr>
                        <tr><td>Forms</td><td>WPForms Lite</td><td>Contact forms, lead capture</td><td><span class="gs-dot-inline gs-dot-green"></span> Essential</td></tr>
                        <tr><td>Analytics</td><td>Site Kit by Google</td><td>GA4, Search Console integration</td><td><span class="gs-dot-inline gs-dot-green"></span> Essential</td></tr>
                        <tr><td>Images</td><td>ShortPixel (Free)</td><td>Image optimization, WebP conversion</td><td><span class="gs-dot-inline gs-dot-gold"></span> Important</td></tr>
                        <tr><td>Memberships</td><td>MemberPress or Paid Memberships Pro</td><td>Subscription tiers, gated content</td><td><span class="gs-dot-inline gs-dot-blue"></span> Phase 2</td></tr>
                        <tr><td>LMS</td><td>LearnDash or TutorLMS</td><td>Academy courses, certificates</td><td><span class="gs-dot-inline gs-dot-blue"></span> Phase 3</td></tr>
                    </tbody>
                </table>

                <h3>Customer Journey — Automated by AI Agents</h3>
                <div class="gs-journey">
                    <div class="gs-journey-step" style="border-top:3px solid #0D5C52">
                        <span class="gs-journey-icon">&#127760;</span>
                        <strong>1. Discovery</strong>
                        <small>SEO + Blog &#8594; Landing Page &#8594; AI Tool Free Trial</small>
                    </div>
                    <div class="gs-journey-step" style="border-top:3px solid #C9A96E">
                        <span class="gs-journey-icon">&#128200;</span>
                        <strong>2. Engagement</strong>
                        <small>BuildCostGH &#8594; Free Report &#8594; Email Capture &#8594; Lead</small>
                    </div>
                    <div class="gs-journey-step" style="border-top:3px solid #7B61FF">
                        <span class="gs-journey-icon">&#129302;</span>
                        <strong>3. AI Qualification</strong>
                        <small>AI Agent extracts needs &#8594; Prepares proposal &#8594; Schedules call</small>
                    </div>
                    <div class="gs-journey-step" style="border-top:3px solid #2563EB">
                        <span class="gs-journey-icon">&#128188;</span>
                        <strong>4. Conversion</strong>
                        <small>Proposal &#8594; Invoice (Paystack) &#8594; Onboarding &#8594; Project Start</small>
                    </div>
                    <div class="gs-journey-step" style="border-top:3px solid #059669">
                        <span class="gs-journey-icon">&#128260;</span>
                        <strong>5. Retention</strong>
                        <small>AI Site Reports &#8594; Follow-ups &#8594; Upsell &#8594; Subscription</small>
                    </div>
                </div>
            </div>

            <!-- REVENUE -->
            <div class="gs-panel" id="gs-revenue">
                <h2>The Revenue Ladder</h2>
                <p class="gs-subtitle">Start free, build trust, ascend to recurring revenue. Every level feeds the next.</p>
                <div class="gs-revenue-grid">
                    <div class="gs-revenue-card" style="border-left:4px solid #a7aaad"><span class="gs-rev-level">Level 1</span><strong>Free AI Calculators</strong><span class="gs-rev-model">Lead generation</span><span class="gs-rev-price">GH&#x20B5; 0</span></div>
                    <div class="gs-revenue-card" style="border-left:4px solid #0D5C52"><span class="gs-rev-level">Level 2</span><strong>PDF Reports</strong><span class="gs-rev-model">One-time purchase</span><span class="gs-rev-price">GH&#x20B5; 50&#8211;200</span></div>
                    <div class="gs-revenue-card" style="border-left:4px solid #C9A96E"><span class="gs-rev-level">Level 3</span><strong>Productized Services</strong><span class="gs-rev-model">Project fees</span><span class="gs-rev-price">GH&#x20B5; 500&#8211;5,000</span></div>
                    <div class="gs-revenue-card" style="border-left:4px solid #2563EB"><span class="gs-rev-level">Level 4</span><strong>SaaS Tools</strong><span class="gs-rev-model">Monthly subscription</span><span class="gs-rev-price">GH&#x20B5; 99&#8211;499/mo</span></div>
                    <div class="gs-revenue-card" style="border-left:4px solid #7B61FF"><span class="gs-rev-level">Level 5</span><strong>AI Employees</strong><span class="gs-rev-model">Monthly recurring</span><span class="gs-rev-price">GH&#x20B5; 299&#8211;999/mo</span></div>
                    <div class="gs-revenue-card" style="border-left:4px solid #E55039"><span class="gs-rev-level">Level 6</span><strong>Academy</strong><span class="gs-rev-model">Membership</span><span class="gs-rev-price">GH&#x20B5; 149/mo</span></div>
                    <div class="gs-revenue-card gs-rev-enterprise"><span class="gs-rev-level">Level 7</span><strong>Enterprise</strong><span class="gs-rev-model">High-ticket contracts</span><span class="gs-rev-price">GH&#x20B5; 10,000+</span></div>
                </div>

                <h2>Membership Tiers</h2>
                <div class="gs-pricing-grid">
                    <div class="gs-pricing-tier">
                        <span class="gs-tier-name">Explorer</span>
                        <span class="gs-tier-price">Free</span>
                        <span class="gs-tier-desc">Get started with basic AI tools</span>
                        <ul><li>BuildCostGH Basic (limited queries)</li><li>Building Advisor (5 questions/mo)</li><li>Newsletter access</li><li>Community forum</li></ul>
                    </div>
                    <div class="gs-pricing-tier gs-tier-popular">
                        <span class="gs-popular-tag">Most Popular</span>
                        <span class="gs-tier-name">Professional</span>
                        <span class="gs-tier-price">GH&#x20B5; 149 <small>/mo</small></span>
                        <span class="gs-tier-desc">Full access to AI tools and reports</span>
                        <ul><li>Unlimited BuildCostGH queries</li><li>Unlimited Building Advisor</li><li>Premium PDF reports</li><li>AI Quantity Surveyor (10/mo)</li><li>Material price alerts</li><li>Priority support</li></ul>
                    </div>
                    <div class="gs-pricing-tier">
                        <span class="gs-tier-name">Enterprise</span>
                        <span class="gs-tier-price">GH&#x20B5; 499 <small>/mo</small></span>
                        <span class="gs-tier-desc">AI employees for your firm</span>
                        <ul><li>Everything in Professional</li><li>1 AI Employee (choice of role)</li><li>API access</li><li>Custom workflows</li><li>White-label reports</li><li>Dedicated support</li></ul>
                    </div>
                    <div class="gs-pricing-tier">
                        <span class="gs-tier-name">Academy</span>
                        <span class="gs-tier-price">GH&#x20B5; 99 <small>/mo</small></span>
                        <span class="gs-tier-desc">Learn AI skills for AEC</span>
                        <ul><li>All courses access</li><li>Live workshops</li><li>Prompt library</li><li>AI workflow templates</li><li>Certificates</li><li>Community access</li></ul>
                    </div>
                </div>

                <h2>Revenue Milestones</h2>
                <table class="widefat gs-table">
                    <thead><tr><th>Timeline</th><th>Milestone</th><th>Revenue Target</th><th>Key Driver</th></tr></thead>
                    <tbody>
                        <tr><td>Month 1&#8211;3</td><td>Launch + First Users</td><td>GH&#x20B5; 0&#8211;500</td><td>Free tools, email capture, SEO foundation</td></tr>
                        <tr><td>Month 4&#8211;6</td><td>First Paying Users</td><td>GH&#x20B5; 1,000&#8211;3,000</td><td>PDF reports, productized services</td></tr>
                        <tr><td>Month 7&#8211;12</td><td>Subscription Growth</td><td>GH&#x20B5; 5,000&#8211;15,000/mo</td><td>Professional tier, AI tools traction</td></tr>
                        <tr><td>Year 2</td><td>Scale + Enterprise</td><td>GH&#x20B5; 20,000&#8211;50,000/mo</td><td>Enterprise tier, AI employees, Academy</td></tr>
                        <tr><td>Year 3</td><td>Platform Status</td><td>GH&#x20B5; 100,000+/mo</td><td>SaaS extraction, marketplace, API</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- SEO & SEARCH DOMINATION -->
            <div class="gs-panel" id="gs-seo">
                <h2>Search Engine Domination Strategy</h2>
                <p class="gs-subtitle">Own every search engine, every keyword, every surface — Google, Bing, Yahoo, DuckDuckGo, Apple Maps, YouTube, ChatGPT, Perplexity, and AI Overviews.</p>

                <!-- OVERVIEW -->
                <div class="gs-two-col">
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Mission</span>
                        <h3>Search Everywhere Optimization (SEO)</h3>
                        <p>Traditional SEO targets Google. We target <strong>every surface people search</strong> — traditional search engines, AI chatbots, voice assistants, maps, social media, video platforms, and generative AI. When someone asks "how much does it cost to build a house in Ghana" — whether they type it in Google, ask ChatGPT, search on YouTube, or query Bing — the answer is Gabochie Design.</p>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">Search Everywhere</span><span class="gs-tag gs-tag-teal">AI-First</span><span class="gs-tag gs-tag-teal">Multi-Engine</span></div>
                    </div>
                    <div class="gs-card">
                        <span class="gs-badge gs-badge-teal">Strategy</span>
                        <h3>Four-Layer Approach</h3>
                        <div class="gs-perf-item"><span>Layer 1: Technical Foundation</span><span>Every engine can find us</span></div>
                        <div class="gs-perf-item"><span>Layer 2: Content Moat</span><span>10x better than any competitor</span></div>
                        <div class="gs-perf-item"><span>Layer 3: Authority & Trust</span><span>Backlinks, citations, reviews</span></div>
                        <div class="gs-perf-item"><span>Layer 4: AI & GEO</span><span>Optimized for generative AI</span></div>
                    </div>
                </div>

                <!-- ENGINE-BY-ENGINE STRATEGY -->
                <h2>Engine-by-Engine Strategy</h2>
                <div class="gs-grid-3">
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Primary</span>
                        <h3>&#128269; Google (55% of traffic)</h3>
                        <ul>
                            <li>Rank Math SEO Pro setup (schema, sitemaps, redirects)</li>
                            <li>Google Search Console verified + sitemap submitted</li>
                            <li>Google Analytics 4 via Site Kit</li>
                            <li>Google Business Profile (Ghana + diaspora)</li>
                            <li>Google Merchant Center (for digital products)</li>
                            <li>Structured data: Organization, Article, FAQPage, LocalBusiness, Product</li>
                            <li>Core Web Vitals optimized (LCP &lt;2.5s, CLS &lt;0.1)</li>
                            <li>Google AI Overview optimization (GEO)</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">55% traffic</span><span class="gs-tag gs-tag-teal">Priority 1</span></div>
                    </div>
                    <div class="gs-card">
                        <span class="gs-badge gs-badge-teal">Secondary</span>
                        <h3>&#127760; Bing (20% of traffic)</h3>
                        <ul>
                            <li>Bing Webmaster Tools verified + sitemap</li>
                            <li>Bing Places for Business (local listing)</li>
                            <li>IndexNow API (instant indexing)</li>
                            <li>Bing Chat / Copilot optimization</li>
                            <li>Microsoft Clarity (free heatmaps)</li>
                            <li>LinkedIn sharing (Microsoft ecosystem)</li>
                            <li>Bing favors: XML sitemaps, structured data, social signals</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">20% traffic</span><span class="gs-tag gs-tag-teal">Priority 2</span></div>
                    </div>
                    <div class="gs-card">
                        <span class="gs-badge gs-badge-teal">Emerging</span>
                        <h3>&#129302; AI Search Engines</h3>
                        <ul>
                            <li>ChatGPT Search — structured content, clear answers</li>
                            <li>Perplexity AI — citation-optimized, authoritative</li>
                            <li>Google AI Overviews — featured snippet targeting</li>
                            <li>Claude — referenced in training data</li>
                            <li>Gemini — Google ecosystem integration</li>
                            <li>Cohere, Mistral — future-proofing</li>
                            <li>Strategy: Write definitive, well-structured content that AI cites as source</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">Future-proof</span><span class="gs-tag gs-tag-gold">Growth</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#127909; YouTube (2nd largest search engine)</h3>
                        <ul>
                            <li>YouTube channel: "Gabochie Design"</li>
                            <li>Video content: Ghana building cost breakdowns</li>
                            <li>Tutorials: "How to build in Ghana step by step"</li>
                            <li>Shorts: quick tips, before/after, cost reveals</li>
                            <li>Video schema markup on embedded videos</li>
                            <li>YouTube SEO: titles, descriptions, timestamps</li>
                            <li>Cross-link: YouTube videos in blog posts</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">Video SEO</span><span class="gs-tag gs-tag-teal">High Intent</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#128205; Apple Maps &amp; Siri</h3>
                        <ul>
                            <li>Apple Maps Connect business listing</li>
                            <li>Apple Business Register</li>
                            <li>LocalBusiness schema (NAP consistency)</li>
                            <li>Apple's crawlable business data format</li>
                            <li>Optimize for "Hey Siri, architect near me"</li>
                            <li>Voice search optimization (natural language)</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">Local</span><span class="gs-tag gs-tag-teal">Voice</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#128221; Social Media Search</h3>
                        <ul>
                            <li>Pinterest: architectural boards, house plans, Ghana homes</li>
                            <li>TikTok: quick architecture tips, Ghana builds</li>
                            <li>Instagram: project portfolio, Reels, location tags</li>
                            <li>LinkedIn: thought leadership, B2B services</li>
                            <li>Facebook: page + group for diaspora community</li>
                            <li>Each platform has its own search algorithm</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">Discovery</span><span class="gs-tag gs-tag-teal">Brand</span></div>
                    </div>
                </div>

                <!-- TECHNICAL SEO -->
                <h2>Layer 1: Technical SEO Foundation</h2>
                <table class="widefat gs-table">
                    <thead><tr><th>Element</th><th>What to Do</th><th>Target</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr><td>XML Sitemaps</td><td>Rank Math auto-generates; submit to GSC + Bing WMT</td><td>All 43 pages indexed</td><td><span class="gs-status-planned">Flush needed</span></td></tr>
                        <tr><td>Schema Markup</td><td>Organization, Article, FAQPage, LocalBusiness, Product</td><td>All pages have schema</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Open Graph / Twitter Cards</td><td>Dynamic per-page OG tags + Twitter summary</td><td>All pages share correctly</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Canonical URLs</td><td>Self-referencing canonicals on all pages</td><td>No duplicate content</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Mobile Responsive</td><td>Mobile-first design, responsive across devices</td><td>100% mobile friendly</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Core Web Vitals</td><td>LCP &lt;2.5s, CLS &lt;0.1, INP &lt;200ms</td><td>All green on PageSpeed</td><td><span class="gs-status-live">LCP 1.3s, CLS 0.0</span></td></tr>
                        <tr><td>HTTPS Everywhere</td><td>SSL certificate, redirect HTTP to HTTPS</td><td>100% HTTPS</td><td><span class="gs-status-live">Done (Cloudflare)</span></td></tr>
                        <tr><td>Compressed Images</td><td>WebP format, lazy loading, srcset</td><td>All images optimized</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Internal Linking</td><td>Related posts, breadcrumbs, contextual links</td><td>Every post links to 3+ others</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Robots.txt</td><td>Allow all crawlers, point to sitemap</td><td>Clean robots.txt</td><td><span class="gs-status-planned">Verify</span></td></tr>
                        <tr><td>404 Error Handling</td><td>Custom 404 page, monitor in GSC</td><td>No broken links</td><td><span class="gs-status-planned">Add 404 page</span></td></tr>
                        <tr><td>hreflang Tags</td><td>English for Ghana + diaspora markets</td><td>en-US, en-GH, en-GB</td><td><span class="gs-status-planned">Add hreflang</span></td></tr>
                        <tr><td>IndexNow</td><td>Submit new/updated pages to Bing instantly</td><td>Instant indexing</td><td><span class="gs-status-planned">Implement</span></td></tr>
                        <tr><td>Page Speed</td><td>Minify CSS/JS, defer render-blocking, preload fonts</td><td>&lt;3s full load</td><td><span class="gs-status-planned">Optimize</span></td></tr>
                        <tr><td>AdSense Compliance</td><td>Ad placements, lazy loading, proper spacing</td><td>Ready for AdSense approval</td><td><span class="gs-status-live">Hooks added</span></td></tr>
                    </tbody>
                </table>

                <!-- CONTENT STRATEGY -->
                <h2>Layer 2: Content Moat Strategy</h2>
                <p class="gs-subtitle">Create content so comprehensive, so definitive, that every competitor becomes a footnote.</p>

                <h3>5 SEO Pillar Posts (3,000+ words each)</h3>
                <div class="gs-grid-2">
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Pillar 1</span>
                        <h3>&#128176; The Complete Guide to Building a House in Ghana in 2026</h3>
                        <p>Target keywords: building cost ghana, cost to build house ghana, ghana construction cost 2026, building a house in ghana step by step</p>
                        <ul>
                            <li>Full cost breakdown by region (Accra, Kumasi, Tema, etc.)</li>
                            <li>Material costs with live pricing data</li>
                            <li>Labour costs by trade</li>
                            <li>Timeline from land acquisition to move-in</li>
                            <li>Common mistakes and how to avoid them</li>
                            <li>Diaspora-specific advice (remote building)</li>
                            <li>Interactive cost calculator embed</li>
                            <li>PDF download (lead magnet)</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">Lead Magnet</span><span class="gs-tag gs-tag-teal">3,000+ words</span></div>
                    </div>
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Pillar 2</span>
                        <h3>&#127757; How to Build a House in Ghana From the UK, USA, or Canada — Complete Diaspora Guide</h3>
                        <p>Target keywords: build house ghana from abroad, ghana diaspora construction, building in ghana from uk, ghana property diaspora</p>
                        <ul>
                            <li>Legal requirements for diaspora buyers</li>
                            <li>Land verification process (step by step)</li>
                            <li>Finding trustworthy contractors remotely</li>
                            <li>Payment protection and escrow</li>
                            <li>Currency conversion strategies</li>
                            <li>Virtual site visits and progress monitoring</li>
                            <li>Tax implications in home country + Ghana</li>
                            <li>Case studies of successful diaspora builds</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">High Value</span><span class="gs-tag gs-tag-teal">3,000+ words</span></div>
                    </div>
                    <div class="gs-card">
                        <span class="gs-badge gs-badge-teal">Pillar 3</span>
                        <h3>&#129302; AI in Architecture: How Artificial Intelligence is Transforming Building Design in Ghana</h3>
                        <p>Target keywords: ai architecture ghana, ai construction cost estimator, ai building design, artificial intelligence architecture</p>
                        <ul>
                            <li>How AI generates floor plans</li>
                            <li>AI cost estimation vs traditional QS</li>
                            <li>3D visualization with AI</li>
                            <li>Predictive analytics for construction</li>
                            <li>Ghana-specific AI applications</li>
                            <li>Our AI tools overview and demo</li>
                        </ul>
                    </div>
                    <div class="gs-card">
                        <span class="gs-badge gs-badge-teal">Pillar 4</span>
                        <h3>&#128221; Ghana Building Regulations and Permits — Everything You Need to Know</h3>
                        <p>Target keywords: ghana building regulations, building permit ghana, epa approval ghana, lands commission ghana, district assembly permit</p>
                        <ul>
                            <li>National Building Regulations overview</li>
                            <li>Permit application step by step</li>
                            <li>EPA environmental clearance</li>
                            <li>Fire Service requirements</li>
                            <li>District Assembly permits</li>
                            <li>Common reasons for rejection</li>
                            <li>Timeline and costs</li>
                        </ul>
                    </div>
                    <div class="gs-card">
                        <span class="gs-badge gs-badge-teal">Pillar 5</span>
                        <h3>&#127959;&#65039; How to Choose the Best Architectural Firm in Ghana — Complete Guide</h3>
                        <p>Target keywords: best architect ghana, architectural firm accra, house design ghana, building design company ghana</p>
                        <ul>
                            <li>What to look for in a Ghanaian architect</li>
                            <li>Questions to ask before hiring</li>
                            <li>Portfolio evaluation checklist</li>
                            <li>Cost expectations and red flags</li>
                            <li>Why AI-powered firms are different</li>
                            <li>Our process and portfolio</li>
                        </ul>
                    </div>
                </div>

                <h3>Content Calendar — Monthly Output</h3>
                <table class="widefat gs-table">
                    <thead><tr><th>Content Type</th><th>Frequency</th><th>Word Count</th><th>Target Engine</th><th>Purpose</th></tr></thead>
                    <tbody>
                        <tr><td>Pillar Posts</td><td>1 per month</td><td>3,000–5,000</td><td>Google, Bing, AI</td><td>Keyword authority, backlink magnet</td></tr>
                        <tr><td>Blog Posts</td><td>4 per month</td><td>1,500–2,500</td><td>Google, Bing</td><td>Long-tail keywords, topical authority</td></tr>
                        <tr><td>YouTube Videos</td><td>2 per month</td><td>8–15 min</td><td>YouTube, Google Video</td><td>Video SEO, brand trust</td></tr>
                        <tr><td>YouTube Shorts</td><td>4 per month</td><td>30–60 sec</td><td>YouTube, TikTok</td><td>Discovery, virality</td></tr>
                        <tr><td>Pinterest Pins</td><td>10 per month</td><td>N/A</td><td>Pinterest, Google Images</td><td>Visual search, house plans</td></tr>
                        <tr><td>LinkedIn Articles</td><td>2 per month</td><td>800–1,500</td><td>LinkedIn, Bing</td><td>B2B authority, professional network</td></tr>
                        <tr><td>TikTok Videos</td><td>4 per month</td><td>30–90 sec</td><td>TikTok, Google Video</td><td>Gen Z audience, viral potential</td></tr>
                        <tr><td>AI-Optimized FAQs</td><td>Weekly</td><td>300–500</td><td>ChatGPT, Perplexity</td><td>AI citation, featured snippets</td></tr>
                    </tbody>
                </table>

                <!-- LOCAL SEO -->
                <h2>Layer 3: Local SEO &amp; Citations</h2>
                <div class="gs-grid-2">
                    <div class="gs-card">
                        <h3>&#128205; Google Business Profile</h3>
                        <ul>
                            <li>Create profile: "Gabochie Design"</li>
                            <li>Category: Architect, Architectural Designer</li>
                            <li>Service area: Accra, Tema, Kumasi, all Ghana + diaspora cities</li>
                            <li>Upload portfolio images (20+)</li>
                            <li>Collect client reviews (target: 50+ in 6 months)</li>
                            <li>Post weekly updates (projects, tips, offers)</li>
                            <li>Add services with pricing</li>
                            <li>Enable messaging and booking</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">Critical</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#127760; Bing Places for Business</h3>
                        <ul>
                            <li>Create Bing Places listing</li>
                            <li>Same info as Google Business Profile</li>
                            <li>Import from Google (one-click)</li>
                            <li>Add photos and services</li>
                            <li>Monitor and respond to reviews</li>
                            <li>Bing Places feeds into Bing Maps + Cortana</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">Important</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#128230; Business Directory Listings</h3>
                        <p><strong>Ghana Directories:</strong></p>
                        <ul>
                            <li>GhanaWeb Business Directory</li>
                            <li>GhanaYello (construction category)</li>
                            <li>Accra Street Directory</li>
                            <li>Tema Business Directory</li>
                            <li>Ghana Chamber of Construction Industry</li>
                            <li>Association of Building and Civil Engineering Contractors</li>
                        </ul>
                        <p><strong>International Directories:</strong></p>
                        <ul>
                            <li>Clutch.co (architecture firms)</li>
                            <li>Houzz (architects)</li>
                            <li>Architects.com</li>
                            <li>LinkedIn Company Page</li>
                            <li>Facebook Business Page</li>
                            <li>Yelp (US diaspora)</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-teal">NAP Consistency</span></div>
                    </div>
                    <div class="gs-card">
                        <h3>&#127760; Diaspora-Specific Directories</h3>
                        <ul>
                            <li>GhanaHomelink.com</li>
                            <li>GhanaRealEstate.com</li>
                            <li>Diaspora African Forum</li>
                            <li>Ghana National Council (UK)</li>
                            <li>Ghana National Council (USA)</li>
                            <li>ABCEnterpriseOnline.com</li>
                            <li>Ghana Business Directory (London)</li>
                        </ul>
                        <div class="gs-tags"><span class="gs-tag gs-tag-gold">High Value</span></div>
                    </div>
                </div>

                <!-- AI & GEO -->
                <h2>Layer 4: Generative Engine Optimization (GEO)</h2>
                <p class="gs-subtitle">When someone asks ChatGPT, Perplexity, or Google AI Overviews about building in Ghana — our content is the source.</p>

                <div class="gs-two-col">
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">GEO Strategy</span>
                        <h3>&#129302; Be the AI's Source of Truth</h3>
                        <ul>
                            <li><strong>Structured content:</strong> Clear H1/H2/H3 hierarchy that AI can parse</li>
                            <li><strong>Definitive answers:</strong> "The cost to build a 3-bedroom house in Ghana in 2026 is GH₵350,000–600,000 depending on region and finish quality."</li>
                            <li><strong>Statistics and data:</strong> Cite specific numbers, prices, timelines that AI will reference</li>
                            <li><strong>FAQ format:</strong> Direct Q&A pairs that match voice search queries</li>
                            <li><strong>Citation-ready:</strong> Clear attributions, dates, sources that AI can reference</li>
                            <li><strong>llms.txt file:</strong> Tell AI crawlers what to index and how to cite us</li>
                            <li><strong>Author authority:</strong> Entity markup connecting to professional profiles</li>
                            <li><strong>Topical depth:</strong> Cover every subtopic so comprehensively that we become the default source</li>
                        </ul>
                    </div>
                    <div class="gs-card">
                        <span class="gs-badge gs-badge-teal">Technical</span>
                        <h3>&#128268; AI Crawler Optimization</h3>
                        <ul>
                            <li><strong>robots.txt:</strong> Allow all AI crawlers (GPTBot, CCBot, Anthropic, PerplexityBot)</li>
                            <li><strong>llms.txt:</strong> Standard file telling AI how to cite us</li>
                            <li><strong>Structured data:</strong> JSON-LD schema on every page</li>
                            <li><strong>Clean HTML:</strong> Minimal divs, semantic HTML5 elements</li>
                            <li><strong>Fast loading:</strong> AI crawlers prioritize fast sites</li>
                            <li><strong>RSS feed:</strong> Let AI know about new content</li>
                            <li><strong>Author pages:</strong> E-E-A-T signals (Experience, Expertise, Authoritativeness, Trust)</li>
                        </ul>
                    </div>
                </div>

                <!-- BACKLINK STRATEGY -->
                <h2>Authority &amp; Backlink Strategy</h2>
                <div class="gs-grid-3">
                    <div class="gs-card">
                        <h3>&#128279; Guest Posting</h3>
                        <ul>
                            <li>GhanaWeb.com articles</li>
                            <li>ModernGhana.com features</li>
                            <li>MyJoyOnline.com op-eds</li>
                            <li>Diaspora blogs and publications</li>
                            <li>Architecture/design magazines</li>
                            <li>Construction industry publications</li>
                            <li>AI/tech publications (AI angle)</li>
                        </ul>
                    </div>
                    <div class="gs-card">
                        <h3>&#128230; PR &amp; Media</h3>
                        <ul>
                            <li>Press releases for tool launches</li>
                            <li>Media kit for journalists</li>
                            <li>Expert commentary on Ghana construction</li>
                            <li>Radio/TV appearances (Citi FM, Joy FM, etc.)</li>
                            <li>Podcast guesting (diaspora podcasts)</li>
                            <li>Conference speaking (GHIAA, GIA)</li>
                        </ul>
                    </div>
                    <div class="gs-card">
                        <h3>&#128101; Community &amp; Partnerships</h3>
                        <ul>
                            <li>Ghana Institute of Architects partnership</li>
                            <li>University collaborations (KNUST, UG)</li>
                            <li>Construction forum participation</li>
                            <li>Reddit r/ghana, r/africanarchitecture</li>
                            <li>Quora answers (architecture + Ghana)</li>
                            <li>Facebook groups for diaspora builders</li>
                            <li>Stack Overflow (AI/tech questions)</li>
                        </ul>
                    </div>
                </div>

                <!-- COMPETITOR LANDSCAPE -->
                <h2>Competitor Landscape</h2>
                <table class="widefat gs-table">
                    <thead><tr><th>Competitor</th><th>What They Do</th><th>Weakness</th><th>Our Advantage</th></tr></thead>
                    <tbody>
                        <tr><td><strong>GhanaHousePlanner.com</strong></td><td>Free cost calculator, AI floor plans</td><td>Cost estimation only, no full services</td><td>Full AEC ecosystem + AI employees + content depth</td></tr>
                        <tr><td><strong>ExactoCost.com</strong></td><td>BOQ &amp; cost estimation for QS</td><td>Niche (QS professionals only), no SEO</td><td>Consumer + diaspora focused, 10x content</td></tr>
                        <tr><td><strong>Ownkey.com/gh</strong></td><td>Real estate + cost guides</td><td>Generic, no AI tools, thin content</td><td>AI-powered, subscription model, definitive guides</td></tr>
                        <tr><td><strong>Planner5D.com</strong></td><td>Global design tool</td><td>Not Ghana-specific</td><td>Ghana-native pricing + local data + local SEO</td></tr>
                        <tr><td><strong>KeyArchitect.com</strong></td><td>Traditional architecture firm</td><td>No AI, no digital products, no content</td><td>AI tools + academy + community + content moat</td></tr>
                        <tr><td><strong>GhanaWeb</strong></td><td>News + business directory</td><td>Not construction-focused, no tools</td><td>Purpose-built for AEC, interactive tools</td></tr>
                    </tbody>
                </table>

                <!-- KEYWORD MAP -->
                <h2>Keyword Domination Map</h2>
                <div class="gs-grid-2">
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Critical — Month 1</span>
                        <h3>&#128176; Cost &amp; Estimation Keywords</h3>
                        <ul>
                            <li><strong>how much does it cost to build a house in ghana</strong> (3,600/mo)</li>
                            <li><strong>building cost ghana 2026</strong> (1,900/mo)</li>
                            <li><strong>construction cost calculator ghana</strong> (1,200/mo)</li>
                            <li>cost of building a 3 bedroom house in ghana (880/mo)</li>
                            <li>ghana building cost per square meter (720/mo)</li>
                            <li>building material prices ghana 2026 (590/mo)</li>
                            <li>how much to build a house in accra (480/mo)</li>
                            <li>ghana construction budget (320/mo)</li>
                        </ul>
                    </div>
                    <div class="gs-card gs-card-featured">
                        <span class="gs-badge gs-badge-gold">Critical — Month 1</span>
                        <h3>&#127959;&#65039; Architecture &amp; Design Keywords</h3>
                        <ul>
                            <li><strong>architect in accra</strong> (2,400/mo)</li>
                            <li><strong>architect ghana</strong> (1,800/mo)</li>
                            <li><strong>house plans ghana</strong> (1,500/mo)</li>
                            <li>building design ghana (990/mo)</li>
                            <li>architectural firm accra (720/mo)</li>
                            <li>modern house design ghana (590/mo)</li>
                            <li>house design in ghana with prices (480/mo)</li>
                            <li>3 bedroom house plan ghana (410/mo)</li>
                        </ul>
                    </div>
                    <div class="gs-card">
                        <span class="gs-badge gs-badge-teal">Month 2</span>
                        <h3>&#127757; Diaspora Keywords (Highest Value — Ready to Pay)</h3>
                        <ul>
                            <li><strong>building a house in ghana from abroad</strong> (1,100/mo)</li>
                            <li><strong>building a house in ghana from uk</strong> (720/mo)</li>
                            <li>building a house in ghana from usa (590/mo)</li>
                            <li>building a house in ghana from canada (320/mo)</li>
                            <li>how to build a house in ghana remotely (260/mo)</li>
                            <li>ghana property for diaspora (210/mo)</li>
                            <li>investing in ghana real estate (480/mo)</li>
                            <li>ghana land verification process (170/mo)</li>
                        </ul>
                    </div>
                    <div class="gs-card">
                        <span class="gs-badge gs-badge-teal">Month 2</span>
                        <h3>&#129302; AI &amp; Technology Keywords</h3>
                        <ul>
                            <li><strong>ai construction cost estimator</strong> (590/mo)</li>
                            <li>ai building cost calculator ghana (320/mo)</li>
                            <li>ai quantity surveyor (260/mo)</li>
                            <li>ai architecture ghana (170/mo)</li>
                            <li>automated building cost estimate (140/mo)</li>
                            <li>ai for construction industry (390/mo)</li>
                            <li>ai building design software (310/mo)</li>
                        </ul>
                    </div>
                </div>

                <h3>Long-Tail Keywords (Topical Authority)</h3>
                <div class="gs-grid-3">
                    <div class="gs-card">
                        <h4>Legal &amp; Finance</h4>
                        <ul>
                            <li>ghana land title verification</li>
                            <li>how to buy land in ghana as a foreigner</li>
                            <li>ghana building permit process</li>
                            <li>lands commission ghana requirements</li>
                            <li>epa approval ghana construction</li>
                            <li>ghana property tax for diaspora</li>
                            <li>contractor payment schedule ghana</li>
                        </ul>
                    </div>
                    <div class="gs-card">
                        <h4>Building &amp; Construction</h4>
                        <ul>
                            <li>concrete block price ghana 2026</li>
                            <li>cement price ghana per bag</li>
                            <li>roofing sheets ghana prices</li>
                            <li>foundation types ghana</li>
                            <li>structural engineer ghana</li>
                            <li>plumbing installation ghana</li>
                            <li>electrical wiring ghana house</li>
                        </ul>
                    </div>
                    <div class="gs-card">
                        <h4>Lifestyle &amp; Relocation</h4>
                        <ul>
                            <li>moving back to ghana from uk</li>
                            <li>relocating to ghana from usa</li>
                            <li>best neighborhoods in accra</li>
                            <li>tema vs accra for building</li>
                            <li>east legon house prices</li>
                            <li>ghana retirement home design</li>
                            <li>building a rental property in ghana</li>
                        </ul>
                    </div>
                </div>

                <!-- MEASUREMENT -->
                <h2>Measurement &amp; KPIs</h2>
                <table class="widefat gs-table">
                    <thead><tr><th>KPI</th><th>Tool</th><th>Month 1 Target</th><th>Month 3 Target</th><th>Month 6 Target</th><th>Month 12 Target</th></tr></thead>
                    <tbody>
                        <tr><td>Organic Traffic</td><td>GA4</td><td>100 visits</td><td>500 visits</td><td>2,000 visits</td><td>10,000 visits</td></tr>
                        <tr><td>Keywords on Page 1</td><td>GSC + Bing WMT</td><td>5</td><td>20</td><td>50</td><td>100+</td></tr>
                        <tr><td>Indexed Pages</td><td>GSC + Bing WMT</td><td>43</td><td>60</td><td>100</td><td>200+</td></tr>
                        <tr><td>Backlinks</td><td>Ahrefs / Moz</td><td>10</td><td>50</td><td>150</td><td>500+</td></tr>
                        <tr><td>Domain Authority</td><td>Moz / Ahrefs</td><td>5</td><td>15</td><td>25</td><td>40+</td></tr>
                        <tr><td>Google Business Reviews</td><td>GBP</td><td>5</td><td>15</td><td>30</td><td>50+</td></tr>
                        <tr><td>YouTube Subscribers</td><td>YouTube Studio</td><td>50</td><td>200</td><td>500</td><td>2,000+</td></tr>
                        <tr><td>AI Citations</td><td>Manual check</td><td>0</td><td>5</td><td>20</td><td>50+</td></tr>
                        <tr><td>Email Subscribers</td><td>Mailchimp/MailPoet</td><td>20</td><td>100</td><td>500</td><td>2,000+</td></tr>
                        <tr><td>AdSense Revenue</td><td>AdSense</td><td>$0</td><td>$50/mo</td><td>$200/mo</td><td>$1,000/mo</td></tr>
                    </tbody>
                </table>

                <!-- SEO MILESTONES -->
                <h2>SEO Milestones &amp; Checklist</h2>
                <table class="widefat gs-table">
                    <thead><tr><th>Phase</th><th>Task</th><th>Owner</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr><td>Foundation</td><td>Launch WordPress site with custom theme</td><td>AI + Dev</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Foundation</td><td>Create 21 blog posts + 18 pages</td><td>AI Writer</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Foundation</td><td>Rank Math SEO + schema markup</td><td>AI + Dev</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Foundation</td><td>OG tags + Twitter cards + social meta</td><td>AI + Dev</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Foundation</td><td>Accessibility fixes (viewport, skip links, a11y)</td><td>AI + Dev</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Foundation</td><td>AdSense hooks + lazy loading + fetchpriority</td><td>AI + Dev</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Setup</td><td>Flush Rank Math sitemap cache</td><td>Manual</td><td><span class="gs-status-planned">Blocked on user</span></td></tr>
                        <tr><td>Setup</td><td>Submit sitemap to Google Search Console</td><td>Manual</td><td><span class="gs-status-planned">Needs gabochie@gmail.com</span></td></tr>
                        <tr><td>Setup</td><td>Submit sitemap to Bing Webmaster Tools</td><td>Manual</td><td><span class="gs-status-planned">Needs manual setup</span></td></tr>
                        <tr><td>Setup</td><td>Install Site Kit (GA4 + Search Console)</td><td>Manual</td><td><span class="gs-status-planned">Needs OAuth</span></td></tr>
                        <tr><td>Setup</td><td>Create Google Business Profile</td><td>Manual</td><td><span class="gs-status-planned">Needs gabochie@gmail.com</span></td></tr>
                        <tr><td>Setup</td><td>Create Bing Places for Business</td><td>Manual</td><td><span class="gs-status-planned">Needs manual setup</span></td></tr>
                        <tr><td>Setup</td><td>Apple Maps Connect listing</td><td>Manual</td><td><span class="gs-status-planned">Needs Apple ID</span></td></tr>
                        <tr><td>Setup</td><td>Add llms.txt for AI crawlers</td><td>AI + Dev</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Setup</td><td>Implement IndexNow for Bing instant indexing</td><td>AI + Dev</td><td><span class="gs-status-planned">Next sprint</span></td></tr>
                        <tr><td>Setup</td><td>Add hreflang tags (en-GH, en-US, en-GB)</td><td>AI + Dev</td><td><span class="gs-status-done">Skipped</span> — single-language site, not needed</td></tr>
                        <tr><td>Content</td><td>Write Pillar Post 1: Complete Building Cost Guide</td><td>AI Writer</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Content</td><td>Write Pillar Post 2: Diaspora Building Guide</td><td>AI Writer</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Content</td><td>Write Pillar Post 3: AI in Architecture</td><td>AI Writer</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Content</td><td>Write Pillar Post 4: Ghana Building Regulations</td><td>AI Writer</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Content</td><td>Write Pillar Post 5: Choosing an Architect</td><td>AI Writer</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Content</td><td>Expand all 21 existing posts to 1,500+ words</td><td>AI Writer</td><td><span class="gs-status-planned">Month 1-2</span></td></tr>
                        <tr><td>Content</td><td>Add FAQ sections to all key pages</td><td>AI + Dev</td><td><span class="gs-status-planned">Month 1</span></td></tr>
                        <tr><td>Content</td><td>Launch YouTube channel + first 4 videos</td><td>Manual</td><td><span class="gs-status-planned">Month 2</span></td></tr>
                        <tr><td>Content</td><td>Pinterest account + 20 pins</td><td>Manual</td><td><span class="gs-status-planned">Month 2</span></td></tr>
                        <tr><td>Authority</td><td>Guest post on GhanaWeb</td><td>Manual</td><td><span class="gs-status-planned">Month 2</span></td></tr>
                        <tr><td>Authority</td><td>Submit to 5 Ghana directories</td><td>Manual</td><td><span class="gs-status-planned">Month 1</span></td></tr>
                        <tr><td>Authority</td><td>Submit to 3 diaspora directories</td><td>Manual</td><td><span class="gs-status-planned">Month 1</span></td></tr>
                        <tr><td>Authority</td><td>Create LinkedIn company page</td><td>Manual</td><td><span class="gs-status-planned">Month 1</span></td></tr>
                        <tr><td>Authority</td><td>Collect 10 Google Business reviews</td><td>Manual</td><td><span class="gs-status-planned">Month 2-3</span></td></tr>
                        <tr><td>Technical</td><td>Add custom 404 page</td><td>AI + Dev</td><td><span class="gs-status-live">Done</span></td></tr>
                        <tr><td>Technical</td><td>Minify CSS/JS + defer render-blocking</td><td>AI + Dev</td><td><span class="gs-status-planned">Next sprint</span></td></tr>
                        <tr><td>Technical</td><td>Implement breadcrumb schema</td><td>AI + Dev</td><td><span class="gs-status-planned">Next sprint</span></td></tr>
                        <tr><td>Technical</td><td>Add video schema for embedded videos</td><td>AI + Dev</td><td><span class="gs-status-planned">When videos added</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private static function get_default_strategy() {
        return ''; // Using hardcoded content above
    }
}
