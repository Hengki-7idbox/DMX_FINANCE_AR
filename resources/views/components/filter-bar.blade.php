@props([
    'show' => true,
])

@if($show)
    <div {{ $attributes->merge(['class' => 'flex items-center justify-between p-4 mb-4 rounded-lg border border-border bg-card']) }}>
        {{ $slot }}
    </div>
@endif
