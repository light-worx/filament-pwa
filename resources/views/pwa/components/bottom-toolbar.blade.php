<footer class="bottom-toolbar d-flex justify-content-around align-items-center fixed-bottom py-2">

    @foreach(config('pwa.bottom_items', []) as $item)
        @php
            $href = isset($item['route'])
                ? (Route::has($item['route']) ? route($item['route']) : '#')
                : ($item['url'] ?? '#');

            $active = request()->url() === $href ? 'active' : '';
            $badge  = $item['badge'] ?? null;
        @endphp
        <a href="{{ $href }}"
           class="text-center position-relative {{ $active }}"
           aria-label="{{ $item['label'] ?? '' }}"
           @if($badge) data-badge="{{ $badge }}" @endif>

            {{-- Icon with optional badge bubble --}}
            <span class="position-relative d-inline-block">
                <i class="bi {{ $item['icon'] }} fs-4 d-block"></i>
                @if($badge)
                    <span class="pwa-toolbar-badge position-absolute top-0 start-100
                                 translate-middle badge rounded-pill bg-danger d-none"
                          data-badge-type="{{ $badge }}"
                          style="font-size:.55rem; min-width:16px; padding:2px 4px;
                                 transform:translate(-40%,-20%)!important;">
                        0
                    </span>
                @endif
            </span>

            @if(!empty($item['label']))
                <span style="font-size:.65rem; display:block; margin-top:1px">
                    {{ $item['label'] }}
                </span>
            @endif
        </a>
    @endforeach

    {{-- Developer-injected extra items --}}
    @stack('pwa-bottom-items')

</footer>

{{-- Badge loader — runs after push-notifications.js has settled the device id --}}
<script>
(function () {
    const badges   = document.querySelectorAll('[data-badge-type="messages"]');
    if (!badges.length) return;
    const PWA_BASE = (document.querySelector('meta[name="pwa-base"]')?.content ?? '/app')
                     .replace(/\/$/, '');

    async function loadMessageBadge() {
        // Wait for device id to be settled (same logic as user-menu)
        let deviceId = localStorage.getItem('pwa_device_id');
        if (!deviceId || !deviceId.startsWith('https://')) {
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                for (let i = 0; i < 20; i++) {
                    await new Promise(r => setTimeout(r, 100));
                    deviceId = localStorage.getItem('pwa_device_id');
                    if (deviceId && deviceId.startsWith('https://')) break;
                }
            }
        }
        if (!deviceId) return;

        try {
            const res = await fetch(
                PWA_BASE + '/messages/unread?device_id=' + encodeURIComponent(deviceId),
                { headers: { 'Accept': 'application/json' } }
            );
            if (!res.ok) return;
            const data = await res.json();
            const unread = data.unread ?? 0;

            badges.forEach(badge => {
                if (unread > 0) {
                    badge.textContent = unread > 99 ? '99+' : String(unread);
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            });
        } catch { /* non-fatal — badge stays hidden */ }
    }

    // Run after the page has loaded and push-notifications.js has had a chance
    // to settle the device id into localStorage
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadMessageBadge);
    } else {
        loadMessageBadge();
    }
})();
</script>