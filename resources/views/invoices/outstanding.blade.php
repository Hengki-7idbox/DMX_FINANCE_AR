<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="{{ $pageTitle }}" description="{{ $pageDescription }}" back-url="{{ route('invoices.index') }}">
            @if($mode === 'outstanding')
            <x-button onclick="document.getElementById('import_outstanding_modal').showModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import Outstanding
            </x-button>
            @endif
        </x-page-header>
    </x-slot>

    @include('invoices._tabs')

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif
    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filter bar --}}
    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-3">
        @if($mode === 'anomali')
        <div class="w-44">
            <label for="f_status" class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
            <select id="f_status" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"></select>
        </div>
        @else
        <div class="w-40">
            <label for="f_lunas" class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
            <select id="f_lunas" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"></select>
        </div>
        <div class="w-44">
            <label for="f_tukar" class="mb-1 block text-xs font-medium text-muted-foreground">Tukar Faktur</label>
            <select id="f_tukar" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"></select>
        </div>
        @endif
        <div class="w-40">
            <label for="f_bulan" class="mb-1 block text-xs font-medium text-muted-foreground">Bulan</label>
            <select id="f_bulan" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"></select>
        </div>
        <div class="w-28">
            <label for="f_tahun" class="mb-1 block text-xs font-medium text-muted-foreground">Tahun</label>
            <select id="f_tahun" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"></select>
        </div>
        <div class="w-52">
            <label for="f_kode" class="mb-1 block text-xs font-medium text-muted-foreground">Kode Customer</label>
            <select id="f_kode" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"></select>
        </div>
        <div class="w-64">
            <label for="f_nama" class="mb-1 block text-xs font-medium text-muted-foreground">Nama Customer</label>
            <select id="f_nama" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"></select>
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
                <span class="mb-1 block text-xs invisible select-none" aria-hidden="true">Action</span>
                <span id="sel_count" class="hidden min-h-9 items-center text-sm font-medium text-foreground">0 data dipilih</span>
            </div>
            <div class="dropdown dropdown-end">
                <button type="button" id="bulk_btn" tabindex="0" disabled
                        class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    Action
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul tabindex="0" class="dropdown-content menu z-[90] mt-1 w-52 rounded-lg border border-border bg-card p-1 shadow-xl">
                    <li><button type="button" class="flex w-full rounded-md px-3 py-2 text-sm font-medium text-destructive hover:bg-accent" data-bulk="anomali">Anomali</button></li>
                    <li><button type="button" class="flex w-full rounded-md px-3 py-2 text-sm font-medium text-warning hover:bg-accent" data-bulk="biaya_bank">Biaya Bank</button></li>
                    <li><button type="button" class="flex w-full rounded-md px-3 py-2 text-sm font-medium text-info hover:bg-accent" data-bulk="sudah_bayar">Sudah Bayar Belum Proses</button></li>
                    <li><button type="button" class="flex w-full rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent" data-bulk="clear">Kembalikan</button></li>
                </ul>
            </div>
        </div>
    </div>

    <x-card>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="outstanding-table" class="w-full min-w-[1300px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Action</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">
                            <input type="checkbox" id="select_all_rows"
                                   class="h-4 w-4 rounded border-border text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                   aria-label="Pilih semua baris" />
                        </th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Status</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Customer No.</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Customer Name</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Suspended</th>
                        <th class="h-10 px-2 text-right font-medium text-muted-foreground">Terms (Days)</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice No.</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Tukar Faktur</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice Date</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Due Date</th>
                        <th class="h-10 px-2 text-right font-medium text-muted-foreground">Invoice Amount</th>
                        <th class="h-10 px-2 text-right font-medium text-muted-foreground">Prime Owing</th>
                        <th class="h-10 px-2 text-right font-medium text-muted-foreground">Tax Owing</th>
                        <th class="h-10 px-2 text-right font-medium text-muted-foreground">Age fr Due</th>
                        <th class="h-10 px-2 text-right font-medium text-muted-foreground">Credit Limit</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Baris dirender DataTables dari DS_DATA (deferRender) --}}
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Row action menu (fixed) --}}
    <div id="row_action_menu" class="hidden fixed z-[100] w-40 rounded-lg border border-border bg-card p-1 shadow-xl">
        <button type="button" onclick="applyRowException('anomali')"
                class="flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-destructive hover:bg-accent">Anomali</button>
        <button type="button" onclick="applyRowException('biaya_bank')"
                class="flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-warning hover:bg-accent">Biaya Bank</button>
        <button type="button" onclick="applyRowException('sudah_bayar')"
                class="flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-info hover:bg-accent">Sudah Bayar Belum Proses</button>
        <button type="button" onclick="applyRowException('clear')"
                class="flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent">Kembalikan</button>
    </div>

    @if($mode === 'outstanding')
    {{-- Import Modal --}}
    <dialog id="import_outstanding_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Import Outstanding Invoices</h3>
            <p class="py-2 text-sm text-muted-foreground">
                Upload file Accurate <strong>Outstanding Invoices</strong> (.xls/.xlsx). Nomor invoice yang sama akan
                <strong>diperbarui</strong> (bukan ditambah).
            </p>
            <form action="{{ route('invoices.outstanding.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="py-4">
                    <label for="file_outstanding" class="block text-sm font-medium text-muted-foreground mb-2">File Excel</label>
                    <input type="file" id="file_outstanding" name="file" accept=".xlsx,.xls" required
                           class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 cursor-pointer" />
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Upload & Import</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('import_outstanding_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
    @endif

    @push('scripts')
    <script>
        var MODE = @json($mode);
        var EXCEPTION_URL = @json(route('invoices.outstanding.exception'));
        var EXCEPTION_CSRF = @json(csrf_token());

        function formatRp(value) {
            var n = Number(value || 0);
            return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function fmtDate(v) {
            if (!v) return '-';
            var p = String(v).split('-');
            return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : escapeHtml(v);
        }

        var BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        var BADGE = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ';

        function tukarFakturHtml(v) {
            if (v === 'sudah') return '<span class="' + BADGE + 'bg-success/10 text-success">Sudah Tukar</span>';
            if (v === 'belum') return '<span class="' + BADGE + 'bg-warning/10 text-warning">Belum Tukar</span>';
            return '<span class="' + BADGE + 'border border-border text-muted-foreground">Tidak Match</span>';
        }

        function exceptionHtml(v) {
            if (v === 'anomali') return '<span class="' + BADGE + 'bg-destructive/10 text-destructive">Anomali</span>';
            if (v === 'biaya_bank') return '<span class="' + BADGE + 'bg-warning/10 text-warning">Biaya Bank</span>';
            if (v === 'sudah_bayar') return '<span class="' + BADGE + 'bg-info/10 text-info">Sudah Bayar Belum Proses</span>';
            return '-';
        }

        function statusCell(row) {
            var parts = [];
            if (row.exception_type === 'anomali') parts.push('<span class="' + BADGE + 'bg-destructive/10 text-destructive">Anomali</span>');
            else if (row.exception_type === 'biaya_bank') parts.push('<span class="' + BADGE + 'bg-warning/10 text-warning">Biaya Bank</span>');
            else if (row.exception_type === 'sudah_bayar') parts.push('<span class="' + BADGE + 'bg-info/10 text-info">Sudah Bayar Belum Proses</span>');
            if (Number(row.missing_count) >= 1) parts.push('<span class="' + BADGE + 'bg-info/10 text-info">Sudah Lunas</span>');
            return parts.length ? parts.join(' ') : '-';
        }

        var DS_DATA = @json($payload);

        function uniqueAttr(key) {
            var seen = {};
            DS_DATA.forEach(function (r) { var v = r[key] || ''; if (v) seen[v] = true; });
            return Object.keys(seen).sort(function (a, b) { return a.toLowerCase().localeCompare(b.toLowerCase()); });
        }

        function fillSelect(id, values, labelFn, selected) {
            var sel = document.getElementById(id);
            sel.innerHTML = '';
            selected = (selected || []).map(Number);
            values.forEach(function (v) {
                var o = document.createElement('option');
                o.value = v;
                o.textContent = labelFn ? labelFn(v) : v;
                if (selected.indexOf(Number(v)) !== -1) o.selected = true;
                sel.appendChild(o);
            });
        }

        fillSelect('f_bulan', @json($availableBulan), function (v) { return BULAN[parseInt(v, 10) - 1] || v; }, @json($filterBulan));
        fillSelect('f_tahun', @json($availableTahun), null, @json($filterTahun));
        fillSelect('f_kode', uniqueAttr('customer_no'));
        fillSelect('f_nama', uniqueAttr('customer_name'));
        if (MODE === 'anomali') {
            fillSelect('f_status', ['anomali', 'biaya_bank', 'sudah_bayar'], function (v) {
                if (v === 'anomali') return 'Anomali';
                if (v === 'biaya_bank') return 'Biaya Bank';
                return 'Sudah Bayar Belum Proses';
            });
        } else {
            fillSelect('f_lunas', ['sudah_lunas', 'belum_lunas'], function (v) { return v === 'sudah_lunas' ? 'Sudah Lunas' : 'Belum Lunas'; });
            fillSelect('f_tukar', ['sudah', 'belum', 'tidak_ada'], function (v) {
                return v === 'sudah' ? 'Sudah Tukar' : (v === 'belum' ? 'Belum Tukar' : 'Tidak Match');
            });
        }

        var FILTER_IDS = ['f_bulan', 'f_tahun', 'f_kode', 'f_nama'];
        if (MODE === 'anomali') {
            FILTER_IDS.push('f_status');
        } else {
            FILTER_IDS = FILTER_IDS.concat(['f_lunas', 'f_tukar']);
        }
        FILTER_IDS.forEach(function (id) {
            $('#' + id).select2({ width: '100%', placeholder: 'Semua', minimumResultsForSearch: 0 });
        });

        var selectedIds = new Set();

        function actionBtn(d) {
            return '<button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-info/15 text-info ring-1 ring-info/40 hover:bg-info/30" data-id="' + d.id + '" onclick="openRowMenu(this)" aria-label="Action">'
                + '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg></button>';
        }

        function checkBox(d) {
            return '<input type="checkbox" class="row-check h-4 w-4 rounded border-border text-primary" value="' + d.id + '"'
                + (selectedIds.has(d.id) ? ' checked' : '') + ' aria-label="Pilih baris ' + (d.invoice_no || '') + '" />';
        }

        var DATA_COLS = [
            { data: 'customer_no', className: 'px-2 py-1.5 text-foreground',
                render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
            { data: 'customer_name', className: 'px-2 py-1.5 font-medium text-foreground max-w-[200px] truncate',
                render: function (v, type) { v = v || ''; if (type !== 'display') return v; return v === '' ? '-' : '<span title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>'; } },
            { data: 'customer_suspended', className: 'px-2 py-1.5 text-muted-foreground',
                render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
            { data: 'terms_net_days', className: 'px-2 py-1.5 text-right text-muted-foreground',
                render: function (v, type) { if (type !== 'display') return v == null ? '' : v; return (v == null) ? '-' : v; } },
            { data: 'invoice_no', className: 'px-2 py-1.5 text-foreground',
                render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
            { data: 'tukar_faktur', className: 'px-2 py-1.5',
                render: function (v, type) { if (type !== 'display') return v || ''; return tukarFakturHtml(v); } },
            { data: 'invoice_date', className: 'px-2 py-1.5 text-muted-foreground',
                render: function (v, type) { if (type !== 'display') return v || ''; return fmtDate(v); } },
            { data: 'due_date', className: 'px-2 py-1.5 text-muted-foreground',
                render: function (v, type) { if (type !== 'display') return v || ''; return fmtDate(v); } },
            { data: 'invoice_amount', className: 'px-2 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
            { data: 'prime_owing', className: 'px-2 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
            { data: 'tax_owing', className: 'px-2 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
            { data: 'age_from_due', className: 'px-2 py-1.5 text-right text-muted-foreground',
                render: function (v, type) { if (type !== 'display') return v == null ? '' : v; return (v == null) ? '-' : v; } },
            { data: 'credit_limit', className: 'px-2 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } }
        ];

        var cols = [
            { data: null, orderable: false, searchable: false, className: 'px-2 py-1.5 sticky left-0 bg-card z-10',
                render: function (d, type) { return type === 'display' ? actionBtn(d) : ''; } },
            { data: null, orderable: false, searchable: false, className: 'px-2 py-1.5',
                render: function (d, type) { return type === 'display' ? checkBox(d) : ''; } }
        ];
        cols.push({ data: null, orderable: false, searchable: false, className: 'px-2 py-1.5',
            render: function (d, type) { return type === 'display' ? statusCell(d) : ''; } });
        cols = cols.concat(DATA_COLS);

        var filterTable = $('#outstanding-table').DataTable({
            data: DS_DATA,
            deferRender: true,
            pageLength: 25,
            order: [],
            columns: cols,
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
                paginate: { first: 'Pertama', last: 'Terakhir', next: 'Selanjutnya', previous: 'Sebelumnya' }
            }
        });

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'outstanding-table') return true;
            var row = filterTable.row(dataIndex).data();
            if (!row) return true;
            var k = $('#f_kode').val() || [];
            var n = $('#f_nama').val() || [];
            var st = $('#f_status').val() || [];
            if (st.length && st.indexOf(row.exception_type || '') === -1) return false;
            var ln = $('#f_lunas').val() || [];
            if (ln.length) {
                var lunas = Number(row.missing_count) >= 1 ? 'sudah_lunas' : 'belum_lunas';
                if (ln.indexOf(lunas) === -1) return false;
            }
            var tk = $('#f_tukar').val() || [];
            if (tk.length && tk.indexOf(row.tukar_faktur || '') === -1) return false;
            if (k.length && k.indexOf(row.customer_no || '') === -1) return false;
            if (n.length && !n.some(function (x) { return x.toLowerCase() === (row.customer_name || '').toLowerCase(); })) return false;
            return true;
        });

        $('#f_kode, #f_nama, #f_status, #f_lunas, #f_tukar').on('change', function () { filterTable.draw(); });
        $('#f_bulan, #f_tahun').on('change', function () {
            var p = new URLSearchParams(window.location.search);
            var b = $('#f_bulan').val() || [];
            var t = $('#f_tahun').val() || [];
            if (b.length) p.set('bulan', b.join(',')); else p.delete('bulan');
            if (t.length) p.set('tahun', t.join(',')); else p.delete('tahun');
            // Keduanya kosong = tampilkan semua (tanpa filter bulan/tahun).
            if (!b.length && !t.length) p.set('all', '1'); else p.delete('all');
            window.location.search = p.toString();
        });
        $('#f_reset').on('click', function () {
            // Reset mengosongkan SEMUA filter → tampilkan semua.
            $('#f_kode, #f_nama, #f_status, #f_lunas, #f_tukar').val([]).trigger('change');
            var p = new URLSearchParams(window.location.search);
            p.delete('bulan'); p.delete('tahun');
            p.set('all', '1');
            window.location.search = p.toString();
        });

        // ==== Selection + bulk ====
        var rowMenuId = null;

        function syncVisibleChecks() {
            filterTable.rows({ search: 'applied' }).every(function () {
                var node = this.node();
                if (!node) return;
                var cb = node.querySelector('.row-check');
                if (cb) cb.checked = selectedIds.has(Number(cb.value));
            });
        }
        function syncSelectAll() {
            var all = document.getElementById('select_all_rows');
            var total = 0, checked = 0;
            filterTable.rows({ search: 'applied' }).every(function () {
                var d = this.data();
                if (!d) return;
                total++;
                if (selectedIds.has(d.id)) checked++;
            });
            if (total === 0) { all.checked = false; all.indeterminate = false; }
            else { all.checked = checked === total; all.indeterminate = checked > 0 && checked < total; }
        }
        function updateSelectUI() {
            var count = selectedIds.size;
            var el = document.getElementById('sel_count');
            var btn = document.getElementById('bulk_btn');
            if (count > 0) {
                el.classList.remove('hidden'); el.classList.add('flex');
                el.textContent = count + ' data dipilih';
                btn.disabled = false;
            } else {
                el.classList.add('hidden'); el.classList.remove('flex');
                btn.disabled = true;
            }
            syncSelectAll();
        }

        document.addEventListener('change', function (e) {
            var t = e.target;
            if (t.id === 'select_all_rows') {
                filterTable.rows({ search: 'applied' }).every(function () {
                    var d = this.data();
                    if (!d) return;
                    if (t.checked) selectedIds.add(d.id); else selectedIds.delete(d.id);
                });
                syncVisibleChecks();
                updateSelectUI();
                return;
            }
            if (t.classList && t.classList.contains('row-check')) {
                var id = Number(t.value);
                if (t.checked) selectedIds.add(id); else selectedIds.delete(id);
                updateSelectUI();
            }
        });

        $('#outstanding-table').on('draw.dt', function () { closeRowMenu(); syncVisibleChecks(); syncSelectAll(); });

        // ==== Row action menu ====
        function openRowMenu(btn) {
            var menu = document.getElementById('row_action_menu');
            rowMenuId = Number(btn.getAttribute('data-id'));
            menu.classList.remove('hidden');
            var rect = btn.getBoundingClientRect();
            var w = menu.offsetWidth, h = menu.offsetHeight;
            var left = rect.right + 6;
            if (left + w > window.innerWidth - 8) left = rect.left - w - 6;
            if (left < 8) left = 8;
            var top = rect.top;
            if (top + h > window.innerHeight - 8) top = window.innerHeight - h - 8;
            if (top < 8) top = 8;
            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
        }
        function closeRowMenu() {
            document.getElementById('row_action_menu').classList.add('hidden');
            rowMenuId = null;
        }
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-id][onclick*="openRowMenu"]');
            if (btn) { e.stopPropagation(); openRowMenu(btn); return; }
            if (!e.target.closest('#row_action_menu')) closeRowMenu();
        });
        window.addEventListener('scroll', closeRowMenu, true);
        window.addEventListener('resize', closeRowMenu);

        function applyRowException(type) {
            var id = rowMenuId;
            closeRowMenu();
            if (!id) return;
            setException([id], type);
        }

        // ==== Bulk menu ====
        document.querySelectorAll('#bulk_btn ~ ul [data-bulk]').forEach(function (item) {
            item.addEventListener('click', function () {
                var ids = Array.from(selectedIds);
                if (!ids.length) return;
                setException(ids, item.getAttribute('data-bulk'));
            });
        });

        function setException(ids, type) {
            fetch(EXCEPTION_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': EXCEPTION_CSRF
                },
                body: JSON.stringify({ ids: ids, type: type })
            })
                .then(function (res) { return res.json().then(function (j) { return { ok: res.ok, body: j }; }); })
                .then(function (r) {
                    if (!r.ok || !r.body.ok) { alert('Gagal menyimpan.'); return; }
                    window.location.reload();
                })
                .catch(function () { alert('Gagal menyimpan.'); });
        }
    </script>
    @endpush
</x-layouts.app>
