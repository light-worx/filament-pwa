<footer class="bottom-toolbar d-flex justify-content-around align-items-center fixed-bottom py-2">

    @foreach(config('pwa.bottom_items', []) as $item)
        @php
            $href = isset($item['route'])
                ? (Route::has($item['route']) ? route($item['route']) : '#')
                : ($item['url'] ?? '#');

            $active = request()->url() === $href ? 'active' : '';
        @endphp
        <a href="{{ $href }}" class="text-center {{ $active }}" aria-label="{{ $item['label'] ?? '' }}">
            <i class="bi {{ $item['icon'] }} fs-4 d-block"></i>
            @if(!empty($item['label']))
                <span style="font-size:.65rem">{{ $item['label'] }}</span>
            @endif
        </a>
    @endforeach

    {{-- Developer-injected extra items --}}
    @stack('pwa-bottom-items')

</footer>