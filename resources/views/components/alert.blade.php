@props([
    'type' => 'info',
    'title' => null,
    'closable' => true,
])

@php
$types = [
    'info' => 'bg-[#3b82f6]/10 text-[#3b82f6] border-[#3b82f6]/30',
    'success' => 'bg-[#10b981]/10 text-[#10b981] border-[#10b981]/30',
    'warning' => 'bg-[#f5b041]/10 text-[#f5b041] border-[#f5b041]/30',
    'error' => 'bg-[#ef4444]/10 text-[#ef4444] border-[#ef4444]/30',
];

$icons = [
    'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
    'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
    'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
];
@endphp

<div x-data="{ show: true }" x-show="show" {{ $attributes->merge(['class' => 'rounded-lg border p-4 ' . ($types[$type] ?? $types['info'])]) }}>
    <div class="flex items-start gap-3">
        <svg class="h-5 w-5 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            {!! $icons[$type] ?? $icons['info'] !!}
        </svg>
        <div class="flex-1">
            @if($title)
                <h5 class="font-medium">{{ $title }}</h5>
            @endif
            <div class="text-sm">{{ $slot }}</div>
        </div>
        @if($closable)
            <button type="button" @click="show = false" class="opacity-70 hover:opacity-100">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        @endif
    </div>
</div>
