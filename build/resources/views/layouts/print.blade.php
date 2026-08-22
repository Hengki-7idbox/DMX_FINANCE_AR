<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Report') — AR Finance Tools</title>
    <style>
        * { font-family: 'Inter', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        body { font-size: 12px; color: #1a1a1a; padding: 20mm; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1a1a1a; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; font-weight: 700; }
        .header p { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; font-size: 11px; }
        th { background: #f3f4f6; font-weight: 600; }
        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 10px; color: #666; display: flex; justify-content: space-between; }
        @media print { body { padding: 15mm; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>PT. DMX Trading Indonesia</h1>
            <p>AR Finance Tools — @yield('title', 'Report')</p>
        </div>
        <div style="text-align: right;">
            <p><strong>Tanggal:</strong> {{ date('d/m/Y H:i') }}</p>
            <p><strong>Dicetak oleh:</strong> {{ auth()->user()->name ?? 'System' }}</p>
        </div>
    </div>

    @yield('content')

    <div class="footer">
        <span>AR Finance Tools v1.0</span>
        <span>Halaman 1 dari 1</span>
    </div>
</body>
</html>
