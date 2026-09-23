@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-border bg-card text-card-foreground shadow-sm']) }}>
    @if($title || $description || isset($header))
        <div class="flex flex-col space-y-1.5 p-6">
            <div class="flex items-center justify-between">
                <div>
                    @if($title)
                        <h3 class="text-2xl font-semibold leading-none tracking-tight">{{ $title }}</h3>
                    @endif
                    @if($description)
                        <p class="text-sm text-muted-foreground">{{ $description }}</p>
                    @endif
                </div>
                @if(isset($header))
                    {{ $header }}
                @endif
            </div>
        </div>
    @endif
    <div class="p-6 pt-0">
        {{ $slot }}
    </div>
    @if(isset($footer))
        <div class="flex items-center p-6 pt-0">
            {{ $footer }}
        </div>
    @endif
</div>
