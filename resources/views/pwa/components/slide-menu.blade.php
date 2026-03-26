<div class="p-3 border-bottom d-flex justify-content-between align-items-center">
    <div>
        <div class="fw-semibold">{{ config('pwa.app_name') }}</div>
        <small class="text-muted">Navigation</small>
    </div>
    <button class="btn btn-sm text-muted" onclick="document.getElementById('menuOverlay').click()" aria-label="Close menu">
        <i class="bi bi-x-lg"></i>
    </button>
</div>

<div class="list-group list-group-flush">

    {{-- Config-driven items --}}
    @foreach(config('pwa.nav_items', []) as $item)
        @php
            $href = isset($item['route'])
                ? (Route::has($item['route']) ? route($item['route']) : '#')
                : ($item['url'] ?? '#');

            $active = request()->url() === $href ? 'active' : '';
        @endphp
        <a href="{{ $href }}"
           class="list-group-item list-group-item-action {{ $active }}">
            <i class="bi {{ $item['icon'] ?? 'bi-circle' }} me-2"></i>
            {{ $item['label'] ?? '' }}
        </a>
    @endforeach

    {{-- Developer-injected extra items --}}
    @stack('pwa-nav-items')

</div>