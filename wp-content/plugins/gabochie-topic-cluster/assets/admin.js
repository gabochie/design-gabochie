(function($) {
    'use strict';

    var gtc = window.gtc || {};

    $('.gtc-view-posts').on('click', function() {
        var segment = $(this).data('segment');
        window.location.href = ajaxurl.replace('admin-ajax.php', 'edit.php?s&post_type=post&meta_key=_gtc_segment&meta_value=' + segment);
    });

    $('#gtc-copy-report').on('click', function() {
        var text = $('#gtc-report-text').text();
        var $textarea = $('<textarea>').val(text).appendTo('body').select();
        try { document.execCommand('copy'); } catch(e) {}
        $textarea.remove();
        var $btn = $(this);
        $btn.text('Copied!');
        setTimeout(function() { $btn.text('Copy Report'); }, 2000);
    });
})(jQuery);