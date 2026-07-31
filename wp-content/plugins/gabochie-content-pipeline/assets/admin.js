(function($) {
    'use strict';

    var gcp = window.gcp || {};

    $('#gcp-select-all').on('change', function() {
        $('.gcp-check').prop('checked', $(this).prop('checked'));
    });

    $('#gcp-bulk-apply').on('click', function() {
        var action = $('#gcp-bulk-action').val();
        if (!action) return;

        var ids = [];
        $('.gcp-check:checked').each(function() {
            ids.push($(this).val());
        });

        if (!ids.length) {
            alert('Select items to apply bulk action.');
            return;
        }

        if (action === 'gcp_bulk_reject' && !confirm('Reject and trash ' + ids.length + ' drafts?')) return;
        if (action === 'gcp_bulk_approve_publish' && !confirm('Publish ' + ids.length + ' drafts immediately?')) return;

        $('#gcp-bulk-ids').val(ids.join(','));
        $('#gcp-bulk-action-field').val(action);
        $('#gcp-bulk-form').submit();
    });

    $('.gcp-generate-btn').on('click', function() {
        var $btn = $(this);
        var $spinner = $btn.siblings('.spinner');
        var $result = $('#gcp-result');
        var topic = $('#gcp-topic').val().trim();
        var segment = $('#gcp-segment').val();
        var category = $('#gcp-category').val();

        if (!topic) {
            $result.html('<div class="error" style="padding:8px 12px;background:#fcf0f1;border:1px solid #f5c6cb;border-radius:4px;">Please enter a topic.</div>');
            return;
        }

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');

        $.post(gcp.ajaxurl, {
            action: 'gcp_generate',
            topic: topic,
            segment: segment,
            category: category,
            _ajax_nonce: gcp.nonce
        }, function(resp) {
            if (resp.success) {
                $result.html('<div class="success" style="padding:12px;background:#edfaef;border:1px solid #b8e6bf;border-radius:4px;">' + resp.data.message + '</div>');
                $('#gcp-topic').val('');
            } else {
                $result.html('<div class="error" style="padding:8px 12px;background:#fcf0f1;border:1px solid #f5c6cb;border-radius:4px;">' + resp.data.message + '</div>');
            }
        }).fail(function() {
            $result.html('<div class="error" style="padding:8px 12px;background:#fcf0f1;border:1px solid #f5c6cb;border-radius:4px;">Connection error. Please try again.</div>');
        }).always(function() {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });

    $('#gcp-toggle-data').on('click', function(e) {
        e.preventDefault();
        $('#gcp-data-editor').slideToggle();
    });

    $('#gcp-save-data').on('click', function() {
        var $btn = $(this);
        var $msg = $('#gcp-data-message');
        var json = $('#gcp-data-json').val();

        try {
            JSON.parse(json);
        } catch(e) {
            $msg.html('<span style="color:#b32d2e;">Invalid JSON</span>');
            return;
        }

        $.post(gcp.ajaxurl, {
            action: 'gcp_save_data',
            data: json,
            _ajax_nonce: gcp.nonce
        }, function(resp) {
            if (resp.success) {
                $msg.html('<span style="color:#2d8f5e;">Saved.</span>');
            } else {
                $msg.html('<span style="color:#b32d2e;">' + resp.data.message + '</span>');
            }
        }).fail(function() {
            $msg.html('<span style="color:#b32d2e;">Error saving.</span>');
        });
    });
})(jQuery);