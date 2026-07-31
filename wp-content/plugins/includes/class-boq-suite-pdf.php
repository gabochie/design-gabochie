<?php
/**
 * Class BOQ_Suite_PDF
 * Pure PHP lightweight PDF/Print generator for SMM7 BOQ estimates and Invoices.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}

class BOQ_Suite_PDF {

    /**
     * Generate structured BOQ Estimate PDF/Printable HTML string.
     */
    public static function generate_boq_pdf($data) {
        $title    = isset($data['title']) ? $data['title'] : 'Official Bill of Quantities Estimate';
        $client   = isset($data['client_name']) ? $data['client_name'] : 'Valued Client';
        $location = isset($data['location']) ? $data['location'] : 'Accra, Ghana';
        $currency = isset($data['currency']) ? strtoupper($data['currency']) : 'GHS';
        $symbol   = ($currency === 'USD') ? '$' : 'GH₵ ';
        $total    = isset($data['total_cost']) ? number_format((float)$data['total_cost'], 2) : '0.00';
        $bills    = isset($data['bills']) && is_array($data['bills']) ? $data['bills'] : array();
        $items    = isset($data['items']) && is_array($data['items']) ? $data['items'] : array();
        $date     = date('Y-m-d');

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<title>' . esc_html($title) . '</title>';
        $html .= '<style>
            body { font-family: "Segoe UI", Arial, sans-serif; color: #1e293b; margin: 30px; line-height: 1.5; }
            .header { border-bottom: 3px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
            .header h1 { color: #1e3a8a; margin: 0 0 5px 0; font-size: 24px; font-weight: 700; }
            .header p { margin: 0; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
            .meta-table { width: 100%; margin-bottom: 25px; font-size: 13px; border-collapse: collapse; background: #f8fafc; padding: 12px; border-radius: 6px; }
            .meta-table td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
            .bill-title { background: #1e3a8a; color: #fff; padding: 8px 12px; font-size: 14px; font-weight: 600; margin-top: 25px; border-radius: 4px; }
            .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px; font-size: 13px; }
            .data-table th, .data-table td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; }
            .data-table th { background-color: #f1f5f9; color: #334155; font-weight: 600; text-transform: uppercase; font-size: 11px; }
            .data-table td.num { text-align: right; font-family: monospace; font-size: 13px; }
            .subtotal-row td { font-weight: bold; background: #f8fafc; text-align: right; }
            .total-card { margin-top: 30px; background: #1e3a8a; color: #fff; padding: 18px; border-radius: 6px; text-align: right; font-size: 18px; font-weight: 700; }
            .footer { margin-top: 40px; font-size: 11px; text-align: center; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 15px; }
        </style>';
        $html .= '</head><body>';
        $html .= '<div class="header">
            <div>
                <h1>BUILDSUITE PRO — BILL OF QUANTITIES</h1>
                <p>Standard Method of Measurement (SMM7) Cost Estimate Report</p>
            </div>
        </div>';

        $html .= '<table class="meta-table">
            <tr><td><strong>Project Title:</strong> ' . esc_html($title) . '</td><td><strong>Date Generated:</strong> ' . esc_html($date) . '</td></tr>
            <tr><td><strong>Client / Employer:</strong> ' . esc_html($client) . '</td><td><strong>Location:</strong> ' . esc_html($location) . '</td></tr>
            <tr><td><strong>Currency:</strong> ' . esc_html($currency) . '</td><td><strong>QS Standard:</strong> SMM7 / NRM2 Compliant</td></tr>
        </table>';

        if (!empty($bills)) {
            foreach ($bills as $bill) {
                $bname = esc_html(isset($bill['bill_name']) ? $bill['bill_name'] : 'Bill Section');
                $bitems = isset($bill['items']) ? $bill['items'] : array();
                $bsub   = isset($bill['subtotal']) ? number_format((float)$bill['subtotal'], 2) : '0.00';

                $html .= '<div class="bill-title">' . $bname . '</div>';
                $html .= '<table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Description of Works</th>
                            <th style="width: 10%;">Qty</th>
                            <th style="width: 10%;">Unit</th>
                            <th style="width: 15%; text-align:right;">Rate (' . $currency . ')</th>
                            <th style="width: 15%; text-align:right;">Amount (' . $currency . ')</th>
                        </tr>
                    </thead>
                    <tbody>';

                foreach ($bitems as $item) {
                    $desc   = esc_html(isset($item['description']) ? $item['description'] : '');
                    $qty    = esc_html(isset($item['qty']) ? $item['qty'] : 1);
                    $unit   = esc_html(isset($item['unit']) ? $item['unit'] : 'Item');
                    $rate   = number_format((float)(isset($item['rate']) ? $item['rate'] : 0), 2);
                    $amount = number_format((float)(isset($item['amount']) ? $item['amount'] : 0), 2);

                    $html .= "<tr>
                        <td>{$desc}</td>
                        <td>{$qty}</td>
                        <td>{$unit}</td>
                        <td class='num'>{$symbol}{$rate}</td>
                        <td class='num'>{$symbol}{$amount}</td>
                    </tr>";
                }

                $html .= '<tr class="subtotal-row">
                    <td colspan="4">Section Collection Total:</td>
                    <td class="num">' . $symbol . $bsub . '</td>
                </tr>';

                $html .= '</tbody></table>';
            }
        } else {
            // Fallback for flat items list
            $html .= '<table class="data-table">
                <thead>
                    <tr>
                        <th>Description of Works</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th style="text-align:right;">Rate (' . $currency . ')</th>
                        <th style="text-align:right;">Amount (' . $currency . ')</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($items as $item) {
                $desc   = esc_html(isset($item['description']) ? $item['description'] : '');
                $qty    = esc_html(isset($item['qty']) ? $item['qty'] : 1);
                $unit   = esc_html(isset($item['unit']) ? $item['unit'] : 'Item');
                $rate   = number_format((float)(isset($item['rate']) ? $item['rate'] : 0), 2);
                $amount = number_format((float)(isset($item['amount']) ? $item['amount'] : 0), 2);

                $html .= "<tr>
                    <td>{$desc}</td>
                    <td>{$qty}</td>
                    <td>{$unit}</td>
                    <td class='num'>{$symbol}{$rate}</td>
                    <td class='num'>{$symbol}{$amount}</td>
                </tr>";
            }
            $html .= '</tbody></table>';
        }

        $html .= '<div class="total-card">
            OVERALL ESTIMATED CONTRACT SUM: ' . $symbol . $total . ' ' . $currency . '
        </div>';

        $html .= '<div class="footer"><p>BuildSuite Pro Construction Management & QS Suite | Powered by Antigravity Deepmind Solutions</p></div>';
        $html .= '</body></html>';

        return $html;
    }
}
