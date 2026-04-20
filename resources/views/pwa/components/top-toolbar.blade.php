<header class="top-toolbar d-flex justify-content-between align-items-center px-3 py-2 shadow-sm fixed-top">

    {{-- Left: hamburger --}}
    <button id="hamburgerBtn" class="btn p-1" aria-label="Open navigation menu">
        <i class="bi bi-list fs-4"></i>
    </button>

    {{-- Centre: app title --}}
    <span class="fs-5 fw-bold">{{ $title ?? config('pwa.app_name') }}</span>

    {{-- Right: install + push + user --}}
    <div class="d-flex align-items-center gap-2">

        {{-- Install-to-homescreen button (hidden until beforeinstallprompt fires) --}}
        @if(config('pwa.install_prompt', true))
        <button id="installBtn" class="btn btn-outline-secondary btn-sm d-none" aria-label="Install app">
            <i class="bi bi-download"></i>
        </button>
        @endif

        {{-- User settings panel --}}
        <button id="userMenuBtn" class="btn p-1" aria-label="Open user settings">
            <i class="bi bi-person-circle fs-4"></i>
        </button>

    </div>
</header>