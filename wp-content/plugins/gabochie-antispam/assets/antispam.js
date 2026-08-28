(function () {
    'use strict';

    var inject = function () {
        var style = document.createElement('style');
        style.textContent = '.gabochie-hp{position:absolute!important;left:-9999px!important;top:-9999px!important;width:1px!important;height:1px!important;opacity:0!important;pointer-events:none!important;z-index:-1!important}';
        document.head.appendChild(style);

        document.querySelectorAll('form').forEach(function (form) {
            if (form.querySelector('input[name="gabochie_website"]')) return;
            var h = document.createElement('input');
            h.type = 'text';
            h.name = 'gabochie_website';
            h.value = '';
            h.className = 'gabochie-hp';
            h.tabIndex = -1;
            h.autocomplete = 'off';
            h.setAttribute('aria-hidden', 'true');
            form.appendChild(h);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inject);
    } else {
        inject();
    }
})();