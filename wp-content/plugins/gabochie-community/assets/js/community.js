/* Gabochie Community - frontend interactions */
(function () {
    'use strict';

    var cfg = window.gabochie_community || {};
    var form = document.getElementById('gqc-ask-form');
    if (!form) { return; }

    var msg = form.querySelector('.gqc-form-msg');
    var btn = form.querySelector('.gqc-submit');

    function setMsg(text, isError) {
        msg.textContent = text || '';
        msg.className = 'gqc-form-msg' + (isError ? ' is-error' : ' is-ok');
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        setMsg('');

        var data = new URLSearchParams();
        data.append('action', 'gabochie_ask');
        data.append('nonce', cfg.nonce || '');
        data.append('hp', form.querySelector('[name="hp"]').value);
        data.append('name', form.querySelector('[name="name"]').value);
        data.append('email', form.querySelector('[name="email"]').value);
        data.append('stage', form.querySelector('[name="stage"]').value);
        data.append('title', form.querySelector('[name="title"]').value);
        data.append('body', form.querySelector('[name="body"]').value);

        btn.disabled = true;
        setMsg('Posting your question\u2026');

        fetch(cfg.ajax_url || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: data.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.success && res.data && res.data.url) {
                    window.location.href = res.data.url;
                    return;
                }
                setMsg((res && res.data && res.data.message) || 'Something went wrong. Please try again.', true);
                btn.disabled = false;
            })
            .catch(function () {
                setMsg('Network error. Please try again.', true);
                btn.disabled = false;
            });
    });
})();
