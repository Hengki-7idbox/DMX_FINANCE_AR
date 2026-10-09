<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Data External Tidak Sama" description="Data tab external yang tidak match dengan data internal: {{ $total }} baris ({{ $unmatchCount }} Unmatch, {{ $noPairCount }} tidak ada pasangan).">
        </x-page-header>
    </x-slot>

    {{-- Filter bar: Status / Faktur Pajak / Customer Name / Bulan / Tahun (client-side) --}}
    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-3">
        <div class="w-52">
            <label for="f_status" class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
            <select id="f_status" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                <option value="unmatch">Unmatch</option>
                <option value="no_pair">Tidak ada pasangan</option>
            </select>
        </div>
        <div class="w-40">
            <label for="f_faktur" class="mb-1 block text-xs font-medium text-muted-foreground">Faktur Pajak</label>
            <select id="f_faktur" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                <option value="ada">FP ada</option>
                <option value="belum">fp belum ada</option>
                <option value="gung_gung">Gung Gung</option>
            </select>
        </div>
        <div class="w-64">
            <label for="f_nama" class="mb-1 block text-xs font-medium text-muted-foreground">Customer Name</label>
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
        {{-- Horizontally scrollable data table: card tetap di batas, scroll hanya di wrapper --}}
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="ext-same-table" class="w-full min-w-[1100px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Status</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Faktur Pajak</th>
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
                    {{-- Baris dirender DataTables dari DS_DATA (deferRender) --}}
                </tbody>
            </table>
        </div>
    </x-card>

    @push('scripts')
    <script>
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

        var BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

        var DS_DATA = @json($payload);
        DS_DATA.forEach(function (r) {
            r.bulan = r.transaction_date ? r.transaction_date.slice(5, 7) : '';
            r.tahun = r.transaction_date ? r.transaction_date.slice(0, 4) : '';
        });

        var BADGE_BASE = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ';
        function badgeHtml(status) {
            if (status === 'match') return '<span class="' + BADGE_BASE + 'bg-success/10 text-success">Match</span>';
            if (status === 'unmatch') return '<span class="' + BADGE_BASE + 'bg-info/10 text-info">Unmatch</span>';
            return '<span class="' + BADGE_BASE + 'border border-border text-foreground">Tidak ada pasangan</span>';
        }

        function fpHtml(v) {
            if (v) {
                return '<span class="' + BADGE_BASE + 'bg-success/10 text-success">FP ada</span> '
                    + '<span class="text-foreground">' + escapeHtml(v) + '</span>';
            }
            return '<span class="' + BADGE_BASE + 'bg-error/10 text-error">fp belum ada</span>';
        }

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

        function fillSelect(id, values, labelFn) {
            var sel = document.getElementById(id);
            sel.innerHTML = '';
            values.forEach(function (v) {
                var o = document.createElement('option');
                o.value = v;
                o.textContent = labelFn ? labelFn(v) : v;
                sel.appendChild(o);
            });
        }

        fillSelect('f_bulan', uniqueAttr('bulan'), function (v) {
            return BULAN[parseInt(v, 10) - 1] || v;
        });
        fillSelect('f_tahun', uniqueAttr('tahun'));
        fillSelect('f_nama', uniqueAttr('customer_name'));

        ['f_status', 'f_faktur', 'f_nama', 'f_bulan', 'f_tahun'].forEach(function (id) {
            $('#' + id).select2({ width: '100%', placeholder: 'Semua', minimumResultsForSearch: 0 });
        });

        var filterTable = $('#ext-same-table').DataTable({
            data: DS_DATA,
            deferRender: true,
            pageLength: 25,
            order: [],
            columns: [
                { data: 'status', className: 'px-2 py-1.5',
                    render: function (v, type) { return type === 'display' ? badgeHtml(v) : (v || ''); } },
                { data: 'fp_number', className: 'px-2 py-1.5',
                    render: function (v, type, row) {
                        if (row.fp_status === 'gung_gung') {
                            return type === 'display'
                                ? '<span class="' + BADGE_BASE + 'bg-blue-900 text-blue-300">Gung Gung</span>'
                                : 'Gung Gung';
                        }
                        if (type !== 'display') return v ? 'FP ada ' + v : 'fp belum ada';
                        return fpHtml(v);
                    } },
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

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'ext-same-table') return true;
            var row = filterTable.row(dataIndex).data();
            if (!row) return true;
            var s = $('#f_status').val() || [];
            var fp = $('#f_faktur').val() || [];
            var n = $('#f_nama').val() || [];
            var b = $('#f_bulan').val() || [];
            var t = $('#f_tahun').val() || [];
            if (s.length && s.indexOf(row.status || '') === -1) return false;
            if (b.length && b.indexOf(row.bulan || '') === -1) return false;
            if (t.length && t.indexOf(row.tahun || '') === -1) return false;
            if (n.length && !n.some(function (x) {
                return x.toLowerCase() === (row.customer_name || '').toLowerCase();
            })) return false;
            if (fp.length) {
                var hasFp = !!row.fp_number;
                var isGung = row.fp_status === 'gung_gung';
                if (!((fp.indexOf('ada') !== -1 && hasFp)
                    || (fp.indexOf('belum') !== -1 && !hasFp)
                    || (fp.indexOf('gung_gung') !== -1 && isGung))) return false;
            }
            return true;
        });

        $('#f_status, #f_faktur, #f_nama, #f_bulan, #f_tahun').on('change', function () {
            filterTable.draw();
        });

        // Default: bulan & tahun ini; fallback ke bulan terakhir yang ada data bila kosong.
        (function () {
            var now = new Date();
            var nowB = ('0' + (now.getMonth() + 1)).slice(-2);
            var nowT = String(now.getFullYear());
            var hasNow = DS_DATA.some(function (r) { return r.bulan === nowB && r.tahun === nowT; });

            var defB = nowB;
            var defT = nowT;
            if (!hasNow && DS_DATA.length) {
                var latest = DS_DATA.reduce(function (max, r) {
                    return (r.transaction_date && r.transaction_date > max) ? r.transaction_date : max;
                }, '');
                if (latest) {
                    defT = latest.slice(0, 4);
                    defB = latest.slice(5, 7);
                }
            }

            if ($('#f_bulan option[value="' + defB + '"]').length) {
                $('#f_bulan').val([defB]).trigger('change');
            }
            if ($('#f_tahun option[value="' + defT + '"]').length) {
                $('#f_tahun').val([defT]).trigger('change');
            }
        })();

        $('#f_reset').on('click', function () {
            $('#f_status, #f_faktur, #f_nama, #f_bulan, #f_tahun').val([]).trigger('change');
        });
    </script>
    @endpush
</x-layouts.app>
