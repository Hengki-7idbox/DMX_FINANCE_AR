<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Rekening Koran" description="Import dan verifikasi mutasi rekening koran BCA">
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
                            <td class="px-3 py-2 text-right whitespace-nowrap font-mono">{{ number_format((float) $transaction->balance, 2, ',', '.') }}</td>
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
    @endpush
</x-layouts.app>
