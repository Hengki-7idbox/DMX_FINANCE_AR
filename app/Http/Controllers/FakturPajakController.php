<?php

namespace App\Http\Controllers;

use App\Imports\FakturPajakImport;
use App\Models\DetailSale;
use App\Models\FakturPajak;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FakturPajakController extends Controller
{
    public function index()
    {
        $rows = FakturPajak::query()
            ->orderByDesc('tax_invoice_date')
            ->orderByDesc('id')
            ->get();

        // Map detail-sales tab external: doc_number -> amount + tax_base (utk badge Status & Balance).
        $external = DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)
            ->get(['doc_number', 'amount', 'tax_base'])
            ->keyBy('doc_number');

        // Frekuensi reference (utk badge Unique/Double).
        $refCounts = $rows->pluck('reference')->countBy();

        $payload = $rows->map(function ($r) use ($external, $refCounts) {
            $ext = $external->get($r->reference);
            $isMatch = $ext !== null;
            $isBalance = $isMatch
                && (float) $ext->amount === (float) $r->selling_price
                && (float) $ext->tax_base === (float) $r->vat;

            return [
                'id' => $r->id,
                'status' => $isMatch ? 'match' : 'unmatch',
                'balance' => $isMatch ? $isBalance : null,
                'is_unique' => ($refCounts[$r->reference] ?? 0) <= 1,
                'buyer_tin' => $r->buyer_tin,
                'display_name' => $r->display_name,
                'tax_invoice_code' => $r->tax_invoice_code,
                'tax_invoice_number' => $r->tax_invoice_number,
                'date' => $r->tax_invoice_date?->format('Y-m-d'),
                'period' => $r->tax_invoice_period,
                'tahun' => $r->tax_invoice_year !== null ? (string) $r->tax_invoice_year : '',
                'tax_invoice_status' => $r->tax_invoice_status,
                'e_sign_status' => $r->e_sign_status,
                'selling_price' => (float) $r->selling_price,
                'other_tax_base' => (float) $r->other_tax_base,
                'vat' => (float) $r->vat,
                'signer' => $r->signer,
                'reference' => $r->reference,
                'reported_by_seller' => $r->reported_by_seller,
                'reported_by_vat_collector' => $r->reported_by_vat_collector,
            ];
        })->values();

        return view('faktur-pajak.index', ['payload' => $payload]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $import = new FakturPajakImport();
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return redirect()
                ->route('faktur-pajak.index')
                ->with('error', 'Gagal membaca file CSV. Pastikan file CSV (delimiter koma) dari e-Faktur. Detail: ' . $e->getMessage());
        }

        $message = sprintf(
            'Import Faktur Pajak selesai: %d baru, %d diupdate, %d tidak berubah.',
            $import->getCreatedCount(),
            $import->getUpdatedCount(),
            $import->getUnchangedCount()
        );

        if ($import->getSkippedCount() > 0) {
            $message .= sprintf(' %d baris dilewati.', $import->getSkippedCount());
        }

        return redirect()
            ->route('faktur-pajak.index')
            ->with('success', $message);
    }

    public function clear()
    {
        $deleted = FakturPajak::query()->delete();

        return redirect()
            ->route('faktur-pajak.index')
            ->with('success', "Berhasil menghapus {$deleted} data Faktur Pajak.");
    }

    /**
     * Update reference satu baris (edit via klik 2x pada kolom Reference).
     */
    public function updateReference(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:faktur_pajaks,id',
            'reference' => 'nullable|string|max:50',
        ]);

        $row = FakturPajak::findOrFail($data['id']);
        $row->reference = $data['reference'];
        $row->save();

        return response()->json(['ok' => true]);
    }
}
