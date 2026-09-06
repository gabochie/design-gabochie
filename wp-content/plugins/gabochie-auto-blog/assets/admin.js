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

    $('.gab-ai-test').on('click', function() {
        var $btn = $(this);
        var $msg = $('#gab-ai-test-result, #gab-bank-message').first();
        $btn.prop('disabled', true);
        $msg.html('<span class="spinner is-active"></span> Testing AI connection...');
        $.post(gab.ajaxurl, {
            action: 'gab_ai_test',
            _ajax_nonce: nonce
        }, function(res) {
            if (res.success) {
                $msg.html('<div class="notice notice-success inline"><p>' + res.data.message + '</p></div>');
            } else {
                $msg.html('<div class="notice notice-error inline"><p>' + (res.data.message || 'Test failed') + '</p></div>');
            }
        }).fail(function() {
            $msg.html('<div class="notice notice-error inline"><p>AJAX error checking the AI connection.</p></div>');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    $('.gab-bank-run').on('click', function() {
        var $btn = $(this);
        var $msg = $('#gab-bank-message');
        var date = $btn.data('date') || '';
        var force = $btn.data('force') || '';
        $btn.prop('disabled', true);
        $msg.html('<span class="spinner is-active"></span> Running executor... (may take about a minute)');
        $.post(gab.ajaxurl, {
            action: 'gab_bank_run',
            date: date,
            force: force,
            _ajax_nonce: nonce
        }, function(res) {
            if (res.success) {
                var r = res.data.result || {};
                var msgs = [];
                if (r.skipped) msgs.push('Skipped: ' + r.skipped);
                if (r.status) msgs.push('Status: ' + r.status);
                if (r.post_id) msgs.push('Post #' + r.post_id);
                var html = '<div class="notice notice-' + (r.status === 'error' ? 'error' : 'success') + ' inline"><p>' + (msgs.join(' &middot; ') || 'Done.') + '</p>';
                if (r.qa) html += '<pre style="max-height:220px;overflow:auto;font-size:11px;white-space:pre-wrap;">' + JSON.stringify(r.qa, null, 2) + '</pre>';
                html += '</div>';
                $msg.html(html);
            } else {
                $msg.html('<div class="notice notice-error inline"><p>' + (res.data.message || 'Run failed') + '</p></div>');
            }
        }).fail(function() {
            $msg.html('<div class="notice notice-error inline"><p>AJAX error running the executor.</p></div>');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });
});
