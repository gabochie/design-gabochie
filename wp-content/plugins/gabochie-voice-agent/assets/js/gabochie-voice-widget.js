/**
 * Gabochie Voice Agent — WordPress-native widget
 * Uses Web Speech API (browser STT/TTS) + Gemini proxy via WP REST.
 *
 * @package Gabochie_Voice_Agent
 */
(function () {
  'use strict';

  var CFG = Object.assign(
    { rest_url: '', nonce: '', site_name: 'Gabochie Design', onboarding: '/onboarding/' },
    window.GabochieVoiceConfig || {}
  );

  /* ---------------------------------------------------------- state ----- */
  var recognition = null;
  var synth = window.speechSynthesis || null;
  var history = [];
  var session_id = (function () {
    try {
      var s = sessionStorage.getItem('gva_session');
      if (s) return s;
      s = crypto.randomUUID();
      sessionStorage.setItem('gva_session', s);
      return s;
    } catch (_) {
      return crypto.randomUUID();
    }
  })();
  var speaking = false;
  var listening = false;
  var panelOpen = false;

  /* -------------------------------------------------------- SVGs ----- */
  var ICONS = {
    mic: '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>',
    stop: '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>',
    close: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    send: '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>',
  };

  /* -------------------------------------------------------- CSS ----- */
  var CSS = '\
<style>\
  :host { all: initial; }\
  * { box-sizing: border-box; font-family: "Jost", "Segoe UI", system-ui, sans-serif; }\
  .fab {\
    position: fixed; right: 20px; bottom: 20px; z-index: 999999;\
    width: 56px; height: 56px; border: none; border-radius: 50%;\
    background: #0D5C52; color: #C9A96E;\
    display: flex; align-items: center; justify-content: center;\
    cursor: pointer; box-shadow: 0 4px 16px rgba(13,92,82,0.45);\
    transition: transform 0.15s, box-shadow 0.15s;\
  }\
  .fab:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(13,92,82,0.55); }\
  .fab:active { transform: translateY(0); }\
  .fab.listening { animation: pulse 1.5s infinite; }\
  @keyframes pulse {\
    0%, 100% { box-shadow: 0 4px 16px rgba(13,92,82,0.45); }\
    50% { box-shadow: 0 4px 24px rgba(201,169,110,0.6); }\
  }\
  .panel {\
    position: fixed; right: 20px; bottom: 88px; z-index: 999999;\
    width: 370px; max-width: calc(100vw - 32px);\
    height: 540px; max-height: calc(100vh - 120px);\
    display: flex; flex-direction: column;\
    background: #FAF7F2; border-radius: 16px;\
    box-shadow: 0 12px 40px rgba(28,43,40,0.3);\
    overflow: hidden; color: #1C2B28;\
  }\
  .panel[hidden] { display: none; }\
  .header {\
    display: flex; align-items: center; gap: 12px;\
    padding: 14px 16px; background: #0D5C52; color: #FDFCF9;\
  }\
  .avatar {\
    width: 38px; height: 38px; border-radius: 50%;\
    background: #C9A96E; color: #072E28;\
    font-family: "Playfair Display", Georgia, serif;\
    font-weight: 700; font-size: 18px;\
    display: flex; align-items: center; justify-content: center;\
  }\
  .titles { flex: 1; min-width: 0; }\
  .site-name {\
    font-family: "Playfair Display", Georgia, serif;\
    font-size: 15px; font-weight: 600;\
  }\
  .status { display: flex; align-items: center; gap: 6px; font-size: 12px; opacity: 0.9; margin-top: 2px; }\
  .dot { width: 8px; height: 8px; border-radius: 50%; background: #E8D5A8; }\
  .dot.active { background: #7FD8A9; box-shadow: 0 0 6px #7FD8A9; }\
  .icon-btn {\
    border: none; background: transparent; color: #FDFCF9;\
    width: 30px; height: 30px; border-radius: 50%;\
    display: flex; align-items: center; justify-content: center; cursor: pointer;\
  }\
  .icon-btn:hover { background: rgba(255,255,255,0.15); }\
  .transcript {\
    flex: 1; overflow-y: auto; padding: 14px;\
    display: flex; flex-direction: column; gap: 10px; background: #F2FAF8;\
  }\
  .bubble {\
    max-width: 85%; padding: 10px 13px; border-radius: 14px;\
    font-size: 14px; line-height: 1.45; word-wrap: break-word; white-space: pre-wrap;\
  }\
  .bubble.user {\
    align-self: flex-end; background: #072E28; color: #FDFCF9;\
    border-bottom-right-radius: 4px;\
  }\
  .bubble.assistant {\
    align-self: flex-start; background: #FDFCF9; color: #1C2B28;\
    border-left: 3px solid #C9A96E; border-bottom-left-radius: 4px;\
  }\
  .bubble.interim {\
    align-self: flex-end; background: #E8F4F2; color: #0A4840;\
    font-style: italic; opacity: 0.85;\
  }\
  .bubble.system {\
    align-self: center; background: transparent; color: #6B7B78;\
    font-size: 12px; padding: 4px 8px;\
  }\
  .controls {\
    display: flex; justify-content: center; align-items: center; gap: 12px;\
    padding: 12px; background: #FAF7F2; border-top: 1px solid #E8F4F2;\
  }\
  .ctrl {\
    width: 44px; height: 44px; border: none; border-radius: 50%;\
    background: #E8F4F2; color: #0D5C52;\
    display: flex; align-items: center; justify-content: center;\
    cursor: pointer; transition: background 0.15s;\
  }\
  .ctrl:hover { background: #E8D5A8; }\
  .ctrl.active { background: #0D5C52; color: #FDFCF9; }\
  .ctrl.end { background: #C0392B; color: #FDFCF9; }\
  .ctrl.end:hover { background: #A93226; }\
  .text-input {\
    flex: 1; border: 1px solid #E8D5A8; border-radius: 20px;\
    padding: 8px 14px; font-size: 14px; outline: none;\
    background: #FDFCF9; color: #1C2B28;\
  }\
  .text-input:focus { border-color: #0D5C52; }\
  .error {\
    padding: 8px 14px; font-size: 12px; color: #C0392B;\
    background: #FDECEA; border-top: 1px solid #F5C6C0;\
  }\
  .error[hidden] { display: none; }\
  .lang-hint {\
    text-align: center; padding: 4px; font-size: 11px; color: #6B7B78;\
    background: #F2FAF8; border-top: 1px solid #E8F4F2;\
  }\
</style>';

  /* -------------------------------------------------------- HTML ----- */
  var HTML = '\
<div class="fab" id="fab" aria-label="Talk to Gabochie" title="Talk to Gabochie">' + ICONS.mic + '</div>\
<div class="panel" id="panel" hidden>\
  <div class="header">\
    <div class="avatar">G</div>\
    <div class="titles">\
      <div class="site-name"></div>\
      <div class="status"><span class="dot" id="dot"></span><span id="status-label">Ready</span></div>\
    </div>\
    <button class="icon-btn" id="close" aria-label="Close">' + ICONS.close + '</button>\
  </div>\
  <div class="transcript" id="transcript"></div>\
  <div class="lang-hint">Speak in English or Twi-nglish</div>\
  <div class="controls">\
    <button class="ctrl" id="mic" aria-label="Start speaking" title="Speak">' + ICONS.mic + '</button>\
    <input type="text" class="text-input" id="text-input" placeholder="Or type a message..." />\
    <button class="ctrl" id="send" aria-label="Send" title="Send">' + ICONS.send + '</button>\
    <button class="ctrl end" id="end" aria-label="End" title="End">' + ICONS.stop + '</button>\
  </div>\
  <div class="error" id="error" hidden></div>\
</div>';

  /* ---------------------------------------------------- init ----- */
  function init() {
    if (document.getElementById('gabochie-voice-widget-host')) return;

    var host = document.createElement('div');
    host.id = 'gabochie-voice-widget-host';
    var shadow = host.attachShadow({ mode: 'open' });
    shadow.innerHTML = CSS + HTML;
    document.body.appendChild(host);

    var fab = shadow.getElementById('fab');
    var panel = shadow.getElementById('panel');
    var dot = shadow.getElementById('dot');
    var statusLabel = shadow.getElementById('status-label');
    var transcriptEl = shadow.getElementById('transcript');
    var micBtn = shadow.getElementById('mic');
    var sendBtn = shadow.getElementById('send');
    var endBtn = shadow.getElementById('end');
    var closeBtn = shadow.getElementById('close');
    var textInput = shadow.getElementById('text-input');
    var errorEl = shadow.getElementById('error');
    var siteName = shadow.querySelector('.site-name');
    siteName.textContent = CFG.site_name;

    /* ------------------------------------------------ toggle ----- */
    function togglePanel() {
      if (panelOpen) {
        closePanel();
      } else {
        panel.hidden = false;
        panelOpen = true;
        addSystem('Hi! I\'m Gabochie. Ask me anything about building in Ghana.');
        textInput.focus();
      }
    }

    function closePanel() {
      panel.hidden = true;
      panelOpen = false;
      stopListening();
      synth && synth.cancel();
      speaking = false;
    }

    fab.addEventListener('click', togglePanel);
    closeBtn.addEventListener('click', closePanel);
    endBtn.addEventListener('click', closePanel);

    /* ------------------------------------------------ status ----- */
    function setStatus(text, active) {
      statusLabel.textContent = text;
      dot.classList.toggle('active', !!active);
      fab.classList.toggle('listening', !!active);
    }

    /* ------------------------------------------------ transcript -- */
    function addBubble(role, text) {
      if (!text || !text.trim()) return;
      var d = document.createElement('div');
      d.className = 'bubble ' + role;
      d.textContent = text;
      transcriptEl.appendChild(d);
      transcriptEl.scrollTop = transcriptEl.scrollHeight;
    }

    function addSystem(text) {
      addBubble('system', text);
    }

    function setInterim(text) {
      var existing = transcriptEl.querySelector('.interim');
      if (existing) existing.remove();
      if (!text) return;
      var d = document.createElement('div');
      d.className = 'bubble interim';
      d.textContent = text;
      transcriptEl.appendChild(d);
      transcriptEl.scrollTop = transcriptEl.scrollHeight;
    }

    function clearInterim() {
      var el = transcriptEl.querySelector('.interim');
      if (el) el.remove();
    }

    /* ------------------------------------------------ error ----- */
    function showError(msg) {
      errorEl.textContent = msg;
      errorEl.hidden = false;
      setTimeout(function () { errorEl.hidden = true; }, 5000);
    }

    /* ------------------------------------------------ chat ----- */
    function sendToAI(userMessage) {
      addBubble('user', userMessage);
      history.push({ role: 'user', text: userMessage });
      setStatus('Thinking...', true);

      fetch(CFG.rest_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': CFG.nonce,
        },
        body: JSON.stringify({
          message: userMessage,
          history: history.slice(-10),
          session_id: session_id,
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.data && data.data.code) {
            showError(data.message || 'AI error');
            setStatus('Ready', false);
            return;
          }
          var reply = data.reply || data.data?.reply || 'Sorry, I couldn\'t understand that.';
          addBubble('assistant', reply);
          history.push({ role: 'assistant', text: reply });
          setStatus('Speaking...', true);
          speakText(reply);
        })
        .catch(function (err) {
          showError('Connection error. Please try again.');
          setStatus('Ready', false);
        });
    }

    /* ------------------------------------------------ TTS ----- */
    function speakText(text) {
      if (!synth) {
        setStatus('Ready', false);
        return;
      }
      synth.cancel();
      var utter = new SpeechSynthesisUtterance(text);
      utter.rate = 0.95;
      utter.pitch = 1.0;

      // Try to find an English voice
      var voices = synth.getVoices();
      var english = voices.filter(function (v) {
        return v.lang.startsWith('en');
      });
      if (english.length > 0) {
        utter.voice = english[0];
      }

      utter.onend = function () {
        speaking = false;
        setStatus('Ready', false);
      };
      utter.onerror = function () {
        speaking = false;
        setStatus('Ready', false);
      };

      speaking = true;
      synth.speak(utter);
    }

    // Pre-load voices
    if (synth) {
      synth.getVoices();
      synth.onvoiceschanged = function () { synth.getVoices(); };
    }

    /* ------------------------------------------------ STT ----- */
    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    function startListening() {
      if (!SpeechRecognition) {
        showError('Speech recognition not supported in this browser. Try Chrome or Edge.');
        return;
      }
      if (listening) {
        stopListening();
        return;
      }

      // Cancel TTS if speaking
      if (synth && speaking) {
        synth.cancel();
        speaking = false;
      }

      recognition = new SpeechRecognition();
      recognition.continuous = false;
      recognition.interimResults = true;
      recognition.lang = 'en-GH'; // Ghanaian English

      recognition.onstart = function () {
        listening = true;
        setStatus('Listening...', true);
        micBtn.classList.add('active');
      };

      recognition.onresult = function (event) {
        var interim = '';
        var final = '';
        for (var i = event.resultIndex; i < event.results.length; i++) {
          var transcript = event.results[i][0].transcript;
          if (event.results[i].isFinal) {
            final += transcript;
          } else {
            interim += transcript;
          }
        }
        if (interim) setInterim(interim);
        if (final) {
          clearInterim();
          stopListening();
          sendToAI(final);
        }
      };

      recognition.onerror = function (event) {
        if (event.error === 'no-speech') {
          setStatus('Ready', false);
        } else if (event.error === 'not-allowed') {
          showError('Microphone access denied. Please allow microphone access.');
        } else {
          showError('Speech error: ' + event.error);
        }
        stopListening();
      };

      recognition.onend = function () {
        stopListening();
      };

      recognition.start();
    }

    function stopListening() {
      listening = false;
      micBtn.classList.remove('active');
      if (recognition) {
        try { recognition.stop(); } catch (_) {}
        recognition = null;
      }
    }

    micBtn.addEventListener('click', startListening);

    /* ------------------------------------------------ text input -- */
    function handleSend() {
      var text = textInput.value.trim();
      if (!text) return;
      textInput.value = '';
      sendToAI(text);
    }

    sendBtn.addEventListener('click', handleSend);
    textInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
      }
    });
  }

  /* -------------------------------------------------------- boot ----- */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
