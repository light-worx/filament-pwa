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
        body { padding-top: 56px; padding-bottom: 56px; }
        .slide-menu { position: fixed; top: 0; left: -250px; width: 250px; height: 100%; background: #fff; box-shadow: 2px 0 6px rgba(0,0,0,0.2); transition: left 0.3s ease; z-index: 1050; }
        .slide-menu.open { left: 0; }
        .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.3); display: none; z-index: 1040; }
        .overlay.show { display: block; }
    </style>
</head>
<body>
    {{-- Top toolbar --}}
    @include('vendor.pwa.components.top-toolbar')

    {{-- Slide-over menu --}}
    <div class="slide-menu" id="slideMenu">
        @include('vendor.pwa.components.slide-menu')
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
        const menu = document.getElementById('slideMenu');
        const overlay = document.getElementById('menuOverlay');
        document.getElementById('hamburgerBtn').addEventListener('click', () => {
            menu.classList.add('open');
            overlay.classList.add('show');
        });
        overlay.addEventListener('click', () => {
            menu.classList.remove('open');
            overlay.classList.remove('show');
        });
    </script>
    <script src="{{ asset('pwa/js/push-notifications.js') }}"></script>
    <script>
        /* ===============================
        SERVICE WORKER REGISTRATION
        =============================== */
        let swRegistration = null;
        let pendingWorker = null;

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(reg => {
                    console.log('Service Worker registered:', reg);
                    swRegistration = reg;

                    // Helper function to request info from a specific worker
                    const requestUpdateInfo = (worker) => {
                        const channel = new MessageChannel();
                        channel.port1.onmessage = (event) => {
                            if (event.data) {
                                // Now version and notes are defined from the SW's response
                                const { version, notes } = event.data;
                                showUpdateBanner(worker, version, notes);
                            }
                        };
                        // This sends the "ping" to the Service Worker
                        worker.postMessage({ action: 'getVersionInfo' }, [channel.port2]);
                    };

                    // 1. If an update is already waiting (user refreshed but didn't update yet)
                    if (reg.waiting) {
                        requestUpdateInfo(reg.waiting);
                    }

                    // 2. If a new update is found and finishes installing
                    reg.addEventListener('updatefound', () => {
                        const newWorker = reg.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                requestUpdateInfo(newWorker);
                            }
                        });
                    });
                })
                .catch(err => console.error('Service Worker registration failed:', err));
        }

        // Reload when new SW takes control
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            window.location.reload();
        });

        /* ===============================
        UPDATE BANNER
        =============================== */
        function showUpdateBanner(worker, version, notes) {
            const banner = document.getElementById('pwa-update');
            const btn = document.getElementById('pwa-update-btn');
            const versionSpan = document.getElementById('pwa-version');
            const notesDiv = document.getElementById('pwa-notes');

            if (!banner || !btn) return;

            // Inject the data into the HTML
            if (versionSpan) versionSpan.textContent = version;
            if (notesDiv) notesDiv.textContent = notes;

            banner.classList.remove('d-none');

            btn.onclick = () => {
                worker.postMessage({ action: 'skipWaiting' });
                // The SW will skipWaiting, and the controllerchange 
                // listener (if you have one) will reload the page.
                window.location.reload(); 
            };
        }
        document.addEventListener('livewire:load', () => {
            Livewire.hook('message.processed', () => {});

            Livewire.on('notify', (payload) => {
                const { type, message } = payload;
                // Example: simple toast using Bootstrap
                const toastEl = document.createElement('div');
                toastEl.className = `toast align-items-center text-bg-${type} border-0`;
                toastEl.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                document.body.appendChild(toastEl);
                const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
                toast.show();
            });
        });

        /* ===============================
        INSTALL PROMPT
        =============================== */
        window.deferredPrompt = null;

        window.addEventListener('beforeinstallprompt', e => {
            e.preventDefault();
            window.deferredPrompt = e;

            const btn = document.getElementById('install-app');
            if (btn) btn.classList.remove('d-none');
        });

        /* ===============================
        DOM READY
        =============================== */
        document.addEventListener('DOMContentLoaded', () => {

            /* ---- Install button ---- */
            const installBtn = document.getElementById('install-app');
            if (installBtn) {
                installBtn.onclick = async () => {
                    if (!window.deferredPrompt) return;

                    window.deferredPrompt.prompt();
                    const { outcome } = await window.deferredPrompt.userChoice;

                    window.deferredPrompt = null;
                    installBtn.classList.add('d-none');

                    console.log('Install outcome:', outcome);
                };
            }

            /* ---- Enable Push button ---- */
            const enablePushBtn = document.getElementById('enable-push');

            if (
                enablePushBtn &&
                'Notification' in window &&
                Notification.permission === 'default'
            ) {
                enablePushBtn.hidden = false;

                enablePushBtn.onclick = async () => {
                    try {
                        if (!window.pushNotifications) {
                            alert('Push notifications not available');
                            return;
                        }

                        await window.pushNotifications.subscribe();
                        enablePushBtn.hidden = true;
                        alert('Push notifications enabled!');
                    } catch (e) {
                        console.error('Push enable failed:', e);
                        alert('Failed to enable notifications');
                    }
                };
            }

            /* ---- Auto-subscribe if already granted ---- */
            setTimeout(async () => {
                if (!window.pushNotifications) return;

                const status = await window.pushNotifications.checkStatus();

                if (status.permission === 'granted' && !status.subscribed) {
                    await window.pushNotifications.subscribe();
                }

                if (status.subscribed && enablePushBtn) {
                    enablePushBtn.style.display = 'none';
                }
            }, 1000);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
