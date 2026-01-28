<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Open issues ({{count($issues)}})
            &nbsp;&nbsp;&nbsp;
            @if ($this->getHeaderAction())
                {{ $this->getHeaderAction() }}
            @endif
        </x-slot>
        <div class="space-y-4">
            <ul>
            @foreach ($issues as $issue)
                <li>{{$issue->description}} ({{$issue->created_at->diffForHumans()}})</li>
            @endforeach
            </ul>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>