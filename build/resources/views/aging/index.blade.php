@extends('layouts.app')
@section('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endsection

@section('content')
<div x-data="agingPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Aging Report Generator</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Generate laporan aging berdasarkan periode dan filter</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="download" class="w-4 h-4"></i> Export Excel
            </button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="file-text" class="w-4 h-4"></i> Export PDF
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Periode</label>
                <input type="month" value="2026-08" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Customer</label>
                <select class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                    <option>Semua Customer</option>
                    <option>PT. Maju Jaya</option>
                    <option>PT. Sukses Abadi</option>
                    <option>PT. Makmur Sejahtera</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tipe</label>
                <select class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                    <option>Semua Tipe</option>
                    <option>Lokal</option>
                    <option>Ekspor</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full sm:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Generate
                </button>
            </div>
        </div>
    </div>

    <!-- Aging Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
        <h3 class="font-semibold mb-4">Distribusi Aging</h3>
        <canvas id="agingReportChart" height="150"></canvas>
    </div>

    <!-- Summary Table -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
            <p class="text-sm text-green-600 dark:text-green-400 font-medium">Current (0-30 hari)</p>
            <p class="text-2xl font-bold mt-1">Rp 15.2M</p>
            <p class="text-xs text-gray-500 mt-1">156 invoices</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
            <p class="text-sm text-yellow-600 dark:text-yellow-400 font-medium">31-60 hari</p>
            <p class="text-2xl font-bold mt-1">Rp 4.8M</p>
            <p class="text-xs text-gray-500 mt-1">43 invoices</p>
        </div>
        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-4">
            <p class="text-sm text-orange-600 dark:text-orange-400 font-medium">61-90 hari</p>
            <p class="text-2xl font-bold mt-1">Rp 2.4M</p>
            <p class="text-xs text-gray-500 mt-1">18 invoices</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <p class="text-sm text-red-600 dark:text-red-400 font-medium">> 90 hari</p>
            <p class="text-2xl font-bold mt-1">Rp 2.1M</p>
            <p class="text-xs text-gray-500 mt-1">12 invoices</p>
        </div>
    </div>

    <!-- Detail Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Invoice No</th>
                        <th class="text-left px-4 py-3 font-medium">Customer</th>
                        <th class="text-right px-4 py-3 font-medium">Amount</th>
                        <th class="text-center px-4 py-3 font-medium">Due Date</th>
                        <th class="text-center px-4 py-3 font-medium">Hari Tertunggak</th>
                        <th class="text-center px-4 py-3 font-medium">Aging Bucket</th>
                        <th class="text-left px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-mono text-xs">INV-2026-0847</td>
                        <td class="px-4 py-3">PT. Maju Jaya</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 45.500.000</td>
                        <td class="px-4 py-3 text-center text-gray-500">2026-08-15</td>
                        <td class="px-4 py-3 text-center font-medium text-green-600">7 hari</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs">Current</span></td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-xs">Outstanding</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-mono text-xs">INV-2026-0798</td>
                        <td class="px-4 py-3">PT. Sukses Abadi</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 89.200.000</td>
                        <td class="px-4 py-3 text-center text-gray-500">2026-07-01</td>
                        <td class="px-4 py-3 text-center font-medium text-red-600">52 hari</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-xs">31-60 hari</span></td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs">Overdue</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-mono text-xs">INV-2026-0712</td>
                        <td class="px-4 py-3">PT. Makmur Sejahtera</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 156.800.000</td>
                        <td class="px-4 py-3 text-center text-gray-500">2026-05-20</td>
                        <td class="px-4 py-3 text-center font-medium text-red-600">93 hari</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs">> 90 hari</span></td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs">Overdue</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function agingPage() {
    return {
        init() {
            this.$nextTick(() => {
                lucide.createIcons();
                new Chart(document.getElementById('agingReportChart'), {
                    type: 'bar',
                    data: {
                        labels: ['Current', '31-60', '61-90', '> 90'],
                        datasets: [{
                            label: 'Jumlah (Miliar Rp)',
                            data: [15.2, 4.8, 2.4, 2.1],
                            backgroundColor: ['#22c55e', '#eab308', '#f97316', '#ef4444'],
                            borderRadius: 8,
                            barThickness: 50,
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                });
            });
        }
    }
}
</script>
@endsection
