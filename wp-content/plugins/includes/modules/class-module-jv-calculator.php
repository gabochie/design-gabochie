<?php
/**
 * Module 3: Real Estate Joint Venture & Financial Modeler
 */

if (!defined('ABSPATH')) {
    exit;
}

class BOQ_Module_JV_Calculator {

    public static function calculate_jv_equity($land_value, $construction_cost, $projected_annual_rent, $holding_years = 10) {
        $land_value        = (float)$land_value;
        $construction_cost = (float)$construction_cost;
        $projected_annual_rent = (float)$projected_annual_rent;

        $total_project_cost = $land_value + $construction_cost;
        if ($total_project_cost <= 0) {
            return array('error' => 'Total cost must be greater than zero.');
        }

        // Equity Shares %
        $landowner_equity_share = round(($land_value / $total_project_cost) * 100, 2);
        $developer_equity_share = round(($construction_cost / $total_project_cost) * 100, 2);

        // Annual Rental Yield
        $gross_rental_yield = round(($projected_annual_rent / $total_project_cost) * 100, 2);
        
        // Annual Revenue Distribution
        $landowner_annual_income = round(($projected_annual_rent * $landowner_equity_share) / 100, 2);
        $developer_annual_income = round(($projected_annual_rent * $developer_equity_share) / 100, 2);

        // Cumulative Return Over Holding Period
        $total_rent_collected   = $projected_annual_rent * $holding_years;
        $payback_period_years   = $projected_annual_rent > 0 ? round($construction_cost / $developer_annual_income, 1) : 'N/A';

        return array(
            'land_value'               => $land_value,
            'construction_cost'        => $construction_cost,
            'total_project_cost'       => $total_project_cost,
            'landowner_equity_share'   => $landowner_equity_share,
            'developer_equity_share'   => $developer_equity_share,
            'projected_annual_rent'    => $projected_annual_rent,
            'gross_rental_yield'       => $gross_rental_yield,
            'landowner_annual_income'  => $landowner_annual_income,
            'developer_annual_income'  => $developer_annual_income,
            'payback_period_years'     => $payback_period_years,
            'holding_years'            => $holding_years,
            'total_rent_collected'     => $total_rent_collected,
        );
    }
}
