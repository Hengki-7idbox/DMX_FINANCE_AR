<?php

namespace App\Http\Controllers;

use App\Imports\FakturPajakImport;
use App\Models\DetailSale;
use App\Models\FakturPajak;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class FakturPajakController extends Controller
{
    /**
     * Nama bulan sesuai kolom tax_invoice_period (hasil import CSV e-Faktur).
     *
     * @var array<int,string>
     */
    private const BULAN = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

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

        // Default modal "Clear Data": periode terbaru yang ada datanya.
        $latest = $payload->first();
        $defaultBulan = (int) now()->month;
        $defaultTahun = (int) now()->year;

        if ($latest !== null) {
            $foundBulan = array_search($latest['period'], self::BULAN, true);
            if ($foundBulan !== false) {
                $defaultBulan = (int) $foundBulan;
            }
            if ($latest['tahun'] !== '') {
                $defaultTahun = (int) $latest['tahun'];
            }
        }

        $defaultDate = Carbon::create($defaultTahun, $defaultBulan, 1);
        $defaultDari = $defaultDate->copy()->startOfMonth()->toDateString();
        $defaultSampai = $defaultDate->copy()->endOfMonth()->toDateString();

        $tahunOptions = $payload->pluck('tahun')
            ->filter(fn ($t) => $t !== '')
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if ($tahunOptions === []) {
            $tahunOptions = [(string) $defaultTahun];
        }

        return view('faktur-pajak.index', [
            'payload' => $payload,
            'bulanNames' => self::BULAN,
            'defaultBulan' => $defaultBulan,
            'defaultTahun' => $defaultTahun,
            'defaultDari' => $defaultDari,
            'defaultSampai' => $defaultSampai,
            'tahunOptions' => $tahunOptions,
        ]);
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

        if ($import->getDraftCount() > 0) {
            $message .= sprintf(' %d baris belum terbit nomor FP (draft).', $import->getDraftCount());
        }

        return redirect()
            ->route('faktur-pajak.index')
            ->with('success', $message);
    }

    /**
     * Hapus data Faktur Pajak per periode (bulan) atau per rentang tanggal.
     * Tidak ada opsi "hapus semua" — supaya data tidak terhapus tanpa sengaja.
     */
    public function clear(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['bulan', 'tanggal'])],
            'bulan' => ['nullable', 'integer', 'between:1,12', 'required_if:mode,bulan'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100', 'required_if:mode,bulan'],
            'dari' => ['nullable', 'date', 'required_if:mode,tanggal'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari', 'required_if:mode,tanggal'],
        ]);

        $query = FakturPajak::query();

        if ($data['mode'] === 'bulan') {
            $bulan = (int) $data['bulan'];
            $tahun = (int) $data['tahun'];
            $namaBulan = self::BULAN[$bulan];

            // "Per Bulan" mengikuti periode FP (kolom tax_invoice_period),
            // bukan tanggal terbit — sama seperti filter Bulan di tabel.
            $query->where('tax_invoice_period', $namaBulan)
                ->where('tax_invoice_year', $tahun);

            $label = 'periode ' . $namaBulan . ' ' . $tahun;
        } else {
            $dari = Carbon::parse($data['dari'])->toDateString();
            $sampai = Carbon::parse($data['sampai'])->toDateString();

            // "Per Tanggal" mengikuti tanggal FP (tax_invoice_date).
            $query->whereBetween('tax_invoice_date', [$dari, $sampai]);

            $label = 'tanggal ' . Carbon::parse($dari)->format('d/m/Y')
                . ' s/d ' . Carbon::parse($sampai)->format('d/m/Y');
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            return redirect()
                ->route('faktur-pajak.index')
                ->with('error', 'Tidak ada data Faktur Pajak pada ' . $label . '. Tidak ada yang dihapus.');
        }

        $deleted = DB::transaction(fn () => $query->delete());

        return redirect()
            ->route('faktur-pajak.index')
            ->with('success', 'Berhasil menghapus ' . $deleted . ' data Faktur Pajak ' . $label . '.');
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
