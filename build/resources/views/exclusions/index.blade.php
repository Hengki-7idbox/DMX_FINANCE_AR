@extends('layouts.app')
@section('content')
<div x-data="exclusionPage()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Invoice Exclusion / Blacklist</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola daftar invoice yang dikecualikan dari pengiriman reminder</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showImportModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="upload" class="w-4 h-4"></i> Bulk Import
            </button>
            <button @click="showAddModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i> Tambah Exclusion
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Exclusions</p>
            <p class="text-2xl font-bold mt-1">127</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Active</p>
            <p class="text-2xl font-bold mt-1 text-green-600">98</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Reverted</p>
            <p class="text-2xl font-bold mt-1 text-gray-400">29</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" placeholder="Cari invoice/customer..." class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <select class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                <option>Semua Status</option>
                <option>Active</option>
                <option>Reverted</option>
            </select>
            <select class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                <option>Semua Alasan</option>
                <option>Dispute</option>
                <option>Duplicate</option>
                <option>Cancelled</option>
                <option>Other</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Invoice No</th>
                        <th class="text-left px-4 py-3 font-medium">Customer</th>
                        <th class="text-left px-4 py-3 font-medium">Amount</th>
                        <th class="text-left px-4 py-3 font-medium">Alasan</th>
                        <th class="text-left px-4 py-3 font-medium">Status</th>
                        <th class="text-left px-4 py-3 font-medium">Excluded By</th>
                        <th class="text-left px-4 py-3 font-medium">Tanggal</th>
                        <th class="text-right px-4 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-mono text-xs">INV-2026-0847</td>
                        <td class="px-4 py-3">PT. Maju Jaya</td>
                        <td class="px-4 py-3 font-medium">Rp 45.500.000</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-xs">Dispute</span></td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs">Active</span></td>
                        <td class="px-4 py-3">Ahmad Fauzi</td>
                        <td class="px-4 py-3 text-gray-500">2026-08-20</td>
                        <td class="px-4 py-3 text-right">
                            <button class="text-gray-400 hover:text-red-500 transition-colors" title="Revert">
                                <i data-lucide="undo-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-mono text-xs">INV-2026-0832</td>
                        <td class="px-4 py-3">PT. Sukses Abadi</td>
                        <td class="px-4 py-3 font-medium">Rp 128.750.000</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs">Cancelled</span></td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs">Active</span></td>
                        <td class="px-4 py-3">Siti Rahayu</td>
                        <td class="px-4 py-3 text-gray-500">2026-08-18</td>
                        <td class="px-4 py-3 text-right">
                            <button class="text-gray-400 hover:text-red-500 transition-colors" title="Revert">
                                <i data-lucide="undo-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-mono text-xs">INV-2026-0819</td>
                        <td class="px-4 py-3">PT. Makmur Sejahtera</td>
                        <td class="px-4 py-3 font-medium">Rp 67.200.000</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full text-xs">Duplicate</span></td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-full text-xs">Reverted</span></td>
                        <td class="px-4 py-3">Budi Santoso</td>
                        <td class="px-4 py-3 text-gray-500">2026-08-15</td>
                        <td class="px-4 py-3 text-right">-</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500">Menampilkan 1-10 dari 127</p>
            <div class="flex gap-1">
                <button class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Prev</button>
                <button class="px-3 py-1 bg-blue-600 text-white rounded text-sm">1</button>
                <button class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm hover:bg-gray-200 dark:hover:bg-gray-600">2</button>
                <button class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Next</button>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div x-show="showAddModal" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.away="showAddModal = false">
            <h3 class="text-lg font-semibold mb-4">Tambah Invoice Exclusion</h3>
            <form class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Invoice Number</label>
                    <input type="text" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm" placeholder="INV-2026-XXXX">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Alasan</label>
                    <select class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                        <option>Dispute</option>
                        <option>Duplicate</option>
                        <option>Cancelled</option>
                        <option>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Catatan</label>
                    <textarea rows="3" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm" placeholder="Alasan pengecualian..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showAddModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Modal -->
    <div x-show="showImportModal" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.away="showImportModal = false">
            <h3 class="text-lg font-semibold mb-4">Bulk Import Exclusion</h3>
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center">
                <i data-lucide="file-spreadsheet" class="w-12 h-12 mx-auto text-gray-400 mb-3"></i>
                <p class="text-sm text-gray-500 mb-2">Drag & drop file atau klik untuk browse</p>
                <p class="text-xs text-gray-400">Format: .xlsx, .csv (Maks 5MB)</p>
                <input type="file" accept=".xlsx,.csv" class="hidden">
                <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Pilih File</button>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button @click="showImportModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600">Batal</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function exclusionPage() {
    return {
        showAddModal: false,
        showImportModal: false,
        init() { this.$nextTick(() => lucide.createIcons()); }
    }
}
</script>
@endsection
