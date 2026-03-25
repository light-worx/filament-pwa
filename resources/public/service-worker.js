const CACHE = 'filament-pwa-v2';

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE).then(cache => cache.addAll([
            '/pwa/css/bootstrap.min.css',
            '/pwa/css/app.css',
            '/pwa/js/bootstrap.bundle.min.js'
        ]))
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;

    if (request.headers.get('accept')?.includes('text/html')) {
        return;
    }

    // Cache-first for assets
    event.respondWith(
        caches.match(request).then(response => {
            return response || fetch(request);
        })
    );
});