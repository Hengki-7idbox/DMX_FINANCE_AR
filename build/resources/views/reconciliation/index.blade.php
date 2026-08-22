@extends('layouts.app')
@section('content')
<div x-data="reconPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">AR Reconciliation</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Rekonstruksi AR dengan bank statement secara otomatis</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="download" class="w-4 h-4"></i> Export
            </button>
            <button @click="showImport = true" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="upload" class="w-4 h-4"></i> Import Bank Statement
            </button>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500">Total Transaksi Bank</p>
            <p class="text-2xl font-bold mt-1">234</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-4">
            <p class="text-sm text-green-600">Auto-Matched</p>
            <p class="text-2xl font-bold mt-1 text-green-600">198</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800 p-4">
            <p class="text-sm text-yellow-600">Need Review</p>
            <p class="text-2xl font-bold mt-1 text-yellow-600">28</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-4">
            <p class="text-sm text-red-600">Unmatched</p>
            <p class="text-2xl font-bold mt-1 text-red-600">8</p>
        </div>
    </div>

    <!-- Match Button -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i data-lucide="zap" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
            <div>
                <p class="font-medium">Auto-Matching</p>
                <p class="text-sm text-gray-500">Jalankan pencocokan otomatis antara invoice dan transaksi bank</p>
            </div>
        </div>
        <button class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
            Run Matching
        </button>
    </div>

    <!-- Reconciliation Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <h3 class="font-semibold">Reconciliation Results</h3>
            <div class="flex gap-1">
                <button class="px-3 py-1 bg-blue-600 text-white rounded-full text-xs">Semua (234)</button>
                <button class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-xs">Matched (198)</button>
                <button class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-xs">Review (28)</button>
                <button class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-xs">Unmatched (8)</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Date</th>
                        <th class="text-left px-4 py-3 font-medium">Description</th>
                        <th class="text-right px-4 py-3 font-medium">Bank Amount</th>
                        <th class="text-right px-4 py-3 font-medium">Invoice No</th>
                        <th class="text-right px-4 py-3 font-medium">AR Amount</th>
                        <th class="text-center px-4 py-3 font-medium">Status</th>
                        <th class="text-center px-4 py-3 font-medium">Confidence</th>
                        <th class="text-right px-4 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 text-gray-500">2026-08-20</td>
                        <td class="px-4 py-3">TRF dari PT MAJU JAYA</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 45.500.000</td>
                        <td class="px-4 py-3 text-right font-mono text-xs">INV-2026-0847</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 45.500.000</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs">Matched</span></td>
                        <td class="px-4 py-3 text-center"><span class="text-green-600 font-medium">98%</span></td>
                        <td class="px-4 py-3 text-right">-</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 text-gray-500">2026-08-20</td>
                        <td class="px-4 py-3">TRF dari PT SUKSES</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 89.000.000</td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-yellow-600">INV-2026-0798?</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 89.200.000</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-xs">Review</span></td>
                        <td class="px-4 py-3 text-center"><span class="text-yellow-600 font-medium">82%</span></td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex gap-1 justify-end">
                                <button class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">Match</button>
                                <button class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">Reject</button>
                            </div>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 text-gray-500">2026-08-19</td>
                        <td class="px-4 py-3">CASH DEPOSIT</td>
                        <td class="px-4 py-3 text-right font-medium">Rp 15.000.000</td>
                        <td class="px-4 py-3 text-right text-gray-400">-</td>
                        <td class="px-4 py-3 text-right text-gray-400">-</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs">Unmatched</span></td>
                        <td class="px-4 py-3 text-center"><span class="text-red-600 font-medium">0%</span></td>
                        <td class="px-4 py-3 text-right">
                            <button class="px-2 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">Manual Match</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Import Modal -->
    <div x-show="showImport" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.away="showImport = false">
            <h3 class="text-lg font-semibold mb-4">Import Bank Statement</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Bank</label>
                    <select class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                        <option>BCA</option><option>BRI</option><option>Mandiri</option><option>BNI</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Periode</label>
                    <input type="month" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                </div>
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center">
                    <i data-lucide="file-spreadsheet" class="w-10 h-10 mx-auto text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-500">Drag & drop file bank statement</p>
                    <p class="text-xs text-gray-400 mt-1">CSV, XLSX, atau OFX</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="showImport = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm">Batal</button>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Import</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function reconPage() {
    return { showImport: false, init() { this.$nextTick(() => lucide.createIcons()); } }
}
</script>
@endsection
