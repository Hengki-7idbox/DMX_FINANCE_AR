@props([
    'id' => null,
    'options' => [],
    'placeholder' => 'Pilih...',
    'searchable' => false,
    'allowClear' => false,
])

<select {{ $attributes->merge(['class' => 'flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50', 'id' => $id]) }}>
    @if($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif
    {{ $slot }}
</select>

@if($searchable)
@once
<script>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('{{ $id }}');
    if (select && typeof $.fn.select2 !== 'undefined') {
        $(select).select2({
            placeholder: '{{ $placeholder }}',
            allowClear: {{ $allowClear ? 'true' : 'false' }},
            width: '100%'
        });
    }
});
</script>
@endonce
@endif
