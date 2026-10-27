/**
 * Gabochie Design — AI Chat Widget
 * @package Gabochie_Design
 */
(function($) {
    'use strict';

    var $widget = $('#gd-ai-chat');
    var $toggle = $('#gd-chat-toggle');
    var $window = $('#gd-chat-window');
    var $close = $('#gd-chat-close');
    var $messages = $('#gd-chat-messages');
    var $form = $('#gd-chat-form');
    var $input = $('#gd-chat-input');
    var $suggestions = $('#gd-chat-suggestions');
    var $badge = $('.gd-chat-badge');
    var isOpen = false;
    var hasGreeted = false;

    function addMessage(text, type) {
        var $msg = $('<div class="gd-chat-msg ' + type + '"></div>');
        $msg.html(text.replace(/\n/g, '<br>'));
        $messages.append($msg);
        $messages.scrollTop($messages[0].scrollHeight);
    }

    function showTyping() {
        var $typing = $('<div class="gd-typing"><span></span><span></span><span></span></div>');
        $messages.append($typing);
        $messages.scrollTop($messages[0].scrollHeight);
        return $typing;
    }

    function sendMessage(msg) {
        if (!msg || !msg.trim()) return;

        addMessage(msg, 'user');
        $input.val('');
        $suggestions.hide();

        var $typing = showTyping();

        $.ajax({
            url: gabochieAI.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gabochie_ai_chat',
                nonce: gabochieAI.nonce,
                message: msg
            },
            success: function(res) {
                $typing.remove();
                if (res.success) {
                    addMessage(res.data.message, 'bot');
                } else {
                    addMessage("I'm sorry, I'm having trouble responding right now. Please try again or contact us at admin@design.gabochie.com", 'bot');
                }
            },
            error: function() {
                $typing.remove();
                addMessage("I'm sorry, something went wrong. Please try again later.", 'bot');
            }
        });
    }

    function toggleChat() {
        isOpen = !isOpen;
        $window.toggleClass('active', isOpen);
        $badge.hide();

        if (isOpen && !hasGreeted) {
            setTimeout(function() {
                addMessage(gabochieAI.welcomeMsg, 'bot');
                $suggestions.show();
            }, 500);
            hasGreeted = true;
        }

        if (isOpen) {
            $input.focus();
        }
    }

    $toggle.on('click', toggleChat);
    $close.on('click', toggleChat);

    $form.on('submit', function(e) {
        e.preventDefault();
        sendMessage($input.val());
    });

    $suggestions.on('click', '.gd-suggestion', function() {
        var msg = $(this).data('msg');
        sendMessage(msg);
    });

    // Auto-open after 30 seconds
    setTimeout(function() {
        if (!isOpen) {
            $badge.show();
        }
    }, 30000);

})(jQuery);
