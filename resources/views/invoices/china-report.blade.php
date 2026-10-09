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

    @php $colCount = 4 + count($months); @endphp

    <x-card>
        <div class="border-b border-border px-3 py-2 text-sm text-muted-foreground">
            *Update on {{ $updatedAt->format('d/m/Y H:i:s') }}
        </div>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table class="w-full text-sm whitespace-nowrap">
                @forelse($sections as $section)
                    <thead class="border-b border-border bg-accent/40">
                        <tr>
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
                    <tbody>
                        <tr class="border-b border-border bg-accent/70">
                            <td colspan="{{ $colCount }}" class="h-9 px-3 font-semibold text-foreground">{{ $section['title'] }}</td>
                        </tr>
                        @foreach($section['rows'] as $r)
                            <tr class="border-b border-border hover:bg-accent/40">
                                <td class="px-3 py-1.5 font-medium text-foreground max-w-[240px] truncate" title="{{ $r['name'] }}">{{ $r['name'] ?: '-' }}</td>
                                <td class="px-3 py-1.5 text-muted-foreground">{{ $r['term'] }}</td>
                                @foreach($months as $m)
                                    <td class="px-3 py-1.5 text-right text-foreground">{{ $r['months'][$m['key']] != 0 ? number_format($r['months'][$m['key']], 0, ',', '.') : '-' }}</td>
                                @endforeach
                                <td class="px-3 py-1.5 text-right text-foreground">{{ $r['catch'] != 0 ? number_format($r['catch'], 0, ',', '.') : '-' }}</td>
                                <td class="px-3 py-1.5 text-right font-medium text-foreground">{{ number_format($r['balance'], 0, ',', '.') }}</td>
                                <td class="px-3 py-1.5 text-muted-foreground">-</td>
                            </tr>
                        @endforeach
                        <tr class="border-b-2 border-border bg-accent/40 font-semibold">
                            <td class="px-3 py-2 text-foreground">Subtotal</td>
                            <td class="px-3 py-2"></td>
                            @foreach($months as $m)
                                <td class="px-3 py-2 text-right text-foreground">{{ number_format($section['subtotal']['months'][$m['key']], 0, ',', '.') }}</td>
                            @endforeach
                            <td class="px-3 py-2 text-right text-foreground">{{ number_format($section['subtotal']['catch'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-foreground">{{ number_format($section['subtotal']['balance'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2"></td>
                        </tr>
                    </tbody>
                @empty
                    <thead class="border-b border-border bg-accent/40">
                        <tr>
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
                    <tbody>
                        <tr><td colspan="{{ $colCount }}" class="px-3 py-6 text-center text-muted-foreground">Tidak ada data.</td></tr>
                    </tbody>
                @endforelse
                @if(count($sections))
                    <tfoot>
                        <tr class="border-t-2 border-border bg-accent/70 font-semibold">
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
                @endif
            </table>
        </div>
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
        document.getElementById('f_tahun').addEventListener('change', function () {
            var p = new URLSearchParams(window.location.search);
            p.set('tahun', this.value);
            window.location.search = p.toString();
        });
    </script>
    @endpush
</x-layouts.app>
