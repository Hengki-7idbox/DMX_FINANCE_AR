<?php

namespace App\Http\Controllers;

use App\Models\CoretaxItemCode;
use App\Services\CoretaxItemCodeSync;
use Illuminate\Http\Request;

class CoretaxItemCodeController extends Controller
{
    public function index(Request $request)
    {
        $query = CoretaxItemCode::query();

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term);
            });
        }

        $codes = $query->orderBy('name')->get();

        return view('coretax-item-codes.index', compact('codes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $normalized = CoretaxItemCode::normalizeName($data['name']);

        CoretaxItemCode::updateOrCreate(
            ['name_norm' => $normalized],
            ['code' => $data['code'], 'name' => $data['name']]
        );

        return redirect()
            ->route('coretax-item-codes.index')
            ->with('success', 'Kode Barang/Jasa berhasil disimpan.');
    }

    public function update(Request $request, CoretaxItemCode $coretaxItemCode)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $coretaxItemCode->fill([
            'code' => $data['code'],
            'name' => $data['name'],
            'name_norm' => CoretaxItemCode::normalizeName($data['name']),
        ])->save();

        return redirect()
            ->route('coretax-item-codes.index')
            ->with('success', 'Kode Barang/Jasa berhasil diupdate.');
    }

    public function destroy(CoretaxItemCode $coretaxItemCode)
    {
        $coretaxItemCode->delete();

        return redirect()
            ->route('coretax-item-codes.index')
            ->with('success', 'Kode Barang/Jasa berhasil dihapus.');
    }

    public function clear()
    {
        $deleted = CoretaxItemCode::query()->delete();

        if ($deleted === 0) {
            return redirect()
                ->route('coretax-item-codes.index')
                ->with('error', 'Tidak ada data untuk dihapus.');
        }

        return redirect()
            ->route('coretax-item-codes.index')
            ->with('success', "Berhasil menghapus {$deleted} kode barang/jasa.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        try {
            @ini_set('memory_limit', '-1');
            $result = (new CoretaxItemCodeSync())->importWorkbook($request->file('file')->getRealPath());

            $message = "Import selesai: {$result['created']} ditambahkan, {$result['updated']} diupdate"
                . ", {$result['products']} mapping product diisi kode";

            if ($result['skipped'] > 0) {
                $message .= ", {$result['skipped']} baris dilewati";
            }

            return redirect()
                ->route('coretax-item-codes.index')
                ->with('success', $message . '.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('coretax-item-codes.index')
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }
}
