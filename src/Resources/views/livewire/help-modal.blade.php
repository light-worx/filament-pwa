{{-- In resources/views/filament-pwa/livewire/help-modal.blade.php --}}

{{-- This root div satisfies Livewire and prevents the error --}}
<div> 
    @if ($this->helpDocument)
        {{-- Only render the button if a help document exists --}}
        {{ $this->issuesAction->iconButton() }}
    @endif
 
    {{-- This must always be present to render any Filament actions/modals --}}
    <x-filament-actions::modals />
</div>