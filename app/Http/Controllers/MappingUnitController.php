<?php

namespace App\Http\Controllers;

use App\Imports\MappingUnitsImport;
use App\Models\DetailSaleItem;
use App\Models\MappingUnit;
use App\Services\AccurateUnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class MappingUnitController extends Controller
{
    /**
     * Tampilkan seluruh unit Accurate Online + dropdown unit Coretax.
     */
    public function index(Request $request, AccurateUnitService $units)
    {
        if ($request->boolean('refresh')) {
            $units->list(true);
            $error = $units->error();

            return redirect()
                ->route('mapping-units.index')
                ->with($error ? 'error' : 'success', $error ?: 'Daftar unit berhasil dimuat ulang dari Accurate.');
        }

        $accurateUnits = $units->list();
        $accurateError = $units->error();

        $mappings = MappingUnit::query()
            ->get()
            ->keyBy(fn ($row) => (string) $row->unit_internal);

        $mappingByLower = [];
        foreach ($mappings as $name => $mapping) {
            $lower = mb_strtolower(trim((string) $name));
            if ($lower !== '' && ! isset($mappingByLower[$lower])) {
                $mappingByLower[$lower] = (string) $mapping->unit_coretax;
            }
        }

        // Resolusi disamakan dengan CoretaxXmlBuilder: persis dulu, lalu case-insensitive.
        $resolveMapped = function (string $name) use ($mappings, $mappingByLower): ?string {
            if (isset($mappings[$name])) {
                return (string) $mappings[$name]->unit_coretax;
            }

            $lower = mb_strtolower(trim($name));

            return $lower !== '' ? ($mappingByLower[$lower] ?? null) : null;
        };

        $usage = DetailSaleItem::query()
            ->selectRaw('unit, count(*) as total')
            ->groupBy('unit')
            ->pluck('total', 'unit');

        $rows = [];
        $known = [];

        foreach ($accurateUnits as $unit) {
            $name = (string) $unit['name'];
            $known[$name] = true;

            $rows[] = [
                'id' => $unit['id'],
                'name' => $name,
                'in_accurate' => true,
                'usage' => (int) ($usage[$name] ?? 0),
                'mapped' => $resolveMapped($name),
            ];
        }

        // Unit yang muncul di data lokal tetapi tidak ada di master Accurate (mis. Ikt).
        foreach ($usage as $name => $total) {
            $name = trim((string) $name);
            if ($name === '' || isset($known[$name])) {
                continue;
            }

            $known[$name] = true;
            $rows[] = [
                'id' => null,
                'name' => $name,
                'in_accurate' => false,
                'usage' => (int) $total,
                'mapped' => $resolveMapped($name),
            ];
        }

        // Mapping lama yang tidak ada di Accurate maupun di data tetap ditampilkan agar bisa dibersihkan.
        foreach ($mappings as $name => $mapping) {
            $name = (string) $name;
            if ($name === '' || isset($known[$name])) {
                continue;
            }

            $known[$name] = true;
            $rows[] = [
                'id' => null,
                'name' => $name,
                'in_accurate' => false,
                'usage' => 0,
                'mapped' => $mapping->unit_coretax,
            ];
        }

        $coretaxUnits = MappingUnit::CORETAX_UNITS;
        $mappedCount = count(array_filter($rows, fn ($row) => ! empty($row['mapped'])));
        $unmappedCount = count($rows) - $mappedCount;

        // Jangan biarkan browser menyimpan halaman lama: nilai dropdown harus
        // selalu mengikuti isi tabel mapping_units saat halaman dibuka.
        return response()
            ->view('mapping-units.index', compact('rows', 'coretaxUnits', 'accurateError', 'mappedCount', 'unmappedCount'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Simpan satu mapping langsung dari dropdown (AJAX).
     */
    public function quickSave(Request $request)
    {
        $data = $request->validate([
            'unit_internal' => 'required|string|max:50',
            'unit_coretax' => ['nullable', 'string', 'in:' . implode(',', array_keys(MappingUnit::CORETAX_UNITS))],
        ]);

        $unitInternal = trim($data['unit_internal']);
        $unitCoretax = trim((string) ($data['unit_coretax'] ?? ''));

        // Tabel memakai collation case-insensitive, jadi "CRT" dan "Crt" adalah
        // baris yang sama. Cari dulu tanpa membedakan huruf besar/kecil agar nilai
        // selalu tersimpan pada nama yang sudah ada — kalau tidak, baris "CRT"
        // akan tampak kosong lagi setiap halaman di-refresh.
        $existing = MappingUnit::query()
            ->whereRaw('LOWER(unit_internal) = ?', [mb_strtolower($unitInternal)])
            ->first();

        $canonical = $existing->unit_internal ?? $unitInternal;

        if ($unitCoretax === '') {
            if ($existing) {
                $existing->delete();
            }

            Log::info('mapping-units.quick-save', [
                'unit' => $unitInternal,
                'canonical' => $canonical,
                'coretax' => null,
                'action' => 'cleared',
            ]);

            return response()->json([
                'ok' => true,
                'unit_internal' => $canonical,
                'unit_coretax' => null,
            ]);
        }

        if ($existing) {
            $existing->unit_coretax = $unitCoretax;
            $existing->save();
            $mapping = $existing;
        } else {
            $mapping = MappingUnit::create([
                'unit_internal' => $unitInternal,
                'unit_coretax' => $unitCoretax,
            ]);
        }

        Log::info('mapping-units.quick-save', [
            'unit' => $unitInternal,
            'canonical' => $mapping->unit_internal,
            'coretax' => $mapping->unit_coretax,
            'action' => $existing ? 'updated' : 'created',
        ]);

        return response()->json([
            'ok' => true,
            'unit_internal' => $mapping->unit_internal,
            'unit_coretax' => $mapping->unit_coretax,
        ]);
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
