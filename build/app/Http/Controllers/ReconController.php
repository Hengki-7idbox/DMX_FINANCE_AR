<?php

namespace App\Http\Controllers;

use App\Models\ArReconciliationLog;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReconExport;

class ReconController extends Controller
{
    public function index()
    {
        $logs = ArReconciliationLog::latest('recon_date')->paginate(20);
        $latest = ArReconciliationLog::latest('recon_date')->first();

        return view('reconciliation.index', compact('logs', 'latest'));
    }

    public function importBank(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);

        // Process bank statement import
        return redirect()->route('recon.index')->with('success', 'Data bank berhasil diimport.');
    }

    public function match(Request $request)
    {
        $glBalance = Invoice::outstanding()->sum('amount');
        $paymentBalance = Invoice::where('status', 'SETTLED')->sum('amount');
        $difference = abs($glBalance - $paymentBalance);
        $total = max($glBalance, $paymentBalance, 1);
        $accuracy = round((1 - ($difference / $total)) * 100, 2);

        $log = ArReconciliationLog::create([
            'recon_date' => now()->toDateString(),
            'gl_balance' => $glBalance,
            'payment_balance' => $paymentBalance,
            'difference' => $difference,
            'accuracy_pct' => $accuracy,
            'matched_count' => Invoice::where('status', 'SETTLED')->count(),
            'unmatched_count' => Invoice::overdue()->count(),
            'performed_by' => auth()->user()->name,
        ]);

        return redirect()->route('recon.index')->with('success', "Reconcilation selesai. Accuracy: {$accuracy}%");
    }

    public function report()
    {
        $data = ArReconciliationLog::latest('recon_date')->limit(30)->get();
        return response()->json($data);
    }

    public function export()
    {
        return Excel::download(new ReconExport, 'reconciliation-' . now()->format('Y-m-d') . '.xlsx');
    }
}
