@extends('layouts.app')
@section('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endsection

@section('content')
<div x-data="analyzerPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Customer Analyzer</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Analisis perilaku pembayaran dan pola customer</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium">
                <i data-lucide="users" class="w-4 h-4"></i> Cohort Analysis
            </button>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex gap-3">
            <div class="flex-1 relative">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                <input type="text" placeholder="Cari customer (nama atau kode)..." class="w-full pl-10 pr-4 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
            </div>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Analyze</button>
        </div>
    </div>

    <!-- Customer Analysis Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold">PT. Maju Jaya</h3>
                <p class="text-sm text-gray-500">CUST-003 • Customer sejak 2021</p>
            </div>
            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-sm font-medium">Rating: A</span>
        </div>

        <!-- Score Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">87</p>
                <p class="text-xs text-gray-500">Payment Score</p>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-2xl font-bold">12.3</p>
                <p class="text-xs text-gray-500">Avg Days to Pay</p>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-2xl font-bold">Rp 2.4M</p>
                <p class="text-xs text-gray-500">Avg Monthly AR</p>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-2xl font-bold">45</p>
                <p class="text-xs text-gray-500">Total Invoices</p>
            </div>
        </div>

        <!-- Payment History Chart -->
        <h4 class="font-medium mb-3">Riwayat Pembayaran (12 Bulan)</h4>
        <canvas id="paymentHistoryChart" height="120"></canvas>
    </div>

    <!-- Cohort Analysis -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold">Customer Cohort</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Customer</th>
                        <th class="text-center px-4 py-3 font-medium">Rating</th>
                        <th class="text-center px-4 py-3 font-medium">Avg DTP</th>
                        <th class="text-right px-4 py-3 font-medium">Total AR</th>
                        <th class="text-center px-4 py-3 font-medium">Payment Trend</th>
                        <th class="text-center px-4 py-3 font-medium">Risk Level</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-medium">PT. Maju Jaya</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 rounded-full text-xs font-bold">A</span></td>
                        <td class="px-4 py-3 text-center">12 hari</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 220M</td>
                        <td class="px-4 py-3 text-center"><span class="text-green-500">📈 Improving</span></td>
                        <td class="px-4 py-3 text-center"><span class="text-green-600 font-medium">Low</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-medium">PT. Sukses Abadi</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 rounded-full text-xs font-bold">B</span></td>
                        <td class="px-4 py-3 text-center">28 hari</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 735M</td>
                        <td class="px-4 py-3 text-center"><span class="text-yellow-500">➡️ Stable</span></td>
                        <td class="px-4 py-3 text-center"><span class="text-yellow-600 font-medium">Medium</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-medium">PT. Makmur Sejahtera</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 rounded-full text-xs font-bold">C</span></td>
                        <td class="px-4 py-3 text-center">65 hari</td>
                        <td class="px-4 py-3 text-right font-medium text-red-600">Rp 525M</td>
                        <td class="px-4 py-3 text-center"><span class="text-red-500">📉 Declining</span></td>
                        <td class="px-4 py-3 text-center"><span class="text-red-600 font-medium">High</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function analyzerPage() {
    return {
        init() {
            this.$nextTick(() => {
                lucide.createIcons();
                const ctx = document.getElementById('paymentHistoryChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                            datasets: [
                                { label: 'AR Balance', data: [180,195,210,200,225,240,220,230,215,225,235,220], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.4 },
                                { label: 'Payment', data: [160,185,200,195,210,230,225,215,220,230,225,240], borderColor: '#22c55e', borderDash: [5,5], tension: 0.4 }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
                    });
                }
            });
        }
    }
}
</script>
@endsection
