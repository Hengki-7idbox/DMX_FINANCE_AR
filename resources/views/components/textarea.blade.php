@props([
    'id' => null,
    'rows' => 4,
])

<textarea {{ $attributes->merge(['class' => 'flex min-h-[80px] w-full rounded-lg border border-border bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50', 'id' => $id, 'rows' => $rows]) }}>{{ $slot }}</textarea>
