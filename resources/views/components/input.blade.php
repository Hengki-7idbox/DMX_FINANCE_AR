@props([
    'id' => null,
    'size' => 'default',
])

@php
$baseClasses = 'flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';

$sizes = [
    'default' => '',
    'sm' => 'h-8 text-xs',
    'lg' => 'h-10',
];

$classes = $baseClasses . ' ' . ($sizes[$size] ?? $sizes['default']);
@endphp

<input {{ $attributes->merge(['class' => $classes, 'id' => $id]) }} />
