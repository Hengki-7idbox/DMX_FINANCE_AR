<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Dashboard" description="Overview of your accounts receivable" />
    </x-slot>

    {{-- KPI Cards --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Total Outstanding</p>
                    <p class="text-2xl font-bold text-foreground">Rp 125.500.000</p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center">
                    <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Overdue</p>
                    <p class="text-2xl font-bold text-destructive">Rp 45.200.000</p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-destructive/10 flex items-center justify-center">
                    <svg class="h-6 w-6 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Paid This Month</p>
                    <p class="text-2xl font-bold text-success">Rp 85.300.000</p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-success/10 flex items-center justify-center">
                    <svg class="h-6 w-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Unreconciled</p>
                    <p class="text-2xl font-bold text-warning">Rp 12.800.000</p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-warning/10 flex items-center justify-center">
                    <svg class="h-6 w-6 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                    <span class="text-sm text-muted-foreground">Current</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 bg-input rounded-full overflow-hidden">
                            <div class="h-full bg-success" style="width: 45%"></div>
                        </div>
                        <span class="text-sm font-medium text-foreground">Rp 56.250.000</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">1-30 Days</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 bg-input rounded-full overflow-hidden">
                            <div class="h-full bg-info" style="width: 25%"></div>
                        </div>
                        <span class="text-sm font-medium text-foreground">Rp 31.375.000</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">31-60 Days</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 bg-input rounded-full overflow-hidden">
                            <div class="h-full bg-warning" style="width: 15%"></div>
                        </div>
                        <span class="text-sm font-medium text-foreground">Rp 18.825.000</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">61-90 Days</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 bg-input rounded-full overflow-hidden">
                            <div class="h-full bg-primary/90" style="width: 10%"></div>
                        </div>
                        <span class="text-sm font-medium text-foreground">Rp 12.550.000</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">>90 Days</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 bg-input rounded-full overflow-hidden">
                            <div class="h-full bg-destructive" style="width: 5%"></div>
                        </div>
                        <span class="text-sm font-medium text-foreground">Rp 6.500.000</span>
                    </div>
                </div>
            </div>
        </x-card>

        {{-- Top Overdue Customers --}}
        <x-card title="Top Overdue" class="col-span-3">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-primary flex items-center justify-center">
                            <span class="text-sm font-medium text-primary-foreground">PT</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-foreground">PT ABC Indonesia</p>
                            <p class="text-xs text-muted-foreground">4 invoices</p>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-destructive">Rp 15.200.000</p>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-primary flex items-center justify-center">
                            <span class="text-sm font-medium text-primary-foreground">CV</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-foreground">CV XYZ Trading</p>
                            <p class="text-xs text-muted-foreground">2 invoices</p>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-destructive">Rp 12.500.000</p>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-primary flex items-center justify-center">
                            <span class="text-sm font-medium text-primary-foreground">PT</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-foreground">PT Maju Jaya</p>
                            <p class="text-xs text-muted-foreground">3 invoices</p>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-destructive">Rp 10.800.000</p>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-primary flex items-center justify-center">
                            <span class="text-sm font-medium text-primary-foreground">UD</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-foreground">UD Sejahtera</p>
                            <p class="text-xs text-muted-foreground">1 invoice</p>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-destructive">Rp 6.700.000</p>
                </div>
            </div>
        </x-card>
    </div>

    {{-- Recent Activity --}}
    <x-card title="Recent Activity">
        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <div class="h-9 w-9 rounded-full bg-success/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-foreground">Payment received from <strong>PT ABC Indonesia</strong></p>
                    <p class="text-xs text-muted-foreground">INV-001, INV-002 - Rp 15.000.000</p>
                </div>
                <span class="text-xs text-tertiary">2 jam lalu</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="h-9 w-9 rounded-full bg-info/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-foreground">New invoice created: <strong>INV-003</strong></p>
                    <p class="text-xs text-muted-foreground">CV XYZ Trading - Rp 8.500.000</p>
                </div>
                <span class="text-xs text-tertiary">5 jam lalu</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="h-9 w-9 rounded-full bg-warning/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-foreground">Collection call to <strong>PT Maju Jaya</strong></p>
                    <p class="text-xs text-muted-foreground">Promise to pay: 25 Sep 2026</p>
                </div>
                <span class="text-xs text-tertiary">1 hari lalu</span>
            </div>
        </div>
    </x-card>
</x-layouts.app>
