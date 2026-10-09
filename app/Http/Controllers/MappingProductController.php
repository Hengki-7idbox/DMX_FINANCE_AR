<?php

namespace App\Http\Controllers;

use App\Imports\MappingProductsImport;
use App\Models\MappingProduct;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class MappingProductController extends Controller
{
    public function index()
    {
        $mappingProducts = MappingProduct::orderBy('id')->get();

        return view('mapping-products.index', compact('mappingProducts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_id' => 'nullable|integer|min:0',
            'kode_item_internal' => 'required|string|max:100',
            'kode_item_external' => 'required|string|max:100',
            'code_coretax' => 'nullable|string|max:100',
            'nama_item_internal' => 'required|string|max:255',
            'nama_item_external' => 'required|string|max:255',
        ]);

        MappingProduct::create($data);

        return redirect()
            ->route('mapping-products.index')
            ->with('success', 'Data mapping product berhasil ditambahkan.');
    }

    public function update(Request $request, MappingProduct $mappingProduct)
    {
        $data = $request->validate([
            'source_id' => 'nullable|integer|min:0',
            'kode_item_internal' => 'required|string|max:100',
            'kode_item_external' => 'required|string|max:100',
            'code_coretax' => 'nullable|string|max:100',
            'nama_item_internal' => 'required|string|max:255',
            'nama_item_external' => 'required|string|max:255',
        ]);

        $mappingProduct->fill($data)->save();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Data mapping product berhasil diupdate.',
                'mappingProduct' => $mappingProduct->fresh(),
            ]);
        }

        return redirect()
            ->route('mapping-products.index')
            ->with('success', 'Data mapping product berhasil diupdate.');
    }

    public function destroy(MappingProduct $mappingProduct)
    {
        $label = $mappingProduct->kode_item_internal . ' → ' . $mappingProduct->kode_item_external;
        $mappingProduct->delete();

        return redirect()
            ->route('mapping-products.index')
            ->with('success', "Mapping product {$label} berhasil dihapus.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $import = new MappingProductsImport();
            Excel::import($import, $request->file('file'));

            $created = $import->getCreatedCount();
            $updated = $import->getUpdatedCount();
            $skipped = $import->getSkippedCount();

            if ($created + $updated === 0) {
                return redirect()
                    ->route('mapping-products.index')
                    ->with('error', 'Tidak ada data terimport. Pastikan format file sesuai template (ID | Item Code Internal | Item Code external | Code Coretax | Nama Barang Internal | Nama Barang external).');
            }

            $message = "Import selesai: {$created} ditambahkan, {$updated} diupdate";
            if ($skipped > 0) {
                $message .= ", {$skipped} baris dilewati";
            }
            $message .= '.';

            return redirect()
                ->route('mapping-products.index')
                ->with('success', $message);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstErrors = [];
            foreach ($errors as $field => $messages) {
                $firstErrors[] = "{$field}: " . $messages[0];
            }

            return redirect()
                ->route('mapping-products.index')
                ->with('error', 'Validasi gagal - ' . implode('; ', array_slice($firstErrors, 0, 5)));
        } catch (\Exception $e) {
            return redirect()
                ->route('mapping-products.index')
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    public function clear()
    {
        $deleted = MappingProduct::query()->delete();

        if ($deleted === 0) {
            return redirect()
                ->route('mapping-products.index')
                ->with('error', 'Tidak ada data untuk dihapus.');
        }

        return redirect()
            ->route('mapping-products.index')
            ->with('success', "Berhasil menghapus {$deleted} data mapping product.");
    }
}
