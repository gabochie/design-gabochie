<?php
/**
 * Plugin Name: Gabochie Content Importer
 * Description: Imports all blog posts, pages, FAQ content, and settings from the original Gabochie Design site
 * Version: 1.0.0
 * Author: Gabochie Design Studio
 */

if (!defined('ABSPATH')) exit;

class Gabochie_Content_Importer {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_import'));
    }

    public function add_admin_menu() {
        add_management_page(
            'Gabochie Content Importer',
            'Import Content',
            'manage_options',
            'gabochie-importer',
            array($this, 'render_page')
        );
    }

    public function render_page() {
        $imported = get_option('gabochie_imported', false);
        ?>
        <div class="wrap">
            <h1>Gabochie Design Content Importer</h1>
            <p>Click the button below to import all blog posts, pages, FAQ content, and settings from the original site.</p>
            <?php if ($imported): ?>
                <div class="notice notice-success"><p>Content has already been imported. Click below to re-import.</p></div>
            <?php endif; ?>
            <form method="post">
                <?php wp_nonce_field('gabochie_import'); ?>
                <p><input type="submit" name="gabochie_run_import" class="button button-primary" value="Import All Content"></p>
            </form>
            <?php
            if (isset($_POST['gabochie_run_import']) && check_admin_referer('gabochie_import')) {
                $this->run_full_import();
                echo '<div class="notice notice-success"><p>Import complete!</p></div>';
            }
            ?>
        </div>
        <?php
    }

    public function handle_import() {}

    public function run_full_import() {
        $this->import_blog_posts();
        $this->import_pages();
        $this->import_faq();
        $this->import_settings();
        update_option('gabochie_imported', true);
    }

    /* ============================================
       BLOG POSTS
       ============================================ */
    public function import_blog_posts() {
        $posts = $this->get_blog_data();

        foreach ($posts as $post_data) {
            $existing = get_posts(array(
                'name' => $post_data['post_name'],
                'post_type' => 'post',
                'numberposts' => 1,
            ));

            if (!empty($existing)) {
                wp_update_post(array(
                    'ID' => $existing[0]->ID,
                    'post_content' => $post_data['post_content'],
                    'post_title' => $post_data['post_title'],
                ));
                update_post_meta($existing[0]->ID, '_gabochie_category', $post_data['category']);
                update_post_meta($existing[0]->ID, '_gabochie_read_time', $post_data['read_time']);
                update_post_meta($existing[0]->ID, '_thumbnail_id_attachment_url', $post_data['image']);
                $tags = explode(',', $post_data['tags']);
                wp_set_post_tags($existing[0]->ID, array_map('trim', $tags));
            } else {
                $post_id = wp_insert_post(array(
                    'post_title' => $post_data['post_title'],
                    'post_name' => $post_data['post_name'],
                    'post_content' => $post_data['post_content'],
                    'post_status' => 'publish',
                    'post_type' => 'post',
                    'post_date' => $post_data['date'],
                    'meta_input' => array(
                        '_gabochie_category' => $post_data['category'],
                        '_gabochie_read_time' => $post_data['read_time'],
                        '_thumbnail_id_attachment_url' => $post_data['image'],
                    ),
                ));
                $tags = explode(',', $post_data['tags']);
                wp_set_post_tags($post_id, array_map('trim', $tags));
            }
        }
    }

    private function get_blog_data() {
        return array(
            array(
                'post_title' => 'How To Know If That Accra Plot Is Worth the Price Before You Wire the Money',
                'post_name' => 'accra-plot-worth-price',
                'date' => '2025-03-03 12:00:00',
                'category' => 'tips',
                'read_time' => '10',
                'image' => 'https://images.unsplash.com/photo-1448630360428-65456885c650?w=900&quality=80',
                'tags' => 'Plot Valuation,Accra,Land Price,Investment,Ghana Real Estate',
                'post_content' => '<p>Before you commit to any plot in Accra, you need to know if the price is fair. Here is the valuation framework used by professionals.</p>
<h2>Location Analysis</h2>
<p>Compare the asking price to similar plots in the same area that have sold in the last 12 months. Prices can vary dramatically between adjacent streets.</p>
<h3>Accessibility</h3>
<p>How close is the plot to main roads, schools, hospitals, and commercial areas? Better access means higher value.</p>
<h3>Infrastructure</h3>
<p>Is there ready access to electricity, water, and drainage? Missing infrastructure adds significant cost to development.</p>
<h2>Zoning Verification</h2>
<p>Verify the zoning. Residential plots in commercial zones may have restrictions or opportunities that affect value.</p>
<h2>Development Potential</h2>
<p>Consider development potential. Larger plots in up-and-coming areas may appreciate faster than smaller plots in established neighborhoods.</p>
<h3>Neighborhood Trajectory</h3>
<p>Is the area improving or declining? Look for signs of new development, infrastructure improvements, or deterioration.</p>
<blockquote><p>Location is the primary driver of land value. Two plots 100 meters apart can have vastly different values based on accessibility and neighborhood.</p></blockquote>
<h2>Price Per Square Meter</h2>
<p>Calculate the price per square meter and compare to neighborhood averages. Plots priced significantly above average need justification.</p>',
            ),
            array(
                'post_title' => 'How To Budget for a Ghana Build When You Are Paid in Pounds or Dollars',
                'post_name' => 'budget-ghana-build-pounds-dollars',
                'date' => '2025-03-06 12:00:00',
                'category' => 'tips',
                'read_time' => '10',
                'image' => 'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?w=900&quality=80',
                'tags' => 'Currency,Budget,Finance,GBP,USD,Ghana Cedis',
                'post_content' => '<p>Building in Ghana while earning in pounds or dollars requires a currency strategy. The cedi fluctuates. Costs change. What you budgeted last year may not cover this years costs.</p>
<h2>The Currency Strategy</h2>
<h3>Always Budget in Cedis</h3>
<p>Get quotes and estimates in GHS, not foreign currency. This gives you a consistent baseline for comparison.</p>
<h3>Build in Contingency</h3>
<p>Build in a 20-30% contingency for currency fluctuations. The cedi can move significantly against major currencies in a short period.</p>
<h3>Open a Ghana Bank Account</h3>
<p>Maintain a Ghana bank account and reserve. This allows you to exchange at favorable rates and avoid repeated wire transfer fees.</p>
<h3>Establish a Transfer Schedule</h3>
<p>Regular monthly transfers during construction help smooth out currency fluctuations. Avoid large lump-sum transfers when possible.</p>
<h3>Work with a Local Accountant</h3>
<p>Work with a local accountant who understands diaspora finances. They can help optimize your tax position and currency conversion strategy.</p>
<blockquote><p>Currency management is not about predicting the market. It is about protecting yourself against its movements.</p></blockquote>
<h2>Typical Budget Components</h2>
<ul>
<li>Land acquisition: varies by location</li>
<li>Architectural design: 5-10% of construction cost</li>
<li>Construction: 60-70% of total budget</li>
<li>Contingency: 15-20% of construction</li>
<li>Legal and registration: 3-5% of land plus construction</li>
<li>Furnishings: 10-15% of construction if applicable</li>
</ul>',
            ),
            array(
                'post_title' => 'How To Build a House in Ghana Without Quitting Your Job in London',
                'post_name' => 'build-house-ghana-without-quitting-london-job',
                'date' => '2025-03-13 12:00:00',
                'category' => 'diaspora',
                'read_time' => '11',
                'image' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=900&quality=80',
                'tags' => 'Remote Building,Diaspora Building,London to Ghana,Project Management,Ghana Construction',
                'post_content' => '<p>You have a demanding job in London. You can\'t take months off to supervise construction in Accra. But you also can\'t wait until retirement to build in Ghana. What do you do?</p>
<p>The answer: build a remote project management system that doesn\'t require your physical presence. Thousands of diaspora Ghanaians have done it. Here\'s exactly how.</p>
<h2>The Core Principle: You Need an On-Ground Team</h2>
<p>Remote building management doesn\'t mean managing from afar alone. It means assembling a reliable team in Ghana who can act as your eyes, ears, and decision-makers on the ground.</p>
<p>Your core team should include:</p>
<ul>
<li><strong>A project manager or clerk of works:</strong> Someone who visits the site daily</li>
<li><strong>A contractor:</strong> The construction crew</li>
<li><strong>An architect:</strong> To verify work matches designs</li>
<li><strong>A quantity surveyor:</strong> To track costs and payments</li>
</ul>
<h2>The Communication System</h2>
<h3>Daily Updates via WhatsApp</h3>
<p>Set up a WhatsApp group with your entire team. Your project manager should send:</p>
<ul>
<li>Photos of the day\'s progress every evening</li>
<li>Brief voice notes explaining what\'s happening</li>
<li>Immediate alerts for any issues or decisions needed</li>
</ul>
<p>This takes 10 minutes a day from your project manager but gives you complete visibility.</p>
<h3>Weekly Video Calls</h3>
<p>Schedule a 30-minute video call every Sunday evening (Ghana time works well for UK evenings). Cover:</p>
<ul>
<li>What was completed this week</li>
<li>What\'s planned for next week</li>
<li>Any decisions or approvals you need to make</li>
<li>Budget updates</li>
</ul>
<h3>Milestone Inspections</h3>
<p>At each major milestone—foundation, ring beam, roofing, plastering, finishes—require:</p>
<ul>
<li>A video walkthrough of the completed work</li>
<li>Photos from multiple angles</li>
<li>Your architect\'s written sign-off</li>
<li>A cost breakdown for the completed stage</li>
</ul>
<h2>The Documentation System</h2>
<p>Every decision, payment, and change should be documented in writing. Use:</p>
<ul>
<li><strong>Shared Google Drive folder:</strong> For all documents, photos, and plans</li>
<li><strong>Signed change orders:</strong> Any scope changes documented and signed by both parties</li>
<li><strong>Payment receipts:</strong> Photos of every receipt and bank transfer confirmation</li>
<li><strong>Progress reports:</strong> Weekly written summaries from your project manager</li>
</ul>
<blockquote><p>"The documentation is your protection. If something goes wrong, you need a paper trail. If everything goes right, you have a record for the next time."</p></blockquote>
<h2>The Payment System</h2>
<p>Never pay large sums upfront. Structure payments around completed milestones:</p>
<ul>
<li><strong>Contractor mobilization:</strong> 10-15% to start work</li>
<li><strong>Foundation complete:</strong> 20%</li>
<li><strong>Structural frame complete:</strong> 25%</li>
<li><strong>Roofing and external works:</strong> 20%</li>
<li><strong>Internal finishes:</strong> 20%</li>
<li><strong>Final handover:</strong> 10% after snagging list complete</li>
</ul>
<p>Each payment should be preceded by your architect\'s verification that the work is complete.</p>
<h2>How Many Trips Do You Actually Need?</h2>
<p>Even with excellent remote management, you should plan for 3-4 visits:</p>
<ul>
<li><strong>Visit 1:</strong> Land purchase and initial architect meetings (3-5 days)</li>
<li><strong>Visit 2:</strong> Design approval and contractor selection (5-7 days)</li>
<li><strong>Visit 3:</strong> Mid-construction inspection (3-5 days)</li>
<li><strong>Visit 4:</strong> Final inspection and handover (3-5 days)</li>
</ul>
<h2>The Biggest Mistakes to Avoid</h2>
<ul>
<li><strong>Hiring based on price alone:</strong> Cheapest contractor is often most expensive in the long run</li>
<li><strong>No written contracts:</strong> Always have detailed contracts with penalties for delays</li>
<li><strong>Skipping the architect:</strong> You need someone to verify quality, not just your contractor</li>
<li><strong>Micromanaging small decisions:</strong> Trust your team for day-to-day choices</li>
<li><strong>Ignoring warning signs:</strong> If something feels wrong early, address it immediately</li>
</ul>
<h2>The Bottom Line</h2>
<p>Building in Ghana from London is absolutely possible. It requires upfront investment in the right team, clear communication systems, and disciplined documentation. But thousands of diaspora Ghanaians have done it successfully—and you can too.</p>
<p>The key is taking the time to set up systems before construction starts. Once you\'re building, you want to be managing, not problem-solving.</p>',
            ),
            array(
                'post_title' => 'How To Buy Land in Ghana From the UK Without Ever Being Cheated',
                'post_name' => 'buy-land-in-ghana-from-uk',
                'date' => '2025-03-15 12:00:00',
                'category' => 'diaspora',
                'read_time' => '12',
                'image' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=900&quality=80',
                'tags' => 'Buying Land Ghana,Diaspora,Land Scams,Ghana Property,UK to Ghana',
                'post_content' => '<p>Every week, we hear from Ghanaian diaspora buyers in London, Manchester, or Birmingham who found what looked like the perfect plot—only to discover months later that the land was sold multiple times, the title was fake, or the seller had no legal right to sell.</p>
<p>Buying land in Ghana from abroad isn\'t inherently risky. But without the right process, you\'re vulnerable. This guide gives you the exact steps that protect you—verified by lawyers, tested by diaspora buyers who\'ve gone before you.</p>
<h2>Why Diaspora Buyers Are Prime Targets</h2>
<p>Scammers target diaspora buyers for one reason: you\'re disconnected. You can\'t verify information in person. You rely on photos, WhatsApp messages, and trust. And desperate sellers or fraudsters exploit exactly that.</p>
<p>The most common scams include:</p>
<ul>
<li><strong>Double-selling:</strong> The same plot sold to two or more buyers</li>
<li><strong>Ghost land:</strong> Land that doesn\'t exist or belongs to someone else entirely</li>
<li><strong>Fake indenture:</strong> Forged land documents that look legitimate</li>
<li><strong>Family disputes:</strong> Land sold by one family member without consent from others</li>
</ul>
<h2>The Step-by-Step Process</h2>
<h3>Step 1: Hire a Ghana-Based Property Lawyer Before Anything Else</h3>
<p>This is non-negotiable. You need a qualified property lawyer (not a family friend, not a "connection") who specializes in land transactions. They should be licensed by the Ghana Bar Association and have experience with diaspora clients.</p>
<p>Your lawyer\'s job is to conduct due diligence, not just review documents. Make sure they actually visit the land office.</p>
<h3>Step 2: Get the Seller\'s Documents—All of Them</h3>
<p>Request:</p>
<ul>
<li>The original indenture (land title document)</li>
<li>Proof of identity for the seller (Ghana Card/passport)</li>
<li>Land Commission search results (more on this in Step 3)</li>
<li>Any inheritance documents if the land was passed down</li>
<li>Receipts showing property rate payments to the District Assembly</li>
</ul>
<h3>Step 3: Conduct an Official Lands Commission Search</h3>
<p>Your lawyer should visit or file online with the Lands Commission to verify:</p>
<ul>
<li>The registered owner matches the seller</li>
<li>There are no encumbrances (liens, caveats, or competing claims)</li>
<li>The land use type matches what you\'re buying it for</li>
</ul>
<blockquote><p>"Never wire money without a Lands Commission search. Not for any reason. Not even if the seller is a cousin."</p></blockquote>
<h3>Step 4: Physically Verify the Land Boundaries</h3>
<p>You might be 5,000 miles away, but this step is critical. Use one of these approaches:</p>
<ul>
<li><strong>Send a trusted representative:</strong> A family member or close friend who can physically go to the land</li>
<li><strong>Use a site inspection service:</strong> Some property management companies offer this for diaspora clients</li>
<li><strong>Request a GPS survey:</strong> A licensed surveyor can mark the exact boundaries</li>
</ul>
<h3>Step 5: Negotiate and Structure the Payment</h3>
<p>Never pay the full amount upfront. Structure payments in stages:</p>
<ul>
<li>Reservation deposit (10-15%) — after due diligence passes</li>
<li>Second payment (40-50%) — at signing</li>
<li>Final payment (35-50%) — upon registration transfer</li>
</ul>
<p>All payments should be made to a law firm trust account, not directly to the seller.</p>
<h3>Step 6: Transfer the Title to Your Name</h3>
<p>The land doesn\'t belong to you until it\'s officially registered in your name at the Lands Commission. Your lawyer handles this, but you should follow up. Request a copy of the updated land register showing your name.</p>
<h2>Red Flags That Should Stop You Immediately</h2>
<ul>
<li>Seller pressures you to pay quickly "before someone else buys it"</li>
<li>They refuse to provide original documents or make excuses</li>
<li>The price is significantly below market value</li>
<li>They claim "family land" doesn\'t need documentation</li>
<li>They\'re unwilling to involve a lawyer</li>
</ul>
<h2>What This Actually Costs</h2>
<p>Beyond the land price, budget for:</p>
<ul>
<li>Legal fees: 2-5% of land value</li>
<li>Lands Commission registration: varies by location and value</li>
<li>Stamp duty: 0.5% of land value</li>
<li>Survey fees: GHS 2,000-5,000 depending on plot size</li>
<li>Due diligence: GHS 3,000-8,000</li>
</ul>
<h2>The Bottom Line</h2>
<p>Buying land in Ghana from the UK is entirely possible—and safe—if you follow the process. The key is simple: never skip verification, always use a lawyer, and never rush because someone is pressuring you.</p>
<p>Take your time. The right land will still be there when you\'ve done your homework.</p>',
            ),
            array(
                'post_title' => 'How To Choose the Right Neighbourhood in Accra for Your Lifestyle and Budget',
                'post_name' => 'choose-neighbourhood-accra',
                'date' => '2025-02-28 12:00:00',
                'category' => 'tips',
                'read_time' => '12',
                'image' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=900&quality=80',
                'tags' => 'Neighborhood,East Legon,Adenta,Accra,Relocation,Location',
                'post_content' => '<p>Choosing where to live in Accra affects everything from your daily commute to your property investment returns. Here is a comparison of popular areas.</p>
<h2>East Legon</h2>
<h3>The Premium Choice</h3>
<p>Established, expensive, and convenient. Expect to pay premium prices for land and rentals.</p>
<h3>Benefits</h3>
<ul>
<li>Good roads and infrastructure</li>
<li>Restaurants, shops, and amenities</li>
<li>International schools nearby</li>
<li>Generally excellent security</li>
</ul>
<h3>Considerations</h3>
<p>Best for families who prioritize convenience and are willing to pay for it. Land prices can exceed GHS 1 million per plot.</p>
<h2>Adenta</h2>
<h3>The Emerging Alternative</h3>
<p>More affordable and still developing. Housing options range from modest to comfortable.</p>
<h3>Benefits</h3>
<ul>
<li>More affordable land and housing</li>
<li>Growing amenities and schools</li>
<li>More community feel</li>
</ul>
<h3>Considerations</h3>
<p>Access to some areas can be challenging during rush hour. Infrastructure development is ongoing.</p>
<h2>Other Areas to Consider</h2>
<ul>
<li>Tema: Waterfront living, industrial proximity</li>
<li>Airport Residential Area: Convenient, established</li>
<li>Tema Community: Strong community feel</li>
<li>Spintex: Growing commercial hub</li>
</ul>
<blockquote><p>The best neighborhood is one that matches your priorities. Convenience costs money. Balance your needs against your budget.</p></blockquote>',
            ),
            array(
                'post_title' => 'How To Find a Trustworthy Property Lawyer in Ghana as a Diaspora Buyer',
                'post_name' => 'find-property-lawyer-ghana',
                'date' => '2025-03-05 12:00:00',
                'category' => 'tips',
                'read_time' => '8',
                'image' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=900&quality=80',
                'tags' => 'Property Lawyer,Legal,Diaspora,Ghana Lawyer,Due Diligence',
                'post_content' => '<p>Your property lawyer is your most important ally when buying land in Ghana. But how do you find one you can trust?</p>
<h2>Finding the Right Lawyer</h2>
<h3>Get Referrals</h3>
<p>Start by getting referrals from people who have successfully completed property transactions. Personal recommendations are gold.</p>
<h3>Questions to Ask</h3>
<p>Once you have names, ask these questions:</p>
<ul>
<li>How many diaspora clients have you represented in the last year?</li>
<li>What is your experience with Lands Commission procedures?</li>
<li>Can you provide references from recent clients?</li>
<li>What are your fees and how are they structured?</li>
<li>What will you personally handle versus delegate?</li>
</ul>
<h3>Red Flags</h3>
<p>A good lawyer will be patient with your questions and provide clear answers. Red flags include:</p>
<ul>
<li>Reluctance to provide references</li>
<li>Vague fee structures</li>
<li>Pressure to skip due diligence steps</li>
<li>No experience with diaspora clients</li>
</ul>
<blockquote><p>A good lawyer will tell you when a deal is bad. A great lawyer will save you from a bad deal.</p></blockquote>
<h2>Fee Structure</h2>
<p>Property lawyers in Ghana typically charge 2-5% of the transaction value, plus out-of-pocket expenses. Be wary of lawyers who quote significantly below market rates.</p>',
            ),
            array(
                'post_title' => 'How To Get Your Ghana Build Back on Track After Years of Stalling',
                'post_name' => 'get-ghana-build-back-on-track',
                'date' => '2025-03-09 12:00:00',
                'category' => 'tips',
                'read_time' => '11',
                'image' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=900&quality=80',
                'tags' => 'Stalled Build,Building Rescue,Ghana Construction,Project Recovery',
                'post_content' => '<p>You started building years ago. Then life happened. Money ran out. Trust was broken. The project stalled. Now you are looking at a half-finished structure and wondering if you should continue or walk away.</p>
<h2>The Rescue Plan</h2>
<h3>Step 1: Honest Assessment</h3>
<p>Document everything. Take photos, gather all contracts, receipts, and communications. Know exactly where you stand.</p>
<h3>Step 2: Structural Evaluation</h3>
<p>Get a structural assessment. Is the existing work sound? Has the partial structure been affected by weather and time? This determines if continuation is even viable.</p>
<h3>Step 3: Budget Reassessment</h3>
<p>Costs have likely increased since you started. Get fresh quotes for completing the work. Budget 20-30% more than initial estimates.</p>
<h3>Step 4: Decide on Contractor</h3>
<p>Sometimes the relationship is too damaged. Decide if you want to continue with the same contractor or start fresh. New contractors may charge more to take over, but may deliver better.</p>
<h3>Step 5: Phased Approach</h3>
<p>A phased approach often works best for stalled projects. Start with essential structural work before moving to finishes.</p>
<blockquote><p>Every stalled project can be rescued. The question is whether the cost of completion is worth the investment.</p></blockquote>
<h2>Is It Worth Continuing?</h2>
<p>Calculate the total cost to complete versus the current value of the property if finished. If completion costs exceed 70% of finished value, consider selling the incomplete structure and starting fresh elsewhere.</p>',
            ),
            array(
                'post_title' => 'How To Get Your Ghana House Built in 24 Months Not 10 Years',
                'post_name' => 'ghana-house-built-24-months',
                'date' => '2025-03-02 12:00:00',
                'category' => 'tips',
                'read_time' => '11',
                'image' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=900&quality=80',
                'tags' => 'Timeline,Building,Construction,Phased Build,Project Management',
                'post_content' => '<p>The average self-built home in Ghana takes 7-10 years. Yours does not have to. The phased construction approach works.</p>
<h2>The Phased Timeline</h2>
<h3>Phase 1: Site Preparation and Foundation</h3>
<p>Typically 2-3 months. Includes site clearing, setting out, and foundation construction.</p>
<h3>Phase 2: Structural Frame</h3>
<p>Another 2-3 months. Complete load-bearing elements including columns, beams, and floor slabs.</p>
<h3>Phase 3: Roof and External Works</h3>
<p>1-2 months. Roof structure, roofing material, and external wall finishing to roof level.</p>
<h3>Phase 4: First Fix and Plastering</h3>
<p>2-3 months. Electrical rough-in, plumbing, and internal wall plastering.</p>
<h3>Phase 5: Second Fix and Finishes</h3>
<p>3-4 months. Door and window installation, painting, tiling, fixtures, and fittings.</p>
<h3>Phase 6: External Landscaping and Handover</h3>
<p>1 month. Driveway, landscaping, final cleaning, and handover.</p>
<h2>Total Timeline</h2>
<p>11-16 months for construction alone. Add 2-3 months for planning and design. Add 1-2 months for permits. Your total should be 14-21 months from start to finish.</p>
<blockquote><p>Having your full budget in place before breaking ground is the single biggest factor in completing on time.</p></blockquote>
<h2>Why Projects Stall</h2>
<ul>
<li>Running out of money mid-construction</li>
<li>Contractor performance issues</li>
<li>Scope changes during construction</li>
<li>Lack of project management oversight</li>
<li>Permit or documentation delays</li>
</ul>',
            ),
            array(
                'post_title' => 'How To Do a Full Ghana Property Due Diligence Check in 14 Days',
                'post_name' => 'ghana-property-due-diligence',
                'date' => '2025-02-25 12:00:00',
                'category' => 'tips',
                'read_time' => '13',
                'image' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=900&quality=80',
                'tags' => 'Due Diligence,Checklist,14 Days,Land Purchase,Verification',
                'post_content' => '<p>Two weeks is enough time to verify a Ghana property thoroughly. Here is the day-by-day checklist.</p>
<h2>The 14-Day Plan</h2>
<h3>Days 1-2: Document Collection</h3>
<p>Gather all documents from seller. Indenture, ID, Lands Commission search, receipts. Without documents, you cannot proceed.</p>
<h3>Days 3-4: Official Lands Commission Search</h3>
<p>Your lawyer conducts official search. This verifies the registered owner and any encumbrances.</p>
<h3>Days 5-6: Identity Verification</h3>
<p>Verify seller identity. Confirm name matches exactly. Any discrepancy needs explanation.</p>
<h3>Days 7-8: Site Inspection</h3>
<p>Send someone physically to verify land exists and boundaries match. Photos and GPS coordinates required.</p>
<h3>Days 9-10: District Assembly Check</h3>
<p>Verify zoning and development plans. Is the land in an area approved for your intended use?</p>
<h3>Days 11-12: Utility Company Check</h3>
<p>Verify with utility companies for future infrastructure plans. Are roads, power lines, or drainage planned through the plot?</p>
<h3>Days 13-14: Compile and Decide</h3>
<p>Compile report and make decision. If anything is unclear, do not proceed.</p>
<blockquote><p>This timeline assumes you have a competent lawyer and someone reliable on the ground. Without these, you cannot complete due diligence properly.</p></blockquote>
<h2>Due Diligence Costs</h2>
<p>Budget GHS 5,000-15,000 for professional due diligence. This is not the place to cut costs.</p>',
            ),
            array(
                'post_title' => 'How To Handle Contractor Payments in Ghana Without Getting Robbed',
                'post_name' => 'handle-contractor-payments-ghana',
                'date' => '2025-02-26 12:00:00',
                'category' => 'tips',
                'read_time' => '9',
                'image' => 'https://images.unsplash.com/photo-1621905252507-b35492cc74b4?w=900&quality=80',
                'tags' => 'Payments,Contractor,Milestones,Finance,Building',
                'post_content' => '<p>Contractor payments are where most people get scammed in Ghana construction. Here is how to protect yourself with a milestone-based payment structure.</p>
<h2>The Golden Rules</h2>
<h3>Never Pay More Than 15% Upfront</h3>
<p>This should only cover initial mobilization and materials. Anything more is too much risk.</p>
<h3>Pay Only for Verified Work</h3>
<p>Before each payment, verify the work. Your architect should inspect and confirm. Get photos and a written report.</p>
<h3>Never Pay Based on Promises</h3>
<p>Only pay for completed, verified work. Verbal assurances are worthless without documentation.</p>
<h2>Milestone Payment Structure</h2>
<ul>
<li>Mobilization: 10-15%</li>
<li>Foundation complete: 20%</li>
<li>Structural frame complete: 25%</li>
<li>Roof complete: 15%</li>
<li>Finishes complete: 15%</li>
<li>Final handover: 10-15% after defects period</li>
</ul>
<h2>Keep 10% Until After Defects Period</h2>
<p>This gives you leverage to ensure the contractor returns to fix any issues. Release it only after all snagging is complete.</p>
<h3>Documentation Required</h3>
<p>Photos, signed approvals, receipts, bank transfer confirmations. These are your protection. Keep everything.</p>
<blockquote><p>If a contractor refuses milestone payments, walk away. No legitimate contractor needs full payment upfront.</p></blockquote>',
            ),
            array(
                'post_title' => 'How To Hire a Contractor in Ghana Who Will Actually Finish the Job',
                'post_name' => 'hire-contractor-in-ghana',
                'date' => '2025-03-11 12:00:00',
                'category' => 'tips',
                'read_time' => '10',
                'image' => 'https://images.unsplash.com/photo-1621905252507-b35492cc74b4?w=900&quality=80',
                'tags' => 'Contractor,Building,Ghana Construction,Hiring,Vetting',
                'post_content' => '<p>You have heard the horror stories. Projects that drag on for years. Contractors who disappear mid-build. Payments made but work not done. This guide gives you the exact framework to hire a contractor who will actually finish your Ghana build.</p>
<h2>The Vetting Process</h2>
<p>The vetting process starts before you even meet a contractor.</p>
<h3>Check Their Portfolio</h3>
<p>Ask to visit 3-4 completed projects. Not photos, actual visits. Talk to the homeowners. Ask about timeline adherence, quality, and whether the contractor resolved issues after completion.</p>
<h3>Verify Business Registration</h3>
<p>A legitimate contractor should have a registered business, not just operate as a one-man band. Ask for their registration documents.</p>
<h3>Check for Lawsuits</h3>
<p>Ask around. Have they been taken to court by previous clients? A good contractor will have satisfied clients, not lawsuits.</p>
<h3>Get Detailed Pricing</h3>
<p>A good contractor will provide a written breakdown, not just a lump sum. Vague pricing often hides problems.</p>
<h3>Trust Your Instincts</h3>
<p>If something feels off during meetings, it probably is. The right contractor will be patient with your questions, provide references, and put everything in writing.</p>
<blockquote><p>Finding the right contractor is the most important decision you will make in your Ghana build. Take your time with this step.</p></blockquote>
<h2>Red Flags During Vetting</h2>
<ul>
<li>Reluctance to provide references</li>
<li>Vague or oral agreements</li>
<li>Pressure to pay large sums upfront</li>
<li>Unwillingness to sign written contracts</li>
<li>No physical office or business presence</li>
</ul>',
            ),
            array(
                'post_title' => 'How To Move Back to Ghana With Children and Not Regret It',
                'post_name' => 'move-back-to-ghana-with-children',
                'date' => '2025-03-08 12:00:00',
                'category' => 'diaspora',
                'read_time' => '12',
                'image' => 'https://images.unsplash.com/photo-1583468323330-9032ad490fed?w=900&quality=80',
                'tags' => 'Relocation,Family,Moving to Ghana,Children,Diaspora',
                'post_content' => '<p>Moving back to Ghana with children is one of the biggest decisions your family will make. Done right, it can be transformative. Done wrong, it can be disruptive for everyone.</p>
<h2>The Relocation Checklist</h2>
<h3>Education First</h3>
<p>Research schools thoroughly. International schools offer continuity but at premium cost. Local schools offer immersion but require adjustment. Consider which system aligns with your long-term plans for your children.</p>
<h3>Healthcare Setup</h3>
<p>Register with a good hospital before you need it. Identify specialists for any ongoing conditions. Build relationships with healthcare providers early.</p>
<h3>Housing Location</h3>
<p>Choose a location with good schools, healthcare, and amenities. Do not sacrifice convenience for prestige. Your daily quality of life matters more than neighborhood status.</p>
<h3>Build Your Support Network</h3>
<p>Grandparents, extended family, and community connections make the transition smoother for everyone. Leverage these relationships.</p>
<h3>Plan for Adjustment</h3>
<p>Children will adjust, but it takes time. Plan for a 6-12 month adjustment period. Be patient with behavioral changes and homesickness.</p>
<blockquote><p>The best predictor of successful relocation is family support. The more connected your family is to Ghana, the smoother the transition.</p></blockquote>
<h2>Common Regrets to Avoid</h2>
<ul>
<li>Choosing prestige over convenience in housing</li>
<li>Not researching schools thoroughly enough</li>
<li>Isolating from extended family</li>
<li>Expecting instant adjustment</li>
<li>Not maintaining connections to childrens friends and activities abroad</li>
</ul>',
            ),
            array(
                'post_title' => 'How To Navigate the Lands Commission Process Without Losing Your Mind',
                'post_name' => 'navigate-lands-commission',
                'date' => '2025-03-01 12:00:00',
                'category' => 'tips',
                'read_time' => '10',
                'image' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=900&quality=80',
                'tags' => 'Lands Commission,Process,Registration,Land Title,Ghana',
                'post_content' => '<p>The Lands Commission process in Ghana is bureaucratic, slow, and often confusing. Here is how to navigate it without losing your mind.</p>
<h2>What the Lands Commission Does</h2>
<p>They handle land registration, which is how you get legal proof of ownership. This is the official record that matters in court.</p>
<h2>Two Main Document Types</h2>
<h3>Land Title Certificate</h3>
<p>Proves absolute ownership. For land that has been properly registered and surveyed.</p>
<h3>Registered Instrument</h3>
<p>For land that was previously unregistered. More complex process but still valid proof of ownership.</p>
<h2>Reality of the Process</h2>
<h3>Multiple Visits Required</h3>
<p>The process is not fully digital yet. You or your lawyer will need to physically visit the office multiple times.</p>
<h3>Processing Time</h3>
<p>Official processing can take 3-6 months for registered land and 12-18 months for unregistered land. Be prepared to wait.</p>
<h3>Keep Copies</h3>
<p>Submit documents, get receipts, follow up relentlessly. Documentation of your submissions is your protection.</p>
<blockquote><p>Patience and persistence are your best tools. The system works, but on its own timeline.</p></blockquote>
<h2>What You Can Do</h2>
<ul>
<li>Engage a lawyer who knows the process</li>
<li>Follow up weekly on your application</li>
<li>Keep all receipts and acknowledgments</li>
<li>Build relationships with Lands Commission staff</li>
<li>Expect delays and plan accordingly</li>
</ul>',
            ),
            array(
                'post_title' => 'How To Protect Your Ghana Property From Family Land Disputes',
                'post_name' => 'protect-ghana-property-family-disputes',
                'date' => '2025-03-07 12:00:00',
                'category' => 'tips',
                'read_time' => '9',
                'image' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=900&quality=80',
                'tags' => 'Land Disputes,Family Land,Legal Protection,Ghana Property,Inheritance',
                'post_content' => '<p>Family land disputes are one of the most common causes of property loss in Ghana. A piece of land that seemed clearly yours can become contested when relatives emerge with claims.</p>
<h2>Prevention Strategies</h2>
<h3>1. Proper Registration</h3>
<p>Ensure your land is properly registered in your name at the Lands Commission. This creates legal evidence of ownership that is difficult to challenge.</p>
<h3>2. Document the Acquisition</h3>
<p>Keep records of payments, agreements, and any family discussions about the land. Documentation is your best defense.</p>
<h3>3. Register with Traditional Authority</h3>
<p>Register the land with the appropriate traditional authority. This creates multiple layers of evidence supporting your ownership.</p>
<h3>4. Create a Will</h3>
<p>Consider a will that clearly specifies the land should pass to your direct heirs, not be shared among extended family.</p>
<h3>5. Maintain Family Relationships</h3>
<p>Many disputes start from poor communication and feelings of exclusion. Keep family members informed and involved where appropriate.</p>
<blockquote><p>An ounce of prevention is worth a pound of cure. Proper documentation and registration cost little compared to legal battles.</p></blockquote>
<h2>If a Dispute Arises</h2>
<p>If a dispute does arise, engage a property lawyer immediately. Do not attempt to handle family disputes without professional legal guidance.</p>',
            ),
            array(
                'post_title' => 'How To Relocate to Ghana and Replace Your UK Salary Within 12 Months',
                'post_name' => 'relocate-ghana-replace-uk-salary',
                'date' => '2025-02-27 12:00:00',
                'category' => 'diaspora',
                'read_time' => '11',
                'image' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=900&quality=80',
                'tags' => 'Relocation,Income,Remote Work,Career,Ghana Career',
                'post_content' => '<p>Replacing your UK salary in Ghana is challenging but possible. Here are strategies that have worked for others.</p>
<h2>Income Strategies</h2>
<h3>1. Leverage Remote Work</h3>
<p>Many UK and international companies allow employees to work from anywhere. Negotiate to keep your current salary while reducing your cost of living.</p>
<h3>2. Start a Consulting Business</h3>
<p>Your UK experience is valuable in Ghana. Offer services to UK companies looking to understand the African market.</p>
<h3>3. Invest in Income Property</h3>
<p>Rental income can supplement or replace employment income over time. Property investment provides passive income streams.</p>
<h3>4. Teaching and Training</h3>
<p>International schools and universities pay well and offer stable employment. Your international experience is highly valued.</p>
<h3>5. Build Multiple Streams</h3>
<p>A combination of remote work, consulting, and property income is more sustainable than relying on one source.</p>
<h2>Timeline Expectations</h2>
<p>Month 1-3: Establish remote work arrangements or consulting clients.</p>
<p>Month 4-6: Build property income or secondary income streams.</p>
<p>Month 7-12: Optimize and scale income streams.</p>
<blockquote><p>The key is not replacing your salary. It is replacing your purchasing power. Your Ghana lifestyle will cost less than your UK lifestyle.</p></blockquote>
<h2>Realistic Expectations</h2>
<p>Do not expect to match your UK salary in Ghana currency terms. Instead, focus on maintaining your purchasing power.</p>',
            ),
            array(
                'post_title' => 'How To Spot a Fake Ghana Property Document Before You Sign Anything',
                'post_name' => 'spot-fake-ghana-property-documents',
                'date' => '2025-03-12 12:00:00',
                'category' => 'tips',
                'read_time' => '9',
                'image' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=900&quality=80',
                'tags' => 'Document Fraud,Fake Documents,Land Scams,Ghana Property,Due Diligence',
                'post_content' => '<p>Every year, diaspora Ghanaians lose thousands of pounds to forged property documents. These documents look legitimate. They feel official. But they are fake. This guide gives you the exact red flags to watch for.</p>
<h2>The 7 Document Red Flags</h2>
<h3>1. No Lands Commission Registration Number</h3>
<p>Any legitimate land document should have a registration number from the Lands Commission. If the document has no number, or if the number format looks wrong, that is your first red flag.</p>
<h3>2. Mismatched Names</h3>
<p>The seller name on the document must exactly match their ID. Not similar, exactly. A middle name missing, a spelling difference, even a nickname can indicate a problem.</p>
<h3>3. Suspiciously Low Price</h3>
<p>If the land is priced significantly below market value, ask why. Fraudsters use low prices to create urgency and bypass careful examination.</p>
<h3>4. Copies Instead of Originals</h3>
<p>Always insist on seeing original documents. Copies can be manipulated. If the seller can only provide copies, be very cautious.</p>
<h3>5. Handwritten Amendments</h3>
<p>Official land documents should not have handwritten changes. Any alterations should be stamped and countersigned. Random marks or white-out are serious red flags.</p>
<h3>6. Wrong Land Use Classification</h3>
<p>The document should clearly state the land use. If it says agricultural but you are buying for residential, there is a problem that needs explaining.</p>
<h3>7. Stamp Duty Evidence Missing</h3>
<p>All Ghana land documents must have evidence of stamp duty payment. Without it, the document may not be legally binding.</p>
<blockquote><p>If something feels wrong about a document, trust your instincts. Walk away and verify independently.</p></blockquote>
<h2>What To Do If You Find a Red Flag</h2>
<p>Do not try to investigate alone. Hire a property lawyer immediately. Let them conduct official verification with the Lands Commission.</p>
<p>The cost of a lawyer is nothing compared to losing your entire investment to document fraud.</p>',
            ),
            array(
                'post_title' => 'How To Stop Paying the Diaspora Tax on Every Ghana Transaction',
                'post_name' => 'stop-diaspora-tax-ghana',
                'date' => '2025-03-10 12:00:00',
                'category' => 'tips',
                'read_time' => '8',
                'image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=900&quality=80',
                'tags' => 'Diaspora Tax,Finance,Currency,Budget,Ghana Money',
                'post_content' => '<p>You are paying 40% more than local buyers. You might not realize it, but this is happening on almost every transaction you make in Ghana from abroad.</p>
<h2>The Diaspora Tax Is Real</h2>
<p>It is not official. It is the extra margin that sellers, contractors, and service providers add when they know you are from overseas.</p>
<h3>Why Does It Happen?</h3>
<p>Some assume you have unlimited resources. Others assume you cannot verify prices locally. Some simply charge what the market will bear.</p>
<h2>How to Stop Paying It</h2>
<h3>1. Present as Locally Connected</h3>
<p>Never reveal you live abroad during initial negotiations. Present yourself as someone who is locally connected.</p>
<h3>2. Get Multiple Quotes</h3>
<p>If one contractor quotes 40% more, that is your signal to get two more. Competition brings prices down.</p>
<h3>3. Use Local Payment Methods</h3>
<p>Wire transfers leave a trail that signals foreign transactions. Where possible, use local payment methods initially.</p>
<h3>4. Build Relationships First</h3>
<p>The best deals go to people who are known in the community. Invest time in relationships before transactions.</p>
<h3>5. Work with Local Representatives</h3>
<p>Trusted local representatives can negotiate on your behalf without the diaspora premium.</p>
<blockquote><p>The goal is not to deceive. It is to be treated fairly. Local buyers get fair prices, and so should you.</p></blockquote>',
            ),
            array(
                'post_title' => 'How To Finally Stop Talking About Building in Ghana and Actually Do It',
                'post_name' => 'stop-talking-start-building-ghana',
                'date' => '2025-02-24 12:00:00',
                'category' => 'diaspora',
                'read_time' => '8',
                'image' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=900&quality=80',
                'tags' => 'Decision,Getting Started,Diaspora,Action,Building',
                'post_content' => '<p>You have been talking about building in Ghana for years. Every family gathering, the topic comes up. You have WhatsApp groups full of ideas. But nothing happens. Here is the decision framework to actually move forward.</p>
<h2>The Framework</h2>
<h3>1. Define Your Goal Clearly</h3>
<p>Is it retirement housing? An investment? A family home? The answer shapes every decision that follows.</p>
<h3>2. Set a Realistic Budget</h3>
<p>Include land, construction, fees, and contingency. Know what you can actually afford before you start.</p>
<h3>3. Set a Timeline</h3>
<p>When do you want to be in the house? Work backward from there. This creates urgency without pressure.</p>
<h3>4. Identify Your Blockers</h3>
<p>Is it fear of being scammed? Lack of money? Family resistance? Each blocker has a solution. Name them.</p>
<h3>5. Take One Small Action This Week</h3>
<p>Hire a lawyer. Visit one plot. Have one conversation. Action creates momentum.</p>
<h3>6. Accept That Perfect Does Not Exist</h3>
<p>The right time is now. The right amount is what you have. Start before you feel ready.</p>
<blockquote><p>Every completed Ghana home was once just a conversation. The difference between talk and action is a decision.</p></blockquote>
<h2>The First Step</h2>
<p>Your first step is simple: schedule a consultation with a property professional. This one action moves you from talk to trajectory.</p>',
            ),
            array(
                'post_title' => 'How To Turn Your Unused Ghana Property Into Monthly Income Without Flying Back',
                'post_name' => 'turn-ghana-property-into-income',
                'date' => '2025-03-04 12:00:00',
                'category' => 'tips',
                'read_time' => '9',
                'image' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=900&quality=80',
                'tags' => 'Rental Income,Property Management,Remote,Investment,Ghana Property',
                'post_content' => '<p>You have a completed property in Ghana but do not live there yet. Instead of letting it sit empty, here is how to generate income remotely.</p>
<h2>Rental Strategy</h2>
<h3>Long-term vs Short-term</h3>
<p>Long-term rentals provide stable income with less management. Short-term rentals offer higher returns but require more attention. Choose based on your involvement capacity.</p>
<h3>Hire a Property Manager</h3>
<p>Property managers handle tenant relations, maintenance, and rent collection. Their fee is typically 8-12% of rental income. This is worth the cost for remote owners.</p>
<h3>Set Up Digital Payments</h3>
<p>Tenants can pay via mobile money directly to your account. This eliminates the need for physical payment collection.</p>
<h3>Smart Home Technology</h3>
<p>Install remote monitoring and digital locks. This gives you visibility and control without being physically present.</p>
<h3>Maintenance Reserve</h3>
<p>Set aside 10-15% of rental income for repairs and emergencies. Properties require ongoing maintenance, especially in Ghana climate.</p>
<blockquote><p>The key to successful remote property management is finding reliable people on the ground. Invest time in finding the right property manager.</p></blockquote>
<h2>Income Expectations</h2>
<p>Rental yields in Ghana typically range from 6-10% annually on property value. A GHS 500,000 property might generate GHS 3,000-4,000 per month in rent.</p>',
            ),
            array(
                'post_title' => 'How To Verify a Ghana Land Title in 5 Steps — Even If You\'re 5,000 Miles Away',
                'post_name' => 'verify-ghana-land-title',
                'date' => '2025-03-14 12:00:00',
                'category' => 'tips',
                'read_time' => '10',
                'image' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=900&quality=80',
                'tags' => 'Lands Commission,Land Verification,Ghana Land Title,Due Diligence,Diaspora',
                'post_content' => '<p>You don\'t need to be physically present in Ghana to verify a land title. What you need is the right process, a competent lawyer, and an understanding of how Ghana\'s land registry system works.</p>
<p>This guide walks you through the exact 5-step verification process used by professionals who handle diaspora property transactions daily.</p>
<h2>Why the Lands Commission Is Your Best Friend</h2>
<p>Ghana\'s Lands Commission maintains the official land registry. Every registered parcel of land in Ghana should have a record there—including the current owner, any encumbrances, and the land\'s official dimensions.</p>
<p>The problem? Many Ghana land transactions happen outside this system. Family land, stool land, and informally transferred plots may not appear in the official registry at all. That\'s why verification is critical.</p>
<h2>The 5-Step Verification Process</h2>
<h3>Step 1: Obtain the Official Land Details from the Seller</h3>
<p>Before anything else, get the following from the seller:</p>
<ul>
<li>The plot number and location address</li>
<li>The registered owner\'s name (as it appears on the title)</li>
<li>The land certificate number or instrument number</li>
<li>The original indenture or land title document</li>
</ul>
<p>If the seller can\'t or won\'t provide these, that\'s your first red flag.</p>
<h3>Step 2: Conduct a Manual Search at the Lands Commission</h3>
<p>Your lawyer should physically visit or file online with the Lands Commission to conduct a search using:</p>
<ul>
<li>The land certificate/instrument number, or</li>
<li>The registrable instrument (deed) number, or</li>
<li>The owner\'s name and location</li>
</ul>
<p>The search reveals the current registered owner, any caveats, liens, or encumbrances on the land, and the land use classification.</p>
<blockquote><p>"A clean search result doesn\'t mean the land is problem-free—it means the registered title is clear. Always verify physical possession too."</p></blockquote>
<h3>Step 3: Verify the Seller\'s Identity Against the Registry</h3>
<p>This step is often skipped but critically important. Confirm that:</p>
<ul>
<li>The seller\'s name matches exactly what\'s on the title document</li>
<li>The seller\'s Ghana Card or passport matches the identity on file</li>
<li>The seller has the legal authority to sell (not a third party without power of attorney)</li>
</ul>
<p>For family land or stool land, verify who actually has the authority to sell. It may require a family meeting or chief\'s consent.</p>
<h3>Step 4: Conduct a Site Inspection</h3>
<p>Even from 5,000 miles away, you need someone on the ground to:</p>
<ul>
<li>Physically visit the land and confirm it exists</li>
<li>Verify the boundaries match what\'s described in documents</li>
<li>Check if anyone else is currently occupying or claiming the land</li>
<li>Photograph the land with identifiable landmarks</li>
</ul>
<p>You can use a trusted family member, a property inspection service, or your lawyer\'s network.</p>
<h3>Step 5: Verify with the District Assembly and Utility Companies</h3>
<p>Two additional checks complete the picture:</p>
<ul>
<li><strong>District Assembly:</strong> Confirm the land is in a zone approved for residential development</li>
<li><strong>Utility companies:</strong> Check if there are plans to run roads, power lines, or drainage through the plot</li>
</ul>
<h2>What to Do If Problems Surface</h2>
<p>If your verification reveals issues:</p>
<ul>
<li><strong>Owner mismatch:</strong> Don\'t proceed until resolved</li>
<li><strong>Encumbrances:</strong> Understand what they are and get them cleared before purchase</li>
<li><strong>Occupants:</strong> Negotiate for them to vacate or reduce the price accordingly</li>
<li><strong>Unregistered land:</strong> Proceed with extreme caution and get legal advice</li>
</ul>
<h2>How Much Does Verification Cost?</h2>
<p>Budget for:</p>
<ul>
<li>Lands Commission search: GHS 500-2,000</li>
<li>Lawyer\'s due diligence fee: GHS 3,000-8,000</li>
<li>Site inspection (if using a service): GHS 500-1,500</li>
<li>District Assembly verification: GHS 200-500</li>
</ul>
<p>This might seem expensive, but it\'s nothing compared to losing your entire investment.</p>
<h2>The Bottom Line</h2>
<p>Verifying a Ghana land title is not optional—it\'s the difference between a secure investment and a devastating loss. The 5 steps above take 1-2 weeks if you have a competent lawyer. Take the time. Do it right.</p>',
            ),
        );
    }

    /* ============================================
       PAGES
       ============================================ */
    public function import_pages() {
        $pages = array(
            array('title' => 'About', 'slug' => 'about', 'template' => 'page-templates/template-about.php', 'content' => ''),
            array('title' => 'Services', 'slug' => 'services', 'template' => 'page-templates/template-services.php', 'content' => ''),
            array('title' => 'Contact', 'slug' => 'contact', 'template' => 'page-templates/template-contact.php', 'content' => ''),
            array('title' => 'Portfolio', 'slug' => 'portfolio', 'template' => 'page-templates/template-portfolio.php', 'content' => ''),
            array('title' => 'FAQ', 'slug' => 'faq', 'template' => 'page-templates/template-faq.php', 'content' => ''),
            array('title' => 'Blog', 'slug' => 'blog', 'template' => '', 'content' => ''),
            array('title' => 'Diaspora Guide', 'slug' => 'diaspora', 'template' => '', 'content' => ''),
            array('title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'template' => '', 'content' => '<p>Effective Date: January 1, 2025</p>
<p>Gabochie Design ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services.</p>
<h2>Information We Collect</h2>
<p>We may collect information about you in a variety of ways, including personal data such as your name, email address, phone number, and project details when you fill out forms or book consultations.</p>
<h2>How We Use Your Information</h2>
<p>We use the information to provide and improve our services, communicate with you, send project updates, and respond to your inquiries.</p>
<h2>Information Sharing</h2>
<p>We do not sell your personal information. We may share information with trusted third parties who assist in providing our services, such as contractors and architects, solely for the purpose of fulfilling your project requirements.</p>
<h2>Data Security</h2>
<p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
<h2>Contact Us</h2>
<p>If you have questions about this Privacy Policy, please contact us at admin@design.gabochie.com.</p>'),
            array('title' => 'Terms of Service', 'slug' => 'terms-of-service', 'template' => '', 'content' => '<p>Effective Date: January 1, 2025</p>
<h2>1. Acceptance of Terms</h2>
<p>By accessing and using the Gabochie Design website and services, you accept and agree to be bound by the terms and provision of this agreement.</p>
<h2>2. Services</h2>
<p>Gabochie Design provides architectural design, interior design, 3D visualization, project management, and AI-powered consultation services for residential construction projects in Ghana.</p>
<h2>3. Payment Terms</h2>
<p>Payments are structured around project milestones. All fees are quoted in the agreed currency and are due as specified in individual project agreements.</p>
<h2>4. Intellectual Property</h2>
<p>All designs, plans, and documents created by Gabochie Design remain our intellectual property until full payment is received, at which point ownership transfers to the client as specified in the project agreement.</p>
<h2>5. Limitation of Liability</h2>
<p>Gabochie Design shall not be liable for any indirect, incidental, or consequential damages arising from the use of our services or website.</p>
<h2>Contact</h2>
<p>For questions about these Terms, contact admin@design.gabochie.com.</p>'),
        );

        foreach ($pages as $page_data) {
            $existing = get_posts(array(
                'name' => $page_data['slug'],
                'post_type' => 'page',
                'numberposts' => 1,
            ));

            if (empty($existing)) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                ));
                if (!empty($page_data['template']) && file_exists(GABOCHIE_DIR . '/' . $page_data['template'])) {
                    update_post_meta($page_id, '_wp_page_template', $page_data['template']);
                }
            }
        }
    }

    /* ============================================
       FAQ
       ============================================ */
    public function import_faq() {
        $faq_items = array(
            array('q' => 'What services does Gabochie Design offer?', 'a' => 'We offer <strong>architectural design</strong>, <strong>interior design</strong>, <strong>3D renders</strong>, <strong>project management</strong>, and <strong>AI-powered consultations</strong>. Whether you need a full design package or expert advice on a specific aspect of your build, we can help.'),
            array('q' => 'Do I need to be in Ghana to work with you?', 'a' => 'Not at all. Most of our clients are based in the UK, USA, Canada, and across Europe. We handle everything remotely — from initial consultation to final design hand-off. Our AI-driven process is built specifically for the diaspora.'),
            array('q' => 'How does the AI consultation work?', 'a' => 'Our AI consultation uses intelligent agents to analyse your requirements, site conditions, budget, and preferences. Within days you receive tailored design options, cost estimates, and a clear roadmap — all without needing to be in Ghana.'),
            array('q' => 'How long does the design process take?', 'a' => 'Timelines vary by project scope. A typical residential design takes 4–8 weeks from brief to final plans. Our AI-augmented workflow significantly reduces the traditional timeline.'),
            array('q' => 'What does it cost to work with Gabochie Design?', 'a' => 'Pricing depends on project complexity, size, and service package. We offer transparent pricing with no hidden fees. Use our cost calculator for a ballpark estimate.'),
            array('q' => 'Can I build in Ghana from abroad?', 'a' => 'Yes. With the right team and tools, building remotely is entirely feasible. Gabochie Design coordinates with local architects, engineers, and contractors on your behalf, providing regular updates and milestone tracking.'),
            array('q' => 'How do I buy land in Ghana as a non-resident?', 'a' => 'The process involves identifying suitable land, conducting due diligence at the Lands Commission, engaging a licensed surveyor, and transferring the title through a qualified lawyer.'),
            array('q' => 'Do I need a building permit?', 'a' => 'Yes. All construction in Ghana requires a building permit from the local municipal assembly. Our project management service includes permit application and liaison with local authorities.'),
            array('q' => 'Can you recommend contractors in Ghana?', 'a' => 'We work with a vetted network of trusted contractors, engineers, and suppliers across Ghana. When you engage our project management service, we handle contractor selection and on-site supervision.'),
            array('q' => 'How do I pay for construction in Ghana from overseas?', 'a' => 'We accept international bank transfers, mobile money (Momo), and select online payment methods. We provide clear invoices and payment schedules.'),
        );

        update_option('gabochie_faq_items', $faq_items);
    }

    /* ============================================
       SETTINGS
       ============================================ */
    public function import_settings() {
        update_option('blogname', 'GABOCHIE DESIGN');
        update_option('blogdescription', 'Residential Architecture & Interior Design');
        update_option('site_icon', '');
    }
}

new Gabochie_Content_Importer();
