@props([
    'variant' => 'default',
    'size' => 'default',
    'href' => null,
    'type' => 'button',
    'disabled' => false,
])

@php
$baseClasses = 'inline-flex items-center justify-center rounded-lg text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1dd7d7] focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50';

$variants = [
    'default' => 'bg-[#e8c493] text-[#1a1f2e] hover:bg-[#d4a574]',
    'destructive' => 'bg-[#ef4444] text-white hover:bg-[#ef4444]/90',
    'outline' => 'border border-[#4a556a] bg-transparent text-white hover:bg-[#3a4556]',
    'secondary' => 'bg-[#3a4556] text-white hover:bg-[#4a556a]',
    'ghost' => 'hover:bg-[#3a4556] hover:text-white',
    'link' => 'text-[#e8c493] underline-offset-4 hover:underline',
];

$sizes = [
    'default' => 'h-9 px-4 py-2',
    'sm' => 'h-8 rounded-md px-3 text-xs',
    'lg' => 'h-10 rounded-md px-8',
    'icon' => 'h-9 w-9',
];

$classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['default']) . ' ' . ($sizes[$size] ?? $sizes['default']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes, 'disabled' => $disabled]) }}>
        {{ $slot }}
    </button>
@endif
