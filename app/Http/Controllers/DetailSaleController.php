<?php

namespace App\Http\Controllers;

use App\Imports\DetailSaleItemsImport;
use App\Imports\DetailSalesImport;
use App\Models\Customer;
use App\Models\DetailSale;
use App\Models\DetailSaleItem;
use App\Models\ExternalDocument;
use App\Models\FakturPajak;
use App\Models\ImportSession;
use App\Models\MappingCustomer;
use App\Models\MappingProduct;
use App\Models\PaymentTerm;
use App\Models\Setting;
use App\Models\SalesReturn;
use App\Services\AccurateApiClient;
use App\Services\AccurateItemUnitService;
use App\Services\CoretaxXmlBuilder;
use App\Services\ExternalXmlBuilder;
use App\Support\Taxability;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class DetailSaleController extends Controller
{
    public function index(Request $request)
    {
        $tab = $this->resolveTab($request);

        // Halaman /invoices = detail-sales difilter `owing != 0` (judul & base tab berbeda).
        $isInvoices = $request->routeIs('invoices.*');
        $tabRoute = $isInvoices ? 'invoices.index' : 'detail-sales.index';

        $query = DetailSale::where('source_type', $tab);
        if ($isInvoices) {
            $query->where('owing', '!=', 0);
        }

        // Sembunyikan dokumen retur di kedua tab (internal & external):
        // 1) nomor yang terdaftar di modul Retur Sales, atau
        // 2) berawalan SR diikuti pemisah: SR/ , SR- , SR - , SR. (termasuk huruf kecil).
        $this->hideReturnDocs($query);

        // Filter tahun & bulan via query param (?bulan=8,9&tahun=2026).
        // Tanpa param → default bulan & tahun terakhir yang punya data (fallback: bulan berjalan).
        $filterBulan = $this->intListParam($request->query('bulan'));
        $filterTahun = $this->intListParam($request->query('tahun'));
        $hasFilterParam = $request->query('bulan') !== null || $request->query('tahun') !== null;

        if (! $hasFilterParam) {
            $maxDate = (clone $query)->max('transaction_date');
            if ($maxDate !== null) {
                $filterTahun = [(int) substr((string) $maxDate, 0, 4)];
                $filterBulan = [(int) substr((string) $maxDate, 5, 2)];
            } else {
                $filterTahun = [(int) now()->year];
                $filterBulan = [(int) now()->month];
            }
        }

        if ($filterTahun !== []) {
            $query->whereRaw('YEAR(transaction_date) IN (' . implode(',', $filterTahun) . ')');
        }
        if ($filterBulan !== []) {
            $query->whereRaw('MONTH(transaction_date) IN (' . implode(',', $filterBulan) . ')');
        }

        $detailSales = $query
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get([
                'id', 'fp_status', 'customer_code', 'customer_name', 'doc_number',
                'transaction_date', 'description',
                'invoice_amount', 'amount', 'tax_base', 'discount',
                'return_amount', 'owing', 'payment_amount', 'calculate_field',
            ]);

        $this->annotateMatch($detailSales, $tab);

        // Kolom Faktur Pajak (tab external): cocokkan doc_number dengan reference faktur_pajak.
        // Reference double (2 faktur) dipetakan ke nomor pajak faktur pertama.
        // Status manual FP ("Gung Gung" = FP memang tidak dibuat) disimpan di detail_sales.fp_status.
        $fpMap = collect();
        $fpDraftMap = collect();
        if ($tab === DetailSale::SOURCE_EXTERNAL) {
            // Hanya FP yang sudah terbit nomor yang dipakai untuk kolom Faktur Pajak.
            $fpMap = FakturPajak::query()
                ->whereNotNull('reference')
                ->where('reference', '!=', '')
                ->whereRaw("TRIM(COALESCE(tax_invoice_number, '')) <> ''")
                ->orderBy('id')
                ->get(['reference', 'tax_invoice_number'])
                ->unique('reference')
                ->mapWithKeys(fn ($r) => [$r->reference => $r->tax_invoice_number]);

            // Draft (belum terbit) ditandai terpisah agar kolom menampilkan "Draft".
            $fpDraftMap = FakturPajak::query()
                ->whereNotNull('reference')
                ->where('reference', '!=', '')
                ->whereRaw("TRIM(COALESCE(tax_invoice_number, '')) = ''")
                ->pluck('reference')
                ->unique()
                ->flip();
        }

        // Dokumen external yang sudah dibuat via API (mapping per tahap SO/DO/INV).
        $extDocs = [];
        $extStages = [];
        if ($detailSales->isNotEmpty()) {
            $rows = ExternalDocument::query()
                ->whereIn('doc_number', $detailSales->pluck('doc_number'))
                ->get(['doc_number', 'type']);

            $rank = [ExternalDocument::TYPE_SO => 1, ExternalDocument::TYPE_DO => 2, ExternalDocument::TYPE_INV => 3];
            $label = array_flip($rank);
            foreach ($rows as $row) {
                $doc = (string) $row->doc_number;
                $extDocs[$doc][$row->type] = true;
                if ($tab === DetailSale::SOURCE_EXTERNAL) {
                    $current = $rank[$extStages[$doc] ?? ''] ?? 0;
                    $candidate = $rank[$row->type] ?? 0;
                    if ($candidate > $current) {
                        $extStages[$doc] = $label[$candidate];
                    }
                }
            }
        }

        // Kode customer pasangan (internal <-> external) untuk kolom "Customer No." tambahan.
        // Diutamakan kode yang benar-benar dipakai dokumen (baris tab lawan dengan
        // doc_number sama), lalu fallback ke mapping customer.
        $docNumbers = $detailSales->pluck('doc_number')->all();
        $internalCustByDoc = [];
        $externalCustByDoc = [];
        if ($docNumbers !== []) {
            $internalCustByDoc = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
                ->whereIn('doc_number', $docNumbers)
                ->pluck('customer_code', 'doc_number')
                ->all();
            $externalCustByDoc = DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)
                ->whereIn('doc_number', $docNumbers)
                ->pluck('customer_code', 'doc_number')
                ->all();
        }

        $custExternalMap = [];
        $custInternalMap = [];
        foreach (MappingCustomer::query()->get(['kode_internal', 'kode_external']) as $mapping) {
            $internalCode = (string) $mapping->kode_internal;
            $externalCode = (string) $mapping->kode_external;

            if ($internalCode !== '' && ! array_key_exists($internalCode, $custExternalMap)) {
                $custExternalMap[$internalCode] = $externalCode;
            }
            if ($externalCode !== '' && ! array_key_exists($externalCode, $custInternalMap)) {
                $custInternalMap[$externalCode] = $internalCode;
            }
        }

        // Payload ringkas untuk render client-side (DataTables deferRender).
        $payload = $detailSales->map(fn ($r) => [
            'id' => $r->id,
            'customer_code' => $r->customer_code,
            // Mapping customer adalah acuan kode pasangan. Kode dari dokumen tab
            // lawan hanya dipakai bila customer tersebut belum punya mapping —
            // data external hasil import sering masih memakai kode internal.
            'customer_code_external' => $tab === DetailSale::SOURCE_EXTERNAL
                ? $r->customer_code
                : ($custExternalMap[(string) $r->customer_code] ?? $externalCustByDoc[(string) $r->doc_number] ?? null),
            'customer_code_internal' => $tab === DetailSale::SOURCE_INTERNAL
                ? $r->customer_code
                : ($custInternalMap[(string) $r->customer_code] ?? $internalCustByDoc[(string) $r->doc_number] ?? null),
            'customer_name' => $r->customer_name,
            'doc_number' => $r->doc_number,
            'transaction_date' => $r->transaction_date?->format('Y-m-d'),
            'description' => $r->description,
            'status' => $r->match_status,
            'ext_so' => ! empty($extDocs[$r->doc_number][ExternalDocument::TYPE_SO]),
            'ext_do' => ! empty($extDocs[$r->doc_number][ExternalDocument::TYPE_DO]),
            'ext_inv' => ! empty($extDocs[$r->doc_number][ExternalDocument::TYPE_INV]),
            'ext_stage' => $tab === DetailSale::SOURCE_EXTERNAL
                ? ($extStages[(string) $r->doc_number] ?? null)
                : null,
            'fp_number' => $tab === DetailSale::SOURCE_EXTERNAL
                ? ($fpMap[$r->doc_number] ?? null)
                : null,
            'fp_draft' => $tab === DetailSale::SOURCE_EXTERNAL
                ? $fpDraftMap->has((string) $r->doc_number)
                : false,
            'fp_status' => $tab === DetailSale::SOURCE_EXTERNAL
                ? $r->fp_status
                : null,
            'invoice_amount' => (float) $r->invoice_amount,
            'amount' => (float) $r->amount,
            'tax_base' => (float) $r->tax_base,
            'discount' => (float) $r->discount,
            'return_amount' => (float) $r->return_amount,
            'owing' => (float) $r->owing,
            'payment_amount' => (float) $r->payment_amount,
            'calculate_field' => (float) $r->calculate_field,
        ])->values();

        // Opsi filter Bulan/Tahun (distinct seluruh data tab) + total utk modal clear.
        // Untuk /invoices, opsi dibatasi pada baris ber-owing.
        $optQuery = DetailSale::where('source_type', $tab);
        if ($isInvoices) {
            $optQuery->where('owing', '!=', 0);
        }
        $this->hideReturnDocs($optQuery);
        // Buang 0 — hasil MONTH/YEAR(NULL) pada baris tanpa tanggal (import rusak).
        $availableBulan = (clone $optQuery)
            ->selectRaw('DISTINCT MONTH(transaction_date) AS m')
            ->pluck('m')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v >= 1 && $v <= 12)
            ->sort()
            ->values();
        $availableTahun = (clone $optQuery)
            ->selectRaw('DISTINCT YEAR(transaction_date) AS y')
            ->pluck('y')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v >= 2000)
            ->sort()
            ->values();
        $totalQuery = DetailSale::where('source_type', $tab);
        $this->hideReturnDocs($totalQuery);
        $totalAll = $totalQuery->count();

        $pageTitle = $isInvoices ? 'Invoice Belum Lunas' : 'Detail Sales';
        $pageDescription = $isInvoices
            ? 'Daftar invoice dengan nilai owing (belum lunas)'
            : 'Detail transaksi sales per invoice';

        // Alias database Accurate (untuk label di konfirmasi hapus/cek). Di-cache 1 jam.
        $accurateAlias = null;
        try {
            $accurateAlias = (new AccurateApiClient())->identity()['alias'] ?? null;
        } catch (\Throwable $e) {
            $accurateAlias = null;
        }

        return view('detail-sales.index', compact(
            'detailSales', 'tab', 'payload',
            'availableBulan', 'availableTahun', 'filterBulan', 'filterTahun', 'totalAll',
            'isInvoices', 'tabRoute', 'pageTitle', 'pageDescription',
            'accurateAlias'
        ));
    }

    /**
     * Parse param query list angka comma-separated ("8,9" → [8, 9]).
     * Token non-digit diabaikan; hasil unik & terurut.
     */
    private function intListParam(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $out = [];
        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if (ctype_digit($part)) {
                $out[] = (int) $part;
            }
        }

        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }

    /**
     * Sembunyikan dokumen retur dari query Detail Sales (berlaku kedua tab).
     *
     * Aturan:
     *  1) nomor terdaftar di modul Retur Sales (`sales_returns.return_no`), atau
     *  2) berawalan "SR" diikuti pemisah: SR/ , SR- , SR - , SR. (case-insensitive,
     *     termasuk varian sufiks huruf seperti SR/2610/0043D).
     */
    private function hideReturnDocs(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNotIn('doc_number', function ($sub) {
                $sub->select('return_no')->from('sales_returns');
            })
            ->whereRaw("TRIM(doc_number) NOT REGEXP '^SR[[:space:]]*[/.-]'");
    }

    /**
     * Compare doc_number against the other tab.
     *
     * Kolom angka pada export Accurate tidak konsisten antar periode / antar entitas:
     *   - 'Calculate Field' bisa berarti NET setelah retur (invoice - retur),
     *   - 'Invoice Amount' = nilai GROSS (sebelum retur),
     *   - 'Amount' = nilai DPP (mis. invoice / 1.11 saat ada PPN).
     *
     * Karena itu sebuah pasangan dianggap 'match' bila nilai eksternal (kolom Amount
     * tab lawan) sama dengan SALAH SATU representasi internal pada doc_number yang sama:
     * calculate_field, amount (DPP), invoice_amount (gross), atau calculate_field + retur.
     * Sebelumnya hanya dibandingkan satu nilai turunan saja sehingga invoice yang
     * sebenarnya cocok (mis. DMXS26100311) ditandai 'unmatch'.
     *
     * match_status:
     *   'match'       — cocok dengan salah satu representasi (selisih < 0.01)
     *   'unmatch'     — tidak ada yang cocok (selisih >= 0.01)
     *   'no_pair'     — doc_number tidak ada di tab lain
     *   'full_return' — (tab internal) semua qty item sudah diretur
     *
     * Berlaku untuk kedua tab (status identik untuk sepasang doc), kecuali
     * 'full_return' yang hanya muncul di tab internal.
     */
    private function annotateMatch($detailSales, string $tab): void
    {
        $otherTab = $tab === DetailSale::SOURCE_INTERNAL
            ? DetailSale::SOURCE_EXTERNAL
            : DetailSale::SOURCE_INTERNAL;

        // Cukup load pasangan doc yang tampil (filter server-side) — hemat memori.
        $docNumbers = $detailSales->pluck('doc_number');

        $others = DetailSale::where('source_type', $otherTab)
            ->whereIn('doc_number', $docNumbers)
            ->get(['doc_number', 'amount'])
            ->keyBy('doc_number');

        // Baris internal per doc_number. Saat tab = internal, baris internal = $detailSales
        // itu sendiri; saat tab = external, ambil dari tab internal.
        $internalRows = $tab === DetailSale::SOURCE_INTERNAL
            ? $detailSales
            : DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
                ->whereIn('doc_number', $docNumbers)
                ->get(['doc_number', 'tax_base', 'invoice_amount', 'amount', 'calculate_field', 'return_amount']);

        // Kumpulan nilai internal yang mungkin (semua representasi) per doc_number.
        $internalCandidates = [];
        foreach ($internalRows as $r) {
            $calc = (float) $r->calculate_field;
            $internalCandidates[(string) $r->doc_number] = [
                $calc,
                (float) $r->amount,
                (float) $r->invoice_amount,
                $calc + (float) $r->return_amount,
            ];
        }

        // Dokumen yang seluruh qty itemnya sudah diretur: statusnya Full Retur
        // (bukan Match/Unmatch) karena tidak ada qty tersisa untuk dikirim.
        $fullReturn = $tab === DetailSale::SOURCE_INTERNAL
            ? $this->fullReturnDocs($docNumbers->all())
            : [];

        foreach ($detailSales as $row) {
            $doc = (string) $row->doc_number;

            if (isset($fullReturn[$doc])) {
                $row->match_status = 'full_return';
                continue;
            }

            $partner = $others[$row->doc_number] ?? null;

            if ($partner === null) {
                $row->match_status = 'no_pair';
                continue;
            }

            $candidates = $internalCandidates[$doc] ?? [];
            if ($candidates === []) {
                $row->match_status = 'no_pair';
                continue;
            }

            // Nilai eksternal = kolom Amount pada baris tab lawan.
            $nilaiExternal = $tab === DetailSale::SOURCE_INTERNAL
                ? (float) $partner->amount
                : (float) $row->amount;

            $isMatch = false;
            foreach ($candidates as $v) {
                if (abs($v - $nilaiExternal) < 0.01) {
                    $isMatch = true;
                    break;
                }
            }

            $row->match_status = $isMatch ? 'match' : 'unmatch';
        }
    }

    /**
     * Bandingkan nilai internal (beberapa representasi) dengan nilai external.
     *
     * @param  array<int,float>  $candidates
     */
    private function compareMatch(array $candidates, float $externalAmount): string
    {
        if ($candidates === []) {
            return 'no_pair';
        }

        foreach ($candidates as $value) {
            if (abs((float) $value - $externalAmount) < 0.01) {
                return 'match';
            }
        }

        return 'unmatch';
    }

    /**
     * Dokumen internal yang seluruh qty itemnya sudah tertutup retur.
     *
     * Dokumen seperti ini tidak punya sisa qty untuk dikirim ke Accurate, jadi
     * statusnya bukan Match/Unmatch melainkan 'full_return'.
     *
     * @param  array<int,string>  $docNumbers
     * @return array<string,bool> doc_number => true
     */
    private function fullReturnDocs(array $docNumbers): array
    {
        $docNumbers = array_values(array_unique(array_map('strval', $docNumbers)));
        if ($docNumbers === []) {
            return [];
        }

        $itemTotals = DB::table('detail_sale_items')
            ->select('doc_number', 'item_no', DB::raw('SUM(qty) AS qty'))
            ->where('source_type', DetailSale::SOURCE_INTERNAL)
            ->whereIn('doc_number', $docNumbers)
            ->groupBy('doc_number', 'item_no')
            ->get();

        if ($itemTotals->isEmpty()) {
            return [];
        }

        $returnTotals = DB::table('sales_returns')
            ->select('invoice_no', 'item_no', DB::raw('SUM(ABS(quantity)) AS qty'))
            ->whereIn('invoice_no', $docNumbers)
            ->groupBy('invoice_no', 'item_no')
            ->get();

        if ($returnTotals->isEmpty()) {
            return [];
        }

        [, $itemCodeMap] = $this->externalMaps();

        // Retur bisa menunjuk kode internal berbeda untuk barang yang sama, jadi
        // item disamakan lewat kode eksternal bila ada, kalau tidak kode internal.
        $keyOf = static function (string $doc, string $itemNo) use ($itemCodeMap): string {
            $external = trim((string) ($itemCodeMap[$itemNo] ?? ''));

            return $doc . '|' . ($external !== '' ? 'X:' . strtoupper($external) : 'I:' . $itemNo);
        };

        $returned = [];
        foreach ($returnTotals as $row) {
            $key = $keyOf((string) $row->invoice_no, (string) $row->item_no);
            $returned[$key] = ($returned[$key] ?? 0.0) + (float) $row->qty;
        }

        // Qty item dijumlahkan per kunci yang sama dengan retur. Beberapa kode
        // internal bisa menunjuk barang Accurate yang sama (mis. CHF028.1.5.1
        // dan CHF028.1.5.1.2 -> BP020.10), jadi qty-nya harus digabung dulu.
        $itemQty = [];
        $documents = [];
        foreach ($itemTotals as $row) {
            $doc = (string) $row->doc_number;
            $qty = (float) $row->qty;

            if ($qty <= 0.005) {
                continue;
            }

            // Hanya dokumen yang punya item qty positif yang bisa dinilai.
            $documents[$doc] = true;

            $key = $keyOf($doc, (string) $row->item_no);
            $itemQty[$key] = ($itemQty[$key] ?? 0.0) + $qty;
        }

        foreach ($itemQty as $key => $qty) {
            $doc = (string) strstr($key, '|', true);
            if (($returned[$key] ?? 0.0) < $qty - 0.005) {
                $documents[$doc] = false;
            }
        }

        return array_filter($documents);
    }

    /**
     * Status Match/Unmatch sekumpulan dokumen pada satu tab.
     *
     * @param  array<int,string>  $docNumbers
     * @return array<string,string> doc_number => match|unmatch|no_pair
     */
    private function matchStatusMap(array $docNumbers, string $tab): array
    {
        $docNumbers = array_values(array_unique(array_map('strval', $docNumbers)));
        if ($docNumbers === []) {
            return [];
        }

        $otherTab = $tab === DetailSale::SOURCE_INTERNAL
            ? DetailSale::SOURCE_EXTERNAL
            : DetailSale::SOURCE_INTERNAL;

        $others = DetailSale::where('source_type', $otherTab)
            ->whereIn('doc_number', $docNumbers)
            ->get(['doc_number', 'amount'])
            ->keyBy('doc_number');

        // Tab external: nilai pembanding diambil dari baris external itu sendiri.
        $selfAmounts = $tab === DetailSale::SOURCE_EXTERNAL
            ? DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)
                ->whereIn('doc_number', $docNumbers)
                ->pluck('amount', 'doc_number')
            : collect();

        $internalRows = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
            ->whereIn('doc_number', $docNumbers)
            ->get(['doc_number', 'tax_base', 'invoice_amount', 'amount', 'calculate_field', 'return_amount']);

        $internalCandidates = [];
        foreach ($internalRows as $r) {
            $calc = (float) $r->calculate_field;
            $internalCandidates[(string) $r->doc_number] = [
                $calc,
                (float) $r->amount,
                (float) $r->invoice_amount,
                $calc + (float) $r->return_amount,
            ];
        }

        $result = [];
        $fullReturn = $tab === DetailSale::SOURCE_INTERNAL
            ? $this->fullReturnDocs($docNumbers)
            : [];

        foreach ($docNumbers as $doc) {
            if (isset($fullReturn[$doc])) {
                $result[$doc] = 'full_return';
                continue;
            }

            $partner = $others[$doc] ?? null;
            if ($partner === null) {
                $result[$doc] = 'no_pair';
                continue;
            }

            $nilaiExternal = $tab === DetailSale::SOURCE_INTERNAL
                ? (float) $partner->amount
                : (float) ($selfAmounts[$doc] ?? 0);

            $result[$doc] = $this->compareMatch($internalCandidates[$doc] ?? [], $nilaiExternal);
        }

        return $result;
    }

    /**
     * Hitung ulang status Match/Unmatch satu dokumen tanpa reload halaman.
     */
    public function matchStatus(Request $request)
    {
        $data = $request->validate([
            'doc_number' => ['required', 'string', 'max:50'],
            'tab' => ['nullable', Rule::in([DetailSale::SOURCE_INTERNAL, DetailSale::SOURCE_EXTERNAL])],
        ]);

        $tab = $data['tab'] ?? $this->resolveTab($request);
        $map = $this->matchStatusMap([$data['doc_number']], $tab);

        return response()->json([
            'ok' => true,
            'status' => $map[$data['doc_number']] ?? 'no_pair',
        ]);
    }

    /**
     * Hitung ulang status Match/Unmatch banyak dokumen sekaligus (tombol toolbar).
     */
    public function matchStatusBulk(Request $request)
    {
        $data = $request->validate([
            'docs' => ['required', 'array', 'min:1', 'max:5000'],
            'docs.*' => ['required'],
            'tab' => ['nullable', Rule::in([DetailSale::SOURCE_INTERNAL, DetailSale::SOURCE_EXTERNAL])],
        ]);

        $docs = array_map(fn ($doc) => trim((string) $doc), $data['docs']);

        $tab = $data['tab'] ?? $this->resolveTab($request);

        return response()->json([
            'ok' => true,
            'statuses' => $this->matchStatusMap($docs, $tab),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'source_type' => ['required', Rule::in(['internal', 'external'])],
            'import_type' => ['required', Rule::in(['sales', 'items'])],
        ]);

        $tab = $request->input('source_type');
        $importType = $request->input('import_type');

        // Proses tetap diselesaikan server walau koneksi/browser terputus di tengah —
        // data tidak boleh masuk setengah jalan karena user F5/retry.
        ignore_user_abort(true);
        set_time_limit(0);

        Log::info('detail-sales.import mulai', [
            'tab' => $tab,
            'import_type' => $importType,
            'file' => $request->file('file')?->getClientOriginalName(),
            'user_id' => $request->user()?->id,
        ]);

        $session = null;
        $salesImport = null;

        try {
            if ($importType === 'items') {
                $import = new DetailSaleItemsImport($tab);
                Excel::import($import, $request->file('file'));
                $created = $import->getCreatedCount();
                $updated = $import->getUpdatedCount();
                $skipped = $import->getSkippedCount();
                $label = 'Detail Product Sales';

                if ($created + $updated === 0) {
                    Log::warning('detail-sales.import 0 baris (items)', [
                        'tab' => $tab,
                        'file' => $request->file('file')?->getClientOriginalName(),
                    ]);

                    return redirect()
                        ->route('detail-sales.index', ['tab' => $tab])
                        ->with('error', "Tidak ada data terimport dari file {$label}. Pastikan file export Accurate yang benar.");
                }

                $message = "Import {$label} selesai: {$created} ditambahkan, {$updated} diupdate";
                if ($skipped > 0) {
                    $message .= ", {$skipped} baris dilewati";
                }
                $message .= '.';
                Log::info('detail-sales.import selesai', [
                    'tab' => $tab,
                    'import_type' => 'items',
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                ]);
            } else {
                $session = ImportSession::create([
                    'source_type' => $tab,
                    'import_type' => ImportSession::TYPE_SALES,
                    'file_name' => $request->file('file')->getClientOriginalName(),
                    'user_id' => $request->user()?->id,
                ]);

                $salesImport = new DetailSalesImport($tab, $session->id);
                Excel::import($salesImport, $request->file('file'));

                $created = $salesImport->getCreatedCount();
                $updated = $salesImport->getChangedCount();
                $unchanged = $salesImport->getUnchangedCount();
                $skipped = $salesImport->getSkippedCount();
                $label = 'Detail Sales';

                $this->finalizeSession($session, $salesImport, false);

                $counts = $created + $updated + $unchanged + $skipped;
                $rowsRead = $salesImport->getRowsRead();

                if ($counts === 0 || !$salesImport->isHeaderDetected()) {
                    $detail = $rowsRead === 0
                        ? 'File terbaca tetapi 0 baris ditemukan pada semua sheet — file kosong, sheet data tidak ada, atau bukan export Accurate.'
                        : "File berisi {$rowsRead} baris tetapi header \"Customer No.\" tidak ditemukan — format file tidak sesuai template Accurate.";

                    Log::warning('detail-sales.import 0 baris (sales)', [
                        'tab' => $tab,
                        'file' => $request->file('file')?->getClientOriginalName(),
                        'counts' => $counts,
                        'rows_read' => $rowsRead,
                        'header_detected' => $salesImport->isHeaderDetected(),
                    ]);

                    return redirect()
                        ->route('detail-sales.index', ['tab' => $tab])
                        ->with('error', "Tidak ada data terimport dari file {$label}. {$detail}");
                }

                $message = "Import {$label} selesai: {$created} ditambahkan, {$updated} diupdate";
                if ($unchanged > 0) {
                    $message .= ", {$unchanged} tidak berubah";
                }
                if ($skipped > 0) {
                    $message .= ", {$skipped} baris dilewati";
                }
                $message .= '.';

                Log::info('detail-sales.import selesai', [
                    'tab' => $tab,
                    'import_type' => 'sales',
                    'session_id' => $session->id,
                    'created' => $created,
                    'changed' => $updated,
                    'unchanged' => $unchanged,
                    'skipped' => $skipped,
                    'rows_read' => $salesImport->getRowsRead(),
                ]);

                if ($salesImport->getFutureDateCount() > 0) {
                    $importWarning = $this->buildFutureDateWarning($salesImport);

                    Log::warning('detail-sales.import tanggal melewati bulan berjalan', [
                        'tab' => $tab,
                        'session_id' => $session->id,
                        'future_date_count' => $salesImport->getFutureDateCount(),
                        'samples' => $salesImport->getFutureDateSamples(),
                        'cutoff' => $salesImport->getFutureDateCutoff(),
                    ]);
                }
            }

            $redirect = redirect()
                ->route('detail-sales.index', ['tab' => $tab])
                ->with('success', $message);

            if (isset($importWarning)) {
                $redirect->with('import_warning', $importWarning);
            }

            return $redirect;
        } catch (ValidationException $e) {
            $this->finalizeSession($session, $salesImport, true);
            $errors = $e->errors();
            $firstErrors = [];
            foreach ($errors as $field => $messages) {
                $firstErrors[] = "{$field}: " . $messages[0];
            }

            Log::warning('detail-sales.import validasi Excel gagal', [
                'tab' => $tab,
                'import_type' => $importType,
                'errors' => array_slice($firstErrors, 0, 5),
            ]);

            return redirect()
                ->route('detail-sales.index', ['tab' => $tab])
                ->with('error', 'Validasi gagal - ' . implode('; ', array_slice($firstErrors, 0, 5)));
        } catch (\Exception $e) {
            $this->finalizeSession($session, $salesImport, true);

            Log::error('detail-sales.import gagal', [
                'tab' => $tab,
                'import_type' => $importType,
                'file' => $request->file('file')?->getClientOriginalName(),
                'session_id' => $session?->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('detail-sales.index', ['tab' => $tab])
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    /**
     * Pesan peringatan bila hasil import memuat tanggal melewati akhir bulan berjalan
     * (indikasi tanggal/bulan tertukar pada file export).
     */
    private function buildFutureDateWarning(DetailSalesImport $import): string
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $cutoff = $import->getFutureDateCutoff();
        $label = $cutoff
            ? ($bulan[(int) substr($cutoff, 5, 2)] . ' ' . substr($cutoff, 0, 4))
            : 'bulan berjalan';

        $samples = $import->getFutureDateSamples();
        $contoh = $samples === []
            ? ''
            : ' Contoh dokumen: ' . implode(', ', $samples) . '.';

        return $import->getFutureDateCount()
            . " baris bertanggal melewati akhir {$label} ikut terimpor."
            . $contoh
            . ' Kemungkinan tanggal dan bulan tertukar (format d/m/Y vs m/d/Y) — periksa kembali sebelum data dipakai.';
    }

    /**
     * Tutup sesi import: simpan counts; hapus sesi bila gagal tanpa jejak data.
     */
    private function finalizeSession(?ImportSession $session, ?DetailSalesImport $import, bool $failed): void
    {
        if ($session === null) {
            return;
        }

        if ($import !== null) {
            $session->update([
                'new_count' => $import->getCreatedCount(),
                'changed_count' => $import->getChangedCount(),
                'unchanged_count' => $import->getUnchangedCount(),
                'skipped_count' => $import->getSkippedCount(),
            ]);
        }

        if ($failed && !$session->details()->exists() && $session->skipped_count === 0) {
            $session->delete();
        }
    }

    public function items(Request $request, string $docNumber)
    {
        $tab = $this->resolveTab($request);

        $items = DetailSaleItem::where('source_type', $tab)
            ->where('doc_number', $docNumber)
            ->orderBy('id')
            ->get([
                'item_no',
                'item_description',
                'unit_price',
                'qty',
                'unit',
                'amount',
                'discount',
            ]);

        return response()->json([
            'doc_number' => $docNumber,
            'items' => $items,
            'count' => $items->count(),
        ]);
    }

    public function clear(Request $request)
    {
        $tab = $this->resolveTab($request);

        $deletedItems = DetailSaleItem::where('source_type', $tab)->delete();
        $deleted = DetailSale::where('source_type', $tab)->delete();

        if ($deleted + $deletedItems === 0) {
            return redirect()
                ->route('detail-sales.index', ['tab' => $tab])
                ->with('error', 'Tidak ada data untuk dihapus di tab ' . $tab . '.');
        }

        return redirect()
            ->route('detail-sales.index', ['tab' => $tab])
            ->with('success', "Berhasil menghapus {$deleted} invoice dan {$deletedItems} item Detail Sales tab {$tab}.");
    }

    /**
     * Hapus data per periode (bulan+tahun) pada tab tertentu — invoice + item.
     * Untuk koreksi salah upload per bulan tanpa menghapus seluruh tab.
     */
    public function clearPeriod(Request $request)
    {
        $data = $request->validate([
            'tab' => ['required', Rule::in([DetailSale::SOURCE_INTERNAL, DetailSale::SOURCE_EXTERNAL])],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2020,2030'],
        ]);

        $tab = $data['tab'];
        $start = sprintf('%04d-%02d-01', $data['tahun'], $data['bulan']);
        $end = date('Y-m-t', strtotime($start));

        [$deleted, $deletedItems] = DB::transaction(function () use ($tab, $start, $end) {
            $docNumbers = DetailSale::where('source_type', $tab)
                ->whereBetween('transaction_date', [$start, $end])
                ->pluck('doc_number');

            $items = 0;
            if ($docNumbers->isNotEmpty()) {
                $items = DetailSaleItem::where('source_type', $tab)
                    ->whereIn('doc_number', $docNumbers)
                    ->delete();
            }

            $sales = DetailSale::where('source_type', $tab)
                ->whereBetween('transaction_date', [$start, $end])
                ->delete();

            return [$sales, $items];
        });

        $periode = Carbon::parse($start)->locale('id')->translatedFormat('F Y');

        if ($deleted + $deletedItems === 0) {
            return redirect()
                ->route('detail-sales.index', ['tab' => $tab])
                ->with('error', "Tidak ada data tab {$tab} periode {$periode} untuk dihapus.");
        }

        return redirect()
            ->route('detail-sales.index', ['tab' => $tab])
            ->with('success', "Berhasil menghapus {$deleted} invoice dan {$deletedItems} item tab {$tab} periode {$periode}.");
    }

    public function export(Request $request)
    {
        $data = $request->validate([
            'tab' => ['required', Rule::in([DetailSale::SOURCE_INTERNAL, DetailSale::SOURCE_EXTERNAL])],
            'format' => ['required', Rule::in(['xml', 'csv', 'xls'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $docNumbers = DetailSale::whereIn('id', $data['ids'])
            ->where('source_type', $data['tab'])
            ->orderBy('id')
            ->pluck('doc_number');

        $filename = 'detail-sales-' . $data['tab'] . '-' . now()->format('Ymd-His') . '.txt';

        return response(implode("\n", $docNumbers->all()) . "\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export "XML External": konversi invoice internal → 3 dokumen XML
     * (Sales Order, Delivery Order, Sales Invoice) memakai Mapping + Settings.
     * Menerima ids[] (bulk) atau doc_number (satuan). Balas JSON berisi 3 file.
     */
    public function exportXmlExternal(Request $request)
    {
        $data = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
            'doc_number' => ['nullable', 'string', 'max:50'],
        ]);

        $docNumbers = [];
        if (! empty($data['ids'])) {
            $docNumbers = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
                ->whereIn('id', $data['ids'])
                ->orderBy('id')
                ->pluck('doc_number')
                ->all();
        } elseif (! empty($data['doc_number'])) {
            $docNumbers = [$data['doc_number']];
        }

        if ($docNumbers === []) {
            return response()->json(['ok' => false, 'message' => 'Tidak ada invoice internal yang dipilih.'], 422);
        }

        $result = (new ExternalXmlBuilder())->build($docNumbers);

        if ($result['count'] === 0) {
            return response()->json(['ok' => false, 'message' => 'Invoice tidak ditemukan di tab internal.'], 422);
        }

        $single = count($docNumbers) === 1 ? $docNumbers[0] : null;
        $suffix = ($single !== null ? ' ' . $single : '') . ' ' . now()->format('Ymd-His');

        return response()->json([
            'ok' => true,
            'count' => $result['count'],
            'files' => [
                ['name' => 'Ext Sales Order' . $suffix . '.xml', 'content' => $result['order']],
                ['name' => 'Ext Delivery Order' . $suffix . '.xml', 'content' => $result['delivery']],
                ['name' => 'Ext Sales Invoice' . $suffix . '.xml', 'content' => $result['invoice']],
            ],
        ]);
    }

    /**
     * Export "XML Coretax": konversi invoice (tab internal/external) menjadi satu
     * file TaxInvoiceBulk memakai Mapping Customer/Product/Unit, Kata Kunci, dan Settings.
     * Menerima ids[] (bulk) atau doc_number (satuan) + tab.
     */
    public function exportXmlCoretax(Request $request)
    {
        $data = $request->validate([
            'tab' => ['required', Rule::in([DetailSale::SOURCE_INTERNAL, DetailSale::SOURCE_EXTERNAL])],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
            'doc_number' => ['nullable', 'string', 'max:50'],
        ]);

        $tab = $data['tab'];
        $docNumbers = [];
        if (! empty($data['ids'])) {
            $docNumbers = DetailSale::where('source_type', $tab)
                ->whereIn('id', $data['ids'])
                ->orderBy('id')
                ->pluck('doc_number')
                ->all();
        } elseif (! empty($data['doc_number'])) {
            $docNumbers = [$data['doc_number']];
        }

        if ($docNumbers === []) {
            return response()->json(['ok' => false, 'message' => 'Tidak ada invoice yang dipilih.'], 422);
        }

        $result = (new CoretaxXmlBuilder())->build($docNumbers, $tab);

        // Dokumen bermasalah dilewati satu per satu; export tetap jalan untuk
        // dokumen yang valid dan daftar yang dilewati dikirim ke pengguna.
        if ($result['count'] === 0) {
            return response()->json([
                'ok' => false,
                'message' => $result['skipped'] !== []
                    ? 'Tidak ada dokumen yang bisa diexport.'
                    : 'Invoice tidak ditemukan di tab ' . $tab . '.',
                'errors' => $result['skipped'],
                'unmapped' => $result['unmapped'] ?? [],
                'unmapped_default' => $result['unmapped_default'] ?? null,
            ], 422);
        }

        $single = count($docNumbers) === 1 ? $docNumbers[0] : null;
        $suffix = ($single !== null ? ' ' . $single : '') . ' ' . now()->format('Ymd-His');

        return response()->json([
            'ok' => true,
            'count' => $result['count'],
            'skipped' => $result['skipped'],
            'unmapped' => $result['unmapped'] ?? [],
            'unmapped_default' => $result['unmapped_default'] ?? null,
            'files' => [
                ['name' => 'Coretax' . $suffix . '.xml', 'content' => $result['xml']],
            ],
        ]);
    }

    /**
     * Aksi "Ubah Status Fp" (per-baris via doc_number, atau bulk via ids[]).
     * 'gung_gung' = FP memang tidak dibuat; '' / kosong = kembalikan ke NULL.
     * Disimpan di detail_sales.fp_status (tab external).
     */
    public function updateFakturPajakStatus(Request $request)
    {
        $data = $request->validate([
            'doc_number' => ['nullable', 'string', 'max:50'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $status = $data['status'] ?? null;
        if ($status === '') {
            $status = null;
        }

        $query = DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL);
        if (! empty($data['ids'])) {
            $query->whereIn('id', $data['ids']);
        } elseif (! empty($data['doc_number'])) {
            $query->where('doc_number', $data['doc_number']);
        } else {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak ada baris yang dipilih.',
            ], 422);
        }

        $updated = $query->update(['fp_status' => $status]);

        if ($updated === 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Invoice tidak ditemukan di tab external.',
            ], 422);
        }

        return response()->json(['ok' => true, 'updated' => $updated]);
    }

    public function createExternalDoc(Request $request)
    {
        $data = $request->validate([
            'doc_number' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(['so', 'do', 'inv'])],
        ]);

        $type = $data['type'];
        $doc = $data['doc_number'];
        $module = ExternalDocument::MODULES[$type];

        $existing = ExternalDocument::where('doc_number', $doc)->where('type', $type)->first();
        if ($existing) {
            return response()->json([
                'ok' => true,
                'already' => true,
                'number' => $existing->accurate_number,
                'message' => strtoupper($type) . ' ' . $existing->accurate_number . ' sudah ada di Accurate.',
            ]);
        }

        $client = new AccurateApiClient();
        [$custMap, $itemCodeMap, $itemNameMap] = $this->externalMaps();

        $prep = $this->prepareExternalDoc($doc, $type, $client, $custMap, $itemCodeMap, $itemNameMap);

        if (! $prep['ok']) {
            return $this->externalFailure($prep['reasons']);
        }

        $header = $prep['header'];
        $prepared = $prep['prepared'];
        $payload = $prep['payload'];
        $notes = $prep['notes'];
        $custCode = (string) $header->customer_code;
        $customerNo = (string) ($payload['customerNo'] ?? '');

        // Dokumen sudah pernah dibuat di Accurate (mis. sebelum fitur ini) -> adopsi nomornya.
        $found = $client->findByNumber($module, $doc);
        if ($found !== null) {
            $this->storeExternalDocument($doc, $type, $payload, [
                'ok' => true,
                'status' => 200,
                'body' => ['r' => $found],
                'message' => null,
            ]);

            if ($type === ExternalDocument::TYPE_INV) {
                $this->mirrorExternalInvoice($header, $payload, $client);
            }

            return response()->json([
                'ok' => true,
                'already' => true,
                'number' => (string) ($found['number'] ?? $doc),
                'message' => strtoupper($type) . ' ' . ($found['number'] ?? $doc) . ' sudah ada di Accurate.',
                'notes' => $notes,
            ]);
        }

        $result = $client->post('/accurate/api/' . $module . '/save.do', $payload);

        if (! $result['ok']) {
            $reason = $result['message'] ?? ('Accurate menolak pembuatan ' . strtoupper($type) . '.');

            // Pelanggan non-aktif di Accurate hampir selalu berarti mapping ganda:
            // aplikasi memilih mapping pertama, yang bisa jadi customer lama.
            if (preg_match('/non\s*aktif|tidak\s*aktif|suspended/i', $reason)) {
                $reason = $this->nonActiveCustomerHint($reason, $custCode, $customerNo);
            }

            $payload = [
                'ok' => false,
                'message' => $reason,
                'errors' => [$reason],
            ];

            $unitIssues = $this->missingUnitIssues($reason, $prepared);
            if ($unitIssues !== []) {
                $payload['unit_issues'] = $unitIssues;
            }

            return response()->json($payload, 422);
        }

        $r = $result['body']['r'] ?? [];
        $number = (string) ($r['number'] ?? $doc);

        $this->storeExternalDocument($doc, $type, $payload, $result);

        if ($type === ExternalDocument::TYPE_INV) {
            $this->mirrorExternalInvoice($header, $payload, $client);
        }

        return response()->json([
            'ok' => true,
            'number' => $number,
            'status' => $r['status'] ?? null,
            'message' => strtoupper($type) . ' ' . $number . ' berhasil dibuat di Accurate.',
            'notes' => $notes,
        ]);
    }

    /**
     * Buat SO/DO/INV di Accurate dari data tab EXTERNAL.
     *
     * Dipakai untuk dokumen yang hanya ada di tab external (mis. hasil import
     * yang belum pernah dibuat di Accurate). Sumber harga/qty/satuan adalah
     * baris external apa adanya; tidak ada konversi satuan dan tidak ada mirror
     * ulang karena tab external memang sudah berisi data tersebut.
     */
    public function createExternalDocFromExternal(Request $request)
    {
        $data = $request->validate([
            'doc_number' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(['so', 'do', 'inv'])],
        ]);

        $type = $data['type'];
        $doc = $data['doc_number'];
        $module = ExternalDocument::MODULES[$type];

        $existing = ExternalDocument::where('doc_number', $doc)->where('type', $type)->first();
        if ($existing) {
            return response()->json([
                'ok' => true,
                'already' => true,
                'number' => $existing->accurate_number,
                'message' => strtoupper($type) . ' ' . $existing->accurate_number . ' sudah ada di Accurate.',
                'notes' => [],
            ]);
        }

        $client = new AccurateApiClient();

        if (! $client->configured()) {
            return $this->externalFailure(['API Token dan Signature Secret Accurate belum diisi di Settings.']);
        }

        // DO sudah difakturkan dengan nomor lain (mis. SI.2026.11.00008):
        // adopsi invoice yang sudah ada, jangan buat invoice baru.
        if ($type === ExternalDocument::TYPE_INV) {
            $adopted = $this->adoptExistingInvoice($doc, $client);

            if ($adopted !== null) {
                return response()->json([
                    'ok' => true,
                    'already' => true,
                    'number' => $adopted['number'],
                    'message' => 'INV ' . $adopted['number'] . ' sudah ada di Accurate. Tahap diperbarui.',
                    'notes' => [],
                ]);
            }
        }

        $prep = $this->prepareExternalDocFromExternal($doc, $type, $client);

        if (! $prep['ok']) {
            return $this->externalFailure($prep['reasons']);
        }

        $payload = $prep['payload'];

        // Dokumen sudah ada di Accurate -> adopsi nomornya (jangan buat dobel).
        $found = $client->findByNumber($module, $doc);
        if ($found !== null) {
            $this->storeExternalDocument($doc, $type, $payload, [
                'ok' => true,
                'status' => 200,
                'body' => ['r' => $found],
                'message' => null,
            ]);

            return response()->json([
                'ok' => true,
                'already' => true,
                'number' => (string) ($found['number'] ?? $doc),
                'message' => strtoupper($type) . ' ' . ($found['number'] ?? $doc) . ' sudah ada di Accurate.',
                'notes' => $prep['notes'],
            ]);
        }

        $result = $client->post('/accurate/api/' . $module . '/save.do', $payload);

        if (! $result['ok']) {
            $reason = $result['message'] ?? ('Accurate menolak pembuatan ' . strtoupper($type) . '.');

            if (preg_match('/non\s*aktif|tidak\s*aktif|suspended/i', $reason)) {
                $reason = $this->nonActiveCustomerHint($reason, (string) $prep['header']->customer_code, (string) $payload['customerNo']);
            }

            return response()->json([
                'ok' => false,
                'message' => $reason,
                'errors' => [$reason],
            ], 422);
        }

        $r = $result['body']['r'] ?? [];
        $number = (string) ($r['number'] ?? $doc);

        $this->storeExternalDocument($doc, $type, $payload, $result);

        return response()->json([
            'ok' => true,
            'number' => $number,
            'status' => $r['status'] ?? null,
            'message' => strtoupper($type) . ' ' . $number . ' berhasil dibuat di Accurate.',
            'notes' => $prep['notes'],
        ]);
    }

    /**
     * Siapkan payload dokumen external tanpa memanggil save.do.
     *
     * Dipakai jalur satuan (createExternalDoc) dan jalur bulk supaya aturan
     * retur, mapping item, PPN/TNPWP, termin, dan gudang tetap identik.
     *
     * @param  array<string,string>  $custMap
     * @param  array<string,string>  $itemCodeMap
     * @param  array<string,string>  $itemNameMap
     * @return array{ok:bool,reasons:array<int,string>,header:?DetailSale,prepared:array,notes:array<int,string>,payload:array<string,mixed>,so_number:string,do_number:string}
     */
    private function prepareExternalDoc(
        string $doc,
        string $type,
        AccurateApiClient $client,
        array $custMap,
        array $itemCodeMap,
        array $itemNameMap,
        ?array $doDetail = null
    ): array {
        $header = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
            ->where('doc_number', $doc)
            ->first();

        if (! $header) {
            return $this->preparedFailure(['Dokumen ' . $doc . ' tidak ditemukan di tab internal.']);
        }

        $lines = DetailSaleItem::where('source_type', DetailSaleItem::SOURCE_INTERNAL)
            ->where('doc_number', $doc)
            ->orderBy('id')
            ->get();

        if ($lines->isEmpty()) {
            return $this->preparedFailure(['Item dokumen ' . $doc . ' tidak ditemukan.']);
        }

        $reasons = [];

        if ($header->transaction_date === null) {
            $reasons[] = 'Tanggal transaksi ' . $doc . ' kosong.';
        }

        if (! $client->configured()) {
            $reasons[] = 'API Token dan Signature Secret Accurate belum diisi di Settings.';
        }

        $custCode = (string) $header->customer_code;
        $customerNo = trim((string) ($custMap[$custCode] ?? ''));
        if ($customerNo === '') {
            $reasons[] = 'Mapping Customer ' . $custCode . ' (' . $header->customer_name . ') tidak ditemukan.';
        }

        $soDoc = $type === ExternalDocument::TYPE_SO
            ? null
            : ExternalDocument::where('doc_number', $doc)->where('type', ExternalDocument::TYPE_SO)->first();
        $doDoc = $type === ExternalDocument::TYPE_INV
            ? ExternalDocument::where('doc_number', $doc)->where('type', ExternalDocument::TYPE_DO)->first()
            : null;

        if ($client->configured()) {
            if ($type === ExternalDocument::TYPE_DO && ! $soDoc
                && $client->findByNumber(ExternalDocument::MODULES[ExternalDocument::TYPE_SO], $doc) === null) {
                $reasons[] = 'SO ' . $doc . ' belum ada di Accurate. Buat SO terlebih dahulu.';
            }

            if ($type === ExternalDocument::TYPE_INV && ! $doDoc
                && $client->findByNumber(ExternalDocument::MODULES[ExternalDocument::TYPE_DO], $doc) === null) {
                $reasons[] = 'DO ' . $doc . ' belum ada di Accurate. Buat DO terlebih dahulu.';
            }
        }

        // Kurangi qty per item dengan qty retur (retur bernilai negatif).
        $prepared = [];
        foreach ($lines as $line) {
            $prepared[] = ['line' => $line, 'qty' => (float) $line->qty];
        }

        $notes = [];
        $returns = SalesReturn::where('invoice_no', $doc)->orderBy('id')->get();

        foreach ($returns as $ret) {
            $remaining = $this->refineReturnQty($ret, $prepared, $itemCodeMap);
            $found = false;
            $retExternal = trim((string) ($itemCodeMap[(string) $ret->item_no] ?? ''));

            foreach ($prepared as &$row) {
                if (! $this->isSameReturnItem($row['line'], $ret, $itemCodeMap)) {
                    continue;
                }

                $found = true;

                if ($remaining >= -0.00001) {
                    break;
                }

                $deduct = min($row['qty'], -$remaining);
                if ($deduct <= 0) {
                    continue;
                }

                $before = $row['qty'];
                $row['qty'] -= $deduct;
                $remaining += $deduct;
                $notes[] = $ret->return_no . ' - ' . $ret->item_no . ': qty '
                    . $this->formatQty($before) . ' → ' . $this->formatQty($row['qty']);
            }
            unset($row);

            if ($remaining < -0.00001) {
                $reasons[] = 'Qty retur ' . $ret->item_no . ' (' . $ret->return_no . ') melebihi qty sales.';
            } elseif (! $found) {
                $reasons[] = $retExternal === ''
                    ? 'Item retur ' . $ret->item_no . ' (' . $ret->return_no . ') tidak ada di mapping item.'
                    : 'Item retur ' . $ret->item_no . ' (' . $ret->return_no . ') tidak cocok dengan item invoice.';
            }
        }

        $prepared = array_values(array_filter($prepared, fn ($row) => $row['qty'] > 0.00001));

        if ($prepared === [] && $returns->isNotEmpty()) {
            $reasons[] = 'Semua item invoice sudah diretur (qty 0).';
        }

        foreach ($prepared as $row) {
            $code = (string) $row['line']->item_no;
            if (trim((string) ($itemCodeMap[$code] ?? '')) === '') {
                $reasons[] = 'Mapping Item ' . $code . ' (' . $row['line']->item_description . ') tidak ditemukan.';
            }
        }

        if ($reasons !== []) {
            return $this->preparedFailure($reasons);
        }

        foreach ($prepared as &$row) {
            $row['item_no_external'] = (string) $itemCodeMap[(string) $row['line']->item_no];
            $row['item_name_external'] = $this->externalItemName($row['line'], $itemNameMap);
        }
        unset($row);

        // Qty di data internal tersimpan dalam satuan dasar (mis. Crt), sedangkan
        // harga bisa dalam satuan lain (Kg/Pcs). Samakan qty dengan satuan yang
        // dikirim supaya Accurate tidak membaca qty dasar sebagai Kg/Pcs.
        $this->applyUnitConversion($prepared, $client);

        // Harga wajib sama dengan internal. Setelah konversi satuan, pastikan
        // qty x harga sepadan dengan nilai baris (setelah memperhitungkan diskon
        // invoice). Kalau tidak, dokumen tidak dikirim — lebih baik dicek dulu
        // daripada membuat invoice dengan angka karangan.
        $discRate = ((float) $header->amount + (float) $header->discount) > 0
            ? (float) $header->discount / ((float) $header->amount + (float) $header->discount)
            : 0.0;

        foreach ($prepared as $row) {
            $line = $row['line'];
            if ((float) $line->amount <= 0) {
                continue;
            }

            // Nilai baris diskalakan bila qty-nya berkurang karena retur.
            $scale = (float) $line->qty != 0.0 ? (float) $row['qty'] / (float) $line->qty : 1.0;
            $value = (float) $line->amount * $scale;

            $gross = (float) ($row['qty_send'] ?? $row['qty'])
                * (float) ($row['price_send'] ?? $line->unit_price);
            $tolerance = max(1.0, abs($value) * 0.01);

            if (abs($gross - $value) <= $tolerance) {
                continue; // amount = nilai kotor
            }
            if (abs($gross * (1 - $discRate) - $value) <= $tolerance) {
                continue; // amount = nilai setelah diskon
            }

            $reasons[] = 'Item ' . $line->item_no . ' (' . $line->item_description . '): qty x harga '
                . number_format($gross, 2, ',', '.') . ' tidak sama dengan nilai baris '
                . number_format($value, 2, ',', '.') . '. Periksa satuan/harga item.';
        }

        if ($reasons !== []) {
            return $this->preparedFailure($reasons);
        }

        $soNumber = (string) ($soDoc?->accurate_number ?? $doc);
        $doNumber = (string) ($doDoc?->accurate_number ?? $doc);

        $payload = $this->buildExternalPayload($type, $header, $prepared, $customerNo, $soNumber, $doNumber);

        if ($type === ExternalDocument::TYPE_INV) {
            $this->linkInvoiceLinesToDeliveryOrder(
                $payload,
                $client,
                $doNumber,
                $doDoc?->accurate_id !== null ? (int) $doDoc->accurate_id : null,
                $doDetail
            );
        }

        return [
            'ok' => true,
            'reasons' => [],
            'header' => $header,
            'prepared' => $prepared,
            'notes' => $notes,
            'payload' => $payload,
            'so_number' => $soNumber,
            'do_number' => $doNumber,
        ];
    }

    /**
     * Siapkan payload SO/DO/INV dari data tab EXTERNAL.
     *
     * Dipakai untuk dokumen yang hanya ada di tab external. Harga, qty, dan
     * satuan dikirim apa adanya (tanpa konversi); baris yang qty x harga-nya
     * tidak sama dengan nilai baris ditolak agar nilai dokumen tidak berubah.
     *
     * @return array{ok:bool,reasons:array<int,string>,header:?DetailSale,prepared:array,notes:array,payload:array,so_number:string,do_number:string}
     */
    private function prepareExternalDocFromExternal(
        string $doc,
        string $type,
        AccurateApiClient $client,
        ?array $doDetail = null
    ): array {
        $header = DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)
            ->where('doc_number', $doc)
            ->first();

        if (! $header) {
            return $this->preparedFailure(['Dokumen ' . $doc . ' tidak ditemukan di tab external.']);
        }

        $lines = DetailSaleItem::where('source_type', DetailSaleItem::SOURCE_EXTERNAL)
            ->where('doc_number', $doc)
            ->orderBy('id')
            ->get();

        if ($lines->isEmpty()) {
            return $this->preparedFailure(['Item dokumen ' . $doc . ' tidak ditemukan.']);
        }

        $reasons = [];

        if ($header->transaction_date === null) {
            $reasons[] = 'Tanggal transaksi ' . $doc . ' kosong.';
        }

        if (! $client->configured()) {
            $reasons[] = 'API Token dan Signature Secret Accurate belum diisi di Settings.';
        }

        $customerNo = trim((string) $header->customer_code);
        if ($customerNo === '') {
            $reasons[] = 'Dokumen ' . $doc . ' tidak punya Customer No. Accurate.';
        }

        $soDoc = $type === ExternalDocument::TYPE_SO
            ? null
            : ExternalDocument::where('doc_number', $doc)->where('type', ExternalDocument::TYPE_SO)->first();
        $doDoc = $type === ExternalDocument::TYPE_INV
            ? ExternalDocument::where('doc_number', $doc)->where('type', ExternalDocument::TYPE_DO)->first()
            : null;

        if ($client->configured()) {
            if ($type === ExternalDocument::TYPE_DO && ! $soDoc
                && $client->findByNumber(ExternalDocument::MODULES[ExternalDocument::TYPE_SO], $doc) === null) {
                $reasons[] = 'SO ' . $doc . ' belum ada di Accurate. Buat SO terlebih dahulu.';
            }

            if ($type === ExternalDocument::TYPE_INV && ! $doDoc
                && $client->findByNumber(ExternalDocument::MODULES[ExternalDocument::TYPE_DO], $doc) === null) {
                $reasons[] = 'DO ' . $doc . ' belum ada di Accurate. Buat DO terlebih dahulu.';
            }
        }

        $prepared = [];

        foreach ($lines as $line) {
            $qty = (float) $line->qty;
            $price = (float) $line->unit_price;
            $value = (float) $line->amount;

            if (trim((string) $line->item_no) === '') {
                $reasons[] = 'Item ' . $line->item_description . ' belum punya kode barang Accurate.';
                continue;
            }

            if ($qty <= 0) {
                $reasons[] = 'Item ' . $line->item_no . ' (' . $line->item_description . '): qty harus lebih dari 0.';
                continue;
            }

            if ($value > 0) {
                $tolerance = max(1.0, abs($value) * 0.01);

                if (abs($qty * $price - $value) > $tolerance) {
                    $reasons[] = 'Item ' . $line->item_no . ' (' . $line->item_description . '): qty x harga '
                        . number_format($qty * $price, 2, ',', '.') . ' tidak sama dengan nilai baris '
                        . number_format($value, 2, ',', '.') . '. Periksa satuan/harga item.';
                    continue;
                }
            }

            $prepared[] = [
                'line' => $line,
                'qty' => $qty,
                'qty_send' => $qty,
                'unit_send' => (string) $line->unit,
                'price_send' => $price,
                'item_no_external' => (string) $line->item_no,
                'item_name_external' => (string) $line->item_description,
            ];
        }

        if ($reasons !== []) {
            return $this->preparedFailure($reasons);
        }

        $soNumber = (string) ($soDoc?->accurate_number ?? $doc);
        $doNumber = (string) ($doDoc?->accurate_number ?? $doc);

        // Payment term: kode external dipetakan ke kode internal bila ada.
        $termCode = MappingCustomer::where('kode_external', $customerNo)->value('kode_internal');
        $termCode = $termCode !== null && trim((string) $termCode) !== ''
            ? (string) $termCode
            : $customerNo;

        $payload = $this->buildExternalPayload(
            $type,
            $header,
            $prepared,
            $customerNo,
            $soNumber,
            $doNumber,
            $termCode
        );

        if ($type === ExternalDocument::TYPE_INV) {
            $this->linkInvoiceLinesToDeliveryOrder(
                $payload,
                $client,
                $doNumber,
                $doDoc?->accurate_id !== null ? (int) $doDoc->accurate_id : null,
                $doDetail
            );
        }

        return [
            'ok' => true,
            'reasons' => [],
            'header' => $header,
            'prepared' => $prepared,
            'notes' => [],
            'payload' => $payload,
            'so_number' => $soNumber,
            'do_number' => $doNumber,
        ];
    }

    /**
     * @param  array<int,string>  $reasons
     * @return array{ok:false,reasons:array<int,string>,header:null,prepared:array,notes:array,payload:array,so_number:string,do_number:string}
     */
    private function preparedFailure(array $reasons): array
    {
        return [
            'ok' => false,
            'reasons' => array_values($reasons),
            'header' => null,
            'prepared' => [],
            'notes' => [],
            'payload' => [],
            'so_number' => '',
            'do_number' => '',
        ];
    }

    /**
     * Buat dokumen external secara bulk lewat endpoint resmi Accurate.
     *
     * Satu request menangani satu tahap (so/do/inv) untuk sekumpulan dokumen.
     * Tahap berikutnya dijalankan terpisah oleh UI supaya progres terlihat dan
     * dokumen yang gagal di satu tahap tidak dilanjutkan.
     */
    public function createExternalDocsBulk(Request $request)
    {
        $data = $request->validate([
            'docs' => ['required', 'array', 'min:1'],
            'docs.*' => ['required', 'string', 'max:50'],
            'types' => ['required', 'array', 'min:1'],
            'types.*' => ['required', Rule::in(['so', 'do', 'inv'])],
            'source' => ['nullable', Rule::in(['internal', 'external'])],
        ]);

        $source = $data['source'] ?? 'internal';

        $docs = [];
        foreach ($data['docs'] as $doc) {
            $doc = trim((string) $doc);
            if ($doc !== '' && ! in_array($doc, $docs, true)) {
                $docs[] = $doc;
            }
        }

        $types = array_values(array_intersect(
            [ExternalDocument::TYPE_SO, ExternalDocument::TYPE_DO, ExternalDocument::TYPE_INV],
            array_values(array_unique($data['types']))
        ));

        if ($docs === [] || $types === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak ada dokumen atau tahap yang diproses.',
                'errors' => ['Tidak ada dokumen atau tahap yang diproses.'],
            ], 422);
        }

        // Bulk bisa berjalan puluhan detik (Accurate memproses tiap dokumen).
        @set_time_limit(0);

        $client = new AccurateApiClient();

        if (! $client->configured()) {
            return response()->json([
                'ok' => false,
                'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.',
                'errors' => ['API Token dan Signature Secret Accurate belum diisi di Settings.'],
            ], 422);
        }

        [$custMap, $itemCodeMap, $itemNameMap] = $this->externalMaps();

        $results = [];
        $timings = [];

        foreach ($types as $type) {
            $startedAt = microtime(true);
            $module = ExternalDocument::MODULES[$type];
            $typeOk = 0;
            $typeFailed = 0;

            // Lewati dokumen yang sudah tercatat (tidak perlu dibuat ulang).
            $existing = ExternalDocument::where('type', $type)
                ->whereIn('doc_number', $docs)
                ->get()
                ->keyBy('doc_number');

            $queue = [];
            foreach ($docs as $doc) {
                if (isset($existing[$doc])) {
                    $results[] = [
                        'doc' => $doc,
                        'type' => $type,
                        'ok' => true,
                        'already' => true,
                        'number' => (string) $existing[$doc]->accurate_number,
                        'message' => strtoupper($type) . ' ' . $existing[$doc]->accurate_number . ' sudah ada di Accurate.',
                        'notes' => [],
                        'unit_issues' => [],
                    ];
                    $typeOk++;

                    continue;
                }

                $queue[] = $doc;
            }

            // Detail DO diambil paralel supaya pembuatan invoice tidak menunggu
            // satu per satu (dipakai untuk deliveryOrderDetailId).
            $doDetails = [];
            if ($type === ExternalDocument::TYPE_INV && $queue !== []) {
                $doRows = ExternalDocument::where('type', ExternalDocument::TYPE_DO)
                    ->whereIn('doc_number', $queue)
                    ->get()
                    ->keyBy('doc_number');

                $detailDocs = [];
                $detailRequests = [];
                foreach ($queue as $doc) {
                    $doId = $doRows[$doc]->accurate_id ?? null;
                    if ($doId === null) {
                        continue;
                    }

                    $detailDocs[] = $doc;
                    $detailRequests[] = [
                        'path' => '/accurate/api/delivery-order/detail.do',
                        'query' => ['id' => (int) $doId],
                    ];
                }

                if ($detailRequests !== []) {
                    $detailResponses = $client->getMany($detailRequests, 6);

                    foreach ($detailDocs as $i => $doc) {
                        $detail = $detailResponses[$i]['body']['d'] ?? null;
                        if (is_array($detail)) {
                            $doDetails[$doc] = $detail;
                        }
                    }
                }
            }

            $batch = [];
            foreach ($queue as $doc) {
                // DO sudah difakturkan dengan nomor lain -> adopsi (hanya tab external).
                if ($source === 'external' && $type === ExternalDocument::TYPE_INV) {
                    $adopted = $this->adoptExistingInvoice($doc, $client);

                    if ($adopted !== null) {
                        $results[] = [
                            'doc' => $doc,
                            'type' => $type,
                            'ok' => true,
                            'already' => true,
                            'number' => $adopted['number'],
                            'message' => 'INV ' . $adopted['number'] . ' sudah ada di Accurate. Tahap diperbarui.',
                            'notes' => [],
                            'unit_issues' => [],
                        ];
                        $typeOk++;

                        continue;
                    }
                }

                $prep = $source === 'external'
                    ? $this->prepareExternalDocFromExternal($doc, $type, $client, $doDetails[$doc] ?? null)
                    : $this->prepareExternalDoc(
                        $doc,
                        $type,
                        $client,
                        $custMap,
                        $itemCodeMap,
                        $itemNameMap,
                        $doDetails[$doc] ?? null
                    );

                if (! $prep['ok']) {
                    $results[] = [
                        'doc' => $doc,
                        'type' => $type,
                        'ok' => false,
                        'already' => false,
                        'number' => null,
                        'message' => $prep['reasons'][0] ?? 'Dokumen belum siap dibuat.',
                        'errors' => $prep['reasons'],
                        'notes' => [],
                        'unit_issues' => [],
                    ];
                    $typeFailed++;

                    continue;
                }

                $prep['doc'] = $doc;
                $batch[] = $prep;
            }

            // Nama customer untuk mirror invoice: sekali per tahap, cache 1 jam.
            // Hanya diperlukan saat sumbernya tab internal (yang di-mirror).
            $customerNames = [];
            if ($source === 'internal' && $type === ExternalDocument::TYPE_INV && $batch !== []) {
                $customerNames = $this->accurateCustomerNames($client, array_map(
                    fn (array $item) => (string) ($item['payload']['customerNo'] ?? ''),
                    $batch
                ));
            }

            // Accurate memproses dokumen berurutan di dalam satu request bulk, jadi
            // kelompok kecil yang paralel (maks 6 request) jauh lebih cepat.
            $groups = array_chunk($batch, 4);
            $payloadGroups = array_map(fn (array $group) => array_column($group, 'payload'), $groups);
            $bulkGroups = $client->bulkSaveMany($module, $payloadGroups, 6);

            $pending = [];
            foreach ($groups as $groupIndex => $group) {
                $bulk = $bulkGroups[$groupIndex] ?? [
                    'ok' => false,
                    'status' => 0,
                    'results' => [],
                    'message' => 'Tidak ada respons bulk dari Accurate.',
                    'unsupported' => false,
                ];

                foreach ($group as $index => $item) {
                    $pending[] = [$bulk, $index, $item];
                }
            }

            foreach ($pending as [$bulk, $index, $item]) {
                    $doc = (string) $item['doc'];
                    $payload = $item['payload'];
                    $customerNo = (string) ($payload['customerNo'] ?? '');

                    if ($bulk['unsupported']) {
                        // Endpoint bulk tidak tersedia -> kembali ke save.do per dokumen.
                        $single = $client->post('/accurate/api/' . $module . '/save.do', $payload);
                        $row = [
                            'ok' => (bool) ($single['ok'] ?? false),
                            'status' => (int) ($single['status'] ?? 0),
                            'doc' => is_array($single['body']['r'] ?? null) ? $single['body']['r'] : [],
                            'body' => is_array($single['body'] ?? null) ? $single['body'] : [],
                            'message' => $single['message'] ?? null,
                        ];
                    } else {
                        $row = $bulk['results'][$index] ?? [
                            'ok' => false,
                            'status' => (int) ($bulk['status'] ?? 0),
                            'doc' => [],
                            'body' => [],
                            'message' => $bulk['message'] ?? 'Respons bulk Accurate tidak memuat dokumen ini.',
                        ];
                    }

                    if ($row['ok']) {
                        // Jaga-jaga bila bentuk respons tidak memuat id/nomor:
                        // lengkapi dari list.do supaya accurate_id tetap tercatat.
                        if (empty($row['doc']['id'])) {
                            $found = $client->findByNumber($module, $doc);
                            if ($found !== null) {
                                $row['doc'] = $found;
                            }
                        }

                        $this->storeExternalDocument($doc, $type, $payload, [
                            'ok' => true,
                            'status' => $row['status'] ?? 200,
                            'body' => ['r' => $row['doc'] ?? []],
                            'message' => null,
                        ]);

                        if ($source === 'internal' && $type === ExternalDocument::TYPE_INV) {
                            $this->mirrorExternalInvoice($item['header'], $payload, $client, $customerNames[$customerNo] ?? null);
                        }

                        $number = (string) (($row['doc']['number'] ?? null) ?: $doc);
                        $results[] = [
                            'doc' => $doc,
                            'type' => $type,
                            'ok' => true,
                            'already' => false,
                            'number' => $number,
                            'message' => strtoupper($type) . ' ' . $number . ' berhasil dibuat di Accurate.',
                            'notes' => $item['notes'],
                            'unit_issues' => [],
                        ];
                        $typeOk++;

                        continue;
                    }

                    $message = (string) ($row['message'] ?? ('Accurate menolak pembuatan ' . strtoupper($type) . '.'));

                    // Fallback adopsi: dokumen mungkin sudah ada di Accurate walau
                    // belum tercatat lokal (nomor dokumen sudah dipakai).
                    $found = $client->findByNumber($module, $doc);

                    if ($found !== null) {
                        $this->storeExternalDocument($doc, $type, $payload, [
                            'ok' => true,
                            'status' => 200,
                            'body' => ['r' => $found],
                            'message' => null,
                        ]);

                        if ($source === 'internal' && $type === ExternalDocument::TYPE_INV) {
                            $this->mirrorExternalInvoice($item['header'], $payload, $client, $customerNames[$customerNo] ?? null);
                        }

                        $number = (string) ($found['number'] ?? $doc);
                        $results[] = [
                            'doc' => $doc,
                            'type' => $type,
                            'ok' => true,
                            'already' => true,
                            'number' => $number,
                            'message' => strtoupper($type) . ' ' . $number . ' sudah ada di Accurate.',
                            'notes' => $item['notes'],
                            'unit_issues' => [],
                        ];
                        $typeOk++;

                        continue;
                    }

                    if (preg_match('/non\s*aktif|tidak\s*aktif|suspended/i', $message)) {
                        $message = $this->nonActiveCustomerHint($message, (string) $item['header']->customer_code, $customerNo);
                    }

                    $unitIssues = $this->missingUnitIssues($message, $item['prepared']);

                    $results[] = [
                        'doc' => $doc,
                        'type' => $type,
                        'ok' => false,
                        'already' => false,
                        'number' => null,
                        'message' => $message,
                        'errors' => [$message],
                        'notes' => $item['notes'],
                        'unit_issues' => $unitIssues,
                    ];
                    $typeFailed++;
                }

            $timings[$type] = (int) round((microtime(true) - $startedAt) * 1000);

            Log::info('detail-sales.bulk-create', [
                'type' => $type,
                'docs' => count($queue),
                'ok' => $typeOk,
                'failed' => $typeFailed,
                'ms' => $timings[$type],
            ]);
        }

        return response()->json([
            'ok' => true,
            'results' => $results,
            'timings' => $timings,
        ]);
    }

    public function createItemUnit(Request $request)
    {
        $data = $request->validate([
            'doc_number' => ['nullable', 'string', 'max:50'],
            'item_no' => ['required', 'string', 'max:100'],
            'item_name' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:20'],
            'ratio' => ['required', 'numeric', 'gt:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $result = (new AccurateItemUnitService())->addUnit(
            $data['item_no'],
            $data['unit'],
            (float) $data['ratio'],
            (float) ($data['price'] ?? 0)
        );

        Log::info('accurate.item-unit', [
            'doc_number' => $data['doc_number'] ?? null,
            'item_no' => $data['item_no'],
            'item_name' => $data['item_name'] ?? null,
            'unit' => $data['unit'],
            'ratio' => (float) $data['ratio'],
            'price' => (float) ($data['price'] ?? 0),
            'ok' => $result['ok'],
            'already' => $result['already'] ?? false,
            'message' => $result['message'],
            'slot' => $result['slot'] ?? null,
        ]);

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'],
                'errors' => [$result['message']],
            ], 422);
        }

        return response()->json($result);
    }

    /**
     * Ambil daftar item yang satuannya belum ada di master item Accurate dari pesan error API.
     *
     * @return array<int,array{item_no:string,item_name:string,unit:string,suggested_ratio:?int}>
     */
    private function missingUnitIssues(string $message, array $prepared): array
    {
        if (! preg_match_all('/Tidak ada satuan "([^"]+)" pada barang "([^"]+)"/u', $message, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $issues = [];
        foreach ($matches as $match) {
            $unit = trim($match[1]);
            $name = trim($match[2]);

            $itemNo = '';
            foreach ($prepared as $row) {
                if (strcasecmp(trim((string) ($row['item_name_external'] ?? '')), $name) === 0) {
                    $itemNo = (string) ($row['item_no_external'] ?? '');
                    break;
                }
            }

            $ratio = null;
            if (preg_match('/(\d+)\s*PCS/i', $name, $ratioMatch)) {
                $ratio = (int) $ratioMatch[1];
            }

            $issues[] = [
                'item_no' => $itemNo !== '' ? $itemNo : $name,
                'item_name' => $name,
                'unit' => $unit,
                'suggested_ratio' => $ratio,
            ];
        }

        return $issues;
    }

    /**
     * Cek satu nomor dokumen langsung ke Accurate: sudah ada SO, DO, dan/atau INV?
     * Hasilnya disinkronkan ke external_documents agar kolom "Tahap" ikut berubah.
     */
    public function checkExternalDoc(Request $request)
    {
        $data = $request->validate([
            'doc_number' => ['required', 'string', 'max:50'],
            'tab' => ['nullable', Rule::in([DetailSale::SOURCE_INTERNAL, DetailSale::SOURCE_EXTERNAL])],
        ]);

        $doc = $data['doc_number'];

        $client = new AccurateApiClient();
        if (! $client->configured()) {
            return response()->json([
                'ok' => false,
                'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.',
            ], 422);
        }

        $numbers = [];

        foreach ([ExternalDocument::TYPE_SO, ExternalDocument::TYPE_DO, ExternalDocument::TYPE_INV] as $type) {
            $lookup = $client->lookupByNumber(ExternalDocument::MODULES[$type], $doc);

            if (! $lookup['ok']) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Gagal cek ' . strtoupper($type) . ' ' . $doc . ': '
                        . ($lookup['message'] ?? 'tidak ada respons Accurate.'),
                ], 422);
            }

            $row = $this->recordExternalDoc($doc, $type, $lookup['row']);
            if ($row !== null) {
                $numbers[$type] = (string) $row->accurate_number;
            }
        }

        // Invoice bisa bernomor lain (mis. SI.2026.11.00008) walau DO sudah
        // INVOICED. Coba adopsi supaya kolom Tahap ikut terisi INV.
        if (empty($numbers[ExternalDocument::TYPE_INV])) {
            $adopted = $this->adoptExistingInvoice($doc, $client);

            if ($adopted !== null) {
                $numbers[ExternalDocument::TYPE_INV] = $adopted['number'];
            }
        }

        $ext = $this->externalDocFlags($doc);

        $stages = [];
        $labels = [
            ExternalDocument::TYPE_SO => 'SO',
            ExternalDocument::TYPE_DO => 'DO',
            ExternalDocument::TYPE_INV => 'INV',
        ];
        foreach ($labels as $type => $label) {
            if (! empty($ext[$type])) {
                $number = $numbers[$type] ?? '';
                $stages[] = ($number !== '' && $number !== $doc) ? $label . ' (' . $number . ')' : $label;
            }
        }

        $alias = $client->identity()['alias'] ?? null;

        $tab = $data['tab'] ?? DetailSale::SOURCE_EXTERNAL;

        return response()->json([
            'ok' => true,
            'ext' => $ext,
            'numbers' => $numbers,
            'match_status' => $this->matchStatusMap([$doc], $tab)[$doc] ?? 'no_pair',
            'database' => $alias,
            'message' => $stages === []
                ? 'Tidak ada SO/DO/INV untuk ' . $doc . ' di Accurate.'
                : $doc . ': ' . implode(', ', $stages) . ' ditemukan di Accurate'
                    . ($alias ? ' (' . $alias . ')' : '')
                    . '.',
        ]);
    }

    public function deleteExternalDoc(Request $request)
    {
        $data = $request->validate([
            'doc_number' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(['so', 'do', 'inv'])],
        ]);

        $type = $data['type'];
        $doc = $data['doc_number'];

        $client = new AccurateApiClient();
        if (! $client->configured()) {
            return response()->json([
                'ok' => false,
                'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.',
            ], 422);
        }

        // Sinkronkan dokumen yang mau dihapus + dependensinya langsung dari Accurate.
        // Dokumen yang ditemukan ikut dicatat supaya tombol hapusnya muncul di tabel.
        $target = $this->syncExternalDoc($doc, $type, $client);

        if ($type === ExternalDocument::TYPE_SO) {
            $do = $this->syncExternalDoc($doc, ExternalDocument::TYPE_DO, $client);
            $inv = $this->syncExternalDoc($doc, ExternalDocument::TYPE_INV, $client);

            if ($do !== null || $inv !== null) {
                $need = [];
                if ($inv !== null) {
                    $need[] = 'INV';
                }
                if ($do !== null) {
                    $need[] = 'DO';
                }

                return response()->json([
                    'ok' => false,
                    'message' => 'Hapus ' . implode(' lalu ', $need) . ' ' . $doc . ' dulu (urutan: INV → DO → SO).',
                    'ext' => $this->externalDocFlags($doc),
                ], 422);
            }
        }

        if ($type === ExternalDocument::TYPE_DO) {
            $inv = $this->syncExternalDoc($doc, ExternalDocument::TYPE_INV, $client);

            if ($inv !== null) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Hapus INV ' . $doc . ' dulu (urutan: INV → DO → SO).',
                    'ext' => $this->externalDocFlags($doc),
                ], 422);
            }
        }

        if ($target === null) {
            return response()->json([
                'ok' => true,
                'message' => strtoupper($type) . ' ' . $doc . ' sudah tidak ada di Accurate.',
                'ext' => $this->externalDocFlags($doc),
            ]);
        }

        $result = $client->delete(ExternalDocument::MODULES[$type], $target->accurate_id);
        if (! $result['ok'] && ! $this->isNotFound($result['message'])) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?? ('Accurate gagal menghapus ' . strtoupper($type) . '.'),
                'ext' => $this->externalDocFlags($doc),
            ], 422);
        }

        $target->delete();

        return response()->json([
            'ok' => true,
            'message' => strtoupper($type) . ' ' . $doc . ' dihapus dari Accurate. Data tab External tidak diubah.',
            'ext' => $this->externalDocFlags($doc),
        ]);
    }

    /** Hapus dokumen lengkap dengan urutan aman: INV -> DO -> SO. */
    public function deleteExternalDocChain(Request $request)
    {
        $data = $request->validate([
            'doc_number' => ['required', 'string', 'max:50'],
        ]);

        $doc = $data['doc_number'];

        $client = new AccurateApiClient();
        if (! $client->configured()) {
            return response()->json([
                'ok' => false,
                'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.',
            ], 422);
        }

        $results = [];
        $failed = null;

        foreach ([ExternalDocument::TYPE_INV, ExternalDocument::TYPE_DO, ExternalDocument::TYPE_SO] as $type) {
            $row = $this->syncExternalDoc($doc, $type, $client);

            if ($row === null) {
                $results[] = ['type' => $type, 'ok' => true, 'skipped' => true, 'message' => 'tidak ada'];
                continue;
            }

            $result = $client->delete(ExternalDocument::MODULES[$type], $row->accurate_id);
            if (! $result['ok'] && ! $this->isNotFound($result['message'])) {
                $results[] = [
                    'type' => $type,
                    'ok' => false,
                    'skipped' => false,
                    'message' => $result['message'] ?? 'Accurate gagal menghapus ' . strtoupper($type) . '.',
                ];
                $failed = $type;
                break;
            }

            $row->delete();

            $results[] = ['type' => $type, 'ok' => true, 'skipped' => false, 'message' => 'dihapus'];
        }

        $ok = $failed === null;
        $deleted = array_filter($results, fn ($r) => empty($r['skipped']));
        $alias = $client->identity()['alias'] ?? null;

        return response()->json([
            'ok' => $ok,
            'message' => ! $ok
                ? strtoupper($failed) . ' ' . $doc . ' gagal dihapus. ' . ($results[count($results) - 1]['message'] ?? '')
                : ($deleted === []
                    ? 'Tidak ada dokumen ' . $doc . ' di Accurate.'
                    : 'Dokumen ' . $doc . ' dihapus dari Accurate (INV → DO → SO)'
                        . ($alias ? ' (' . $alias . ')' : '')
                        . '. Data tab External tidak diubah.'),
            'results' => $results,
            'ext' => $this->externalDocFlags($doc),
            'database' => $alias,
        ], $ok ? 200 : 422);
    }

    /**
     * Tahap 1 refresh tab external: hitung jumlah SO/DO/INV Accurate pada periode.
     * Hanya 3 panggilan list (pageSize=1) supaya ringan.
     */
    public function prepareRefreshExternal(Request $request)
    {
        $data = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $client = new AccurateApiClient();
        if (! $client->configured()) {
            return response()->json([
                'ok' => false,
                'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.',
            ], 422);
        }

        $from = Carbon::createFromFormat('Y-m-d', $data['date_from'])->format('d/m/Y');
        $to = Carbon::createFromFormat('Y-m-d', $data['date_to'])->format('d/m/Y');

        $counts = [];
        foreach (ExternalDocument::MODULES as $type => $module) {
            $result = $client->get('/accurate/api/' . $module . '/list.do', [
                'filter.transDate.op' => 'BETWEEN',
                'filter.transDate.val[0]' => $from,
                'filter.transDate.val[1]' => $to,
                'sp.pageSize' => 1,
                'sp.page' => 1,
                'fields' => 'id',
            ]);

            if (! $result['ok']) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Gagal menghitung ' . strtoupper($type) . ' di Accurate: '
                        . ($result['message'] ?? 'tidak ada respons.'),
                ], 422);
            }

            $counts[$type] = (int) ($result['body']['sp']['rowCount'] ?? 0);
        }

        return response()->json([
            'ok' => true,
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'counts' => $counts,
            'total' => array_sum($counts),
            'page_size' => [
                ExternalDocument::TYPE_SO => 100,
                ExternalDocument::TYPE_DO => 100,
                ExternalDocument::TYPE_INV => 15,
            ],
        ]);
    }

    /**
     * Tahap 2 refresh tab external: proses satu halaman dokumen Accurate.
     * SO/DO cukup dicatat di external_documents; INV yang belum ada di-mirror
     * ke detail_sales + detail_sales_items supaya tampil di tab External.
     */
    public function refreshExternalChunk(Request $request)
    {
        $data = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'type' => ['required', Rule::in(['so', 'do', 'inv'])],
            'page' => ['required', 'integer', 'min:1'],
        ]);

        $client = new AccurateApiClient();
        if (! $client->configured()) {
            return response()->json([
                'ok' => false,
                'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.',
            ], 422);
        }

        $type = $data['type'];
        $page = (int) $data['page'];
        $pageSize = $type === ExternalDocument::TYPE_INV ? 15 : 100;

        $from = Carbon::createFromFormat('Y-m-d', $data['date_from'])->format('d/m/Y');
        $to = Carbon::createFromFormat('Y-m-d', $data['date_to'])->format('d/m/Y');

        $result = $client->get('/accurate/api/' . ExternalDocument::MODULES[$type] . '/list.do', [
            'filter.transDate.op' => 'BETWEEN',
            'filter.transDate.val[0]' => $from,
            'filter.transDate.val[1]' => $to,
            'sp.pageSize' => $pageSize,
            'sp.page' => $page,
            'fields' => 'id,number,transDate,status',
        ]);

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => 'Gagal mengambil ' . strtoupper($type) . ' halaman ' . $page . ': '
                    . ($result['message'] ?? 'tidak ada respons.'),
            ], 422);
        }

        $rows = $result['body']['d'] ?? [];
        $rowCount = (int) ($result['body']['sp']['rowCount'] ?? count($rows));

        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $row) {
            $accurateId = (int) ($row['id'] ?? 0);
            $number = trim((string) ($row['number'] ?? ''));

            if ($accurateId <= 0 || $number === '') {
                $skipped++;
                continue;
            }

            $existing = ExternalDocument::withTrashed()
                ->where('doc_number', $number)
                ->where('type', $type)
                ->first();

            // SO/DO: cukup catat dokumennya (tidak perlu detail).
            if ($type !== ExternalDocument::TYPE_INV) {
                if ($existing !== null && $existing->deleted_at === null && $existing->accurate_id !== null) {
                    $skipped++;
                    continue;
                }

                $this->storeRefreshedExternal(
                    $number,
                    $type,
                    $accurateId,
                    $number,
                    isset($row['status']) ? (string) $row['status'] : null
                );
                $imported++;
                continue;
            }

            // INV: sudah tercatat lengkap (external_documents + mirror tab external) -> lewati.
            $mirrorExists = DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)
                ->where('doc_number', $number)
                ->exists();

            if ($mirrorExists && $existing !== null && $existing->deleted_at === null && $existing->accurate_id !== null) {
                $skipped++;
                continue;
            }

            // Mirror sudah ada tetapi external_documents belum -> cukup catat dokumennya.
            if ($mirrorExists) {
                $this->storeRefreshedExternal(
                    $number,
                    ExternalDocument::TYPE_INV,
                    $accurateId,
                    $number,
                    isset($row['status']) ? (string) $row['status'] : null
                );
                $imported++;
                continue;
            }

            $detail = $client->get('/accurate/api/sales-invoice/detail.do', ['id' => $accurateId]);
            if (! $detail['ok'] || ! is_array($detail['body']['d'] ?? null)) {
                $failed++;
                $errors[] = $number . ': ' . ($detail['message'] ?? 'detail invoice tidak ditemukan.');
                continue;
            }

            try {
                $this->mirrorRefreshedInvoice($detail['body']['d']);
                $this->storeRefreshedExternal(
                    $number,
                    ExternalDocument::TYPE_INV,
                    $accurateId,
                    (string) ($detail['body']['d']['number'] ?? $number),
                    isset($detail['body']['d']['status']) ? (string) $detail['body']['d']['status'] : null
                );
                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $number . ': ' . $e->getMessage();
                Log::warning('detail-sales.refresh-ext', [
                    'doc_number' => $number,
                    'type' => $type,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'type' => $type,
            'page' => $page,
            'page_size' => $pageSize,
            'row_count' => $rowCount,
            'processed' => count($rows),
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
            'has_more' => $page * $pageSize < $rowCount,
        ]);
    }

    /** Catat dokumen Accurate hasil refresh tanpa menimpa payload pembuatan lama. */
    private function storeRefreshedExternal(string $doc, string $type, int $accurateId, string $number, ?string $status): void
    {
        $row = ExternalDocument::withTrashed()->firstOrNew([
            'doc_number' => $doc,
            'type' => $type,
        ]);

        if ($row->payload === null) {
            $row->payload = json_encode([
                'source' => 'refresh',
                'doc_number' => $doc,
                'type' => $type,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $row->fill([
            'accurate_id' => $accurateId,
            'accurate_number' => $number,
            'accurate_status' => $status,
            'http_status' => 200,
        ]);

        $row->deleted_at = null;
        $row->save();
    }

    /**
     * Mirror invoice Accurate ke detail_sales + detail_sales_items (tab External).
     * Dipakai refresh karena invoice belum tentu ada di tab Internal.
     */
    private function mirrorRefreshedInvoice(array $d): void
    {
        $doc = trim((string) ($d['number'] ?? ''));
        if ($doc === '') {
            throw new \RuntimeException('Nomor invoice kosong pada respons Accurate.');
        }

        $customer = is_array($d['customer'] ?? null) ? $d['customer'] : [];
        $customerCode = trim((string) ($customer['customerNo'] ?? $d['customerNo'] ?? ''));
        $customerName = trim((string) ($customer['name'] ?? ''));
        $transDate = $this->parseAccurateDate($d['transDate'] ?? null);

        $amount = (float) ($d['salesAmount'] ?? 0);
        $tax = (float) ($d['tax1Amount'] ?? 0);
        $total = (float) ($d['totalAmount'] ?? 0);
        // Diskon invoice yang benar-benar tercatat di Accurate. Dipakai tab
        // external untuk menghitung ulang diskon di XML Coretax.
        $cashDiscount = max(0.0, (float) ($d['cashDiscount'] ?? 0));

        $items = [];
        $sumDpp = 0.0;
        $sumTax = 0.0;

        foreach (($d['detailItem'] ?? []) as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unitPrice'] ?? 0);
            $lineAmount = $qty * $price;
            $sumDpp += (float) ($item['dppAmount'] ?? $lineAmount);
            $sumTax += (float) ($item['tax1Amount'] ?? 0);

            $items[] = [
                'source_type' => DetailSaleItem::SOURCE_EXTERNAL,
                'doc_number' => $doc,
                'customer_code' => $customerCode,
                'transaction_date' => $transDate,
                'item_no' => (string) ($item['item']['no'] ?? ''),
                'item_description' => (string) ($item['detailName'] ?? $item['item']['name'] ?? ''),
                'unit_price' => $price,
                'qty' => $qty,
                'unit' => (string) ($item['itemUnit']['name'] ?? ''),
                'amount' => $lineAmount,
                'discount' => 0,
            ];
        }

        if ($amount <= 0) {
            $amount = $sumDpp;
        }
        if ($tax <= 0) {
            $tax = $sumTax;
        }
        if ($total <= 0) {
            $total = $amount + $tax;
        }

        $owing = $d['primeOwing'] ?? null;
        if (! is_numeric($owing)) {
            $owing = (($d['status'] ?? '') === 'OUTSTANDING' || ($d['outstanding'] ?? false) === true) ? $total : 0;
        }
        $owing = max(0, (float) $owing);
        $paid = max(0, $total - $owing);

        DB::transaction(function () use ($doc, $customerCode, $customerName, $transDate, $total, $amount, $tax, $cashDiscount, $owing, $paid, $items) {
            DetailSale::updateOrCreate(
                [
                    'source_type' => DetailSale::SOURCE_EXTERNAL,
                    'doc_number' => $doc,
                ],
                [
                    'fp_status' => null,
                    'customer_code' => $customerCode,
                    'customer_name' => $customerName,
                    'transaction_date' => $transDate,
                    'description' => $doc,
                    'invoice_amount' => $total,
                    'amount' => $amount,
                    'tax_base' => $tax,
                    'discount' => $cashDiscount,
                    'return_amount' => 0,
                    'owing' => $owing,
                    'payment_amount' => $paid,
                    'calculate_field' => $total,
                ]
            );

            DetailSaleItem::where('source_type', DetailSaleItem::SOURCE_EXTERNAL)
                ->where('doc_number', $doc)
                ->delete();

            foreach ($items as $item) {
                $item['customer_name'] = $customerName;
                DetailSaleItem::create($item);
            }
        });
    }

    private function parseAccurateDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value))->startOfDay();
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Ambil dokumen dari Accurate lalu catat ke external_documents bila belum ada.
     * Mengembalikan null bila dokumen tidak ada di Accurate.
     */
    private function syncExternalDoc(string $doc, string $type, AccurateApiClient $client): ?ExternalDocument
    {
        $row = ExternalDocument::withTrashed()
            ->where('doc_number', $doc)
            ->where('type', $type)
            ->first();

        if ($row !== null && $row->deleted_at === null && $row->accurate_id !== null) {
            return $row;
        }

        return $this->recordExternalDoc(
            $doc,
            $type,
            $client->findByNumber(ExternalDocument::MODULES[$type], $doc)
        );
    }

    /**
     * Simpan hasil cek Accurate ke external_documents. Baris null berarti
     * dokumen tidak ditemukan -> baris lokal di-soft-delete.
     */
    private function recordExternalDoc(string $doc, string $type, ?array $found): ?ExternalDocument
    {
        $row = ExternalDocument::withTrashed()
            ->where('doc_number', $doc)
            ->where('type', $type)
            ->first();

        if ($found === null) {
            if ($row !== null && $row->deleted_at === null) {
                $row->delete();
            }

            return null;
        }

        if (! isset($found['id']) || (int) $found['id'] <= 0) {
            return null;
        }

        $row ??= new ExternalDocument(['doc_number' => $doc, 'type' => $type]);
        $row->fill([
            'accurate_id' => (int) $found['id'],
            'accurate_number' => isset($found['number']) ? (string) $found['number'] : $doc,
            // lookupByNumber kadang tidak mengembalikan status; jangan hapus
            // status lama hanya karena field itu tidak ada di respons.
            'accurate_status' => isset($found['status'])
                ? (string) $found['status']
                : $row->accurate_status,
            'http_status' => 200,
        ]);
        $row->deleted_at = null;
        $row->save();

        return $row;
    }

    /** Status SO/DO/INV yang tercatat untuk satu dokumen (untuk update UI tanpa reload). */
    private function externalDocFlags(string $doc): array
    {
        $types = ExternalDocument::where('doc_number', $doc)->pluck('type')->all();

        return [
            ExternalDocument::TYPE_SO => in_array(ExternalDocument::TYPE_SO, $types, true),
            ExternalDocument::TYPE_DO => in_array(ExternalDocument::TYPE_DO, $types, true),
            ExternalDocument::TYPE_INV => in_array(ExternalDocument::TYPE_INV, $types, true),
        ];
    }

    /**
     * DO tertentu sudah difakturkan di Accurate, tetapi invoice-nya memakai
     * nomor lain (mis. SI.2026.11.00008, dibuat manual). Cari invoice yang
     * memakai baris DO tersebut lalu catat sebagai tahap INV untuk dokumen ini.
     *
     * Tidak membuat dokumen apa pun di Accurate.
     *
     * @return array{number:string,id:int,status:?string}|null
     */
    private function adoptExistingInvoice(string $doc, AccurateApiClient $client): ?array
    {
        $doRow = ExternalDocument::withTrashed()
            ->where('doc_number', $doc)
            ->where('type', ExternalDocument::TYPE_DO)
            ->first();

        $doId = $doRow?->accurate_id !== null ? (int) $doRow->accurate_id : null;

        if ($doId === null) {
            $found = $client->findByNumber(ExternalDocument::MODULES[ExternalDocument::TYPE_DO], $doc);
            $doId = isset($found['id']) ? (int) $found['id'] : null;

            if ($doId === null) {
                return null;
            }

            $this->recordExternalDoc($doc, ExternalDocument::TYPE_DO, $found);
        }

        $detail = $client->get('/accurate/api/delivery-order/detail.do', ['id' => $doId]);
        if (! ($detail['ok'] ?? false)) {
            return null;
        }

        $d = $detail['body']['d'] ?? [];
        if (isset($d[0]) && is_array($d[0])) {
            $d = $d[0];
        }

        $items = is_array($d['detailItem'] ?? null) ? $d['detailItem'] : [];
        if ($items === []) {
            return null;
        }

        // Sudah difakturkan? status INVOICED, atau semua baris habis difakturkan.
        $invoiced = strtoupper((string) ($d['status'] ?? '')) === 'INVOICED';
        $allInvoiced = true;
        $detailIds = [];

        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0) {
                $detailIds[] = $id;
            }

            $qty = (float) ($item['quantity'] ?? 0);
            $invoiceQty = (float) ($item['invoiceQuantity'] ?? 0);

            if ($qty <= 0 || $invoiceQty + 0.00001 < $qty) {
                $allInvoiced = false;
            }
        }

        if ($detailIds === [] || (! $invoiced && ! $allInvoiced)) {
            return null;
        }

        $customerId = (int) ($d['customer']['id'] ?? 0);
        if ($customerId <= 0) {
            return null;
        }

        // Invoice manual biasanya dibuat beberapa hari/minggu setelah DO.
        $shipDate = $this->parseAccurateDate($d['shipDate'] ?? $d['transDate'] ?? null);
        $from = ($shipDate ?? now())->copy()->subDays(7)->format('d/m/Y');
        $to = ($shipDate ?? now())->copy()->addDays(120)->format('d/m/Y');

        $candidates = [];

        for ($page = 1; $page <= 3; $page++) {
            $list = $client->get('/accurate/api/sales-invoice/list.do', [
                'filter.customerId.op' => 'EQUAL',
                'filter.customerId.val' => $customerId,
                'filter.transDate.op' => 'BETWEEN',
                'filter.transDate.val[0]' => $from,
                'filter.transDate.val[1]' => $to,
                'sp.pageSize' => 50,
                'sp.page' => $page,
                'fields' => 'id,number,transDate,status',
            ]);

            if (! ($list['ok'] ?? false)) {
                break;
            }

            $rows = $list['body']['d'] ?? [];
            if (! is_array($rows) || $rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $candidates[] = $row;
            }

            if (count($rows) < 50) {
                break;
            }
        }

        foreach ($candidates as $candidate) {
            $invoiceId = (int) ($candidate['id'] ?? 0);
            $invoiceNumber = trim((string) ($candidate['number'] ?? ''));

            if ($invoiceId <= 0 || $invoiceNumber === '') {
                continue;
            }

            $invoiceDetail = $client->get('/accurate/api/sales-invoice/detail.do', ['id' => $invoiceId]);
            if (! ($invoiceDetail['ok'] ?? false)) {
                continue;
            }

            $invoice = $invoiceDetail['body']['d'] ?? [];
            if (isset($invoice[0]) && is_array($invoice[0])) {
                $invoice = $invoice[0];
            }

            foreach (($invoice['detailItem'] ?? []) as $line) {
                if (! in_array((int) ($line['deliveryOrderDetailId'] ?? 0), $detailIds, true)) {
                    continue;
                }

                $status = isset($invoice['status']) ? (string) $invoice['status'] : null;

                $this->recordExternalDoc($doc, ExternalDocument::TYPE_INV, [
                    'id' => $invoiceId,
                    'number' => $invoiceNumber,
                    'status' => $status,
                ]);

                return [
                    'number' => $invoiceNumber,
                    'id' => $invoiceId,
                    'status' => $status,
                ];
            }
        }

        return null;
    }

    private function storeExternalDocument(string $doc, string $type, array $payload, array $result): void
    {
        $r = $result['body']['r'] ?? [];
        $json = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        ExternalDocument::withTrashed()->updateOrCreate(
            ['doc_number' => $doc, 'type' => $type],
            [
                'payload' => json_encode($payload, $json),
                'response' => json_encode($result['body'] ?? [], $json),
                'accurate_id' => isset($r['id']) ? (int) $r['id'] : null,
                'accurate_number' => isset($r['number']) ? (string) $r['number'] : $doc,
                'accurate_status' => isset($r['status']) ? (string) $r['status'] : null,
                'http_status' => $result['status'] ?? null,
            ]
        )->restore();
    }

    private function isNotFound(?string $message): bool
    {
        $m = mb_strtolower((string) $message);

        foreach (['tidak ditemukan', 'tidak tepat', 'not found', 'not exist', 'does not exist'] as $needle) {
            if (str_contains($m, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Samakan qty tiap baris dengan satuan yang dikirim ke Accurate.
     *
     * Data internal menyimpan qty dalam satuan dasar item (mis. Crt) sementara
     * harga bisa per satuan lain (Kg/Pcs). Tanpa konversi, Accurate membaca
     * qty dasar sebagai Kg/Pcs sehingga total dokumen mengecil.
     *
     * @param  array<int,array{line:DetailSaleItem,qty:float}>  $prepared
     */
    private function applyUnitConversion(array &$prepared, AccurateApiClient $client): void
    {
        $codes = [];
        foreach ($prepared as $row) {
            $code = trim((string) ($row['item_no_external'] ?? ''));
            if ($code !== '') {
                $codes[$code] = true;
            }
        }

        if ($codes === []) {
            return;
        }

        $units = $this->itemUnitRatios($client, array_keys($codes));

        foreach ($prepared as &$row) {
            $line = $row['line'];
            $qtyBase = (float) $row['qty'];
            $price = (float) $line->unit_price;
            $unit = trim((string) $line->unit);

            $row['qty_send'] = $qtyBase;
            $row['unit_send'] = $unit;
            $row['price_send'] = $price;

            if ($qtyBase <= 0) {
                continue;
            }

            $originalQty = (float) $line->qty;
            $target = $originalQty > 0
                ? (float) $line->amount * ($qtyBase / $originalQty)
                : $qtyBase * $price;

            if ($target <= 0) {
                continue;
            }

            $info = $units[$this->normalizeItemCode((string) ($row['item_no_external'] ?? ''))] ?? null;
            if ($info === null) {
                continue;
            }

            $tolerance = max(1.0, abs($target) * 0.005);
            $unitKey = $this->normalizeUnitName($unit);
            $ratio = $info['units'][$unitKey] ?? null;
            $unitSupported = $ratio !== null && $ratio > 0;

            // Accurate mencocokkan nama satuan secara case-sensitive ("Crt" ≠ "crt"),
            // sedangkan data internal bisa menyimpan "crt"/"CRT". Kirim nama kanonik
            // dari master item Accurate bila ada.
            $unitSend = (string) ($info['names'][$unitKey] ?? $unit);

            // 1) Qty baris sudah sepadan dengan harganya (qty x harga = nilai baris):
            //    kirim apa adanya selama satuannya dikenal item Accurate.
            if ($unitSupported && abs(($qtyBase * $price) - $target) <= $tolerance) {
                $row['qty_send'] = $qtyBase;
                $row['unit_send'] = $unitSend;
                $row['price_send'] = $price;
                continue;
            }

            // 2) Qty internal dalam satuan dasar: konversi ke satuan baris (Kg/Pcs).
            if ($unitSupported) {
                $qtySend = $qtyBase / $ratio;

                // Qty pecahan kecil sering hanya pembulatan dari satuan dasar
                // (mis. 0,23 Crt = 9,2 Pcs padahal aslinya 9 Pcs). Pakai angka
                // bulat bila nilainya jadi persis sama dengan nilai baris.
                $qtyBulat = round($qtySend);
                if ($qtyBulat > 0 && abs(($qtyBulat * $price) - $target) <= 0.01) {
                    $row['qty_send'] = $qtyBulat;
                    $row['unit_send'] = $unitSend;
                    $row['price_send'] = $price;
                    continue;
                }

                if (abs(($qtySend * $price) - $target) <= $tolerance) {
                    $row['qty_send'] = $qtySend;
                    $row['unit_send'] = $unitSend;
                    $row['price_send'] = $price;
                    continue;
                }
            }

            // 3) Satuan baris tidak cocok/tidak dikenal: pakai satuan dasar item.
            //    Harga TIDAK boleh diubah — harus sama dengan harga internal.
            //    Kalau qty tidak sepadan, prepareExternalDoc akan menandai dokumen
            //    ini perlu dicek (tidak dikirim dengan angka karangan).
            $row['qty_send'] = $qtyBase;
            $row['unit_send'] = $info['base'];
            $row['price_send'] = $price;
        }
        unset($row);
    }

    /**
     * Rasio satuan item Accurate: kode => ['base' => 'Crt', 'units' => ['crt' => 1, 'kg' => 0.2], 'names' => ['crt' => 'Crt', 'kg' => 'Kg']].
     * Rasio = jumlah satuan dasar per 1 satuan tersebut. Di-cache 1 hari.
     *
     * @param  array<int,string>  $codes
     * @return array<string,array{base:string,units:array<string,float>,names:array<string,string>}>
     */
    private function itemUnitRatios(AccurateApiClient $client, array $codes): array
    {
        $map = [];
        $missing = [];

        foreach ($codes as $code) {
            $key = $this->normalizeItemCode($code);
            if ($key === '') {
                continue;
            }

            // v2: nilai cache menyertakan nama satuan kanonik (case-sensitive Accurate).
            $cached = Cache::get('accurate.item.units.v2.' . md5($key));
            if (is_array($cached) && isset($cached['names'])) {
                $map[$key] = $cached;
            } else {
                $missing[$key] = $code;
            }
        }

        foreach (array_chunk(array_values($missing), 20) as $chunk) {
            $query = [
                'sp.pageSize' => 100,
                'fields' => 'id,no,unit1,unit2,unit3,unit4,unit5,ratio2,ratio3,ratio4,ratio5',
                'filter.no.op' => 'EQUAL',
            ];

            foreach (array_values($chunk) as $index => $code) {
                $query['filter.no.val[' . $index . ']'] = $code;
            }

            $result = $client->get('/accurate/api/item/list.do', $query);
            if (! ($result['ok'] ?? false)) {
                continue;
            }

            foreach (($result['body']['d'] ?? []) as $row) {
                $no = $this->normalizeItemCode((string) ($row['no'] ?? ''));
                $base = trim((string) ($row['unit1']['name'] ?? ''));

                if ($no === '' || $base === '') {
                    continue;
                }

                $units = [$this->normalizeUnitName($base) => 1.0];
                $names = [$this->normalizeUnitName($base) => $base];
                for ($i = 2; $i <= 5; $i++) {
                    $name = trim((string) ($row['unit' . $i]['name'] ?? ''));
                    $ratio = (float) ($row['ratio' . $i] ?? 0);
                    if ($name !== '' && $ratio > 0) {
                        $units[$this->normalizeUnitName($name)] = $ratio;
                        $names[$this->normalizeUnitName($name)] = $name;
                    }
                }

                $info = ['base' => $base, 'units' => $units, 'names' => $names];
                $map[$no] = $info;
                Cache::put('accurate.item.units.v2.' . md5($no), $info, 86400);
            }

            usleep(150000);
        }

        return $map;
    }

    private function normalizeItemCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    private function normalizeUnitName(string $unit): string
    {
        return strtolower(trim($unit));
    }

    private function buildExternalPayload(string $type, DetailSale $header, array $prepared, string $customerNo, string $soNumber, string $doNumber, ?string $termCustomerCode = null): array
    {
        $termCode = $termCustomerCode ?? (string) $header->customer_code;
        $termId = Customer::where('source_type', Customer::SOURCE_INTERNAL)
            ->where('customer_code', $termCode)
            ->value('payment_term_id');
        $termDays = (int) (PaymentTerm::whereKey($termId)->value('days') ?? 0);

        $warehouse = trim((string) Setting::get('accurate_warehouse_name', 'Utama')) ?: 'Utama';
        $date = $header->transaction_date->format('d/m/Y');
        $doc = (string) $header->doc_number;

        $items = [];
        // Kode customer external ber-"TNPWP" -> non-PPN: jangan centang PPN
        // pada dokumen maupun item. Kode lain tetap ber-PPN seperti sebelumnya.
        $taxable = ! Taxability::isNonPpnCustomer($customerNo);

        // Diskon di Accurate adalah DISKON INVOICE (satu nilai untuk dokumen).
        // Harga item tetap harga gross; diskon dikirim terpisah lewat cashDiscount,
        // bukan dengan menurunkan unitPrice.
        $invoiceDiscount = max(0.0, round((float) $header->discount, 2));

        foreach ($prepared as $row) {
            $qty = (float) ($row['qty_send'] ?? $row['qty']);
            // Harga SELALU harga internal (kotor). Diskon invoice dikirim terpisah
            // lewat cashDiscount, tidak pernah ditempel ke harga satuan.
            $priceGross = (float) ($row['price_send'] ?? $row['line']->unit_price);

            $item = [
                'itemNo' => (string) $row['item_no_external'],
                'detailName' => (string) $row['item_name_external'],
                'quantity' => $qty,
                'itemUnitName' => (string) ($row['unit_send'] ?? $row['line']->unit),
                'unitPrice' => $priceGross,
                'useTax1' => $taxable,
            ];

            if ($type !== ExternalDocument::TYPE_SO) {
                $item['salesOrderNumber'] = $soNumber;
                $item['warehouseName'] = $warehouse;
            }

            if ($type === ExternalDocument::TYPE_INV) {
                $item['deliveryOrderNumber'] = $doNumber;
            }

            $items[] = $item;
        }

        $payload = [
            'transDate' => $date,
            'number' => $doc,
            'customerNo' => $customerNo,
            'currencyCode' => 'IDR',
            'rate' => 1,
            'taxable' => $taxable,
            'inclusiveTax' => false,
            'paymentTermName' => $termDays === 0 ? 'C.O.D' : 'Net ' . $termDays,
            'shipDate' => $date,
            'description' => trim((string) $header->description) !== '' ? trim((string) $header->description) : $doc,
            'detailItem' => $items,
        ];

        if ($type !== ExternalDocument::TYPE_DO) {
            $payload['detailExpense'] = [];
        }

        if ($type === ExternalDocument::TYPE_INV) {
            $payload['detailDownPayment'] = [];

            // Diskon invoice (bukan diskon item): harga tetap gross.
            if ($invoiceDiscount > 0) {
                $payload['cashDiscount'] = $invoiceDiscount;
            }
        }

        return $payload;
    }

    /**
     * Hubungkan tiap baris invoice ke baris DO yang tepat lewat deliveryOrderDetailId.
     *
     * Tanpa ini, Accurate hanya mencocokkan berdasarkan nomor DO + kode barang.
     * Bila satu barang punya beberapa baris di DO (mis. baris harga normal + baris
     * bonus harga 0), semua baris invoice dianggap menumpuk ke baris DO pertama
     * sehingga muncul "Quantity ... in Invoice is greater than delivered qty".
     *
     * @param  array<string,mixed>  $payload
     */
    private function linkInvoiceLinesToDeliveryOrder(array &$payload, AccurateApiClient $client, string $doNumber, ?int $doId = null, ?array $detail = null): void
    {
        if ($doNumber === '' || empty($payload['detailItem'])) {
            return;
        }

        if ($detail === null) {
            if ($doId === null) {
                $doRow = $client->findByNumber(ExternalDocument::MODULES[ExternalDocument::TYPE_DO], $doNumber);
                $doId = isset($doRow['id']) ? (int) $doRow['id'] : null;
            }

            if ($doId === null) {
                return;
            }

            $result = $client->get('/accurate/api/delivery-order/detail.do', ['id' => $doId]);
            if (! ($result['ok'] ?? false)) {
                return;
            }

            $detail = $result['body']['d'] ?? [];
        }

        if (! is_array($detail)) {
            return;
        }

        if (isset($detail[0]) && is_array($detail[0])) {
            $detail = $detail[0];
        }

        $barisDo = $detail['detailItem'] ?? [];
        if (! is_array($barisDo) || $barisDo === []) {
            return;
        }

        $sisa = [];
        foreach ($barisDo as $baris) {
            $item = is_array($baris['item'] ?? null) ? $baris['item'] : [];
            $unit = is_array($baris['itemUnit'] ?? null) ? $baris['itemUnit'] : [];

            $sisa[] = [
                'id' => $baris['id'] ?? null,
                'itemNo' => (string) ($item['no'] ?? ''),
                'unit' => (string) ($unit['name'] ?? ''),
                'soDetailId' => $baris['salesOrderDetailId'] ?? null,
            ];
        }

        foreach ($payload['detailItem'] as &$item) {
            foreach ($sisa as $pos => $baris) {
                if ((string) ($item['itemNo'] ?? '') !== $baris['itemNo']
                    || (string) ($item['itemUnitName'] ?? '') !== $baris['unit']) {
                    continue;
                }

                $item['deliveryOrderDetailId'] = $baris['id'];

                if ($baris['soDetailId'] !== null) {
                    $item['salesOrderDetailId'] = $baris['soDetailId'];
                }

                unset($sisa[$pos]);
                $sisa = array_values($sisa);

                break;
            }
        }
        unset($item);
    }

    /**
     * Perjelas pesan "pelanggan Non Aktif" dari Accurate.
     *
     * Pesan asli hanya menyebut nama pelanggan. Penyebab paling sering adalah satu
     * kode internal punya beberapa mapping, sehingga yang terpilih justru customer
     * lama yang sudah dinonaktifkan di Accurate.
     */
    private function nonActiveCustomerHint(string $reason, string $internalCode, string $externalCode): string
    {
        $mappings = MappingCustomer::query()
            ->where('kode_internal', $internalCode)
            ->pluck('kode_external')
            ->all();

        $hint = $reason . ' Kode tujuan di Accurate: ' . $externalCode . '.';

        if (count($mappings) > 1) {
            $hint .= ' Kode internal ' . $internalCode . ' punya ' . count($mappings)
                . ' mapping (' . implode(', ', $mappings) . ').'
                . ' Hapus mapping yang tidak dipakai di menu Mapping Customer agar dokumen diarahkan ke customer yang aktif.';
        } else {
            $hint .= ' Periksa status pelanggan tersebut di menu Customer Accurate (Non Aktif).';
        }

        return $hint;
    }

    private function externalFailure(array $reasons)
    {
        return response()->json([
            'ok' => false,
            'message' => 'Tidak bisa membuat transaksi external.',
            'errors' => array_values($reasons),
        ], 422);
    }

    private function formatQty(float $value): string
    {
        $formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    /**
     * Cocokkan item retur dengan baris invoice: kode internal sama, atau
     * kode eksternal (Accurate) sama.
     */
    private function isSameReturnItem(object $line, SalesReturn $ret, array $itemCodeMap): bool
    {
        if ((string) $line->item_no === (string) $ret->item_no) {
            return true;
        }

        $retExternal = trim((string) ($itemCodeMap[(string) $ret->item_no] ?? ''));
        if ($retExternal === '') {
            return false;
        }

        return trim((string) ($itemCodeMap[(string) $line->item_no] ?? '')) === $retExternal;
    }

    /**
     * Qty retur dari export Accurate hanya 2 desimal (mis. 0,47 Crt padahal
     * sebenarnya 1,9 Kg = 0,475 Crt), sehingga konversinya ke satuan baris
     * bergeser (1,88 Kg). Bila qty hasil hitung dari nilai retur berbeda hanya
     * karena pembulatan tersebut, pakai angka dari nilai retur supaya
     * qty x harga = nilai retur dan total invoice tetap balance.
     *
     * @param  array<int,array{line:DetailSaleItem,qty:float}>  $prepared
     */
    private function refineReturnQty(SalesReturn $ret, array $prepared, array $itemCodeMap): float
    {
        $qty = (float) $ret->quantity;

        if (abs((float) $ret->amount) <= 0.005) {
            return $qty;
        }

        $matchedIndex = null;
        $matchedCount = 0;

        foreach ($prepared as $index => $row) {
            if ($this->isSameReturnItem($row['line'], $ret, $itemCodeMap)) {
                $matchedIndex = $index;
                $matchedCount++;
            }
        }

        // Retur yang menunjuk beberapa baris invoice tetap dibagi seperti semula.
        if ($matchedCount !== 1) {
            return $qty;
        }

        $line = $prepared[$matchedIndex]['line'];
        $lineQty = (float) $line->qty;
        $lineAmount = (float) $line->amount;

        if ($lineQty <= 0 || $lineAmount <= 0) {
            return $qty;
        }

        // Harga per satuan dasar item, dipakai mengubah nilai retur jadi qty.
        $implied = abs((float) $ret->amount) / ($lineAmount / $lineQty);

        // Selisih di atas 0,0051 berarti harga retur memang beda dari harga
        // invoice (bukan pembulatan 2 desimal) -> tetap pakai qty retur.
        if ($implied <= 0 || abs($implied - abs($qty)) > 0.0051) {
            return $qty;
        }

        return $qty < 0 ? -$implied : $implied;
    }

    private function externalMaps(): array
    {
        // Mapping jarang berubah; cache 5 menit. Kunci cache memakai sidik jari
        // (updated_at + jumlah baris) supaya perubahan mapping/import/clear
        // langsung terpakai tanpa menunggu TTL.
        $signature = implode('|', [
            (string) MappingCustomer::max('updated_at'),
            (string) MappingCustomer::count(),
            (string) MappingProduct::max('updated_at'),
            (string) MappingProduct::count(),
        ]);

        return Cache::remember('accurate.external.maps.' . md5($signature), 300, function () {
            $custMap = [];
            foreach (MappingCustomer::query()->get(['kode_internal', 'kode_external']) as $row) {
                $key = (string) $row->kode_internal;
                if ($key !== '' && ! array_key_exists($key, $custMap)) {
                    $custMap[$key] = (string) $row->kode_external;
                }
            }

            $itemCodeMap = [];
            $itemNameMap = [];
            foreach (MappingProduct::query()->get(['kode_item_internal', 'kode_item_external', 'nama_item_internal', 'nama_item_external']) as $row) {
                $code = (string) $row->kode_item_internal;
                if ($code !== '' && ! array_key_exists($code, $itemCodeMap)) {
                    $itemCodeMap[$code] = (string) $row->kode_item_external;
                }

                $name = (string) $row->nama_item_internal;
                if ($name !== '' && ! array_key_exists($name, $itemNameMap)) {
                    $itemNameMap[$name] = (string) $row->nama_item_external;
                }
            }

            return [$custMap, $itemCodeMap, $itemNameMap];
        });
    }

    private function externalItemName($line, array $itemNameMap): string
    {
        $name = trim((string) ($itemNameMap[$line->item_description] ?? ''));

        if ($name === '' || str_starts_with($name, '=')) {
            $name = $this->cleanItemName((string) $line->item_description);
        }

        return $name !== '' ? $name : (string) $line->item_no;
    }

    private function cleanItemName(string $value): string
    {
        $name = trim($value);
        $clean = preg_replace('/^[^\x20-\x7E]+/u', '', $name);
        if ($clean === null) {
            $clean = preg_replace('/^[^\x20-\x7E]+/', '', $name) ?? '';
        }

        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return trim($clean);
    }

    private function mirrorExternalInvoice(DetailSale $header, array $payload, AccurateApiClient $client, ?string $customerName = null): void
    {
        $doc = (string) $header->doc_number;

        $grossDpp = 0.0;
        $items = [];
        foreach ($payload['detailItem'] as $item) {
            $qty = (float) $item['quantity'];
            $price = (float) $item['unitPrice'];
            $amount = $qty * $price;
            $grossDpp += $amount;

            $items[] = [
                'source_type' => DetailSaleItem::SOURCE_EXTERNAL,
                'doc_number' => $doc,
                'customer_code' => $payload['customerNo'],
                'transaction_date' => $header->transaction_date,
                'item_no' => (string) $item['itemNo'],
                'item_description' => (string) $item['detailName'],
                'unit_price' => $price,
                'qty' => $qty,
                'unit' => (string) $item['itemUnitName'],
                'amount' => $amount,
                'discount' => 0,
            ];
        }

        $rate = (float) Setting::get('tax_rate', 11);
        // Diskon invoice dicatat terpisah (bukan ditempel ke harga).
        // DPP = nilai kotor barang - cashDiscount.
        $cashDiscount = max(0.0, round((float) ($payload['cashDiscount'] ?? 0), 2));
        $dpp = max(0.0, $grossDpp - $cashDiscount);
        // PPN hanya ditambahkan bila dokumen memang taxable (bukan customer TNPWP).
        $taxable = (bool) ($payload['taxable'] ?? true);
        $tax = $taxable ? round($dpp * $rate / 100, 2) : 0.0;
        $total = round($dpp + $tax, 2);
        $customerName = $customerName !== null && trim($customerName) !== ''
            ? $customerName
            : ($this->accurateCustomerName($client, (string) $payload['customerNo']) ?? $header->customer_name);

        DetailSale::updateOrCreate(
            ['source_type' => DetailSale::SOURCE_EXTERNAL, 'doc_number' => $doc],
            [
                'fp_status' => null,
                'customer_code' => (string) $payload['customerNo'],
                'customer_name' => $customerName,
                'transaction_date' => $header->transaction_date,
                'description' => $doc,
                'invoice_amount' => $total,
                'amount' => $dpp,
                'tax_base' => $tax,
                'discount' => $cashDiscount,
                'return_amount' => 0,
                'owing' => $total,
                'payment_amount' => 0,
                'calculate_field' => $total,
            ]
        );

        DetailSaleItem::where('source_type', DetailSaleItem::SOURCE_EXTERNAL)
            ->where('doc_number', $doc)
            ->delete();

        foreach ($items as $item) {
            $item['customer_name'] = $customerName;
            DetailSaleItem::create($item);
        }
    }

    /**
     * Nama customer Accurate (cache 1 jam) — dipakai mirror invoice.
     */
    private function accurateCustomerName(AccurateApiClient $client, string $customerNo): ?string
    {
        if ($customerNo === '') {
            return null;
        }

        $key = 'accurate.customer.name.' . $customerNo;
        $cached = Cache::get($key);
        if (is_string($cached) && trim($cached) !== '') {
            return trim($cached);
        }

        $result = $client->get('/accurate/api/customer/detail.do', ['customerNo' => $customerNo]);
        if (! $result['ok']) {
            return null;
        }

        $name = $result['body']['d']['name'] ?? null;
        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        $name = trim($name);
        Cache::put($key, $name, 3600);

        return $name;
    }

    /**
     * Nama beberapa customer sekaligus (paralel 6, cache 1 jam per customer).
     *
     * @param  array<int,string>  $customerNos
     * @return array<string,string> customerNo => nama
     */
    private function accurateCustomerNames(AccurateApiClient $client, array $customerNos): array
    {
        $names = [];
        $missing = [];

        foreach (array_unique($customerNos) as $customerNo) {
            $customerNo = trim((string) $customerNo);
            if ($customerNo === '') {
                continue;
            }

            $cached = Cache::get('accurate.customer.name.' . $customerNo);
            if (is_string($cached) && trim($cached) !== '') {
                $names[$customerNo] = trim($cached);

                continue;
            }

            $missing[] = $customerNo;
        }

        if ($missing !== []) {
            $responses = $client->getMany(array_map(
                fn (string $customerNo) => [
                    'path' => '/accurate/api/customer/detail.do',
                    'query' => ['customerNo' => $customerNo],
                ],
                $missing
            ));

            foreach ($missing as $index => $customerNo) {
                $name = $responses[$index]['body']['d']['name'] ?? null;
                if (! is_string($name) || trim($name) === '') {
                    continue;
                }

                $name = trim($name);
                Cache::put('accurate.customer.name.' . $customerNo, $name, 3600);
                $names[$customerNo] = $name;
            }
        }

        return $names;
    }

    private function resolveTab(Request $request): string
    {
        // Halaman /invoices tetap default External; /detail-sales default Internal.
        $default = $request->routeIs('invoices.*')
            ? DetailSale::SOURCE_EXTERNAL
            : DetailSale::SOURCE_INTERNAL;

        $tab = $request->query('tab', $request->input('tab', $default));

        return in_array($tab, [DetailSale::SOURCE_INTERNAL, DetailSale::SOURCE_EXTERNAL], true)
            ? $tab
            : $default;
    }
}
