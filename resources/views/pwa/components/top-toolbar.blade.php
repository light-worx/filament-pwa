<header class="top-toolbar d-flex justify-content-between align-items-center px-3 py-2 shadow-sm fixed-top">

    {{-- Left: hamburger --}}
    <button id="hamburgerBtn" class="btn p-1" aria-label="Open navigation menu">
        <i class="bi bi-list fs-4"></i>
    </button>

    {{-- Centre: app title --}}
    <span class="fw-bold">{{ $title ?? config('pwa.app_name') }}</span>

    {{-- Right: install + push + user --}}
    <div class="d-flex align-items-center gap-2">

        @if(config('pwa.install_prompt', true))
        <button id="installBtn" class="btn btn-outline-secondary btn-sm d-none" aria-label="Install app">
            <i class="bi bi-download"></i>
        </button>
        @endif

        <button id="updateBtn" class="btn btn-sm btn-warning d-none" aria-label="Update available">
            <i class="bi bi-arrow-clockwise"></i> Update
        </button>

        @if($showUserMenu ?? true)
        <button id="userMenuBtn" class="btn p-1" aria-label="Open user settings">
            <i class="bi bi-person-circle fs-4"></i>
        </button>
        @endif

    </div>
</header>