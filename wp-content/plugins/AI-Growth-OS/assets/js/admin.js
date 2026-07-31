(function () {
    'use strict';

    const API = window.AIGrowthOS?.restUrl + '/scores';
    const LOADING = 'Loading...';

    async function fetchScores() {
        try {
            const res = await fetch(API, {
                headers: { 'X-WP-Nonce': window.AIGrowthOS?.nonce },
            });
            if (!res.ok) throw new Error('Failed to fetch scores');
            return await res.json();
        } catch (err) {
            console.error('[AI Growth OS]', err);
            return [];
        }
    }

    function renderScores(scores) {
        document.querySelectorAll('[data-metric]').forEach(function (el) {
            const key = el.dataset.metric;
            const score = scores.find(function (s) { return s.type === key; });
            el.textContent = score ? score.value.toFixed(1) : '--';

            const trendEl = el.parentElement.querySelector('.metric-trend');
            if (trendEl && score) {
                trendEl.textContent = score.trend;
                trendEl.className = 'metric-trend ' + score.trend;
            }
        });
    }

    async function init() {
        if (!window.AIGrowthOS) return;

        const scores = await fetchScores();
        renderScores(scores);
    }

    document.addEventListener('DOMContentLoaded', init);
})();
