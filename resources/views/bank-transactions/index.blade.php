<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Rekening Koran" description="Import dan verifikasi mutasi rekening koran BCA">
            <x-button variant="outline" x-data @click="$dispatch('open-modal', 'sync-baserow')">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Sync Baserow
            </x-button>
            <x-button href="{{ route('bank-transactions.import') }}">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import Rekening Koran
            </x-button>
        </x-page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    {{-- Ringkasan --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 mb-4">
        <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
            <p class="text-sm text-muted-foreground">Total Baris</p>
            <p class="text-2xl font-semibold">{{ number_format($stats['total'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
            <p class="text-sm text-muted-foreground">Cocok Baserow</p>
            <p class="text-2xl font-semibold text-success">{{ number_format($stats['matched'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
            <p class="text-sm text-muted-foreground">Belum Ditemukan</p>
            <p class="text-2xl font-semibold text-destructive">{{ number_format($stats['not_found'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
            <p class="text-sm text-muted-foreground">Total Kredit (CR)</p>
            <p class="text-2xl font-semibold">{{ number_format($stats['credit'], 2, ',', '.') }}</p>
        </div>
        <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
            <p class="text-sm text-muted-foreground">Total Debet (DB)</p>
            <p class="text-2xl font-semibold">{{ number_format($stats['debit'], 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <x-card class="mb-4">
        <form method="GET" action="{{ route('bank-transactions.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-7 items-end">
            <x-form-group label="Bank Account" for="filter-bank">
                <x-select id="filter-bank" name="bank_account_id">
                    <option value="">Semua rekening</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" @selected((string) ($filters['bank_account_id'] ?? '') === (string) $account->id)>
                            {{ $account->bank_name }} — {{ $account->account_number }}
                        </option>
                    @endforeach
                </x-select>
            </x-form-group>

            <x-form-group label="Tahun" for="filter-year">
                <x-select id="filter-year" name="period_year">
                    <option value="">Semua</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" @selected((string) ($filters['period_year'] ?? '') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </x-select>
            </x-form-group>

            <x-form-group label="Bulan" for="filter-month">
                <x-select id="filter-month" name="period_month">
                    <option value="">Semua</option>
                    @foreach(range(1, 12) as $month)
                        <option value="{{ $month }}" @selected((string) ($filters['period_month'] ?? '') === (string) $month)>
                            {{ \Carbon\Carbon::create()->month($month)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </x-select>
            </x-form-group>

            <x-form-group label="Posisi" for="filter-position">
                <x-select id="filter-position" name="position">
                    <option value="">Semua</option>
                    <option value="CR" @selected(($filters['position'] ?? '') === 'CR')>CR (Kredit)</option>
                    <option value="DB" @selected(($filters['position'] ?? '') === 'DB')>DB (Debet)</option>
                </x-select>
            </x-form-group>

            <x-form-group label="Status" for="filter-status">
                <x-select id="filter-status" name="status">
                    <option value="">Semua</option>
                    <option value="MATCHED" @selected(($filters['status'] ?? '') === 'MATCHED')>MATCHED</option>
                    <option value="NOT_FOUND" @selected(($filters['status'] ?? '') === 'NOT_FOUND')>NOT_FOUND</option>
                    <option value="AMBIGUOUS" @selected(($filters['status'] ?? '') === 'AMBIGUOUS')>AMBIGUOUS</option>
                </x-select>
            </x-form-group>

            <div class="xl:col-span-2">
                <x-form-group label="Pencarian" for="filter-q">
                    <x-input id="filter-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Keterangan / nomor dokumen / catatan" />
                </x-form-group>
            </div>

            <div class="flex items-center gap-2 md:col-span-2 xl:col-span-7">
                <x-button type="submit">Terapkan</x-button>
                <x-button variant="outline" href="{{ route('bank-transactions.index') }}">Reset</x-button>
            </div>
        </form>
    </x-card>

    <x-card>
        <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <span class="inline-block h-3 w-6 rounded border border-amber-500/40 bg-amber-500/25"></span>
            <span>Saldo dari Baserow (best-effort, bisa berbeda dari CSV) — akan diganti setelah rekening koran CSV periode tersebut diimport.</span>
        </div>
        <div class="relative w-full overflow-auto">
            <table class="w-full caption-bottom text-sm">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground whitespace-nowrap">Tanggal</th>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground whitespace-nowrap">No Dokumen</th>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground min-w-[280px]">Keterangan</th>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground whitespace-nowrap">Cabang</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground whitespace-nowrap">Nilai Rp.</th>
                        <th class="h-10 px-3 text-center font-medium text-muted-foreground whitespace-nowrap">Posisi</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground whitespace-nowrap">Saldo</th>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground whitespace-nowrap">No Dok Internal</th>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground whitespace-nowrap">No Dok External</th>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground min-w-[220px]">Catatan</th>
                        <th class="h-10 px-3 text-center font-medium text-muted-foreground whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr class="border-b border-border/60 hover:bg-accent/40">
                            <td class="px-3 py-2 whitespace-nowrap">{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <a href="{{ route('bank-transactions.show', $transaction) }}" class="text-info hover:underline">
                                    {{ $transaction->document_number ?: '—' }}
                                </a>
                            </td>
                            <td class="px-3 py-2">{{ $transaction->description }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $transaction->branch_code ?: '—' }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap font-mono">{{ number_format((float) $transaction->amount, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                <x-badge :type="$transaction->position === 'CR' ? 'success' : 'danger'">{{ $transaction->position }}</x-badge>
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap font-mono {{ $transaction->source === 'BASEROW' ? 'bg-amber-500/25' : '' }}"
                                @if($transaction->source === 'BASEROW') title="Saldo dari Baserow, bisa berbeda dari CSV" @endif>
                                {{ number_format((float) $transaction->balance, 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $transaction->document_internal ?: '—' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $transaction->document_external ?: '—' }}</td>
                            <td class="px-3 py-2">
                                <input type="text"
                                       value="{{ $transaction->note }}"
                                       placeholder="Tulis catatan..."
                                       data-inline-note
                                       data-url="{{ route('bank-transactions.fields', $transaction) }}"
                                       class="flex h-8 w-full min-w-[200px] rounded-md border border-border bg-background px-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            </td>
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                @php
                                    $statusType = match ($transaction->status) {
                                        'MATCHED' => 'success',
                                        'AMBIGUOUS' => 'warning',
                                        default => 'danger',
                                    };
                                @endphp
                                <x-badge :type="$statusType">{{ $transaction->status }}</x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-8 text-center text-muted-foreground">
                                Belum ada data. Silakan import rekening koran terlebih dahulu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal sync Baserow --}}
    <x-modal id="sync-baserow" title="Sync Data Baserow" description="Tarik baris rekening koran dari Baserow yang belum ada di database lokal." size="lg">
        <div x-data="syncBaserowPanel()"
             x-on:open-modal.window="if ($event.detail === 'sync-baserow') loadInfo()"
             class="space-y-4">

            <template x-if="error">
                <div class="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive" x-text="error"></div>
            </template>

            <template x-if="! done">
                <div class="space-y-4">
                    <div>
                        <label for="sync-account" class="mb-1 block text-sm font-medium">Rekening Tujuan</label>
                        <select id="sync-account" x-model="accountId" :disabled="running"
                                class="flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50">
                            <template x-for="account in accounts" :key="account.id">
                                <option :value="String(account.id)" x-text="account.label + ' (' + fmt(account.transaction_count) + ' baris)'"></option>
                            </template>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-medium">Rentang Data</p>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3">
                            <input type="radio" value="current" x-model="mode" :disabled="running" class="mt-1">
                            <span>
                                <span class="block font-medium">Bulan berjalan</span>
                                <span class="block text-sm text-muted-foreground" x-text="monthLabel(currentMonth)"></span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3">
                            <input type="radio" value="all" x-model="mode" :disabled="running" class="mt-1">
                            <span>
                                <span class="block font-medium">Semua data</span>
                                <span class="block text-sm text-muted-foreground" x-text="months.length ? (months.length + ' bulan: ' + monthLabel(months[0]) + ' s.d. ' + monthLabel(months[months.length - 1])) : 'Memuat…'"></span>
                            </span>
                        </label>
                    </div>

                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3">
                        <input type="checkbox" x-model="fillEmpty" :disabled="running" class="mt-1 h-4 w-4 rounded border-border text-primary">
                        <span>
                            <span class="block font-medium">Isi field yang masih kosong</span>
                            <span class="block text-sm text-muted-foreground">No Dokumen, No Dok Internal, No Dok External, dan Catatan pada baris yang sudah ada. Isian manual tidak akan ditimpa.</span>
                        </span>
                    </label>

                    <p class="text-xs text-muted-foreground">
                        <span x-show="loadingInfo">Memuat info Baserow…</span>
                        <span x-show="! loadingInfo && configured">Rentang Baserow: <span x-text="minDate || '—'"></span> s.d. <span x-text="maxDate || '—'"></span>. Sync berjalan per bulan dan tidak menghapus data.</span>
                        <span x-show="! loadingInfo && ! configured" class="text-destructive">Baserow belum dikonfigurasi.</span>
                    </p>

                    <div x-show="total > 0" x-cloak class="space-y-2">
                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                            <span x-text="running ? ('Memproses ' + currentLabel + '…') : 'Selesai'"></span>
                            <span x-text="doneCount + ' / ' + total + ' bulan · ' + progress + '%'"></span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full bg-primary transition-all duration-300" :style="'width: ' + progress + '%'"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                            <div class="rounded-md border border-border p-2">
                                <p class="text-muted-foreground">Baru</p>
                                <p class="text-lg font-semibold text-info" x-text="fmt(totals.inserted)"></p>
                            </div>
                            <div class="rounded-md border border-border p-2">
                                <p class="text-muted-foreground">Diperbarui</p>
                                <p class="text-lg font-semibold text-warning" x-text="fmt(totals.updated)"></p>
                            </div>
                            <div class="rounded-md border border-border p-2">
                                <p class="text-muted-foreground">Sama</p>
                                <p class="text-lg font-semibold text-success" x-text="fmt(totals.unchanged)"></p>
                            </div>
                            <div class="rounded-md border border-border p-2">
                                <p class="text-muted-foreground">Dilewati</p>
                                <p class="text-lg font-semibold text-muted-foreground" x-text="fmt(totals.invalid + totals.duplicate)"></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <x-button variant="outline" type="button" @click="$dispatch('close-modal', 'sync-baserow')" x-bind:disabled="running">Batal</x-button>
                        <x-button type="button" @click="start()" x-bind:disabled="running || loadingInfo || ! configured">
                            <span x-show="! running">Mulai Sync</span>
                            <span x-show="running" x-cloak>Memproses…</span>
                        </x-button>
                    </div>
                </div>
            </template>

            <template x-if="done">
                <div class="space-y-4">
                    <x-alert type="success">
                        <p class="font-medium">Sync selesai.</p>
                        <p class="text-sm" x-text="fmt(totals.fetched) + ' baris Baserow diproses: ' + fmt(totals.inserted) + ' baru, ' + fmt(totals.updated) + ' diperbarui, ' + fmt(totals.unchanged) + ' sudah sesuai.'"></p>
                    </x-alert>

                    <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                        <div class="rounded-lg border border-border p-3">
                            <p class="text-xs text-muted-foreground">Baru</p>
                            <p class="text-xl font-semibold text-info" x-text="fmt(totals.inserted)"></p>
                        </div>
                        <div class="rounded-lg border border-border p-3">
                            <p class="text-xs text-muted-foreground">Diperbarui</p>
                            <p class="text-xl font-semibold text-warning" x-text="fmt(totals.updated)"></p>
                        </div>
                        <div class="rounded-lg border border-border p-3">
                            <p class="text-xs text-muted-foreground">Sama</p>
                            <p class="text-xl font-semibold text-success" x-text="fmt(totals.unchanged)"></p>
                        </div>
                        <div class="rounded-lg border border-border p-3">
                            <p class="text-xs text-muted-foreground">Dilewati</p>
                            <p class="text-xl font-semibold text-muted-foreground" x-text="fmt(totals.invalid + totals.duplicate)"></p>
                        </div>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        Baris tanpa tanggal/posisi/keterangan/nominal/saldo dilewati:
                        <span x-text="fmt(totals.invalid)"></span> baris.
                        Duplikat identik dilewati: <span x-text="fmt(totals.duplicate)"></span> baris.
                        Tanggal dengan saldo perkiraan: <span x-text="fmt(totals.estimated)"></span>.
                    </p>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <x-button variant="outline" type="button" @click="$dispatch('close-modal', 'sync-baserow')">Tutup</x-button>
                        <x-button type="button" @click="window.location.reload()">Muat Ulang Halaman</x-button>
                    </div>
                </div>
            </template>
        </div>
    </x-modal>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            document.querySelectorAll('[data-inline-note]').forEach(function (input) {
                let original = input.value;

                const save = async function () {
                    if (input.value === original) {
                        return;
                    }

                    input.classList.remove('ring-2', 'ring-success', 'ring-destructive');
                    input.classList.add('opacity-60');

                    try {
                        const response = await fetch(input.dataset.url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({ note: input.value }),
                        });

                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }

                        original = input.value;
                        input.classList.add('ring-2', 'ring-success');
                    } catch (error) {
                        input.classList.add('ring-2', 'ring-destructive');
                    } finally {
                        input.classList.remove('opacity-60');
                        setTimeout(function () {
                            input.classList.remove('ring-2', 'ring-success', 'ring-destructive');
                        }, 1500);
                    }
                };

                input.addEventListener('blur', save);
                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        input.blur();
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('syncBaserowPanel', () => ({
                infoUrl: '{{ route('bank-transactions.sync.info') }}',
                monthUrl: '{{ route('bank-transactions.sync.month') }}',
                csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
                configured: true,
                loadingInfo: false,
                infoLoaded: false,
                running: false,
                done: false,
                error: null,
                mode: 'current',
                accountId: '',
                accounts: [],
                fillEmpty: true,
                months: [],
                currentMonth: '',
                minDate: null,
                maxDate: null,
                total: 0,
                doneCount: 0,
                progress: 0,
                currentLabel: '',
                totals: {
                    fetched: 0,
                    inserted: 0,
                    updated: 0,
                    unchanged: 0,
                    invalid: 0,
                    duplicate: 0,
                    estimated: 0,
                },

                async loadInfo() {
                    if (this.infoLoaded || this.loadingInfo) {
                        return;
                    }

                    this.loadingInfo = true;
                    this.error = null;

                    try {
                        const response = await fetch(this.infoUrl, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await response.json();

                        if (!response.ok || data.ok === false) {
                            throw new Error(data.message || ('HTTP ' + response.status));
                        }

                        this.configured = !!data.configured;
                        this.months = data.months || [];
                        this.currentMonth = data.current_month || '';
                        this.accounts = data.accounts || [];
                        this.minDate = data.min_date || null;
                        this.maxDate = data.max_date || null;

                        if (!this.accountId && data.default_account_id) {
                            this.accountId = String(data.default_account_id);
                        }

                        this.infoLoaded = true;
                    } catch (error) {
                        this.error = 'Gagal memuat info Baserow: ' + error.message;
                    } finally {
                        this.loadingInfo = false;
                    }
                },

                monthLabel(month) {
                    if (!month || !/^\d{4}-\d{2}$/.test(month)) {
                        return '—';
                    }

                    const [year, monthNumber] = month.split('-').map(Number);

                    return new Date(year, monthNumber - 1, 1)
                        .toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
                },

                fmt(value) {
                    return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
                },

                async start() {
                    if (this.running) {
                        return;
                    }

                    await this.loadInfo();

                    if (!this.configured) {
                        this.error = 'Baserow belum dikonfigurasi.';
                        return;
                    }

                    if (!this.accountId) {
                        this.error = 'Pilih rekening tujuan terlebih dahulu.';
                        return;
                    }

                    const target = this.mode === 'all'
                        ? this.months.slice()
                        : this.months.filter((month) => month === this.currentMonth);

                    if (target.length === 0) {
                        this.error = this.mode === 'all'
                            ? 'Tidak ada data Baserow untuk disinkronkan.'
                            : 'Baserow tidak memiliki data pada bulan berjalan (' + this.monthLabel(this.currentMonth) + ').';
                        return;
                    }

                    this.running = true;
                    this.done = false;
                    this.error = null;
                    this.total = target.length;
                    this.doneCount = 0;
                    this.progress = 0;
                    this.currentLabel = '';
                    this.totals = {
                        fetched: 0,
                        inserted: 0,
                        updated: 0,
                        unchanged: 0,
                        invalid: 0,
                        duplicate: 0,
                        estimated: 0,
                    };

                    for (let i = 0; i < target.length; i++) {
                        const month = target[i];
                        this.currentLabel = this.monthLabel(month);

                        try {
                            const response = await fetch(this.monthUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                },
                                body: JSON.stringify({
                                    bank_account_id: Number(this.accountId),
                                    month: month,
                                    fill_empty: this.fillEmpty,
                                }),
                            });

                            const data = await response.json();

                            if (!response.ok || data.ok === false) {
                                throw new Error(data.message || ('HTTP ' + response.status));
                            }

                            this.totals.fetched += Number(data.fetched) || 0;
                            this.totals.inserted += Number(data.inserted) || 0;
                            this.totals.updated += Number(data.updated) || 0;
                            this.totals.unchanged += Number(data.unchanged) || 0;
                            this.totals.invalid += Number(data.invalid) || 0;
                            this.totals.duplicate += Number(data.duplicate) || 0;
                            this.totals.estimated += Number(data.balance_estimated_dates) || 0;
                        } catch (error) {
                            this.error = 'Gagal sync ' + this.currentLabel + ': ' + error.message;
                            this.running = false;
                            return;
                        }

                        this.doneCount = i + 1;
                        this.progress = Math.round((this.doneCount / this.total) * 100);
                    }

                    this.running = false;
                    this.done = true;
                },
            }));
        });
    </script>
    @endpush
</x-layouts.app>
