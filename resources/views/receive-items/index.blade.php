<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Terima Barang" description="Import Receive item XML, cek nomor yang sudah ada, edit item, lalu kirim ke Accurate">
            <x-button variant="outline" onclick="openVendorModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Mapping Vendor
            </x-button>
            <x-button onclick="document.getElementById('import_modal').showModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import XML
            </x-button>
        </x-page-header>
    </x-slot>

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    @if($currentImport)
        <x-card class="mb-4">
            <div class="flex flex-col gap-3 pt-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-semibold text-foreground">{{ $currentImport->original_filename ?: 'Import XML' }}</h3>
                        @if($currentImport->duplicate_check_status === 'ok')
                            <x-badge type="success">Cek duplikat OK</x-badge>
                        @else
                            <x-badge type="danger">Cek duplikat gagal</x-badge>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ $currentImport->created_at?->format('d/m/Y H:i') }} &middot;
                        {{ number_format($currentImport->total_documents) }} dokumen,
                        {{ number_format($currentImport->total_lines) }} baris &middot;
                        {{ number_format($currentImport->new_documents) }} baru,
                        {{ number_format($currentImport->existing_documents) }} sudah ada
                    </p>
                    @if($currentImport->duplicate_check_status === 'failed')
                        <p class="mt-1 text-sm text-destructive">{{ $currentImport->duplicate_check_message }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-button variant="outline" onclick="checkDuplicates()">Cek Ulang Duplikat</x-button>
                    <x-button variant="destructive" onclick="document.getElementById('delete_import_modal').showModal()">Hapus Import</x-button>
                </div>
            </div>
        </x-card>
    @endif

    @if($currentImport)
        <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-6">
            @php
                $cards = [
                    ['key' => 'all', 'label' => 'Total', 'value' => $stats['total'], 'class' => 'text-foreground'],
                    ['key' => 'new', 'label' => 'Baru', 'value' => $stats['new'], 'class' => 'text-info'],
                    ['key' => 'exists', 'label' => 'Sudah Ada', 'value' => $stats['exists'], 'class' => 'text-muted-foreground'],
                    ['key' => 'submitted', 'label' => 'Terkirim', 'value' => $stats['submitted'], 'class' => 'text-success'],
                    ['key' => 'failed', 'label' => 'Gagal', 'value' => $stats['failed'], 'class' => 'text-destructive'],
                    ['key' => 'invalid', 'label' => 'Perlu Perbaikan', 'value' => $stats['invalid'] + $stats['blocked'], 'class' => 'text-warning'],
                ];
            @endphp
            @foreach($cards as $card)
                <a href="{{ route('receive-items.index', array_filter(['import' => $currentImport->id, 'status' => $card['key'] === 'all' ? null : $card['key'], 'q' => $search ?: null])) }}"
                   class="rounded-lg border border-border bg-card p-4 shadow-sm transition hover:bg-accent/40 {{ $status === $card['key'] || ($card['key'] === 'all' && $status === 'all') ? 'ring-2 ring-primary/60' : '' }}">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $card['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold {{ $card['class'] }}">{{ number_format($card['value']) }}</p>
                </a>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('receive-items.index') }}" class="mb-4 flex flex-wrap items-end gap-2">
        <div class="min-w-[220px] flex-1">
            <label for="q" class="mb-1 block text-xs font-medium text-muted-foreground">Cari nomor / vendor</label>
            <input type="text" id="q" name="q" value="{{ $search }}"
                   class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
        </div>
        <div class="min-w-[220px]">
            <label for="import" class="mb-1 block text-xs font-medium text-muted-foreground">File import</label>
            <select id="import" name="import"
                    class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                @foreach($imports as $import)
                    <option value="{{ $import->id }}" @selected($currentImport?->id === $import->id)>
                        {{ $import->original_filename ?: 'Import #' . $import->id }} — {{ $import->created_at?->format('d/m/Y H:i') }}
                    </option>
                @endforeach
            </select>
        </div>
        <input type="hidden" name="status" value="{{ $status }}" />
        <x-button type="submit">Terapkan</x-button>
    </form>

    <x-card>
        @if($currentImport && $drafts->count() > 0)
            <div class="flex flex-wrap items-center justify-between gap-3 pt-6">
                <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                    <input type="checkbox" id="check_all" class="h-4 w-4 rounded border-border" onchange="toggleAll(this)" />
                    Pilih semua dokumen yang bisa dikirim
                </label>
                <x-button onclick="submitSelected()">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Kirim ke Accurate
                </x-button>
            </div>

            <div class="mt-3 w-full overflow-x-auto">
                <table class="w-full min-w-[1100px] text-sm whitespace-nowrap">
                    <thead class="border-b border-border">
                        <tr>
                            <th class="h-10 w-10 px-2 text-left font-medium text-muted-foreground"></th>
                            <th class="h-10 px-2 text-left font-medium text-muted-foreground">No. Receive</th>
                            <th class="h-10 px-2 text-left font-medium text-muted-foreground">Tanggal</th>
                            <th class="h-10 px-2 text-left font-medium text-muted-foreground">Vendor</th>
                            <th class="h-10 px-2 text-right font-medium text-muted-foreground">Rate</th>
                            <th class="h-10 px-2 text-right font-medium text-muted-foreground">Total</th>
                            <th class="h-10 px-2 text-right font-medium text-muted-foreground">Baris</th>
                            <th class="h-10 px-2 text-left font-medium text-muted-foreground">Status</th>
                            <th class="h-10 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drafts as $draft)
                            @php
                                $badge = match ($draft->status) {
                                    'new' => 'info',
                                    'exists' => 'secondary',
                                    'submitted' => 'success',
                                    'failed' => 'danger',
                                    'invalid' => 'warning',
                                    default => 'secondary',
                                };
                                $label = match ($draft->status) {
                                    'new' => 'Baru',
                                    'exists' => 'Sudah Ada',
                                    'submitted' => 'Terkirim',
                                    'failed' => 'Gagal',
                                    'invalid' => 'Perlu Perbaikan',
                                    default => $draft->status,
                                };
                            @endphp
                            <tr class="border-b border-border transition-colors hover:bg-accent/40">
                                <td class="px-2 py-1.5">
                                    <input type="checkbox" class="draft-check h-4 w-4 rounded border-border"
                                           value="{{ $draft->id }}"
                                           data-can-submit="{{ $draft->canSubmit() ? '1' : '0' }}"
                                           @disabled(! $draft->canSubmit()) />
                                </td>
                                <td class="px-2 py-1.5">
                                    <button type="button" class="font-medium text-primary hover:underline" onclick="openDetail({{ $draft->id }})">
                                        {{ $draft->receive_number }}
                                    </button>
                                    @if($draft->accurate_number)
                                        <div class="text-xs text-muted-foreground">{{ $draft->accurate_number }}</div>
                                    @endif
                                </td>
                                <td class="px-2 py-1.5 text-muted-foreground">{{ $draft->trans_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-2 py-1.5">
                                    <div class="font-medium text-foreground">{{ $draft->vendor_internal ?: '-' }}</div>
                                    <div class="text-xs {{ $draft->vendor_external ? 'text-muted-foreground' : 'text-destructive' }}">
                                        {{ $draft->vendor_external ?: 'Belum dipetakan' }}
                                    </div>
                                </td>
                                <td class="px-2 py-1.5 text-right">
                                    <div class="font-medium text-foreground">{{ $draft->currency_code }}</div>
                                    <div class="text-xs text-muted-foreground">{{ number_format((float) $draft->rate, 2, ',', '.') }}</div>
                                </td>
                                <td class="px-2 py-1.5 text-right font-medium text-foreground">
                                    {{ number_format((float) $draft->total_amount, 2, ',', '.') }}
                                </td>
                                <td class="px-2 py-1.5 text-right">
                                    {{ number_format($draft->lines_count) }}
                                    @if($draft->blocked_lines_count > 0)
                                        <div class="text-xs text-destructive">{{ $draft->blocked_lines_count }} perlu perbaikan</div>
                                    @endif
                                </td>
                                <td class="px-2 py-1.5">
                                    <x-badge :type="$badge">{{ $label }}</x-badge>
                                    @if($draft->message)
                                        <div class="mt-1 max-w-[240px] truncate text-xs text-muted-foreground" title="{{ $draft->message }}">{{ $draft->message }}</div>
                                    @endif
                                </td>
                                <td class="px-2 py-1.5">
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium text-foreground hover:bg-accent" onclick="openDetail({{ $draft->id }})">
                                            Detail
                                        </button>
                                        @if($draft->canSubmit())
                                            <button type="button" class="rounded-md bg-primary px-2 py-1 text-xs font-medium text-primary-foreground hover:opacity-90" onclick="submitIds([{{ $draft->id }}])">
                                                Kirim
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $drafts->links() }}
            </div>
        @else
            <div class="py-12 text-center text-sm text-muted-foreground">
                @if($currentImport)
                    Tidak ada dokumen pada filter ini.
                @else
                    Belum ada data. Klik <strong>Import XML</strong> untuk mengunggah file Receive item.
                @endif
            </div>
        @endif
    </x-card>

    {{-- Import Modal --}}
    <dialog id="import_modal" class="modal">
        <div class="modal-box">
            <h3 class="text-lg font-bold text-foreground">Import Receive item XML</h3>
            <p class="py-2 text-sm text-muted-foreground">
                Unggah file XML hasil export Exim (<strong>Import Receive item.xml</strong>).
                Dokumen yang nomornya sudah ada di Accurate akan ditandai dan tidak dikirim ulang.
            </p>
            <form action="{{ route('receive-items.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="py-4">
                    <label for="file" class="mb-2 block text-sm font-medium text-muted-foreground">File XML</label>
                    <input type="file" id="file" name="file" accept=".xml,.txt" required
                           class="block w-full cursor-pointer text-sm text-muted-foreground file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90" />
                    @error('file')
                        <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Upload & Proses</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('import_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    {{-- Delete Import Modal --}}
    @if($currentImport)
        <dialog id="delete_import_modal" class="modal">
            <div class="modal-box">
                <h3 class="text-lg font-bold text-foreground">Hapus Data Import?</h3>
                <p class="py-3 text-sm text-muted-foreground">
                    Semua draft dari <strong>{{ $currentImport->original_filename ?: 'Import #' . $currentImport->id }}</strong>
                    ({{ number_format($currentImport->total_documents) }} dokumen) akan dihapus dari aplikasi.
                    Dokumen yang sudah terkirim ke Accurate tidak terpengaruh.
                </p>
                <div class="modal-action">
                    <form action="{{ route('receive-items.imports.destroy', $currentImport) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-error">Ya, Hapus</button>
                    </form>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('delete_import_modal').close()">Batal</button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>
    @endif

    {{-- Detail Draft Modal --}}
    <dialog id="detail_modal" class="modal">
        <div class="modal-box max-w-6xl">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 class="text-lg font-bold text-foreground" id="detail_title">Detail Dokumen</h3>
                    <p class="text-sm text-muted-foreground" id="detail_subtitle">-</p>
                </div>
                <div class="flex items-center gap-2">
                    <span id="detail_status"></span>
                    <button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('detail_modal').close()">Tutup</button>
                </div>
            </div>

            <div id="detail_alert" class="mt-3 hidden"></div>

            <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">No. Receive</label>
                    <input type="text" id="d_receive_number" readonly class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Tanggal</label>
                    <input type="date" id="d_trans_date" class="draft-field block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" data-field="trans_date" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Ship Date</label>
                    <input type="date" id="d_ship_date" class="draft-field block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" data-field="ship_date" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Term</label>
                    <input type="text" id="d_payment_term_name" class="draft-field block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" data-field="payment_term_name" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Vendor Internal</label>
                    <input type="text" id="d_vendor_internal" class="draft-field block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" data-field="vendor_internal" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Vendor Accurate</label>
                    <input type="text" id="d_vendor_external" class="draft-field block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" data-field="vendor_external" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Mata Uang</label>
                    <select id="d_currency_code" class="draft-field block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" data-field="currency_code">
                        <option value="IDR">IDR</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Rate</label>
                    <input type="number" step="0.000001" id="d_rate" class="draft-field block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" data-field="rate" />
                </div>
                <div class="col-span-2 md:col-span-4">
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Deskripsi</label>
                    <input type="text" id="d_description" class="draft-field block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" data-field="description" />
                </div>
            </div>

            <div class="mt-4 w-full overflow-x-auto">
                <table class="w-full min-w-[1200px] text-sm whitespace-nowrap">
                    <thead class="border-b border-border">
                        <tr>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">#</th>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">Item Internal</th>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">Item Accurate</th>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">Nama Item</th>
                            <th class="h-9 px-2 text-right font-medium text-muted-foreground">Qty XML</th>
                            <th class="h-9 px-2 text-right font-medium text-muted-foreground">Ratio</th>
                            <th class="h-9 px-2 text-right font-medium text-muted-foreground">Qty</th>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">Satuan</th>
                            <th class="h-9 px-2 text-right font-medium text-muted-foreground">Harga</th>
                            <th class="h-9 px-2 text-right font-medium text-muted-foreground">Disc %</th>
                            <th class="h-9 px-2 text-right font-medium text-muted-foreground">Total</th>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">Status</th>
                        </tr>
                    </thead>
                    <tbody id="detail_lines"></tbody>
                </table>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-border pt-3">
                <div class="text-sm text-muted-foreground">
                    Total tagihan: <strong class="text-foreground" id="detail_total">0</strong>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('detail_modal').close()">Tutup</button>
                    <button type="button" class="btn btn-sm btn-primary" id="detail_submit_btn" onclick="submitCurrentDraft()">Kirim Dokumen Ini</button>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    {{-- Progress Modal --}}
    <dialog id="progress_modal" class="modal">
        <div class="modal-box">
            <h3 class="text-lg font-bold text-foreground">Mengirim ke Accurate</h3>
            <p class="text-sm text-muted-foreground" id="progress_label">Menyiapkan...</p>
            <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-secondary">
                <div id="progress_bar" class="h-full bg-primary transition-all" style="width: 0%"></div>
            </div>
            <div id="progress_log" class="mt-3 max-h-64 overflow-y-auto rounded-lg border border-border bg-background p-3 text-xs"></div>
            <div class="modal-action">
                <button type="button" id="progress_close" class="btn btn-ghost" disabled onclick="location.reload()">Tutup</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    {{-- Vendor Mapping Modal --}}
    <dialog id="vendor_modal" class="modal">
        <div class="modal-box max-w-5xl">
            <h3 class="text-lg font-bold text-foreground">Mapping Vendor</h3>
            <p class="py-1 text-sm text-muted-foreground">
                Kode vendor internal (XML) &rarr; kode vendor Accurate. Perubahan langsung dipakai pada draft yang belum terkirim.
            </p>

            <div class="mt-2 max-h-[50vh] overflow-y-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="border-b border-border">
                        <tr>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">Internal</th>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">External (Accurate)</th>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">Nama Vendor</th>
                            <th class="h-9 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="vendor_rows">
                        @forelse($vendors as $vendor)
                            <tr data-vendor-id="{{ $vendor->id }}" class="border-b border-border">
                                <td class="px-2 py-1">
                                    <input type="text" data-vendor-field="kode_vendor_internal" value="{{ $vendor->kode_vendor_internal }}"
                                           class="block w-full rounded-md border border-border bg-input px-2 py-1 text-sm text-foreground" />
                                </td>
                                <td class="px-2 py-1">
                                    <input type="text" data-vendor-field="kode_vendor_external" value="{{ $vendor->kode_vendor_external }}"
                                           placeholder="V.IMPORT-001"
                                           class="block w-full rounded-md border border-border bg-input px-2 py-1 text-sm text-foreground" />
                                </td>
                                <td class="px-2 py-1">
                                    <input type="text" data-vendor-field="nama_vendor_external" value="{{ $vendor->nama_vendor_external }}"
                                           class="block w-full rounded-md border border-border bg-input px-2 py-1 text-sm text-foreground" />
                                </td>
                                <td class="px-2 py-1">
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="rounded-md bg-primary px-2 py-1 text-xs font-medium text-primary-foreground" onclick="saveVendor({{ $vendor->id }})">Simpan</button>
                                        <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium text-destructive" onclick="deleteVendor({{ $vendor->id }})">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-2 py-4 text-center text-sm text-muted-foreground">Belum ada mapping vendor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 rounded-lg border border-border p-3">
                <p class="mb-2 text-sm font-semibold text-foreground">Tambah / Update Cepat</p>
                <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
                    <input type="text" id="new_vendor_internal" placeholder="V.CHINA-002"
                           class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" />
                    <input type="text" id="new_vendor_external" placeholder="V.IMPORT-001"
                           class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" />
                    <input type="text" id="new_vendor_name" placeholder="Nama vendor Accurate"
                           class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground" />
                    <button type="button" class="rounded-lg bg-primary px-3 py-2 text-sm font-medium text-primary-foreground" onclick="createVendor()">Simpan</button>
                </div>
            </div>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('vendor_modal').close()">Tutup</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    @push('scripts')
    <script>
        const CSRF = @json(csrf_token());
        const CURRENT_IMPORT_ID = @json($currentImport?->id);
        const ROUTES = {
            lines: @json(route('receive-items.lines', ['draft' => '__ID__'])),
            draft: @json(route('receive-items.drafts.update', ['draft' => '__ID__'])),
            line: @json(route('receive-items.lines.update', ['line' => '__ID__'])),
            lineDelete: @json(route('receive-items.lines.destroy', ['line' => '__ID__'])),
            submit: @json(route('receive-items.submit')),
            check: @json(route('receive-items.check', ['import' => '__ID__'])),
            vendorStore: @json(route('receive-items.vendors.store')),
            vendorUpdate: @json(route('receive-items.vendors.update', ['vendor' => '__ID__'])),
            vendorDelete: @json(route('receive-items.vendors.destroy', ['vendor' => '__ID__'])),
        };

        let currentDraft = null;
        let currentLines = [];
        let currentCanSubmit = false;

        function url(template, id) {
            return template.replace('__ID__', id);
        }

        function esc(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function num(value) {
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));
        }

        async function requestJson(url, method, body) {
            const options = {
                method: method,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF,
                },
            };

            if (body !== undefined) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(body);
            }

            const response = await fetch(url, options);
            let payload = {};

            try {
                payload = await response.json();
            } catch (e) {
                payload = {};
            }

            if (!response.ok) {
                let message = payload.message || ('Gagal memproses (HTTP ' + response.status + ')');
                if (payload.errors) {
                    const first = Object.keys(payload.errors)[0];
                    if (first) message = payload.errors[first][0];
                }
                throw new Error(message);
            }

            return payload;
        }

        function toast(type, message) {
            const box = document.createElement('div');
            box.className = 'fixed bottom-4 right-4 z-[120] max-w-sm rounded-lg border px-4 py-3 text-sm font-medium shadow-xl ' +
                (type === 'success'
                    ? 'border-success/40 bg-success/10 text-success'
                    : 'border-destructive/40 bg-destructive/10 text-destructive');
            box.textContent = message;
            document.body.appendChild(box);
            setTimeout(() => box.remove(), type === 'success' ? 2500 : 6000);
        }

        function openVendorModal() {
            document.getElementById('vendor_modal').showModal();
        }

        async function openDetail(id) {
            try {
                const data = await requestJson(url(ROUTES.lines, id), 'GET');
                currentDraft = data.draft;
                currentLines = data.lines || [];
                currentCanSubmit = !!data.can_submit;
                renderDraft();
                renderLines();
                document.getElementById('detail_modal').showModal();
            } catch (e) {
                toast('error', e.message);
            }
        }

        function renderDraft() {
            const draft = currentDraft;
            if (!draft) return;

            document.getElementById('detail_title').textContent = draft.receive_number;
            document.getElementById('detail_subtitle').textContent =
                (draft.vendor_internal || '-') + ' → ' + (draft.vendor_external || 'belum dipetakan') +
                ' • ' + (draft.currency_code || '-') + ' rate ' + num(draft.rate);
            document.getElementById('detail_status').innerHTML = statusBadge(draft.status);

            document.getElementById('d_receive_number').value = draft.receive_number || '';
            document.getElementById('d_trans_date').value = draft.trans_date || '';
            document.getElementById('d_ship_date').value = draft.ship_date || '';
            document.getElementById('d_payment_term_name').value = draft.payment_term_name || '';
            document.getElementById('d_vendor_internal').value = draft.vendor_internal || '';
            document.getElementById('d_vendor_external').value = draft.vendor_external || '';
            document.getElementById('d_currency_code').value = draft.currency_code || 'IDR';
            document.getElementById('d_rate').value = draft.rate ?? 1;
            document.getElementById('d_description').value = draft.description || '';

            const readOnly = !!draft.is_read_only;
            document.querySelectorAll('.draft-field').forEach(el => {
                el.disabled = readOnly;
            });

            const alertBox = document.getElementById('detail_alert');
            if (draft.message) {
                alertBox.className = 'mt-3 rounded-lg border border-warning/40 bg-warning/10 px-3 py-2 text-sm text-warning';
                alertBox.textContent = draft.message;
            } else {
                alertBox.className = 'mt-3 hidden';
                alertBox.textContent = '';
            }

            document.getElementById('detail_submit_btn').disabled = readOnly || !currentCanSubmit;
        }

        function statusBadge(status) {
            const map = {
                new: ['bg-info/10 text-info', 'Baru'],
                exists: ['bg-secondary text-secondary-foreground', 'Sudah Ada'],
                submitted: ['bg-success/10 text-success', 'Terkirim'],
                failed: ['bg-destructive/10 text-destructive', 'Gagal'],
                invalid: ['bg-warning/10 text-warning', 'Perlu Perbaikan'],
            };
            const item = map[status] || ['bg-secondary text-secondary-foreground', status || '-'];
            return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' + item[0] + '">' + esc(item[1]) + '</span>';
        }

        function renderLines() {
            const tbody = document.getElementById('detail_lines');
            const readOnly = !!(currentDraft && currentDraft.is_read_only);
            let total = 0;

            if (!currentLines.length) {
                tbody.innerHTML = '<tr><td colspan="13" class="px-2 py-6 text-center text-sm text-muted-foreground">Tidak ada baris item.</td></tr>';
                document.getElementById('detail_total').textContent = num(0);
                return;
            }

            tbody.innerHTML = currentLines.map(line => {
                total += Number(line.line_total || 0);
                const blocked = line.status === 'blocked';
                const statusCell = blocked
                    ? '<span class="text-xs font-medium text-destructive">' + esc(line.message || 'Perlu perbaikan') + '</span>'
                    : '<span class="text-xs font-medium text-success">OK</span>';

                const input = (field, value, attrs = '') =>
                    '<input type="text" data-line-id="' + line.id + '" data-line-field="' + field + '" value="' + esc(value ?? '') + '" ' +
                    (readOnly ? 'disabled ' : '') + attrs +
                    ' class="block w-full rounded-md border border-border bg-input px-2 py-1 text-sm text-foreground ' + (blocked ? 'border-destructive/60' : '') + '" />';

                const numberInput = (field, value, step) =>
                    '<input type="number" step="' + step + '" data-line-id="' + line.id + '" data-line-field="' + field + '" value="' + esc(value ?? '') + '" ' +
                    (readOnly ? 'disabled ' : '') +
                    ' class="block w-24 rounded-md border border-border bg-input px-2 py-1 text-right text-sm text-foreground" />';

                return '<tr class="border-b border-border align-top" data-line-row="' + line.id + '">' +
                    '<td class="px-2 py-1.5">' +
                        (readOnly
                            ? '<span class="text-xs text-muted-foreground">read-only</span>'
                            : '<button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium text-destructive hover:bg-destructive/10" title="Hapus baris" onclick="removeLine(' + line.id + ')">Hapus</button>') +
                    '</td>' +
                    '<td class="px-2 py-1.5 text-muted-foreground">' + esc(line.line_no) + '</td>' +
                    '<td class="px-2 py-1.5 font-medium text-foreground">' + esc(line.item_internal || '-') + '</td>' +
                    '<td class="px-2 py-1.5">' + input('item_external', line.item_external, 'placeholder="Kode item Accurate"') + '</td>' +
                    '<td class="px-2 py-1.5 max-w-[220px] whitespace-normal text-muted-foreground">' + esc(line.item_name || line.description || '-') + '</td>' +
                    '<td class="px-2 py-1.5 text-right text-muted-foreground">' + esc(line.quantity_xml) + ' ' + esc(line.unit_xml || '') + '</td>' +
                    '<td class="px-2 py-1.5 text-right">' + numberInput('unit_ratio', line.unit_ratio, '0.000001') + '</td>' +
                    '<td class="px-2 py-1.5 text-right">' + numberInput('quantity', line.quantity, '0.000001') + '</td>' +
                    '<td class="px-2 py-1.5">' + input('unit_name', line.unit_name, 'placeholder="Satuan"') + '</td>' +
                    '<td class="px-2 py-1.5 text-right">' + numberInput('unit_price', line.unit_price, '0.000001') + '</td>' +
                    '<td class="px-2 py-1.5 text-right">' + numberInput('disc_percent', line.disc_percent, '0.01') + '</td>' +
                    '<td class="px-2 py-1.5 text-right font-medium text-foreground">' + num(line.line_total) + '</td>' +
                    '<td class="px-2 py-1.5 max-w-[220px] whitespace-normal">' + statusCell + '</td>' +
                '</tr>';
            }).join('');

            document.getElementById('detail_total').textContent = num(total);
            document.getElementById('detail_submit_btn').disabled = readOnly || !currentCanSubmit;
        }

        document.addEventListener('change', function (event) {
            const input = event.target.closest('[data-line-field]');
            if (!input) return;
            saveLineField(Number(input.dataset.lineId), input.dataset.lineField, input.value, input);
        });

        document.addEventListener('change', function (event) {
            const input = event.target.closest('.draft-field');
            if (!input) return;
            saveDraftField(input.dataset.field, input.value);
        });

        async function saveLineField(lineId, field, value, inputEl) {
            inputEl.disabled = true;
            try {
                const data = await requestJson(url(ROUTES.line, lineId), 'PUT', { [field]: value });

                if (Array.isArray(data.lines)) {
                    currentLines = data.lines;
                } else {
                    const index = currentLines.findIndex(line => Number(line.id) === lineId);
                    if (index >= 0) currentLines[index] = data.line;
                }

                currentCanSubmit = !!data.draft_can_submit;
                renderLines();
                toast('success', data.rebalanced
                    ? 'Qty diubah, harga baris lain disesuaikan agar total tagihan tetap sama'
                    : 'Baris tersimpan');
            } catch (e) {
                toast('error', e.message);
                inputEl.disabled = false;
            }
        }

        async function removeLine(lineId) {
            if (!confirm('Hapus baris ini dari draft? Harga baris lain akan disesuaikan agar total tagihan tetap sama.')) return;

            try {
                const data = await requestJson(url(ROUTES.lineDelete, lineId), 'DELETE');

                if (Array.isArray(data.lines)) {
                    currentLines = data.lines;
                } else {
                    currentLines = currentLines.filter(line => Number(line.id) !== lineId);
                }

                currentCanSubmit = !!data.draft_can_submit;
                renderLines();

                if (data.message) {
                    toast('error', data.message);
                } else {
                    toast('success', 'Baris dihapus, total tagihan tetap sama');
                }
            } catch (e) {
                toast('error', e.message);
            }
        }

        async function saveDraftField(field, value) {
            if (!currentDraft) return;

            try {
                const data = await requestJson(url(ROUTES.draft, currentDraft.id), 'PUT', { [field]: value });
                currentDraft = data.draft;
                currentCanSubmit = !!data.can_submit;
                renderDraft();
                toast('success', 'Dokumen tersimpan');
            } catch (e) {
                toast('error', e.message);
                renderDraft();
            }
        }

        function toggleAll(checkbox) {
            document.querySelectorAll('.draft-check').forEach(cb => {
                if (!cb.disabled) cb.checked = checkbox.checked;
            });
        }

        function submitSelected() {
            const ids = Array.from(document.querySelectorAll('.draft-check:checked'))
                .map(cb => Number(cb.value))
                .filter(id => id > 0);

            if (!ids.length) {
                toast('error', 'Pilih minimal satu dokumen yang bisa dikirim.');
                return;
            }

            submitIds(ids);
        }

        function submitCurrentDraft() {
            if (!currentDraft || !currentCanSubmit) return;
            document.getElementById('detail_modal').close();
            submitIds([currentDraft.id]);
        }

        async function submitIds(ids) {
            const modal = document.getElementById('progress_modal');
            const bar = document.getElementById('progress_bar');
            const label = document.getElementById('progress_label');
            const log = document.getElementById('progress_log');
            const closeBtn = document.getElementById('progress_close');

            bar.style.width = '0%';
            log.innerHTML = '';
            closeBtn.disabled = true;
            label.textContent = 'Menyiapkan ' + ids.length + ' dokumen...';
            modal.showModal();

            let done = 0;
            let ok = 0;
            let failed = 0;
            let skipped = 0;

            const append = (text, type) => {
                const row = document.createElement('div');
                row.className = type === 'ok' ? 'text-success' : (type === 'skip' ? 'text-warning' : 'text-destructive');
                row.textContent = text;
                log.appendChild(row);
                log.scrollTop = log.scrollHeight;
            };

            for (let i = 0; i < ids.length; i += 12) {
                const batch = ids.slice(i, i + 12);
                label.textContent = 'Mengirim ' + (i + 1) + ' - ' + Math.min(i + 12, ids.length) + ' dari ' + ids.length + ' dokumen...';

                try {
                    const data = await requestJson(ROUTES.submit, 'POST', { ids: batch });

                    if (data.message) append(data.message, 'fail');

                    (data.results || []).forEach(result => {
                        done++;
                        if (result.ok) {
                            ok++;
                            append('OK  ' + result.receive_number + (result.accurate_number ? ' → ' + result.accurate_number : ''), 'ok');
                        } else {
                            failed++;
                            append('GAGAL  ' + result.receive_number + ': ' + (result.message || '-'), 'fail');
                        }
                    });

                    (data.skipped || []).forEach(result => {
                        done++;
                        skipped++;
                        append('LEWATI  ' + result.receive_number + ': ' + (result.message || '-'), 'skip');
                    });

                    if (!data.ok && !(data.results || []).length && !(data.skipped || []).length) {
                        failed += batch.length;
                        done += batch.length;
                    }
                } catch (e) {
                    failed += batch.length;
                    done += batch.length;
                    append('ERROR batch ' + (i + 1) + ': ' + e.message, 'fail');
                }

                bar.style.width = Math.round((done / ids.length) * 100) + '%';
            }

            label.textContent = 'Selesai: ' + ok + ' terkirim, ' + failed + ' gagal, ' + skipped + ' dilewati.';
            closeBtn.disabled = false;
        }

        async function checkDuplicates() {
            if (!CURRENT_IMPORT_ID) return;

            try {
                const data = await requestJson(url(ROUTES.check, CURRENT_IMPORT_ID), 'POST');
                if (data.ok) {
                    toast('success', 'Cek duplikat selesai: ' + data.existing + ' sudah ada, ' + data.new + ' baru.');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toast('error', data.message || 'Gagal cek duplikat.');
                }
            } catch (e) {
                toast('error', e.message);
            }
        }

        async function saveVendor(id) {
            const row = document.querySelector('[data-vendor-id="' + id + '"]');
            if (!row) return;

            const body = {};
            row.querySelectorAll('[data-vendor-field]').forEach(input => {
                body[input.dataset.vendorField] = input.value;
            });

            try {
                await requestJson(url(ROUTES.vendorUpdate, id), 'PUT', body);
                toast('success', 'Mapping vendor tersimpan');
                setTimeout(() => location.reload(), 900);
            } catch (e) {
                toast('error', e.message);
            }
        }

        async function createVendor() {
            const body = {
                kode_vendor_internal: document.getElementById('new_vendor_internal').value,
                kode_vendor_external: document.getElementById('new_vendor_external').value,
                nama_vendor_external: document.getElementById('new_vendor_name').value,
            };

            if (!body.kode_vendor_internal) {
                toast('error', 'Kode vendor internal wajib diisi.');
                return;
            }

            try {
                await requestJson(ROUTES.vendorStore, 'POST', body);
                toast('success', 'Mapping vendor tersimpan');
                setTimeout(() => location.reload(), 900);
            } catch (e) {
                toast('error', e.message);
            }
        }

        async function deleteVendor(id) {
            if (!confirm('Hapus mapping vendor ini?')) return;

            try {
                await requestJson(url(ROUTES.vendorDelete, id), 'DELETE');
                toast('success', 'Mapping vendor dihapus');
                setTimeout(() => location.reload(), 900);
            } catch (e) {
                toast('error', e.message);
            }
        }
    </script>
    @endpush
</x-layouts.app>
