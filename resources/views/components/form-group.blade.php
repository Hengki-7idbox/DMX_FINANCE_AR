@props([
    'for' => null,
    'label' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
])

<div class="space-y-2">
    @if($label)
        <label for="{{ $for }}" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            {{ $label }}
            @if($required)
                <span class="text-destructive">*</span>
            @endif
        </label>
    @endif
    
    {{ $slot }}
    
    @if($error)
        <p class="text-sm text-destructive">{{ $error }}</p>
    @endif
    
    @if($hint && !$error)
        <p class="text-sm text-muted-foreground">{{ $hint }}</p>
    @endif
</div>
