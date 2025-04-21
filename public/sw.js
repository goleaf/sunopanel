// Service Worker for SunoPanel - Music Management System
const CACHE_NAME = 'sunopanel-cache-v1';
const OFFLINE_PAGE = '/offline';
const ASSETS_TO_CACHE = [
  '/offline',
  '/vendor/livewire/livewire.js',
  '/build/assets/app.css',
  '/build/assets/app.js',
  '/favicon.ico',
  // Add any additional critical assets here
];

// Install event - Cache static assets for offline use
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        return cache.addAll(ASSETS_TO_CACHE);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - Clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.filter((cacheName) => {
          return cacheName !== CACHE_NAME;
        }).map((cacheName) => {
          return caches.delete(cacheName);
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - Network with cache fallback strategy
self.addEventListener('fetch', (event) => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip some browsers' requests for additional search/analytics
  const url = new URL(event.request.url);
  if (
    url.pathname.startsWith('/livewire/') ||
    url.pathname.includes('analytics') ||
    url.pathname.includes('chrome-extension')
  ) {
    return;
  }

  // Handle API requests differently
  if (url.pathname.startsWith('/api/')) {
    // For API requests, always try network first
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          return caches.match(event.request)
            .then((cachedResponse) => {
              if (cachedResponse) {
                return cachedResponse;
              }
              
              // For API requests with no cached response, return a JSON error
              return new Response(
                JSON.stringify({ error: 'You are offline' }),
                {
                  headers: { 'Content-Type': 'application/json' },
                  status: 503
                }
              );
            });
        })
    );
    return;
  }

  // Handle document (HTML) requests - Network first, then cache, then offline page
  if (event.request.headers.get('Accept').includes('text/html')) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          // Cache a copy of the response
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseClone);
          });
          return response;
        })
        .catch(() => {
          return caches.match(event.request)
            .then((cachedResponse) => {
              // Return cached response if available
              if (cachedResponse) {
                return cachedResponse;
              }
              // Otherwise, return the offline page
              return caches.match(OFFLINE_PAGE);
            });
        })
    );
    return;
  }

  // For other requests (CSS, JS, images) - Cache first, then network
  event.respondWith(
    caches.match(event.request)
      .then((cachedResponse) => {
        if (cachedResponse) {
          // Return cached response and update cache in background
          const fetchPromise = fetch(event.request)
            .then((networkResponse) => {
              caches.open(CACHE_NAME)
                .then((cache) => {
                  cache.put(event.request, networkResponse.clone());
                });
              return networkResponse;
            })
            .catch(() => cachedResponse);
            
          return cachedResponse;
        }
        
        // If not in cache, fetch from network
        return fetch(event.request)
          .then((response) => {
            // Cache a copy of the response
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseClone);
            });
            return response;
          });
      })
  );
}); 