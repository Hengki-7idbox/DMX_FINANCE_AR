<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CreditLimit;
use App\Models\Invoice;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function index()
    {
        $customers = Customer::active()
            ->with(['invoices' => fn($q) => $q->outstanding()])
            ->get()
            ->map(fn($c) => [
                'customer' => $c,
                'outstanding' => $c->getTotalOutstanding(),
                'utilization' => $c->getUtilizationPct(),
                'status' => match(true) {
                    $c->getUtilizationPct() >= 95 => 'critical',
                    $c->getUtilizationPct() >= 80 => 'warning',
                    default => 'safe',
                },
            ]);

        return view('credit.index', compact('customers'));
    }

    public function detail(Customer $customer)
    {
        $customer->load(['invoices' => fn($q) => $q->outstanding()->with('payments')]);
        $creditHistory = $customer->creditLimits()->latest('effective_date')->get();

        return view('credit.detail', compact('customer', 'creditHistory'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        CreditLimit::create([
            'customer_id' => $customer->id,
            'credit_limit' => $validated['credit_limit'],
            'effective_date' => now()->toDateString(),
            'approved_by' => auth()->user()->name,
            'notes' => $validated['notes'] ?? null,
        ]);

        $customer->update(['credit_limit' => $validated['credit_limit']]);

        return redirect()->route('credit.detail', $customer)->with('success', 'Credit limit berhasil diupdate.');
    }

    public function forecast(Customer $customer)
    {
        // Simple forecast based on payment history
        $avgPayment = $customer->invoices()
            ->where('status', 'SETTLED')
            ->avg('amount');

        return response()->json([
            'customer' => $customer->name,
            'current_outstanding' => $customer->getTotalOutstanding(),
            'credit_limit' => $customer->credit_limit,
            'utilization_pct' => $customer->getUtilizationPct(),
            'avg_payment' => $avgPayment,
            'estimated_clear_days' => $avgPayment > 0
                ? ceil($customer->getTotalOutstanding() / $avgPayment) * 30
                : null,
        ]);
    }
}
