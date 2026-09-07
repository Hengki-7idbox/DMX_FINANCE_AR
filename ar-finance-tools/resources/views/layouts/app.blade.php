<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} - AR Finance Tools</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-50 text-surface-900 min-h-screen" x-data="appLayout()">
    <!-- Toast Container -->
    <div x-show="toast.show" x-transition x-cloak
         class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-2xl shadow-lg border p-4"
         :class="toast.type === 'success' ? 'border-emerald-200' : 'border-red-200'">
        <div class="flex items-center gap-3">
            <div class="w-5 h-5 flex items-center justify-center rounded-full"
                 :class="toast.type === 'success' ? 'bg-emerald-100' : 'bg-red-100'">
                <i :data-lucide="toast.type === 'success' ? 'check' : 'x'" class="w-3 h-3"
                   :class="toast.type === 'success' ? 'text-emerald-600' : 'text-red-600'"></i>
            </div>
            <p class="text-sm" x-text="toast.message"></p>
        </div>
    </div>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <aside class="sidebar" :class="sidebarExpanded ? 'expanded' : 'collapsed'"
               @mouseenter="sidebarExpanded = true"
               @mouseleave="sidebarExpanded = false">
            <!-- Logo -->
            <div class="flex items-center gap-3 px-4 h-16 border-b border-surface-100">
                <div class="w-10 h-10 bg-primary-500 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="receipt" class="w-6 h-6 text-white"></i>
                </div>
                <div x-show="sidebarExpanded" x-transition>
                    <h1 class="font-bold text-sm text-surface-900 whitespace-nowrap">AR Finance Tools</h1>
                    <p class="text-xs text-surface-500 whitespace-nowrap">PT. DMX Trading</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-3 space-y-1 overflow-y-auto scrollbar-thin" style="max-height:calc(100vh - 180px)">
                <template x-for="item in navGroups" :key="item.id">
                    <div>
                        <!-- Group with children -->
                        <template x-if="item.children">
                            <div>
                                <button @click="openGroup = openGroup === item.id ? null : item.id"
                                        class="sidebar-item w-full"
                                        :class="item.children.some(c => c.id === currentPage) ? 'active' : ''">
                                    <i :data-lucide="item.icon" class="w-5 h-5 flex-shrink-0"></i>
                                    <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap flex-1 text-left" x-text="item.label"></span>
                                    <i x-show="sidebarExpanded" :data-lucide="openGroup === item.id ? 'chevron-up' : 'chevron-down'" class="w-4 h-4 flex-shrink-0"></i>
                                </button>
                                <div x-show="openGroup === item.id && sidebarExpanded" x-transition
                                     class="ml-5 mt-0.5 space-y-0.5 border-l border-surface-200 pl-3">
                                    <template x-for="child in item.children" :key="child.id">
                                        <a :href="'/' + child.id"
                                           @click.prevent="navigateTo(child.id)"
                                           class="sidebar-item w-full text-sm"
                                           :class="currentPage === child.id ? 'active' : ''">
                                            <i :data-lucide="child.icon" class="w-4 h-4 flex-shrink-0"></i>
                                            <span class="whitespace-nowrap" x-text="child.label"></span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <!-- Single item -->
                        <template x-if="!item.children">
                            <a :href="'/' + item.id"
                               @click.prevent="navigateTo(item.id)"
                               class="sidebar-item w-full"
                               :class="currentPage === item.id ? 'active' : ''">
                                <i :data-lucide="item.icon" class="w-5 h-5 flex-shrink-0"></i>
                                <span x-show="sidebarExpanded" x-transition class="whitespace-nowrap" x-text="item.label"></span>
                            </a>
                        </template>
                    </div>
                </template>
            </nav>

            <!-- Sidebar Footer -->
            <div class="absolute bottom-0 left-0 right-0 p-3 border-t border-surface-100 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-bold text-primary-600">A</span>
                    </div>
                    <div x-show="sidebarExpanded" x-transition class="overflow-hidden flex-1 min-w-0">
                        <p class="text-sm font-medium text-surface-900 truncate">Admin</p>
                        <p class="text-xs text-surface-500">Administrator</p>
                    </div>
                    <button x-show="sidebarExpanded"
                            @click="window.location.href = '/login'"
                            class="p-1.5 text-surface-400 hover:text-primary-500 rounded-lg hover:bg-surface-50 transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="flex-1 transition-all duration-300" :class="sidebarExpanded ? 'ml-[260px]' : 'ml-[80px]'">
            <!-- TOPBAR -->
            <header class="topnav">
                <div class="flex items-center justify-between h-16 px-6">
                    <div>
                        <h2 class="text-lg font-bold text-surface-900" x-text="pageTitle"></h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleDarkMode()"
                                class="p-2 rounded-xl hover:bg-surface-100 transition-colors">
                            <i data-lucide="moon" class="w-5 h-5"></i>
                        </button>
                        <button class="relative p-2 rounded-xl hover:bg-surface-100 transition-colors">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-primary-500 rounded-full"></span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function appLayout() {
            return {
                sidebarExpanded: false,
                openGroup: null,
                currentPage: '{{ $currentPage ?? "dashboard" }}',
                darkMode: localStorage.getItem('darkMode') === 'true',
                toast: { show: false, message: '', type: 'success' },

                navGroups: [
                    { id: 'dashboard', label: 'Dashboard', icon: 'layout-dashboard' },
                    { id: 'grp-data', label: 'Data', icon: 'database', children: [
                        { id: 'customers', label: 'Customer', icon: 'users' },
                        { id: 'exclusions', label: 'Invoice Exclusion', icon: 'shield-x' },
                        { id: 'import-accurate', label: 'Import Laporan', icon: 'file-spreadsheet' },
                    ]},
                    { id: 'aging', label: 'Aging Report', icon: 'calendar-clock' },
                    { id: 'reconciliation', label: 'AR Reconciliation', icon: 'git-compare-arrows' },
                    { id: 'reminders', label: 'Reminders', icon: 'bell-ring' },
                    { id: 'credit', label: 'Credit Monitor', icon: 'credit-card' },
                    { id: 'analyzer', label: 'Customer Analyzer', icon: 'bar-chart-3' },
                    { id: 'tracker', label: 'Collection Tracker', icon: 'target' },
                    { id: 'grp-laporan', label: 'Laporan', icon: 'file-text', children: [
                        { id: 'aging-china', label: 'Aging China', icon: 'bar-chart-2' },
                        { id: 'aging-normal', label: 'Aging Normal', icon: 'bar-chart' },
                        { id: 'query-hutang', label: 'Query Data Hutang', icon: 'filter' },
                    ]},
                ],

                get pageTitle() {
                    const titles = {
                        dashboard: 'Dashboard',
                        customers: 'Customer',
                        exclusions: 'Invoice Exclusion',
                        'import-accurate': 'Import Laporan',
                        aging: 'Aging Report',
                        reconciliation: 'AR Reconciliation',
                        reminders: 'Reminders',
                        credit: 'Credit Monitor',
                        analyzer: 'Customer Analyzer',
                        tracker: 'Collection Tracker',
                        'aging-china': 'Aging China',
                        'aging-normal': 'Aging Normal',
                        'query-hutang': 'Query Data Hutang',
                    };
                    return titles[this.currentPage] || 'Dashboard';
                },

                navigateTo(pageId) {
                    if (this.currentPage === pageId) return;
                    this.currentPage = pageId;
                    history.pushState({ page: pageId }, '', '/' + pageId);
                    window.location.href = '/' + pageId;
                },

                init() {
                    const urlPage = window.location.pathname.replace(/^\//, '');
                    if (urlPage) {
                        this.currentPage = urlPage;
                    }

                    window.addEventListener('popstate', (e) => {
                        if (e.state && e.state.page) {
                            this.currentPage = e.state.page;
                        } else {
                            this.currentPage = window.location.pathname.replace(/^\//, '') || 'dashboard';
                        }
                    });

                    setTimeout(() => {
                        if (window.lucide) window.lucide.createIcons();
                    }, 100);
                },

                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    document.documentElement.classList.toggle('dark', this.darkMode);
                    localStorage.setItem('darkMode', this.darkMode);
                },

                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => { this.toast.show = false; }, 3000);
                }
            };
        }
    </script>
    @yield('scripts')
</body>
</html>
