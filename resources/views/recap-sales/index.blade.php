<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Recap Sales" description="Rekapitulasi sales">
            <x-button onclick="document.getElementById('import_modal').showModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import
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
        <a href="{{ route('recap-sales.index', ['tab' => 'external']) }}"
           class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'external' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground' }}">
            External
        </a>
        <a href="{{ route('recap-sales.index', ['tab' => 'internal']) }}"
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
        Import dari export Accurate <strong>Sales By Customer</strong> (sudah ringkas per customer).
        Periode, kode, nama, total sales, dan varian dibaca langsung dari file.
        @if($tab === 'external')
            <span class="block mt-1">
                <strong>Balance check:</strong> kode external dipetakan via Mapping Customer → total sales tab Internal = <strong>Nilai Internal</strong>.
                Selisih Total Sales − Nilai Internal (jika ada) tampil di kolom Varian. Balance jika selisih = 0.
                Jika kode tidak ada di mapping, badge bertuliskan <strong>mapping Tidak ada</strong>.
                Jika kode sudah di-mapping tapi baris pasangan belum di-import di tab lain, badge bertuliskan
                <strong>{{ $tab === 'external' ? 'Data internal tidak ada' : 'Data external tidak ada' }}</strong>.
            </span>
        @elseif($tab === 'internal')
            <span class="block mt-1">
                <strong>Balance check:</strong> kode internal dipetakan via Mapping Customer → total sales tab External = <strong>Nilai Internal</strong> (pasangan).
                Selisih Total Sales − Nilai Internal (jika ada) tampil di kolom Varian. Balance jika selisih = 0.
                Jika kode tidak ada di mapping, badge bertuliskan <strong>mapping Tidak ada</strong>.
                Jika kode sudah di-mapping tapi baris pasangan belum di-import di tab lain, badge bertuliskan
                <strong>Data external tidak ada</strong>.
            </span>
        @endif
    </x-alert>

    {{-- Filter bar: Status / Nama / Bulan / Tahun (client-side DataTables, select2 multi) --}}
    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-3">
        <div class="w-52">
            <label for="f_status" class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
            <select id="f_status" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                <option value="balance">Balance</option>
                <option value="not_balance">Not Balance</option>
                <option value="partner_tidak_ada">Data {{ $tab === 'external' ? 'internal' : 'external' }} tidak ada</option>
                <option value="mapping_tidak_ada">mapping Tidak ada</option>
            </select>
        </div>
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
        <div>
            <span class="mb-1 block text-xs invisible select-none" aria-hidden="true">Reset</span>
            <button type="button" id="f_reset"
                    class="min-h-9 w-full rounded-md border border-border bg-background px-3 text-sm text-muted-foreground hover:bg-accent hover:text-foreground">
                Reset
            </button>
        </div>
    </div>

    <x-card>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="recap-sales-table" class="w-full min-w-[1100px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Status</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Periode</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Kode</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Nama</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Total Sales</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Nilai Internal</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Varian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recapSales as $row)
                        @php
                            preg_match('/(\d{4})-(\d{2})/', (string) $row->period, $periodParts);
                        @endphp
                        <tr class="border-b border-border transition-colors hover:bg-accent/50"
                            data-status="{{ $row->balance_status ?? '' }}"
                            data-bulan="{{ $periodParts[2] ?? '' }}"
                            data-tahun="{{ $periodParts[1] ?? '' }}"
                            data-nama="{{ $row->customer_name ?? '' }}">
                            <td class="px-2 py-1.5 sticky left-0 bg-card z-10">
                                <button type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-info/15 text-info ring-1 ring-info/40 hover:bg-info/30 hover:text-info"
                                        aria-label="Action">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-2 py-1.5">
                                @if(isset($row->balance_status))
                                    @if($row->balance_status === 'balance')
                                        <x-badge type="success" label="Balance" />
                                    @elseif($row->balance_status === 'not_balance')
                                        <x-badge type="danger" label="Not Balance" />
                                    @elseif($row->balance_status === 'partner_tidak_ada')
                                        <x-badge type="warning" label="{{ $tab === 'external' ? 'Data internal tidak ada' : 'Data external tidak ada' }}" />
                                    @else
                                        <x-badge type="warning" label="mapping Tidak ada" />
                                    @endif
                                @else
                                    <x-badge type="outline" label="-" />
                                @endif
                            </td>
                            <td class="px-2 py-1.5 text-muted-foreground">{{ $row->period ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-foreground">{{ $row->customer_code ?? '-' }}</td>
                            <td class="px-2 py-1.5 font-medium text-foreground max-w-[220px] truncate" title="{{ $row->customer_name ?? '' }}">{{ $row->customer_name ?? '-' }}</td>
                            <td class="px-2 py-1.5 font-medium text-foreground">Rp {{ number_format((float) ($row->total_sales ?? 0), 0, ',', '.') }}</td>
                            <td class="px-2 py-1.5 font-medium text-foreground">
                                @if($row->nilai_internal !== null)
                                    Rp {{ number_format($row->nilai_internal, 0, ',', '.') }}
                                @else
                                    <span class="text-muted-foreground" title="{{ $row->balance_status === 'partner_tidak_ada' ? 'Kode sudah di-mapping, tapi baris pasangan (' . ($tab === 'external' ? 'internal' : 'external') . ') belum di-import' : 'Tidak ada pasangan untuk dibandingkan' }}">-</span>
                                @endif
                            </td>
                            <td class="px-2 py-1.5 text-foreground">
                                @if($row->selisih !== null && abs($row->selisih) >= 0.01)
                                    <span class="font-medium text-destructive">Rp {{ number_format($row->selisih, 0, ',', '.') }}</span>
                                @else
                                    {{ $row->varian ?: '-' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        {{-- Baris empty tanpa colspan: hindari DataTables warning tn/4 --}}
                        <tr class="border-b border-border">
                            @for($i = 0; $i < 8; $i++)
                                <td class="px-2 py-1.5 text-muted-foreground">-</td>
                            @endfor
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($recapSales->isEmpty())
            <div class="p-4 text-center text-sm text-muted-foreground border-t border-border">
                Belum ada data di tab {{ $tab }}. Menunggu import Excel.
            </div>
        @endif
    </x-card>

    {{-- Import Modal --}}
    <dialog id="import_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Import Recap Sales {{ $tab === 'external' ? 'External' : 'Internal' }}</h3>
            <p class="py-2 text-sm text-muted-foreground">Upload export Accurate <strong>Sales By Customer</strong> (.xls/.xlsx) — format ringkas per customer. Data masuk ke tab {{ $tab }}.</p>

            <form action="{{ route('recap-sales.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="source_type" value="{{ $tab }}">
                <div class="py-4">
                    <label for="file" class="block text-sm font-medium text-muted-foreground mb-2">File Excel</label>
                    <input type="file"
                           id="file"
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
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('import_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Clear Data Modal --}}
    <dialog id="clear_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Hapus Semua Data?</h3>
            <p class="py-2 text-sm text-muted-foreground">
                Semua data Recap Sales di tab <strong>{{ $tab }}</strong> ({{ number_format($recapSales->count()) }} baris) akan dihapus permanen.
                Data di tab lain tidak terpengaruh.
            </p>
            <div class="modal-action">
                <form action="{{ route('recap-sales.clear') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <button type="submit" class="btn btn-error">Ya, Hapus</button>
                </form>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('clear_modal').close()">Batal</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var table = $('#recap-sales-table').DataTable({
                pageLength: 25,
                order: [],
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

            var BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

            function uniqueAttr(attr) {
                var seen = {};
                table.rows().every(function() {
                    var v = this.node() ? (this.node().getAttribute(attr) || '') : '';
                    if (v) seen[v] = true;
                });
                return Object.keys(seen).sort(function(a, b) {
                    return a.toLowerCase().localeCompare(b.toLowerCase());
                });
            }

            function fillSelect(id, values, labelFn) {
                var sel = document.getElementById(id);
                sel.innerHTML = '';
                values.forEach(function(v) {
                    var o = document.createElement('option');
                    o.value = v;
                    o.textContent = labelFn ? labelFn(v) : v;
                    sel.appendChild(o);
                });
            }

            fillSelect('f_bulan', uniqueAttr('data-bulan'), function(v) {
                return BULAN[parseInt(v, 10) - 1] || v;
            });
            fillSelect('f_tahun', uniqueAttr('data-tahun'));
            fillSelect('f_nama', uniqueAttr('data-nama'));

            var MAX_SEL = { f_bulan: 12, f_tahun: 4 };
            ['f_status', 'f_nama', 'f_bulan', 'f_tahun'].forEach(function(id) {
                $('#' + id).select2({
                    width: '100%',
                    placeholder: 'Semua',
                    minimumResultsForSearch: 0,
                    maximumSelectionLength: MAX_SEL[id] || 0
                });
            });

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'recap-sales-table') return true;
                var row = table.row(dataIndex).node();
                if (!row) return true;
                var s = $('#f_status').val() || [];
                var n = $('#f_nama').val() || [];
                var b = $('#f_bulan').val() || [];
                var t = $('#f_tahun').val() || [];
                if (s.length && s.indexOf(row.getAttribute('data-status') || '') === -1) return false;
                if (b.length && b.indexOf(row.getAttribute('data-bulan') || '') === -1) return false;
                if (t.length && t.indexOf(row.getAttribute('data-tahun') || '') === -1) return false;
                if (n.length && !n.some(function(x) {
                    return x.toLowerCase() === (row.getAttribute('data-nama') || '').toLowerCase();
                })) return false;
                return true;
            });

            $('#f_status, #f_nama, #f_bulan, #f_tahun').on('change', function() {
                table.draw();
            });

            $('#f_reset').on('click', function() {
                $('#f_status, #f_nama, #f_bulan, #f_tahun').val([]).trigger('change');
            });

            // Default filter: bulan & tahun berjalan (batasi data saat load)
            var DEFAULT_BULAN = @json(now()->format('m'));
            var DEFAULT_TAHUN = @json(now()->format('Y'));
            if ($('#f_bulan option[value="' + DEFAULT_BULAN + '"]').length) {
                $('#f_bulan').val([DEFAULT_BULAN]);
            }
            if ($('#f_tahun option[value="' + DEFAULT_TAHUN + '"]').length) {
                $('#f_tahun').val([DEFAULT_TAHUN]);
            }
            table.draw();
        });
    </script>
    @endpush
</x-layouts.app>
