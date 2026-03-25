<div class="p-3 border-bottom d-flex align-items-center gap-3">
    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white"
         style="width:40px;height:40px;">
        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
    </div>

    <div>
        <div class="fw-semibold">{{ auth()->user()->name ?? 'User' }}</div>
        <small class="text-muted">{{ auth()->user()->email ?? '' }}</small>
    </div>
</div>

<div class="p-3">
    @livewire('pwa-user-settings')
</div>