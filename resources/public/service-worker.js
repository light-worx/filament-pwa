/**
 * service-worker.js
 *
 * Cache version is injected from the app-version meta tag at registration time
 * via a query string: /service-worker.js?v=1.2.3
 * The SW reads it from self.location.search so stale caches are cleared on deploy.
 */

const VERSION    = new URLSearchParams(self.location.search).get('v') || '1.0.0';
const CACHE_NAME = 'filament-pwa-v' + VERSION;

const PRECACHE = [
    '/pwa/css/bootstrap.min.css',
    '/pwa/css/app.css',
    '/pwa/js/bootstrap.bundle.min.js',
    '/pwa/js/push-notifications.js',
];

// ── Install ──────────────────────────────────────────────────────────────────

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
              .then(cache => cache.addAll(PRECACHE))
              .then(() => self.skipWaiting())   // activate immediately
    );
});

// ── Activate — purge old caches ──────────────────────────────────────────────

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ── Fetch — cache-first for assets, network-only for HTML ────────────────────

self.addEventListener('fetch', event => {
    const { request } = event;

    // Let HTML requests go straight to the network (SSR pages must be fresh)
    if (request.headers.get('accept')?.includes('text/html')) return;

    // Network-only for POST / non-GET
    if (request.method !== 'GET') return;

    event.respondWith(
        caches.match(request).then(cached => cached || fetch(request))
    );
});

// ── Push ─────────────────────────────────────────────────────────────────────

self.addEventListener('push', event => {
    let data = {};
    try { data = event.data?.json() ?? {}; } catch { data = { title: event.data?.text() }; }

    const title   = data.title ?? 'Notification';
    const options = {
        body:    data.body  ?? '',
        icon:    data.icon  ?? '/pwa/icons/icon-192.png',
        badge:   data.badge ?? '/pwa/icons/badge-72.png',
        tag:     data.tag   ?? 'pwa-notification',       // replaces previous same-tag notification
        renotify: false,
        data:    { url: data.url ?? '/' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// ── Notification click ───────────────────────────────────────────────────────

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const target = event.notification.data?.url ?? '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windows => {
            // Focus an existing tab at that URL if one exists
            const match = windows.find(w => w.url === target);
            if (match) return match.focus();
            return clients.openWindow(target);
        })
    );
});