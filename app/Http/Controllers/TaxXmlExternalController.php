<?php

namespace App\Http\Controllers;

use App\Exports\CoretaxSkippedExport;
use App\Models\DetailSale;
use App\Services\CoretaxXmlBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Halaman "Tax → Download Xml External": menarik XML Coretax untuk data
 * Detail Sales tab external berdasarkan periode (bulan/tahun atau rentang
 * tanggal) dan tipe Customer No. (KTP/NPWP/TNPWP/ETC).
 *
 * Builder, urutan dokumen (orderBy id), dan aturan filter retur sengaja
 * disamakan dengan bulk export di Detail Sales agar isi XML identik.
 */
class TaxXmlExternalController extends Controller
{
    /** Tipe Customer No. (aturan sama dengan filter #f_cust_type di Detail Sales). */
    private const CUSTOMER_TYPES = ['ktp', 'npwp', 'tnpwp', 'etc'];

    public function index()
    {
        $base = DetailSale::query()->where('source_type', DetailSale::SOURCE_EXTERNAL);
        $this->hideReturnDocs($base);

        $availableBulan = (clone $base)
            ->selectRaw('DISTINCT MONTH(transaction_date) AS m')
            ->pluck('m')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v >= 1 && $v <= 12)
            ->sort()
            ->values();

