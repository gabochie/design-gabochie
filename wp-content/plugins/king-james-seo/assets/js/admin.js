(function($) {
    'use strict';

    $(function() {
        $('.kjs-advance').on('click', function() {
            var runId = $(this).data('run-id');
            $.post(kjsAdmin.ajaxUrl, {
                action: 'kjs_advance_pipeline',
                run_id: runId,
                _ajax_nonce: kjsAdmin.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        });

        $('.kjs-fail').on('click', function() {
            var runId = $(this).data('run-id');
            var reason = prompt('Reason for failure:');
            if (reason !== null) {
                $.post(kjsAdmin.ajaxUrl, {
                    action: 'kjs_fail_pipeline',
                    run_id: runId,
                    reason: reason,
                    _ajax_nonce: kjsAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            }
        });

        $('#kjs-create-pipeline').on('click', function() {
            var name = prompt('Pipeline name:');
            if (name) {
                $.post(kjsAdmin.ajaxUrl, {
                    action: 'kjs_create_pipeline',
                    name: name,
                    _ajax_nonce: kjsAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            }
        });
    });
})(jQuery);
