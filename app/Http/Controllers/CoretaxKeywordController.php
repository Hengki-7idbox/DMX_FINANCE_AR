<?php

namespace App\Http\Controllers;

use App\Models\CoretaxKeyword;
use Illuminate\Http\Request;

class CoretaxKeywordController extends Controller
{
    public function index()
    {
        $keywords = CoretaxKeyword::orderBy('id')->get();

        return view('coretax-keywords.index', compact('keywords'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kata' => 'required|string|max:255',
        ]);

        $kata = trim($data['kata']);
        CoretaxKeyword::updateOrCreate(['kata' => $kata], ['kata' => $kata]);

        return redirect()
            ->route('coretax-keywords.index')
            ->with('success', "Kata kunci \"{$kata}\" berhasil disimpan.");
    }

    public function destroy(CoretaxKeyword $coretaxKeyword)
    {
        $kata = $coretaxKeyword->kata;
        $coretaxKeyword->delete();

        return redirect()
            ->route('coretax-keywords.index')
            ->with('success', "Kata kunci \"{$kata}\" berhasil dihapus.");
    }

    public function clear()
    {
        $deleted = CoretaxKeyword::query()->delete();

        if ($deleted === 0) {
            return redirect()
                ->route('coretax-keywords.index')
                ->with('error', 'Tidak ada data untuk dihapus.');
        }

        return redirect()
            ->route('coretax-keywords.index')
            ->with('success', "Berhasil menghapus {$deleted} kata kunci.");
    }
}
