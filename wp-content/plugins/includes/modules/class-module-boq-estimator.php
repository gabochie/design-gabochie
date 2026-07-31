<?php
/**
 * Module 1: Enriched BOQ Estimator & Material Take-Off
 * Powered by real-world Quantity Surveying data from the boq2 reference suite.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BOQ_Module_BOQ_Estimator {

    /**
     * Seed comprehensive master rates (50+ professional construction items).
     */
    public static function seed_default_rates() {
        $rates = get_option('boq_suite_master_rates', false);
        if (!$rates || count($rates) < 15) {
            $default_rates = array(
                // 1. Substructure & Earthworks
                'site_clearance'       => array('title' => 'Clear Site of Vegetation & Grub Roots', 'unit' => 'm²', 'rate' => 3.50, 'category' => 'Substructure'),
                'topsoil_excavation'   => array('title' => 'Excavate Topsoil 150mm Deep', 'unit' => 'm²', 'rate' => 4.00, 'category' => 'Substructure'),
                'trench_excavation'    => array('title' => 'Excavate Foundation Trench 0.45m x 1.5m Depth', 'unit' => 'm³', 'rate' => 18.00, 'category' => 'Substructure'),
                'pit_excavation'       => array('title' => 'Pit Excavation for Column Bases (<2.0m)', 'unit' => 'm³', 'rate' => 22.00, 'category' => 'Substructure'),
                'backfilling_comp'     => array('title' => 'Backfill Selected Materials & Compact in Layers', 'unit' => 'm³', 'rate' => 12.00, 'category' => 'Substructure'),
                'hardcore_filling'     => array('title' => '25cm Hardcore Filling under Floor Slab', 'unit' => 'm²', 'rate' => 25.00, 'category' => 'Substructure'),
                'concrete_c25'         => array('title' => 'Ordinary Portland Concrete C25 Mix', 'unit' => 'm³', 'rate' => 480.00, 'category' => 'Substructure'),
                'blinding_concrete'    => array('title' => '50mm Plain Concrete Blinding (1:4:8)', 'unit' => 'm²', 'rate' => 28.00, 'category' => 'Substructure'),

                // 2. Concrete, Steel & Masonry
                'cement'               => array('title' => 'Ordinary Portland Cement (42.5N Grade)', 'unit' => 'Bags', 'rate' => 110.00, 'category' => 'Materials'),
                'rough_sand'           => array('title' => 'River Sand / Approved Sharp Sand (20m³/Trip)', 'unit' => 'Trip', 'rate' => 2200.00, 'category' => 'Materials'),
                'stone_ballast'        => array('title' => 'Crushed Stone Ballast (20mm Aggregate)', 'unit' => 'Tonne', 'rate' => 180.00, 'category' => 'Materials'),
                'rebar_y12'            => array('title' => 'High Tensile Steel Re-Bar Y12', 'unit' => 'Tonne', 'rate' => 14500.00, 'category' => 'Steelwork'),
                'rebar_r6'             => array('title' => 'Mild Steel Link Re-Bar R6', 'unit' => 'Tonne', 'rate' => 13800.00, 'category' => 'Steelwork'),
                'hollow_blocks_150'    => array('title' => 'Hollow Concrete Blocks 150mm (6 Inch)', 'unit' => 'Pcs', 'rate' => 8.50, 'category' => 'Masonry'),
                'hollow_blocks_200'    => array('title' => 'Solid / Hollow Concrete Blocks 200mm (8 Inch)', 'unit' => 'Pcs', 'rate' => 11.00, 'category' => 'Masonry'),

                // 3. Timber, Roofing & Carpentry
                'timber_6x1'           => array('title' => 'White Timber Hardwood 6" x 1"', 'unit' => 'Pcs', 'rate' => 45.00, 'category' => 'Carpentry'),
                'timber_12x1'          => array('title' => 'White Timber Hardwood 12" x 1"', 'unit' => 'Pcs', 'rate' => 85.00, 'category' => 'Carpentry'),
                'gci_roofing_sheets'   => array('title' => 'Corrugated Galvanized Iron Sheets (No. 28 Gauge)', 'unit' => 'Pcs', 'rate' => 135.00, 'category' => 'Roofing'),
                'roof_tiles'           => array('title' => 'Supply & Install Roof Tiles on Purlins', 'unit' => 'm²', 'rate' => 160.00, 'category' => 'Roofing'),
                'roofing_nails'        => array('title' => 'Roofing Nails with Rubber Washers', 'unit' => 'Kg', 'rate' => 35.00, 'category' => 'Roofing'),
                'wire_nails_assorted'  => array('title' => 'Assorted Wire Nails (3" - 4")', 'unit' => 'Kg', 'rate' => 28.00, 'category' => 'Carpentry'),

                // 4. Finishes & Dampness Remediation
                'plaster_cut'          => array('title' => 'Cut & Chisel Deteriorated Wall Plaster', 'unit' => 'm²', 'rate' => 32.00, 'category' => 'Finishes'),
                'dampness_treatment'   => array('title' => 'Anti-Dampness Anti-Fungal Chemical Coating', 'unit' => 'm²', 'rate' => 45.00, 'category' => 'Finishes'),
                'waterproof_plaster'   => array('title' => 'Re-Plastering with Waterproof Additive Mortar', 'unit' => 'm²', 'rate' => 40.00, 'category' => 'Finishes'),
                'colored_cement_tiles' => array('title' => '50mm Colored Cement Tile Bedded in 1:3 Mortar', 'unit' => 'm²', 'rate' => 95.00, 'category' => 'Finishes'),
                'curb_stone_c20'       => array('title' => 'Vibrated C-20 Concrete Curb Stone with Formwork', 'unit' => 'm', 'rate' => 65.00, 'category' => 'External Works'),

                // 5. Plumbing, Mechanical & KVIP Sanitation
                'water_pump_2_2hp'     => array('title' => '2.2 Horse Power Capacity Water Pump & Fittings', 'unit' => 'Set', 'rate' => 3800.00, 'category' => 'Plumbing'),
                'kvip_toilet_block'    => array('title' => '6-Seater KVIP Toilet Pit Digging, Slab & Vent Pipe', 'unit' => 'Unit', 'rate' => 24000.00, 'category' => 'Sanitation'),
            );

            update_option('boq_suite_master_rates', $default_rates);
        }
    }

    /**
     * Calculate comprehensive BOQ estimate based on project specifications.
     *
     * @param string $project_type
     * @param float $area
     * @param int $floors
     * @param string $location
     * @param float $escalation
     * @param string $currency 'GHS' or 'USD'
     * @return array
     */
    public static function calculate_estimate($project_type = 'classroom_block', $area = 250, $floors = 1, $location = 'Accra', $escalation = 0, $currency = 'GHS') {
        self::seed_default_rates();

        $location_multipliers = array(
            'Accra'        => 1.00,
            'Kumasi'       => 0.96,
            'Tamale'       => 0.98,
            'Takoradi'     => 1.03,
            'Nkoranza'     => 0.92,
            'Cape Coast'   => 0.95,
            'Ho'           => 0.93,
            'Koforidua'    => 0.95,
            'Prampram'     => 1.05,
            'North Legon'  => 1.10,
        );

        $usd_exchange_rate = 15.50; // 1 USD = 15.50 GHS

        $loc_mult = isset($location_multipliers[$location]) ? $location_multipliers[$location] : 1.00;
        $esc_mult = 1 + ($escalation / 100);
        $curr_mult = (strtoupper($currency) === 'USD') ? (1 / $usd_exchange_rate) : 1.00;

        $total_mult = $loc_mult * $esc_mult * $curr_mult;

        $bills = array();

        if ($project_type === 'classroom_block') {
            // 3-Unit Classroom Block with Staff Room, Store & KVIP Toilet
            $bills[] = array(
                'bill_name' => 'Bill No. 1 - Preliminaries & General Conditions',
                'items' => array(
                    array('description' => 'Site signboards, insurance, setting out & temporary office setup', 'qty' => 1, 'unit' => 'Item', 'rate' => round(5000.00 * $total_mult, 2), 'amount' => round(5000.00 * $total_mult, 2)),
                )
            );
            $bills[] = array(
                'bill_name' => 'Bill No. 2 - Substructure Works',
                'items' => array(
                    array('description' => 'Clear site of all vegetation and grub up roots', 'qty' => $area, 'unit' => 'm²', 'rate' => round(3.50 * $total_mult, 2), 'amount' => round($area * 3.50 * $total_mult, 2)),
                    array('description' => 'Excavate foundation trench 0.45m width x 1.5m depth', 'qty' => round($area * 0.4, 2), 'unit' => 'm³', 'rate' => round(18.00 * $total_mult, 2), 'amount' => round($area * 0.4 * 18.00 * $total_mult, 2)),
                    array('description' => 'Plain Concrete Blinding 50mm thick (1:4:8 mix)', 'qty' => $area, 'unit' => 'm²', 'rate' => round(28.00 * $total_mult, 2), 'amount' => round($area * 28.00 * $total_mult, 2)),
                    array('description' => 'Vibrated Concrete C25 Foundation Footing & Bed', 'qty' => round($area * 0.18, 2), 'unit' => 'm³', 'rate' => round(480.00 * $total_mult, 2), 'amount' => round($area * 0.18 * 480.00 * $total_mult, 2)),
                )
            );
            $bills[] = array(
                'bill_name' => 'Bill No. 3 - Superstructure Blockwork & Columns',
                'items' => array(
                    array('description' => 'Solid Concrete Blockwork 150mm thick in cement mortar 1:4', 'qty' => round($area * 1.8, 2), 'unit' => 'm²', 'rate' => round(95.00 * $total_mult, 2), 'amount' => round($area * 1.8 * 95.00 * $total_mult, 2)),
                    array('description' => 'High Tensile Steel Re-Bar Y12 to columns & lintels', 'qty' => round($area * 0.008, 2), 'unit' => 'Tonne', 'rate' => round(14500.00 * $total_mult, 2), 'amount' => round($area * 0.008 * 14500.00 * $total_mult, 2)),
                )
            );
            $bills[] = array(
                'bill_name' => 'Bill No. 4 - Timber Trusses & Roofing Structure',
                'items' => array(
                    array('description' => 'White Timber Hardwood 6" x 1" purlins & rafters', 'qty' => ceil($area * 0.8), 'unit' => 'Pcs', 'rate' => round(45.00 * $total_mult, 2), 'amount' => round(ceil($area * 0.8) * 45.00 * $total_mult, 2)),
                    array('description' => 'Corrugated Galvanized Iron Sheets No. 28 Gauge', 'qty' => round($area * 1.25, 2), 'unit' => 'Pcs', 'rate' => round(135.00 * $total_mult, 2), 'amount' => round($area * 1.25 * 135.00 * $total_mult, 2)),
                )
            );
            $bills[] = array(
                'bill_name' => 'Bill No. 5 - KVIP Toilet Facility & External Services',
                'items' => array(
                    array('description' => '6-Seater KVIP Toilet Pit Excavation, Reinforced Slab & Vent Pipes', 'qty' => 1, 'unit' => 'Facility', 'rate' => round(24000.00 * $total_mult, 2), 'amount' => round(24000.00 * $total_mult, 2)),
                )
            );

        } elseif ($project_type === 'medical_center') {
            // Medical Center Building
            $bills[] = array(
                'bill_name' => 'Bill No. 1 - Preliminaries & Clinical Setup',
                'items' => array(
                    array('description' => 'Medical center site establishment, safety barriers & sanitation testing', 'qty' => 1, 'unit' => 'Item', 'rate' => round(8500.00 * $total_mult, 2), 'amount' => round(8500.00 * $total_mult, 2)),
                )
            );
            $bills[] = array(
                'bill_name' => 'Bill No. 2 - Substructure & Foundation Slab',
                'items' => array(
                    array('description' => 'Excavate foundation pit for columns & beams', 'qty' => round($area * 0.45, 2), 'unit' => 'm³', 'rate' => round(22.00 * $total_mult, 2), 'amount' => round($area * 0.45 * 22.00 * $total_mult, 2)),
                    array('description' => '25cm Hardcore filling under medical floor slab', 'qty' => $area, 'unit' => 'm²', 'rate' => round(25.00 * $total_mult, 2), 'amount' => round($area * 25.00 * $total_mult, 2)),
                    array('description' => 'Reinforced Concrete Floor Slab C25 (100mm thick)', 'qty' => round($area * 0.12, 2), 'unit' => 'm³', 'rate' => round(520.00 * $total_mult, 2), 'amount' => round($area * 0.12 * 520.00 * $total_mult, 2)),
                )
            );
            $bills[] = array(
                'bill_name' => 'Bill No. 3 - Clinical Finishes & Water Systems',
                'items' => array(
                    array('description' => '50mm Colored cement floor tile bedded in 1:3 mortar', 'qty' => $area, 'unit' => 'm²', 'rate' => round(95.00 * $total_mult, 2), 'amount' => round($area * 95.00 * $total_mult, 2)),
                    array('description' => 'Vibrated C20 Concrete Curb Stones along access driveways', 'qty' => round($area * 0.3, 2), 'unit' => 'm', 'rate' => round(65.00 * $total_mult, 2), 'amount' => round($area * 0.3 * 65.00 * $total_mult, 2)),
                    array('description' => '2.2 HP Capacity Water Pump & High Pressure Delivery System', 'qty' => 1, 'unit' => 'Set', 'rate' => round(3800.00 * $total_mult, 2), 'amount' => round(3800.00 * $total_mult, 2)),
                )
            );

        } elseif ($project_type === 'market_place') {
            // Community Market Place Sheds
            $bills[] = array(
                'bill_name' => 'Bill No. 1 - Substructure & Foundation Pads',
                'items' => array(
                    array('description' => 'Excavation for post foundation pads 0.6m x 0.6m x 0.8m', 'qty' => round($area * 0.15, 2), 'unit' => 'm³', 'rate' => round(18.00 * $total_mult, 2), 'amount' => round($area * 0.15 * 18.00 * $total_mult, 2)),
                    array('description' => 'Crushed Stone Ballast (20mm size aggregate)', 'qty' => round($area * 0.05, 2), 'unit' => 'Tonne', 'rate' => round(180.00 * $total_mult, 2), 'amount' => round($area * 0.05 * 180.00 * $total_mult, 2)),
                )
            );
            $bills[] = array(
                'bill_name' => 'Bill No. 2 - Open Shed Framing & Roof Trusses',
                'items' => array(
                    array('description' => 'White Timber Hardwood 6" x 1"', 'qty' => ceil($area * 0.5), 'unit' => 'Pcs', 'rate' => round(45.00 * $total_mult, 2), 'amount' => round(ceil($area * 0.5) * 45.00 * $total_mult, 2)),
                    array('description' => 'White Timber Hardwood 12" x 1"', 'qty' => ceil($area * 0.3), 'unit' => 'Pcs', 'rate' => round(85.00 * $total_mult, 2), 'amount' => round(ceil($area * 0.3) * 85.00 * $total_mult, 2)),
                    array('description' => 'Corrugated Galvanized Iron Sheets No. 28 Gauge', 'qty' => round($area * 1.15, 2), 'unit' => 'Pcs', 'rate' => round(135.00 * $total_mult, 2), 'amount' => round($area * 1.15 * 135.00 * $total_mult, 2)),
                    array('description' => 'High Tensile Steel Re-Bar Y12 & R6 Links', 'qty' => round($area * 0.004, 2), 'unit' => 'Tonne', 'rate' => round(14200.00 * $total_mult, 2), 'amount' => round($area * 0.004 * 14200.00 * $total_mult, 2)),
                )
            );

        } elseif ($project_type === 'rehab_renovation') {
            // Public Building Rehabilitation
            $bills[] = array(
                'bill_name' => 'Bill No. 1 - Demolition & Dampness Remediation',
                'items' => array(
                    array('description' => 'Cut wall plaster, chisel affected area & dispose off site', 'qty' => $area, 'unit' => 'm²', 'rate' => round(32.00 * $total_mult, 2), 'amount' => round($area * 32.00 * $total_mult, 2)),
                    array('description' => 'Provide anti-damp chemical coating & damp proofing treatment', 'qty' => $area, 'unit' => 'm²', 'rate' => round(45.00 * $total_mult, 2), 'amount' => round($area * 45.00 * $total_mult, 2)),
                    array('description' => 'Re-plastering walls with waterproof mortar additive mix', 'qty' => $area, 'unit' => 'm²', 'rate' => round(40.00 * $total_mult, 2), 'amount' => round($area * 40.00 * $total_mult, 2)),
                )
            );

        } else {
            // Residential Villa / Standard Building
            $bills[] = array(
                'bill_name' => 'Bill No. 1 - Substructure & Foundation',
                'items' => array(
                    array('description' => 'Site Clearance & Excavate Oversite 150mm Deep', 'qty' => $area, 'unit' => 'm²', 'rate' => round(4.00 * $total_mult, 2), 'amount' => round($area * 4.00 * $total_mult, 2)),
                    array('description' => 'Substructure & Foundation Concrete (C25)', 'qty' => round($area * 0.25 * $floors, 2), 'unit' => 'm³', 'rate' => round(480.00 * $total_mult, 2), 'amount' => round($area * 0.25 * $floors * 480.00 * $total_mult, 2)),
                )
            );
            $bills[] = array(
                'bill_name' => 'Bill No. 2 - Superstructure & Finishes',
                'items' => array(
                    array('description' => 'Hollow Concrete Blockwork 150mm', 'qty' => round($area * 2.2 * $floors, 2), 'unit' => 'm²', 'rate' => round(95.00 * $total_mult, 2), 'amount' => round($area * 2.2 * $floors * 95.00 * $total_mult, 2)),
                    array('description' => 'Roof Covering (Roof Tiles on Hardwood Purlins)', 'qty' => round($area * 1.15, 2), 'unit' => 'm²', 'rate' => round(160.00 * $total_mult, 2), 'amount' => round($area * 1.15 * 160.00 * $total_mult, 2)),
                )
            );
        }

        // Calculate Totals across all bills
        $grand_total = 0;
        $all_items = array();

        foreach ($bills as &$b) {
            $bill_subtotal = 0;
            foreach ($b['items'] as $it) {
                $bill_subtotal += $it['amount'];
                $all_items[] = $it;
            }
            $b['subtotal'] = round($bill_subtotal, 2);
            $grand_total += $bill_subtotal;
        }

        return array(
            'project_type' => $project_type,
            'area'         => $area,
            'floors'       => $floors,
            'location'     => $location,
            'escalation'   => $escalation,
            'currency'     => strtoupper($currency),
            'bills'        => $bills,
            'items'        => $all_items,
            'total_cost'   => round($grand_total, 2),
            'formatted_total' => (strtoupper($currency) === 'USD' ? '$' : 'GH₵ ') . number_format($grand_total, 2),
        );
    }
}
