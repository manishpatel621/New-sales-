/**
 * FILE: sw.js
 * Basic Service Worker - enables "Install App" prompt and caches
 * static assets (CSS/JS/icons) so the shell loads fast/offline.
 * Order/product data always comes fresh from the server (not cached).
 */
const CACHE_NAME = 'order-system-shell-v1';
const STATIC_ASSETS = [
    'assets/css/style.css',
    'assets/js/script.js',
    'assets/icons/icon-192.png',
    'assets/icons/icon-512.png',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(STATIC_ASSETS).catch(() => {});
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) { return key !== CACHE_NAME; })
                    .map(function (key) { return caches.delete(key); })
            );
        })
    );
    self.clients.claim();
});

// Only cache static assets (CSS/JS/images). All .php pages always
// go to the network so orders/products/login stay fully live.
self.addEventListener('fetch', function (event) {
    const url = event.request.url;
    const isStatic = /\.(css|js|png|jpg|jpeg|webp|svg)$/.test(url);
    if (!isStatic) return; // let browser handle PHP pages normally (always fresh)

    event.respondWith(
        caches.match(event.request).then(function (cached) {
            return cached || fetch(event.request).then(function (response) {
                const clone = response.clone();
                caches.open(CACHE_NAME).then(function (cache) {
                    cache.put(event.request, clone);
                });
                return response;
            });
        })
    );
});
