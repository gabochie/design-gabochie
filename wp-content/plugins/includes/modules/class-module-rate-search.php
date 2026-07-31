<?php
/**
 * Module 4: Enriched Construction Rate Index & Document Search Engine
 * Sourced from professional quantity surveying rates in Ghana & West Africa.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BOQ_Module_Rate_Search {

    /**
     * Search rates database with query, category filter, location, and currency.
     *
     * @param string $query_str
     * @param string $location
     * @param string $category
     * @param string $currency 'GHS' or 'USD'
     * @return array
     */
    public static function search_rates($query_str = '', $location = 'Accra', $category = '', $currency = 'GHS') {
        BOQ_Module_BOQ_Estimator::seed_default_rates();
        $rates = get_option('boq_suite_master_rates', array());
        $results = array();

        $q = strtolower(trim($query_str));
        $cat_filter = strtolower(trim($category));
        $usd_rate = 15.50; // 1 USD = 15.50 GHS

        $location_multipliers = array(
            'Accra'       => 1.00,
            'Kumasi'      => 0.96,
            'Tamale'      => 0.98,
            'Takoradi'    => 1.03,
            'Nkoranza'    => 0.92,
            'Prampram'    => 1.05,
            'North Legon' => 1.10,
        );

        $loc_mult = isset($location_multipliers[$location]) ? $location_multipliers[$location] : 1.00;

        foreach ($rates as $key => $item) {
            $title = strtolower($item['title']);
            $item_cat = isset($item['category']) ? strtolower($item['category']) : 'general';

            $matches_query = empty($q) || (strpos($title, $q) !== false || strpos($key, $q) !== false || strpos($item_cat, $q) !== false);
            $matches_cat   = empty($cat_filter) || ($cat_filter === 'all') || (strpos($item_cat, $cat_filter) !== false);

            if ($matches_query && $matches_cat) {
                $base_rate_ghs = (float)$item['rate'] * $loc_mult;
                $final_rate    = (strtoupper($currency) === 'USD') ? ($base_rate_ghs / $usd_rate) : $base_rate_ghs;
                $symbol        = (strtoupper($currency) === 'USD') ? '$' : 'GH₵ ';

                $results[] = array(
                    'key'          => $key,
                    'title'        => $item['title'],
                    'category'     => isset($item['category']) ? $item['category'] : 'General',
                    'unit'         => $item['unit'],
                    'rate_raw'     => round($final_rate, 2),
                    'formatted_rate'=> $symbol . number_format($final_rate, 2),
                    'currency'     => strtoupper($currency),
                    'location'     => !empty($location) ? $location : 'Accra',
                );
            }
        }

        return $results;
    }
}
