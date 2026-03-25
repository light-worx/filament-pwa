<header class="top-toolbar d-flex justify-content-between align-items-center px-3 py-2 shadow-sm fixed-top">
    {{-- Left: Hamburger --}}
    <button id="hamburgerBtn" class="btn btn-outline-secondary">
        <i class="bi bi-list fs-4"></i>
    </button>

    {{-- Center: App Title --}}
    <span class="fs-5 fw-bold">{{ $title ?? 'PWA App' }}</span>

    {{-- Right: User & Push --}}
    <div class="d-flex align-items-center gap-2">
        {{-- Push notification button --}}
        <button id="enable-push" class="btn btn-outline-primary btn-sm d-none">
            <i class="bi bi-bell-fill"></i>
        </button>

        {{-- User menu button --}}
        <button id="userMenuBtn" class="btn btn-outline-secondary">
            <i class="bi bi-person-circle fs-4"></i>
        </button>
    </div>
</header>