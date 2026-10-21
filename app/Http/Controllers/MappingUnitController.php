<?php

namespace App\Http\Controllers;

use App\Imports\MappingUnitsImport;
use App\Models\MappingUnit;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class MappingUnitController extends Controller
{
    public function index()
    {
        $mappingUnits = MappingUnit::orderBy('id')->get();

        return view('mapping-units.index', compact('mappingUnits'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_internal' => 'required|string|max:50',
            'unit_coretax' => 'required|string|max:50',
        ]);

        MappingUnit::updateOrCreate(['unit_internal' => $data['unit_internal']], $data);

        return redirect()
            ->route('mapping-units.index')
            ->with('success', 'Data mapping unit berhasil disimpan.');
    }

    public function update(Request $request, MappingUnit $mappingUnit)
    {
        $data = $request->validate([
            'unit_internal' => 'required|string|max:50',
            'unit_coretax' => 'required|string|max:50',
        ]);

        $mappingUnit->fill($data)->save();

        return redirect()
            ->route('mapping-units.index')
            ->with('success', 'Data mapping unit berhasil diupdate.');
    }

    public function destroy(MappingUnit $mappingUnit)
    {
        $label = $mappingUnit->unit_internal . ' → ' . $mappingUnit->unit_coretax;
        $mappingUnit->delete();

        return redirect()
            ->route('mapping-units.index')
            ->with('success', "Mapping unit {$label} berhasil dihapus.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $import = new MappingUnitsImport();
            Excel::import($import, $request->file('file'));

            $created = $import->getCreatedCount();
            $updated = $import->getUpdatedCount();
            $skipped = $import->getSkippedCount();

            if ($created + $updated === 0) {
                return redirect()
                    ->route('mapping-units.index')
                    ->with('error', 'Tidak ada data terimport. Pastikan format file sesuai (Unit Internal | Unit Coretax).');
            }

            $message = "Import selesai: {$created} ditambahkan, {$updated} diupdate";
            if ($skipped > 0) {
                $message .= ", {$skipped} baris dilewati";
            }

            return redirect()
                ->route('mapping-units.index')
                ->with('success', $message . '.');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstErrors = [];
            foreach ($errors as $field => $messages) {
                $firstErrors[] = "{$field}: " . $messages[0];
            }

            return redirect()
                ->route('mapping-units.index')
                ->with('error', 'Validasi gagal - ' . implode('; ', array_slice($firstErrors, 0, 5)));
        } catch (\Exception $e) {
            return redirect()
                ->route('mapping-units.index')
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    public function clear()
    {
        $deleted = MappingUnit::query()->delete();

        if ($deleted === 0) {
            return redirect()
                ->route('mapping-units.index')
                ->with('error', 'Tidak ada data untuk dihapus.');
        }

        return redirect()
            ->route('mapping-units.index')
            ->with('success', "Berhasil menghapus {$deleted} data mapping unit.");
    }
}
