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

// Flags are cached on-demand (cache-first) via the fetch handler below.
// We don't precache all 80+ flags at install time — that would slow down
// the SW install significantly. They're cached the first time they're seen.

// ── Install ──────────────────────────────────────────────────────────────────

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE))
        // removed: .then(() => self.skipWaiting())
        // — the new worker now waits until the Update button tells it to proceed
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
    const url = new URL(request.url);

    // Let HTML requests go straight to the network (SSR pages must be fresh)
    if (request.headers.get('accept')?.includes('text/html')) return;

    // Network-only for POST / non-GET
    if (request.method !== 'GET') return;

    // Flag images: cache-first, then network, then serve a transparent 1px PNG
    // so the onerror handler fires and the ISO text fallback shows instead of
    // a broken-image icon when offline and not yet cached.
    if (url.pathname.startsWith('/pwa/flags/')) {
        event.respondWith(
            caches.open(CACHE_NAME).then(async cache => {
                const cached = await cache.match(request);
                if (cached) return cached;

                try {
                    const response = await fetch(request);
                    if (response.ok) cache.put(request, response.clone());
                    return response;
                } catch {
                    // Offline and not cached — return a transparent 1×1 PNG
                    // so the img onerror handler fires cleanly
                    return new Response(
                        atob('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='),
                        { headers: { 'Content-Type': 'image/png' } }
                    );
                }
            })
        );
        return;
    }

    // All other assets: cache-first
    event.respondWith(
        caches.match(request).then(cached => cached || fetch(request))
    );
});

// ── Push ─────────────────────────────────────────────────────────────────────

// Icon paths are stored here when the page sends them via postMessage.
// This lets the SW use the app-configured icons even when the payload
// doesn't include them (e.g. notifications sent from external systems).
let pushIcon  = '/pwa/icons/icon-192.png';
let pushBadge = '/pwa/icons/badge-72.png';

// same file — extend the existing message listener rather than adding a second one
self.addEventListener('message', event => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data?.type === 'PWA_CONFIG') {
        if (event.data.pushIcon)  pushIcon  = event.data.pushIcon;
        if (event.data.pushBadge) pushBadge = event.data.pushBadge;
    }
});

self.addEventListener('push', event => {
    let data = {};
    try { data = event.data?.json() ?? {}; } catch { data = { title: event.data?.text() }; }

    const title   = data.title ?? 'Notification';
    const options = {
        body:     data.body  ?? '',
        icon:     data.icon  ?? pushIcon,    // payload > postMessage config > SW default
        badge:    data.badge ?? pushBadge,
        tag:      data.tag   ?? 'pwa-notification',
        renotify: false,
        data:     { url: data.url ?? '/' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// ── Notification click ───────────────────────────────────────────────────────

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const target = event.notification.data?.url ?? '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windows => {
            // If the PWA is already open, navigate it to the target URL
            // (which may include ?open=<id>) rather than just focusing it —
            // this ensures the correct message panel opens even if the user
            // is already on the /messages page.
            const existing = windows.find(w => w.url.includes(self.registration.scope));

            if (existing) {
                existing.focus();
                return existing.navigate(target);
            }

            return clients.openWindow(target);
        })
    );
});