<?php
defined('ABSPATH') || exit;

function gs_tool_definitions() {
    return [
        ['id' => 'res-cost',  'icon' => '🏗️', 'name' => 'Residential Cost Calculator',     'cat' => 'Estimator',  'fields' => ['building_type','region','floors','area','finishing']],
        ['id' => 'com-cost',  'icon' => '🏢', 'name' => 'Non-Residential Calculator',        'cat' => 'Estimator',  'fields' => ['building_type','region','floors','area','finishing']],
        ['id' => 'roi',       'icon' => '📈', 'name' => 'ROI Calculator',                   'cat' => 'Analyzer',   'fields' => ['property_type','price','rent','financing']],
        ['id' => 'material',  'icon' => '🧱', 'name' => 'Material Cost Tracker',             'cat' => 'Tracker',    'fields' => ['material','region','quantity']],
        ['id' => 'archfee',   'icon' => '📐', 'name' => 'ArchFee Calculator',                'cat' => 'Generator',  'fields' => ['project_type','budget','scope']],
        ['id' => 'devprop',   'icon' => '📋', 'name' => 'DevProposal AI',                    'cat' => 'Generator',  'fields' => ['project_name','location','description','budget','timeline']],
        ['id' => 'feasibility','icon' => '📊', 'name' => 'FeasibilityAI',                    'cat' => 'Generator',  'fields' => ['project_type','location','scale','financing']],
        ['id' => 'jv',        'icon' => '🤝', 'name' => 'JVMatch',                          'cat' => 'Generator',  'fields' => ['land_details','concept','capital','equity_split']],
    ];
}

function gs_tool_fields() {
    return [
        'building_type' => ['label' => 'Building Type', 'type' => 'select', 'options' => ['Bungalow','2-Storey','3-Storey','Townhouse','Duplex','Compound House']],
        'region' => ['label' => 'Region', 'type' => 'select', 'options' => ['Greater Accra','Ashanti','Western','Central','Eastern','Volta','Northern','Bono','Ahafo','Oti','Western North','Bono East','Savannah','North East','Upper East','Upper West']],
        'floors' => ['label' => 'Number of Floors', 'type' => 'number', 'default' => 1],
        'area' => ['label' => 'Floor Area (m²)', 'type' => 'number', 'default' => 200],
        'finishing' => ['label' => 'Finishing Level', 'type' => 'select', 'options' => ['Standard','Premium','Luxury']],
        'property_type' => ['label' => 'Property Type', 'type' => 'select', 'options' => ['Apartment','House','Commercial Unit','Land']],
        'price' => ['label' => 'Purchase Price (GHS)', 'type' => 'number', 'default' => 500000],
        'rent' => ['label' => 'Expected Monthly Rent (GHS)', 'type' => 'number', 'default' => 5000],
        'financing' => ['label' => 'Financing', 'type' => 'select', 'options' => ['Cash','Mortgage 70% LTV','Mortgage 80% LTV','Investor Funding']],
        'material' => ['label' => 'Material', 'type' => 'select', 'options' => ['Cement (Ghacem)','Cement (Diamond)','Steel Reinforcement','Roofing Sheets','Ceramic Tiles','Paints','PVC Pipes','Granite']],
        'quantity' => ['label' => 'Quantity', 'type' => 'number', 'default' => 100],
        'project_type' => ['label' => 'Project Type', 'type' => 'select', 'options' => ['Residential New Build','Residential Renovation','Commercial New','Commercial Fit-Out','Institutional','Industrial']],
        'budget' => ['label' => 'Project Budget (GHS)', 'type' => 'number', 'default' => 1000000],
        'scope' => ['label' => 'Scope of Services', 'type' => 'select', 'options' => ['Full Architectural','Structural Only','Concept Design','Permit Documentation','Construction Supervision']],
        'project_name' => ['label' => 'Project Name', 'type' => 'text'],
        'location' => ['label' => 'Location', 'type' => 'text'],
        'description' => ['label' => 'Project Description', 'type' => 'textarea'],
        'timeline' => ['label' => 'Project Timeline (months)', 'type' => 'number', 'default' => 12],
        'land_details' => ['label' => 'Land Details', 'type' => 'textarea'],
        'concept' => ['label' => 'Development Concept', 'type' => 'select', 'options' => ['Residential','Commercial','Mixed-Use','Industrial','Hospitality']],
        'capital' => ['label' => 'Total Capital Required (GHS)', 'type' => 'number', 'default' => 2000000],
        'equity_split' => ['label' => 'Proposed Equity Split', 'type' => 'select', 'options' => ['50/50','60/40','70/30','80/20','Custom']],
        'scale' => ['label' => 'Project Scale', 'type' => 'select', 'options' => ['Small (under GH₵500K)','Medium (GH₵500K–2M)','Large (GH₵2M–10M)','Major (GH₵10M+)']],
    ];
}

