jQuery(document).ready(function($) {

    // Global variable to store active estimate response
    window.currentEstimateData = null;

    // 1. Tab Navigation
    $('.boq-tab-btn').on('click', function() {
        var tabId = $(this).data('tab');
        $('.boq-tab-btn').removeClass('active');
        $('.boq-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
    });

    // 2. BOQ Calculation AJAX
    $('#btn-calculate-boq').on('click', function() {
        var payload = {
            project_type: $('#boq_project_type').val(),
            area: parseFloat($('#boq_area').val()) || 100,
            location: $('#boq_location').val(),
            currency: $('#boq_currency').val() || 'GHS',
            escalation: parseFloat($('#boq_escalation').val()) || 0
        };

        $.ajax({
            url: boqSuiteVars.restUrl + 'calculate-boq',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            beforeSend: function(xhr) {
                if (boqSuiteVars.nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', boqSuiteVars.nonce);
                }
                $('#btn-calculate-boq').text('Calculating SMM7 BOQ...');
            },
            success: function(res) {
                $('#btn-calculate-boq').text('Calculate SMM7 Itemized BOQ');
                window.currentEstimateData = res;

                var symbol = (res.currency === 'USD') ? '$' : 'GH₵ ';
                $('#boq-total-display').text(symbol + res.total_cost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + res.currency);
                
                var $container = $('#boq-bills-container');
                $container.empty();

                if (res.bills && res.bills.length > 0) {
                    $.each(res.bills, function(bIdx, bill) {
                        var billHtml = '<div class="boq-bill-card" style="margin-bottom:20px; border:1px solid #cbd5e1; border-radius:6px; overflow:hidden;">' +
                            '<div style="background:#1e3a8a; color:#fff; padding:10px 15px; font-weight:bold; font-size:14px;">' + bill.bill_name + '</div>' +
                            '<table class="boq-table" style="width:100%; margin:0;">' +
                                '<thead><tr><th>Description of Works</th><th>Qty</th><th>Unit</th><th style="text-align:right;">Rate</th><th style="text-align:right;">Amount</th></tr></thead>' +
                                '<tbody>';

                        $.each(bill.items, function(i, item) {
                            billHtml += '<tr>' +
                                '<td>' + item.description + '</td>' +
                                '<td>' + item.qty + '</td>' +
                                '<td>' + item.unit + '</td>' +
                                '<td style="text-align:right;">' + symbol + item.rate.toFixed(2) + '</td>' +
                                '<td style="text-align:right; font-weight:600;">' + symbol + item.amount.toFixed(2) + '</td>' +
                            '</tr>';
                        });

                        billHtml += '<tr style="background:#f8fafc; font-weight:bold;">' +
                            '<td colspan="4" style="text-align:right;">Section Subtotal:</td>' +
                            '<td style="text-align:right;">' + symbol + bill.subtotal.toFixed(2) + '</td>' +
                        '</tr></tbody></table></div>';

                        $container.append(billHtml);
                    });
                } else {
                    // Fallback table
                    var tableHtml = '<table class="boq-table" id="boq-items-table"><thead><tr><th>Item Description</th><th>Qty</th><th>Unit</th><th style="text-align:right;">Rate</th><th style="text-align:right;">Amount</th></tr></thead><tbody>';
                    $.each(res.items, function(i, item) {
                        tableHtml += '<tr>' +
                            '<td>' + item.description + '</td>' +
                            '<td>' + item.qty + '</td>' +
                            '<td>' + item.unit + '</td>' +
                            '<td style="text-align:right;">' + symbol + item.rate.toFixed(2) + '</td>' +
                            '<td style="text-align:right;">' + symbol + item.amount.toFixed(2) + '</td>' +
                        '</tr>';
                    });
                    tableHtml += '</tbody></table>';
                    $container.html(tableHtml);
                }

                $('#boq-results-area').slideDown();
            },
            error: function() {
                $('#btn-calculate-boq').text('Calculate SMM7 Itemized BOQ');
                alert('Error calculating BOQ estimate. Please check network connection.');
            }
        });
    });

    // 3. Export PDF
    $('#btn-export-boq-pdf').on('click', function() {
        if (!window.currentEstimateData) {
            alert('Please calculate a BOQ estimate first.');
            return;
        }

        var payload = {
            title: $('#boq_project_type option:selected').text(),
            client_name: 'Valued Client',
            location: $('#boq_location').val(),
            currency: window.currentEstimateData.currency || 'GHS',
            total_cost: window.currentEstimateData.total_cost,
            bills: window.currentEstimateData.bills || [],
            items: window.currentEstimateData.items || []
        };

        $.ajax({
            url: boqSuiteVars.restUrl + 'generate-pdf',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            beforeSend: function(xhr) {
                if (boqSuiteVars.nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', boqSuiteVars.nonce);
                }
            },
            success: function(res) {
                var win = window.open('', '_blank');
                win.document.write(res.html);
                win.document.close();
                win.print();
            }
        });
    });

    // 4. BOQ CSV Importer
    $('#btn-import-boq').on('click', function() {
        var csvText = $('#boq_csv_input').val();
        if (!csvText || csvText.trim() === '') {
            alert('Please enter CSV data to import.');
            return;
        }

        var payload = {
            csv_text: csvText,
            import_to_rates: $('#boq_save_to_rates').is(':checked') ? 1 : 0
        };

        $.ajax({
            url: boqSuiteVars.restUrl + 'import-boq',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            beforeSend: function(xhr) {
                if (boqSuiteVars.nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', boqSuiteVars.nonce);
                }
                $('#btn-import-boq').text('Importing...');
            },
            success: function(res) {
                $('#btn-import-boq').text('Parse & Import BOQ Data');
                if (res.success) {
                    var msg = '<div class="boq-alert-success" style="padding:15px; background:#dcfce7; color:#166534; border-radius:6px; margin-top:15px;">' +
                        '<strong>Import Successful!</strong> Processed ' + res.count + ' BOQ items. Total BOQ Value: GH₵ ' + res.total_amount.toLocaleString() + '. ';
                    if (res.master_rates_added) {
                        msg += res.master_rates_added + ' items added to Master Rates database.';
                    }
                    msg += '</div>';
                    $('#import-result-notice').html(msg).slideDown();
                } else {
                    alert(res.message || 'Error importing CSV data.');
                }
            },
            error: function() {
                $('#btn-import-boq').text('Parse & Import BOQ Data');
                alert('Error parsing CSV input.');
            }
        });
    });

    // 5. Create Invoice
    $('#btn-create-invoice').on('click', function() {
        var milestones = [];
        $('.boq-milestone-row').each(function() {
            var desc = $(this).find('.ms-desc').val();
            var amt  = parseFloat($(this).find('.ms-amount').val()) || 0;
            if (desc && amt > 0) {
                milestones.push({ description: desc, amount: amt });
            }
        });

        var payload = {
            client_name: $('#inv_client_name').val(),
            client_email: $('#inv_client_email').val(),
            project_title: $('#inv_project_title').val(),
            milestones: milestones
        };

        $.ajax({
            url: boqSuiteVars.restUrl + 'create-invoice',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            beforeSend: function(xhr) {
                if (boqSuiteVars.nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', boqSuiteVars.nonce);
                }
            },
            success: function(res) {
                $('#invoice-details-text').text('Invoice ' + res.number + ' issued for ' + res.client + ' — Total: ' + res.currency + ' ' + res.total.toLocaleString());
                $('#invoice-created-notice').slideDown();
            }
        });
    });

    // 6. JV Calculator
    $('#btn-calculate-jv').on('click', function() {
        var payload = {
            land_value: parseFloat($('#jv_land_val').val()) || 0,
            construction_cost: parseFloat($('#jv_const_cost').val()) || 0,
            annual_rent: parseFloat($('#jv_annual_rent').val()) || 0
        };

        $.ajax({
            url: boqSuiteVars.restUrl + 'calculate-jv',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            beforeSend: function(xhr) {
                if (boqSuiteVars.nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', boqSuiteVars.nonce);
                }
            },
            success: function(res) {
                $('#jv-total-cap').text('GHS ' + res.total_project_cost.toLocaleString());
                $('#jv-landowner-pct').text(res.landowner_equity_share + '%');
                $('#jv-developer-pct').text(res.developer_equity_share + '%');
                $('#jv-gross-yield').text(res.gross_rental_yield + '%');
                $('#jv-payback').text(res.payback_period_years + ' Years');

                $('#jv-results-area').slideDown();
            }
        });
    });

    // 7. Rate Search with Category & Currency
    $('#btn-search-rates').on('click', function() {
        var q = $('#rate_search_input').val();
        var cat = $('#rate_category_filter').val() || 'all';
        var curr = $('#rate_search_currency').val() || 'GHS';

        var url = boqSuiteVars.restUrl + 'search-rates?q=' + encodeURIComponent(q) + '&category=' + encodeURIComponent(cat) + '&currency=' + encodeURIComponent(curr);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(res) {
                var $tbody = $('#rates-result-table tbody');
                $tbody.empty();

                if (res.length === 0) {
                    $tbody.append('<tr><td colspan="5" style="text-align:center;">No matching rates found.</td></tr>');
                    return;
                }

                $.each(res, function(i, item) {
                    var row = '<tr>' +
                        '<td><strong>' + item.title + '</strong></td>' +
                        '<td><span style="background:#e2e8f0; padding:3px 8px; border-radius:4px; font-size:12px;">' + item.category + '</span></td>' +
                        '<td>' + item.unit + '</td>' +
                        '<td style="font-weight:600;">' + item.formatted_rate + '</td>' +
                        '<td>' + item.location + '</td>' +
                    '</tr>';
                    $tbody.append(row);
                });
            }
        });
    });

});