        $availableTahun = (clone $base)
            ->selectRaw('DISTINCT YEAR(transaction_date) AS y')
            ->pluck('y')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v >= 2000)
            ->sort()
            ->values();

        // Default: bulan & tahun terakhir yang punya data (fallback bulan berjalan).
        $maxDate = (clone $base)->max('transaction_date');
        $defaultTahun = $maxDate !== null ? (int) substr((string) $maxDate, 0, 4) : (int) now()->year;
        $defaultBulan = $maxDate !== null ? (int) substr((string) $maxDate, 5, 2) : (int) now()->month;

        $defaultDari = sprintf('%04d-%02d-01', $defaultTahun, $defaultBulan);
        $defaultSampai = date('Y-m-t', strtotime($defaultDari));

        return view('tax.download-xml-external', compact(
            'availableBulan',
            'availableTahun',
            'defaultBulan',
            'defaultTahun',
            'defaultDari',
            'defaultSampai'
        ));
    }

    /**
     * Ringkasan jumlah invoice untuk filter yang sedang dipilih.
     * Dipakai halaman untuk menampilkan "N invoice akan ditarik".
     */
    public function summary(Request $request)
    {
        $data = $request->validate($this->rules());

        $base = $this->buildQuery($data);

        $byType = [];
        foreach (self::CUSTOMER_TYPES as $type) {
            $query = clone $base;
            $this->applyCustomerType($query, $type);
            $byType[$type] = $query->count();
        }

        $totalQuery = clone $base;
        $this->applyCustomerType($totalQuery, $data['cust_type'] ?? null);

        return response()->json([
            'ok' => true,
            'total' => $totalQuery->count(),
            'by_type' => $byType,
        ]);
    }

    /**
     * Bangun XML Coretax untuk seluruh invoice yang cocok filter, lalu
     * kembalikan sebagai JSON berisi file (pola sama dengan
     * DetailSaleController::exportXmlCoretax).
     */
    public function download(Request $request)
    {
        $data = $request->validate($this->rules());

        // Hanya "FP belum ada" yang boleh ditarik. Status lain berarti FP-nya
        // sudah ada (terbit/draft) atau sengaja tidak dibuat -> upload ulang dobel.
        $fpStatus = (string) ($data['fp'] ?? 'belum');

        if ($fpStatus !== 'belum') {
            $reason = match ($fpStatus) {
                'ada' => 'sudah punya Faktur Pajak bernomor',
                'draft' => 'masih berstatus draft dan sudah ada di Coretax',
                'gung_gung' => 'ditandai Gung Gung (FP sengaja tidak dibuat)',
                default => 'tidak memenuhi syarat tarik',
            };

            return response()->json([
                'ok' => false,
                'message' => 'Dokumen tidak bisa ditarik: ' . $reason . '. '
                    . 'Upload ulang berisiko membuat Faktur Pajak dobel.',
                'errors' => [],
                'unmapped' => [],
            ], 422);
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '-1');

        $query = $this->buildQuery($data);
        $this->applyCustomerType($query, $data['cust_type'] ?? null);

        // Urutan id = urutan dokumen di XML, sama seperti bulk export Detail Sales.
        $docNumbers = $query->orderBy('id')->pluck('doc_number')->all();

        if ($docNumbers === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak ada invoice yang cocok dengan filter.',
                'errors' => [],
                'unmapped' => [],
            ], 422);
        }

        $result = (new CoretaxXmlBuilder())->build($docNumbers, DetailSale::SOURCE_EXTERNAL);

        if ($result['count'] === 0) {
            return response()->json([
                'ok' => false,
                'message' => $result['skipped'] !== []
                    ? 'Tidak ada dokumen yang bisa diexport.'
                    : 'Invoice tidak ditemukan di tab external.',
                'errors' => $result['skipped'],
                'unmapped' => $result['unmapped'] ?? [],
                'unmapped_default' => $result['unmapped_default'] ?? null,
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'count' => $result['count'],
            'selected' => count($docNumbers),
            'skipped' => $result['skipped'],
            'unmapped' => $result['unmapped'] ?? [],
            'unmapped_default' => $result['unmapped_default'] ?? null,
            'files' => [
                ['name' => 'Coretax' . $this->fileSuffix($data) . '.xml', 'content' => $result['xml']],
            ],
        ]);
    }

    /**
     * Export Excel dokumen yang dilewati pada filter yang sama dengan tarikan
     * terakhir. Sheet "Customer eksternal" bisa langsung diisi NIK lalu
     * diimpor lewat Customers → Import NPWP.
     */
    public function skippedExport(Request $request)
    {
        $data = $request->validate($this->rules());

        @set_time_limit(0);
        @ini_set('memory_limit', '-1');

        $query = $this->buildQuery($data);
        $this->applyCustomerType($query, $data['cust_type'] ?? null);
        $docNumbers = $query->orderBy('id')->pluck('doc_number')->all();

        if ($docNumbers === []) {
            return redirect()->route('tax.download-xml-external')
                ->with('error', 'Tidak ada invoice yang cocok dengan filter.');
        }

        $builder = new CoretaxXmlBuilder();
        $builder->build($docNumbers, DetailSale::SOURCE_EXTERNAL);
        $details = $builder->skippedDetails();

        if ($details === []) {
            return redirect()->route('tax.download-xml-external')
                ->with('error', 'Tidak ada dokumen yang dilewati pada filter ini — semua dokumen valid.');
        }

        // Ringkas per customer untuk sheet yang bisa diimpor.
        $customers = [];
        foreach ($details as $row) {
            $code = trim((string) ($row['customer_code'] ?? ''));
            if ($code === '') {
                $code = '(tanpa kode)';
            }

            if (! isset($customers[$code])) {
                $customers[$code] = [
                    'code' => $code,
                    'name' => (string) ($row['customer_name'] ?? ''),
                    'tax' => (string) ($row['tax_number'] ?? ''),
                    'count' => 0,
                    'reasons' => [],
                ];
            }

            $customers[$code]['count']++;

            if ($customers[$code]['name'] === '' && ! empty($row['customer_name'])) {
                $customers[$code]['name'] = (string) $row['customer_name'];
            }
            if ($customers[$code]['tax'] === '' && ! empty($row['tax_number'])) {
                $customers[$code]['tax'] = (string) $row['tax_number'];
            }

            foreach (($row['reasons'] ?? []) as $reason) {
                $customers[$code]['reasons'][$reason] = true;
            }
        }

        foreach ($customers as &$customer) {
            $customer['reasons'] = array_keys($customer['reasons']);
        }
        unset($customer);

        $filename = 'Dokumen Dilewati Coretax' . $this->fileSuffix($data) . '.xlsx';

        return Excel::download(
            new CoretaxSkippedExport($details, array_values($customers)),
            $filename
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['bulan', 'tanggal'])],
            'bulan' => ['nullable', 'integer', 'between:1,12', 'required_if:mode,bulan'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100', 'required_if:mode,bulan'],
            'dari' => ['nullable', 'date', 'required_if:mode,tanggal'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari', 'required_if:mode,tanggal'],
            'cust_type' => ['nullable', Rule::in(self::CUSTOMER_TYPES)],
            'fp' => ['nullable', Rule::in(['ada', 'belum', 'draft', 'gung_gung'])],
        ];
    }

    /**
     * Query dasar: tab external + sembunyikan dokumen retur (sama dengan
     * DetailSaleController::hideReturnDocs) + filter periode.
     *
     * @param  array<string,mixed>  $data
     */
    private function buildQuery(array $data): Builder
    {
        $query = DetailSale::query()->where('source_type', DetailSale::SOURCE_EXTERNAL);
        $this->hideReturnDocs($query);

        if (($data['mode'] ?? 'bulan') === 'tanggal') {
            $query->whereBetween('transaction_date', [$data['dari'], $data['sampai']]);
        } else {
            $query->whereRaw('YEAR(transaction_date) = ?', [(int) $data['tahun']])
                ->whereRaw('MONTH(transaction_date) = ?', [(int) $data['bulan']]);
        }

        // Default "FP belum ada": dokumen yang sudah punya faktur pajak tidak
        // ikut ditarik supaya upload ulang ke Coretax tidak membuat dobel.
        $this->applyFakturPajak($query, (string) ($data['fp'] ?? 'belum'));

        return $query;
    }

    /**
     * Filter status Faktur Pajak (aturan sama dengan kolom Faktur Pajak di
     * Detail Sales tab external):
     *  - ada       : punya nomor faktur (faktur_pajaks), bukan Gung Gung
     *  - draft     : sudah punya record FP tapi belum terbit nomor (CREATED)
     *  - belum     : belum punya record FP sama sekali, bukan Gung Gung
     *  - gung_gung : detail_sales.fp_status = 'gung_gung'
     *
     * Draft tidak dimasukkan ke "belum" supaya tidak ditarik ulang: FP-nya
     * sudah ada di Coretax dan upload ulang bisa membuat dobel.
     */
    private function applyFakturPajak(Builder $query, string $status): void
    {
        if ($status === '') {
            $status = 'belum';
        }

        if ($status === 'gung_gung') {
            $query->where('fp_status', 'gung_gung');

            return;
        }

        $hasFaktur = function ($sub) {
            $sub->select(DB::raw(1))
                ->from('faktur_pajaks')
                ->whereColumn('faktur_pajaks.reference', 'detail_sales.doc_number')
                ->whereRaw("TRIM(COALESCE(tax_invoice_number, '')) <> ''");
        };

        $hasDraft = function ($sub) {
            $sub->select(DB::raw(1))
                ->from('faktur_pajaks')
                ->whereColumn('faktur_pajaks.reference', 'detail_sales.doc_number')
                ->whereRaw("TRIM(COALESCE(tax_invoice_number, '')) = ''");
        };

        if ($status === 'ada') {
            $query->whereExists($hasFaktur);
        } elseif ($status === 'draft') {
            $query->whereNotExists($hasFaktur)->whereExists($hasDraft);
        } else {
            $query->whereNotExists($hasFaktur)->whereNotExists($hasDraft);
        }

        // Gung Gung = FP sengaja tidak dibuat; tidak masuk hitungan "ada"/"belum".
        // fp_status bisa NULL, jadi NULL harus tetap lolos.
        $query->where(function ($sub) {
            $sub->whereNull('fp_status')->orWhere('fp_status', '!=', 'gung_gung');
        });
    }

    /**
     * Aturan identik dengan filter Customer No. di Detail Sales:
     * TNPWP diutamakan, lalu KTP, lalu NPWP, sisanya ETC.
     */
    private function applyCustomerType(Builder $query, ?string $type): void
    {
        $type = (string) $type;
        if ($type === '') {
            return;
        }

        $tnpwp = "UPPER(customer_code) NOT LIKE '%TNPWP%'";
        $ktp = "UPPER(customer_code) LIKE '%KTP%'";
        $npwp = "UPPER(customer_code) LIKE '%NPWP%'";

        if ($type === 'tnpwp') {
            $query->whereRaw("UPPER(customer_code) LIKE '%TNPWP%'");

            return;
        }

        $query->whereRaw($tnpwp);

        if ($type === 'ktp') {
            $query->whereRaw($ktp);
        } elseif ($type === 'npwp') {
            $query->whereRaw($npwp);
        } elseif ($type === 'etc') {
            $query->whereRaw("UPPER(customer_code) NOT LIKE '%KTP%'")
                ->whereRaw("UPPER(customer_code) NOT LIKE '%NPWP%'");
        }
    }

    /**
     * Sembunyikan dokumen retur: terdaftar di modul Retur Sales atau
     * berawalan SR diikuti pemisah (SR/ , SR- , SR - , SR.).
     */
    private function hideReturnDocs(Builder $query): void
    {
        $query->whereNotIn('doc_number', function ($sub) {
            $sub->select('return_no')->from('sales_returns');
        })->whereRaw("TRIM(doc_number) NOT REGEXP '^SR[[:space:]]*[/.-]'");
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function fileSuffix(array $data): string
    {
        $label = ($data['mode'] ?? 'bulan') === 'tanggal'
            ? $data['dari'] . '_sd_' . $data['sampai']
            : sprintf('%04d-%02d', (int) $data['tahun'], (int) $data['bulan']);

        if (! empty($data['cust_type'])) {
            $label .= ' ' . strtoupper((string) $data['cust_type']);
        }

        $fpLabel = match ((string) ($data['fp'] ?? 'belum')) {
            'ada' => 'FP-ada',
            'draft' => 'FP-draft',
            'gung_gung' => 'FP-gunggung',
            default => 'FP-belum',
        };

        return ' External ' . $label . ' ' . $fpLabel . ' ' . now()->format('Ymd-His');
    }
}
