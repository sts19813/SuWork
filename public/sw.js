const CACHE_VERSION = 'suwork-pwa-v1';
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;
const PRECACHE_URLS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/assets/css/app.css',
    '/assets/img/suhomes-app-logo.png',
    '/pwa-icons/icon-192.png',
    '/pwa-icons/icon-512.png',
    '/pwa-icons/maskable-icon-192.png',
    '/pwa-icons/maskable-icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('suwork-pwa-') && key !== CACHE_VERSION && key !== RUNTIME_CACHE)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstNavigation(request));
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request));
    }
});

async function networkFirstNavigation(request) {
    try {
        return await fetch(request);
    } catch (error) {
        const cache = await caches.open(CACHE_VERSION);
        return await cache.match('/offline.html');
    }
}

async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    const response = await fetch(request);

    if (response && response.ok && response.type === 'basic') {
        const cache = await caches.open(RUNTIME_CACHE);
        cache.put(request, response.clone());
    }

    return response;
}

function isStaticAsset(url) {
    if (url.pathname.startsWith('/storage/')) {
        return false;
    }

    return [
        '/assets/',
        '/build/',
        '/metronic/assets/',
        '/pwa-icons/',
    ].some((path) => url.pathname.startsWith(path))
        || url.pathname === '/manifest.webmanifest'
        || url.pathname === '/favicon.ico';
}
