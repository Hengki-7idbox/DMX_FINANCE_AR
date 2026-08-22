@extends('layouts.app')
@section('content')
<div x-data="trackerPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">Collection Status Tracker</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pantau status penagihan dan riwayat aksi collection</p>
        </div>
        <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
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
@endsection

@section('scripts')
<script>
function trackerPage() {
    return { init() { this.$nextTick(() => lucide.createIcons()); } }
}
</script>
@endsection
