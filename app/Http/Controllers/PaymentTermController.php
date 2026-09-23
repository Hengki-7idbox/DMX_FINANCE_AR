<?php

namespace App\Http\Controllers;

use App\Models\PaymentTerm;
use Illuminate\Http\Request;

class PaymentTermController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentTerm::query();

        if ($request->filled('search')) {
            $query->where('term_name', 'like', '%' . $request->search . '%')
                  ->orWhere('term_code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paymentTerms = $query->latest()->paginate(25)->withQueryString();

        return view('payment-terms.index', compact('paymentTerms'));
    }

    public function create()
    {
        return view('payment-terms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'term_code' => 'required|string|max:20|unique:payment_terms,term_code',
            'term_name' => 'required|string|max:100',
            'days' => 'required|integer|min:0',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        PaymentTerm::create($validated);

        return redirect()->route('payment-terms.index')
            ->with('success', 'Payment term berhasil ditambahkan');
    }

    public function show(PaymentTerm $paymentTerm)
    {
        $paymentTerm->load('customers');

        return view('payment-terms.show', compact('paymentTerm'));
    }

    public function edit(PaymentTerm $paymentTerm)
    {
        return view('payment-terms.edit', compact('paymentTerm'));
    }

    public function update(Request $request, PaymentTerm $paymentTerm)
    {
        $validated = $request->validate([
            'term_name' => 'required|string|max:100',
            'days' => 'required|integer|min:0',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $paymentTerm->update($validated);

        return redirect()->route('payment-terms.index')
            ->with('success', 'Payment term berhasil diupdate');
    }

    public function destroy(PaymentTerm $paymentTerm)
    {
        $paymentTerm->delete();

        return redirect()->route('payment-terms.index')
            ->with('success', 'Payment term berhasil dihapus');
    }
}
