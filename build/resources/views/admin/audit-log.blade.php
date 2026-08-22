@extends('layouts.app')
@section('content')
<div x-data="auditPage()" x-init="init()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Audit Log</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Riwayat aktivitas semua pengguna sistem</p>
        </div>
        <button class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium">
            <i data-lucide="download" class="w-4 h-4"></i> Export Log
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <input type="date" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm flex-1">
            <span class="self-center text-gray-400">s/d</span>
            <input type="date" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm flex-1">
            <select class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                <option>Semua User</option><option>Hengki</option><option>Ahmad Fauzi</option><option>Siti Rahayu</option>
            </select>
            <select class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                <option>Semua Aksi</option><option>Create</option><option>Update</option><option>Delete</option><option>Login</option>
            </select>
        </div>
    </div>

    <!-- Log Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Timestamp</th>
                        <th class="text-left px-4 py-3 font-medium">User</th>
                        <th class="text-left px-4 py-3 font-medium">Aksi</th>
                        <th class="text-left px-4 py-3 font-medium">Module</th>
                        <th class="text-left px-4 py-3 font-medium">Detail</th>
                        <th class="text-left px-4 py-3 font-medium">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">2026-08-22 09:15:32</td>
                        <td class="px-4 py-3">Hengki</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 rounded-full text-xs">CREATE</span></td>
                        <td class="px-4 py-3">Invoice Exclusion</td>
                        <td class="px-4 py-3 text-gray-500">Added INV-2026-0847 to exclusion list</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-400">192.168.1.100</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">2026-08-22 08:30:15</td>
                        <td class="px-4 py-3">Ahmad Fauzi</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 rounded-full text-xs">UPDATE</span></td>
                        <td class="px-4 py-3">Aging Report</td>
                        <td class="px-4 py-3 text-gray-500">Generated aging report for August 2026</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-400">192.168.1.105</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">2026-08-21 17:45:00</td>
                        <td class="px-4 py-3">Siti Rahayu</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 rounded-full text-xs">SEND</span></td>
                        <td class="px-4 py-3">Reminders</td>
                        <td class="px-4 py-3 text-gray-500">Sent batch reminders to 15 customers (email + WA)</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-400">192.168.1.110</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">2026-08-21 09:00:00</td>
                        <td class="px-4 py-3">Hengki</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 rounded-full text-xs">LOGIN</span></td>
                        <td class="px-4 py-3">Auth</td>
                        <td class="px-4 py-3 text-gray-500">Login successful</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-400">192.168.1.100</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500">Menampilkan 1-10 dari 1,234</p>
            <div class="flex gap-1">
                <button class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm">Prev</button>
                <button class="px-3 py-1 bg-blue-600 text-white rounded text-sm">1</button>
                <button class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm">Next</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function auditPage() { return { init() { this.$nextTick(() => lucide.createIcons()); } } }
</script>
@endsection
