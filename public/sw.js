// LaundryHub Progressive Web App Service Worker
const CACHE_NAME = 'laundryhub-v3';
const ASSETS_TO_CACHE = [
  '/',
  '/manifest.json',
  '/favicon.ico',
  '/favicon.png',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE).catch((err) => {
        console.warn('SW cache.addAll asset warning:', err);
      });
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    (async () => {
      try {
        const response = await fetch(event.request);
        if (response) {
          return response;
        }
      } catch (e) {
        // Fallback to cache on network failure
      }

      const cached = await caches.match(event.request);
      if (cached) {
        return cached;
      }

      return new Response('Network Unavailable', {
        status: 503,
        statusText: 'Service Unavailable',
        headers: new Headers({ 'Content-Type': 'text/plain' }),
      });
    })()
  );
});

self.addEventListener('push', function (e) {
  if (!(self.Notification && self.Notification.permission === 'granted')) {
      return;
  }

  if (e.data) {
      let msg = e.data.json();
      e.waitUntil(self.registration.showNotification(msg.title, {
          body: msg.body,
          icon: msg.icon || '/icons/icon-192x192.png',
          data: msg.data
      }));
  }
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  if (event.notification.data && event.notification.data.url) {
      event.waitUntil(
          clients.openWindow(event.notification.data.url)
      );
  }
});
