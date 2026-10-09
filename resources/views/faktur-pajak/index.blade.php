<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Faktur Pajak" description="Faktur pajak transaksi sales">
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

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    @if ($payload->isEmpty())
        <x-alert type="info" class="mb-4">
            Belum ada data Faktur Pajak. Klik <strong>Import</strong> lalu upload file CSV dari e-Faktur (delimiter koma).
        </x-alert>
    @endif

    {{-- Filter bar: Status / Nama / Bulan / Tahun --}}
    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-3">
        <div class="w-52">
            <label for="f_status" class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
            <select id="f_status" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
            </select>
        </div>
        <div class="w-44">
            <label for="f_balance" class="mb-1 block text-xs font-medium text-muted-foreground">Balance</label>
            <select id="f_balance" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
            </select>
        </div>
        <div class="w-40">
            <label for="f_unique" class="mb-1 block text-xs font-medium text-muted-foreground">Unique</label>
            <select id="f_unique" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
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
    </div>

    <x-card>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="faktur-pajak-table" class="w-full min-w-[1800px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Status</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Balance</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Unique</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Buyer Tin</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Display Name</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Tax Invoice Code</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Tax Invoice Number</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Tax Invoice Date</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Tax Invoice Period</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Tax Invoice Year</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Tax Invoice Status</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">E Sign Status</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Selling Price</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Other Tax Base</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Vat</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Signer</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Reference</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Reported By Seller</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Reported By Vat Collector</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Baris dirender DataTables dari FP_DATA (deferRender) --}}
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Import Modal --}}
    <dialog id="import_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Import Faktur Pajak</h3>
            <p class="py-2 text-sm text-muted-foreground">Upload file <strong>CSV</strong> dari e-Faktur (delimiter koma). Data dengan nomor faktur yang sama akan di-update.</p>

            <form action="{{ route('faktur-pajak.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="py-4">
                    <label for="file" class="block text-sm font-medium text-muted-foreground mb-2">File CSV</label>
                    <input type="file"
                           id="file"
                           name="file"
                           accept=".csv"
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
                Semua data Faktur Pajak akan dihapus permanen.
            </p>
            <div class="modal-action">
                <form action="{{ route('faktur-pajak.clear') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error">Ya, Hapus</button>
                </form>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('clear_modal').close()">Batal</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Edit Reference Modal (klik 2x pada kolom Reference) --}}
    <dialog id="edit_ref_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Edit Reference</h3>
            <p class="py-2 text-sm text-muted-foreground">Ubah nilai reference (mis. hapus suffix tambahan customer). Maks. 50 karakter.</p>
            <div class="py-2">
                <label for="edit_ref_value" class="block text-sm font-medium text-muted-foreground mb-2">Reference</label>
                <input type="text" id="edit_ref_value" maxlength="50"
                       placeholder="DMXB26090411"
                       class="block w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
            </div>
            <div class="modal-action">
                <button type="button" id="edit_ref_save" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('edit_ref_modal').close()">Batal</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Toast feedback --}}
    <div id="fp_toast" class="fixed right-4 bottom-4 z-[200] hidden rounded-lg px-4 py-2 text-sm font-medium text-white shadow-xl"></div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var FP_DATA = @json($payload);

            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function formatRp(value) {
                var n = Number(value || 0);
                return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
            }

            function boolCell(v) {
                if (v === null || v === undefined) return '-';
                return v ? 'TRUE' : 'FALSE';
            }

            var table = $('#faktur-pajak-table').DataTable({
                data: FP_DATA,
                deferRender: true,
                pageLength: 25,
                order: [],
                columns: [
                    { data: 'status', orderable: false, searchable: false, className: 'px-2 py-1.5',
                        render: function (v, type) {
                            if (type !== 'display') return v || '';
                            var match = v === 'match';
                            return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold text-white '
                                + (match ? 'bg-green-800' : 'bg-red-800') + '">'
                                + (match ? 'Match' : 'Unmatch') + '</span>';
                        } },
                    { data: 'balance', orderable: false, searchable: false, className: 'px-2 py-1.5',
                        render: function (v, type) {
                            if (type !== 'display') return (v === null || v === undefined) ? '' : String(v);
                            if (v === null || v === undefined) return '-';
                            return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold text-white '
                                + (v ? 'bg-green-800' : 'bg-red-800') + '">'
                                + (v ? 'Balance' : 'Tidak Balance') + '</span>';
                        } },
                    { data: 'is_unique', orderable: false, searchable: false, className: 'px-2 py-1.5',
                        render: function (v, type) {
                            if (type !== 'display') return v ? '1' : '0';
                            return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold text-white '
                                + (v ? 'bg-green-800' : 'bg-red-800') + '">'
                                + (v ? 'Unique' : 'Double') + '</span>';
                        } },
                    { data: 'buyer_tin', className: 'px-2 py-1.5 text-muted-foreground',
                        render: function (v, type) { if (type !== 'display') return v || ''; return v ? escapeHtml(v) : '-'; } },
                    { data: 'display_name', className: 'px-2 py-1.5 text-foreground',
                        render: function (v, type) { if (type !== 'display') return v || ''; return v ? escapeHtml(v) : '-'; } },
                    { data: 'tax_invoice_code', className: 'px-2 py-1.5 max-w-[280px] truncate text-foreground',
                        render: function (v, type) { v = v || ''; if (type !== 'display') return v; return v === '' ? '-' : '<span title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>'; } },
                    { data: 'tax_invoice_number', className: 'px-2 py-1.5 font-medium text-foreground',
                        render: function (v, type) { if (type !== 'display') return v || ''; return v ? escapeHtml(v) : '-'; } },
                    { data: 'date', className: 'px-2 py-1.5 text-muted-foreground',
                        render: function (v, type) {
                            if (type !== 'display') return v || '';
                            if (!v) return '-';
                            var p = String(v).split('-');
                            return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : escapeHtml(v);
                        } },
                    { data: 'period', className: 'px-2 py-1.5 text-muted-foreground',
                        render: function (v, type) { if (type !== 'display') return v || ''; return v ? escapeHtml(v) : '-'; } },
                    { data: 'tahun', className: 'px-2 py-1.5 text-muted-foreground',
                        render: function (v, type) { if (type !== 'display') return v || ''; return v ? escapeHtml(v) : '-'; } },
                    { data: 'tax_invoice_status', className: 'px-2 py-1.5 text-foreground',
                        render: function (v, type) { if (type !== 'display') return v || ''; return v ? escapeHtml(v) : '-'; } },
                    { data: 'e_sign_status', className: 'px-2 py-1.5 text-foreground',
                        render: function (v, type) { if (type !== 'display') return v || ''; return v ? escapeHtml(v) : '-'; } },
                    { data: 'selling_price', className: 'px-2 py-1.5 text-right text-foreground',
                        render: function (v, type) { if (type !== 'display') return v; return formatRp(v); } },
                    { data: 'other_tax_base', className: 'px-2 py-1.5 text-right text-foreground',
                        render: function (v, type) { if (type !== 'display') return v; return formatRp(v); } },
                    { data: 'vat', className: 'px-2 py-1.5 text-right text-foreground',
                        render: function (v, type) { if (type !== 'display') return v; return formatRp(v); } },
                    { data: 'signer', className: 'px-2 py-1.5 text-foreground',
                        render: function (v, type) { if (type !== 'display') return v || ''; return v ? escapeHtml(v) : '-'; } },
                    { data: 'reference', className: 'px-2 py-1.5 text-foreground',
                        render: function (v, type, row) {
                            if (type !== 'display') return v || '';
                            var label = v ? escapeHtml(v) : '-';
                            return '<span class="reference-cell cursor-pointer select-none decoration-dotted underline-offset-2 hover:underline" '
                                + 'data-id="' + row.id + '" title="Klik 2x untuk edit reference">' + label + '</span>';
                        } },
                    { data: 'reported_by_seller', className: 'px-2 py-1.5 text-muted-foreground',
                        render: function (v, type) { if (type !== 'display') return v; return boolCell(v); } },
                    { data: 'reported_by_vat_collector', className: 'px-2 py-1.5 text-muted-foreground',
                        render: function (v, type) { if (type !== 'display') return v; return boolCell(v); } }
                ],
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

            function uniqueAttr(key) {
                var seen = {};
                FP_DATA.forEach(function (r) {
                    var v = r[key] || '';
                    if (v) seen[v] = true;
                });
                return Object.keys(seen).sort(function (a, b) {
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

            fillSelect('f_status', uniqueAttr('status'), function(v) {
                return v === 'match' ? 'Match' : v === 'unmatch' ? 'Unmatch' : v;
            });
            fillSelect('f_balance', ['true', 'false', 'null'], function(v) {
                return v === 'true' ? 'Balance' : v === 'false' ? 'Tidak Balance' : '–';
            });
            fillSelect('f_unique', ['true', 'false'], function(v) {
                return v === 'true' ? 'Unique' : 'Double';
            });
            fillSelect('f_nama', uniqueAttr('display_name'));
            fillSelect('f_bulan', uniqueAttr('period'));
            fillSelect('f_tahun', uniqueAttr('tahun'));

            var MAX_SEL = { f_bulan: 12, f_tahun: 4 };
            ['f_status', 'f_balance', 'f_unique', 'f_nama', 'f_bulan', 'f_tahun'].forEach(function(id) {
                $('#' + id).select2({
                    width: '100%',
                    placeholder: 'Semua',
                    minimumResultsForSearch: 0,
                    maximumSelectionLength: MAX_SEL[id] || 0
                });
            });

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'faktur-pajak-table') return true;
                var row = table.row(dataIndex).data();
                if (!row) return true;
                var s = $('#f_status').val() || [];
                var bal = $('#f_balance').val() || [];
                var uni = $('#f_unique').val() || [];
                var n = $('#f_nama').val() || [];
                var b = $('#f_bulan').val() || [];
                var t = $('#f_tahun').val() || [];
                var d1 = $('#f_tgl_dari').val() || '';
                var d2 = $('#f_tgl_sampai').val() || '';
                if (s.length && s.indexOf(row.status || '') === -1) return false;
                if (bal.length && bal.indexOf(String(row.balance)) === -1) return false;
                if (uni.length && uni.indexOf(String(row.is_unique)) === -1) return false;
                if (b.length && b.indexOf(row.period || '') === -1) return false;
                if (t.length && t.indexOf(row.tahun || '') === -1) return false;
                if (n.length && n.indexOf(row.display_name || '') === -1) return false;
                var d = row.date || '';
                if (d1 && (!d || d < d1)) return false;
                if (d2 && (!d || d > d2)) return false;
                return true;
            });

            $('#f_status, #f_balance, #f_unique, #f_nama, #f_bulan, #f_tahun').on('change', function() {
                table.draw();
            });

            $('#f_tgl_dari, #f_tgl_sampai').on('change', function() {
                if ($('#f_tgl_dari').val() || $('#f_tgl_sampai').val()) {
                    // Tanggal menimpa bulan/tahun — kosongkan lalu draw via handler change
                    $('#f_bulan, #f_tahun').val([]).trigger('change');
                    return;
                }
                table.draw();
            });

            $('#f_reset').on('click', function() {
                $('#f_status, #f_balance, #f_unique, #f_nama, #f_bulan, #f_tahun').val([]).trigger('change');
                $('#f_tgl_dari, #f_tgl_sampai').val('');
            });

            // ==== Edit reference via klik 2x ====
            var EDIT_REF_URL = @json(route('faktur-pajak.reference'));
            var EDIT_REF_CSRF = @json(csrf_token());
            var editRefId = null;
            var fpToastTimer = null;

            function showFpToast(message, ok) {
                var t = document.getElementById('fp_toast');
                t.textContent = message;
                t.className = 'fixed right-4 bottom-4 z-[200] rounded-lg px-4 py-2 text-sm font-medium text-white shadow-xl '
                    + (ok ? 'bg-success' : 'bg-destructive');
                t.classList.remove('hidden');
                if (fpToastTimer) clearTimeout(fpToastTimer);
                fpToastTimer = setTimeout(function () { t.classList.add('hidden'); }, 2500);
            }

            $('#faktur-pajak-table').on('dblclick', '.reference-cell', function () {
                var id = Number(this.getAttribute('data-id'));
                var row = null;
                for (var i = 0; i < FP_DATA.length; i++) {
                    if (FP_DATA[i].id === id) { row = FP_DATA[i]; break; }
                }
                if (!row) return;
                editRefId = id;
                var input = document.getElementById('edit_ref_value');
                input.value = row.reference || '';
                document.getElementById('edit_ref_modal').showModal();
                input.focus();
                input.select();
            });

            document.getElementById('edit_ref_save').addEventListener('click', function () {
                if (editRefId === null) return;
                var value = document.getElementById('edit_ref_value').value;
                fetch(EDIT_REF_URL, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': EDIT_REF_CSRF
                    },
                    body: JSON.stringify({ id: editRefId, reference: value })
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(function () {
                        document.getElementById('edit_ref_modal').close();
                        showFpToast('Tersimpan — memuat ulang...', true);
                        setTimeout(function () { window.location.reload(); }, 600);
                    })
                    .catch(function () {
                        showFpToast('Gagal menyimpan', false);
                    });
            });
        });
    </script>
    @endpush
</x-layouts.app>
