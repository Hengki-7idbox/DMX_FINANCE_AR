<?php

namespace App\Http\Controllers;

use App\Models\InvoiceExclusion;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ExclusionImport;

class ExclusionController extends Controller
{
    public function index(Request $request)
    {
        $exclusions = InvoiceExclusion::query()
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->where('invoice_number', 'like', "%{$s}%"))
            ->latest('excluded_date')
            ->paginate(20);

        $stats = [
            'total' => InvoiceExclusion::count(),
            'active' => InvoiceExclusion::active()->count(),
            'reverted' => InvoiceExclusion::reverted()->count(),
        ];

        return view('exclusions.index', compact('exclusions', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:invoice_exclusions,invoice_number',
            'customer_name' => 'nullable|string',
            'original_amount' => 'nullable|numeric',
            'reason_code' => 'required|in:DUPLICATE,GL_ERROR,TEST_INVOICE,DATA_ENTRY_ERROR,WORKFLOW_ERROR,OTHER',
            'reason_detail' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['excluded_by'] = auth()->user()->name;
        $validated['excluded_date'] = now();

        $exclusion = InvoiceExclusion::create($validated);

        AuditLog::log('CREATE', 'invoice_exclusions', $exclusion->id, null, $exclusion->toArray());

        return redirect()->route('exclusions.index')->with('success', 'Invoice berhasil di-exclude.');
    }

    public function revert(InvoiceExclusion $exclusion)
    {
        $old = $exclusion->toArray();
        $exclusion->update([
            'status' => 'REVERTED',
            'revert_by' => auth()->user()->name,
            'revert_date' => now(),
        ]);

        AuditLog::log('REVERT', 'invoice_exclusions', $exclusion->id, $old, $exclusion->toArray());

        return redirect()->route('exclusions.index')->with('success', 'Exclusion berhasil di-revert.');
    }

    public function bulkImport(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);

        Excel::import(new ExclusionImport, $request->file('file'));

        return redirect()->route('exclusions.index')->with('success', 'Bulk import berhasil.');
    }

    public function statistics()
    {
        return response()->json([
            'total' => InvoiceExclusion::count(),
            'active' => InvoiceExclusion::active()->count(),
            'reverted' => InvoiceExclusion::reverted()->count(),
            'by_reason' => InvoiceExclusion::selectRaw('reason_code, count(*) as count')->groupBy('reason_code')->pluck('count', 'reason_code'),
        ]);
    }
}
