@extends('layouts.app')
@section('content')
<div x-data="reminderPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Auto Email & WhatsApp Reminders</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kirim pengingat otomatis ke customer via email dan WhatsApp</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="scroll-text" class="w-4 h-4"></i> View Log
            </button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="send" class="w-4 h-4"></i> Send Batch Reminder
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500">Sent This Month</p>
            <p class="text-2xl font-bold mt-1">342</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500">Email Sent</p>
            <p class="text-2xl font-bold mt-1">228</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500">WhatsApp Sent</p>
            <p class="text-2xl font-bold mt-1">114</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500">Pending Payment</p>
            <p class="text-2xl font-bold mt-1 text-yellow-600">67</p>
        </div>
    </div>

    <!-- Reminder Queue -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold">Antrian Reminder</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium"><input type="checkbox" class="rounded"></th>
                        <th class="text-left px-4 py-3 font-medium">Customer</th>
                        <th class="text-left px-4 py-3 font-medium">Invoice</th>
                        <th class="text-right px-4 py-3 font-medium">Amount</th>
                        <th class="text-center px-4 py-3 font-medium">Hari Tertunggak</th>
                        <th class="text-left px-4 py-3 font-medium">Channel</th>
                        <th class="text-left px-4 py-3 font-medium">Template</th>
                        <th class="text-center px-4 py-3 font-medium">Select</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3"><input type="checkbox" class="rounded"></td>
                        <td class="px-4 py-3">PT. Maju Jaya</td>
                        <td class="px-4 py-3 font-mono text-xs">INV-2026-0847</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 45.500.000</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 rounded-full text-xs">7 hari</span></td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1">
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 rounded-full text-xs">Email</span>
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 rounded-full text-xs">WA</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">Level 1 - Friendly</td>
                        <td class="px-4 py-3 text-center"><input type="checkbox" class="rounded"></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3"><input type="checkbox" class="rounded"></td>
                        <td class="px-4 py-3">PT. Sukses Abadi</td>
                        <td class="px-4 py-3 font-mono text-xs">INV-2026-0798</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 89.200.000</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 rounded-full text-xs">52 hari</span></td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1">
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 rounded-full text-xs">Email</span>
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 rounded-full text-xs">WA</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">Level 3 - Urgent</td>
                        <td class="px-4 py-3 text-center"><input type="checkbox" class="rounded"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm text-gray-500">0 dari 67 dipilih</p>
            <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Send Selected</button>
        </div>
    </div>

    <!-- Template Config -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="font-semibold mb-4">Reminder Templates</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-medium text-sm">Level 1 - Friendly</span>
                    <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 rounded-full text-xs">1-30 hari</span>
                </div>
                <p class="text-xs text-gray-500">Pengingat ramah tentang invoice yang belum dibayar. Tone: Help & Friendly.</p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-medium text-sm">Level 2 - Firm</span>
                    <span class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 rounded-full text-xs">31-60 hari</span>
                </div>
                <p class="text-xs text-gray-500">Pengingat tegas dengan deadline pembayaran. Tone: Professional & Firm.</p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-medium text-sm">Level 3 - Urgent</span>
                    <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 rounded-full text-xs">> 60 hari</span>
                </div>
                <p class="text-xs text-gray-500">Peringatan keras dengan anjuran konsekuensi. Tone: Urgent & Escalation.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function reminderPage() {
    return { init() { this.$nextTick(() => lucide.createIcons()); } }
}
</script>
@endsection
