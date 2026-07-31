<?php
/**
 * Class BOQ_Suite_Public
 * Front-end shortcodes & rendering interface with boq2 dataset enrichment.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BOQ_Suite_Public {

    public function enqueue_public_assets() {
        wp_enqueue_style('boq-suite-public-css', BOQ_SUITE_URL . 'public/css/boq-suite-public.css', array(), BOQ_SUITE_VERSION);
        wp_enqueue_script('boq-suite-public-js', BOQ_SUITE_URL . 'public/js/boq-suite-public.js', array('jquery'), BOQ_SUITE_VERSION, true);

        wp_localize_script('boq-suite-public-js', 'boqSuiteVars', array(
            'restUrl' => esc_url_raw(rest_url('boq-suite/v1/')),
            'nonce'   => wp_create_nonce('wp_rest'),
        ));
    }

    public function register_shortcodes() {
        add_shortcode('boq_suite_estimator', array($this, 'render_estimator_shortcode'));
        add_shortcode('boq_suite_invoices', array($this, 'render_invoices_shortcode'));
        add_shortcode('boq_suite_jv_calculator', array($this, 'render_jv_shortcode'));
        add_shortcode('boq_suite_rate_search', array($this, 'render_rate_search_shortcode'));
        add_shortcode('boq_suite_importer', array($this, 'render_importer_shortcode'));
        add_shortcode('boq_suite_all_in_one', array($this, 'render_all_in_one_shortcode'));
    }

    // 1. BOQ Estimator Shortcode
    public function render_estimator_shortcode($atts) {
        ob_start();
        ?>
        <div class="boq-widget-card" id="boq-estimator-app">
            <div class="boq-widget-header">
                <h2>🏗️ Smart BOQ Cost Estimator & Material Take-Off</h2>
                <p>Generate SMM7 compliant itemized cost estimates based on real-world Quantity Surveying data.</p>
            </div>
            <form id="boq-calc-form">
                <div class="boq-form-grid">
                    <div class="boq-form-group">
                        <label>Project Type / Building Specification</label>
                        <select id="boq_project_type">
                            <option value="classroom_block">🏫 3-Unit Classroom Block + Staff Room & KVIP Toilet</option>
                            <option value="medical_center">🏥 Medical Center / Community Health Clinic</option>
                            <option value="market_place">🏪 Community Market Place Commercial Sheds</option>
                            <option value="rehab_renovation">🏛️ Public Building / Post Office Rehabilitation</option>
                            <option value="residential_villa">🏡 3-Bedroom Residential Villa</option>
                            <option value="fence_wall">🧱 Perimeter Fence & Retaining Blockwork Wall</option>
                            <option value="dampness_treatment">💧 Wall Anti-Dampness Remediation</option>
                        </select>
                    </div>
                    <div class="boq-form-group">
                        <label>Floor Area / Size (m² or Linear Meters)</label>
                        <input type="number" id="boq_area" value="250" min="10">
                    </div>
                    <div class="boq-form-group">
                        <label>Project Location</label>
                        <select id="boq_location">
                            <option value="Accra">Accra (Standard Base Rate)</option>
                            <option value="Kumasi">Kumasi (-4% Freight Adjustment)</option>
                            <option value="Tamale">Tamale (-2% Regional Factor)</option>
                            <option value="Takoradi">Takoradi (+3% Industrial Zone)</option>
                            <option value="Nkoranza">Nkoranza (-8% Inland Zone)</option>
                            <option value="Prampram">Prampram (+5% Freight/Coastal)</option>
                            <option value="North Legon">North Legon (+10% Premium Zone)</option>
                        </select>
                    </div>
                    <div class="boq-form-group">
                        <label>Currency</label>
                        <select id="boq_currency">
                            <option value="GHS">GH₵ - Ghana Cedi</option>
                            <option value="USD">$ - US Dollar</option>
                        </select>
                    </div>
                    <div class="boq-form-group">
                        <label>Material Price Escalation Buffer (%)</label>
                        <input type="number" id="boq_escalation" value="0" min="0" max="50">
                    </div>
                </div>
                <button type="button" id="btn-calculate-boq" class="boq-btn-primary">Calculate SMM7 Itemized BOQ</button>
            </form>

            <div id="boq-results-area" style="display:none; margin-top:25px;">
                <div class="boq-result-header">
                    <h3>ESTIMATED CONTRACT SUM: <span id="boq-total-display" class="boq-highlight">GH₵ 0.00</span></h3>
                    <button id="btn-export-boq-pdf" class="boq-btn-secondary">📥 Download SMM7 PDF Report</button>
                </div>
                <div id="boq-bills-container" style="margin-top:20px;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // 2. BOQ CSV/JSON Importer Shortcode
    public function render_importer_shortcode($atts) {
        ob_start();
        ?>
        <div class="boq-widget-card" id="boq-importer-app">
            <div class="boq-widget-header">
                <h2>📥 Excel / CSV BOQ Importer & Rate Converter</h2>
                <p>Upload or paste CSV BOQ lines to convert them into active project items and master rates.</p>
            </div>
            <form id="boq-import-form">
                <div class="boq-form-group">
                    <label>Paste CSV Content (Item Code, Description, Qty, Unit, Rate, Amount)</label>
                    <textarea id="boq_csv_input" rows="8" style="width:100%; font-family:monospace; padding:10px;" placeholder="Item,Description,Qty,Unit,Rate,Amount&#10;1.1,Excavation of trench,120,m3,18.00,2160.00&#10;1.2,Concrete C25 in foundation,25,m3,480.00,12000.00"></textarea>
                </div>
                <div class="boq-form-group">
                    <label><input type="checkbox" id="boq_save_to_rates" value="1" checked> Automatically add imported items to Master Rates Database</label>
                </div>
                <button type="button" id="btn-import-boq" class="boq-btn-primary">Parse & Import BOQ Data</button>
            </form>

            <div id="import-result-notice" style="display:none; margin-top:20px;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    // 3. QS Invoices Shortcode
    public function render_invoices_shortcode($atts) {
        ob_start();
        ?>
        <div class="boq-widget-card" id="boq-invoicing-app">
            <div class="boq-widget-header">
                <h2>🧾 Architect & QS Invoice Generator & Payment Portal</h2>
                <p>Create professional milestone invoices for client billing.</p>
            </div>
            <form id="qs-invoice-form">
                <div class="boq-form-grid">
                    <div class="boq-form-group">
                        <label>Client Name</label>
                        <input type="text" id="inv_client_name" placeholder="e.g. Mr. Alex Mensah">
                    </div>
                    <div class="boq-form-group">
                        <label>Client Email</label>
                        <input type="email" id="inv_client_email" placeholder="alex@example.com">
                    </div>
                    <div class="boq-form-group" style="grid-column: span 2;">
                        <label>Project Title</label>
                        <input type="text" id="inv_project_title" placeholder="Proposed 3-Unit Classroom Block Construction">
                    </div>
                </div>

                <h4>Milestone Line Items</h4>
                <div id="invoice-milestones-list">
                    <div class="boq-milestone-row">
                        <input type="text" class="ms-desc" placeholder="Stage 1: Substructure & Foundation Supervision" value="Stage 1: Substructure & Foundation Supervision">
                        <input type="number" class="ms-amount" placeholder="Amount (GHS)" value="5000">
                    </div>
                    <div class="boq-milestone-row">
                        <input type="text" class="ms-desc" placeholder="Stage 2: Superstructure & Roofing Inspection" value="Stage 2: Superstructure & Roofing Inspection">
                        <input type="number" class="ms-amount" placeholder="Amount (GHS)" value="7500">
                    </div>
                </div>
                <button type="button" id="btn-create-invoice" class="boq-btn-primary" style="margin-top:15px;">Generate Client Invoice</button>
            </form>

            <div id="invoice-created-notice" style="display:none; margin-top:20px;" class="boq-alert-success">
                <p><strong>Invoice Created Successfully!</strong></p>
                <p id="invoice-details-text"></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // 4. Real Estate JV Modeler Shortcode
    public function render_jv_shortcode($atts) {
        ob_start();
        ?>
        <div class="boq-widget-card" id="boq-jv-app">
            <div class="boq-widget-header">
                <h2>💼 Real Estate Joint Venture (JV) Financial Modeler</h2>
                <p>Compute equity splits and rental yield ROIs between Landowners and Developers.</p>
            </div>
            <form id="jv-calc-form">
                <div class="boq-form-grid">
                    <div class="boq-form-group">
                        <label>Land Valuation (GHS)</label>
                        <input type="number" id="jv_land_val" value="500000">
                    </div>
                    <div class="boq-form-group">
                        <label>Estimated Construction Cost (GHS)</label>
                        <input type="number" id="jv_const_cost" value="1200000">
                    </div>
                    <div class="boq-form-group" style="grid-column: span 2;">
                        <label>Projected Annual Rental Income (GHS)</label>
                        <input type="number" id="jv_annual_rent" value="180000">
                    </div>
                </div>
                <button type="button" id="btn-calculate-jv" class="boq-btn-primary">Compute JV Financial Model</button>
            </form>

            <div id="jv-results-area" style="display:none; margin-top:25px;">
                <div class="boq-jv-summary-grid">
                    <div class="boq-stat-card">
                        <span>Total Project Capital</span>
                        <h3 id="jv-total-cap">GHS 0.00</h3>
                    </div>
                    <div class="boq-stat-card">
                        <span>Landowner Equity Share</span>
                        <h3 id="jv-landowner-pct">0.00%</h3>
                    </div>
                    <div class="boq-stat-card">
                        <span>Developer Equity Share</span>
                        <h3 id="jv-developer-pct">0.00%</h3>
                    </div>
                    <div class="boq-stat-card">
                        <span>Gross Annual Rental Yield</span>
                        <h3 id="jv-gross-yield">0.00%</h3>
                    </div>
                    <div class="boq-stat-card">
                        <span>Developer Payback Period</span>
                        <h3 id="jv-payback">0 Years</h3>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // 5. Rate Search Shortcode
    public function render_rate_search_shortcode($atts) {
        ob_start();
        ?>
        <div class="boq-widget-card" id="boq-search-app">
            <div class="boq-widget-header">
                <h2>🔍 Historical Construction Unit Rate Search Engine</h2>
                <p>Search 50+ master unit rates for materials, labor, timber, re-bar, and specialized construction works.</p>
            </div>
            <div class="boq-form-grid" style="margin-bottom:15px;">
                <div class="boq-form-group">
                    <label>Keywords</label>
                    <input type="text" id="rate_search_input" placeholder="Type material (e.g. cement, timber, rebar, tiles, block)...">
                </div>
                <div class="boq-form-group">
                    <label>Category Filter</label>
                    <select id="rate_category_filter">
                        <option value="all">All Categories</option>
                        <option value="Substructure">Substructure & Concrete</option>
                        <option value="Materials">Basic Materials</option>
                        <option value="Steelwork">Steelwork & Re-Bar</option>
                        <option value="Masonry">Blockwork & Masonry</option>
                        <option value="Carpentry">Timber & Carpentry</option>
                        <option value="Roofing">Roofing & Sheets</option>
                        <option value="Finishes">Finishes & Waterproofing</option>
                        <option value="Plumbing">Plumbing & Pumps</option>
                        <option value="Sanitation">Sanitation & KVIP</option>
                    </select>
                </div>
                <div class="boq-form-group">
                    <label>Currency Display</label>
                    <select id="rate_search_currency">
                        <option value="GHS">GH₵ - Ghana Cedi</option>
                        <option value="USD">$ - US Dollar</option>
                    </select>
                </div>
            </div>
            <button type="button" id="btn-search-rates" class="boq-btn-primary" style="margin-bottom:20px;">Search Rates Database</button>

            <div id="rate-search-results">
                <table class="boq-table" id="rates-result-table">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Master Rate</th>
                            <th>Location Benchmark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="5" style="text-align:center;">Click Search or type a query above to view unit rates.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // 6. All in One Portal Shortcode
    public function render_all_in_one_shortcode($atts) {
        ob_start();
        ?>
        <div class="boq-suite-tabs-container">
            <div class="boq-tabs-nav">
                <button class="boq-tab-btn active" data-tab="tab-estimator">🏗️ BOQ Estimator</button>
                <button class="boq-tab-btn" data-tab="tab-importer">📥 BOQ Importer</button>
                <button class="boq-tab-btn" data-tab="tab-invoices">🧾 QS Invoices</button>
                <button class="boq-tab-btn" data-tab="tab-jv">💼 JV Modeler</button>
                <button class="boq-tab-btn" data-tab="tab-search">🔍 Rate Search</button>
            </div>
            <div class="boq-tab-content active" id="tab-estimator">
                <?php echo $this->render_estimator_shortcode($atts); ?>
            </div>
            <div class="boq-tab-content" id="tab-importer">
                <?php echo $this->render_importer_shortcode($atts); ?>
            </div>
            <div class="boq-tab-content" id="tab-invoices">
                <?php echo $this->render_invoices_shortcode($atts); ?>
            </div>
            <div class="boq-tab-content" id="tab-jv">
                <?php echo $this->render_jv_shortcode($atts); ?>
            </div>
            <div class="boq-tab-content" id="tab-search">
                <?php echo $this->render_rate_search_shortcode($atts); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
