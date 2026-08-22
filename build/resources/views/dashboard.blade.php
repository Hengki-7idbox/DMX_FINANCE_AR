@extends('layouts.app')
@section('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endsection

@section('content')
<div x-data="dashboard()" x-init="init()">
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total AR -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-5 py-4">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total AR</p>
                    <p class="text-xl font-bold">Rp {{ number_format(24500000000, 0, ',', '.') }}</p>
                    <div class="flex items-center gap-1 mt-1">
                        <i data-lucide="trending-down" class="w-3 h-3 text-green-500"></i>
                        <span class="text-xs text-green-500 font-medium">-5.2%</span>
                    </div>
                </div>
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0 ml-3">
                    <i data-lucide="wallet" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        <!-- Outstanding -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-5 py-4">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Outstanding</p>
                    <p class="text-xl font-bold">Rp {{ number_format(8750000000, 0, ',', '.') }}</p>
                    <div class="flex items-center gap-1 mt-1">
                        <i data-lucide="alert-triangle" class="w-3 h-3 text-yellow-500"></i>
                        <span class="text-xs text-yellow-500 font-medium">35.7%</span>
                    </div>
                </div>
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center flex-shrink-0 ml-3">
                    <i data-lucide="clock" class="w-5 h-5 text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
        </div>

        <!-- Overdue -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-5 py-4">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Overdue (>90h)</p>
                    <p class="text-xl font-bold">Rp {{ number_format(2100000000, 0, ',', '.') }}</p>
                    <div class="flex items-center gap-1 mt-1">
                        <i data-lucide="trending-up" class="w-3 h-3 text-red-500"></i>
                        <span class="text-xs text-red-500 font-medium">+2.1%</span>
                    </div>
                </div>
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center flex-shrink-0 ml-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 dark:text-red-400"></i>
                </div>
            </div>
        </div>

        <!-- Collection Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-5 py-4">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Collection Rate</p>
                    <p class="text-xl font-bold">87.3%</p>
                    <div class="flex items-center gap-1 mt-1">
                        <i data-lucide="trending-up" class="w-3 h-3 text-green-500"></i>
                        <span class="text-xs text-green-500 font-medium">+1.8%</span>
                    </div>
                </div>
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0 ml-3">
                    <i data-lucide="percent" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <!-- Aging Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold">Aging Distribution</h3>
                <select class="text-sm bg-gray-100 dark:bg-gray-700 border-0 rounded-lg px-3 py-1.5">
                    <option>Bulan Ini</option>
                    <option>3 Bulan Terakhir</option>
                    <option>6 Bulan Terakhir</option>
                </select>
            </div>
            <canvas id="agingChart" height="200"></canvas>
        </div>

        <!-- Collection Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-4">Status Koleksi</h3>
            <canvas id="statusChart" height="200"></canvas>
            <div class="mt-4 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2"><span class="w-3 h-3 bg-green-500 rounded-full"></span> Settled</span>
                    <span class="font-medium">64.3%</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2"><span class="w-3 h-3 bg-yellow-500 rounded-full"></span> In Progress</span>
                    <span class="font-medium">21.5%</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2"><span class="w-3 h-3 bg-red-500 rounded-full"></span> Overdue</span>
                    <span class="font-medium">14.2%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-4">Aktivitas Terbaru</h3>
            <div class="space-y-3">
                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm">Invoice <strong>INV-2026-0847</strong> telah dibayar</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">PT. Maju Jaya • 5 menit lalu</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i data-lucide="send" class="w-4 h-4 text-yellow-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm">Reminder terkirim ke <strong>PT. Sukses Abadi</strong></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Email + WhatsApp • 15 menit lalu</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i data-lucide="upload" class="w-4 h-4 text-blue-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm">Bank statement diimport (<strong>BCA Agustus</strong>)</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">12 transaksi • 1 jam lalu</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm">Credit limit <strong>PT. Makmur Sejahtera</strong> melebihi 95%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Auto-escalate ke Finance Manager • 2 jam lalu</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-4">Aksi Cepat</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('exclusions.index') }}" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i data-lucide="shield-x" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                    <span class="text-sm font-medium">Exclusion List</span>
                </a>
                <a href="{{ route('aging.index') }}" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i data-lucide="calendar-clock" class="w-5 h-5 text-yellow-600 dark:text-yellow-400"></i>
                    <span class="text-sm font-medium">Generate Aging</span>
                </a>
                <a href="{{ route('recon.index') }}" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i data-lucide="git-compare-arrows" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
                    <span class="text-sm font-medium">Reconciliation</span>
                </a>
                <a href="{{ route('reminders.index') }}" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i data-lucide="bell-ring" class="w-5 h-5 text-red-600 dark:text-red-400"></i>
                    <span class="text-sm font-medium">Kirim Reminder</span>
                </a>
                <a href="{{ route('credit.index') }}" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i data-lucide="credit-card" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
                    <span class="text-sm font-medium">Credit Monitor</span>
                </a>
                <a href="{{ route('tracker.index') }}" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i data-lucide="target" class="w-5 h-5 text-orange-600 dark:text-orange-400"></i>
                    <span class="text-sm font-medium">Collection Tracker</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function dashboard() {
    return {
        init() {
            this.$nextTick(() => {
                lucide.createIcons();
                this.renderCharts();
            });
        },
        renderCharts() {
            // Aging Bar Chart
            const agingCtx = document.getElementById('agingChart');
            if (agingCtx) {
                new Chart(agingCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Current (0-30)', '31-60 hari', '61-90 hari', '> 90 hari'],
                        datasets: [{
                            label: 'Nilai (Miliar Rp)',
                            data: [15.2, 4.8, 2.4, 2.1],
                            backgroundColor: ['#22c55e', '#eab308', '#f97316', '#ef4444'],
                            borderRadius: 8,
                            barThickness: 40,
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

            // Status Doughnut Chart
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Settled', 'In Progress', 'Overdue'],
                        datasets: [{
                            data: [64.3, 21.5, 14.2],
                            backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
                            borderWidth: 0,
                            cutout: '70%',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } }
                    }
                });
            }
        }
    }
}
</script>
@endsection
