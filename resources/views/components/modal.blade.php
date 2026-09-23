@props([
    'id' => 'modal',
    'title' => null,
    'description' => null,
    'size' => 'default',
    'closeable' => true,
])

@php
$sizes = [
    'sm' => 'max-w-sm',
    'default' => 'max-w-lg',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
    'full' => 'max-w-6xl',
];
@endphp

<div x-data="{ open: false }" 
     x-on:open-modal.window="$event.detail === '{{ $id }}' ? open = true : null"
     x-on:close-modal.window="$event.detail === '{{ $id }}' ? open = false : null"
     x-on:keydown.escape.window="open = false"
     x-show="open"
     x-cloak
     class="relative z-50">
    
    {{-- Backdrop --}}
    <div x-show="open" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/80">
    </div>
    
    {{-- Modal Content --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="w-full {{ $sizes[$size] ?? $sizes['default'] }} rounded-lg border border-border bg-card p-6 shadow-lg">
                
                {{-- Header --}}
                @if($title || $description || isset($header))
                    <div class="mb-4">
                        @if(isset($header))
                            {{ $header }}
                        @else
                            <div class="flex items-center justify-between">
                                <div>
                                    @if($title)
                                        <h3 class="text-lg font-semibold">{{ $title }}</h3>
                                    @endif
                                    @if($description)
                                        <p class="text-sm text-muted-foreground">{{ $description }}</p>
                                    @endif
                                </div>
                                @if($closeable)
                                    <button type="button" 
                                            @click="open = false"
                                            class="rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
                
                {{-- Content --}}
                <div>
                    {{ $slot }}
                </div>
                
                {{-- Footer --}}
                @if(isset($footer))
                    <div class="mt-6 flex justify-end gap-2">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
