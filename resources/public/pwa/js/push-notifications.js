/**
 * push-notifications.js
 * Single source of truth for push + PWA install logic.
 * Exposes window.pushNotifications for use by other components.
 */

(function () {
    'use strict';

    const VAPID_KEY   = document.querySelector('meta[name="vapid-key"]')?.content ?? '';
    const CSRF_TOKEN  = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const APP_VERSION = document.querySelector('meta[name="app-version"]')?.content ?? '1.0.0';
    const PWA_BASE    = (document.querySelector('meta[name="pwa-base"]')?.content ?? '/app')
                        .replace(/\/$/, '');
    const PUSH_ICON   = document.querySelector('meta[name="pwa-push-icon"]')?.content  ?? '/pwa/icons/icon-192.png';
    const PUSH_BADGE  = document.querySelector('meta[name="pwa-push-badge"]')?.content ?? '/pwa/icons/badge-72.png';

    if (!VAPID_KEY) {
        console.warn('PWA: vapid-key meta tag missing. Push notifications will not work.');
    }

    // ── Device ID cookie ─────────────────────────────────────────────────────
    // Writing pwa_device_id to a cookie (in addition to localStorage) lets
    // PHP middleware read the device identity on every request without an
    // AJAX call — enabling server-side access to custom_settings, circuit_id etc.
    function writeDeviceIdCookie(id) {
        try {
            const maxAge = 60 * 60 * 24 * 365; // 1 year
            document.cookie = `pwa_device_id=${encodeURIComponent(id)}; max-age=${maxAge}; path=/; SameSite=Lax`;
        } catch {}
    }

    // ── Utilities ────────────────────────────────────────────────────────────

    function urlBase64ToUint8Array(base64) {
        const padding = '='.repeat((4 - base64.length % 4) % 4);
        const b64     = (base64 + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw     = atob(b64);
        return Uint8Array.from(raw, c => c.charCodeAt(0));
    }

    function isSupported() {
        return 'serviceWorker' in navigator && 'PushManager' in window;
    }

    async function getRegistration() {
        if (!('serviceWorker' in navigator)) return null;
        try { return await navigator.serviceWorker.ready; } catch { return null; }
    }

    async function getCurrentSubscription() {
        const reg = await getRegistration();
        return reg ? reg.pushManager.getSubscription() : null;
    }

    // ── Server sync ──────────────────────────────────────────────────────────

    async function saveSubscriptionToServer(subscription) {
        const json = subscription.toJSON();
        const res  = await fetch(PWA_BASE + '/subscribe', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body:    JSON.stringify(json),
        });
        if (!res.ok) throw new Error('Server error: ' + res.status);

        // Store the endpoint as the canonical device_id so the user-menu's
        // preference loader finds the same UserPreference row the push
        // subscription is linked to.
        try {
            localStorage.setItem('pwa_device_id', subscription.endpoint);
            writeDeviceIdCookie(subscription.endpoint);
        } catch {}

        return res.json();
    }

    async function removeSubscriptionFromServer(endpoint) {
        await fetch(PWA_BASE + '/unsubscribe', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body:    JSON.stringify({ endpoint }),
        });
    }

    /**
     * Ask the server whether it holds a record for this endpoint.
     * Fixes the "appears subscribed locally but no DB record" bug:
     * the browser retains a PushSubscription across page loads even if
     * the server DB was wiped or the row was deleted.
     */
    async function getServerStatus(endpoint) {
        try {
            const res = await fetch(PWA_BASE + '/push/status', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body:    JSON.stringify({ endpoint }),
            });
            if (!res.ok) return { subscribed: false, phone_verified: false };
            return await res.json();
        } catch {
            return { subscribed: false, phone_verified: false };
        }
    }

    // ── Public API ───────────────────────────────────────────────────────────

    async function subscribe() {
        if (!isSupported()) throw new Error('Push not supported');
        if (!VAPID_KEY)     throw new Error('VAPID key not configured');

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') throw new Error('Permission denied');

        const reg = await getRegistration();
        if (!reg) throw new Error('Service worker not ready');

        let sub = await reg.pushManager.getSubscription();
        if (!sub) {
            sub = await reg.pushManager.subscribe({
                userVisibleOnly:      true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_KEY),
            });
        }

        await saveSubscriptionToServer(sub);
        return sub;
    }

    async function unsubscribe() {
        const sub = await getCurrentSubscription();
        if (!sub) return;
        const endpoint = sub.endpoint;
        await sub.unsubscribe();
        await removeSubscriptionFromServer(endpoint);
    }

    /**
     * checkStatus now verifies BOTH the browser subscription AND the server record.
     * If the browser thinks it's subscribed but the server has no record,
     * we re-save the subscription so state is consistent.
     */
    async function checkStatus() {
        if (!isSupported()) {
            return { subscribed: false, permission: 'default', supported: false };
        }

        const sub = await getCurrentSubscription();

        if (!sub) {
            return { subscribed: false, permission: Notification.permission, supported: true };
        }

        // Ensure localStorage always reflects the push endpoint as device_id.
        // This covers page loads where the subscription already exists from a
        // previous session — the user-menu JS reads this key to load preferences.
        try {
            const stored = localStorage.getItem('pwa_device_id');
            if (stored !== sub.endpoint) {
                localStorage.setItem('pwa_device_id', sub.endpoint);
                writeDeviceIdCookie(sub.endpoint);
            }
        } catch {}

        // Check whether the server has this subscription AND whether the
        // device has a verified phone number.
        const serverStatus = await getServerStatus(sub.endpoint);

        if (!serverStatus.subscribed) {
            // Browser has a subscription but server doesn't.
            // Only re-save if this device has a verified phone — otherwise the
            // subscription was deliberately cleared or the user hasn't verified yet.
            if (serverStatus.phone_verified) {
                try { await saveSubscriptionToServer(sub); } catch { /* non-fatal */ }
            }
            // If no verified phone, leave the browser subscription in place
            // (so the toggle can be enabled later once they verify) but don't
            // persist it server-side yet.
        }

        return {
            subscribed:  serverStatus.subscribed,
            permission:  Notification.permission,
            supported:   true,
        };
    }

    window.pushNotifications = { subscribe, unsubscribe, checkStatus };

    // ── Install prompt ───────────────────────────────────────────────────────

    let deferredInstallPrompt = null;

    window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault();
        deferredInstallPrompt = e;
        document.getElementById('installBtn')?.classList.remove('d-none');
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        document.getElementById('installBtn')?.classList.add('d-none');
        window.showToast?.('App installed successfully');
    });

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('installBtn')?.addEventListener('click', async () => {
            if (!deferredInstallPrompt) return;
            deferredInstallPrompt.prompt();
            const { outcome } = await deferredInstallPrompt.userChoice;
            if (outcome === 'accepted') {
                deferredInstallPrompt = null;
                document.getElementById('installBtn')?.classList.add('d-none');
            }
        });
    });

    // ── Service worker registration ──────────────────────────────────────────

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker
            .register('/service-worker.js?v=' + APP_VERSION)
            .then(async registration => {
                // Send app-configured icon paths to the SW so it can use them
                // as fallbacks when a push payload doesn't include icon/badge.
                const sw = registration.active
                        ?? registration.waiting
                        ?? registration.installing;
                if (sw) {
                    sw.postMessage({
                        type:      'PWA_CONFIG',
                        pushIcon:  PUSH_ICON,
                        pushBadge: PUSH_BADGE,
                    });
                }

                // Run checkStatus immediately so the push endpoint is written
                // to localStorage as early as possible.
                await checkStatus();
            })
            .catch(err => console.error('PWA: SW registration failed', err));
    }

})();