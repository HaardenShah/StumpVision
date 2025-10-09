// service-worker.js — basic offline cache (stale-while-revalidate)
const VERSION = 'stumpvision-v1';
const CORE = [
  'index.php',
  'assets/css/style.css',
  'assets/js/app.js',
  'assets/js/state.js',
  'assets/js/scoring.js',
  'assets/js/ui.js',
  'assets/js/util.js',
  'manifest.webmanifest'
];

self.addEventListener('install', (event)=>{
  event.waitUntil(
    caches.open(VERSION).then(cache=>cache.addAll(CORE)).then(self.skipWaiting())
  );
});

self.addEventListener('activate', (event)=>{
  event.waitUntil(
    caches.keys().then(keys=>Promise.all(keys.filter(k=>k!==VERSION).map(k=>caches.delete(k)))))
  self.clients.claim();
});

// Network-first for API, cache-first for assets
self.addEventListener('fetch', (event)=>{
  const url = new URL(event.request.url);
  if (url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(event.request).catch(()=>caches.match(event.request))
    );
    return;
  }
  event.respondWith(
    caches.match(event.request).then(cached=>{
      const fetchPromise = fetch(event.request).then(resp=>{
        const clone = resp.clone();
        caches.open(VERSION).then(cache=>cache.put(event.request, clone));
        return resp;
      }).catch(()=>cached);
      return cached || fetchPromise;
    })
  );
});
