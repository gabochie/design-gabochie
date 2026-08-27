/**
 * Ama — Gabochie Voice Agent widget (v3.0)
 * Auto-greets, always listening, mature female voice.
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
  var greeted = false; /* only greet once per session */
  var autoListen = false; /* auto-restart mic after TTS finishes */
  var voicesLoaded = false;
  var preferredVoice = null;

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
  .listening-indicator {\
    display: flex; align-items: center; justify-content: center; gap: 6px;\
    padding: 6px; font-size: 12px; color: #0D5C52; background: #E8F4F2;\
  }\
  .listening-indicator[hidden] { display: none; }\
  .pulse-dot {\
    width: 6px; height: 6px; border-radius: 50%; background: #C0392B;\
    animation: blink 1s infinite;\
  }\
  @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.2; } }\
</style>';

  /* -------------------------------------------------------- HTML ----- */
  var HTML = '\
<div class="fab" id="fab" aria-label="Talk to Ama" title="Talk to Ama">' + ICONS.mic + '</div>\
<div class="panel" id="panel" hidden>\
  <div class="header">\
    <div class="avatar">A</div>\
    <div class="titles">\
      <div class="site-name"></div>\
      <div class="status"><span class="dot" id="dot"></span><span id="status-label">Ready</span></div>\
    </div>\
    <button class="icon-btn" id="close" aria-label="Close">' + ICONS.close + '</button>\
  </div>\
  <div class="transcript" id="transcript"></div>\
  <div class="listening-indicator" id="listening-indicator" hidden>\
    <div class="pulse-dot"></div> Listening... speak now\
  </div>\
  <div class="lang-hint">Speak in English, Twi, or Ga</div>\
  <div class="controls">\
    <button class="ctrl" id="mic" aria-label="Start speaking" title="Speak">' + ICONS.mic + '</button>\
    <input type="text" class="text-input" id="text-input" placeholder="Or type a message..." />\
    <button class="ctrl" id="send" aria-label="Send" title="Send">' + ICONS.send + '</button>\
    <button class="ctrl end" id="end" aria-label="End" title="End">' + ICONS.stop + '</button>\
  </div>\
  <div class="error" id="error" hidden></div>\
</div>';

  /* ---- Voice selection: prefer mature female ---- */
  function pickVoice() {
    if (!synth) return null;
    var voices = synth.getVoices();
    if (!voices.length) return null;

    /* Priority order for female voices */
    var femalePatterns = [
      'Microsoft Zira',        /* Windows female */
      'Google UK English Female',
      'Google US English',     /* often female */
      'Samantha',              /* macOS female */
      'Karen',                 /* macOS (AU) female */
      'Moira',                 /* macOS (IE) female */
      'Tessa',                 /* macOS (ZA) female */
      'Victoria',              /* macOS female */
      'Alice',                 /* Chrome OS female */
      'Allison',               /* US English female */
      'Julie',                 /* US English female */
      'Microsoft Hazel',       /* Windows female */
    ];

    /* 1. Exact match */
    for (var i = 0; i < femalePatterns.length; i++) {
      for (var j = 0; j < voices.length; j++) {
        if (voices[j].name.indexOf(femalePatterns[i]) !== -1) return voices[j];
      }
    }

    /* 2. Any English voice with a female-sounding name */
    var english = voices.filter(function (v) { return v.lang.startsWith('en'); });
    var femaleNames = ['female', 'woman', 'girl', 'zira', 'samantha', 'karen', 'hazel', 'moira', 'tessa', 'victoria', 'alice', 'allison', 'julie', 'google uk english female'];
    for (var k = 0; k < english.length; k++) {
      var lower = english[k].name.toLowerCase();
      for (var m = 0; m < femaleNames.length; m++) {
        if (lower.indexOf(femaleNames[m]) !== -1) return english[k];
      }
    }

    /* 3. First English voice (fallback) */
    return english[0] || voices[0];
  }

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
    var listeningIndicator = shadow.getElementById('listening-indicator');
    var siteName = shadow.querySelector('.site-name');
    siteName.textContent = CFG.site_name;

    /* ---- pick voice once voices are loaded ---- */
    function ensureVoice() {
      if (preferredVoice) return;
      preferredVoice = pickVoice();
    }
    if (synth) {
      synth.getVoices();
      synth.onvoiceschanged = function () {
        synth.getVoices();
        ensureVoice();
      };
    }

    /* ------------------------------------------------ toggle ----- */
    function togglePanel() {
      if (panelOpen) {
        closePanel();
      } else {
        panel.hidden = false;
        panelOpen = true;

        if (!greeted) {
          greeted = true;
          var greeting = 'Hello! I\'m Ama, your building consultant at Gabochie Design. ' +
            'Ask me anything about building in Ghana — I can help with costs, permits, readiness, or getting started.';
          addBubble('assistant', greeting);
          history.push({ role: 'assistant', text: greeting });
          /* Speak the greeting, then auto-start listening */
          autoListen = true;
          speakText(greeting);
        } else {
          /* Already greeted — just start listening */
          startListening();
        }
      }
    }

    function closePanel() {
      panel.hidden = true;
      panelOpen = false;
      autoListen = false;
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

    function showListeningIndicator(show) {
      listeningIndicator.hidden = !show;
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
          var reply = data.reply || (data.data && data.data.reply) || 'Sorry, I couldn\'t understand that.';
          addBubble('assistant', reply);
          history.push({ role: 'assistant', text: reply });
          setStatus('Speaking...', true);
          autoListen = true;
          speakText(reply);
        })
        .catch(function () {
          showError('Connection error. Please try again.');
          setStatus('Ready', false);
        });
    }

    /* ------------------------------------------------ TTS ----- */
    function speakText(text) {
      if (!synth) {
        onTTSComplete();
        return;
      }
      synth.cancel();
      ensureVoice();

      var utter = new SpeechSynthesisUtterance(text);
      utter.rate = 0.88;       /* slightly slower for mature feel */
      utter.pitch = 1.15;      /* slightly higher for feminine tone */
      utter.volume = 1.0;

      if (preferredVoice) {
        utter.voice = preferredVoice;
      }

      utter.onend = function () {
        speaking = false;
        onTTSComplete();
      };
      utter.onerror = function () {
        speaking = false;
        onTTSComplete();
      };

      speaking = true;
      synth.speak(utter);
    }

    function onTTSComplete() {
      if (panelOpen && autoListen) {
        /* Auto-start listening after speaking */
        autoListen = false;
        startListening();
      } else {
        setStatus('Ready', false);
      }
    }

    /* ------------------------------------------------ STT ----- */
    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    function startListening() {
      if (!SpeechRecognition) {
        showError('Speech recognition not supported in this browser. Try Chrome or Edge.');
        return;
      }
      if (listening) {
        return; /* already listening */
      }
      if (speaking) {
        synth && synth.cancel();
        speaking = false;
      }

      recognition = new SpeechRecognition();
      recognition.continuous = true;       /* stay listening until stopped */
      recognition.interimResults = true;
      recognition.lang = 'en-GH';
      recognition.maxAlternatives = 1;

      recognition.onstart = function () {
        listening = true;
        setStatus('Listening...', true);
        micBtn.classList.add('active');
        showListeningIndicator(true);
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
          /* No speech detected — restart listening silently */
          if (panelOpen && !speaking) {
            stopListening();
            setTimeout(function () {
              if (panelOpen && !speaking && !listening) startListening();
            }, 300);
          }
        } else if (event.error === 'not-allowed') {
          showError('Microphone access denied. Please allow microphone access in your browser settings.');
          stopListening();
        } else if (event.error === 'aborted') {
          /* Intentional abort — ignore */
        } else {
          showError('Speech error: ' + event.error);
          stopListening();
        }
      };

      recognition.onend = function () {
        if (listening) {
          /* Unexpected end — restart if panel open */
          listening = false;
          micBtn.classList.remove('active');
          showListeningIndicator(false);
          if (panelOpen && !speaking) {
            setTimeout(function () {
              if (panelOpen && !speaking && !listening) startListening();
            }, 200);
          }
        }
      };

      try {
        recognition.start();
      } catch (_) {
        /* already started */
      }
    }

    function stopListening() {
      listening = false;
      micBtn.classList.remove('active');
      showListeningIndicator(false);
      if (recognition) {
        try { recognition.abort(); } catch (_) {}
        recognition = null;
      }
    }

    micBtn.addEventListener('click', function () {
      if (listening) {
        stopListening();
        autoListen = false;
      } else {
        startListening();
      }
    });

    /* ------------------------------------------------ text input -- */
    function handleSend() {
      var text = textInput.value.trim();
      if (!text) return;
      textInput.value = '';
      autoListen = true; /* auto-listen after AI responds */
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

  /* -------------------------------------------------- deploy notifications ----- */
  var lastDeploySeen = null;
  var audioCtx = null;

  function playChime() {
    try {
      if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      /* Two-tone chime: 880Hz then 1175Hz, each 150ms */
      [880, 1175].forEach(function(freq, i) {
        var osc = audioCtx.createOscillator();
        var gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.value = freq;
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime + i * 0.18);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + i * 0.18 + 0.3);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start(audioCtx.currentTime + i * 0.18);
        osc.stop(audioCtx.currentTime + i * 0.18 + 0.3);
      });
    } catch (_) {}
  }

  function checkDeployNotification() {
    if (!CFG.rest_url) return;
    var m = CFG.rest_url.match(/^(.+?\/wp-json)/);
    var base = m ? m[1] + '/' : CFG.rest_url;
    fetch(base + 'gabochie/v1/deploy-status', {
      method: 'GET',
      headers: { 'X-WP-Nonce': CFG.nonce },
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.pending && data.notification) {
        var n = data.notification;
        var key = n.timestamp + n.version;
        if (lastDeploySeen !== key) {
          lastDeploySeen = key;
          /* Play chime and show notification */
          playChime();
          var msg = 'A new version just went live! ' + n.version + (n.message ? ' — ' + n.message : '');
          addBubble('system', msg);
          /* Also inject into AI context so Ama can mention it */
          history.push({ role: 'system', text: 'DEPLOY NOTIFICATION: ' + msg });
        }
      }
    })
    .catch(function() {});
  }

  /* Poll every 30 seconds */
  setInterval(checkDeployNotification, 30000);
  /* Check once on load after 5 seconds */
  setTimeout(checkDeployNotification, 5000);

  /* -------------------------------------------------------- boot ----- */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
