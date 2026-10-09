<header class="sticky top-0 z-30 flex h-14 items-center gap-4 border-b border-border bg-card px-6">
    {{-- Mobile Menu Toggle --}}
    <button class="lg:hidden text-muted-foreground hover:text-foreground" onclick="toggleSidebar()">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Search --}}
    <div class="flex-1 max-w-md">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text"
                   placeholder="Cari invoice, customer, payment..."
                   class="h-9 w-full rounded-lg border border-border bg-input pl-10 pr-4 text-sm text-foreground placeholder:text-tertiary focus:outline-none focus:ring-2 focus:ring-ring">
            <kbd class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded border border-border bg-secondary px-1.5 py-0.5 text-[10px] font-medium text-tertiary">
                Ctrl+K
            </kbd>
        </div>
    </div>

    {{-- Right Side --}}
    <div class="flex items-center gap-2">
        {{-- Theme Toggle --}}
        <button class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent hover:text-foreground"
                onclick="toggleTheme()" title="Ganti tema">
            <svg class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>

        {{-- Notifications --}}
        <div class="relative" x-data="{ open: false }">
            <button class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent hover:text-foreground relative"
                    @click="open = !open">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                @if(isset($notifications) && $notifications->count() > 0)
                    <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-[10px] font-medium text-destructive-foreground">
                        {{ $notifications->count() }}
                    </span>
                @endif
            </button>

            {{-- Notification Dropdown --}}
            <div x-show="open" @click.away="open = false"
                 class="absolute right-0 mt-2 w-80 rounded-lg border border-border bg-popover shadow-lg"
                 x-transition>
                <div class="border-b border-border p-3">
                    <h3 class="text-sm font-semibold text-foreground">Notifikasi</h3>
                </div>
                <div class="max-h-80 overflow-y-auto p-2">
                    @if(isset($notifications) && $notifications->count() > 0)
                        @foreach($notifications as $notification)
                            <div class="rounded-lg p-3 hover:bg-accent">
                                <p class="text-sm text-foreground">{{ $notification->message }}</p>
                                <p class="text-xs text-muted-foreground mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        @endforeach
                    @else
                        <p class="p-3 text-sm text-tertiary text-center">Tidak ada notifikasi</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('aside');
    sidebar.classList.toggle('-translate-x-full');
}

function toggleTheme() {
    const html = document.documentElement;
    const next = html.classList.contains('dark') ? 'light' : 'dark';
    html.classList.remove('dark', 'light');
    html.classList.add(next);
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
}
</script>
