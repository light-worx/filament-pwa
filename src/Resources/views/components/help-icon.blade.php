@php
    // Get the HelpModal Livewire component instance
    $helpModal = app(\Lightworx\FilamentIssues\Http\Livewire\HelpModal::class);
@endphp

@if ($helpModal->getContextualHelp()) {{-- Only show if there is a help document --}}
    {{-- Render the icon button using the Filament Action --}}
    {{ $helpModal->issuesAction()->iconButton() }}
@endif
