(function($) {
  'use strict';

  // ===== CHAT =====
  var chatContainer = $('.ghbc-chat-container');
  if (chatContainer.length) {
    var messages = $('#ghbc-messages');
    var input = $('#ghbc-input');
    var sendBtn = $('#ghbc-send');
    var product = chatContainer.data('product') || 'compliance';
    var history = [];

    // Auto-resize textarea
    input.on('input', function() {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Send on Enter (Shift+Enter = newline)
    input.on('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });

    sendBtn.on('click', sendMessage);

    // Example buttons
    $(document).on('click', '.ghbc-example-btn', function() {
      input.val($(this).text().replace(/"/g, ''));
      sendMessage();
    });

    function sendMessage() {
      var query = input.val().trim();
      if (!query) return;

      addMessage(query, 'user');
      input.val('');
      input.css('height', 'auto');
      showTyping();
      loadRemaining();

      $.post(ghbc_ajax.ajax_url, {
        action: 'ghbc_chat',
        query: query,
        product: product,
        history: JSON.stringify(history),
        nonce: ghbc_ajax.nonce
      }, function(res) {
        removeTyping();
        if (res.success) {
          addMessage(res.data.response, 'bot');
          history.push({ role: 'user', content: query });
          history.push({ role: 'assistant', content: res.data.response });
          loadRemaining();
        } else {
          addMessage(res.data.message || 'Error processing query.', 'bot');
        }
      }).fail(function() {
        removeTyping();
        addMessage('Network error. Check your connection and try again.', 'bot');
      });
    }

    function addMessage(text, role) {
      var html = '<div class="ghbc-message ghbc-' + role + '">' +
        '<div class="ghbc-avatar">' + (role === 'user' ? '&#128100;' : '&#129302;') + '</div>' +
        '<div class="ghbc-bubble">' + formatText(text) + '</div></div>';
      messages.append(html);
      messages.scrollTop(messages[0].scrollHeight);
    }

    function formatText(text) {
      return text
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/\n/g, '<br>')
        .replace(/Part (\d+)/g, '<strong>Part $1</strong>')
        .replace(/Section (\d+\.\d+)/g, '<strong>Section $1</strong>')
        .replace(/Clause (\d+\.\d+\.?\d*)/g, '<strong>Clause $1</strong>')
        .replace(/Table (\d+\.?\d*)/g, '<strong>Table $1</strong>');
    }

    function showTyping() {
      messages.append('<div class="ghbc-message ghbc-bot ghbc-typing" id="ghbc-typing">' +
        '<div class="ghbc-avatar">&#129302;</div>' +
        '<div class="ghbc-bubble"></div></div>');
      messages.scrollTop(messages[0].scrollHeight);
    }

    function removeTyping() {
      $('#ghbc-typing').remove();
    }

    function loadRemaining() {
      // Remaining queries shown via REST endpoint — simplified for now
    }
  }

  // ===== EXAM =====
  var examContainer = $('.ghbc-exam-container');
  if (examContainer.length) {
    var score = 0;
    var streak = 0;
    var currentQuestion = null;
    var answered = false;

    $('#ghbc-next-q').on('click', function() {
      var btn = $(this);
      btn.prop('disabled', true).text('Loading...');
      answered = false;

      $.post(ghbc_ajax.ajax_url, {
        action: 'ghbc_exam_question',
        nonce: ghbc_ajax.nonce
      }, function(res) {
        if (res.success) {
          currentQuestion = res.data;
          renderQuestion(res.data);
        } else {
          $('#ghbc-exam-body').html('<div class="ghbc-error">Failed to load question.</div>');
        }
        btn.prop('disabled', false).text('Next Question');
      });
    });

    function renderQuestion(q) {
      var html = '<div class="ghbc-question"><strong>' + q.question + '</strong></div>';
      var letters = ['A', 'B', 'C', 'D'];
      letters.forEach(function(l) {
        html += '<button class="ghbc-option" data-letter="' + l + '" data-correct="' + q.correct + '">' +
          l + '. ' + (q.options[l] || '') + '</button>';
      });
      html += '<div id="ghbc-exam-feedback" style="margin-top:12px;"></div>';
      $('#ghbc-exam-body').html(html);

      $('.ghbc-option').on('click', function() {
        if (answered) return;
        answered = true;
        var self = $(this);
        var chosen = self.data('letter');
        var correct = self.data('correct');
        var isCorrect = chosen === correct;

        self.addClass(isCorrect ? 'correct' : 'wrong');
        if (!isCorrect) {
          $('.ghbc-option[data-letter="' + correct + '"]').addClass('correct');
        }
        $('.ghbc-option').prop('disabled', true);

        if (isCorrect) {
          score++;
          streak++;
        } else {
          streak = 0;
        }

        $('#ghbc-score').text(score);
        $('#ghbc-streak').text(streak);

        var feedback = isCorrect
          ? '<div style="color:#2e7d32;font-weight:600;">&#10003; Correct! +1 point</div>'
          : '<div style="color:#c62828;font-weight:600;">&#10060; Incorrect. -0.25 points</div>';
        feedback += '<div style="margin-top:8px;font-size:13px;color:#666;">' + (q.explanation || '') + '</div>';
        feedback += '<div style="margin-top:4px;font-size:12px;color:#999;">Clause: ' + (q.clause || 'N/A') + '</div>';
        $('#ghbc-exam-feedback').html(feedback);
      });
    }
  }

  // ===== SMALL BUILDING CHECK =====
  var sbContainer = $('.ghbc-sb-container');
  if (sbContainer.length) {
    $('#ghbc-sb-check').on('click', function() {
      var btn = $(this);
      btn.prop('disabled', true).text('Checking...');

      $.post(ghbc_ajax.ajax_url, {
        action: 'ghbc_small_building_check',
        storeys: $('#sb-storeys').val(),
        area: $('#sb-area').val(),
        height: $('#sb-height').val(),
        occupancy: $('#sb-occupancy').val(),
        nonce: ghbc_ajax.nonce
      }, function(res) {
        if (res.success) {
          var cls = res.data.qualifies ? 'pass' : 'fail';
          var icon = res.data.qualifies ? '&#10003;' : '&#10060;';
          $('#ghbc-sb-result')
            .removeClass('pass fail')
            .addClass(cls)
            .html('<div style="font-size:20px;font-weight:700;margin-bottom:8px;">' + icon + ' ' +
              (res.data.qualifies ? 'Qualifies as Small Building' : 'Does Not Qualify') + '</div>' +
              '<div style="font-size:14px;line-height:1.6;">' + res.data.explanation.replace(/\n/g, '<br>') + '</div>')
            .show();
        } else {
          $('#ghbc-sb-result')
            .removeClass('pass fail')
            .addClass('fail')
            .html('<div class="ghbc-error">Error: ' + (res.data.message || 'Check failed') + '</div>')
            .show();
        }
        btn.prop('disabled', false).text('Check Qualification');
      });
    });
  }
  // ===== FIRE SAFETY =====
  var fsContainer = $('.ghbc-fs-container');
  if (fsContainer.length) {
    $('#ghbc-fs-check').on('click', function() {
      var btn = $(this);
      btn.prop('disabled', true).text('Checking...');

      $.post(ghbc_ajax.ajax_url, {
        action: 'ghbc_fire_safety',
        occupancy: $('#fs-occupancy').val(),
        height: $('#fs-height').val(),
        area: $('#fs-area').val(),
        storeys: $('#fs-storeys').val(),
        sprinklered: $('#fs-sprinklered').is(':checked') ? 1 : 0,
        basement: $('#fs-basement').is(':checked') ? 1 : 0,
        nonce: ghbc_ajax.nonce
      }, function(res) {
        if (res.success) {
          var d = res.data;
          var html = '<div class="ghbc-fs-section">';
          html += '<h3 style="margin:0 0 12px;">&#128293; Fire Resistance Ratings</h3>';
          html += '<table class="ghbc-fs-table"><tr><th>Element</th><th>Rating</th></tr>';
          var labels = {'structural_frame':'Structural Frame','bearing_walls':'Bearing Walls','floors':'Floors','roofs':'Roofs'};
          $.each(d.ratings, function(k, v) {
            var hours = v.match(/(\d+\.?\d*) hour/);
            html += '<tr><td>' + (labels[k] || k) + '</td><td>' + (hours ? hours[0] : 'N/A') + '</td></tr>';
          });
          html += '</table></div>';

          html += '<div class="ghbc-fs-section"><h3>&#128167; Sprinkler Requirement</h3>';
          html += '<p>' + d.sprinkler.replace(/\n/g, '<br>') + '</p></div>';

          html += '<div class="ghbc-fs-section"><h3>&#128694; Travel Distance</h3>';
          html += '<p>' + d.travel.replace(/\n/g, '<br>') + '</p></div>';

          if (d.llm_summary) {
            html += '<div class="ghbc-fs-section"><h3>&#129302; AI Summary</h3>';
            html += '<p>' + d.llm_summary.replace(/\n/g, '<br>') + '</p></div>';
          }

          html += '<div class="ghbc-fs-disclaimer">This is advisory. Always verify with a qualified fire engineer.</div>';
          $('#ghbc-fs-result').html(html).show();
        } else {
          $('#ghbc-fs-result')
            .html('<div class="ghbc-error">Error: ' + (res.data.message || 'Check failed') + '</div>')
            .show();
        }
        btn.prop('disabled', false).text('Check Fire Safety Requirements');
      });
    });
  }

  // ===== TRAVEL DISTANCE =====
  var tdContainer = $('.ghbc-td-container');
  if (tdContainer.length) {
    $('#ghbc-td-check').on('click', function() {
      var btn = $(this);
      btn.prop('disabled', true).text('Calculating...');

      $.post(ghbc_ajax.ajax_url, {
        action: 'ghbc_travel_distance',
        occupancy: $('#td-occupancy').val(),
        sprinklered: $('#td-sprinklered').is(':checked') ? 1 : 0,
        nonce: ghbc_ajax.nonce
      }, function(res) {
        if (res.success) {
          var d = res.data;
          var status = d.sprinklered ? 'Sprinklered' : 'Non-Sprinklered';
          var html = '<div class="ghbc-fs-section">';
          html += '<h3 style="margin:0 0 12px;">&#128200; Travel Distance Result</h3>';
          html += '<table class="ghbc-fs-table">';
          html += '<tr><th>Occupancy</th><td>' + d.occupancy + '</td></tr>';
          html += '<tr><th>System</th><td>' + status + '</td></tr>';
          html += '<tr><th>Max Travel Distance</th><td style="font-size:20px;font-weight:700;color:#1a73e8;">' + d.distance + ' meters</td></tr>';
          html += '</table></div>';
          html += '<div class="ghbc-fs-disclaimer">Reference: Part 8 (Means of Egress), GhBC GS 1207:2018. This is advisory.</div>';
          $('#ghbc-td-result').html(html).show();
        } else {
          $('#ghbc-td-result')
            .html('<div class="ghbc-error">Error: ' + (res.data.message || 'Calculation failed') + '</div>')
            .show();
        }
        btn.prop('disabled', false).text('Calculate Travel Distance');
      });
    });
  }

  // ===== EXIT WIDTH =====
  var ewContainer = $('.ghbc-ew-container');
  if (ewContainer.length) {
    $('#ghbc-ew-check').on('click', function() {
      var btn = $(this);
      btn.prop('disabled', true).text('Calculating...');

      $.post(ghbc_ajax.ajax_url, {
        action: 'ghbc_exit_width',
        occupants: $('#ew-occupants').val(),
        type: $('#ew-type').val(),
        nonce: ghbc_ajax.nonce
      }, function(res) {
        if (res.success) {
          var d = res.data;
          var typeLabels = {'stairs':'Stairs','doors':'Doors','corridors':'Corridors'};
          var html = '<div class="ghbc-fs-section">';
          html += '<h3 style="margin:0 0 12px;">&#128297; Exit Width Result</h3>';
          html += '<table class="ghbc-fs-table">';
          html += '<tr><th>Occupant Load</th><td>' + d.occupants + ' people</td></tr>';
          html += '<tr><th>Exit Type</th><td>' + (typeLabels[d.type] || d.type) + '</td></tr>';
          html += '<tr><th>Required Total Width</th><td style="font-size:20px;font-weight:700;color:#1a73e8;">' + d.width_mm + ' mm (' + d.width_m.toFixed(2) + ' m)</td></tr>';
          html += '<tr><th>Minimum per Exit</th><td>900 mm (0.9 m)</td></tr>';
          html += '<tr><th>Minimum Exits Required</th><td>' + d.min_exits + '</td></tr>';
          html += '</table></div>';
          html += '<div class="ghbc-fs-disclaimer">Reference: Part 8 (Means of Egress), GhBC GS 1207:2018. This is advisory.</div>';
          $('#ghbc-ew-result').html(html).show();
        } else {
          $('#ghbc-ew-result')
            .html('<div class="ghbc-error">Error: ' + (res.data.message || 'Calculation failed') + '</div>')
            .show();
        }
        btn.prop('disabled', false).text('Calculate Exit Width');
      });
    });
  }

  // ===== FIRE RATING =====
  var frContainer = $('.ghbc-fr-container');
  if (frContainer.length) {
    $('#ghbc-fr-check').on('click', function() {
      var btn = $(this);
      btn.prop('disabled', true).text('Calculating...');

      $.post(ghbc_ajax.ajax_url, {
        action: 'ghbc_fire_rating',
        occupancy: $('#fr-occupancy').val(),
        height: $('#fr-height').val(),
        element: $('#fr-element').val(),
        sprinklered: $('#fr-sprinklered').is(':checked') ? 1 : 0,
        nonce: ghbc_ajax.nonce
      }, function(res) {
        if (res.success) {
          var d = res.data;
          var note = d.sprinklered ? ' (reduced due to sprinklers)' : '';
          var html = '<div class="ghbc-fs-section">';
          html += '<h3 style="margin:0 0 12px;">&#128293; Fire Rating Result</h3>';
          html += '<table class="ghbc-fs-table">';
          html += '<tr><th>Occupancy</th><td>' + d.occupancy + '</td></tr>';
          html += '<tr><th>Height</th><td>' + d.height + '</td></tr>';
          html += '<tr><th>Element</th><td>' + d.element_label + '</td></tr>';
          html += '<tr><th>Sprinklered</th><td>' + (d.sprinklered ? 'Yes' : 'No') + '</td></tr>';
          html += '<tr><th>Required Rating</th><td style="font-size:20px;font-weight:700;color:#1a73e8;">' + d.rating_hours + ' hour(s)' + note + '</td></tr>';
          html += '</table></div>';
          html += '<div class="ghbc-fs-disclaimer">Reference: Part 8 (Fire Resistance), GhBC GS 1207:2018. This is advisory.</div>';
          $('#ghbc-fr-result').html(html).show();
        } else {
          $('#ghbc-fr-result')
            .html('<div class="ghbc-error">Error: ' + (res.data.message || 'Calculation failed') + '</div>')
            .show();
        }
        btn.prop('disabled', false).text('Calculate Fire Rating');
      });
    });
  }

  // ===== SPRINKLER CHECK =====
  var scContainer = $('.ghbc-sc-container');
  if (scContainer.length) {
    $('#ghbc-sc-check').on('click', function() {
      var btn = $(this);
      btn.prop('disabled', true).text('Checking...');

      $.post(ghbc_ajax.ajax_url, {
        action: 'ghbc_sprinkler_check',
        occupancy: $('#sc-occupancy').val(),
        area: $('#sc-area').val(),
        storeys: $('#sc-storeys').val(),
        basement: $('#sc-basement').is(':checked') ? 1 : 0,
        nonce: ghbc_ajax.nonce
      }, function(res) {
        if (res.success) {
          var d = res.data;
          var icon = d.required ? '&#128683;' : '&#10003;';
          var cls = d.required ? 'fail' : 'pass';
          var html = '<div class="ghbc-fs-section">';
          html += '<h3 style="margin:0 0 12px;">&#128167; Sprinkler Requirement Result</h3>';
          html += '<div style="font-size:18px;font-weight:700;margin-bottom:12px;color:' + (d.required ? '#c62828' : '#2e7d32') + ';">' + icon + ' ' + (d.required ? 'Sprinklers REQUIRED' : 'Sprinklers NOT Required') + '</div>';
          html += '<div style="font-size:14px;line-height:1.6;">' + d.raw_result.replace(/\n/g, '<br>') + '</div>';
          html += '</div>';
          $('#ghbc-sc-result').html(html).show();
        } else {
          $('#ghbc-sc-result')
            .html('<div class="ghbc-error">Error: ' + (res.data.message || 'Check failed') + '</div>')
            .show();
        }
        btn.prop('disabled', false).text('Check Sprinkler Requirement');
      });
    });
  }

  // ===== CODE SEARCH =====
  var csContainer = $('.ghbc-cs-container');
  if (csContainer.length) {
    $('#ghbc-cs-search').on('click', function() {
      var btn = $(this);
      var query = $('#cs-query').val().trim();
      if (!query) return;
      btn.prop('disabled', true).text('Searching...');

      $.post(ghbc_ajax.ajax_url, {
        action: 'ghbc_code_search',
        query: query,
        nonce: ghbc_ajax.nonce
      }, function(res) {
        if (res.success) {
          var d = res.data;
          var html = '<div class="ghbc-fs-section">';
          html += '<h3 style="margin:0 0 12px;">&#128269; Search Results (' + d.count + ')</h3>';
          if (d.count === 0) {
            html += '<p>No results found for "' + query + '". Try a different search term.</p>';
          } else {
            $.each(d.results, function(i, r) {
              var clause = r.clause ? ' <strong>Clause ' + r.clause + '</strong>' : '';
              html += '<div style="margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #eee;">';
              html += '<div style="font-weight:600;color:#0D5C52;">Part ' + r.part + clause + ': ' + r.title + '</div>';
              html += '<div style="font-size:13px;margin-top:4px;line-height:1.5;">' + r.content.substring(0, 300) + '...</div>';
              html += '</div>';
            });
          }
          html += '</div>';
          html += '<div class="ghbc-fs-disclaimer">Reference: Ghana Building Code GS 1207:2018.</div>';
          $('#ghbc-cs-result').html(html).show();
        } else {
          $('#ghbc-cs-result')
            .html('<div class="ghbc-error">Error: ' + (res.data.message || 'Search failed') + '</div>')
            .show();
        }
        btn.prop('disabled', false).text('Search Code');
      });
    });
    // Enter key support
    $('#cs-query').on('keydown', function(e) {
      if (e.key === 'Enter') $('#ghbc-cs-search').click();
    });
  }

  // ===== CLAUSE LOOKUP =====
  var clContainer = $('.ghbc-cl-container');
  if (clContainer.length) {
    $('#ghbc-cl-lookup').on('click', function() {
      var btn = $(this);
      var clause = $('#cl-clause').val().trim();
      if (!clause) return;
      btn.prop('disabled', true).text('Looking up...');

      $.post(ghbc_ajax.ajax_url, {
        action: 'ghbc_clause_lookup',
        clause: clause,
        nonce: ghbc_ajax.nonce
      }, function(res) {
        if (res.success) {
          var d = res.data;
          var html = '<div class="ghbc-fs-section">';
          html += '<h3 style="margin:0 0 12px;">&#128214; Clause ' + d.clause + '</h3>';
          html += '<table class="ghbc-fs-table">';
          html += '<tr><th>Part</th><td>' + d.part + '</td></tr>';
          html += '<tr><th>Clause</th><td>' + d.clause + '</td></tr>';
          html += '<tr><th>Title</th><td>' + d.title + '</td></tr>';
          html += '</table>';
          html += '<div style="margin-top:12px;padding:12px;background:#f5f5f5;border-radius:6px;font-size:14px;line-height:1.6;white-space:pre-wrap;">' + d.content + '</div>';
          html += '</div>';
          html += '<div class="ghbc-fs-disclaimer">Reference: Ghana Building Code GS 1207:2018.</div>';
          $('#ghbc-cl-result').html(html).show();
        } else {
          $('#ghbc-cl-result')
            .html('<div class="ghbc-error">Error: ' + (res.data.message || 'Lookup failed') + '</div>')
            .show();
        }
        btn.prop('disabled', false).text('Look Up Clause');
      });
    });
    // Enter key support
    $('#cl-clause').on('keydown', function(e) {
      if (e.key === 'Enter') $('#ghbc-cl-lookup').click();
    });
  }
})(jQuery);