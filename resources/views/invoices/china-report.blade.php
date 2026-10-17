<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="China Report" description="Matriks outstanding per kategori termin" back-url="{{ route('invoices.index') }}"></x-page-header>
    </x-slot>

    @include('invoices._tabs')

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
        <div class="ml-auto flex items-end gap-3">
            <a href="{{ route('invoices.china-report.export', ['tahun' => $year ?? '']) }}"
               class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Export Excel
            </a>
            <a href="{{ route('invoices.china-report.print', ['tahun' => $year ?? '']) }}" target="_blank"
               class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border bg-background px-3 text-sm font-medium text-foreground hover:bg-accent">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zM7 9V4a2 2 0 012-2h6a2 2 0 012 2v5" /></svg>
                Print Preview
            </a>
        </div>
    </div>

    <x-card>
        <div class="border-b border-border px-3 py-2 text-sm text-muted-foreground">
            *Update on {{ $updatedAt->format('d/m/Y H:i:s') }}
        </div>

        @if(count($sections))
            @foreach($sections as $i => $section)
                <section class="pt-4 {{ !$loop->last ? 'mb-8' : '' }}">
                    <div class="mb-1.5 flex items-baseline justify-between">
                        <h3 class="text-sm font-semibold text-foreground">{{ $section['title'] }}</h3>
                        <span class="text-xs text-muted-foreground">{{ count($section['rows']) }} customer</span>
                    </div>
                    <div class="table-container w-full overflow-x-auto overflow-y-hidden">
                        <table id="china-sec-{{ $i }}" class="w-full min-w-[1500px] caption-bottom text-sm whitespace-nowrap">
                            <thead class="border-b border-border bg-accent/40">
                                <tr>
                                    <th class="h-10 px-3 text-left font-medium text-muted-foreground">Company Code</th>
                                    <th class="h-10 px-3 text-left font-medium text-muted-foreground">Company Name</th>
                                    <th class="h-10 px-3 text-left font-medium text-muted-foreground">CR. Term</th>
                                    @foreach($months as $m)
                                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">{{ $m['label'] }}</th>
                                    @endforeach
                                    <th class="h-10 px-3 text-right font-medium text-muted-foreground">{{ $catch['label'] }}</th>
                                    <th class="h-10 px-3 text-right font-medium text-muted-foreground">Balance</th>
                                    <th class="h-10 px-3 text-left font-medium text-muted-foreground">Last Pay Date</th>
                                </tr>
                            </thead>
                            <tbody>{{-- Baris dirender DataTables dari SEC_DATA --}}</tbody>
                            <tfoot>
                                <tr class="border-t-2 border-border bg-accent/40 font-semibold">
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2 text-foreground">Subtotal</td>
                                    <td class="px-3 py-2"></td>
                                    @foreach($months as $m)
                                        <td class="px-3 py-2 text-right text-foreground">{{ number_format($section['subtotal']['months'][$m['key']], 0, ',', '.') }}</td>
                                    @endforeach
                                    <td class="px-3 py-2 text-right text-foreground">{{ number_format($section['subtotal']['catch'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-foreground">{{ number_format($section['subtotal']['balance'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            @endforeach

            <div class="mt-8 overflow-x-auto">
                <table class="w-full min-w-[1500px] text-sm whitespace-nowrap">
                    <tfoot>
                        <tr class="border-t-2 border-border bg-accent/70 font-semibold">
                            <td class="px-3 py-2"></td>
                            <td class="px-3 py-2 text-foreground">GRAND TOTAL</td>
                            <td class="px-3 py-2"></td>
                            @foreach($months as $m)
                                <td class="px-3 py-2 text-right text-foreground">{{ number_format($grand['months'][$m['key']], 0, ',', '.') }}</td>
                            @endforeach
                            <td class="px-3 py-2 text-right text-foreground">{{ number_format($grand['catch'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-foreground">{{ number_format($grand['balance'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="px-3 py-6 text-center text-muted-foreground">Tidak ada data.</div>
        @endif
    </x-card>

    @if(count($recap))
        <x-card class="mt-4">
            <div class="border-b border-border px-3 py-2 text-sm font-medium text-foreground">Recap</div>
            <div class="table-container w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead class="border-b border-border bg-accent/40">
                        <tr>
                            <th class="h-10 px-3 text-left font-medium text-muted-foreground">Kategori</th>
                            @foreach($months as $m)
                                <th class="h-10 px-3 text-right font-medium text-muted-foreground">{{ $m['label'] }}</th>
                            @endforeach
                            <th class="h-10 px-3 text-right font-medium text-muted-foreground">{{ $catch['label'] }}</th>
                            <th class="h-10 px-3 text-right font-medium text-muted-foreground">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recap as $r)
                            <tr class="border-b border-border hover:bg-accent/40">
                                <td class="px-3 py-1.5 font-medium text-foreground">{{ $r['label'] }}</td>
                                @foreach($months as $m)
                                    @php $v = $r['months'][$m['key']]; @endphp
                                    <td class="px-3 py-1.5 text-right text-foreground">
                                        @if($v != 0)
                                            {{ number_format($v, 0, ',', '.') }} <span class="text-muted-foreground">({{ number_format($r['pct'][$m['key']], 1, ',', '.') }}%)</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-1.5 text-right text-foreground">
                                    @if($r['catch'] != 0)
                                        {{ number_format($r['catch'], 0, ',', '.') }}
                                        @if($grand['catch'] > 0)<span class="text-muted-foreground">({{ number_format($r['catch'] / $grand['catch'] * 100, 1, ',', '.') }}%)</span>@endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-1.5 text-right font-medium text-foreground">{{ number_format($r['total'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-border bg-accent/70 font-semibold">
                            <td class="px-3 py-2 text-foreground">Total</td>
                            @foreach($months as $m)
                                <td class="px-3 py-2 text-right text-foreground">{{ number_format($grand['months'][$m['key']], 0, ',', '.') }}</td>
                            @endforeach
                            <td class="px-3 py-2 text-right text-foreground">{{ number_format($grand['catch'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-foreground">{{ number_format($grand['balance'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
    @endif

    @push('scripts')
    <script>
        function fmtNum(v) {
            if (v === null || v === undefined) return '-';
            return Number(v).toLocaleString('id-ID');
        }
        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        var SEC_DATA = @json(array_values(array_map(fn ($s) => $s['rows'], $sections)));
        var MONTH_KEYS = @json(array_column($months, 'key'));

        document.getElementById('f_tahun').addEventListener('change', function () {
            var p = new URLSearchParams(window.location.search);
            p.set('tahun', this.value);
            window.location.search = p.toString();
        });

        SEC_DATA.forEach(function (rows, i) {
            var columns = [
                { data: 'code', className: 'px-3 py-1.5 font-medium text-foreground max-w-[180px] truncate',
                    render: function (v, type) { v = v || ''; if (type !== 'display') return v; return v === '' ? '-' : '<span title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>'; } },
                { data: 'name', className: 'px-3 py-1.5 font-medium text-foreground max-w-[240px] truncate',
                    render: function (v, type) { v = v || ''; if (type !== 'display') return v; return v === '' ? '-' : '<span title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>'; } },
                { data: 'term', className: 'px-3 py-1.5 text-muted-foreground',
                    render: function (v, type) { v = v || ''; return type === 'display' ? escapeHtml(v) : v; } }
            ];
            MONTH_KEYS.forEach(function (k) {
                columns.push({
                    data: function (row) { return row.months[k] || 0; },
                    className: 'px-3 py-1.5 text-right text-foreground',
                    render: function (v, type) { return type === 'display' ? (v ? fmtNum(v) : '-') : (v || 0); }
                });
            });
            columns.push(
                { data: 'catch', className: 'px-3 py-1.5 text-right text-foreground',
                    render: function (v, type) { return type === 'display' ? (v ? fmtNum(v) : '-') : (v || 0); } },
                { data: 'balance', className: 'px-3 py-1.5 text-right font-medium text-foreground',
                    render: function (v, type) { return type === 'display' ? fmtNum(v) : (v || 0); } },
                { data: null, defaultContent: '-', className: 'px-3 py-1.5 text-muted-foreground', orderable: false }
            );

            $('#china-sec-' + i).DataTable({
                data: rows,
                deferRender: true,
                pageLength: 25,
                order: [],
                searching: true,
                columns: columns,
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
        });
    </script>
    @endpush
</x-layouts.app>
