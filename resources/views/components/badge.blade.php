@props([
    'type' => 'default',
    'label' => null,
])

@php
$types = [
    'default' => 'bg-primary text-primary-foreground',
    'secondary' => 'bg-secondary text-secondary-foreground',
    'destructive' => 'bg-destructive text-destructive-foreground',
    'outline' => 'border border-border text-foreground',
    'success' => 'bg-success/10 text-success',
    'warning' => 'bg-warning/10 text-warning',
    'danger' => 'bg-destructive/10 text-destructive',
    'info' => 'bg-info/10 text-info',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . ($types[$type] ?? $types['default'])]) }}>
    @if($label)
        {{ $label }}
    @else
        {{ $slot }}
    @endif
</span>
