@extends('layouts.app')
@section('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endsection

@section('content')
<div x-data="creditPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Customer Credit Limit Monitor</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pantau penggunaan credit limit setiap customer</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
            <p class="text-sm text-green-600 font-medium">🟢 Safe (0-75%)</p>
            <p class="text-3xl font-bold mt-1">24</p>
            <p class="text-xs text-gray-500">customers</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
            <p class="text-sm text-yellow-600 font-medium">🟡 Caution (75-95%)</p>
            <p class="text-3xl font-bold mt-1">8</p>
            <p class="text-xs text-gray-500">customers</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <p class="text-sm text-red-600 font-medium">🔴 Critical (95-100%)</p>
            <p class="text-3xl font-bold mt-1">3</p>
            <p class="text-xs text-gray-500">customers</p>
        </div>
        <div class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl p-4">
            <p class="text-sm text-gray-600 dark:text-gray-300 font-medium">⚫ Blocked (>100%)</p>
            <p class="text-3xl font-bold mt-1">1</p>
            <p class="text-xs text-gray-500">customer</p>
        </div>
    </div>

    <!-- Credit Monitor Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="font-semibold">Customer Credit Status</h3>
            <input type="text" placeholder="Cari customer..." class="w-full sm:w-64 px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Customer</th>
                        <th class="text-right px-4 py-3 font-medium">Credit Limit</th>
                        <th class="text-right px-4 py-3 font-medium">Outstanding</th>
                        <th class="text-right px-4 py-3 font-medium">Available</th>
                        <th class="text-center px-4 py-3 font-medium">Usage</th>
                        <th class="text-center px-4 py-3 font-medium">Status</th>
                        <th class="text-center px-4 py-3 font-medium">Forecast</th>
                        <th class="text-right px-4 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium">PT. Makmur Sejahtera</p>
                                <p class="text-xs text-gray-500">CUST-001</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">Rp 500.000.000</td>
                        <td class="px-4 py-3 text-right font-medium text-red-600">Rp 525.000.000</td>
                        <td class="px-4 py-3 text-right font-medium text-red-600">-Rp 25.000.000</td>
                        <td class="px-4 py-3">
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-red-500 h-2 rounded-full" style="width: 100%"></div>
                            </div>
                            <p class="text-xs text-center mt-1 font-medium text-red-600">105%</p>
                        </td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded-full text-xs font-medium">⚫ Blocked</span></td>
                        <td class="px-4 py-3 text-center"><span class="text-red-600 text-xs font-medium">SO Ditolak</span></td>
                        <td class="px-4 py-3 text-right"><button class="text-blue-600 hover:text-blue-700 text-xs font-medium">Review</button></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium">PT. Sukses Abadi</p>
                                <p class="text-xs text-gray-500">CUST-002</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">Rp 750.000.000</td>
                        <td class="px-4 py-3 text-right font-medium text-red-600">Rp 735.000.000</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 15.000.000</td>
                        <td class="px-4 py-3">
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-red-500 h-2 rounded-full" style="width: 98%"></div>
                            </div>
                            <p class="text-xs text-center mt-1 font-medium text-red-600">98%</p>
                        </td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 rounded-full text-xs font-medium">🔴 Critical</span></td>
                        <td class="px-4 py-3 text-center"><span class="text-yellow-600 text-xs font-medium">Need Approval</span></td>
                        <td class="px-4 py-3 text-right"><button class="text-blue-600 hover:text-blue-700 text-xs font-medium">Review</button></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium">PT. Maju Jaya</p>
                                <p class="text-xs text-gray-500">CUST-003</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">Rp 400.000.000</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 220.000.000</td>
                        <td class="px-4 py-3 text-right font-medium text-green-600">Rp 180.000.000</td>
                        <td class="px-4 py-3">
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: 55%"></div>
                            </div>
                            <p class="text-xs text-center mt-1 font-medium text-green-600">55%</p>
                        </td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 rounded-full text-xs font-medium">🟢 Safe</span></td>
                        <td class="px-4 py-3 text-center"><span class="text-green-600 text-xs font-medium">Normal</span></td>
                        <td class="px-4 py-3 text-right"><button class="text-blue-600 hover:text-blue-700 text-xs font-medium">Detail</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function creditPage() {
    return { init() { this.$nextTick(() => lucide.createIcons()); } }
}
</script>
@endsection
