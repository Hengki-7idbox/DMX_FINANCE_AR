@extends('layouts.app')

@section('title', 'Aging China')

@section('content')
<div x-data="{
    filter: 'all',
    data: [
        { id: 1, customer: 'Shenzhen Tech Co.', city: 'Shenzhen', outstanding: 450, current: 120, d3160: 80, d6190: 150, over90: 100, days: 75, status: 'Active' },
        { id: 2, customer: 'Guangzhou Trading Ltd.', city: 'Guangzhou', outstanding: 320, current: 200, d3160: 70, d6190: 30, over90: 20, days: 42, status: 'Active' },
        { id: 3, customer: 'Hong Kong Finance Corp', city: 'Hong Kong', outstanding: 890, current: 50, d3160: 100, d6190: 240, over90: 500, days: 110, status: 'Overdue' },
        { id: 4, customer: 'Taipei Electronics Inc.', city: 'Taipei', outstanding: 210, current: 210, d3160: 0, d6190: 0, over90: 0, days: 15, status: 'Active' },
        { id: 5, customer: 'Shanghai Import Export', city: 'Shanghai', outstanding: 560, current: 100, d3160: 150, d6190: 160, over90: 150, days: 88, status: 'Active' },
        { id: 6, customer: 'Dongguan Manufacturing', city: 'Dongguan', outstanding: 780, current: 30, d3160: 50, d6190: 100, over90: 600, days: 125, status: 'Blocked' },
        { id: 7, customer: 'Beijing Digital Solutions', city: 'Beijing', outstanding: 150, current: 150, d3160: 0, d6190: 0, over90: 0, days: 8, status: 'Active' },
    ],
    get filtered() {
        if (this.filter === 'all') return this.data;
        if (this.filter === 'overdue') return this.data.filter(d => d.days > 90);
        if (this.filter === 'blocked') return this.data.filter(d => d.status === 'Blocked');
        return this.data.filter(d => d.status === 'Active');
    },
    get totalAR() { return this.data.reduce((a,b) => a + b.outstanding, 0); },
    get totalOverdue() { return this.data.filter(d => d.days > 90).length; },
    get avgDays() { return Math.round(this.data.reduce((a,b) => a + b.days, 0) / this.data.length); }
}">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Aging China</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Laporan aging receivable untuk customer China/Hong Kong/Taiwan</p>
        </div>
        <div class="flex gap-2">
            <select x-model="filter" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                <option value="all">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="overdue">Overdue</option>
                <option value="blocked">Blocked</option>
            </select>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="download" class="w-4 h-4"></i> Export
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total AR China</p>
            <p class="text-2xl font-bold mt-1">Rp <span x-text="totalAR.toLocaleString()"></span>M</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Customer</p>
            <p class="text-2xl font-bold mt-1 text-primary-600 dark:text-primary-400" x-text="data.length"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Overdue (>90 hari)</p>
            <p class="text-2xl font-bold mt-1 text-red-600 dark:text-red-400" x-text="totalOverdue"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Avg Days Outstanding</p>
            <p class="text-2xl font-bold mt-1 text-yellow-600 dark:text-yellow-400"><span x-text="avgDays"></span> hari</p>
        </div>
    </div>

    {{-- Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
        <h3 class="font-semibold mb-4">Aging Distribution - China Region</h3>
        <div class="h-64"><canvas id="agingChinaChart"></canvas></div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold">Detail Aging per Customer</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Kota</th>
                        <th class="px-4 py-3 text-right">Outstanding (M)</th>
                        <th class="px-4 py-3 text-right">Current</th>
                        <th class="px-4 py-3 text-right">31-60</th>
                        <th class="px-4 py-3 text-right">61-90</th>
                        <th class="px-4 py-3 text-right">>90</th>
                        <th class="px-4 py-3 text-center">Days</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    <template x-for="d in filtered" :key="d.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-medium" x-text="d.customer"></td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400" x-text="d.city"></td>
                            <td class="px-4 py-3 text-right font-mono" x-text="d.outstanding.toLocaleString()"></td>
                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400 font-mono" x-text="d.current.toLocaleString()"></td>
                            <td class="px-4 py-3 text-right text-yellow-600 dark:text-yellow-400 font-mono" x-text="d.d3160.toLocaleString()"></td>
                            <td class="px-4 py-3 text-right text-orange-600 dark:text-orange-400 font-mono" x-text="d.d6190.toLocaleString()"></td>
                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400 font-mono" x-text="d.over90.toLocaleString()"></td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-medium" :class="d.days<=30?'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400':d.days<=60?'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400':d.days<=90?'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400':'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'" x-text="d.days + 'h'"></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs" :class="d.status==='Active'?'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400':d.status==='Overdue'?'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400':'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'" x-text="d.status"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const ctx = document.getElementById('agingChinaChart');
        if (ctx) {
            const data = @json(App\Models\Customer::where('region', 'china')->get());
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Shenzhen Tech', 'Guangzhou Trading', 'HK Finance', 'Taipei Electronics', 'Shanghai Import', 'Dongguan Mfg', 'Beijing Digital'],
                    datasets: [
                        { label: 'Current', data: [120, 200, 50, 210, 100, 30, 150], backgroundColor: '#22c55e', borderRadius: 4 },
                        { label: '31-60 hari', data: [80, 70, 100, 0, 150, 50, 0], backgroundColor: '#eab308', borderRadius: 4 },
                        { label: '61-90 hari', data: [150, 30, 240, 0, 160, 100, 0], backgroundColor: '#f97316', borderRadius: 4 },
                        { label: '>90 hari', data: [100, 20, 500, 0, 150, 600, 0], backgroundColor: '#ef4444', borderRadius: 4 },
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle' } } },
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: { stacked: true, grid: { color: 'rgba(156,163,175,0.1)' } }
                    }
                }
            });
        }
    }, 500);
});
</script>
@endpush
