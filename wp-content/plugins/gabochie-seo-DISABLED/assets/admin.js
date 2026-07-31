jQuery(document).ready(function($) {
    var nonce = gseo.nonce;

    $('.gseo-analyze-all').on('click', function() {
        var $btn = $(this);
        var $progress = $('#gseo-analysis-progress');
        $btn.prop('disabled', true);
        $progress.show();

        var postIds = [];
        $('#the-list tr').each(function() {
            var id = $(this).attr('id');
            if (id && id.indexOf('post-') === 0) {
                postIds.push(id.replace('post-', ''));
            }
        });

        $.post(gseo.ajaxurl, {
            action: 'gseo_bulk_analysis',
            post_ids: postIds,
            _ajax_nonce: nonce
        }, function(res) {
            if (res.success) {
                location.reload();
            }
        }).always(function() {
            $btn.prop('disabled', false);
            $progress.hide();
        });
    });

    $('.gseo-clear-cache, .gseo-clear-cache-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post(gseo.ajaxurl, {
            action: 'gseo_clear_analysis_cache',
            _ajax_nonce: nonce
        }, function(res) {
            if (res.success) {
                location.reload();
            }
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    $('.gseo-regen-sitemap').on('click', function() {
        var $btn = $(this);
        var $msg = $('#gseo-tools-message');
        $btn.prop('disabled', true);
        $msg.html('<span class="spinner is-active"></span> Regenerating...');
        $.post(gseo.ajaxurl, {
            action: 'gseo_regenerate_sitemap',
            _ajax_nonce: nonce
        }, function(res) {
            if (res.success) {
                $msg.html('<div class="notice notice-success inline"><p>Sitemap regenerated.</p></div>');
            }
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });
});
