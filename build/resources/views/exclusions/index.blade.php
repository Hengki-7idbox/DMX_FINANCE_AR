@extends('layouts.app')

@section('title', 'Invoice Exclusion')

@section('content')
<div x-data="{
    showModal: false,
    showImportModal: false,
    form: { invoiceNo: '', customer: '', amount: '', alasan: 'Dispute', date: '' },
    importFile: null,
    exclusions: {{ \App\Models\InvoiceExclusion::count() > 0 ? \App\Models\InvoiceExclusion::with('customer')->get()->map(fn($e) => [
        'id' => $e->id,
        'invoice_no' => $e->invoice_no,
        'customer_name' => $e->customer->nama ?? '-',
        'amount' => 'Rp ' . number_format($e->amount / 1000000, 1) . 'M',
        'reason' => $e->reason,
        'status' => $e->status,
        'excluded_at' => $e->excluded_at?->format('Y-m-d') ?? '-',
    ])->toJson() : '[]' }},
    get totalExclusions() { return this.exclusions.length; },
    get activeExclusions() { return this.exclusions.filter(e => e.status === 'Active').length; },
    get revertedExclusions() { return this.exclusions.filter(e => e.status === 'Reverted').length; },
    saveExclusion() {
        if (!this.form.invoiceNo || !this.form.customer) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Invoice No dan Customer harus diisi', type: 'error' } }));
            return;
        }
        fetch('{{ route('exclusions.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(this.form)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.exclusions.push(data.exclusion);
                this.form = { invoiceNo: '', customer: '', amount: '', alasan: 'Dispute', date: '' };
                this.showModal = false;
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Invoice exclusion berhasil ditambahkan!', type: 'success' } }));
            }
        })
        .catch(() => {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Gagal menyimpan exclusion', type: 'error' } }));
        });
    },
    handleImport() {
        if (!this.importFile) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Pilih file terlebih dahulu', type: 'error' } }));
            return;
        }
        const formData = new FormData();
        formData.append('file', this.importFile);
        fetch('{{ route('exclusions.import') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.showImportModal = false;
                this.importFile = null;
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.message || 'Import berhasil!', type: 'success' } }));
                setTimeout(() => location.reload(), 1500);
            }
        });
    },
    revertExclusion(id) {
        fetch('{{ route('exclusions.revert', ':id') }}'.replace(':id', id), {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const item = this.exclusions.find(e => e.id === id);
                if (item) item.status = item.status === 'Reverted' ? 'Active' : 'Reverted';
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Status exclusion diubah', type: 'success' } }));
            }
        });
    },
    deleteExclusion(id) {
        if (!confirm('Yakin ingin menghapus exclusion ini?')) return;
        fetch('{{ route('exclusions.destroy', ':id') }}'.replace(':id', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.exclusions = this.exclusions.filter(e => e.id !== id);
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Exclusion berhasil dihapus', type: 'success' } }));
            }
        });
    }
}">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Invoice Exclusion / Blacklist</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola daftar invoice yang dikecualikan dari pengiriman reminder</p>
        </div>
        <div class="flex gap-2">
            <button @click="showImportModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="upload" class="w-4 h-4"></i> Bulk Import
            </button>
            <button @click="showModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i> Tambah
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Exclusions</p>
            <p class="text-2xl font-bold mt-1" x-text="totalExclusions"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Active</p>
            <p class="text-2xl font-bold mt-1 text-green-600 dark:text-green-400" x-text="activeExclusions"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Reverted</p>
            <p class="text-2xl font-bold mt-1 text-gray-400" x-text="revertedExclusions"></p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Invoice No</th>
                        <th class="text-left px-4 py-3 font-medium">Customer</th>
                        <th class="text-right px-4 py-3 font-medium">Amount</th>
                        <th class="text-left px-4 py-3 font-medium">Alasan</th>
                        <th class="text-left px-4 py-3 font-medium">Status</th>
                        <th class="text-left px-4 py-3 font-medium">Date</th>
                        <th class="text-right px-4 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    <template x-for="e in exclusions" :key="e.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-mono text-xs" x-text="e.invoice_no"></td>
                            <td class="px-4 py-3" x-text="e.customer_name"></td>
                            <td class="px-4 py-3 text-right font-medium" x-text="e.amount"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs" :class="e.reason==='Dispute'?'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400':e.reason==='Cancelled'?'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400':'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'" x-text="e.reason"></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs" :class="e.status==='Active'?'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400':'bg-gray-100 dark:bg-gray-600/30 text-gray-500 dark:text-gray-400'" x-text="e.status"></span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400" x-text="e.excluded_at"></td>
                            <td class="px-4 py-3 text-right">
                                <button @click="revertExclusion(e.id)" class="text-gray-400 hover:text-yellow-500 mr-2" title="Revert">
                                    <i data-lucide="undo-2" class="w-4 h-4"></i>
                                </button>
                                <button @click="deleteExclusion(e.id)" class="text-gray-400 hover:text-red-500" title="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal: Tambah Exclusion --}}
    <div x-show="showModal" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/60" @click="showModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold">Tambah Invoice Exclusion</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Invoice yang dikecualikan dari reminder</p>
                </div>
                <button @click="showModal = false" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Invoice No <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.invoiceNo" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="INV-2026-XXXX">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Customer <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.customer" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="PT. Nama Perusahaan">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount (Miliar Rp)</label>
                        <input type="number" x-model="form.amount" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="45.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alasan</label>
                        <select x-model="form.alasan" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="Dispute">Dispute</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Duplicate">Duplicate</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tanggal</label>
                    <input type="date" x-model="form.date" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                <button @click="showModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="saveExclusion()" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">Simpan</button>
            </div>
        </div>
    </div>

    {{-- Modal: Bulk Import --}}
    <template x-if="showImportModal">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" @click="showImportModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold">Bulk Import Exclusion</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Upload file Excel/CSV daftar exclusion</p>
                </div>
                <button @click="showImportModal = false" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-5">
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center hover:border-primary-500 transition-colors cursor-pointer" @click="$refs.importInput.click()" @dragover.prevent="" @drop.prevent="importFile = $event.dataTransfer.files[0]">
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
                    <input type="file" x-ref="importInput" accept=".xlsx,.csv" class="hidden" @change="importFile = $event.target.files[0]">
                </div>
                <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-2">Format kolom yang diperlukan:</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-xs">Invoice No</span>
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-xs">Customer</span>
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-xs">Amount</span>
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-xs">Alasan</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                <button @click="showImportModal = false; importFile = null" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="handleImport()" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">Import Sekarang</button>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
