@props([
    'backUrl' => null,
    'title' => null,
    'description' => null,
])

<div class="flex items-center justify-between">
    <div>
        @if($backUrl)
            <a href="{{ $backUrl }}" class="inline-flex items-center text-sm text-[#b0b8c4] hover:text-white mb-2">
                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        @endif
        @if($title)
            <h1 class="text-2xl font-bold tracking-tight text-white">{{ $title }}</h1>
        @endif
        @if($description)
            <p class="text-[#b0b8c4]">{{ $description }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        {{ $slot }}
    </div>
</div>
