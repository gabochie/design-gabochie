const CACHE='gv-v{{VERSION}}';
const PRECACHE=['/offline.html','/wp-content/themes/gabochie-value/assets/css/styles.css','/wp-content/themes/gabochie-value/assets/css/membership.css','/wp-content/themes/gabochie-value/assets/css/userflow.css','/wp-content/themes/gabochie-value/assets/js/main.js'];
self.addEventListener('install',e=>{
  e.waitUntil(caches.open(CACHE).then(c=>c.addAll(PRECACHE)).then(()=>self.skipWaiting()));
});
self.addEventListener('activate',e=>{
  e.waitUntil(caches.keys().then(keys=>Promise.all(keys.map(k=>k!==CACHE&&caches.delete(k)))).then(()=>self.clients.claim()));
});
self.addEventListener('fetch',e=>{
  const req=e.request;
  if(req.method!=='GET')return;
  const url=new URL(req.url);
  if(url.pathname.startsWith('/wp-admin')||url.pathname.startsWith('/wp-login')||url.pathname.startsWith('/cart')||url.pathname.startsWith('/checkout')||url.pathname.startsWith('/my-account')||url.search.includes('add-to-cart')||url.pathname.startsWith('/wc-api')||url.pathname.startsWith('/wp-json')||url.pathname.startsWith('/admin-ajax'))return;
  if(req.mode==='navigate'){
    e.respondWith(fetch(req).catch(()=>caches.match('/offline.html')));
    return;
  }
  e.respondWith(caches.match(req).then(r=>r||fetch(req).catch(()=>null)));
});
