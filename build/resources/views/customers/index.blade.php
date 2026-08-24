@extends('layouts.app')

@section('title', 'Customer')

@section('content')
<div x-data="{
    showModal: false,
    showImportModal: false,
    searchQuery: '',
    form: { nama: '', kota: '', creditLimit: '', email: '', telepon: '', alamat: '' },
    importFile: null,
    customers: {{ \App\Models\Customer::count() > 0 ? \App\Models\Customer::all()->map(fn($c) => [
        'id' => $c->id,
        'kode' => $c->kode,
        'nama' => $c->nama,
        'kota' => $c->kota,
        'credit_limit' => 'Rp ' . number_format($c->credit_limit / 1000000, 0) . 'M',
        'outstanding' => 'Rp ' . number_format($c->outstanding / 1000000, 1) . 'M',
        'status' => $c->status,
    ])->toJson() : '[]' }},
    get filteredCustomers() {
        if (!this.searchQuery) return this.customers;
        return this.customers.filter(c =>
            c.nama.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
            c.kode.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
            c.kota.toLowerCase().includes(this.searchQuery.toLowerCase())
        );
    },
    get totalCustomers() { return this.customers.length; },
    get activeCustomers() { return this.customers.filter(c => c.status === 'Aktif').length; },
    get inactiveCustomers() { return this.customers.filter(c => c.status === 'Non-aktif').length; },
    saveCustomer() {
        if (!this.form.nama || !this.form.kota) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Nama dan kota harus diisi', type: 'error' } }));
            return;
        }
        fetch('{{ route('customers.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(this.form)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.customers.push(data.customer);
                this.form = { nama: '', kota: '', creditLimit: '', email: '', telepon: '', alamat: '' };
                this.showModal = false;
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Customer berhasil ditambahkan!', type: 'success' } }));
            }
        })
        .catch(() => {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Gagal menyimpan customer', type: 'error' } }));
        });
    },
    handleImport() {
        if (!this.importFile) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Pilih file terlebih dahulu', type: 'error' } }));
            return;
        }
        const formData = new FormData();
        formData.append('file', this.importFile);
        fetch('{{ route('customers.import') }}', {
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
        })
        .catch(() => {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Gagal import data', type: 'error' } }));
        });
    },
    deleteCustomer(id) {
        if (!confirm('Yakin ingin menghapus customer ini?')) return;
        fetch('{{ route('customers.destroy', ':id') }}'.replace(':id', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.customers = this.customers.filter(c => c.id !== id);
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Customer berhasil dihapus', type: 'success' } }));
            }
        });
    }
}">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Data Customer</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola data master customer</p>
        </div>
        <div class="flex gap-2">
            <button @click="showImportModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="upload" class="w-4 h-4"></i> Import
            </button>
            <button @click="showModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i> Tambah Customer
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Customer</p>
            <p class="text-2xl font-bold mt-1" x-text="totalCustomers"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Aktif</p>
            <p class="text-2xl font-bold mt-1 text-green-600 dark:text-green-400" x-text="activeCustomers"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Non-aktif</p>
            <p class="text-2xl font-bold mt-1 text-gray-400" x-text="inactiveCustomers"></p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold">Daftar Customer</h3>
            <input type="text" x-model="searchQuery" placeholder="Cari customer..." class="bg-gray-100 dark:bg-gray-700 border-0 rounded-lg px-3 py-1.5 text-sm w-64">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Kota</th>
                        <th class="px-4 py-3 text-left">Credit Limit</th>
                        <th class="px-4 py-3 text-left">Outstanding</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    <template x-for="c in filteredCustomers" :key="c.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-mono text-xs" x-text="c.kode"></td>
                            <td class="px-4 py-3" x-text="c.nama"></td>
                            <td class="px-4 py-3" x-text="c.kota"></td>
                            <td class="px-4 py-3" x-text="c.credit_limit"></td>
                            <td class="px-4 py-3" x-text="c.outstanding"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs" :class="c.status==='Aktif'?'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400':c.status==='Warning'?'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400':'bg-gray-100 dark:bg-gray-600/30 text-gray-500 dark:text-gray-400'" x-text="c.status"></span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="deleteCustomer(c.id)" class="text-gray-400 hover:text-red-500" title="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal: Tambah Customer --}}
    <div x-show="showModal" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/60" @click="showModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold">Tambah Customer Baru</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Isi data customer di bawah ini</p>
                </div>
                <button @click="showModal = false" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Customer <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.nama" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="PT. Nama Perusahaan">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kota <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.kota" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Jakarta">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Credit Limit (Miliar Rp)</label>
                        <input type="number" x-model="form.creditLimit" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                        <input type="email" x-model="form.email" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="email@perusahaan.com">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon</label>
                    <input type="tel" x-model="form.telepon" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="0812xxxxxxx">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat</label>
                    <textarea x-model="form.alamat" rows="2" class="w-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none" placeholder="Alamat lengkap"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                <button @click="showModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="saveCustomer()" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">Simpan Customer</button>
            </div>
        </div>
    </div>

    {{-- Modal: Import Customer --}}
    <div x-show="showImportModal" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/60" @click="showImportModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold">Import Data Customer</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Upload file Excel/CSV</p>
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
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-xs">Kode</span>
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-xs">Nama</span>
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-xs">Kota</span>
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-xs">Credit Limit</span>
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-xs">Email</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                <button @click="showImportModal = false; importFile = null" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors">Batal</button>
                <button @click="handleImport()" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">Import Sekarang</button>
            </div>
        </div>
    </div>
</div>
@endsection
