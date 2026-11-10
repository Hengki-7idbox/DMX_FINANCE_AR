<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Import Rekening Koran" description="Upload file CSV BCA, cocokkan dengan Baserow, lalu simpan" backUrl="{{ route('bank-transactions.index') }}" />
    </x-slot>

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    @if(! $prepared)
        <div class="grid gap-4 lg:grid-cols-3">
            <x-card class="lg:col-span-2" title="Upload File" description="Format CSV hasil download rekening koran BCA">
                <form method="POST" action="{{ route('bank-transactions.import.preview') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <x-form-group label="File Rekening Koran (CSV)" for="statement-file" required
                                  hint="Delimiter titik koma (;), maksimal 20 MB.">
                        <x-input id="statement-file" type="file" name="file" accept=".csv,.txt" required />
                    </x-form-group>

                    @error('file')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror

                    <div class="flex items-center gap-2">
                        <x-button type="submit">Upload &amp; Preview</x-button>
                        <x-button variant="outline" href="{{ route('bank-transactions.index') }}">Batal</x-button>
                    </div>
                </form>
            </x-card>

            <x-card title="Info" description="Bagaimana proses import bekerja">
                <ol class="list-decimal list-inside space-y-2 text-sm text-muted-foreground">
                    <li>File dibaca dan divalidasi: saldo awal, mutasi debet/kredit, dan saldo berjalan harus cocok.</li>
                    <li>Setiap baris dicocokkan dengan data Baserow pada tanggal yang sama untuk mengambil nomor dokumen internal/external.</li>
                    <li>Baris yang tidak ditemukan tetap diimport dengan status <span class="font-medium text-foreground">NOT_FOUND</span> dan bisa diisi manual.</li>
                    <li>Jika periode pernah diimport, sistem menampilkan perbandingan (baru / berubah / sama / hilang) beserta rekomendasi strategi.</li>
                </ol>
            </x-card>
        </div>
    @else
        @php
            $summary = $prepared['summary'];
            $diff = $prepared['diff'];
        @endphp

        {{-- Ringkasan file --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-4">
            <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">Rekening</p>
                <p class="text-lg font-semibold">{{ $prepared['bank_account_label'] }}</p>
                <p class="text-xs text-muted-foreground">{{ $prepared['file_name'] }}</p>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">Periode</p>
                <p class="text-lg font-semibold">
                    {{ $prepared['meta']['period_start'] ? \Carbon\Carbon::parse($prepared['meta']['period_start'])->format('d/m/Y') : '—' }}
                    s.d.
                    {{ $prepared['meta']['period_end'] ? \Carbon\Carbon::parse($prepared['meta']['period_end'])->format('d/m/Y') : '—' }}
                </p>
                <p class="text-xs text-muted-foreground">{{ number_format($summary['row_count'], 0, ',', '.') }} baris mutasi</p>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">Total Debet / Kredit</p>
                <p class="text-lg font-semibold">{{ number_format($summary['total_debit'], 2, ',', '.') }}</p>
                <p class="text-xs text-muted-foreground">{{ number_format($summary['total_credit'], 2, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">Saldo Awal / Akhir</p>
                <p class="text-lg font-semibold">{{ number_format((float) $summary['opening_balance'], 2, ',', '.') }}</p>
                <p class="text-xs text-muted-foreground">
                    Akhir {{ number_format((float) ($summary['computed_closing_balance'] ?? $summary['closing_balance']), 2, ',', '.') }}
                    @if(isset($summary['computed_closing_balance'], $summary['closing_balance'])
                        && abs((float) $summary['computed_closing_balance'] - (float) $summary['closing_balance']) > 0.01)
                        · footer file: {{ number_format((float) $summary['closing_balance'], 2, ',', '.') }}
                    @endif
                </p>
            </div>
        </div>

        <x-alert type="{{ $summary['validated'] ? 'success' : 'warning' }}" class="mb-4">
            <p class="font-medium">
                {{ $summary['validated'] ? 'Validasi file berhasil.' : 'Validasi file memiliki catatan.' }}
                {{ number_format($summary['debit_count'], 0, ',', '.') }} baris DB,
                {{ number_format($summary['credit_count'], 0, ',', '.') }} baris CR.
                Baserow: {{ $prepared['baserow_configured'] ? number_format($prepared['baserow_row_count'], 0, ',', '.') . ' baris pada tanggal terkait' : 'belum dikonfigurasi' }}.
            </p>
        </x-alert>

        {{-- Perbandingan --}}
        <x-card class="mb-4" title="Perbandingan dengan Data Tersimpan">
            <div class="grid gap-3 sm:grid-cols-4 mb-4">
                <div class="rounded-lg border border-border p-3">
                    <p class="text-xs text-muted-foreground">Baru</p>
                    <p class="text-xl font-semibold text-info">{{ number_format($diff['new'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-border p-3">
                    <p class="text-xs text-muted-foreground">Berubah</p>
                    <p class="text-xl font-semibold text-warning">{{ number_format($diff['changed'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-border p-3">
                    <p class="text-xs text-muted-foreground">Sama</p>
                    <p class="text-xl font-semibold text-success">{{ number_format($diff['unchanged'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-border p-3">
                    <p class="text-xs text-muted-foreground">Hilang dari file</p>
                    <p class="text-xl font-semibold text-destructive">{{ number_format($diff['missing'], 0, ',', '.') }}</p>
                </div>
            </div>

            <x-alert type="{{ $prepared['recommendation'] === 'replace' ? 'warning' : 'info' }}">
                <p class="font-medium">Rekomendasi: {{ $prepared['recommendation'] === 'replace' ? 'Replace periode' : 'Incremental' }}</p>
                <p class="text-sm">{{ $prepared['recommendation_reason'] }}</p>
            </x-alert>

            <form id="import-store-form" method="POST" action="{{ route('bank-transactions.import.store') }}" class="mt-4 space-y-3">
                @csrf

                <label class="flex items-start gap-3 rounded-lg border border-border p-3 cursor-pointer">
                    <input type="radio" name="strategy" value="incremental" class="mt-1"
                           @checked($prepared['recommendation'] === 'incremental')>
                    <span>
                        <span class="block font-medium">Incremental</span>
                        <span class="block text-sm text-muted-foreground">Tambah baris baru dan perbarui baris yang berubah. Catatan dan nomor dokumen manual dipertahankan.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-border p-3 cursor-pointer">
                    <input type="radio" name="strategy" value="replace" class="mt-1"
                           @checked($prepared['recommendation'] === 'replace')>
                    <span>
                        <span class="block font-medium">Replace periode</span>
                        <span class="block text-sm text-muted-foreground">Hapus seluruh transaksi pada periode ini lalu tulis ulang dari file. Catatan manual pada baris yang masih ada ikut terhapus.</span>
                    </span>
                </label>

                <div class="flex items-center gap-2" x-data>
                    <x-button type="button" @click="$dispatch('open-modal', 'confirm-import')">Import Sekarang</x-button>
                    <x-button variant="outline" href="{{ route('bank-transactions.import') }}">Upload Ulang</x-button>
                </div>
            </form>
        </x-card>

        {{-- Peringatan --}}
        @if(! empty($prepared['warnings']))
            <x-card class="mb-4" title="Peringatan" description="Tidak menghalangi import, namun perlu diperiksa">
                <ul class="list-disc list-inside space-y-1 text-sm text-muted-foreground">
                    @foreach($prepared['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        @if(! empty($prepared['errors']))
            <x-card class="mb-4" title="Error" description="Perlu diperbaiki sebelum import">
                <ul class="list-disc list-inside space-y-1 text-sm text-destructive">
                    @foreach($prepared['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        {{-- Preview baris --}}
        <x-card title="Preview Baris" description="{{ number_format(count($prepared['rows']), 0, ',', '.') }} baris siap diimport">
            <div class="relative w-full overflow-auto max-h-[560px]">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b border-border sticky top-0 bg-card z-10">
                        <tr>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground whitespace-nowrap">Tanggal</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground min-w-[280px]">Keterangan</th>
                            <th class="h-10 px-3 text-right font-medium text-muted-foreground whitespace-nowrap">Nilai</th>
                            <th class="h-10 px-3 text-center font-medium text-muted-foreground whitespace-nowrap">Posisi</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground whitespace-nowrap">No Dokumen</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground whitespace-nowrap">Internal / External</th>
                            <th class="h-10 px-3 text-center font-medium text-muted-foreground whitespace-nowrap">Baserow</th>
                            <th class="h-10 px-3 text-center font-medium text-muted-foreground whitespace-nowrap">Perubahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prepared['rows'] as $row)
                            <tr class="border-b border-border/60">
                                <td class="px-3 py-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($row['transaction_date'])->format('d/m/Y') }}</td>
                                <td class="px-3 py-2">
                                    {{ $row['description'] }}
                                    @if(! empty($row['changes']))
                                        <div class="mt-1 text-xs text-warning">
                                            @foreach($row['changes'] as $field => $change)
                                                <span class="mr-2">{{ $field }}: {{ is_numeric($change['old']) ? number_format((float) $change['old'], 2, ',', '.') : $change['old'] }}
                                                    &rarr; {{ is_numeric($change['new']) ? number_format((float) $change['new'], 2, ',', '.') : $change['new'] }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap font-mono">{{ number_format((float) $row['amount'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    <x-badge :type="$row['position'] === 'CR' ? 'success' : 'danger'">{{ $row['position'] }}</x-badge>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row['document_number'] ?: '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    <div>{{ $row['document_internal'] ?: '—' }}</div>
                                    <div class="text-muted-foreground">{{ $row['document_external'] ?: '—' }}</div>
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @php
                                        $statusType = match ($row['status']) {
                                            'MATCHED' => 'success',
                                            'AMBIGUOUS' => 'warning',
                                            default => 'danger',
                                        };
                                    @endphp
                                    <x-badge :type="$statusType">{{ $row['status'] }}</x-badge>
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @php
                                        $stateType = match ($row['state']) {
                                            'new' => 'info',
                                            'changed' => 'warning',
                                            default => 'success',
                                        };
                                        $stateLabel = match ($row['state']) {
                                            'new' => 'Baru',
                                            'changed' => 'Berubah',
                                            default => 'Sama',
                                        };
                                    @endphp
                                    <x-badge :type="$stateType">{{ $stateLabel }}</x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Modal konfirmasi --}}
        <x-modal id="confirm-import" title="Konfirmasi Import" description="Pastikan ringkasan berikut sudah benar.">
            <div class="space-y-2 text-sm">
                <p><span class="text-muted-foreground">Rekening:</span> {{ $prepared['bank_account_label'] }}</p>
                <p><span class="text-muted-foreground">Baris:</span> {{ number_format($summary['row_count'], 0, ',', '.') }}</p>
                <p><span class="text-muted-foreground">Baru / Berubah / Sama / Hilang:</span>
                    {{ $diff['new'] }} / {{ $diff['changed'] }} / {{ $diff['unchanged'] }} / {{ $diff['missing'] }}
                </p>
                <p class="text-muted-foreground">Import akan menulis ke database. Tindakan ini tidak bisa dibatalkan otomatis.</p>
            </div>

            <x-slot name="footer">
                <x-button variant="outline" type="button" @click="$dispatch('close-modal', 'confirm-import')">Batal</x-button>
                <x-button type="submit" form="import-store-form">Ya, Import</x-button>
            </x-slot>
        </x-modal>
    @endif
</x-layouts.app>
