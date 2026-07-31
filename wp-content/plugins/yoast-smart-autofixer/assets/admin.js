/**
 * Yoast Auto-Fixer Dashboard — admin.js v3.0.0
 */
(function($) {
    'use strict';

    var state = {
        page: 1,
        filter: 'all',
        search: '',
        type_filter: '',
        posts: [],
        selected: [],
        preview_post_id: null,
        preview_meta: '',
        settings: null,
        batch_total: 0,
        batch_processed: 0,
        batch_status: 'idle',
        cpts: [],
    };

    /* ═══════════ INIT ═══════════ */

    $(document).ready(function() {
        load_stats();
        load_analysis();
        bind_events();
    });

    /* ═══════════ DATA LOADING ═══════════ */

    function load_stats() {
        $.post(YAF.ajax, { action: 'yaf_get_stats', nonce: YAF.nonce }, function(res) {
            if (!res.success) return;
            var d = res.data;
            $('#yaf-total').text(d.total);
            $('#yaf-optimized').text(d.optimized);
            $('#yaf-missing').text(d.needs_fix);
            $('#yaf-coverage').text(d.coverage + '%');
            $('#yaf-progress-fill').css('width', d.coverage + '%');
            $('#yaf-progress-text').text(d.coverage + '%');

            if (d.issues) {
                $('#yaf-issues-seo').text(d.issues.seo || 0);
                $('#yaf-issues-content').text(d.issues.content || 0);
                $('#yaf-issues-readability').text(d.issues.readability || 0);
                $('#yaf-issues-social').text(d.issues.social || 0);
                $('#yaf-issues-technical').text(d.issues.technical || 0);
            }
        });

        $.post(YAF.ajax, { action: 'yaf_get_gscc_stats', nonce: YAF.nonce }, function(res) {
            if (!res.success || !res.data.available) {
                $('#yaf-gscc-score').text('N/A');
                $('#yaf-gscc-compliant').text('N/A');
                $('#yaf-gscc-fail').text('N/A');
                $('#yaf-issues-gscc').text('0');
                return;
            }
            var g = res.data;
            $('#yaf-gscc-score').text(g.avg_score);
            $('#yaf-gscc-compliant').text(g.compliant);
            $('#yaf-gscc-fail').text(g.non_compliant);
            $('#yaf-issues-gscc').text(g.non_compliant);
        });
    }

    function load_analysis() {
        $('#yaf-tbody').html('<tr><td colspan="8" class="yaf-loading"><span class="spinner is-active"></span> Analyzing...</td></tr>');
        state.selected = [];
        update_fix_selected_btn();

        $.post(YAF.ajax, {
            action: 'yaf_get_analysis',
            nonce: YAF.nonce,
            page: state.page,
            filter: state.filter,
            search: state.search,
            type_filter: state.type_filter,
        }, function(res) {
            if (!res.success) {
                $('#yaf-tbody').html('<tr><td colspan="8" class="yaf-loading">Error loading data.</td></tr>');
                return;
            }
            state.posts = res.data.posts;
            render_table(res.data.posts);
            render_pagination(res.data.pages, res.data.current_page);
        });
    }

    /* ═══════════ RENDER TABLE ═══════════ */

    function render_table(posts) {
        var $tbody = $('#yaf-tbody');
        $tbody.empty();

        if (posts.length === 0) {
            $tbody.html('<tr><td colspan="8" class="yaf-loading">No posts found.</td></tr>');
            return;
        }

        $.each(posts, function(i, p) {
            var meta_html = p.metadesc
                ? '<div class="yaf-meta-text">' + esc_html(p.metadesc) + '</div>'
                : '<div class="yaf-meta-empty">[Missing meta description]</div>';

            var type_badge = '<span class="yaf-type-badge">' + p.type + '</span>';

            var issues_html = '';
            var fixable = 0;
            $.each(p.issues || [], function(j, issue) {
                var badge_class = issue.severity === 'error' ? 'yaf-badge-error' : (issue.severity === 'warning' ? 'yaf-badge-warning' : 'yaf-badge-info');
                var fix_badge = issue.fixable ? ' yaf-badge-fixable' : '';
                issues_html += '<span class="yaf-issue-badge ' + badge_class + fix_badge + '">' + esc_html(issue.title) + '</span> ';
                if (issue.fixable) fixable++;
            });
            if (!issues_html) issues_html = '<span class="yaf-issue-badge yaf-badge-fixable">All good</span>';

            var seo_score = score_badge(p.score);
            var read_score = score_badge(p.readability);
            var gscc = gscc_badge(p.gscc_score);

            var checked = state.selected.indexOf(p.id) !== -1 ? ' checked' : '';

            var extra_actions = '';
            if (YAF.features && YAF.features.image_gen) {
                extra_actions += ' <button class="yaf-btn-image" data-id="' + p.id + '" title="Generate featured image">\uD83D\uDDBC\uFE0F Img</button>';
            }
            if (YAF.features && YAF.features.alt_text) {
                extra_actions += ' <button class="yaf-btn-alt" data-id="' + p.id + '" title="Fix image alt text">\uD83D\uDCCB Alt</button>';
            }
            extra_actions += ' <button class="yaf-btn-links" data-id="' + p.id + '" title="Suggest internal links">\uD83D\uDD17 Links</button>';

            var row = '<tr data-id="' + p.id + '">'
                + '<td class="yaf-col-check"><input type="checkbox" class="yaf-row-check" data-id="' + p.id + '"' + checked + '></td>'
                + '<td><a href="' + esc_attr(p.edit_url) + '" target="_blank"><strong>' + esc_html(p.title) + '</strong></a>' + type_badge + '</td>'
                + '<td>' + meta_html + '</td>'
                + '<td>' + issues_html + '</td>'
                + '<td class="yaf-col-score">' + seo_score + '</td>'
                + '<td class="yaf-col-score">' + read_score + '</td>'
                + '<td class="yaf-col-score yaf-col-gscc">' + gscc + '</td>'
                + '<td class="yaf-col-actions">'
                + '<button class="yaf-btn-issues" data-id="' + p.id + '" title="View all issues">Issues</button> '
                + '<button class="yaf-btn-preview" data-id="' + p.id + '" title="Preview & fix meta">Preview</button> '
                + (fixable > 0 ? '<button class="yaf-btn-fix" data-id="' + p.id + '" title="Fix all auto-fixable issues">\u26A1 Fix</button>' : '')
                + extra_actions
                + '</td></tr>';
            $tbody.append(row);
        });
    }

    function score_badge(score) {
        if (score === 0) return '<span class="yaf-score yaf-score-na">—</span>';
        if (score >= 71) return '<span class="yaf-score yaf-score-good">' + score + '</span>';
        if (score >= 41) return '<span class="yaf-score yaf-score-ok">' + score + '</span>';
        return '<span class="yaf-score yaf-score-bad">' + score + '</span>';
    }

    function gscc_badge(score) {
        if (score === 0) return '<span class="yaf-gscc-badge yaf-gscc-na">—</span>';
        if (score >= 70) return '<span class="yaf-gscc-badge yaf-gscc-good">' + score + '</span>';
        if (score >= 50) return '<span class="yaf-gscc-badge yaf-gscc-ok">' + score + '</span>';
        return '<span class="yaf-gscc-badge yaf-gscc-bad">' + score + '</span>';
    }

    function render_pagination(total_pages, current) {
        var $pag = $('#yaf-pagination').empty();
        if (total_pages <= 1) return;

        if (current > 1) $pag.append('<button class="yaf-page-btn" data-page="' + (current - 1) + '">&laquo;</button>');
        for (var i = 1; i <= total_pages; i++) {
            if (i === current) {
                $pag.append('<button class="yaf-page-btn active">' + i + '</button>');
            } else if (Math.abs(i - current) <= 2 || i === 1 || i === total_pages) {
                $pag.append('<button class="yaf-page-btn" data-page="' + i + '">' + i + '</button>');
            } else if (Math.abs(i - current) === 3) {
                $pag.append('<span style="padding:4px 6px;color:#646970;">...</span>');
            }
        }
        if (current < total_pages) $pag.append('<button class="yaf-page-btn" data-page="' + (current + 1) + '">&raquo;</button>');
    }

    /* ═══════════ EVENTS ═══════════ */

    function bind_events() {
        /* Filters */
        $(document).on('click', '.yaf-filter', function() {
            $('.yaf-filter').removeClass('active');
            $(this).addClass('active');
            state.filter = $(this).data('filter');
            state.page = 1;
            load_analysis();
        });

        /* Search */
        var search_timer;
        $(document).on('keyup', '#yaf-search', function() {
            clearTimeout(search_timer);
            var val = $(this).val();
            search_timer = setTimeout(function() {
                state.search = val;
                state.page = 1;
                load_analysis();
            }, 400);
        });

        /* Pagination */
        $(document).on('click', '.yaf-page-btn[data-page]', function() {
            state.page = parseInt($(this).data('page'));
            load_analysis();
            $('html, body').animate({ scrollTop: 200 }, 200);
        });

        /* Checkboxes */
        $(document).on('change', '#yaf-check-all', function() {
            var checked = $(this).is(':checked');
            $('.yaf-row-check').prop('checked', checked);
            state.selected = [];
            if (checked) {
                $.each(state.posts, function(i, p) { state.selected.push(p.id); });
            }
            update_fix_selected_btn();
        });

        $(document).on('change', '.yaf-row-check', function() {
            var id = parseInt($(this).data('id'));
            if ($(this).is(':checked')) {
                if (state.selected.indexOf(id) === -1) state.selected.push(id);
            } else {
                state.selected = state.selected.filter(function(s) { return s !== id; });
            }
            $('#yaf-check-all').prop('checked', state.selected.length === state.posts.length);
            update_fix_selected_btn();
        });

        /* Fix Single */
        $(document).on('click', '.yaf-btn-fix', function() {
            var $btn = $(this);
            var id = parseInt($btn.data('id'));
            $btn.prop('disabled', true).text('Fixing...');
            $.post(YAF.ajax, { action: 'yaf_fix_single', nonce: YAF.nonce, post_id: id }, function(res) {
                if (res.success) {
                    $btn.addClass('fixed').text('✓ Fixed');
                    load_stats();
                    setTimeout(function() { load_analysis(); }, 1000);
                } else {
                    $btn.prop('disabled', false).text('⚡ Fix');
                    alert('Error: ' + (res.data || 'Fix failed'));
                }
            });
        });

        /* Fix Selected - Async */
        $(document).on('click', '#yaf-btn-fix-selected', function() {
            if (state.selected.length === 0) return;
            var $btn = $(this);
            var ids = state.selected.slice();
            $btn.prop('disabled', true).text('Fixing ' + ids.length + ' posts...');

            $.post(YAF.ajax, { action: 'yaf_fix_bulk_async', nonce: YAF.nonce, post_ids: ids }, function(res) {
                if (res.success) {
                    state.batch_total = res.data.total;
                    state.batch_processed = 0;
                    state.batch_status = 'running';
                    poll_batch_progress();
                } else {
                    $btn.prop('disabled', false).text('Fix Selected');
                }
            });
        });

        /* Fix All - Async with progress */
        $(document).on('click', '#yaf-btn-fix-all', function() {
            if (!confirm('Auto-fix ALL fixable issues across ALL posts and pages?')) return;
            var $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0;"></span> Fixing all...');

            $.post(YAF.ajax, { action: 'yaf_fix_bulk_async', nonce: YAF.nonce }, function(res) {
                if (res.success) {
                    state.batch_total = res.data.total;
                    state.batch_processed = 0;
                    state.batch_status = 'running';
                    $btn.text('Processing 0/' + state.batch_total + '...');
                    poll_batch_progress();
                } else {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-superhero"></span> Fix All');
                }
            });
        });

        function poll_batch_progress() {
            $.post(YAF.ajax, { action: 'yaf_fix_bulk_progress', nonce: YAF.nonce }, function(res) {
                if (!res.success) return;
                var d = res.data;
                state.batch_processed = d.processed;
                state.batch_status = d.status;

                var $btn = $('#yaf-btn-fix-all');
                if (d.status === 'running') {
                    $btn.text('Processing ' + d.processed + '/' + d.total + ' (' + d.fixed + ' fixes)...');
                    setTimeout(poll_batch_progress, 2000);
                } else if (d.status === 'complete') {
                    $btn.html('<span class="dashicons dashicons-superhero"></span> Fix All');
                    alert('Complete! Fixed ' + d.fixed + ' issues across ' + d.total + ' posts.');
                    load_stats();
                    load_analysis();
                } else {
                    $btn.html('<span class="dashicons dashicons-superhero"></span> Fix All');
                }
            });
        }

        /* Preview - Extended with OG/Twitter tabs */
        $(document).on('click', '.yaf-btn-preview', function() {
            var post_id = parseInt($(this).data('id'));
            state.preview_post_id = post_id;
            state.preview_meta = '';

            $.post(YAF.ajax, { action: 'yaf_preview', nonce: YAF.nonce, post_id: post_id }, function(res) {
                if (!res.success) return;
                var d = res.data;
                state.preview_meta = d.preview;
                var url = d.url || YAF.site;
                try { url = new URL(url).pathname; } catch(e) {}
                $('#yaf-preview-url').text(url);
                $('#yaf-preview-title').text(d.seo_title);
                $('#yaf-preview-desc').text(d.preview);
                $('#yaf-preview-chars').text(d.chars + ' / 155');
                $('#yaf-preview-existing').text(d.existing);

                // Extended preview
                $('#yaf-preview-og-title').text(d.og_title || '—');
                $('#yaf-preview-og-desc').text(d.og_desc || '—');
                $('#yaf-preview-tw-title').text(d.tw_title || '—');
                $('#yaf-preview-tw-desc').text(d.tw_desc || '—');

                // Show Google tab by default
                $('.yaf-preview-tab').removeClass('active');
                $('.yaf-preview-tab[data-type="google"]').addClass('active');
                $('.yaf-preview-pane').hide();
                $('#yaf-preview-pane-google').show();

                $('#yaf-preview-modal').show();
            });
        });

        /* Apply from preview */
        $(document).on('click', '#yaf-preview-apply', function() {
            if (!state.preview_post_id) return;
            var $btn = $(this);
            $btn.prop('disabled', true).text('Applying...');
            $.post(YAF.ajax, {
                action: 'yaf_fix_issue',
                nonce: YAF.nonce,
                post_id: state.preview_post_id,
                issue_id: 'missing_metadesc'
            }, function(res) {
                $btn.prop('disabled', false).text('Apply This Meta');
                $('#yaf-preview-modal').hide();
                load_stats();
                load_analysis();
            });
        });

        /* Regenerate AI */
        $(document).on('click', '#yaf-preview-ai', function() {
            if (!state.preview_post_id) return;
            var $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            $.post(YAF.ajax, { action: 'yaf_generate_ai', nonce: YAF.nonce, post_id: state.preview_post_id }, function(res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-superhero"></span> Regenerate AI');
                if (res.success) {
                    state.preview_meta = res.data.meta;
                    $('#yaf-preview-desc').text(res.data.meta);
                    $('#yaf-preview-chars').text(res.data.meta.length + ' / 155');
                } else {
                    alert('AI generation failed: ' + (res.data || 'No API key?'));
                }
            });
        });

        /* Issues Modal */
        $(document).on('click', '.yaf-btn-issues', function() {
            var post_id = parseInt($(this).data('id'));
            var post = state.posts.find(function(p) { return p.id === post_id; });
            if (!post) return;

            $('#yaf-issue-modal-title span').text(post.title);
            var $list = $('#yaf-issue-list').empty();

            if (post.issues.length === 0) {
                $list.html('<p style="text-align:center;color:#137333;padding:20px;">All good! No issues found.</p>');
            } else {
                $.each(post.issues, function(j, issue) {
                    var icon_class = issue.severity;
                    var icon = issue.severity === 'error' ? '✕' : (issue.severity === 'warning' ? '!' : 'i');
                    var action_html = '';
                    if (issue.fixable) {
                        action_html = '<button class="yaf-issue-fix-btn" data-post="' + post_id + '" data-issue="' + issue.id + '">⚡ Fix</button>';
                    } else if (issue.edit_url) {
                        action_html = '<a href="' + esc_attr(issue.edit_url) + '" target="_blank" class="yaf-issue-manual-btn">Edit →</a>';
                    }

                    $list.append(
                        '<div class="yaf-issue-item">'
                        + '<div class="yaf-issue-item-icon ' + icon_class + '">' + icon + '</div>'
                        + '<div class="yaf-issue-item-text">'
                        + '<div class="yaf-issue-item-title">' + esc_html(issue.title) + '</div>'
                        + '<div class="yaf-issue-item-desc">' + esc_html(issue.description) + '</div>'
                        + '</div>'
                        + '<div class="yaf-issue-item-actions">' + action_html + '</div>'
                        + '</div>'
                    );
                });
            }

            state.preview_post_id = post_id;
            $('#yaf-issue-modal').show();
        });

        /* Fix from issues modal */
        $(document).on('click', '.yaf-issue-fix-btn', function() {
            var $btn = $(this);
            var post_id = parseInt($btn.data('post'));
            var issue_id = $btn.data('issue');
            $btn.prop('disabled', true).text('Fixing...');
            $.post(YAF.ajax, { action: 'yaf_fix_issue', nonce: YAF.nonce, post_id: post_id, issue_id: issue_id }, function(res) {
                if (res.success && res.data.result) {
                    $btn.text('✓ Fixed').css({ background: '#e6f4ea', color: '#137333', border: '1px solid #137333' });
                } else {
                    $btn.prop('disabled', false).text('⚡ Fix');
                }
            });
        });

        /* Fix All from issues modal */
        $(document).on('click', '#yaf-fix-all-modal', function() {
            if (!state.preview_post_id) return;
            var $btn = $(this);
            $btn.prop('disabled', true).text('Fixing...');
            $.post(YAF.ajax, { action: 'yaf_fix_all_issues', nonce: YAF.nonce, post_id: state.preview_post_id }, function(res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-superhero"></span> Fix All Auto-Fixable');
                if (res.success) {
                    $('#yaf-issue-modal').hide();
                    load_stats();
                    load_analysis();
                }
            });
        });

        /* Undo */
        $(document).on('click', '#yaf-btn-undo', function() {
            $.post(YAF.ajax, { action: 'yaf_undo', nonce: YAF.nonce }, function(res) {
                if (res.success) {
                    alert('Undid: ' + res.data.undone.field + ' on "' + res.data.undone.title + '"');
                    load_stats();
                    load_analysis();
                } else {
                    alert('Nothing to undo');
                }
            });
        });

        /* History */
        $(document).on('click', '#yaf-btn-history', function() {
            $.post(YAF.ajax, { action: 'yaf_get_history', nonce: YAF.nonce }, function(res) {
                var $list = $('#yaf-history-list').empty();
                if (!res.success || res.data.history.length === 0) {
                    $list.html('<p style="text-align:center;color:#646970;padding:20px;">No changes yet.</p>');
                } else {
                    $.each(res.data.history, function(i, h) {
                        $list.append(
                            '<div class="yaf-history-item">'
                            + '<div class="yaf-history-head">'
                            + '<span class="yaf-history-title">' + esc_html(h.title) + '</span>'
                            + '<span class="yaf-history-time">' + esc_html(h.time) + '</span>'
                            + '</div>'
                            + '<div class="yaf-history-field">' + esc_html(h.field) + '</div>'
                            + '<div class="yaf-history-detail"><span class="yaf-history-old">' + esc_html(h.old) + '</span> → <span class="yaf-history-new">' + esc_html(h.new) + '</span></div>'
                            + '</div>'
                        );
                    });
                }
                $('#yaf-history-modal').show();
            });
        });

        /* Settings - Load from server */
        $(document).on('click', '#yaf-btn-settings', function() {
            $.post(YAF.ajax, { action: 'yaf_get_settings', nonce: YAF.nonce }, function(res) {
                if (!res.success) return;
                var s = res.data;
                state.settings = s;

                $('#yaf-set-tone').val(s.tone);
                $('#yaf-set-length').val(s.target_length);
                $('#yaf-set-auto-save').prop('checked', s.auto_fix_on_save == 1);

                // Load CPTs
                $.post(YAF.ajax, { action: 'yaf_get_cpts', nonce: YAF.nonce }, function(cpt_res) {
                    if (cpt_res.success) {
                        state.cpts = cpt_res.data.cpts;
                        var $cpt_list = $('#yaf-cpt-list').empty();
                        $.each(cpt_res.data.cpts, function(i, cpt) {
                            var checked = s.post_types.indexOf(cpt.name) !== -1 ? ' checked' : '';
                            $cpt_list.append('<label style="display:inline-block;margin:4px 8px 4px 0;"><input type="checkbox" name="post_types[]" value="' + cpt.name + '"' + checked + '> ' + cpt.label + '</label>');
                        });
                    }
                });

                // Load provider cards
                if (s.providers) {
                    var providers = s.providers;
                    $('.yaf-provider-card').each(function() {
                        var $card = $(this);
                        var pk = $card.data('provider');
                        var cfg = providers[pk] || { enabled: 0, key: '', model: '', priority: 99 };
                        $card.find('.yaf-provider-enabled').prop('checked', cfg.enabled == 1);
                        $card.find('.yaf-provider-key').val(cfg.key);
                        $card.find('.yaf-provider-model').val(cfg.model);
                        $card.find('.yaf-provider-priority').val(cfg.priority);
                        if (cfg.enabled == 1) {
                            $card.addClass('yaf-provider-active');
                            $card.find('.yaf-provider-body').show();
                        } else {
                            $card.removeClass('yaf-provider-active');
                            $card.find('.yaf-provider-body').hide();
                        }
                    });
                }

                // Load feature flags
                if (s.feature_image_gen) $('#yaf-feature-image-gen').prop('checked', true);
                if (s.feature_alt_text) $('#yaf-feature-alt-text').prop('checked', true);
                if (s.feature_breadcrumb) $('#yaf-feature-breadcrumb').prop('checked', true);
                if (s.feature_faq_schema) $('#yaf-feature-faq-schema').prop('checked', true);
                if (s.feature_local_seo) $('#yaf-feature-local-seo').prop('checked', true);
                if (s.feature_saas_dash) $('#yaf-feature-saas-dash').prop('checked', true);
                if (s.feature_content_brief) $('#yaf-feature-content-brief').prop('checked', true);

                $('#yaf-settings-modal').show();
            });
        });

        /* Provider toggle */
        $(document).on('change', '.yaf-provider-enabled', function() {
            var $card = $(this).closest('.yaf-provider-card');
            var $body = $card.find('.yaf-provider-body');
            if ($(this).is(':checked')) {
                $card.addClass('yaf-provider-active');
                $body.slideDown(200);
            } else {
                $card.removeClass('yaf-provider-active');
                $body.slideUp(200);
            }
        });

        /* Test provider */
        $(document).on('click', '.yaf-test-btn', function() {
            var $btn = $(this);
            var provider = $btn.data('provider');
            var $card = $btn.closest('.yaf-provider-card');
            var key = $card.find('.yaf-provider-key').val();
            if (!key) { alert('Enter an API key first.'); return; }

            $btn.prop('disabled', true).text('Testing...');
            $.post(YAF.ajax, {
                action: 'yaf_test_provider',
                nonce: YAF.nonce,
                provider: provider,
                key: key,
            }, function(res) {
                $btn.prop('disabled', false).text('Test');
                if (res.success) {
                    $card.find('.yaf-provider-status').removeClass('yaf-status-off yaf-status-error').addClass('yaf-status-ok').text('Connected');
                    alert(res.data.message);
                } else {
                    $card.find('.yaf-provider-status').removeClass('yaf-status-ok').addClass('yaf-status-error').text('Failed');
                    alert(res.data || 'Connection failed');
                }
            });
        });

        $(document).on('click', '#yaf-save-settings', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            var post_types = [];
            $('#yaf-cpt-list input:checked').each(function() {
                post_types.push($(this).val());
            });

            var data = {
                action: 'yaf_save_settings',
                nonce: YAF.nonce,
                tone: $('#yaf-set-tone').val(),
                target_length: $('#yaf-set-length').val(),
                enforce_kw: 1,
                auto_fix_on_save: $('#yaf-set-auto-save').is(':checked') ? 1 : 0,
                openai_key: '',
                openai_model: 'gpt-4o-mini',
                yaf_p_enabled: {},
                yaf_p_key: {},
                yaf_p_model: {},
                yaf_p_priority: {},
                post_types: post_types,
                feature_image_gen: $('#yaf-feature-image-gen').is(':checked') ? 1 : 0,
                feature_alt_text: $('#yaf-feature-alt-text').is(':checked') ? 1 : 0,
                feature_breadcrumb: $('#yaf-feature-breadcrumb').is(':checked') ? 1 : 0,
                feature_faq_schema: $('#yaf-feature-faq-schema').is(':checked') ? 1 : 0,
                feature_local_seo: $('#yaf-feature-local-seo').is(':checked') ? 1 : 0,
                feature_saas_dash: $('#yaf-feature-saas-dash').is(':checked') ? 1 : 0,
                feature_content_brief: $('#yaf-feature-content-brief').is(':checked') ? 1 : 0,
            };

            $('.yaf-provider-card').each(function() {
                var $card = $(this);
                var pk = $card.data('provider');
                data.yaf_p_enabled[pk] = $card.find('.yaf-provider-enabled').is(':checked') ? 1 : 0;
                data.yaf_p_key[pk] = $card.find('.yaf-provider-key').val();
                data.yaf_p_model[pk] = $card.find('.yaf-provider-model').val();
                data.yaf_p_priority[pk] = $card.find('.yaf-provider-priority').val();
            });

            $.post(YAF.ajax, data, function(res) {
                $btn.prop('disabled', false).text('Save Settings');
                if (res.success) {
                    $('#yaf-settings-modal').hide();
                    // Reload data with new post types
                    load_stats();
                    load_analysis();
                }
            });
        });

        /* Modal close */
        $(document).on('click', '.yaf-modal-close, .yaf-modal-overlay', function() {
            $(this).closest('.yaf-modal').hide();
        });

        /* Stop propagation on modal content */
        $(document).on('click', '.yaf-modal-content', function(e) {
            e.stopPropagation();
        });

        /* Generate featured image */
        $(document).on('click', '.yaf-btn-image', function() {
            var $btn = $(this);
            var id = parseInt($btn.data('id'));
            $btn.prop('disabled', true).text('Generating...');
            $.post(YAF.ajax, { action: 'yaf_generate_featured_image', nonce: YAF.nonce, post_id: id }, function(res) {
                if (res.success) {
                    $btn.text('\u2705 Set').css({ background: '#e6f4ea', color: '#137333', border: '1px solid #137333' });
                    alert('Featured image generated!');
                    load_analysis();
                } else {
                    $btn.prop('disabled', false).html('\uD83D\uDDBC\uFE0F Img');
                    alert('Failed: ' + (res.data || 'No image could be generated'));
                }
            });
        });

        /* Fix alt text */
        $(document).on('click', '.yaf-btn-alt', function() {
            var $btn = $(this);
            var id = parseInt($btn.data('id'));
            $btn.prop('disabled', true).text('Fixing...');
            $.post(YAF.ajax, { action: 'yaf_generate_alt_text', nonce: YAF.nonce, post_id: id }, function(res) {
                if (res.success) {
                    $btn.text('\u2705 Set').css({ background: '#e6f4ea', color: '#137333', border: '1px solid #137333' });
                    alert('Alt text updated for ' + (res.data.updated_images || 0) + ' images!');
                    load_analysis();
                } else {
                    $btn.prop('disabled', false).html('\uD83D\uDCCB Alt');
                }
            });
        });

        /* Suggest internal links */
        $(document).on('click', '.yaf-btn-links', function() {
            var $btn = $(this);
            var id = parseInt($btn.data('id'));
            $btn.prop('disabled', true).text('Searching...');
            $.post(YAF.ajax, { action: 'yaf_suggest_links', nonce: YAF.nonce, post_id: id }, function(res) {
                $btn.prop('disabled', false).html('\uD83D\uDD17 Links');
                if (!res.success || !res.data.links || res.data.links.length === 0) {
                    alert('No link suggestions found.');
                    return;
                }
                var msg = 'Suggested internal links:\n';
                $.each(res.data.links, function(i, link) {
                    msg += '\n- ' + link.title + '\n  ' + link.url + '\n  (' + link.reason + ')';
                });
                alert(msg);
            });
        });

        /* Content brief generator - Add to toolbar */
        $(document).on('click', '#yaf-btn-brief', function() {
            var kw = prompt('Enter a keyword to generate a content brief:');
            if (!kw || kw.trim() === '') return;
            var $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            $.post(YAF.ajax, { action: 'yaf_generate_content_brief', nonce: YAF.nonce, keyword: kw.trim() }, function(res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-welcome-write-blog"></span> Brief');
                if (res.success) {
                    // Open in a new modal
                    var $modal = $('<div class="yaf-modal" style="display:flex;"><div class="yaf-modal-overlay"></div><div class="yaf-modal-content yaf-modal-wide"><div class="yaf-modal-header"><h2>Content Brief: ' + esc_html(kw) + '</h2><button class="yaf-modal-close">&times;</button></div><div class="yaf-modal-body"><pre style="white-space:pre-wrap;font-size:13px;line-height:1.6;background:#f9f9f9;padding:16px;border-radius:8px;border:1px solid #e0e0e0;">' + esc_html(res.data.brief) + '</pre></div><div class="yaf-modal-footer"><button class="button yaf-modal-close">Close</button></div></div></div>');
                    $('body').append($modal);
                    $modal.on('click', '.yaf-modal-close, .yaf-modal-overlay', function() { $modal.remove(); });
                    $modal.on('click', '.yaf-modal-content', function(e) { e.stopPropagation(); });
                } else {
                    alert('Failed: ' + (res.data || 'Generation failed'));
                }
            });
        });

        /* Extended preview - show OG/Twitter tabs */
        $(document).on('click', '.yaf-preview-tab', function() {
            $('.yaf-preview-tab').removeClass('active');
            $(this).addClass('active');
            var type = $(this).data('type');
            $('.yaf-preview-pane').hide();
            $('#yaf-preview-pane-' + type).show();
        });
    }

    /* ═══════════ HELPERS ═══════════ */

    function update_fix_selected_btn() {
        var $btn = $('#yaf-btn-fix-selected');
        if (state.selected.length > 0) {
            $btn.prop('disabled', false).text('Fix Selected (' + state.selected.length + ')');
        } else {
            $btn.prop('disabled', true).text('Fix Selected');
        }
    }

    function esc_html(str) {
        if (!str) return '';
        return $('<span>').text(str).html();
    }

    function esc_attr(str) {
        if (!str) return '';
        return str.replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

})(jQuery);
