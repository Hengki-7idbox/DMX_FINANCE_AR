<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="{{ $pageTitle ?? 'Detail Sales' }}" description="{{ $pageDescription ?? 'Detail transaksi sales per invoice' }}">
            <x-button onclick="document.getElementById('import_sales_modal').showModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import Detail Sales
            </x-button>
            <x-button onclick="document.getElementById('import_items_modal').showModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Import Detail Product Sales
            </x-button>
            <x-button variant="destructive" onclick="document.getElementById('clear_modal').showModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Clear Data
            </x-button>
        </x-page-header>
    </x-slot>

    {{-- Tabs External / Internal --}}
    <div class="mb-4 flex gap-1 rounded-lg border border-border bg-card p-1 w-fit">
        <a href="{{ route($tabRoute ?? 'detail-sales.index', array_filter(['tab' => 'external', 'bulan' => request('bulan'), 'tahun' => request('tahun')], fn ($v) => $v !== null)) }}"
           class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'external' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground' }}">
            External
        </a>
        <a href="{{ route($tabRoute ?? 'detail-sales.index', array_filter(['tab' => 'internal', 'bulan' => request('bulan'), 'tahun' => request('tahun')], fn ($v) => $v !== null)) }}"
           class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'internal' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground' }}">
            Internal
        </a>
    </div>

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-alert type="info" class="mb-4">
        Import <strong>Detail Sales</strong> untuk daftar invoice; import <strong>Detail Product Sales</strong> untuk item per invoice.
        Aksi <strong>Lihat Produk</strong> ada di menu titik-tiga pada kolom Aksi.
    </x-alert>

    {{-- Filter bar: Status / Nama / Bulan / Tahun (client-side DataTables, select2 multi) --}}
    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-3">
        <div class="w-52">
            <label for="f_status" class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
            <select id="f_status" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                <option value="match">Match</option>
                <option value="unmatch">Unmatch</option>
                <option value="no_pair">Tidak ada pasangan</option>
            </select>
        </div>
        @if($tab === 'external')
        <div class="w-40">
            <label for="f_faktur" class="mb-1 block text-xs font-medium text-muted-foreground">Faktur Pajak</label>
            <select id="f_faktur" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                <option value="ada">FP ada</option>
                <option value="belum">fp belum ada</option>
                <option value="gung_gung">Gung Gung</option>
            </select>
        </div>
        @endif
        <div class="w-64">
            <label for="f_nama" class="mb-1 block text-xs font-medium text-muted-foreground">Nama</label>
            <select id="f_nama" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
            </select>
        </div>
        <div class="w-40">
            <label for="f_bulan" class="mb-1 block text-xs font-medium text-muted-foreground">Bulan</label>
            <select id="f_bulan" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
            </select>
        </div>
        <div class="w-28">
            <label for="f_tahun" class="mb-1 block text-xs font-medium text-muted-foreground">Tahun</label>
            <select id="f_tahun" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
            </select>
        </div>
        <div class="w-40">
            <label for="f_tgl_dari" class="mb-1 block text-xs font-medium text-muted-foreground">Tanggal Dari</label>
            <input type="date" id="f_tgl_dari"
                   class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
        </div>
        <div class="w-40">
            <label for="f_tgl_sampai" class="mb-1 block text-xs font-medium text-muted-foreground">Tanggal Sampai</label>
            <input type="date" id="f_tgl_sampai"
                   class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
        </div>
        <div>
            <span class="mb-1 block text-xs invisible select-none" aria-hidden="true">Reset</span>
            <button type="button" id="f_reset"
                    class="min-h-9 w-full rounded-md border border-border bg-background px-3 text-sm text-muted-foreground hover:bg-accent hover:text-foreground">
                Reset
            </button>
        </div>
        <div class="ml-auto flex items-end gap-3">
            <div>
                <span class="mb-1 block text-xs invisible select-none" aria-hidden="true">Export</span>
                <span id="export_count"
                      class="hidden min-h-9 items-center text-sm font-medium text-foreground">
                    0 data dipilih
                </span>
            </div>
            @if($tab === 'external')
            <div class="dropdown dropdown-end">
                <button type="button" id="bulk_btn" tabindex="0" disabled
                        class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    Action
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul tabindex="0"
                    class="dropdown-content menu z-[90] mt-1 w-52 rounded-lg border border-border bg-card p-1 shadow-xl">
                    <li><button type="button" class="flex w-full rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground" data-bulk="gung_gung">Ubah Status Fp</button></li>
                    <li><button type="button" class="flex w-full rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground" data-bulk="clear">Kembalikan Status Fp</button></li>
                </ul>
            </div>
            @endif
            <div class="dropdown dropdown-end">
                <button type="button" id="export_btn" tabindex="0" disabled
                        class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    Export Data
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul tabindex="0"
                    class="dropdown-content menu z-[90] mt-1 w-48 rounded-lg border border-border bg-card p-1 shadow-xl">
                    @if($tab === 'internal')
                    <li><button type="button" class="flex w-full rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground" data-format="xml_external">XML External</button></li>
                    @else
                    <li><button type="button" class="flex w-full rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground" data-format="xml">Export XML</button></li>
                    @endif
                    <li><button type="button" class="flex w-full rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground" data-format="csv">Export CSV</button></li>
                    <li><button type="button" class="flex w-full rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground" data-format="xls">Export XLS</button></li>
                </ul>
            </div>
        </div>
    </div>

    <x-card>
        {{-- Horizontally scrollable data table: card tetap di batas, scroll hanya di wrapper --}}
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="detail-sales-table" class="w-full min-w-[1100px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">
                            <input type="checkbox" id="select_all_rows"
                                   class="h-4 w-4 rounded border-border text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                   aria-label="Pilih semua baris" />
                        </th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Status</th>
                        @if($tab === 'external')
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Faktur Pajak</th>
                        @endif
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Customer No.</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Customer Name</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice No.</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice Date</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Description</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice Invoice Amount</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Amount</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice Tax 1 Base Currency</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice Discount</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice Return Amount</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice Owing</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice Payment Amount</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Calculate Field 1</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Baris dirender DataTables dari DS_DATA (deferRender) — DOM hanya baris aktif --}}
                </tbody>
            </table>
        </div>
        @if($detailSales->isEmpty())
            <div class="p-4 text-center text-sm text-muted-foreground border-t border-border">
                Belum ada data di tab {{ $tab }}. Menunggu import Excel.
            </div>
        @endif
    </x-card>

    {{-- Action menu fixed di body: tidak terpotong overflow-x-auto table --}}
    <div id="action_menu"
         class="hidden fixed z-[100] w-52 rounded-lg border border-border bg-card p-1 shadow-xl">
        <button type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground"
                onclick="showProductsFromMenu()">
            <svg class="h-4 w-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            Lihat Produk
        </button>
        @if($tab === 'external')
        <button type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground"
                onclick="setGungGungFromMenu()">
            <svg class="h-4 w-4 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span id="menu_fp_label">Ubah Status Fp</span>
        </button>
        @endif
        @if($tab === 'internal')
        <button type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground"
                onclick="exportXmlExternalDocFromMenu()">
            <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Export XML External
        </button>
        @endif
    </div>

    {{-- Import Detail Sales Modal --}}
    <dialog id="import_sales_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Import Detail Sales {{ $tab === 'external' ? 'External' : 'Internal' }}</h3>
            <p class="py-2 text-sm text-muted-foreground">Upload export Accurate <strong>Sales By Customer Detail</strong> (.xls/.xlsx) — level invoice. Data masuk ke tab {{ $tab }}.</p>

            <form action="{{ route('detail-sales.import') }}" method="POST" enctype="multipart/form-data" class="js-import-form">
                @csrf
                <input type="hidden" name="source_type" value="{{ $tab }}">
                <input type="hidden" name="import_type" value="sales">
                <div class="py-4">
                    <label for="file_sales" class="block text-sm font-medium text-muted-foreground mb-2">File Excel</label>
                    <input type="file"
                           id="file_sales"
                           name="file"
                           accept=".xlsx,.xls"
                           required
                           class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 cursor-pointer" />
                    @error('file')
                        <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Upload & Import</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('import_sales_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Import Detail Product Sales Modal --}}
    <dialog id="import_items_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Import Detail Product Sales {{ $tab === 'external' ? 'External' : 'Internal' }}</h3>
            <p class="py-2 text-sm text-muted-foreground">Upload export Accurate <strong>Detail Sales Per Item</strong> (.xls/.xlsx) — item per invoice. Data masuk ke tab {{ $tab }}.</p>

            <form action="{{ route('detail-sales.import') }}" method="POST" enctype="multipart/form-data" class="js-import-form">
                @csrf
                <input type="hidden" name="source_type" value="{{ $tab }}">
                <input type="hidden" name="import_type" value="items">
                <div class="py-4">
                    <label for="file_items" class="block text-sm font-medium text-muted-foreground mb-2">File Excel</label>
                    <input type="file"
                           id="file_items"
                           name="file"
                           accept=".xlsx,.xls"
                           required
                           class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 cursor-pointer" />
                    @error('file')
                        <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Upload & Import</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('import_items_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Import progress (muncul otomatis saat form import disubmit) --}}
    <dialog id="import_progress_modal" class="modal">
        <div class="modal-box text-center">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <h3 class="mt-4 font-bold text-lg text-foreground">Mengimport data…</h3>
            <p class="py-2 text-sm text-muted-foreground">
                Mohon tunggu, jangan tutup atau muat ulang halaman.<br>
                Proses import biasanya sekitar 5–30 detik tergantung ukuran file.
            </p>
        </div>
        <div class="modal-backdrop"></div>
    </dialog>

    {{-- Import result toast (auto-dismiss) --}}
    <div id="import_toast" class="fixed right-4 top-4 z-[200] hidden rounded-lg px-4 py-3 text-sm font-medium text-white shadow-xl"></div>

    {{-- Lihat Produk Modal (80% layar) --}}
    <dialog id="products_modal" class="modal">
        <div class="modal-box flex flex-col p-6 !w-[80vw] !max-w-[80vw] !h-[80vh] !max-h-[80vh]" style="width:80vw;max-width:80vw;height:80vh;max-height:80vh;">
            <div class="flex items-start justify-between gap-4 shrink-0">
                <div>
                    <h3 class="font-bold text-lg text-foreground">Detail Produk</h3>
                    <p class="py-1 text-sm text-muted-foreground">
                        Invoice: <strong id="products_doc">-</strong>
                        <span id="products_count" class="ml-2"></span>
                    </p>
                </div>
                <button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('products_modal').close()">✕</button>
            </div>

            <div class="flex-1 overflow-auto border border-border rounded-lg mt-2 min-h-0">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead class="border-b border-border bg-muted/50 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-muted-foreground">Item No.</th>
                            <th class="px-3 py-2 text-left font-medium text-muted-foreground">Description</th>
                            <th class="px-3 py-2 text-right font-medium text-muted-foreground">Unit Price</th>
                            <th class="px-3 py-2 text-right font-medium text-muted-foreground">Qty</th>
                            <th class="px-3 py-2 text-left font-medium text-muted-foreground">Unit</th>
                            <th class="px-3 py-2 text-right font-medium text-muted-foreground">Amount</th>
                            <th class="px-3 py-2 text-right font-medium text-muted-foreground">Discount</th>
                        </tr>
                    </thead>
                    <tbody id="products_body">
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-muted-foreground">Memuat data...</td>
                        </tr>
                    </tbody>
                    <tfoot id="products_foot" class="hidden border-t-2 border-border bg-muted/40 sticky bottom-0">
                        <tr>
                            <td colspan="5" class="px-3 py-2 text-right font-semibold text-foreground">Total</td>
                            <td id="products_total_amount" class="px-3 py-2 text-right font-bold text-foreground">Rp 0</td>
                            <td id="products_total_discount" class="px-3 py-2 text-right font-bold text-foreground">Rp 0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex items-center justify-between shrink-0 pt-3">
                <div id="products_summary" class="text-sm text-muted-foreground"></div>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('products_modal').close()">Tutup</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Clear Data Modal: Hapus Periode + Hapus Seluruh Tab --}}
    @php
        $clearBulanNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $defaultBulan = $filterBulan[0] ?? (int) now()->month;
        $defaultTahun = $filterTahun[0] ?? (int) now()->year;
        $clearBulanOpts = collect($availableBulan)->isEmpty() ? range(1, 12) : $availableBulan->all();
        if (! in_array($defaultBulan, $clearBulanOpts, true)) {
            $clearBulanOpts[] = $defaultBulan;
            sort($clearBulanOpts);
        }
        $clearTahunOpts = collect($availableTahun)->isEmpty() ? [now()->year] : $availableTahun->all();
        if (! in_array($defaultTahun, $clearTahunOpts, true)) {
            $clearTahunOpts[] = $defaultTahun;
            sort($clearTahunOpts);
        }
    @endphp
    <dialog id="clear_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Hapus Data Detail Sales</h3>

            {{-- Bagian 1: Hapus Periode --}}
            <div class="py-3">
                <p class="text-sm font-semibold text-foreground">Hapus Periode</p>
                <p class="py-1 text-sm text-muted-foreground">
                    Hapus invoice <strong>dan item</strong> tab <strong>{{ $tab }}</strong> pada bulan–tahun terpilih saja.
                    Data bulan lain di tab ini tidak terpengaruh.
                </p>
                <form action="{{ route('detail-sales.clear-period') }}" method="POST" class="flex flex-wrap items-end gap-3">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div class="w-40">
                        <label for="clear_bulan" class="mb-1 block text-xs font-medium text-muted-foreground">Bulan</label>
                        <select id="clear_bulan" name="bulan"
                                class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                            @foreach ($clearBulanOpts as $b)
                            <option value="{{ $b }}" @selected((int) $b === (int) $defaultBulan)>{{ $clearBulanNames[(int) $b] ?? $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-28">
                        <label for="clear_tahun" class="mb-1 block text-xs font-medium text-muted-foreground">Tahun</label>
                        <select id="clear_tahun" name="tahun"
                                class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                            @foreach ($clearTahunOpts as $t)
                            <option value="{{ $t }}" @selected((int) $t === (int) $defaultTahun)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <span class="mb-1 block text-xs invisible select-none" aria-hidden="true">Hapus</span>
                        <button type="submit" class="btn btn-error" id="clear_period_submit">Ya, Hapus Periode</button>
                    </div>
                </form>
            </div>

            <div class="my-2 border-t border-border"></div>

            {{-- Bagian 2: Hapus Seluruh Tab --}}
            <div class="py-2">
                <p class="text-sm font-semibold text-destructive">Hapus Seluruh Tab (semua periode!)</p>
                <p class="py-1 text-sm text-muted-foreground">
                    Semua data <strong>invoice</strong> dan <strong>item</strong> di tab <strong>{{ $tab }}</strong>
                    ({{ number_format($totalAll) }} invoice — seluruh tab, bukan hanya yang tampil) akan dihapus permanen.
                    Data di tab lain tidak terpengaruh.
                </p>
                <form action="{{ route('detail-sales.clear') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <button type="submit" class="btn btn-error btn-outline">Ya, Hapus Seluruh Tab {{ $tab }}</button>
                </form>
            </div>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('clear_modal').close()">Tutup</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    @push('scripts')
    <script>
        (function () {
            document.querySelectorAll('.js-import-form').forEach(function (f) {
                f.addEventListener('submit', function () {
                    var dlg = document.getElementById('import_progress_modal');
                    if (dlg && !dlg.open) dlg.showModal();
                    f.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = true; });
                });
            });

            var toastTimer = null;
            window.importToast = function (type, msg) {
                var t = document.getElementById('import_toast');
                if (!t) return;
                t.className = 'fixed right-4 top-4 z-[200] rounded-lg px-4 py-3 text-sm font-medium text-white shadow-xl '
                    + (type === 'success' ? 'bg-success' : 'bg-destructive');
                t.textContent = msg;
                t.classList.remove('hidden');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(function () { t.classList.add('hidden'); }, 8000);
            };

            @if(session('success'))
            importToast('success', @json(session('success')));
            @endif
            @if(session('error'))
            importToast('error', @json(session('error')));
            @endif
        })();

        function formatRp(value) {
            var n = Number(value || 0);
            return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }

        function showProducts(docNumber) {
            var modal = document.getElementById('products_modal');
            var body = document.getElementById('products_body');
            var foot = document.getElementById('products_foot');
            var docEl = document.getElementById('products_doc');
            var countEl = document.getElementById('products_count');
            var summaryEl = document.getElementById('products_summary');
            var totalAmountEl = document.getElementById('products_total_amount');
            var totalDiscountEl = document.getElementById('products_total_discount');

            docEl.textContent = docNumber;
            countEl.textContent = '';
            summaryEl.textContent = '';
            totalAmountEl.textContent = 'Rp 0';
            totalDiscountEl.textContent = 'Rp 0';
            foot.classList.add('hidden');
            body.innerHTML = '<tr><td colspan="7" class="px-3 py-6 text-center text-muted-foreground">Memuat data...</td></tr>';
            modal.showModal();

            var url = @json(route('detail-sales.items', ['docNumber' => '__DOC__'])).replace('__DOC__', encodeURIComponent(docNumber));
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'tab=' + @json($tab);

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    countEl.textContent = '(' + data.count + ' item)';
                    if (!data.items || data.items.length === 0) {
                        body.innerHTML = '<tr><td colspan="7" class="px-3 py-6 text-center text-muted-foreground">Belum ada item untuk invoice ini. Import Detail Product Sales terlebih dahulu.</td></tr>';
                        return;
                    }

                    var html = '';
                    var totalAmount = 0;
                    var totalDiscount = 0;
                    var totalQty = 0;

                    data.items.forEach(function (item) {
                        var amount = Number(item.amount || 0);
                        var discount = Number(item.discount || 0);
                        var qty = Number(item.qty || 0);
                        totalAmount += amount;
                        totalDiscount += discount;
                        totalQty += qty;

                        html += '<tr class="border-b border-border">'
                            + '<td class="px-3 py-2 text-foreground">' + escapeHtml(item.item_no || '-') + '</td>'
                            + '<td class="px-3 py-2 text-foreground">' + escapeHtml(item.item_description || '-') + '</td>'
                            + '<td class="px-3 py-2 text-right text-foreground">' + formatRp(item.unit_price) + '</td>'
                            + '<td class="px-3 py-2 text-right text-foreground">' + qty.toLocaleString('id-ID') + '</td>'
                            + '<td class="px-3 py-2 text-muted-foreground">' + escapeHtml(item.unit || '-') + '</td>'
                            + '<td class="px-3 py-2 text-right text-foreground">' + formatRp(amount) + '</td>'
                            + '<td class="px-3 py-2 text-right text-foreground">' + formatRp(discount) + '</td>'
                            + '</tr>';
                    });
                    body.innerHTML = html;

                    totalAmountEl.textContent = formatRp(totalAmount);
                    totalDiscountEl.textContent = formatRp(totalDiscount);
                    foot.classList.remove('hidden');

                    summaryEl.innerHTML = 'Qty total: <strong class="text-foreground">'
                        + totalQty.toLocaleString('id-ID')
                        + '</strong> · Net: <strong class="text-foreground">'
                        + formatRp(totalAmount - totalDiscount)
                        + '</strong>';
                })
                .catch(function (err) {
                    body.innerHTML = '<tr><td colspan="7" class="px-3 py-6 text-center text-destructive">Gagal memuat data: ' + escapeHtml(err.message) + '</td></tr>';
                });
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        var actionMenuDoc = null;
        var actionMenuFpStatus = '';

        function openActionMenu(btn) {
            var menu = document.getElementById('action_menu');
            actionMenuDoc = btn.getAttribute('data-doc');
            actionMenuFpStatus = btn.getAttribute('data-fp-status') || '';

            // Label tombol status FP menyesuaikan kondisi baris (toggle).
            var fpLabel = document.getElementById('menu_fp_label');
            if (fpLabel) {
                fpLabel.textContent = (actionMenuFpStatus === 'gung_gung') ? 'Kembalikan Status Fp' : 'Ubah Status Fp';
            }

            menu.classList.remove('hidden');

            var rect = btn.getBoundingClientRect();
            var menuW = menu.offsetWidth;
            var menuH = menu.offsetHeight;

            // Popup di kanan tombol; flip ke kiri jika melewati tepi kanan layar
            var left = rect.right + 6;
            if (left + menuW > window.innerWidth - 8) left = rect.left - menuW - 6;
            if (left < 8) left = 8;

            var top = rect.top;
            if (top + menuH > window.innerHeight - 8) top = window.innerHeight - menuH - 8;
            if (top < 8) top = 8;

            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
        }

        function closeActionMenu() {
            document.getElementById('action_menu').classList.add('hidden');
            actionMenuDoc = null;
        }

        function showProductsFromMenu() {
            var doc = actionMenuDoc;
            closeActionMenu();
            if (doc) showProducts(doc);
        }

        var FP_STATUS_URL = @json(route('detail-sales.fp-status'));
        var FP_STATUS_CSRF = @json(csrf_token());

        // Aksi status FP (toggle): set 'gung_gung' (FP memang tidak dibuat) atau kembalikan ke NULL.
        function setGungGungFromMenu() {
            var doc = actionMenuDoc;
            var newStatus = (actionMenuFpStatus === 'gung_gung') ? '' : 'gung_gung';
            closeActionMenu();
            if (!doc) return;
            fetch(FP_STATUS_URL, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': FP_STATUS_CSRF
                },
                body: JSON.stringify({ doc_number: doc, status: newStatus })
            })
                .then(function (res) {
                    return res.json().then(function (j) { return { ok: res.ok, body: j }; });
                })
                .then(function (r) {
                    if (!r.ok) {
                        importToast('error', (r.body && r.body.message) || 'Gagal mengubah status faktur pajak.');
                        return;
                    }
                    importToast('success', newStatus === 'gung_gung'
                        ? 'Status faktur pajak diubah ke Gung Gung.'
                        : 'Status faktur pajak dikembalikan ke "fp belum ada".');
                    setTimeout(function () { window.location.reload(); }, 600);
                })
                .catch(function () {
                    importToast('error', 'Gagal mengubah status faktur pajak.');
                });
        }

        var EXPORT_XML_URL = @json(route('detail-sales.export-xml-external'));
        var EXPORT_XML_CSRF = @json(csrf_token());

        // Unduh 3 file XML External (Sales Order, Delivery Order, Sales Invoice).
        function downloadExternalXml(payload) {
            fetch(EXPORT_XML_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': EXPORT_XML_CSRF
                },
                body: JSON.stringify(payload)
            })
                .then(function (res) {
                    return res.json().then(function (j) { return { ok: res.ok, body: j }; });
                })
                .then(function (r) {
                    if (!r.ok || !r.body.ok) {
                        importToast('error', (r.body && r.body.message) || 'Gagal membuat XML External.');
                        return;
                    }
                    r.body.files.forEach(function (f, i) {
                        setTimeout(function () {
                            var blob = new Blob([f.content], { type: 'text/xml' });
                            var url = URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = f.name;
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            URL.revokeObjectURL(url);
                        }, i * 350);
                    });
                    importToast('success', r.body.count + ' invoice → 3 file XML External diunduh.');
                })
                .catch(function () {
                    importToast('error', 'Gagal membuat XML External.');
                });
        }

        function exportXmlExternalDocFromMenu() {
            var doc = actionMenuDoc;
            closeActionMenu();
            if (doc) downloadExternalXml({ doc_number: doc });
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('[data-doc][onclick*="openActionMenu"]');
                if (btn) {
                    e.stopPropagation();
                    openActionMenu(btn);
                    return;
                }
                if (!e.target.closest('#action_menu')) closeActionMenu();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeActionMenu();
            });
            window.addEventListener('scroll', closeActionMenu, true);
            window.addEventListener('resize', closeActionMenu);

            // ==== Data source: payload JSON dari server, dirender DataTables (deferRender) ====
            var DS_DATA = @json($payload);

            var selectedIds = new Set();

            var BADGE_BASE = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ';
            function badgeHtml(status) {
                if (status === 'match') return '<span class="' + BADGE_BASE + 'bg-success/10 text-success">Match</span>';
                if (status === 'unmatch') return '<span class="' + BADGE_BASE + 'bg-info/10 text-info">Unmatch</span>';
                return '<span class="' + BADGE_BASE + 'border border-border text-foreground">Tidak ada pasangan</span>';
            }

            // Kolom Faktur Pajak: hanya tab external (doc_number vs reference faktur_pajak).
            var HAS_FP = @json($tab === 'external');
            var FP_COL = {
                data: 'fp_number',
                className: 'px-2 py-1.5',
                render: function (v, type, row) {
                    if (row.fp_status === 'gung_gung') {
                        if (type !== 'display') return 'Gung Gung';
                        return '<span class="' + BADGE_BASE + 'bg-blue-900 text-blue-300">Gung Gung</span>';
                    }
                    if (v) {
                        if (type !== 'display') return 'FP ada ' + v;
                        return '<span class="' + BADGE_BASE + 'bg-success/10 text-success">FP ada</span> '
                            + '<span class="text-foreground">' + escapeHtml(v) + '</span>';
                    }
                    if (type !== 'display') return 'fp belum ada';
                    return '<span class="' + BADGE_BASE + 'bg-error/10 text-error">fp belum ada</span>';
                }
            };

            $('#detail-sales-table').DataTable({
                data: DS_DATA,
                deferRender: true,
                pageLength: 25,
                order: [],
                columns: (function () {
                    var cols = [
                    { data: null, orderable: false, searchable: false, className: 'px-2 py-1.5 sticky left-0 bg-card z-10',
                        render: function (d, type) {
                            if (type !== 'display') return '';
                            return '<button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-info/15 text-info ring-1 ring-info/40 hover:bg-info/30 hover:text-info" data-doc="' + escapeHtml(d.doc_number || '') + '" data-fp-status="' + escapeHtml(d.fp_status || '') + '" onclick="openActionMenu(this)" aria-label="Action">'
                                + '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg></button>';
                        } },
                    { data: null, orderable: false, searchable: false, className: 'px-2 py-1.5',
                        render: function (d, type) {
                            if (type !== 'display') return '';
                            return '<input type="checkbox" class="row-check h-4 w-4 rounded border-border text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" value="' + d.id + '"'
                                + (selectedIds.has(d.id) ? ' checked' : '')
                                + ' aria-label="Pilih baris ' + escapeHtml(d.doc_number || d.id) + '" />';
                        } },
                    { data: 'status', className: 'px-2 py-1.5',
                        render: function (v, type) { return type === 'display' ? badgeHtml(v) : (v || ''); } },
                    { data: 'customer_code', className: 'px-2 py-1.5 text-foreground',
                        render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
                    { data: 'customer_name', className: 'px-2 py-1.5 font-medium text-foreground max-w-[160px] truncate',
                        render: function (v, type) { v = v || ''; if (type !== 'display') return v; return v === '' ? '-' : '<span title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>'; } },
                    { data: 'doc_number', className: 'px-2 py-1.5 text-foreground',
                        render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
                    { data: 'transaction_date', className: 'px-2 py-1.5 text-muted-foreground',
                        render: function (v, type) {
                            if (!v) return type === 'display' ? '-' : '';
                            if (type !== 'display') return v;
                            var p = v.split('-');
                            return p[2] + '/' + p[1] + '/' + p[0];
                        } },
                    { data: 'description', className: 'px-2 py-1.5 text-muted-foreground max-w-[160px] truncate',
                        render: function (v, type) { v = v || ''; if (type !== 'display') return v; return v === '' ? '-' : '<span title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>'; } },
                    { data: 'invoice_amount', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                    { data: 'amount', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                    { data: 'tax_base', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                    { data: 'discount', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                    { data: 'return_amount', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                    { data: 'owing', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                    { data: 'payment_amount', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                    { data: 'calculate_field', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } }
                    ];
                    if (HAS_FP) cols.splice(3, 0, FP_COL);
                    return cols;
                })(),
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '(disaring dari _MAX_ total data)',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Selanjutnya',
                        previous: 'Sebelumnya'
                    }
                }
            });

            var filterTable = $('#detail-sales-table').DataTable();
            var BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

            function uniqueAttr(key) {
                var seen = {};
                DS_DATA.forEach(function (r) {
                    var v = r[key] || '';
                    if (v) seen[v] = true;
                });
                return Object.keys(seen).sort(function (a, b) {
                    return a.toLowerCase().localeCompare(b.toLowerCase());
                });
            }

            function fillSelect(id, values, labelFn, selected) {
                var sel = document.getElementById(id);
                sel.innerHTML = '';
                selected = (selected || []).map(Number);
                values.forEach(function(v) {
                    var o = document.createElement('option');
                    o.value = v;
                    o.textContent = labelFn ? labelFn(v) : v;
                    if (selected.indexOf(Number(v)) !== -1) o.selected = true;
                    sel.appendChild(o);
                });
            }

            // Opsi Bulan/Tahun & nilai terpilih datang dari server (default: bulan terakhir berdata).
            fillSelect('f_bulan', @json($availableBulan), function(v) {
                return BULAN[parseInt(v, 10) - 1] || v;
            }, @json($filterBulan));
            fillSelect('f_tahun', @json($availableTahun), null, @json($filterTahun));
            fillSelect('f_nama', uniqueAttr('customer_name'));

            var MAX_SEL = { f_bulan: 12, f_tahun: 4 };
            var FILTER_IDS = ['f_status', 'f_nama', 'f_bulan', 'f_tahun'];
            if (HAS_FP) FILTER_IDS.push('f_faktur');
            FILTER_IDS.forEach(function(id) {
                $('#' + id).select2({
                    width: '100%',
                    placeholder: 'Semua',
                    minimumResultsForSearch: 0,
                    maximumSelectionLength: MAX_SEL[id] || 0
                });
            });

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'detail-sales-table') return true;
                var row = filterTable.row(dataIndex).data();
                if (!row) return true;
                var s = $('#f_status').val() || [];
                var n = $('#f_nama').val() || [];
                var fp = $('#f_faktur').val() || [];
                var d1 = $('#f_tgl_dari').val() || '';
                var d2 = $('#f_tgl_sampai').val() || '';
                if (s.length && s.indexOf(row.status || '') === -1) return false;
                if (fp.length) {
                    var hasFp = !!row.fp_number;
                    var isGung = row.fp_status === 'gung_gung';
                    // "belum" = belum ada FP sama sekali — baris gung_gung
                    // (sengaja tidak dibuat) punya kategori sendiri.
                    if (!((fp.indexOf('ada') !== -1 && hasFp && !isGung)
                        || (fp.indexOf('belum') !== -1 && !hasFp && !isGung)
                        || (fp.indexOf('gung_gung') !== -1 && isGung))) return false;
                }
                if (n.length && !n.some(function(x) {
                    return x.toLowerCase() === (row.customer_name || '').toLowerCase();
                })) return false;
                var d = row.transaction_date || '';
                if (d1 && (!d || d < d1)) return false;
                if (d2 && (!d || d > d2)) return false;
                return true;
            });

            $('#f_status, #f_nama, #f_faktur').on('change', function() {
                filterTable.draw();
            });

            // Bulan/Tahun = filter server-side: ganti pilihan → reload dgn ?bulan=&tahun=.
            function reloadBulanTahun() {
                var params = new URLSearchParams(window.location.search);
                var b = $('#f_bulan').val() || [];
                var t = $('#f_tahun').val() || [];
                if (b.length) params.set('bulan', b.join(',')); else params.delete('bulan');
                if (t.length) params.set('tahun', t.join(',')); else params.delete('tahun');
                params.set('tab', @json($tab));
                window.location.search = params.toString();
            }
            $('#f_bulan, #f_tahun').on('change', reloadBulanTahun);

            $('#f_tgl_dari, #f_tgl_sampai').on('change', function() {
                filterTable.draw();
            });

            $('#f_reset').on('click', function() {
                $('#f_status, #f_nama, #f_faktur').val([]);
                $('#f_tgl_dari, #f_tgl_sampai').val('');
                var params = new URLSearchParams(window.location.search);
                params.delete('bulan');
                params.delete('tahun');
                params.set('tab', @json($tab));
                var target = params.toString();
                if (target !== window.location.search.replace(/^\?/, '')) {
                    window.location.search = target;
                } else {
                    filterTable.draw();
                }
            });

            // ==== Selection checkbox + Export ====
            var EXPORT_URL = @json(route('detail-sales.export'));
            var EXPORT_CSRF = @json(csrf_token());
            var EXPORT_TAB = @json($tab);

            function syncVisibleChecks() {
                filterTable.rows({ search: 'applied' }).every(function() {
                    var node = this.node();
                    if (!node) return;
                    var cb = node.querySelector('.row-check');
                    if (cb) cb.checked = selectedIds.has(Number(cb.value));
                });
            }

            function syncSelectAll() {
                var all = document.getElementById('select_all_rows');
                var total = 0, checked = 0;
                filterTable.rows({ search: 'applied' }).every(function() {
                    var d = this.data();
                    if (!d) return;
                    total++;
                    if (selectedIds.has(d.id)) checked++;
                });
                if (total === 0) {
                    all.checked = false;
                    all.indeterminate = false;
                } else {
                    all.checked = checked === total;
                    all.indeterminate = checked > 0 && checked < total;
                }
            }

            function updateExportUI() {
                var count = selectedIds.size;
                var countEl = document.getElementById('export_count');
                var btn = document.getElementById('export_btn');
                var bulkBtn = document.getElementById('bulk_btn');
                if (count > 0) {
                    countEl.classList.remove('hidden');
                    countEl.classList.add('flex');
                    countEl.textContent = count + ' data dipilih';
                    btn.disabled = false;
                    if (bulkBtn) bulkBtn.disabled = false;
                } else {
                    countEl.classList.add('hidden');
                    countEl.classList.remove('flex');
                    btn.disabled = true;
                    if (bulkBtn) bulkBtn.disabled = true;
                }
                syncSelectAll();
            }

            document.addEventListener('change', function(e) {
                var t = e.target;
                if (t.id === 'select_all_rows') {
                    filterTable.rows({ search: 'applied' }).every(function() {
                        var d = this.data();
                        if (!d) return;
                        if (t.checked) selectedIds.add(d.id);
                        else selectedIds.delete(d.id);
                    });
                    syncVisibleChecks();
                    updateExportUI();
                    return;
                }
                if (t.classList && t.classList.contains('row-check')) {
                    var id = Number(t.value);
                    if (t.checked) selectedIds.add(id);
                    else selectedIds.delete(id);
                    updateExportUI();
                }
            });

            document.querySelectorAll('#export_btn ~ ul [data-format]').forEach(function(item) {
                item.addEventListener('click', function() {
                    var ids = Array.from(selectedIds);
                    if (!ids.length) return;

                    if (item.getAttribute('data-format') === 'xml_external') {
                        downloadExternalXml({ ids: ids });
                        return;
                    }

                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = EXPORT_URL;
                    var add = function(name, value) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        form.appendChild(input);
                    };
                    add('_token', EXPORT_CSRF);
                    add('tab', EXPORT_TAB);
                    add('format', item.getAttribute('data-format'));
                    ids.forEach(function(id) { add('ids[]', id); });

                    if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
                    document.body.appendChild(form);
                    form.submit();
                    form.remove();
                });
            });

            // Aksi massal "Action" (external): ubah/kembalikan status FP utk baris terpilih.
            document.querySelectorAll('#bulk_btn ~ ul [data-bulk]').forEach(function(item) {
                item.addEventListener('click', function() {
                    var ids = Array.from(selectedIds);
                    if (!ids.length) return;
                    var mode = item.getAttribute('data-bulk');
                    var status = (mode === 'clear') ? '' : 'gung_gung';
                    fetch(FP_STATUS_URL, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': EXPORT_CSRF
                        },
                        body: JSON.stringify({ ids: ids, status: status })
                    })
                        .then(function (res) {
                            return res.json().then(function (j) { return { ok: res.ok, body: j }; });
                        })
                        .then(function (r) {
                            if (!r.ok) {
                                importToast('error', (r.body && r.body.message) || 'Gagal mengubah status faktur pajak.');
                                return;
                            }
                            importToast('success', mode === 'clear'
                                ? ids.length + ' baris: status FP dikembalikan ke "fp belum ada".'
                                : ids.length + ' baris: status FP diubah ke Gung Gung.');
                            setTimeout(function () { window.location.reload(); }, 600);
                        })
                        .catch(function () {
                            importToast('error', 'Gagal mengubah status faktur pajak.');
                        });
                });
            });

            // Tutup action menu saat DataTable redraw + sinkronkan checkbox dgn Set
            $('#detail-sales-table').on('draw.dt', function() {
                closeActionMenu();
                syncVisibleChecks();
                syncSelectAll();
            });
        });
    </script>
    @endpush
</x-layouts.app>
