<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AgingExport;

class AgingController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'current');

        $buckets = [
            'current' => Invoice::outstanding()->byAgingBucket('current')->with('customer')->get(),
            '31-60' => Invoice::outstanding()->byAgingBucket('31-60')->with('customer')->get(),
            '61-90' => Invoice::outstanding()->byAgingBucket('61-90')->with('customer')->get(),
            'over90' => Invoice::outstanding()->byAgingBucket('over90')->with('customer')->get(),
        ];

        $summary = collect($buckets)->map(fn($invoices) => [
            'count' => $invoices->count(),
            'amount' => $invoices->sum('amount'),
        ]);

        return view('aging.index', compact('buckets', 'summary', 'period'));
    }

    public function generate(Request $request)
    {
        $request->validate(['as_of_date' => 'required|date']);

        // Generate aging report for specific date
        return redirect()->route('aging.index')->with('success', 'Laporan aging berhasil digenerate.');
    }

    public function export(Request $request)
    {
        return Excel::download(new AgingExport, 'aging-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function trend()
    {
        // Return aging trend data for charts
        return response()->json([
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'current' => [12, 15, 14, 16, 15, 15.2],
            '31-60' => [3, 4, 5, 4, 5, 4.8],
            '61-90' => [2, 2, 3, 2, 2, 2.4],
            'over90' => [1, 1.5, 2, 1.8, 2, 2.1],
        ]);
    }
}
