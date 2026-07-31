(function($) {
    'use strict';

    function loadStats() {
        var $spinner = $('#gar-refresh-stats').siblings('.spinner');
        $spinner.addClass('is-active');

        $.post(gar.ajaxurl, {
            action: 'gar_get_stats',
            _ajax_nonce: gar.nonce
        }, function(res) {
            if (res.success) {
                $('#gar-avg-rpm strong').text('$' + res.data.avg_rpm);
                $('#gar-impressions strong').text(res.data.total_impressions);
                $('#gar-clicks strong').text(res.data.total_clicks);
                $('#gar-ctr strong').text(res.data.ctr + '%');
                $('#gar-tracked strong').text(res.data.tracked_posts);

                var $log = $('#gar-test-log');
                if (res.data.log && res.data.log.length > 0) {
                    var html = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Date</th><th>Config</th><th>RPM</th></tr></thead><tbody>';
                    $.each(res.data.log, function(i, entry) {
                        html += '<tr><td>' + entry.date + '</td><td>' + (entry.config || '—') + '</td><td>' + (entry.rpm || '—') + '</td></tr>';
                    });
                    html += '</tbody></table>';
                    $log.html(html);
                } else {
                    $log.html('<p style="color:#787c82;">No test data yet.</p>');
                }
            }
        }).always(function() {
            $spinner.removeClass('is-active');
        });
    }

    $(document).ready(function() {
        loadStats();
    });

    $('#gar-refresh-stats').on('click', function() {
        loadStats();
    });
})(jQuery);
