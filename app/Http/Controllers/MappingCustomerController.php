<?php

namespace App\Http\Controllers;

use App\Imports\MappingCustomersImport;
use App\Models\Customer;
use App\Models\MappingCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class MappingCustomerController extends Controller
{
    public function index()
    {
        $mappingCustomers = MappingCustomer::orderBy('id')->get();

        // Match: kode_internal ada di customers tab internal
        //     DAN kode_external ada di customers tab external
        $internalCodes = Customer::where('source_type', Customer::SOURCE_INTERNAL)
            ->pluck('customer_code')
            ->flip()
            ->all();

        $externalCodes = Customer::where('source_type', Customer::SOURCE_EXTERNAL)
            ->pluck('customer_code')
            ->flip()
            ->all();

        $mappingCustomers->each(function (MappingCustomer $row) use ($internalCodes, $externalCodes) {
            $row->is_match = isset($internalCodes[$row->kode_internal])
                && isset($externalCodes[$row->kode_external]);
        });

        // Satu kode internal idealnya hanya punya satu pasangan di Accurate. Bila lebih dari satu,
        // aplikasi memilih baris pertama dan bisa mengenai customer yang sudah Non Aktif
        // (mis. B004 untuk FAUZIA V-009-C) sehingga pembuatan SO/DO/Invoice ditolak Accurate.
        $duplicateCodes = MappingCustomer::query()
            ->select('kode_internal', DB::raw('COUNT(*) as total'))
            ->groupBy('kode_internal')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('total', 'kode_internal')
            ->all();

        $mappingCustomers->each(function (MappingCustomer $row) use ($duplicateCodes) {
            $row->is_duplicate = isset($duplicateCodes[$row->kode_internal]);
        });

        $duplicateCount = count($duplicateCodes);

        $matchCount = $mappingCustomers->where('is_match', true)->count();
        $notMatchCount = $mappingCustomers->count() - $matchCount;

        return view('mapping-customers.index', compact(
            'mappingCustomers',
            'matchCount',
            'notMatchCount',
            'duplicateCount'
        ));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $import = new MappingCustomersImport();
            Excel::import($import, $request->file('file'));

            $created = $import->getCreatedCount();
            $updated = $import->getUpdatedCount();
            $skipped = $import->getSkippedCount();
            $duplicated = $import->getDuplicateCount();

            if ($created + $updated === 0) {
                return redirect()
                    ->route('mapping-customers.index')
                    ->with('error', 'Tidak ada data terimport. Pastikan format file sesuai template (Kode Internal | Kode External | ...).');
            }

            $message = "Import selesai: {$created} ditambahkan, {$updated} diupdate";
            if ($skipped > 0) {
                $message .= ", {$skipped} baris dilewati";
            }
            $message .= '.';

            if ($duplicated > 0) {
                $message .= " Peringatan: {$duplicated} baris membuat kode internal punya lebih dari satu mapping Customer."
                    . ' Periksa badge "Ganda" lalu hapus mapping yang tidak dipakai.';
            }

            return redirect()
                ->route('mapping-customers.index')
                ->with($duplicated > 0 ? 'error' : 'success', $message);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstErrors = [];
            foreach ($errors as $field => $messages) {
                $firstErrors[] = "{$field}: " . $messages[0];
            }

            return redirect()
                ->route('mapping-customers.index')
                ->with('error', 'Validasi gagal - ' . implode('; ', array_slice($firstErrors, 0, 5)));
        } catch (\Exception $e) {
            return redirect()
                ->route('mapping-customers.index')
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    public function clear()
    {
        $deleted = MappingCustomer::query()->delete();

        if ($deleted === 0) {
            return redirect()
                ->route('mapping-customers.index')
                ->with('error', 'Tidak ada data untuk dihapus.');
        }

        return redirect()
            ->route('mapping-customers.index')
            ->with('success', "Berhasil menghapus {$deleted} data Mapping Customer.");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_id' => 'nullable|integer|min:0',
            'kode_internal' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('mapping_customers', 'kode_internal')
                    ->where('kode_external', $request->input('kode_external')),
            ],
            'kode_external' => 'required|string|max:100',
            'nama_internal' => 'required|string|max:255',
            'nama_external' => 'required|string|max:255',
        ]);

        MappingCustomer::create($validated);

        return redirect()
            ->route('mapping-customers.index')
            ->with('success', 'Mapping customer berhasil ditambahkan.');
    }

    public function update(Request $request, MappingCustomer $mappingCustomer)
    {
        $validated = $request->validate([
            'source_id' => 'nullable|integer|min:0',
            'kode_internal' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('mapping_customers', 'kode_internal')
                    ->where('kode_external', $request->input('kode_external'))
                    ->ignore($mappingCustomer->id),
            ],
            'kode_external' => 'required|string|max:100',
            'nama_internal' => 'required|string|max:255',
            'nama_external' => 'required|string|max:255',
        ]);

        $mappingCustomer->update($validated);

        return redirect()
            ->route('mapping-customers.index')
            ->with('success', 'Mapping customer berhasil diupdate.');
    }

    public function destroy(MappingCustomer $mappingCustomer)
    {
        $mappingCustomer->delete();

        return redirect()
            ->route('mapping-customers.index')
            ->with('success', 'Mapping customer berhasil dihapus.');
    }
}
