const CACHE = 'fakt-shell-v1';
const PRIVATE_CACHE = 'fakt-private-v1';
const STATIC = ['/offline.html', '/fakt-icon.svg', '/manifest.webmanifest'];
const OFFLINE_VIEWS = ['/dashboard', '/naptar', '/feladatok'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(STATIC)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => ![CACHE, PRIVATE_CACHE].includes(key)).map((key) => caches.delete(key)))));
    self.clients.claim();
});

self.addEventListener('message', (event) => {
    if (event.data === 'CLEAR_PRIVATE_CACHES') event.waitUntil(caches.delete(PRIVATE_CACHE));
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;
    if (request.destination === 'script' || request.destination === 'style' || request.destination === 'font' || request.destination === 'image') {
        event.respondWith(caches.open(CACHE).then(async (cache) => {
            const cached = await cache.match(request);
            const fresh = fetch(request).then((response) => {
                if (response.ok) cache.put(request, response.clone());
                return response;
            });
            return cached || fresh;
        }));
        return;
    }
    if (request.mode === 'navigate') {
        const url = new URL(request.url);
        if (OFFLINE_VIEWS.includes(url.pathname)) {
            event.respondWith(caches.open(PRIVATE_CACHE).then(async (cache) => {
                try {
                    const response = await fetch(request);
                    if (response.ok) await cache.put(request, response.clone());
                    return response;
                } catch {
                    return (await cache.match(request)) || (await caches.match('/offline.html'));
                }
            }));
            return;
        }
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
    }
});
