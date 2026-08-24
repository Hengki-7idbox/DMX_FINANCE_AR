@extends('layouts.app')
@section('content')
<div x-data="trackerPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Collection Status Tracker</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pantau status penagihan dan riwayat aksi collection</p>
        </div>
        <button @click="openTrackerAction(trackers[0])" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Collection Action
        </button>
    </div>

    <!-- Status Filter -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-medium">Semua (89)</button>
        <button class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Open (34)</button>
        <button class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-600">In Progress (28)</button>
        <button class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Promise to Pay (12)</button>
        <button class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Escalated (8)</button>
        <button class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Settled (7)</button>
    </div>

    <!-- Tracker Cards -->
    <div class="space-y-4">
        <!-- Card 1 -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold">PT. Makmur Sejahtera</h3>
                            <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs font-medium">Escalated</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">INV-2026-0712 • Rp 156.800.000 • 93 hari tertunggak</p>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                            <span>Assigned: Budi Santoso</span>
                            <span>Last action: 2 hari lalu - Phone call, customer janji bayar 25 Agustus</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:flex-shrink-0">
                    <button class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-medium hover:bg-gray-200 dark:hover:bg-gray-600">View</button>
                    <button class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700">Add Action</button>
                </div>
            </div>
            <!-- Timeline -->
            <div class="mt-4 pl-14 border-t border-gray-200 dark:border-gray-700 pt-3">
                <div class="flex items-start gap-3 text-sm">
                    <span class="text-xs text-gray-400 whitespace-nowrap">20 Agt</span>
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 flex-shrink-0"></div>
                    <p>Phone call - Customer janji bayar 25 Agustus</p>
                </div>
                <div class="flex items-start gap-3 text-sm mt-2">
                    <span class="text-xs text-gray-400 whitespace-nowrap">15 Agt</span>
                    <div class="w-2 h-2 bg-yellow-500 rounded-full mt-1.5 flex-shrink-0"></div>
                    <p>Email reminder Level 3 terkirim</p>
                </div>
                <div class="flex items-start gap-3 text-sm mt-2">
                    <span class="text-xs text-gray-400 whitespace-nowrap">10 Agt</span>
                    <div class="w-2 h-2 bg-gray-400 rounded-full mt-1.5 flex-shrink-0"></div>
                    <p>Escalated ke Finance Manager</p>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <i data-lucide="clock" class="w-5 h-5 text-yellow-600"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold">PT. Sukses Abadi</h3>
                            <span class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-xs font-medium">Promise to Pay</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">INV-2026-0798 • Rp 89.200.000 • 52 hari tertunggak</p>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                            <span>Assigned: Siti Rahayu</span>
                            <span>Last action: 3 hari lalu - WhatsApp, customer akan bayar via transfer</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:flex-shrink-0">
                    <button class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-medium">View</button>
                    <button class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium">Add Action</button>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold">PT. Maju Jaya</h3>
                            <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-medium">Settled</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">INV-2026-0847 • Rp 45.500.000 • Dibayar tepat waktu</p>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                            <span>Assigned: Ahmad Fauzi</span>
                            <span>Settled: 20 Agustus 2026</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:flex-shrink-0">
                    <button class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-medium">View</button>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Modal: Add Tracker Action -->
    <div x-show="showTrackerActionModal" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" @click="showTrackerActionModal=false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-md">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                <div><h3 class="text-lg font-semibold">Tambah Aksi</h3><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Tambah aksi penagihan baru</p></div>
                <button @click="showTrackerActionModal=false" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-5 space-y-4">
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <p class="text-sm font-medium" x-text="trackerTarget?.customer"></p>
                    <p class="text-xs text-gray-500 mt-0.5"><span x-text="trackerTarget?.invoice"></span> • <span x-text="trackerTarget?.amount"></span></p>
                </div>
                <div><label class="block text-sm font-medium mb-1.5">Tipe Aksi</label>
                    <select x-model="trackerAction.type" class="w-full bg-gray-100 dark:bg-gray-700 border-0 rounded-lg px-3 py-2 text-sm">
                        <option value="phone">Phone Call</option><option value="email">Email</option><option value="visit">Kunjungan</option><option value="escalate">Escalation</option><option value="payment">Pembayaran Diterima</option>
                    </select>
                </div>
                <div><label class="block text-sm font-medium mb-1.5">Tanggal</label><input type="date" x-model="trackerAction.date" class="w-full bg-gray-100 dark:bg-gray-700 border-0 rounded-lg px-3 py-2 text-sm"></div>
                <div><label class="block text-sm font-medium mb-1.5">Catatan <span class="text-red-500">*</span></label><textarea x-model="trackerAction.note" rows="3" class="w-full bg-gray-100 dark:bg-gray-700 border-0 rounded-lg px-3 py-2 text-sm resize-none" placeholder="Deskripsi aksi..."></textarea></div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700">
                <button @click="showTrackerActionModal=false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium">Batal</button>
                <button @click="saveTrackerAction()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Simpan Aksi</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function trackerPage() {
    return {
        trackerFilter: 'all',
        showTrackerActionModal: false,
        trackerTarget: null,
        trackerAction: { type: 'phone', note: '', date: '' },
        trackers: [
            { id: 1, customer: 'PT. Makmur Sejahtera', invoice: 'INV-2026-0712', amount: 'Rp 156.800.000', days: 93, status: 'Escalated',
              actions: [
                  { date: '20 Agt', type: 'phone', text: 'Phone call - Customer janji bayar 25 Agt' },
                  { date: '15 Agt', type: 'email', text: 'Email reminder Level 3 terkirim' },
                  { date: '10 Agt', type: 'escalate', text: 'Escalated ke Finance Manager' },
              ]
            },
            { id: 2, customer: 'PT. Sukses Abadi', invoice: 'INV-2026-0798', amount: 'Rp 89.200.000', days: 52, status: 'In Progress',
              actions: [
                  { date: '18 Agt', type: 'phone', text: 'Phone call - Janji bayar minggu depan' },
                  { date: '12 Agt', type: 'email', text: 'Email reminder Level 2 terkirim' },
              ]
            },
            { id: 3, customer: 'PT. Maju Jaya', invoice: 'INV-2026-0847', amount: 'Rp 45.500.000', days: 7, status: 'Settled',
              actions: [
                  { date: '22 Agt', type: 'payment', text: 'Pembayaran diterima - LUNAS' },
              ]
            },
        ],
        get filteredTrackers() {
            if (this.trackerFilter === 'all') return this.trackers;
            return this.trackers.filter(t => t.status === this.trackerFilter);
        },
        trackerCount(filter) {
            if (filter === 'all') return this.trackers.length;
            return this.trackers.filter(t => t.status === filter).length;
        },
        openTrackerAction(tracker) {
            this.trackerTarget = tracker;
            this.trackerAction = { type: 'phone', note: '', date: new Date().toISOString().split('T')[0] };
            this.showTrackerActionModal = true;
        },
        saveTrackerAction() {
            if (!this.trackerAction.note) { showToast('Catatan harus diisi', 'error'); return; }
            const d = new Date();
            const dateStr = d.getDate() + ' ' + d.toLocaleString('id-ID', {month:'short'});
            const labels = { phone: 'Phone call', email: 'Email', escalate: 'Escalated', payment: 'Pembayaran', visit: 'Kunjungan' };
            this.trackerTarget.actions.unshift({ date: dateStr, type: this.trackerAction.type, text: (labels[this.trackerAction.type]||this.trackerAction.type) + ' - ' + this.trackerAction.note });
            this.showTrackerActionModal = false;
            showToast('Aksi berhasil ditambahkan!', 'success');
            this.$nextTick(() => lucide.createIcons());
        },
        init() { this.$nextTick(() => lucide.createIcons()); }
    }
}
</script>
@endsection
