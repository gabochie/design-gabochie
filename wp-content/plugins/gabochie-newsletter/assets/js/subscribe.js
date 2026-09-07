(function () {
  'use strict';

  var forms = document.querySelectorAll('.gab-nl-sub');
  if (!forms.length || typeof gabNlSubmit === 'undefined') {
    return;
  }

  Array.prototype.forEach.call(forms, function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var msg = form.querySelector('.gab-nl-sub-msg');
      var btn = form.querySelector('.gab-nl-sub-btn');
      msg.textContent = '';
      msg.classList.remove('gab-nl-sub-ok', 'gab-nl-sub-err');

      var payload = {
        first_name: form.querySelector('[name="first_name"]').value,
        email: form.querySelector('[name="email"]').value,
        consent: form.querySelector('[name="consent"]') && form.querySelector('[name="consent"]').checked ? '1' : '',
        website: form.querySelector('[name="website"]') ? form.querySelector('[name="website"]').value : '',
        _gab_nl_nonce: form.querySelector('[name="_gab_nl_nonce"]').value
      };

      btn.disabled = true;

      fetch(gabNlSubmit.rest, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': gabNlSubmit.nonce
        },
        body: JSON.stringify(payload)
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          btn.disabled = false;
          if (json && json.success) {
            msg.textContent = json.message || 'Done.';
            msg.classList.add('gab-nl-sub-ok');
            form.querySelector('[name="email"]').value = '';
            form.querySelector('[name="first_name"]').value = '';
            form.querySelector('[name="consent"]').checked = false;
          } else {
            msg.textContent = (json && json.message) || 'Something went wrong. Please try again.';
            msg.classList.add('gab-nl-sub-err');
          }
        })
        .catch(function () {
          btn.disabled = false;
          msg.textContent = 'Something went wrong. Please try again.';
          msg.classList.add('gab-nl-sub-err');
        });
    });
  });
})();