<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>China Report {{ $year ?? 'Semua Tahun' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; margin: 16px; }
        h2 { font-size: 15px; margin: 0 0 2px; }
        h3 { font-size: 13px; margin: 14px 0 4px; }
        .sub { font-size: 11px; color: #444; margin-bottom: 12px; }
        .no-print { margin-bottom: 12px; }
        .no-print button { padding: 6px 14px; font-size: 12px; cursor: pointer; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 32px; }
        th, td { border: 1px solid #888; padding: 3px 6px; text-align: right; }
        th { background: #eee; }
        th.left, td.left { text-align: left; }
        tr.section td { background: #e8e8e8; font-weight: bold; }
        tfoot td { font-weight: bold; background: #f5f5f5; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            @page { size: landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Print</button></div>

    <h2>China Report - Outstanding per Termin</h2>
    <div class="sub">Tahun {{ $year ?? 'Semua Tahun' }} &nbsp;|&nbsp; *Update on {{ $updatedAt->format('d/m/Y H:i:s') }}</div>

    @forelse($sections as $section)
        <table>
            <thead>
                <tr>
                    <th class="left">Company Code</th>
                    <th class="left">Company Name</th>
                    <th class="left">CR. Term</th>
                    @foreach($months as $m)
                        <th>{{ $m['label'] }}</th>
                    @endforeach
                    <th>{{ $catch['label'] }}</th>
                    <th>Balance</th>
                    <th class="left">Last Pay Date</th>
                </tr>
            </thead>
            <tbody>
                <tr class="section">
                    <td class="left" colspan="{{ 5 + count($months) }}">{{ $section['title'] }}</td>
                </tr>
                @foreach($section['rows'] as $r)
                    <tr>
                        <td class="left">{{ $r['code'] ?: '-' }}</td>
                        <td class="left">{{ $r['name'] ?: '-' }}</td>
                        <td class="left">{{ $r['term'] }}</td>
                        @foreach($months as $m)
                            <td>{{ $r['months'][$m['key']] != 0 ? number_format($r['months'][$m['key']], 0, ',', '.') : '-' }}</td>
                        @endforeach
                        <td>{{ $r['catch'] != 0 ? number_format($r['catch'], 0, ',', '.') : '-' }}</td>
                        <td>{{ number_format($r['balance'], 0, ',', '.') }}</td>
                        <td class="left">-</td>
                    </tr>
                @endforeach
                <tr>
                    <td></td>
                    <td class="left">Subtotal</td>
                    <td></td>
                    @foreach($months as $m)
                        <td>{{ number_format($section['subtotal']['months'][$m['key']], 0, ',', '.') }}</td>
                    @endforeach
                    <td>{{ number_format($section['subtotal']['catch'], 0, ',', '.') }}</td>
                    <td>{{ number_format($section['subtotal']['balance'], 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @empty
        <p>Tidak ada data.</p>
    @endforelse

    @if(count($sections))
        <table>
            <tfoot>
                <tr>
                    <td></td>
                    <td class="left">GRAND TOTAL</td>
                    <td></td>
                    @foreach($months as $m)
                        <td>{{ number_format($grand['months'][$m['key']], 0, ',', '.') }}</td>
                    @endforeach
                    <td>{{ number_format($grand['catch'], 0, ',', '.') }}</td>
                    <td>{{ number_format($grand['balance'], 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif

    @if(count($recap))
        <h3>Recap</h3>
        <table>
            <thead>
                <tr>
                    <th class="left">Kategori</th>
                    @foreach($months as $m)
                        <th>{{ $m['label'] }}</th>
                    @endforeach
                    <th>{{ $catch['label'] }}</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recap as $r)
                    <tr>
                        <td class="left">{{ $r['label'] }}</td>
                        @foreach($months as $m)
                            @php $v = $r['months'][$m['key']]; @endphp
                            <td>
                                @if($v != 0)
                                    {{ number_format($v, 0, ',', '.') }} ({{ number_format($r['pct'][$m['key']], 1, ',', '.') }}%)
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                        <td>
                            @if($r['catch'] != 0)
                                {{ number_format($r['catch'], 0, ',', '.') }}
                                @if($grand['catch'] > 0)({{ number_format($r['catch'] / $grand['catch'] * 100, 1, ',', '.') }}%)@endif
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ number_format($r['total'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="left">Total</td>
                    @foreach($months as $m)
                        <td>{{ number_format($grand['months'][$m['key']], 0, ',', '.') }}</td>
                    @endforeach
                    <td>{{ number_format($grand['catch'], 0, ',', '.') }}</td>
                    <td>{{ number_format($grand['balance'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
</body>
</html>
