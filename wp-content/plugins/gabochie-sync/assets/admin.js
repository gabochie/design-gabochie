(function($) {
    'use strict';

    var $progressWrap = $('.gs-progress-wrap');
    var $progressFill = $('.gs-progress-fill');
    var $progressText = $('.gs-progress-text');
    var $result = $('.gs-result');
    var $diffPanel = $('.gs-diff-panel');

    function showProgress(text) {
        $progressText.text(text || 'Working...');
        $progressWrap.slideDown(200);
        $result.hide();
        $diffPanel.hide();
    }

    function hideProgress() {
        $progressWrap.slideUp(200);
    }

    function showResult(message, type) {
        $result.removeClass('gs-result-success gs-result-error gs-result-info')
            .addClass('gs-result-' + type)
            .html(message)
            .slideDown(200);
    }

    function formatDiff(data) {
        if (!data.changes) return '<p>No diff data available.</p>';

        var html = '';
        var s = data.summary;
        html += '<p><strong>Summary:</strong> ';
        html += s.local_only + ' local-only, ';
        html += s.remote_only + ' remote-only, ';
        html += s.modified + ' modified</p>';

        if (s.local_only > 0) {
            html += '<h4>Only on Local (would be pushed)</h4>';
            data.changes.local_only.forEach(function(item) {
                html += '<div class="gs-diff-item gs-diff-local-only">';
                html += '<span><strong>' + item.type + '</strong>: ' + item.title + '</span>';
                html += '<span class="gs-badge gs-badge-success">LOCAL ONLY</span>';
                html += '</div>';
            });
        }

        if (s.remote_only > 0) {
            html += '<h4>Only on Remote (would be pulled)</h4>';
            data.changes.remote_only.forEach(function(item) {
                html += '<div class="gs-diff-item gs-diff-remote-only">';
                html += '<span><strong>' + item.type + '</strong>: ' + item.title + '</span>';
                html += '<span class="gs-badge gs-badge-partial">REMOTE ONLY</span>';
                html += '</div>';
            });
        }

        if (s.modified > 0) {
            html += '<h4>Modified (conflict)</h4>';
            data.changes.modified.forEach(function(item) {
                html += '<div class="gs-diff-item gs-diff-modified">';
                html += '<span><strong>' + item.type + '</strong>: ' + item.title + '</span>';
                html += '<span class="gs-badge gs-badge-error">MODIFIED</span>';
                html += '</div>';
            });
        }

        if (s.local_only === 0 && s.remote_only === 0 && s.modified === 0) {
            html += '<p style="color:#00a32a;font-weight:600">Everything is in sync!</p>';
        }

        return html;
    }

    // Content sync buttons
    $(document).on('click', '.gs-push-btn, .gs-pull-btn, .gs-dry-run-btn, .gs-diff-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var action = $btn.data('action');

        if (action === 'push' && !confirm(gabochieSync.confirmPush)) return;
        if (action === 'pull' && !confirm(gabochieSync.confirmPull)) return;

        var isDryRun = (action === 'dry-run');
        var isDiff = (action === 'diff');

        var label = action === 'push' ? 'Pushing to live...' :
                    action === 'pull' ? 'Pulling from live...' :
                    action === 'dry-run' ? 'Running dry run...' :
                    'Checking differences...';

        showProgress(label);
        $btn.addClass('loading');

        var endpoint = isDiff ? 'diff' : (action === 'push' ? 'push' : 'pull');
        var method = (action === 'push' || action === 'pull') ? 'POST' : 'GET';
        var body = null;

        if (action === 'push' || action === 'pull') {
            body = JSON.stringify({ dry_run: isDryRun ? 'true' : 'false' });
        }

        $.ajax({
            url: gabochieSync.restUrl + endpoint,
            method: method,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', gabochieSync.restNonce);
                if (body) {
                    xhr.setRequestHeader('Content-Type', 'application/json');
                }
            },
            data: body,
            success: function(res) {
                hideProgress();
                $btn.removeClass('loading');

                if (isDiff) {
                    if (res.success) {
                        $diffPanel.find('.gs-diff-content').html(formatDiff(res));
                        $diffPanel.slideDown(200);
                    } else {
                        showResult(res.message || 'Failed to get diff.', 'error');
                    }
                } else {
                    if (res.success) {
                        var msg = '<strong>Success!</strong> ' + res.message;
                        if (res.dry_run) msg += ' <em>(dry run — nothing changed)</em>';
                        showResult(msg, 'success');
                    } else {
                        showResult('<strong>Error:</strong> ' + (res.message || 'Unknown error'), 'error');
                    }
                }
            },
            error: function(xhr) {
                hideProgress();
                $btn.removeClass('loading');
                var msg = 'Request failed. Check your connection settings.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showResult('<strong>Error:</strong> ' + msg, 'error');
            }
        });
    });

    // Git buttons
    $(document).on('click', '.gs-deploy-pull-btn, .gs-deploy-push-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var action = $btn.data('action');

        if (action === 'git-push' && !confirm('Commit and push all local changes?')) return;
        if (action === 'git-pull' && !confirm(gabochieSync.confirmDeploy)) return;

        var endpoint = action === 'git-pull' ? 'deploy' : 'deploy';
        var label = action === 'git-pull' ? 'Pulling from Git remote...' : 'Pushing to Git remote...';

        showProgress(label);
        $btn.addClass('loading');

        $.ajax({
            url: gabochieSync.restUrl + 'deploy',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', gabochieSync.restNonce);
                xhr.setRequestHeader('Content-Type', 'application/json');
            },
            data: JSON.stringify({ action: action }),
            success: function(res) {
                hideProgress();
                $btn.removeClass('loading');
                if (res.success) {
                    showResult('<strong>Deploy success!</strong> ' + res.message + '<br><pre style="margin-top:8px;white-space:pre-wrap;font-size:12px">' + (res.output || '') + '</pre>', 'success');
                } else {
                    showResult('<strong>Deploy failed:</strong> ' + (res.message || 'Unknown error') + '<br><pre style="margin-top:8px;white-space:pre-wrap;font-size:12px">' + (res.output || '') + '</pre>', 'error');
                }
            },
            error: function() {
                hideProgress();
                $btn.removeClass('loading');
                showResult('<strong>Error:</strong> Request failed.', 'error');
            }
        });
    });

    // Load log on page load
    $.ajax({
        url: gabochieSync.restUrl + 'log',
        method: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', gabochieSync.restNonce);
        },
        success: function(res) {
            // Log is already rendered server-side, this is just for refresh
        }
    });

    // ═══════ STRATEGY TABS ═══════
    $(document).on('click', '.gs-tab', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        $('.gs-tab').removeClass('active');
        $(this).addClass('active');
        $('.gs-panel').removeClass('active');
        $('#gs-' + tab).addClass('active');
    });

    // ═══════ ROADMAP CHECKBOXES ═══════
    $(document).on('change', '.gr-cb', function() {
        var $li = $(this).closest('li');
        if ($(this).is(':checked')) {
            $li.addClass('gr-done');
        } else {
            $li.removeClass('gr-done');
        }
        updateRoadmapProgress();
    });

    function updateRoadmapProgress() {
        var total = 0;
        var done = 0;
        $('.gr-cb').each(function() {
            total++;
            if ($(this).is(':checked')) done++;
        });
        var pct = total > 0 ? Math.round((done / total) * 100) : 0;
        $('.gr-progress-fill').css('width', pct + '%');
        $('.gr-progress-info strong:first').text(done);
        $('.gr-progress-info strong:last').text(total);
        $('.gr-progress-info strong:last').next().text(pct + '%');

        // Update sprint stats
        $('.gr-sprint').each(function() {
            var sTotal = $(this).find('.gr-cb').length;
            var sDone = $(this).find('.gr-cb:checked').length;
            var sPct = sTotal > 0 ? Math.round((sDone / sTotal) * 100) : 0;
            $(this).find('.gr-sprint-pct').text(sPct + '%');
            $(this).find('.gr-sprint-count').text(sDone + '/' + sTotal);
        });
    }

})(jQuery);
