<?php

namespace App\Http\Controllers;

use App\Exports\CustomerTemplateExport;
use App\Imports\CustomersImport;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\PaymentTerm;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $tab = $this->resolveTab($request);

        $query = Customer::with(['customerGroup', 'paymentTerm'])
            ->where('source_type', $tab);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_code', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('group_id')) {
            $query->where('customer_group_id', $request->group_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->latest()->paginate(25)->withQueryString();
        $groups = CustomerGroup::where('status', 'ACTIVE')->get();

        return view('customers.index', compact('customers', 'groups', 'tab'));
    }

    public function create(Request $request)
    {
        $tab = $this->resolveTab($request);
        $groups = CustomerGroup::where('status', 'ACTIVE')->get();
        $paymentTerms = PaymentTerm::where('status', 'ACTIVE')->get();

        return view('customers.create', compact('groups', 'paymentTerms', 'tab'));
    }

    public function store(Request $request)
    {
        $tab = $this->resolveTab($request);

        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['internal', 'external'])],
            'customer_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'customer_code')
                    ->where('source_type', $request->input('source_type', $tab)),
            ],
            'customer_name' => 'required|string|max:255',
            'customer_group_id' => 'required|exists:customer_groups,id',
            'customer_type' => 'required|in:DISTRIBUTOR,CORPORATE,RETAIL,GOVERNMENT,RESELLER,INTERNAL',
            'tax_number' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'payment_term_id' => 'required|exists:payment_terms,id',
            'credit_limit' => 'required|numeric|min:0',
            'status' => 'required|in:ACTIVE,INACTIVE',
            'notes' => 'nullable|string|max:500',
        ]);

        Customer::create($validated);

        return redirect()
            ->route('customers.index', ['tab' => $validated['source_type']])
            ->with('success', 'Customer berhasil ditambahkan');
    }

    public function show(Customer $customer)
    {
        $customer->load(['customerGroup', 'paymentTerm', 'invoices' => function ($query) {
            $query->latest()->limit(10);
        }]);

        $tab = $customer->source_type;

        return view('customers.show', compact('customer', 'tab'));
    }

    public function edit(Request $request, Customer $customer)
    {
        $tab = $customer->source_type;
        $groups = CustomerGroup::where('status', 'ACTIVE')->get();
        $paymentTerms = PaymentTerm::where('status', 'ACTIVE')->get();

        return view('customers.edit', compact('customer', 'groups', 'paymentTerms', 'tab'));
    }

    public function update(Request $request, Customer $customer)
    {
        $tab = $customer->source_type;

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_group_id' => 'required|exists:customer_groups,id',
            'customer_type' => 'required|in:DISTRIBUTOR,CORPORATE,RETAIL,GOVERNMENT,RESELLER,INTERNAL',
            'tax_number' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'payment_term_id' => 'required|exists:payment_terms,id',
            'credit_limit' => 'required|numeric|min:0',
            'status' => 'required|in:ACTIVE,INACTIVE',
            'notes' => 'nullable|string|max:500',
        ]);

        $customer->update($validated);

        return redirect()
            ->route('customers.index', ['tab' => $tab])
            ->with('success', 'Customer berhasil diupdate');
    }

    public function destroy(Request $request, Customer $customer)
    {
        $tab = $customer->source_type;
        $customer->delete();

        return redirect()
            ->route('customers.index', ['tab' => $tab])
            ->with('success', 'Customer berhasil dihapus');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'source_type' => ['required', Rule::in(['internal', 'external'])],
        ]);

        $tab = $request->input('source_type');

        try {
            $import = new CustomersImport($tab);
            Excel::import($import, $request->file('file'));

            $created = $import->getCreatedCount();
            $updated = $import->getUpdatedCount();
            $skipped = $import->getSkippedCount();

            $message = "Import selesai: {$created} ditambahkan, {$updated} diupdate";
            if ($skipped > 0) {
                $message .= ", {$skipped} baris dilewati";
            }
            $message .= '.';

            return redirect()
                ->route('customers.index', ['tab' => $tab])
                ->with('success', $message);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstErrors = [];
            foreach ($errors as $field => $messages) {
                $firstErrors[] = "{$field}: " . $messages[0];
            }

            return redirect()
                ->route('customers.index', ['tab' => $tab])
                ->with('error', 'Validasi gagal - ' . implode('; ', array_slice($firstErrors, 0, 5)));
        } catch (\Exception $e) {
            return redirect()
                ->route('customers.index', ['tab' => $tab])
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    public function importTemplate()
    {
        return Excel::download(new CustomerTemplateExport(), 'template-import-customer.xlsx');
    }

    private function resolveTab(Request $request): string
    {
        $tab = $request->query('tab', $request->input('tab', Customer::SOURCE_EXTERNAL));

        return in_array($tab, [Customer::SOURCE_INTERNAL, Customer::SOURCE_EXTERNAL], true)
            ? $tab
            : Customer::SOURCE_EXTERNAL;
    }
}
