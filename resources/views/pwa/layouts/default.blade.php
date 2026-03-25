<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'PWA App' }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ config('pwa.theme_color') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-key" content="{{ config('webpush.vapid.public_key') }}">
    <link href="{{ asset('pwa/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('pwa/css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            padding-top: 56px;
            padding-bottom: 60px;
            background: #f5f6f8;
        }

        /* ===============================
        TOP / BOTTOM TOOLBARS
        =============================== */
        .top-toolbar {
            background: #ffffff;
            border-bottom: 1px solid #e5e5e5;
            z-index: 1030;
        }

        .bottom-toolbar {
            background: #1f2937;
            color: #fff;
            z-index: 1030;
        }

        .bottom-toolbar a {
            color: #cbd5e1;
        }

        .bottom-toolbar a.active {
            color: #ffffff;
        }

        /* ===============================
        SLIDE MENUS
        =============================== */
        .slide-menu {
            position: fixed;
            top: 0;
            width: 280px;
            height: 100%;
            background: #ffffff;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
            z-index: 1050;
            overflow-y: auto;
        }

        .slide-menu.left {
            left: 0;
            transform: translateX(-100%);
        }

        .slide-menu.right {
            right: 0;
            transform: translateX(100%);
        }

        .slide-menu.open {
            transform: translateX(0);
        }

        /* ===============================
        OVERLAY
        =============================== */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.35);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease;
            z-index: 1040;
        }

        .overlay.show {
            opacity: 1;
            visibility: visible;
            backdrop-filter: blur(2px);
        }
    </style>
</head>
<body>
    {{-- Top toolbar --}}
    @include('vendor.pwa.components.top-toolbar')

    {{-- Left Menu (Navigation) --}}
    <div class="slide-menu left" id="leftMenu">
        @include('vendor.pwa.components.slide-menu')
    </div>

    {{-- Right Menu (User Settings) --}}
    <div class="slide-menu right" id="rightMenu">
        @livewire('pwa-user-settings', ['device_id' => '']) {{-- device_id will be set via JS --}}
    </div>

    <div class="overlay" id="menuOverlay"></div>

    {{-- Main content --}}
    <main class="container my-3">
        @yield('content')
    </main>

    {{-- Bottom toolbar --}}
    @include('vendor.pwa.components.bottom-toolbar')

    {{-- Scripts --}}
    <script src="{{ asset('pwa/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        const leftMenu = document.getElementById('leftMenu');
        const rightMenu = document.getElementById('rightMenu');
        const overlay = document.getElementById('menuOverlay');

        const openMenu = (menu) => {
            menu.classList.add('open');
            overlay.classList.add('show');
        };

        const closeMenus = () => {
            leftMenu.classList.remove('open');
            rightMenu.classList.remove('open');
            overlay.classList.remove('show');
        };

        document.getElementById('hamburgerBtn')
            ?.addEventListener('click', () => openMenu(leftMenu));

        document.getElementById('userMenuBtn')
            ?.addEventListener('click', () => openMenu(rightMenu));

        overlay.addEventListener('click', closeMenus);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMenus();
        });
    </script>
    <script src="{{ asset('pwa/js/push-notifications.js') }}"></script>
    <script>
        /* ===============================
        SERVICE WORKER & PUSH SUBSCRIPTION
        =============================== */
        document.addEventListener('DOMContentLoaded', async () => {
            if (!('serviceWorker' in navigator) || !window.Livewire) return;

            const registration = await navigator.serviceWorker.register('/service-worker.js')
                .then(reg => reg)
                .catch(err => { console.error('SW failed', err); return null; });

            if (!registration) return;

            // Subscribe to push if needed
            let subscription = await registration.pushManager.getSubscription();
            if (!subscription && 'PushManager' in window) {
                const vapidKey = '{{ config("webpush.vapid.public_key") }}';
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: vapidKey
                });
            }

            if (!subscription) return;

            const endpoint = subscription.endpoint;
            const keys = {
                p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth'))))
            };

            // Mount the Livewire component for this device
            const userSettingsComponent = Livewire.find(rightMenu.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
            if (userSettingsComponent) {
                // Set device_id dynamically
                userSettingsComponent.set('device_id', endpoint);

                // Save push subscription to the backend
                userSettingsComponent.call('savePushSubscription', endpoint, keys);
            }
            document.addEventListener('DOMContentLoaded', () => {
            const enablePushBtn = document.getElementById('enable-push');

            if (!enablePushBtn || !('Notification' in window) || Notification.permission !== 'default') return;

            enablePushBtn.classList.remove('d-none');

            enablePushBtn.onclick = async () => {
                try {
                    const registration = await navigator.serviceWorker.ready;
                    const vapidKey = '{{ config("webpush.vapid.public_key") }}';

                    const subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: vapidKey
                    });

                    const endpoint = subscription.endpoint;
                    const keys = {
                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                        auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth'))))
                    };

                    // Save subscription via Livewire component
                    const rightMenu = document.getElementById('rightMenu');
                    const userSettingsComponent = Livewire.find(rightMenu.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
                    if (userSettingsComponent) {
                        userSettingsComponent.set('device_id', endpoint);
                        userSettingsComponent.call('savePushSubscription', endpoint, keys);
                    }

                    enablePushBtn.classList.add('d-none');
                    alert('Push notifications enabled!');
                } catch (e) {
                    console.error('Push enable failed:', e);
                    alert('Failed to enable push notifications.');
                }
            };
        });
        });
    </script>
    @stack('scripts')
</body>
</html>