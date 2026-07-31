(function($) {
    'use strict';

    $('.gcr-run-scan').on('click', function() {
        var $btn = $(this);
        var $spinner = $btn.siblings('.spinner');
        var $result = $('#gcr-scan-result');
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.text('Scanning...');

        $.post(gcr.ajaxurl, {
            action: 'gcr_scan',
            _ajax_nonce: gcr.nonce
        }, function(res) {
            if (res.success) {
                $result.text(res.data.count + ' posts need refresh.');
                if (res.data.count > 0) {
                    location.reload();
                }
            } else {
                $result.text('Error: ' + res.data.message);
            }
        }).fail(function() {
            $result.text('Connection error.');
        }).always(function() {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });

    $('.gcr-mark-refreshed').on('click', function() {
        var $btn = $(this);
        var postId = $btn.data('post-id');
        $btn.prop('disabled', true).text('Marking...');

        $.post(gcr.ajaxurl, {
            action: 'gcr_refresh',
            post_id: postId,
            _ajax_nonce: gcr.nonce
        }, function(res) {
            if (res.success) {
                $btn.text('Done').css('color', '#2d8f5e');
                $btn.closest('tr').fadeOut(400, function() { $(this).remove(); });
            } else {
                $btn.text('Failed').css('color', '#d63638');
                $btn.prop('disabled', false);
            }
        }).fail(function() {
            $btn.text('Error').css('color', '#d63638');
            $btn.prop('disabled', false);
        });
    });
})(jQuery);
