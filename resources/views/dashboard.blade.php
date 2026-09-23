<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Dashboard" description="Overview of your accounts receivable" />
    </x-slot>

    {{-- KPI Cards --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#b0b8c4]">Total Outstanding</p>
                    <p class="text-2xl font-bold text-white">Rp 125.500.000</p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-[#e8c493]/10 flex items-center justify-center">
                    <svg class="h-6 w-6 text-[#e8c493]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#b0b8c4]">Overdue</p>
                    <p class="text-2xl font-bold text-[#ef4444]">Rp 45.200.000</p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-[#ef4444]/10 flex items-center justify-center">
                    <svg class="h-6 w-6 text-[#ef4444]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#b0b8c4]">Paid This Month</p>
                    <p class="text-2xl font-bold text-[#10b981]">Rp 85.300.000</p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-[#10b981]/10 flex items-center justify-center">
                    <svg class="h-6 w-6 text-[#10b981]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#b0b8c4]">Unreconciled</p>
                    <p class="text-2xl font-bold text-[#f5b041]">Rp 12.800.000</p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-[#f5b041]/10 flex items-center justify-center">
                    <svg class="h-6 w-6 text-[#f5b041]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
            </div>
        </x-card>
    </div>

    <div class="grid gap-4 lg:grid-cols-7 mb-6">
        {{-- Aging Summary --}}
        <x-card title="Aging Summary" class="col-span-4">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-[#b0b8c4]">Current</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 bg-[#3a4556] rounded-full overflow-hidden">
                            <div class="h-full bg-[#10b981]" style="width: 45%"></div>
                        </div>
                        <span class="text-sm font-medium text-white">Rp 56.250.000</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-[#b0b8c4]">1-30 Days</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 bg-[#3a4556] rounded-full overflow-hidden">
                            <div class="h-full bg-[#1dd7d7]" style="width: 25%"></div>
                        </div>
                        <span class="text-sm font-medium text-white">Rp 31.375.000</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-[#b0b8c4]">31-60 Days</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 bg-[#3a4556] rounded-full overflow-hidden">
                            <div class="h-full bg-[#f5b041]" style="width: 15%"></div>
                        </div>
                        <span class="text-sm font-medium text-white">Rp 18.825.000</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-[#b0b8c4]">61-90 Days</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 bg-[#3a4556] rounded-full overflow-hidden">
                            <div class="h-full bg-[#d4a574]" style="width: 10%"></div>
                        </div>
                        <span class="text-sm font-medium text-white">Rp 12.550.000</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-[#b0b8c4]">>90 Days</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 bg-[#3a4556] rounded-full overflow-hidden">
                            <div class="h-full bg-[#ef4444]" style="width: 5%"></div>
                        </div>
                        <span class="text-sm font-medium text-white">Rp 6.500.000</span>
                    </div>
                </div>
            </div>
        </x-card>

        {{-- Top Overdue Customers --}}
        <x-card title="Top Overdue" class="col-span-3">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-[#e8c493] flex items-center justify-center">
                            <span class="text-sm font-medium text-[#1a1f2e]">PT</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">PT ABC Indonesia</p>
                            <p class="text-xs text-[#b0b8c4]">4 invoices</p>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-[#ef4444]">Rp 15.200.000</p>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-[#e8c493] flex items-center justify-center">
                            <span class="text-sm font-medium text-[#1a1f2e]">CV</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">CV XYZ Trading</p>
                            <p class="text-xs text-[#b0b8c4]">2 invoices</p>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-[#ef4444]">Rp 12.500.000</p>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-[#e8c493] flex items-center justify-center">
                            <span class="text-sm font-medium text-[#1a1f2e]">PT</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">PT Maju Jaya</p>
                            <p class="text-xs text-[#b0b8c4]">3 invoices</p>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-[#ef4444]">Rp 10.800.000</p>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-[#e8c493] flex items-center justify-center">
                            <span class="text-sm font-medium text-[#1a1f2e]">UD</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">UD Sejahtera</p>
                            <p class="text-xs text-[#b0b8c4]">1 invoice</p>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-[#ef4444]">Rp 6.700.000</p>
                </div>
            </div>
        </x-card>
    </div>

    {{-- Recent Activity --}}
    <x-card title="Recent Activity">
        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <div class="h-9 w-9 rounded-full bg-[#10b981]/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-[#10b981]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-white">Payment received from <strong>PT ABC Indonesia</strong></p>
                    <p class="text-xs text-[#b0b8c4]">INV-001, INV-002 - Rp 15.000.000</p>
                </div>
                <span class="text-xs text-[#7a8494]">2 jam lalu</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="h-9 w-9 rounded-full bg-[#1dd7d7]/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-[#1dd7d7]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-white">New invoice created: <strong>INV-003</strong></p>
                    <p class="text-xs text-[#b0b8c4]">CV XYZ Trading - Rp 8.500.000</p>
                </div>
                <span class="text-xs text-[#7a8494]">5 jam lalu</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="h-9 w-9 rounded-full bg-[#f5b041]/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-[#f5b041]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-white">Collection call to <strong>PT Maju Jaya</strong></p>
                    <p class="text-xs text-[#b0b8c4]">Promise to pay: 25 Sep 2026</p>
                </div>
                <span class="text-xs text-[#7a8494]">1 hari lalu</span>
            </div>
        </div>
    </x-card>
</x-layouts.app>
