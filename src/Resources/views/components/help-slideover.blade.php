<div 
    x-data="{ open: false, title: '', content: '' }" 
    x-cloak 
    id="help-slideover" 
    x-show="open"

    {{-- CRITICAL: Listen for the global event dispatched by the button --}}
    @open-help-slideover.window="
        title = $event.detail.title; // Set title from event payload
        content = $event.detail.content; // Set content from event payload
        open = true; // Open the slideover
    "
>
    <div 
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 **z-40**" 
        @click="open = false"
    ></div>

    <div 
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 w-96 bg-white dark:bg-gray-800 shadow-xl overflow-y-auto **z-50**"
    >
        <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 x-text="title" class="text-lg font-bold"></h2>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-4 prose dark:prose-invert" x-html="content"></div>
    </div>
</div>