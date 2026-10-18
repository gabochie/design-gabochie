const CACHE = 'gv-v{{VERSION}}';
const PAGES = 'gv-pages-v{{VERSION}}';
const FONTS = 'gv-fonts-v{{VERSION}}';
const PRECACHE = [
  '/',
  '/offline.html',
  '/wp-content/themes/gabochie-value/assets/css/styles.css',
  '/wp-content/themes/gabochie-value/assets/css/membership.css',
  '/wp-content/themes/gabochie-value/assets/css/userflow.css',
  '/wp-content/themes/gabochie-value/assets/js/main.js',
  '/wp-content/themes/gabochie-value/assets/images/pwa/icon-192.png',
  '/wp-content/themes/gabochie-value/assets/images/pwa/icon-512.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((c) => Promise.all(PRECACHE.map((u) => c.add(u).catch(() => null))))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((k) => k !== CACHE && k !== PAGES && k !== FONTS).map((k) => caches.delete(k))
    )).then(() => self.clients.claim())
  );
});

function isExcluded(url) {
  return /wp-admin|wp-login|wp-json|wc-api|add-to-cart|\/cart\/|\/checkout\/|\/my-account\/|gab-nl|gabochie-nl|wc-ajax/i.test(url.href);
}

function isStaticAsset(url) {
  return url.origin === self.location.origin && /\/wp-content\//.test(url.pathname) && !/\/uploads\//.test(url.pathname);
}

function isUpload(url) {
  return url.origin === self.location.origin && /\/wp-content\/uploads\//.test(url.pathname);
}

function isFont(url) {
  return url.hostname === 'fonts.gstatic.com';
}

function isFontCss(url) {
  return url.hostname === 'fonts.googleapis.com';
}

function pruneCache(cache, max) {
  return cache.keys().then((keys) => {
    if (keys.length <= max) return;
    return Promise.all(keys.slice(0, keys.length - max).map((k) => cache.delete(k)));
  });
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (isExcluded(url)) return;

  // Page navigations: network-first, cache visited pages for offline, fall back to offline.html
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).then((res) => {
        if (res && res.ok) {
          const clone = res.clone();
          caches.open(PAGES).then((c) => { c.put(req, clone); pruneCache(c, 50); });
        }
        return res;
      }).catch(() => caches.match(req).then((c) => c || caches.match('/offline.html')))
    );
    return;
  }

  // Google Fonts CSS: stale-while-revalidate
  if (isFontCss(url)) {
    event.respondWith(
      caches.open(CACHE).then((cache) => cache.match(req).then((cached) => {
        const network = fetch(req).then((res) => {
          if (res && res.ok) cache.put(req, res.clone());
          return res;
        }).catch(() => null);
        return cached || network;
      }))
    );
    return;
  }

  // Fonts: cache-first
  if (isFont(url)) {
    event.respondWith(
      caches.open(FONTS).then((cache) => cache.match(req).then((cached) => {
        if (cached) return cached;
        return fetch(req).then((res) => {
          if (res && res.ok) { cache.put(req, res.clone()); pruneCache(cache, 40); }
          return res;
        }).catch(() => Response.error());
      }))
    );
    return;
  }

  // Same-origin static assets: stale-while-revalidate with ignoreSearch matching
  if (isStaticAsset(url) || isUpload(url)) {
    event.respondWith(
      caches.open(CACHE).then((cache) => cache.match(req, { ignoreSearch: true }).then((cached) => {
        const network = fetch(req).then((res) => {
          if (res && res.ok) cache.put(req, res.clone());
          return res;
        }).catch(() => null);
        return cached || network;
      }))
    );
    return;
  }

  // Default: network with cache fallback
  event.respondWith(
    fetch(req).catch(() => caches.match(req).then((c) => c || Response.error()))
  );
});