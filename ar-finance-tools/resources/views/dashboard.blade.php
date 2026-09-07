@extends('layouts.app')

@section('content')
<div x-data="dashboardApp()" x-init="init()">
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stats-card">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs text-surface-500 font-medium">Total AR</p>
                    <p class="text-2xl font-bold text-surface-900 mt-1">Rp 24.5M</p>
                    <div class="flex items-center gap-1 mt-2">
                        <i data-lucide="trending-down" class="w-3 h-3 text-emerald-500"></i>
                        <span class="text-xs text-emerald-500 font-medium">-5.2% dari bulan lalu</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="wallet" class="w-6 h-6 text-primary-500"></i>
                </div>
            </div>
        </div>

        <div class="stats-card">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs text-surface-500 font-medium">Outstanding</p>
                    <p class="text-2xl font-bold text-surface-900 mt-1">Rp 8.75M</p>
                    <div class="flex items-center gap-1 mt-2">
                        <i data-lucide="alert-triangle" class="w-3 h-3 text-amber-500"></i>
                        <span class="text-xs text-amber-500 font-medium">35.7% dari total</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="clock" class="w-6 h-6 text-amber-500"></i>
                </div>
            </div>
        </div>

        <div class="stats-card">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs text-surface-500 font-medium">Overdue (&gt;90h)</p>
                    <p class="text-2xl font-bold text-surface-900 mt-1">Rp 2.1M</p>
                    <div class="flex items-center gap-1 mt-2">
                        <i data-lucide="trending-up" class="w-3 h-3 text-primary-500"></i>
                        <span class="text-xs text-primary-500 font-medium">+2.1% dari bulan lalu</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="alert-circle" class="w-6 h-6 text-primary-500"></i>
                </div>
            </div>
        </div>

        <div class="stats-card">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs text-surface-500 font-medium">Collection Rate</p>
                    <p class="text-2xl font-bold text-surface-900 mt-1">87.3%</p>
                    <div class="flex items-center gap-1 mt-2">
                        <i data-lucide="trending-up" class="w-3 h-3 text-emerald-500"></i>
                        <span class="text-xs text-emerald-500 font-medium">+1.8% dari bulan lalu</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="percent" class="w-6 h-6 text-emerald-500"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="chart-card rounded-2xl p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-surface-900">Aging Distribution</h3>
                <select class="text-sm bg-surface-50 border border-surface-200 rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option>Bulan Ini</option>
                </select>
            </div>
            <div style="height:220px;max-height:220px"><canvas id="agingChart"></canvas></div>
        </div>
        <div class="chart-card rounded-2xl p-5">
            <h3 class="font-bold text-surface-900 mb-4">Status Koleksi</h3>
            <div style="height:180px;max-height:180px"><canvas id="statusChart"></canvas></div>
            <div class="mt-4 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full"></span>
                        <span class="text-surface-600">Settled</span>
                    </span>
                    <span class="font-medium text-surface-900">64.3%</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 bg-amber-500 rounded-full"></span>
                        <span class="text-surface-600">In Progress</span>
                    </span>
                    <span class="font-medium text-surface-900">21.5%</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 bg-primary-500 rounded-full"></span>
                        <span class="text-surface-600">Overdue</span>
                    </span>
                    <span class="font-medium text-surface-900">14.2%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Activity -->
        <div class="chart-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-surface-900">Aktivitas Terbaru</h3>
                <a href="#" class="text-sm text-primary-500 hover:text-primary-600 font-medium">Lihat semua</a>
            </div>
            <div class="space-y-3">
                <div class="activity-item flex items-start gap-3 p-3">
                    <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-surface-900">Invoice <strong>INV-2026-0847</strong> telah dibayar</p>
                        <p class="text-xs text-surface-500 mt-0.5">PT. Maju Jaya &bull; 5 menit lalu</p>
                    </div>
                </div>
                <div class="activity-item flex items-start gap-3 p-3">
                    <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i data-lucide="send" class="w-4 h-4 text-amber-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-surface-900">Reminder terkirim ke <strong>PT. Sukses Abadi</strong></p>
                        <p class="text-xs text-surface-500 mt-0.5">Email + WhatsApp &bull; 15 menit lalu</p>
                    </div>
                </div>
                <div class="activity-item flex items-start gap-3 p-3">
                    <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-primary-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-surface-900">Credit limit <strong>PT. Makmur</strong> melebihi 95%</p>
                        <p class="text-xs text-surface-500 mt-0.5">Auto-escalate ke Finance Manager &bull; 2 jam lalu</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="chart-card rounded-2xl p-5">
            <h3 class="font-bold text-surface-900 mb-4">Aksi Cepat</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="/exclusions" class="quick-action flex items-center gap-3 p-3">
                    <div class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="shield-x" class="w-5 h-5 text-primary-500"></i>
                    </div>
                    <span class="text-sm font-medium text-surface-900">Exclusion List</span>
                </a>
                <a href="/aging" class="quick-action flex items-center gap-3 p-3">
                    <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="calendar-clock" class="w-5 h-5 text-amber-500"></i>
                    </div>
                    <span class="text-sm font-medium text-surface-900">Generate Aging</span>
                </a>
                <a href="/reconciliation" class="quick-action flex items-center gap-3 p-3">
                    <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="git-compare-arrows" class="w-5 h-5 text-emerald-500"></i>
                    </div>
                    <span class="text-sm font-medium text-surface-900">Reconciliation</span>
                </a>
                <a href="/reminders" class="quick-action flex items-center gap-3 p-3">
                    <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="bell-ring" class="w-5 h-5 text-primary-500"></i>
                    </div>
                    <span class="text-sm font-medium text-surface-900">Kirim Reminder</span>
                </a>
                <a href="/credit" class="quick-action flex items-center gap-3 p-3">
                    <div class="w-10 h-10 bg-lavender-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="credit-card" class="w-5 h-5 text-lavender-500"></i>
                    </div>
                    <span class="text-sm font-medium text-surface-900">Credit Monitor</span>
                </a>
                <a href="/tracker" class="quick-action flex items-center gap-3 p-3">
                    <div class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="target" class="w-5 h-5 text-primary-500"></i>
                    </div>
                    <span class="text-sm font-medium text-surface-900">Collection Tracker</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function dashboardApp() {
    return {
        init() {
            this.renderCharts();
        },
        renderCharts() {
            setTimeout(() => {
                const agingCtx = document.getElementById('agingChart');
                if (agingCtx && !agingCtx._chart) {
                    agingCtx._chart = new Chart(agingCtx, {
                        type: 'bar',
                        data: {
                            labels: ['Current (0-30)', '31-60 hari', '61-90 hari', '> 90 hari'],
                            datasets: [{
                                label: 'Miliar Rp',
                                data: [15.2, 4.8, 2.4, 2.1],
                                backgroundColor: ['#22c55e','#eab308','#f97316','#3A86FF'],
                                borderRadius: 8,
                                barThickness: 40
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: 'rgba(156,163,175,0.1)' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }
                const statusCtx = document.getElementById('statusChart');
                if (statusCtx && !statusCtx._chart) {
                    statusCtx._chart = new Chart(statusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Settled','In Progress','Overdue'],
                            datasets: [{
                                data: [64.3,21.5,14.2],
                                backgroundColor: ['#22c55e','#eab308','#3A86FF'],
                                borderWidth: 0,
                                cutout: '70%'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } }
                        }
                    });
                }
                if (window.lucide) window.lucide.createIcons();
            }, 100);
        }
    };
}
</script>
@endsection