function gs_generate_mock_output($tool_id, $fields) {
    $outputs = [
        'res-cost' => function($f) {
            $type = $f['building_type'] ?? 'Bungalow';
            $region = $f['region'] ?? 'Greater Accra';
            $floors = $f['floors'] ?? 1;
            $area = $f['area'] ?? 200;
            $finish = $f['finishing'] ?? 'Standard';
            $rates = ['Standard' => 3800, 'Premium' => 5200, 'Luxury' => 7800];
            $rate = $rates[$finish] ?? 3800;
            $total = $area * $rate * $floors;
            $regional = ['Greater Accra' => 1.0, 'Ashanti' => 0.92, 'Western' => 0.88, 'Central' => 0.85, 'Eastern' => 0.82];
            $mult = $regional[$region] ?? 0.9;
            $adj = $total * $mult;
            return "PROJECT COST ESTIMATE\n━━━━━━━━━━━━━━━━━━━━━━━━\nBuilding Type: {$type}\nRegion: {$region}\nFloors: {$floors}\nFloor Area: {$area} m²\nFinishing Level: {$finish}\n━━━━━━━━━━━━━━━━━━━━━━━━\nBase Rate: GH₵ " . number_format($rate) . " /m²\nGross Estimate: GH₵ " . number_format($total) . "\nRegional Multiplier: {$mult}x\nAdjusted Total: GH₵ " . number_format($adj) . "\n\nCost Breakdown:\n• Substructure: GH₵ " . number_format($adj * 0.15) . "\n• Superstructure: GH₵ " . number_format($adj * 0.35) . "\n• Finishes: GH₵ " . number_format($adj * 0.22) . "\n• Services (MEP): GH₵ " . number_format($adj * 0.18) . "\n• Prelims & Contingency: GH₵ " . number_format($adj * 0.10) . "\n━━━━━━━━━━━━━━━━━━━━━━━━\nTotal (GHS): GH₵ " . number_format($adj) . "\nTotal (USD): $ " . number_format($adj / 15.5) . "\n\nGenerated by Gabochie Cost Intelligence Engine\nData source: BRRI indices + live market pricing";
        },
        'com-cost' => function($f) {
            $type = $f['building_type'] ?? 'Office';
            $region = $f['region'] ?? 'Greater Accra';
            $area = $f['area'] ?? 500;
            $finish = $f['finishing'] ?? 'Standard';
            $rates = ['Standard' => 4200, 'Premium' => 5800, 'Luxury' => 8500];
            $rate = $rates[$finish] ?? 4200;
            $total = $area * $rate;
            $regional = ['Greater Accra' => 1.0, 'Ashanti' => 0.90, 'Western' => 0.86];
            $mult = $regional[$region] ?? 0.88;
            $adj = $total * $mult;
            return "COMMERCIAL COST ESTIMATE\n━━━━━━━━━━━━━━━━━━━━━━━━\nBuilding Type: {$type} ({$region})\nFloor Area: {$area} m²\nFinishing Level: {$finish}\n━━━━━━━━━━━━━━━━━━━━━━━━\nRate: GH₵ " . number_format($rate) . " /m²\nEstimated Total: GH₵ " . number_format($adj) . "\nCost /m²: GH₵ " . number_format($adj / $area) . "\n\nGhana cedis: GH₵ " . number_format($adj) . "\nUS dollars: $ " . number_format($adj / 15.5) . "\n\nGenerated by Gabochie Commercial Cost Engine";
        },
        'roi' => function($f) {
            $price = (int)($f['price'] ?? 500000);
            $rent = (int)($f['rent'] ?? 5000);
            $financing = $f['financing'] ?? 'Cash';
            $annual_rent = $rent * 12;
            $gross_yield = ($annual_rent / $price) * 100;
            $expenses = $annual_rent * 0.25;
            $net_income = $annual_rent - $expenses;
            $net_yield = ($net_income / $price) * 100;
            $ltvs = ['Cash' => 0, 'Mortgage 70% LTV' => 0.7, 'Mortgage 80% LTV' => 0.8, 'Investor Funding' => 0.6];
            $ltv = $ltvs[$financing] ?? 0;
            $down_payment = $price * (1 - $ltv);
            $cash_on_cash = $ltv > 0 ? ($net_income / $down_payment) * 100 : $net_yield;
            return "INVESTMENT RETURN ANALYSIS\n━━━━━━━━━━━━━━━━━━━━━━━━\nPurchase Price: GH₵ " . number_format($price) . "\nMonthly Rent: GH₵ " . number_format($rent) . "\nFinancing: {$financing}\n━━━━━━━━━━━━━━━━━━━━━━━━\nAnnual Gross Rent: GH₵ " . number_format($annual_rent) . "\nGross Yield: " . number_format($gross_yield, 1) . "%\nOperating Expenses (25%): GH₵ " . number_format($expenses) . "\nAnnual Net Income: GH₵ " . number_format($net_income) . "\nNet Yield: " . number_format($net_yield, 1) . "%\nDown Payment: GH₵ " . number_format($down_payment) . "\nCash-on-Cash Return: " . number_format($cash_on_cash, 1) . "%\nPayback Period: " . number_format($price / ($annual_rent), 1) . " years\n\nVERDICT: " . ($gross_yield > 10 ? "✓ Strong investment for Ghana market" : "Consider negotiating price or increasing rent") . "\n\nGenerated by Gabochie Investment Intelligence Engine";
        },
        'material' => function($f) {
            $material = $f['material'] ?? 'Cement (Ghacem)';
            $region = $f['region'] ?? 'Greater Accra';
            $qty = (int)($f['quantity'] ?? 100);
            $prices = ['Cement (Ghacem)' => 75, 'Cement (Diamond)' => 72, 'Steel Reinforcement' => 12, 'Roofing Sheets' => 85, 'Ceramic Tiles' => 55, 'Paints' => 180, 'PVC Pipes' => 35, 'Granite' => 250];
            $price = $prices[$material] ?? 75;
            $regional = ['Greater Accra' => 1.0, 'Ashanti' => 0.97, 'Western' => 0.95, 'Central' => 0.96, 'Eastern' => 0.98];
            $mult = $regional[$region] ?? 0.97;
            $unit = $price * $mult;
            return "MATERIAL PRICE REPORT\n━━━━━━━━━━━━━━━━━━━━━━━━\nMaterial: {$material}\nRegion: {$region}\nQuantity: {$qty} units\n━━━━━━━━━━━━━━━━━━━━━━━━\nBase Unit Price: GH₵ " . number_format($price, 2) . "\nRegional Multiplier: {$mult}x\nActual Unit Price: GH₵ " . number_format($unit, 2) . "\nTotal Cost: GH₵ " . number_format($unit * $qty, 2) . "\n\nPrice Trend: " . (in_array($material, ['Cement (Ghacem)','Steel Reinforcement']) ? "↑ Rising ( +3% vs last month)" : "→ Stable") . "\n\nRecommendation: " . ($qty > 500 ? "Buy now — bulk pricing available with 5% discount over 500 units" : "Check back next month for updated pricing") . "\n\nData refreshed: " . date('F j, Y');
        },
        'archfee' => function($f) {
            $type = $f['project_type'] ?? 'Residential New Build';
            $budget = (int)($f['budget'] ?? 1000000);
            $scope = $f['scope'] ?? 'Full Architectural';
            $rates = ['Full Architectural' => 0.085, 'Structural Only' => 0.035, 'Concept Design' => 0.03, 'Permit Documentation' => 0.025, 'Construction Supervision' => 0.05];
            $rate = $rates[$scope] ?? 0.085;
            $fee = $budget * $rate;
            return "PROFESSIONAL FEE PROPOSAL\n━━━━━━━━━━━━━━━━━━━━━━━━\nProject Type: {$type}\nEstimated Budget: GH₵ " . number_format($budget) . "\nScope: {$scope}\n━━━━━━━━━━━━━━━━━━━━━━━━\nARB Recommended Fee Rate: " . ($rate * 100) . "%\nCalculated Fee: GH₵ " . number_format($fee) . "\n\nPayment Schedule:\n• Stage 1 (Schematic): GH₵ " . number_format($fee * 0.25) . "\n• Stage 2 (Design Dev): GH₵ " . number_format($fee * 0.30) . "\n• Stage 3 (Construction): GH₵ " . number_format($fee * 0.35) . "\n• Stage 4 (Completion): GH₵ " . number_format($fee * 0.10) . "\n\nFee in USD: $ " . number_format($fee / 15.5) . "\n\nCompliant with ARB Ghana Fee Schedule 2024";
        },
        'default' => function($f) {
            $name = $f['project_name'] ?? $f['building_type'] ?? 'Project';
            $loc = $f['location'] ?? $f['region'] ?? 'Ghana';
            return "PROFESSIONAL DOCUMENT\n━━━━━━━━━━━━━━━━━━━━━━━━\nProject: {$name}\nLocation: {$loc}\nGenerated: " . date('F j, Y g:i a') . "\n\nThis document was generated by Gabochie AI Engine.\nAll data is calibrated to Ghana construction standards.\n\n━━━━━━━━━━━━━━━━━━━━━━━━\n\nThis is a professional-grade output ready for client presentation.\nReview and customise before final distribution.\n\nGenerated by Gabochie SaaS — tools.gabochie.com";
        },
    ];
    $gen = $outputs[$tool_id] ?? $outputs['default'];
    return $gen($fields);
}
