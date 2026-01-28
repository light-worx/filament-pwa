<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'PWA App' }}</title>
    <link rel="manifest" href="{{ asset('pwa/manifest.json') }}">
    <meta name="theme-color" content="{{ $themeColor ?? '#4f46e5' }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset('pwa/service-worker.js') }}', { scope: '/' })
                .then(function(registration) {
                    console.log('Service Worker registered with scope:', registration.scope);
                })
                .catch(function(error) {
                    console.error('Service Worker registration failed:', error);
                });
        }
    </script>
</body>
</html>
