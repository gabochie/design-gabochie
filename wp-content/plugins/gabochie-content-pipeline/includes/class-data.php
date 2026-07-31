<?php
defined('ABSPATH') || exit;

class GCP_Data {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_defaults() {
        return array(
            'segments' => self::get_segments(),
            'rates' => self::get_rate_data(),
            'bodies' => self::get_professional_bodies(),
            'regulations' => self::get_regulatory_refs(),
            'pricing' => self::get_pricing_psychology(),
        );
    }

    public static function get_segments() {
        return array(
            'architects' => 'Architects — fee scales, registration (ARC), scope of work, RIBA/ARC standards',
            'quantity_surveyors' => 'Quantity Surveyors — QS rates, cost estimation, procurement, GhIS standards',
            'mep_engineers' => 'MEP Engineers — mechanical, electrical, plumbing design, GhIS/Engineering Council',
            'structural_engineers' => 'Structural Engineers — structural design, Ghana Building Code, Engineering Council',
            'surveyors' => 'Surveyors — land surveys, title registration, GHIS/LANDS Commission',
            'developers' => 'Developers — real estate development, project finance, Ghana property market',
            'contractors' => 'Contractors — construction firms, building permits, MMDA processes, tendering',
            'property_managers' => 'Property Managers — facility management, rent act compliance, tenant relations',
        );
    }

    public static function get_rate_data() {
        return array(
            'architect_fees' => 'ARC Ghana fee scale: 6–12% of construction cost for full architectural services (RIBA stages A–L). Schematic design ~2%, detailed design ~3%, construction supervision ~4%. Minimum charge GHS 15,000–50,000 for residential. Commercial projects negotiable.',
            'qs_rates' => 'Quantity Surveyor fees: 2.5–5% of project cost for full QS services (cost planning, BOQ, tender evaluation, valuation). GhIS recommended scale. Hourly rate GHS 500–1,500. Feasibility studies from GHS 8,000.',
            'structural_rates' => 'Structural engineer fees: 3–6% of structural works cost. Design + supervision. Minimum GHS 12,000 for residential. Geotechnical investigations GHS 5,000–20,000 depending on site.',
            'mep_rates' => 'MEP engineer fees: 4–8% of MEP works cost. Design, specification, supervision. Residential MEP ~GHS 8,000–25,000. Commercial MEP negotiable.',
            'permit_costs' => 'Building permit fees: 0.5–2% of estimated construction cost. Varies by MMDA. Accra: ~1% of project value. Land title registration: 0.5–1% of property value plus statutory fees.',
            'construction_costs' => 'Construction cost per m² (2026): Standard residential GHS 4,500–7,500/m². Premium residential GHS 8,000–12,000/m². Commercial GHS 6,000–10,000/m². Industrial GHS 5,000–8,000/m².',
        );
    }

    public static function get_professional_bodies() {
        return array(
            'arc' => 'Architects Registration Council of Ghana (ARC) — registers and regulates architects. Mandatory registration before practising.',
            'ghis' => 'Ghana Institution of Surveyors (GhIS) — umbrella body for quantity surveyors, land surveyors, valuation surveyors.',
            'engineering_council' => 'Engineering Council of Ghana — registers professional engineers (civil, structural, MEP). CEng/Ing status.',
            'gia' => 'Ghana Institute of Architects (GIA) — professional body for architects, continuing education.',
            'lic' => 'Lands Commission of Ghana — land title registration, deeds, permits.',
            'mma' => 'Metropolitan, Municipal and District Assemblies (MMDAs) — building permit issuance, development control.',
        );
    }

    public static function get_regulatory_refs() {
        return array(
            'building_code' => 'Ghana Building Code (GS 1207:2018) — national building standards. Covers structural design, fire safety, accessibility, energy efficiency.',
            'rent_act' => 'Rent Act 1990 (PNDCL 189) — governs landlord-tenant relationships, rent control, eviction procedures in Ghana.',
            'ghana_building_regs' => 'Ghana Building Regulations (LI 1630) — subsidiary legislation under the Building Code. Detailed technical requirements.',
            'vAT_law' => 'Value Added Tax Act 2013 (Act 870) as amended — VAT on construction services. Standard rate 15%. Flat rate scheme for certain contractors.',
            'ghanaians_act' => 'Ghana Investment Promotion Centre Act 2013 (Act 865) — regulates foreign participation in construction. Minimum capital requirements.',
            'labour_act' => 'Labour Act 2003 (Act 651) — employment standards for construction workers, health and safety requirements.',
            'environmental_act' => 'Environmental Protection Agency Act 1994 (Act 490) — environmental impact assessments required for certain developments.',
        );
    }

    public static function get_pricing_psychology() {
        return array(
            'diaspora_premium' => 'Diaspora clients expect premium service: transparent communication, milestone payments in USD/GHS, virtual consultations. Willing to pay 15–30% above local rates for reliability.',
            'trust_barriers' => 'Major trust barriers: ghost contractors, substandard materials, delayed timelines, land title disputes. Content must address these directly with guarantees and transparent process.',
            'payment_psychology' => 'Ghanaian clients prefer milestone-based payments: 30% design, 30% approvals, 30% construction, 10% completion. Monthly progress billing for longer projects.',
            'value_signals' => 'Key value signals for Ghana AEC: professional body registration numbers, real project photos, specific GHS pricing, WhatsApp availability, references from diaspora clients.',
        );
    }

    public function get_all() {
        $saved = get_option('gcp_ae_data', array());
        return array_merge(self::get_defaults(), $saved);
    }

    public function get_segment_data($segment_key) {
        $data = $this->get_all();
        return isset($data['segments'][$segment_key]) ? $data['segments'][$segment_key] : '';
    }

    public function format_for_prompt() {
        $data = $this->get_all();
        $lines = array();
        $lines[] = '=== GHANA AEC REFERENCE DATA ===';
        $lines[] = 'Use the following real data to add authority and specificity to the draft. Do not make up figures — use these exact values where applicable.';
        $lines[] = '';

        $lines[] = '--- Rate Data ---';
        foreach ($data['rates'] as $key => $val) {
            $lines[] = "- $key: $val";
        }
        $lines[] = '';

        $lines[] = '--- Professional Bodies ---';
        foreach ($data['bodies'] as $key => $val) {
            $lines[] = "- $key: $val";
        }
        $lines[] = '';

        $lines[] = '--- Regulatory References ---';
        foreach ($data['regulations'] as $key => $val) {
            $lines[] = "- $key: $val";
        }
        $lines[] = '';

        $lines[] = '--- Pricing Psychology ---';
        foreach ($data['pricing_psychology'] as $key => $val) {
            $lines[] = "- $key: $val";
        }

        return implode("\n", $lines);
    }
}
