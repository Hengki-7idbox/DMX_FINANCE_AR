<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Rekonsiliasi Harian Internal" description="Cek penerimaan dokumen internal per hari — tandai status terima (Belum/Sudah/Online/Cash) dan isi catatan">
        </x-page-header>
    </x-slot>

    {{-- Ringkasan + filter tanggal --}}
    <x-card class="mb-4">
        <div class="flex flex-wrap items-center justify-between gap-4 p-4">
            <div class="flex items-center gap-3">
                <label for="filter_date" class="text-sm font-medium text-foreground">Tanggal</label>
                <input type="date" id="filter_date" value="{{ $date }}"
                       class="rounded-lg border border-border bg-card px-3 py-1.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                <span class="text-xs text-muted-foreground">otomatis hari ini saat pertama dibuka</span>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <span class="inline-flex items-center rounded-full border border-border px-2.5 py-0.5 text-xs font-semibold text-foreground">
                    Total: <span class="ml-1">{{ $total }}</span> dokumen
                </span>
                <span class="inline-flex items-center rounded-full bg-success/10 px-2.5 py-0.5 text-xs font-semibold text-success">
                    Sudah: <span id="count_received" class="ml-1">{{ $receivedCount }}</span>
                </span>
                <span class="inline-flex items-center rounded-full bg-warning/10 px-2.5 py-0.5 text-xs font-semibold text-warning">
                    Belum: <span id="count_pending" class="ml-1">{{ $pendingCount }}</span>
                </span>
                <span class="inline-flex items-center rounded-full bg-info/10 px-2.5 py-0.5 text-xs font-semibold text-info">
                    Online: <span id="count_online" class="ml-1">{{ $onlineCount }}</span>
                </span>
                <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                    Cash: <span id="count_cash" class="ml-1">{{ $cashCount }}</span>
                </span>
                <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                    Sudah Tukar: <span id="count_exchanged" class="ml-1">{{ $exchangedCount }}</span>
                </span>
                <span class="inline-flex items-center rounded-full bg-warning/10 px-2.5 py-0.5 text-xs font-semibold text-warning">
                    Belum Tukar: <span id="count_not_exchanged" class="ml-1">{{ $notExchangedCount }}</span>
                </span>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-muted-foreground">Progress Penerimaan</span>
                    <div class="h-2 w-32 overflow-hidden rounded-full bg-muted">
                        <div id="penerimaan_bar" class="h-full rounded-full bg-success transition-all"
                             style="width: {{ $total > 0 ? round(($onlineCount + $cashCount) / $total * 100) : 0 }}%"></div>
                    </div>
                    <span id="penerimaan_pct" class="text-xs font-medium text-muted-foreground">{{ $total > 0 ? round(($onlineCount + $cashCount) / $total * 100) : 0 }}%</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-muted-foreground">Progress Tukar Faktur</span>
                    <div class="h-2 w-32 overflow-hidden rounded-full bg-muted">
                        <div id="tukar_bar" class="h-full rounded-full bg-success transition-all"
                             style="width: {{ $total > 0 ? round($exchangedCount / $total * 100) : 0 }}%"></div>
                    </div>
                    <span id="tukar_pct" class="text-xs font-medium text-muted-foreground">{{ $total > 0 ? round($exchangedCount / $total * 100) : 0 }}%</span>
                </div>
            </div>
        </div>
    </x-card>

    <x-card>
        @if ($total === 0)
            <div class="p-8 text-center text-sm text-muted-foreground">
                Tidak ada dokumen internal dengan tanggal <strong>{{ date('d/m/Y', strtotime($date)) }}</strong>.
            </div>
        @else
            {{-- Bulk action: muncul hanya saat minimal 1 invoice dipilih --}}
            <div id="bulk_bar" class="hidden items-center gap-3 border-b border-border px-4 py-2">
                <span id="bulk_count" class="text-sm font-medium text-foreground">0 data dipilih</span>
                <div class="dropdown dropdown-start">
                    <div tabindex="0" role="button" class="btn btn-sm btn-outline">Ubah Status Terima ▼</div>
                    <ul tabindex="0" class="dropdown-content menu rounded-box bg-base-100 z-30 w-48 border border-border p-2 shadow-lg">
                        <li><a data-receive-status="0">Belum</a></li>
                        <li><a data-receive-status="3">Cash</a></li>
                        <li><a data-receive-status="2">Online</a></li>
                    </ul>
                </div>
                <div class="dropdown dropdown-start">
                    <div tabindex="0" role="button" class="btn btn-sm btn-outline">Ubah Tukar Faktur ▼</div>
                    <ul tabindex="0" class="dropdown-content menu rounded-box bg-base-100 z-30 w-48 border border-border p-2 shadow-lg">
                        <li><a data-exchange-status="0">Belum Tukar</a></li>
                        <li><a data-exchange-status="1">Sudah Tukar</a></li>
                    </ul>
                </div>
            </div>

            <div class="table-container w-full overflow-x-auto overflow-y-hidden">
                <table id="receipt-table" class="w-full min-w-[1450px] caption-bottom text-sm whitespace-nowrap">
                    <thead class="border-b border-border">
                        <tr>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">
                                <input type="checkbox" id="select_all_rows"
                                       class="h-4 w-4 rounded border-border text-primary focus-visible:outline-none focus-visible:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                       aria-label="Pilih semua baris" />
                            </th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">Invoice No.</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">Tanggal</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">Due Date</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">Due Date Manual</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">Customer</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">Nilai Invoice</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">Sudah Terima</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">Tukar Faktur</th>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Baris dirender DataTables dari DS_DATA (deferRender) --}}
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    {{-- Toast feedback --}}
    <div id="receipt_toast" class="fixed right-4 bottom-4 z-[200] hidden rounded-lg px-4 py-2 text-sm font-medium text-white shadow-xl"></div>

    {{-- Konfirmasi bulk update --}}
    <x-modal id="bulk_confirm_modal" :closeable="false">
        <h3 id="bulk_confirm_title" class="text-base font-semibold text-foreground"></h3>
        <p id="bulk_confirm_msg" class="mt-2 text-sm text-muted-foreground"></p>
        <x-slot name="footer">
            <button type="button" id="bulk_confirm_cancel" class="btn btn-ghost">Batal</button>
            <button type="button" id="bulk_confirm_ok" class="btn btn-error">Ya, Ubah</button>
        </x-slot>
    </x-modal>

    @push('scripts')
        <script>
            var DS_DATA = @json($payload);
                var RECEIPT_URL = @json(route('olah-data.rekonsiliasi-harian-internal.receipt'));
                var CSRF_TOKEN = @json(csrf_token());

                var STATUS_LABELS = ['Belum', 'Sudah', 'Online', 'Cash'];
                var EXCHANGE_LABELS = ['Belum Tukar', 'Sudah Tukar'];
                var BULK_RECEIPT_URL = @json(route('olah-data.rekonsiliasi-harian-internal.bulk-receipt'));
                var BULK_EXCHANGE_URL = @json(route('olah-data.rekonsiliasi-harian-internal.bulk-exchange'));

                function statusClass(v) {
                    if (v === 1) return 'border-success text-success font-medium';
                    if (v === 2) return 'border-info text-info font-medium';
                    if (v === 3) return 'border-primary text-primary font-medium';
                    return 'border-blue-900 text-blue-900';
                }

                function exchangeClass(v) {
                    return v ? 'border-primary text-primary font-medium' : 'border-blue-900 text-blue-900';
                }

                // ==== Pilihan baris (by id — persist antar halaman/pencarian) ====
                var selectedIds = new Set();
                var receiptTable = null;

                function updateBulkBar() {
                    var bar = document.getElementById('bulk_bar');
                    if (!bar) return;
                    var n = selectedIds.size;
                    document.getElementById('bulk_count').textContent = n + ' data dipilih';
                    if (n > 0) {
                        bar.classList.remove('hidden');
                        bar.classList.add('flex');
                    } else {
                        bar.classList.add('hidden');
                        bar.classList.remove('flex');
                    }
                }

                function updateSelectAllState() {
                    var el = document.getElementById('select_all_rows');
                    if (!el || !receiptTable) return;
                    var all = 0, sel = 0;
                    receiptTable.rows({ search: 'applied' }).every(function () {
                        all++;
                        if (selectedIds.has(this.data().id)) sel++;
                    });
                    el.checked = all > 0 && sel === all;
                    el.indeterminate = sel > 0 && sel < all;
                }

                function redrawTable() {
                    if (receiptTable) {
                        receiptTable.clear();
                        receiptTable.rows.add(DS_DATA);
                        receiptTable.draw(false);
                    }
                }

                function resetSelection() {
                    selectedIds.clear();
                    var el = document.getElementById('select_all_rows');
                    if (el) {
                        el.checked = false;
                        el.indeterminate = false;
                    }
                    redrawTable();
                    updateBulkBar();
                }

                function formatRp(value) {
                    var n = Number(value || 0);
                    return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
                }

                function formatDue(v) {
                    if (!v) return null;
                    var p = String(v).split('-');
                    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : v;
                }

                function escapeHtml(str) {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                }

                var toastTimer = null;
                function showToast(message, ok) {
                    var t = document.getElementById('receipt_toast');
                    t.textContent = message;
                    t.className = 'fixed right-4 bottom-4 z-[200] rounded-lg px-4 py-2 text-sm font-medium text-white shadow-xl '
                        + (ok ? 'bg-success' : 'bg-destructive');
                    t.classList.remove('hidden');
                    if (toastTimer) clearTimeout(toastTimer);
                    toastTimer = setTimeout(function () { t.classList.add('hidden'); }, 2500);
                }

                function refreshSummary() {
                    var counts = [0, 0, 0, 0];
                    var exchanged = 0;
                    DS_DATA.forEach(function (r) {
                        var s = Number(r.receive_status) || 0;
                        counts[s] = (counts[s] || 0) + 1;
                        if (Number(r.is_exchanged) === 1) exchanged++;
                    });
                    var total = DS_DATA.length;
                    var penerimaan = counts[2] + counts[3];
                    var pctP = total > 0 ? Math.round(penerimaan / total * 100) : 0;
                    var pctT = total > 0 ? Math.round(exchanged / total * 100) : 0;
                    document.getElementById('count_received').textContent = counts[1];
                    document.getElementById('count_pending').textContent = counts[0];
                    document.getElementById('count_online').textContent = counts[2];
                    document.getElementById('count_cash').textContent = counts[3];
                    document.getElementById('count_exchanged').textContent = exchanged;
                    document.getElementById('count_not_exchanged').textContent = total - exchanged;
                    document.getElementById('penerimaan_bar').style.width = pctP + '%';
                    document.getElementById('penerimaan_pct').textContent = pctP + '%';
                    document.getElementById('tukar_bar').style.width = pctT + '%';
                    document.getElementById('tukar_pct').textContent = pctT + '%';
                }

                function putReceipt(doc, body, onOk) {
                    fetch(RECEIPT_URL, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify(Object.assign({ doc_number: doc }, body))
                    })
                        .then(function (res) {
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            return res.json();
                        })
                        .then(function () {
                            if (onOk) onOk();
                            showToast('Tersimpan', true);
                        })
                        .catch(function () {
                            showToast('Gagal menyimpan', false);
                        });
                }

            function findRow(doc) {
                for (var i = 0; i < DS_DATA.length; i++) {
                    if (DS_DATA[i].doc === doc) return DS_DATA[i];
                }
                return null;
            }

            if (document.getElementById('receipt-table')) {
                receiptTable = $('#receipt-table').DataTable({
                    data: DS_DATA,
                    deferRender: true,
                    pageLength: 25,
                    order: [],
                    columns: [
                        { data: null, orderable: false, searchable: false, className: 'px-3 py-1.5',
                            render: function (d, type) {
                                if (type !== 'display') return '';
                                return '<input type="checkbox" class="row-check h-4 w-4 rounded border-border text-primary focus-visible:outline-none focus-visible:ring-2 focus:ring-ring" value="' + d.id + '"'
                                    + (selectedIds.has(d.id) ? ' checked' : '')
                                    + ' aria-label="Pilih baris ' + escapeHtml(d.doc) + '" />';
                            } },
                        { data: 'doc', className: 'px-3 py-1.5 text-foreground',
                            render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
                        { data: 'date', className: 'px-3 py-1.5 text-muted-foreground',
                            render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
                        { data: 'due_date', className: 'px-3 py-1.5 text-muted-foreground',
                            render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
                        { data: 'due_date_manual', orderable: false, searchable: false, className: 'px-3 py-1.5',
                            render: function (v, type, row) {
                                if (type !== 'display') return v || '';
                                return '<input type="date" class="due-manual-input rounded-lg border border-border bg-card px-2 py-1 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"'
                                    + ' data-doc="' + escapeHtml(row.doc) + '"'
                                    + ' value="' + escapeHtml(v || '') + '"'
                                    + ' aria-label="Due date manual ' + escapeHtml(row.doc) + '" />';
                            } },
                        { data: 'customer', className: 'px-3 py-1.5 max-w-[200px] truncate text-foreground',
                            render: function (v, type) { v = v || ''; if (type !== 'display') return v; return v === '' ? '-' : '<span title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>'; } },
                        { data: 'invoice_amount', className: 'px-3 py-1.5 text-foreground',
                            render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                        { data: 'receive_status', orderable: false, searchable: false, className: 'px-3 py-1.5',
                            render: function (v, type, row) {
                                var s = Number(v) || 0;
                                if (type !== 'display') return STATUS_LABELS[s] || '';
                                return '<select class="received-select rounded-lg border bg-card px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring ' + statusClass(s) + '" data-doc="' + escapeHtml(row.doc) + '">'
                                    + '<option value="0"' + (s === 0 ? ' selected' : '') + '>Belum</option>'
                                    + '<option value="1"' + (s === 1 ? ' selected' : '') + '>Sudah</option>'
                                    + '<option value="2"' + (s === 2 ? ' selected' : '') + '>Online</option>'
                                    + '<option value="3"' + (s === 3 ? ' selected' : '') + '>Cash</option>'
                                    + '</select>';
                            } },
                        { data: 'is_exchanged', orderable: false, searchable: false, className: 'px-3 py-1.5',
                            render: function (v, type, row) {
                                var s = Number(v) || 0;
                                if (type !== 'display') return EXCHANGE_LABELS[s] || '';
                                return '<select class="exchange-select rounded-lg border bg-card px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring ' + exchangeClass(s) + '" data-doc="' + escapeHtml(row.doc) + '">'
                                    + '<option value="0"' + (s === 0 ? ' selected' : '') + '>Belum Tukar</option>'
                                    + '<option value="1"' + (s === 1 ? ' selected' : '') + '>Sudah Tukar</option>'
                                    + '</select>';
                            } },
                        { data: 'note', orderable: false, searchable: false, className: 'px-3 py-1.5',
                            render: function (v, type, row) {
                                if (type !== 'display') return v || '';
                                return '<textarea rows="2" data-doc="' + escapeHtml(row.doc) + '" placeholder="Catatan..."'
                                    + ' class="note-input w-64 rounded-lg border border-border bg-card px-2 py-1 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring whitespace-normal">'
                                    + escapeHtml(v || '')
                                    + '</textarea>';
                            } }
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

                receiptTable.on('draw.dt', function () {
                    updateSelectAllState();
                });
                updateBulkBar();
                updateSelectAllState();

                // Select All: pilih/batalkan semua baris yang lolos pencarian.
                var selectAllEl = document.getElementById('select_all_rows');
                if (selectAllEl) {
                    selectAllEl.addEventListener('change', function () {
                        var checked = this.checked;
                        receiptTable.rows({ search: 'applied' }).every(function () {
                            var id = this.data().id;
                            if (checked) selectedIds.add(id);
                            else selectedIds.delete(id);
                        });
                        redrawTable();
                        updateSelectAllState();
                        updateBulkBar();
                    });
                }
            }

                document.addEventListener('change', function (e) {
                    var el = e.target;

                    // Pilih/batal pilih satu baris
                    if (el.classList && el.classList.contains('row-check')) {
                        var id = Number(el.value);
                        if (el.checked) selectedIds.add(id);
                        else selectedIds.delete(id);
                        updateSelectAllState();
                        updateBulkBar();
                        return;
                    }

                    // Status Sudah Terima (per baris)
                    if (el.classList && el.classList.contains('received-select')) {
                        var doc = el.getAttribute('data-doc');
                        var val = Number(el.value) || 0;
                        var row = findRow(doc);
                        if (row) row.receive_status = val;
                        el.className = 'received-select rounded-lg border bg-card px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring ' + statusClass(val);
                        putReceipt(doc, { receive_status: val }, refreshSummary);
                        return;
                    }

                    // Status Tukar Faktur (per baris)
                    if (el.classList && el.classList.contains('exchange-select')) {
                        var docEx = el.getAttribute('data-doc');
                        var valEx = Number(el.value) || 0;
                        var rowEx = findRow(docEx);
                        if (rowEx) rowEx.is_exchanged = valEx;
                        el.className = 'exchange-select rounded-lg border bg-card px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring ' + exchangeClass(valEx);
                        putReceipt(docEx, { is_exchanged: valEx }, refreshSummary);
                    }

                    // Due Date Manual (per baris): jika diisi → due date efektif = manual
                    if (el.classList && el.classList.contains('due-manual-input')) {
                        var docDd = el.getAttribute('data-doc');
                        var valDd = el.value || null;
                        var rowDd = findRow(docDd);
                        if (rowDd) {
                            rowDd.due_date_manual = valDd;
                            rowDd.due_date = valDd ? formatDue(valDd) : (rowDd.due_date_acc || null);
                        }
                        putReceipt(docDd, { due_date_manual: valDd });
                        if (receiptTable) receiptTable.draw(false);
                    }
                });

                // ==== Bulk action ====
                var bulkConfirmCb = null;

                function openBulkConfirm(title, message, cb) {
                    document.getElementById('bulk_confirm_title').textContent = title;
                    document.getElementById('bulk_confirm_msg').textContent = message;
                    bulkConfirmCb = cb;
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'bulk_confirm_modal' }));
                }

                function closeBulkConfirm() {
                    bulkConfirmCb = null;
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'bulk_confirm_modal' }));
                }

                document.getElementById('bulk_confirm_cancel').addEventListener('click', closeBulkConfirm);
                document.getElementById('bulk_confirm_ok').addEventListener('click', function () {
                    var cb = bulkConfirmCb;
                    closeBulkConfirm();
                    if (cb) cb();
                });

                document.addEventListener('click', function (e) {
                    var t = e.target;
                    if (!t.getAttribute) return;

                    if (t.hasAttribute('data-receive-status')) {
                        var st = Number(t.getAttribute('data-receive-status'));
                        openBulkConfirm(
                            'Ubah Status Penerimaan',
                            'Anda akan mengubah status ' + selectedIds.size + ' invoice menjadi "' + STATUS_LABELS[st] + '".',
                            function () { applyBulkReceive(st); }
                        );
                    } else if (t.hasAttribute('data-exchange-status')) {
                        var ex = Number(t.getAttribute('data-exchange-status'));
                        openBulkConfirm(
                            'Ubah Status Tukar Faktur',
                            'Anda akan mengubah status ' + selectedIds.size + ' invoice menjadi "' + EXCHANGE_LABELS[ex] + '".',
                            function () { applyBulkExchange(ex); }
                        );
                    }
                });

                function applyBulkReceive(status) {
                    var ids = Array.from(selectedIds);
                    if (!ids.length) return;
                    fetch(BULK_RECEIPT_URL, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify({ ids: ids, receive_status: status })
                    })
                        .then(function (res) {
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            return res.json();
                        })
                        .then(function () {
                            DS_DATA.forEach(function (r) {
                                if (selectedIds.has(r.id)) r.receive_status = status;
                            });
                            refreshSummary();
                            resetSelection();
                            showToast(ids.length + ' invoice berhasil diubah menjadi ' + STATUS_LABELS[status] + '.', true);
                        })
                        .catch(function () {
                            showToast('Gagal mengubah data', false);
                        });
                }

                function applyBulkExchange(status) {
                    var ids = Array.from(selectedIds);
                    if (!ids.length) return;
                    fetch(BULK_EXCHANGE_URL, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify({ ids: ids, is_exchanged: status })
                    })
                        .then(function (res) {
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            return res.json();
                        })
                        .then(function () {
                            DS_DATA.forEach(function (r) {
                                if (selectedIds.has(r.id)) r.is_exchanged = status;
                            });
                            refreshSummary();
                            resetSelection();
                            showToast(ids.length + ' invoice berhasil diubah menjadi ' + EXCHANGE_LABELS[status] + '.', true);
                        })
                        .catch(function () {
                            showToast('Gagal mengubah data', false);
                        });
                }

                document.addEventListener('focusout', function (e) {
                    var ta = e.target;
                    if (!ta.classList || !ta.classList.contains('note-input')) return;
                    var doc = ta.getAttribute('data-doc');
                    var val = ta.value;
                    var row = findRow(doc);
                    if (row && row.note === val) return;
                    if (row) row.note = val;
                    putReceipt(doc, { received_note: val });
                });

                document.getElementById('filter_date').addEventListener('change', function () {
                    if (this.value) {
                        window.location.href = @json(route('olah-data.rekonsiliasi-harian-internal')) + '?date=' + encodeURIComponent(this.value);
                    }
                });
        </script>
    @endpush
</x-layouts.app>
