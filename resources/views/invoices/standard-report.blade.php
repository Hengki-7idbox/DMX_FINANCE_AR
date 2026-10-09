<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Standard Report" description="Aging piutang per customer" back-url="{{ route('invoices.index') }}"></x-page-header>
    </x-slot>

    @include('invoices._tabs')

    {{-- Filter + aksi --}}
    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-3">
        <div class="w-36">
            <label for="f_tahun" class="mb-1 block text-xs font-medium text-muted-foreground">Tahun</label>
            <select id="f_tahun"
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                <option value="" @selected($year === null)>Semua Tahun</option>
                @foreach($years as $y)
                    <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-52">
            <label for="f_code" class="mb-1 block text-xs font-medium text-muted-foreground">Company Code</label>
            <select id="f_code" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"></select>
        </div>
        <div class="w-64">
            <label for="f_name" class="mb-1 block text-xs font-medium text-muted-foreground">Company Name</label>
            <select id="f_name" multiple
                    class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"></select>
        </div>
        <div class="w-40">
            <label for="f_susp" class="mb-1 block text-xs font-medium text-muted-foreground">Suspanded</label>
            <select id="f_susp" multiple
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
            <button type="button" id="btn_export"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Export Excel
            </button>
            <button type="button" id="btn_print"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border bg-background px-3 text-sm font-medium text-foreground hover:bg-accent">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zM7 9V4a2 2 0 012-2h6a2 2 0 012 2v5" /></svg>
                Print Preview
            </button>
        </div>
    </div>

    <x-card>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="standard-report-table" class="w-full min-w-[1200px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground">Company Code</th>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground">Company Name</th>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground">Suspanded</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">Credit Limit</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">Not Yet Due</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">COD</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">1-30 Days</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">31-60 Days</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">61-90 Days</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">91-120 Days</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">&gt; 120 Days</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">Total</th>
                    </tr>
                </thead>
                <tbody>{{-- Baris dirender DataTables dari DS_DATA --}}</tbody>
                <tfoot>
                    <tr class="border-t-2 border-border bg-accent/40 font-semibold">
                        <td class="px-3 py-2 text-foreground">Grand Total</td>
                        <td class="px-3 py-2"></td>
                        <td class="px-3 py-2"></td>
                        <td class="px-3 py-2 text-right text-foreground"></td>
                        <td class="px-3 py-2 text-right text-foreground"></td>
                        <td class="px-3 py-2 text-right text-foreground"></td>
                        <td class="px-3 py-2 text-right text-foreground"></td>
                        <td class="px-3 py-2 text-right text-foreground"></td>
                        <td class="px-3 py-2 text-right text-foreground"></td>
                        <td class="px-3 py-2 text-right text-foreground"></td>
                        <td class="px-3 py-2 text-right text-foreground"></td>
                        <td class="px-3 py-2 text-right text-foreground"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>

    @push('scripts')
    <script>
        function fmtNum(v) {
            if (v === null || v === undefined) return '-';
            return Number(v).toLocaleString('id-ID');
        }
        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        var DS_DATA = @json($rows);

        function uniqueAttr(key) {
            var seen = {};
            DS_DATA.forEach(function (r) { var v = r[key] || ''; if (v) seen[v] = true; });
            return Object.keys(seen).sort(function (a, b) { return a.toLowerCase().localeCompare(b.toLowerCase()); });
        }
        function fillSelect(id, values) {
            var sel = document.getElementById(id);
            sel.innerHTML = '';
            values.forEach(function (v) {
                var o = document.createElement('option');
                o.value = v; o.textContent = v; sel.appendChild(o);
            });
        }

        fillSelect('f_code', uniqueAttr('code'));
        fillSelect('f_name', uniqueAttr('name'));
        fillSelect('f_susp', uniqueAttr('suspended'));

        ['f_code', 'f_name', 'f_susp'].forEach(function (id) {
            $('#' + id).select2({ width: '100%', placeholder: 'Semua', minimumResultsForSearch: 0 });
        });

        var filterTable = $('#standard-report-table').DataTable({
            data: DS_DATA,
            deferRender: true,
            pageLength: 25,
            order: [],
            columns: [
                { data: 'code', className: 'px-3 py-1.5 text-foreground',
                    render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
                { data: 'name', className: 'px-3 py-1.5 font-medium text-foreground max-w-[220px] truncate',
                    render: function (v, type) { v = v || ''; if (type !== 'display') return v; return v === '' ? '-' : '<span title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>'; } },
                { data: 'suspended', className: 'px-3 py-1.5 text-muted-foreground',
                    render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
                { data: 'credit', className: 'px-3 py-1.5 text-right text-foreground',
                    render: function (v, type) { if (type !== 'display') return v === null ? '' : v; return v === null ? '-' : fmtNum(v); } },
                { data: 'not_yet_due', className: 'px-3 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? fmtNum(v) : v; } },
                { data: 'cod', className: 'px-3 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? fmtNum(v) : v; } },
                { data: 'd1_30', className: 'px-3 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? fmtNum(v) : v; } },
                { data: 'd31_60', className: 'px-3 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? fmtNum(v) : v; } },
                { data: 'd61_90', className: 'px-3 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? fmtNum(v) : v; } },
                { data: 'd91_120', className: 'px-3 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? fmtNum(v) : v; } },
                { data: 'd120', className: 'px-3 py-1.5 text-right text-foreground', render: function (v, type) { return type === 'display' ? fmtNum(v) : v; } },
                { data: 'total', className: 'px-3 py-1.5 text-right font-medium text-foreground', render: function (v, type) { return type === 'display' ? fmtNum(v) : v; } }
            ],
            footerCallback: function () {
                var api = this.api();
                var num = function (i) { return typeof i === 'number' ? i : (i === null || i === undefined || i === '' ? 0 : Number(i) || 0); };
                for (var c = 3; c <= 11; c++) {
                    var sum = api.column(c, { search: 'applied' }).data().reduce(function (a, b) { return num(a) + num(b); }, 0);
                    $(api.column(c).footer()).html(fmtNum(sum));
                }
            },
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
            if (settings.nTable.id !== 'standard-report-table') return true;
            var row = filterTable.row(dataIndex).data();
            if (!row) return true;
            var c = $('#f_code').val() || [];
            var n = $('#f_name').val() || [];
            var s = $('#f_susp').val() || [];
            if (c.length && c.indexOf(row.code || '') === -1) return false;
            if (n.length && n.indexOf(row.name || '') === -1) return false;
            if (s.length && s.indexOf(row.suspended || '') === -1) return false;
            return true;
        });

        $('#f_code, #f_name, #f_susp').on('change', function () { filterTable.draw(); });

        $('#f_reset').on('click', function () {
            $('#f_code, #f_name, #f_susp').val([]).trigger('change');
        });

        document.getElementById('f_tahun').addEventListener('change', function () {
            var p = new URLSearchParams(window.location.search);
            p.set('tahun', this.value);
            window.location.search = p.toString();
        });

        // Export & Print mengirim filter aktif (search + Code/Name/Suspended) ke server.
        var EXPORT_URL = @json(route('invoices.standard-report.export'));
        var PRINT_URL = @json(route('invoices.standard-report.print'));

        function buildReportQuery() {
            var p = new URLSearchParams();
            var cur = new URLSearchParams(window.location.search);
            if (cur.has('tahun')) p.set('tahun', cur.get('tahun'));
            var q = filterTable.search();
            if (q) p.set('q', q);
            ($('#f_code').val() || []).forEach(function (v) { p.append('code[]', v); });
            ($('#f_name').val() || []).forEach(function (v) { p.append('name[]', v); });
            ($('#f_susp').val() || []).forEach(function (v) { p.append('susp[]', v); });
            return p.toString();
        }

        document.getElementById('btn_export').addEventListener('click', function () {
            window.location = EXPORT_URL + '?' + buildReportQuery();
        });
        document.getElementById('btn_print').addEventListener('click', function () {
            window.open(PRINT_URL + '?' + buildReportQuery(), '_blank');
        });
    </script>
    @endpush
</x-layouts.app>
