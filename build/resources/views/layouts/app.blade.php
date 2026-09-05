<!DOCTYPE html>
<html lang="id" class="{{ $darkMode ?? '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} - AR Finance Tools</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#f0f7ff',100:'#e0efff',200:'#b8dfff',300:'#7cc4ff',400:'#4ba9ff',500:'#2b8eff',600:'#1570e0',700:'#0d56b3',800:'#0a448f',900:'#083366' },
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-transition { transition: width 0.3s ease, transform 0.3s ease; }
        .fade-in { animation: fadeIn 0.2s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible, [tabindex]:focus-visible {
            outline: 2px solid #4ba9ff;
            outline-offset: 2px;
            border-radius: 4px;
        }
        .sr-only { position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border-width:0; }
    </style>
    @yield('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen" x-data="appLayout()">
    <!-- Toast Container -->
    <div x-show="toast.show" x-transition x-cloak
         class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border p-4"
         :class="toast.type === 'success' ? 'border-green-200 dark:border-green-800' : toast.type === 'error' ? 'border-red-200 dark:border-red-800' : 'border-blue-200 dark:border-blue-800'">
        <div class="flex items-center gap-3">
            <i :data-lucide="toast.type === 'success' ? 'check-circle' : toast.type === 'error' ? 'x-circle' : 'info'" class="w-5 h-5"
               :class="toast.type === 'success' ? 'text-green-500' : toast.type === 'error' ? 'text-red-500' : 'text-blue-500'"></i>
            <p class="text-sm" x-text="toast.message"></p>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
         class="fixed inset-0 bg-black/50 z-40 lg:hidden" x-transition.opacity></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed top-0 left-0 z-40 h-full bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 sidebar-transition"
           :class="(sidebarExpanded || sidebarLocked) ? 'w-64' : 'w-20'" @mouseenter="if(!sidebarLocked) sidebarExpanded = true" @mouseleave="if(!sidebarLocked) sidebarExpanded = false"
           role="navigation" aria-label="Sidebar Navigation">

        <!-- Logo -->
        <div class="flex items-center gap-3 px-4 h-16 border-b border-gray-200 dark:border-gray-700">
            <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <i data-lucide="receipt" class="w-6 h-6 text-white"></i>
            </div>
            <div x-show="sidebarExpanded" x-transition class="overflow-hidden">
                <h1 class="font-bold text-sm whitespace-nowrap">AR Finance Tools</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">PT. DMX Trading</p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="p-3 space-y-1 overflow-y-auto" style="max-height: calc(100vh - 180px)">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap">Dashboard</span>
            </a>

            <!-- Separator -->
            <div x-show="sidebarExpanded" class="pt-3 pb-1 px-3">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Tools AR</p>
            </div>

            <!-- Customer Group -->
            <div x-data="{ open: {{ request()->routeIs('exclusions.*','accurate.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                       {{ request()->routeIs('exclusions.*','accurate.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i data-lucide="database" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap flex-1 text-left">Data</span>
                    <i x-show="sidebarExpanded" :data-lucide="open ? 'chevron-up' : 'chevron-down'" class="w-4 h-4 flex-shrink-0"></i>
                </button>
                <div x-show="open && sidebarExpanded" x-transition class="ml-5 mt-0.5 space-y-0.5 border-l border-gray-200 dark:border-gray-700 pl-3">
                    <a href="{{ route('customers.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->routeIs('customers.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200' }}">
                        <i data-lucide="users" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="whitespace-nowrap">Customer</span>
                    </a>
                    <a href="{{ route('exclusions.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->routeIs('exclusions.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200' }}">
                        <i data-lucide="shield-x" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="whitespace-nowrap">Invoice Exclusion</span>
                    </a>
                    <a href="{{ route('accurate.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->routeIs('accurate.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200' }}">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="whitespace-nowrap">Import Laporan Accurate</span>
                    </a>
                </div>
            </div>

            <!-- Tool 2: Aging Report -->
            <a href="{{ route('aging.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('aging.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i data-lucide="calendar-clock" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap">Aging Report</span>
            </a>

            <!-- Tool 3: AR Reconciliation -->
            <a href="{{ route('recon.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('recon.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i data-lucide="git-compare-arrows" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap">AR Reconciliation</span>
            </a>

            <!-- Tool 4: Reminders -->
            <a href="{{ route('reminders.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('reminders.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i data-lucide="bell-ring" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap">Reminders</span>
            </a>

            <!-- Tool 5: Credit Limit -->
            <a href="{{ route('credit.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('credit.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i data-lucide="credit-card" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap">Credit Monitor</span>
            </a>

            <!-- Tool 7: Customer Analyzer -->
            <a href="{{ route('analyzer.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('analyzer.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i data-lucide="bar-chart-3" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap">Customer Analyzer</span>
            </a>

            <!-- Tool 8: Collection Tracker -->
            <a href="{{ route('tracker.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('tracker.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i data-lucide="target" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap">Collection Tracker</span>
            </a>

            @if(auth()->user()->role === 'admin')
            <!-- Separator -->
            <div x-show="sidebarExpanded" class="pt-3 pb-1 px-3">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Admin</p>
            </div>

            <a href="{{ route('admin.users.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('admin.users.*') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i data-lucide="users" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap">Users</span>
            </a>

            <a href="{{ route('admin.audit-log') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('admin.audit-log') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i data-lucide="scroll-text" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap">Audit Log</span>
            </a>

            <a href="{{ route('admin.settings') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('admin.settings') ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i data-lucide="settings" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap">Settings</span>
            </a>
            @endif
        </nav>

        <!-- User Info at Bottom -->
        <div class="absolute bottom-0 left-0 right-0 p-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">{{ substr(auth()->user()->name ?? 'U', 0, 1) }}</span>
                </div>
                <div x-show="sidebarExpanded" x-transition class="overflow-hidden flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', auth()->user()->role ?? 'viewer') }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" x-show="sidebarExpanded" x-transition>
                    @csrf
                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Logout">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>        <!-- Main Content -->
    <div class="lg:ml-20 min-h-screen" :class="(sidebarExpanded || sidebarLocked) ? 'lg:ml-64' : 'lg:ml-20'" x-transition>
        <!-- Top Bar -->
        <header class="sticky top-0 z-30 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between h-16 px-4 lg:px-6">
                <!-- Mobile Menu Toggle -->
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>

                <!-- Page Title -->
                <div>
                    <h2 class="text-lg font-semibold">{{ $pageTitle ?? 'Dashboard' }}</h2>
                    @if(isset($pageSubtitle))
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $pageSubtitle }}</p>
                    @endif
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-2">
                    <!-- Dark Mode Toggle -->
                <!-- Sidebar Toggle -->
                <button @click="toggleSidebar()" class="p-2 rounded-lg transition-colors" :class="sidebarLocked ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600' : 'hover:bg-gray-100 dark:hover:bg-gray-700'" :title="sidebarLocked ? 'Buka Kunci Sidebar' : 'Kunci Sidebar'" :aria-label="sidebarLocked ? 'Buka kunci sidebar' : 'Kunci sidebar'" role="button">
                    <i :data-lucide="sidebarLocked ? 'lock' : 'unlock'" class="w-5 h-5"></i>
                </button>

                <!-- Dark Mode Toggle -->
                <button @click="toggleDark()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'" role="button">
                    <i x-show="!isDark" data-lucide="moon" class="w-5 h-5"></i>
                    <i x-show="isDark" data-lucide="sun" class="w-5 h-5"></i>
                    <span class="sr-only">Toggle theme</span>
                </button>

                    <!-- Notifications -->
                    <button class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>

                    <!-- Profile Dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                <span class="text-xs font-semibold text-primary-600">{{ substr(auth()->user()->name ?? 'U', 0, 1) }}</span>
                            </div>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak x-transition
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1">
                            <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Profile</a>
                            <a href="{{ route('admin.settings') }}" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Settings</a>
                            <hr class="my-1 border-gray-200 dark:border-gray-700">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-4 lg:p-6 fade-in">
            @yield('content')
        </main>
    </div>

    <script>
        function appLayout() {
            return {
                sidebarOpen: false,
                sidebarExpanded: false,
                sidebarLocked: localStorage.getItem('sidebarLocked') === 'true',
                isDark: localStorage.getItem('darkMode') === 'true',
                toast: { show: false, message: '', type: 'success' },
                init() {
                    if (this.isDark) document.documentElement.classList.add('dark');
                    if (this.sidebarLocked) this.sidebarExpanded = true;
                    this.$nextTick(() => lucide.createIcons());
                    window.addEventListener('show-toast', (e) => {
                        this.toast = { show: true, message: e.detail.message, type: e.detail.type || 'success' };
                        setTimeout(() => this.toast.show = false, 3000);
                    });
                },
                toggleDark() {
                    this.isDark = !this.isDark;
                    localStorage.setItem('darkMode', this.isDark);
                    document.documentElement.classList.toggle('dark', this.isDark);
                },
                toggleSidebar() {
                    this.sidebarLocked = !this.sidebarLocked;
                    localStorage.setItem('sidebarLocked', this.sidebarLocked);
                    if (this.sidebarLocked) this.sidebarExpanded = true;
                }
            }
        }
    </script>
    @yield('scripts')
</body>
</html>
