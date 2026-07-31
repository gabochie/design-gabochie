/**
 * Gabochie Design — Main JavaScript
 * @package Gabochie_Design
 *
 * Real-time currency converter using Open ER API (free, no key)
 * https://open.er-api.com — rates from ECB, updates daily
 */
(function($) {
    'use strict';

    /* ============================================
       LIVE CURRENCY RATES
       ============================================ */
    var GABOCHIE_RATES = {
        _cache: null,
        _cacheTime: 0,
        _cacheDuration: 3600000, // 1 hour
        _fallbackRate: 15.8,

        getRate: function(from, to, callback) {
            var self = this;
            var now = Date.now();

            // Serve from cache if fresh
            if (self._cache && (now - self._cacheTime) < self._cacheDuration) {
                var rate = self._getRateFromCache(from, to);
                callback(rate);
                return;
            }

            // Fetch live rates
            $.ajax({
                url: 'https://open.er-api.com/v6/latest/' + from,
                type: 'GET',
                timeout: 5000,
                success: function(res) {
                    if (res && res.rates && res.rates[to]) {
                        self._cache = res.rates;
                        self._cacheTime = now;
                        callback(res.rates[to]);
                    } else {
                        callback(self._fallbackRate);
                    }
                },
                error: function() {
                    callback(self._fallbackRate);
                }
            });
        },

        _getRateFromCache: function(from, to) {
            if (from === to) return 1;
            if (from === 'USD' && this._cache && this._cache[to]) return this._cache[to];
            return this._fallbackRate;
        }
    };

    /* ============================================
       NAVIGATION
       ============================================ */
    var $nav = $('#mainnav');
    var $menuBtn = $('.mobile-menu-btn');
    var $menuLinks = $('.nav-links');

    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 50) {
            $nav.addClass('scrolled');
        } else {
            $nav.removeClass('scrolled');
        }
    });

    $menuBtn.on('click', function() {
        $(this).toggleClass('active');
        $menuLinks.toggleClass('active');
    });

    // Close mobile menu on link click
    $menuLinks.find('a').on('click', function() {
        $menuBtn.removeClass('active');
        $menuLinks.removeClass('active');
    });

    /* ============================================
       DARK MODE TOGGLE
       ============================================ */
    var $darkToggle = $('.dark-toggle');
    var $html = $('html');
    var savedTheme = localStorage.getItem('gd-theme');

    if (savedTheme === 'dark') {
        $html.addClass('dark-mode');
    } else if (!savedTheme) {
        // Respect system preference on first visit
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            $html.addClass('dark-mode');
        }
    }

    $darkToggle.on('click', function() {
        $html.toggleClass('dark-mode');
        localStorage.setItem('gd-theme', $html.hasClass('dark-mode') ? 'dark' : 'light');
    });

    // Active nav item
    var currentPath = window.location.pathname;
    $('.nav-links a').each(function() {
        if (this.pathname === currentPath) {
            $(this).addClass('current-menu-item');
        }
    });

    /* ============================================
       SMOOTH SCROLL
       ============================================ */
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: target.offset().top - 80 }, 600);
        }
    });

    /* ============================================
       COST CALCULATOR — Live Currency
       ============================================ */
    var $calcForm = $('#gd-calc-form');
    if ($calcForm.length) {
        var currency = 'USD';
        var symbols = { USD: '$', GBP: '£', EUR: '€', GHS: 'GH₵' };

        // Fetch live USD→GHS rate on calculator page
        var calcLiveRate = null;
        GABOCHIE_RATES.getRate('USD', 'GHS', function(rate) {
            calcLiveRate = rate;
            // Show rate if GHS is selected
            if (currency === 'GHS') {
                $('#gd-calc-rate').text('Rate: 1 USD = ' + rate.toFixed(2) + ' GHS').show();
            }
        });

        $('.currency-btn').on('click', function() {
            $('.currency-btn').removeClass('active');
            $(this).addClass('active');
            currency = $(this).data('currency');
            if (currency === 'GHS' && calcLiveRate) {
                $('#gd-calc-rate').text('Rate: 1 USD = ' + calcLiveRate.toFixed(2) + ' GHS').show();
            } else {
                $('#gd-calc-rate').hide();
            }
            calculateCost();
        });

        $calcForm.find('input, select').on('change input', function() {
            calculateCost();
        });

        function calculateCost() {
            var area = parseFloat($('#calc-area').val()) || 150;
            var bedrooms = parseInt($('#calc-bedrooms').val()) || 3;
            var style = $('#calc-style').val() || 'modern';
            var finish = $('#calc-finish').val() || 'standard';

            $.ajax({
                url: gabochie.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gabochie_cost_calc',
                    nonce: gabochie.nonce,
                    area: area,
                    bedrooms: bedrooms,
                    style: style,
                    finish: finish,
                    currency: currency
                },
                success: function(res) {
                    if (res.success) {
                        var d = res.data;
                        var sym = symbols[currency] || '$';
                        var total = d.total;

                        $('#calc-construction').text(sym + d.construction.toLocaleString());
                        $('#calc-permits').text(sym + d.permits.toLocaleString());
                        $('#calc-design').text(sym + d.design.toLocaleString());
                        $('#calc-management').text(sym + d.management.toLocaleString());
                        $('#calc-total').text(sym + total.toLocaleString());
                    }
                }
            });
        }

        calculateCost();
    }

    /* ============================================
       PRICING CURRENCY TOGGLE (USD / GHS)
       Live rates from Open ER API, fallback 15.8
       ============================================ */
    var $pricingToggle = $('#pricing-currency-toggle');
    var liveRate = null; // fetched once, reused

    function applyPricingCurrency(cur) {
        if (cur === 'GHS' && liveRate !== null) {
            // Main pricing cards
            $('.price-card').each(function() {
                var $card = $(this);
                var usd = parseInt($card.data('usd')) || 0;
                var ghs = Math.round(usd * liveRate).toLocaleString();
                $card.find('.price-cur').text('GH₵');
                $card.find('.price-num').text(ghs);
            });
            // Add-on prices
            $('.add-on-price').each(function() {
                var usd = parseInt($(this).data('usd')) || 0;
                $(this).text(Math.round(usd * liveRate).toLocaleString());
            });
            // Currency labels on add-ons
            $('.add-on-price').closest('.price-amt').find('.price-cur').text('GH₵');
        } else {
            $('.price-card').each(function() {
                var $card = $(this);
                $card.find('.price-cur').text('$');
                $card.find('.price-num').text(parseInt($card.data('usd') || 0).toLocaleString());
            });
            $('.add-on-price').each(function() {
                $(this).text(parseInt($(this).data('usd') || 0).toLocaleString());
            });
            $('.add-on-price').closest('.price-amt').find('.price-cur').text('$');
        }
    }

    if ($pricingToggle.length) {
        // Fetch live rate on page load
        GABOCHIE_RATES.getRate('USD', 'GHS', function(rate) {
            liveRate = rate;
            // Show rate indicator
            $('#live-rate-indicator').text('Live rate: 1 USD = ' + rate.toFixed(2) + ' GHS (ECB)');
            // If GHS is already selected, apply immediately
            if ($pricingToggle.find('.currency-btn.active').data('currency') === 'GHS') {
                applyPricingCurrency('GHS');
            }
        });

        $pricingToggle.find('.currency-btn').on('click', function() {
            $pricingToggle.find('.currency-btn').removeClass('active');
            $(this).addClass('active');
            var cur = $(this).data('currency');
            if (cur === 'GHS' && liveRate === null) {
                // Still loading — show fallback
                liveRate = 15.8;
            }
            applyPricingCurrency(cur);
        });
    }

    /* ============================================
       COUNTER ANIMATION
       ============================================ */
    function animateCounters() {
        $('.stat-n, .stat-value, .counter-num').each(function() {
            var $el = $(this);
            if ($el.data('counted')) return;

            var text = $el.text();
            var match = text.match(/^([\d,]+)/);
            if (!match) return;

            var target = parseInt(match[1].replace(/,/g, ''));
            var suffix = text.replace(match[1], '');
            var start = 0;
            var duration = 2000;
            var startTime = null;

            $el.data('counted', true);

            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                var current = Math.floor(eased * target);
                $el.text(current.toLocaleString() + suffix);
                if (progress < 1) requestAnimationFrame(step);
            }

            requestAnimationFrame(step);
        });
    }

    // Trigger counter animation on scroll
    var counterObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                animateCounters();
            }
        });
    }, { threshold: 0.3 });

    $('.stats-grid, .hero-stats').each(function() {
        counterObserver.observe(this);
    });

    /* ============================================
       SCROLL ANIMATIONS
       ============================================ */
    var revealObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                $(entry.target).addClass('revealed');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    $('.reveal, .reveal-left, .reveal-right, .reveal-scale, .fade-in').each(function() {
        revealObserver.observe(this);
    });

    /* ============================================
       FOOTER GAP ELIMINATOR
       Removes invisible whitespace below footer
       caused by hidden plugin elements (WooCommerce,
       Site Kit, AdSense) that add to body scroll height
       or body padding
       ============================================ */
    var isEliminating = false;
    function eliminateFooterGap() {
        if (isEliminating) return;
        isEliminating = true;
        var footer = document.querySelector('footer');
        if (!footer) { isEliminating = false; return; }
        var body = document.body;
        var footerBottom = footer.offsetTop + footer.offsetHeight;
        var bodyHeight = body.offsetHeight;
        if (bodyHeight > footerBottom + 5) {
            body.style.height = footerBottom + 'px';
            body.style.paddingBottom = '0';
        }
        isEliminating = false;
    }
    // Run after page fully loads
    if (document.readyState === 'complete') {
        eliminateFooterGap();
    } else {
        window.addEventListener('load', eliminateFooterGap);
    }
    // Also run after a short delay for dynamic content
    setTimeout(eliminateFooterGap, 1000);
    // Watch for style changes on body (plugins may add padding)
    var gapObserver = new MutationObserver(function(mutations) {
        for (var i = 0; i < mutations.length; i++) {
            if (mutations[i].attributeName === 'style') {
                eliminateFooterGap();
                break;
            }
        }
    });
    gapObserver.observe(document.body, { attributes: true, attributeFilter: ['style'] });

    /* ============================================
       READING PROGRESS BAR
       ============================================ */
    var $progress = $('.reading-progress');
    var $heroImg = $('.hero-main-img');
    if ($progress.length || $heroImg.length) {
        $(window).on('scroll', function() {
            var scrollTop = $(window).scrollTop();
            // Reading progress
            if ($progress.length) {
                var docHeight = $(document).height() - $(window).height();
                var progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
                $progress.css('width', progress + '%');
            }
            // Hero parallax
            if ($heroImg.length && scrollTop < window.innerHeight) {
                $heroImg.css('transform', 'translateY(' + (scrollTop * 0.15) + 'px)');
            }
        });
    }

    /* ============================================
       BACK TO TOP
       ============================================ */
    var $backToTop = $('.back-to-top');
    if ($backToTop.length) {
        $(window).on('scroll', function() {
            if ($(window).scrollTop() > 400) {
                $backToTop.addClass('visible');
            } else {
                $backToTop.removeClass('visible');
            }
        });
        $backToTop.on('click', function() {
            $('html, body').animate({ scrollTop: 0 }, 400);
        });
    }

})(jQuery);
