<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\CollectionAction;
use Illuminate\Http\Request;

class TrackerController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->with(['customer', 'collectionActions'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->where('invoice_number', 'like', "%{$s}%"))
            ->latest('due_date')
            ->paginate(20);

        return view('tracker.index', compact('invoices'));
    }

    public function detail(Invoice $invoice)
    {
        $invoice->load(['customer', 'collectionActions' => fn($q) => $q->latest('action_date'), 'payments']);

        return view('tracker.detail', compact('invoice'));
    }

    public function addAction(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'action_type' => 'required|in:EMAIL,WA,CALL,NOTE,STATUS_CHANGE',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['action_by'] = auth()->user()->name;
        $validated['action_date'] = now();

        CollectionAction::create($validated);

        return redirect()->route('tracker.detail', $validated['invoice_id'])->with('success', 'Action berhasil ditambahkan.');
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|in:PENDING,SENT,PARTIAL,SETTLED',
        ]);

        $old = $invoice->toArray();
        $invoice->update($validated);

        CollectionAction::create([
            'invoice_id' => $invoice->id,
            'action_type' => 'STATUS_CHANGE',
            'description' => "Status changed from {$old['status']} to {$validated['status']}",
            'action_by' => auth()->user()->name,
            'action_date' => now(),
            'status' => $validated['status'],
        ]);

        return redirect()->route('tracker.detail', $invoice)->with('success', 'Status berhasil diupdate.');
    }

    public function escalate(Invoice $invoice)
    {
        CollectionAction::create([
            'invoice_id' => $invoice->id,
            'action_type' => 'NOTE',
            'description' => 'Escalated to Finance Manager',
            'action_by' => auth()->user()->name,
            'action_date' => now(),
            'notes' => 'Auto-escalated due to overdue > 90 days',
        ]);

        return redirect()->route('tracker.detail', $invoice)->with('success', 'Invoice berhasil di-escalate.');
    }
}
