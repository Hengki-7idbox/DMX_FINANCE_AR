<?php

namespace App\Http\Controllers;

use App\Models\CustomerGroup;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerGroup::withCount('customers');

        if ($request->filled('search')) {
            $query->where('group_name', 'like', '%' . $request->search . '%')
                  ->orWhere('group_code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $groups = $query->latest()->paginate(25)->withQueryString();

        return view('customer-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('customer-groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_code' => 'required|string|max:20|unique:customer_groups,group_code',
            'group_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        CustomerGroup::create($validated);

        return redirect()->route('customer-groups.index')
            ->with('success', 'Customer group berhasil ditambahkan');
    }

    public function show(CustomerGroup $customerGroup)
    {
        $customerGroup->load('customers');

        return view('customer-groups.show', ['group' => $customerGroup]);
    }

    public function edit(CustomerGroup $customerGroup)
    {
        return view('customer-groups.edit', ['group' => $customerGroup]);
    }

    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $customerGroup->update($validated);

        return redirect()->route('customer-groups.index')
            ->with('success', 'Customer group berhasil diupdate');
    }

    public function destroy(CustomerGroup $customerGroup)
    {
        $customerGroup->delete();

        return redirect()->route('customer-groups.index')
            ->with('success', 'Customer group berhasil dihapus');
    }
}
