@extends('layouts.app')

@section('title', 'Import Laporan Accurate')

@section('content')
<div x-data="{
    showImportModal: false,
    showHistoryModal: false,
    showClearModal: false,
    clearPassword: '',
    importFile: null,
    importType: 'journal',
    history: {{ \App\Models\ScheduledTask::where('type', 'accurate_import')->orderBy('created_at', 'desc')->get()->map(fn($t) => [
        'id' => $t->id,
        'tanggal' => $t->created_at->format('Y-m-d'),
        'file' => $t->metadata['file_name'] ?? '-',
        'records' => $t->metadata['records_count'] ?? 0,
        'status' => $t->status,
        'user' => $t->user->name ?? '-',
    ])->toJson() : '[]' }},
    get totalImports() { return this.history.length; },
    get totalRecords() { return this.history.reduce((a, b) => a + b.records, 0); },
    get lastImport() { return this.history.length > 0 ? this.history[0].tanggal : '-'; },
    handleClearData() {
        if (this.clearPassword !== 'admin') {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Password salah! Gunakan password admin.', type: 'error' } }));
            return;
        }
        fetch('{{ route('accurate.clear') }}', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            this.history = [];
            this.showClearModal = false;
            this.clearPassword = '';
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Semua data import Accurate berhasil dihapus.', type: 'success' } }));
        })
        .catch(() => {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Gagal menghapus data', type: 'error' } }));
        });
    },
    handleImport() {
        if (!this.importFile) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Pilih file terlebih dahulu', type: 'error' } }));
            return;
        }
        const formData = new FormData();
        formData.append('file', this.importFile);
        formData.append('type', this.importType);
        fetch('{{ route('accurate.import') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.history.unshift({
                    id: Date.now(),
                    tanggal: new Date().toISOString().split('T')[0],
                    file: this.importFile.name,
                    records: data.records_count || 0,
                    status: 'Success',
                    user: '{{ auth()->user()->name ?? "Admin" }}'
                });
                this.showImportModal = false;
                this.importFile = null;
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.message || 'Import berhasil!', type: 'success' } }));
            }
        })
        .catch(() => {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Gagal import data', type: 'error' } }));
        });
    }
}">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Import Laporan Accurate</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Import data transaksi dari software Accurate</p>
        </div>
        <div class="flex gap-2">
            <button @click="showClearModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-900/20 hover:bg-red-200 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 border border-red-300 dark:border-red-600/30 rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Clear Data
            </button>
            <button @click="showHistoryModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="history" class="w-4 h-4"></i> History Import
            </button>
            <button @click="showImportModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="upload" class="w-4 h-4"></i> Import Baru
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Import</p>
            <p class="text-2xl font-bold mt-1" x-text="totalImports"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Records Imported</p>
            <p class="text-2xl font-bold mt-1 text-primary-600 dark:text-primary-400" x-text="totalRecords.toLocaleString()"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Last Import</p>
            <p class="text-lg font-bold mt-1" x-text="lastImport"></p>
        </div>
    </div>

    {{-- History Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold">Riwayat Import</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">File</th>
                        <th class="px-4 py-3 text-left">Records</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    <template x-for="h in history" :key="h.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3" x-text="h.tanggal"></td>
                            <td class="px-4 py-3 font-mono text-xs" x-text="h.file"></td>
                            <td class="px-4 py-3" x-text="h.records.toLocaleString()"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs" :class="h.status==='Success'?'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400':h.status==='Partial'?'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400':'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'" x-text="h.status"></span>
                            </td>
                            <td class="px-4 py-3" x-text="h.user"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal: Import Baru --}}
    <template x-if="showImportModal">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" @click="showImportModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold">Import Laporan Accurate</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Upload file export dari software Accurate</p>
                </div>
                <button @click="showImportModal = false" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tipe Import</label>
                    <select x-model="importType" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="journal">Journal Entry</option>
                        <option value="invoice">Sales Invoice</option>
                        <option value="payment">Payment</option>
                        <option value="customer">Customer Master</option>
                    </select>
                </div>
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center hover:border-primary-500 transition-colors cursor-pointer" @click="$refs.accurateInput.click()" @dragover.prevent="" @drop.prevent="importFile = $event.dataTransfer.files[0]">
                    <template x-if="!importFile">
                        <div>
                            <i data-lucide="file-spreadsheet" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Drag & drop file atau <span class="text-primary-600 font-medium">browse</span></p>
                            <p class="text-xs text-gray-400 mt-1">Format: .xlsx, .csv (maks 10MB)</p>
                        </div>
                    </template>
                    <template x-if="importFile">
                        <div>
                            <i data-lucide="file-check" class="w-12 h-12 text-green-500 mx-auto mb-3"></i>
                            <p class="text-sm text-green-600 dark:text-green-400 font-medium" x-text="importFile.name"></p>
                            <p class="text-xs text-gray-400 mt-1">Klik untuk ganti file</p>
                        </div>
                    </template>
                    <input type="file" x-ref="accurateInput" accept=".xlsx,.csv" class="hidden" @change="importFile = $event.target.files[0]">
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-2">Tips import:</p>
                    <ul class="text-xs text-gray-400 space-y-1">
                        <li>• Export dari Accurate: Menu File → Export → Pilih tipe data</li>
                        <li>• Pastikan kolom sesuai dengan format yang dipilih</li>
                        <li>• File maksimal 10MB</li>
                    </ul>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                <button @click="showImportModal = false; importFile = null" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="handleImport()" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">Import Sekarang</button>
            </div>
        </div>
    </div>
    </template>

    {{-- Modal: History Import --}}
    <template x-if="showHistoryModal">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" @click="showHistoryModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold">Riwayat Import Accurate</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Semua import yang pernah dilakukan</p>
                </div>
                <button @click="showHistoryModal = false" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-5">
                <table class="w-full text-sm">
                    <thead class="text-gray-500 dark:text-gray-400 text-xs uppercase">
                        <tr>
                            <th class="text-left px-3 py-2 font-medium">Tanggal</th>
                            <th class="text-left px-3 py-2 font-medium">File</th>
                            <th class="text-left px-3 py-2 font-medium">Records</th>
                            <th class="text-left px-3 py-2 font-medium">Status</th>
                            <th class="text-left px-3 py-2 font-medium">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                        <template x-for="h in history" :key="h.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-3 py-2.5" x-text="h.tanggal"></td>
                                <td class="px-3 py-2.5 font-mono text-xs" x-text="h.file"></td>
                                <td class="px-3 py-2.5" x-text="h.records.toLocaleString()"></td>
                                <td class="px-3 py-2.5">
                                    <span class="px-2 py-0.5 rounded-full text-xs" :class="h.status==='Success'?'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400':h.status==='Partial'?'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400':'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'" x-text="h.status"></span>
                                </td>
                                <td class="px-3 py-2.5" x-text="h.user"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-end p-5 border-t border-gray-200 dark:border-gray-700">
                <button @click="showHistoryModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">Tutup</button>
            </div>
        </div>
    </div>
    </template>

    {{-- Modal: Clear Data (Password) --}}
    <template x-if="showClearModal">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" @click="showClearModal = false; clearPassword = ''"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-sm">
            <div class="p-6 text-center">
                <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="shield-alert" class="w-7 h-7 text-red-500 dark:text-red-400"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Clear Semua Data Import?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Seluruh riwayat import Accurate akan dihapus. Masukkan password admin untuk konfirmasi.</p>
                <input type="password" x-model="clearPassword" placeholder="Password admin" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-red-500 focus:border-transparent mb-3">
                <p class="text-xs text-yellow-600 dark:text-yellow-400">⚠️ Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="flex items-center justify-center gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                <button @click="showClearModal = false; clearPassword = ''" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="handleClearData()" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">Clear Data</button>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
