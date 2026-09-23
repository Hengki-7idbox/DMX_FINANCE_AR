<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = BankAccount::query();

        if ($request->filled('search')) {
            $query->where('bank_name', 'like', '%' . $request->search . '%')
                  ->orWhere('account_number', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bankAccounts = $query->latest()->paginate(25)->withQueryString();

        return view('bank-accounts.index', compact('bankAccounts'));
    }

    public function create()
    {
        return view('bank-accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:30|unique:bank_accounts,account_number',
            'account_name' => 'required|string|max:100',
            'currency' => 'required|in:IDR,USD,EUR',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        BankAccount::create($validated);

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account berhasil ditambahkan');
    }

    public function show(BankAccount $bankAccount)
    {
        return view('bank-accounts.show', compact('bankAccount'));
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('bank-accounts.edit', compact('bankAccount'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'currency' => 'required|in:IDR,USD,EUR',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $bankAccount->update($validated);

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account berhasil diupdate');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account berhasil dihapus');
    }
}
