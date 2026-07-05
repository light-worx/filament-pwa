<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('pwa.app_name') }}</title>

    {{-- PWA / push meta --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color"  content="{{ config('pwa.theme.theme_color') }}">
    <meta name="csrf-token"   content="{{ csrf_token() }}">
    <meta name="vapid-key"    content="{{ config('webpush.vapid.public_key') }}">
    <meta name="app-version"  content="{{ config('app.version', '1.0.0') }}">
    <meta name="push-icon"    content="{{ config('pwa.push_icon',  '/pwa/icons/icon-192.png') }}">
    <meta name="push-badge"   content="{{ config('pwa.push_badge', '/pwa/icons/badge-72.png') }}">
    <meta name="flags-path"   content="{{ asset('pwa/flags') }}">
    {{--
        pwa-base: the base URL for all package API calls, without trailing slash.
        Computed from route_prefix / route_domain config so JS never hardcodes /app/.
        In path-prefix mode: https://site.com/app
        In subdomain mode:   https://app.site.com
    --}}
    @php
        $pwaPrefix = config('pwa.route_prefix', 'app');
        $pwaDomain = config('pwa.route_domain');
        if ($pwaDomain) {
            $host    = parse_url(config('app.url'), PHP_URL_SCHEME) . '://'
                     . $pwaDomain . '.'
                     . parse_url(config('app.url'), PHP_URL_HOST);
            $pwaBase = rtrim($host, '/');
        } else {
            $pwaBase = $pwaPrefix !== ''
                ? rtrim(url($pwaPrefix), '/')
                : rtrim(url('/'), '/');
        }
    @endphp
    <meta name="pwa-base"       content="{{ $pwaBase }}">
    <meta name="pwa-push-icon"  content="{{ asset(config('pwa.push_icon',  'pwa/icons/icon-192.png')) }}">
    <meta name="pwa-push-badge" content="{{ asset(config('pwa.push_badge', 'pwa/icons/badge-72.png')) }}">

    {{-- Styles --}}
    <link href="{{ asset('pwa/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('pwa/css/app.css') }}"           rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    {{-- Emit theme as CSS custom properties so any override is a config change --}}
    <style>
        :root {
            --pwa-primary:       {{ config('pwa.theme.primary',      '#1f2937') }};
            --pwa-accent:        {{ config('pwa.theme.accent',       '#3b82f6') }};
            --pwa-toolbar-bg:    {{ config('pwa.theme.toolbar_bg',   '#ffffff') }};
            --pwa-toolbar-text:  {{ config('pwa.theme.toolbar_text', '#111827') }};
            --pwa-bottom-bg:     {{ config('pwa.theme.bottom_bg',    '#1f2937') }};
            --pwa-bottom-text:   {{ config('pwa.theme.bottom_text',  '#cbd5e1') }};
            --pwa-bottom-active: {{ config('pwa.theme.bottom_active','#ffffff') }};
            --pwa-body-bg:       {{ config('pwa.theme.body_bg',      '#f5f6f8') }};
        }

        body {
            padding-top: 56px;
            padding-bottom: 60px;
            background: var(--pwa-body-bg);
        }

        a {
            text-decoration: none;
        }

        /* ── Toolbars ──────────────────────────────────────────────── */
        .top-toolbar {
            background: var(--pwa-toolbar-bg);
            color: var(--pwa-toolbar-text);
            border-bottom: 1px solid rgba(0,0,0,.08);
            z-index: 1030;
        }

        .bottom-toolbar {
            background: var(--pwa-bottom-bg);
            color: var(--pwa-bottom-text);
            z-index: 1030;
        }

        .bottom-toolbar a {
            color: var(--pwa-bottom-text);
            text-decoration: none;
        }

        .bottom-toolbar a.active,
        .bottom-toolbar a:hover {
            color: var(--pwa-bottom-active);
        }

        /* ── Slide menus ───────────────────────────────────────────── */
        .slide-menu {
            position: fixed;
            top: 0;
            width: 100%;
            max-width: 480px;   /* caps width on tablets/desktop */
            height: 100%;
            background: #ffffff;
            box-shadow: 0 0 20px rgba(0,0,0,.15);
            transition: transform .3s ease;
            z-index: 1050;
            overflow-y: auto;
        }

        .slide-menu.left  { left: 0;  transform: translateX(-100%); }
        .slide-menu.right { right: 0; transform: translateX(100%);  }
        .slide-menu.open  { transform: translateX(0); }

        /* ── Overlay ───────────────────────────────────────────────── */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.35);
            opacity: 0;
            visibility: hidden;
            transition: opacity .2s ease;
            z-index: 1040;
        }

        .overlay.show {
            opacity: 1;
            visibility: visible;
            backdrop-filter: blur(2px);
        }

        /* ── Toast ─────────────────────────────────────────────────── */
        #pwa-toast {
            position: fixed;
            bottom: 72px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: #1f2937;
            color: #fff;
            padding: 10px 20px;
            border-radius: 24px;
            font-size: .875rem;
            opacity: 0;
            transition: opacity .25s ease, transform .25s ease;
            z-index: 2000;
            pointer-events: none;
            white-space: nowrap;
        }

        #pwa-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        #pwa-toast.error { background: #dc2626; }
    </style>

    @stack('head')
</head>
<body>

    {{-- Top toolbar --}}
    @include('pwa::components.top-toolbar')

    {{-- Left slide menu (navigation) --}}
    <div class="slide-menu left" id="leftMenu">
        @include('pwa::components.slide-menu')
    </div>

    {{-- Right slide menu (user settings) --}}
    <div class="slide-menu right" id="rightMenu">
        @include('pwa::components.user-menu')
    </div>

    {{-- Backdrop --}}
    <div class="overlay" id="menuOverlay"></div>

    {{-- Toast notification --}}
    <div id="pwa-toast"></div>

    {{-- Main content --}}
    <main class="container my-3">
        @yield('content')
    </main>

    {{-- Bottom toolbar --}}
    @include('pwa::components.bottom-toolbar')

    {{-- Core scripts --}}
    <script src="{{ asset('pwa/js/bootstrap.bundle.min.js') }}"></script>
    
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/pwa/service-worker.js');
        }
    </script>
    <script>
        /* ── Slide menu wiring ─────────────────────────────────────── */
        const leftMenu    = document.getElementById('leftMenu');
        const rightMenu   = document.getElementById('rightMenu');
        const overlay     = document.getElementById('menuOverlay');

        const openMenu  = (menu) => { menu.classList.add('open');    overlay.classList.add('show');    };
        const closeMenus = ()    => {
            leftMenu.classList.remove('open');
            rightMenu.classList.remove('open');
            overlay.classList.remove('show');
        };

        document.getElementById('hamburgerBtn')?.addEventListener('click', () => openMenu(leftMenu));
        document.getElementById('userMenuBtn')?.addEventListener('click',  () => openMenu(rightMenu));
        overlay.addEventListener('click', closeMenus);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenus(); });

        /* ── Toast helper (available globally) ─────────────────────── */
        function showToast(message, type = 'info', duration = 3000) {
            const toast = document.getElementById('pwa-toast');
            toast.textContent = message;
            toast.className   = type === 'error' ? 'show error' : 'show';
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => { toast.className = ''; }, duration);
        }

        window.showToast = showToast;
    </script>

    {{-- Push + install logic --}}
    <script src="{{ asset('pwa/js/push-notifications.js') }}"></script>

    @stack('scripts')
</body>
</html>