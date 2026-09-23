@props([
    'id' => null,
])

<div class="flex items-center space-x-2">
    <input type="checkbox" {{ $attributes->merge(['class' => 'h-4 w-4 shrink-0 rounded border-border text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50', 'id' => $id]) }} />
    @if(isset($label))
        <label for="{{ $id }}" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            {{ $label }}
        </label>
    @endif
</div>
