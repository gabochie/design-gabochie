jQuery(document).ready(function($) {
    var nonce = gab.nonce;

    $('.gab-generate-now').on('click', function() {
        var $btn = $(this);
        var $msg = $('#gab-generate-message');
        $btn.prop('disabled', true);
        $msg.html('<span class="spinner is-active"></span> Generating...');
        $.post(gab.ajaxurl, {
            action: 'gab_generate_now',
            _ajax_nonce: nonce
        }, function(res) {
            if (res.success) {
                $msg.html('<div class="notice notice-success inline"><p>' + res.data.message + '</p></div>');
            } else {
                $msg.html('<div class="notice notice-error inline"><p>' + (res.data.message || 'Generation failed') + '</p></div>');
            }
        }).fail(function() {
            $msg.html('<div class="notice notice-error inline"><p>AJAX error. Check console.</p></div>');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    $('.gab-generate-topic').on('click', function() {
        var $btn = $(this);
        var $msg = $('#gab-generate-message');
        var topic = $('#gab-manual-topic').val() || $('#gab-manual-topic-custom').val();
        if (!topic) {
            $msg.html('<div class="notice notice-warning inline"><p>Please select or type a topic.</p></div>');
            return;
        }
        $btn.prop('disabled', true);
        $msg.html('<span class="spinner is-active"></span> Generating "' + topic + '"...');
        $.post(gab.ajaxurl, {
            action: 'gab_generate_now',
            topic: topic,
            category: $btn.data('category'),
            status: $btn.data('status'),
            _ajax_nonce: nonce
        }, function(res) {
            if (res.success) {
                $msg.html('<div class="notice notice-success inline"><p>' + res.data.message + '</p></div>');
            } else {
                $msg.html('<div class="notice notice-error inline"><p>' + (res.data.message || 'Generation failed') + '</p></div>');
            }
        }).fail(function() {
            $msg.html('<div class="notice notice-error inline"><p>AJAX error.</p></div>');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    $('.gab-reset-schedule').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post(gab.ajaxurl, {
            action: 'gab_schedule_reset',
            _ajax_nonce: nonce
        }, function(res) {
            location.reload();
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });
});
