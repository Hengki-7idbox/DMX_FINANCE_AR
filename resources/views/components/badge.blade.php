@props([
    'type' => 'default',
    'label' => null,
])

@php
$types = [
    'default' => 'bg-[#e8c493] text-[#1a1f2e]',
    'secondary' => 'bg-[#3a4556] text-[#b0b8c4]',
    'destructive' => 'bg-[#ef4444] text-white',
    'outline' => 'border border-[#4a556a] text-white',
    'success' => 'bg-[#10b981]/10 text-[#10b981]',
    'warning' => 'bg-[#f5b041]/10 text-[#f5b041]',
    'danger' => 'bg-[#ef4444]/10 text-[#ef4444]',
    'info' => 'bg-[#3b82f6]/10 text-[#3b82f6]',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . ($types[$type] ?? $types['default'])]) }}>
    @if($label)
        {{ $label }}
    @else
        {{ $slot }}
    @endif
</span>
