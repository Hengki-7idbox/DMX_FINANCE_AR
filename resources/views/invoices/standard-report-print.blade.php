<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Standard Report {{ $year ?? 'Semua Tahun' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; margin: 16px; }
        h2 { font-size: 15px; margin: 0 0 2px; }
        .sub { font-size: 11px; color: #444; margin-bottom: 12px; }
        .no-print { margin-bottom: 12px; }
        .no-print button { padding: 6px 14px; font-size: 12px; cursor: pointer; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #888; padding: 3px 6px; text-align: right; }
        th { background: #eee; }
        th.left, td.left { text-align: left; }
        tfoot td { font-weight: bold; background: #f5f5f5; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            @page { size: landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print</button>
    </div>

    <h2>Standard Report — Aging</h2>
    <div class="sub">Tahun {{ $year ?? 'Semua Tahun' }}</div>

    <table>
        <thead>
            <tr>
                <th class="left">Company Code</th>
                <th class="left">Company Name</th>
                <th class="left">Suspanded</th>
                <th>Credit Limit</th>
                <th>Not Yet Due</th>
                <th>COD</th>
                <th>1-30 Days</th>
                <th>31-60 Days</th>
                <th>61-90 Days</th>
                <th>91-120 Days</th>
                <th>&gt; 120 Days</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="left">{{ $r['code'] ?: '-' }}</td>
                    <td class="left">{{ $r['name'] ?: '-' }}</td>
                    <td class="left">{{ $r['suspended'] ?: '-' }}</td>
                    <td>{{ $r['credit'] === null ? '-' : number_format($r['credit'], 0, ',', '.') }}</td>
                    <td>{{ number_format($r['not_yet_due'], 0, ',', '.') }}</td>
                    <td>{{ number_format($r['cod'], 0, ',', '.') }}</td>
                    <td>{{ number_format($r['d1_30'], 0, ',', '.') }}</td>
                    <td>{{ number_format($r['d31_60'], 0, ',', '.') }}</td>
                    <td>{{ number_format($r['d61_90'], 0, ',', '.') }}</td>
                    <td>{{ number_format($r['d91_120'], 0, ',', '.') }}</td>
                    <td>{{ number_format($r['d120'], 0, ',', '.') }}</td>
                    <td>{{ number_format($r['total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="12" class="left">Tidak ada data untuk tahun {{ $year }}.</td></tr>
            @endforelse
        </tbody>
        @if(count($rows))
        <tfoot>
            <tr>
                <td class="left">Grand Total</td>
                <td class="left"></td>
                <td class="left"></td>
                <td>{{ number_format($grand['credit'], 0, ',', '.') }}</td>
                <td>{{ number_format($grand['not_yet_due'], 0, ',', '.') }}</td>
                <td>{{ number_format($grand['cod'], 0, ',', '.') }}</td>
                <td>{{ number_format($grand['d1_30'], 0, ',', '.') }}</td>
                <td>{{ number_format($grand['d31_60'], 0, ',', '.') }}</td>
                <td>{{ number_format($grand['d61_90'], 0, ',', '.') }}</td>
                <td>{{ number_format($grand['d91_120'], 0, ',', '.') }}</td>
                <td>{{ number_format($grand['d120'], 0, ',', '.') }}</td>
                <td>{{ number_format($grand['total'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
