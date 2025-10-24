// StumpVision Service Worker - Offline Support
const VERSION = 'stumpvision-v2.0';
const CORE_CACHE = [
  '/',
  '/index.php',
  '/setup.php',
  '/manifest.webmanifest'
];

// Install - cache core files
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(VERSION)
      .then(cache => cache.addAll(CORE_CACHE))
      .then(() => self.skipWaiting())
  );
});

// Activate - clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames
            .filter(name => name !== VERSION)
            .map(name => caches.delete(name))
        );
      })
      .then(() => self.clients.claim())
  );
});

// Fetch - network first for API, cache first for static
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  
  // Network-first for API calls
  if (url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(event.request)
        .catch(() => caches.match(event.request))
    );
    return;
  }
  
  // Cache-first for everything else
  event.respondWith(
    caches.match(event.request)
      .then(cached => {
        if (cached) return cached;
        
        return fetch(event.request)
          .then(response => {
            // Cache successful responses
            if (response.status === 200) {
              const clone = response.clone();
              caches.open(VERSION)
                .then(cache => cache.put(event.request, clone));
            }
            return response;
          });
      })
      .catch(() => {
        // Offline fallback
        if (event.request.mode === 'navigate') {
          return caches.match('/index.php');
        }
      })
  );
});