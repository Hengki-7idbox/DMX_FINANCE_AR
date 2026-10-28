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
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        // Tab internal: sembunyikan dokumen retur (format SR — bukan DMX/SDMX) persis.
        // Varian sufiks huruf (mis. ...D) disaring di PHP setelah fetch agar query cepat.
        if ($tab === DetailSale::SOURCE_INTERNAL) {
            $query->whereNotIn('doc_number', function ($sub) {
                $sub->select('return_no')->from('sales_returns');
            });
        }

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

        // Filter PHP: varian sufiks huruf retur (mis. ...D) yang base-nya ada di sales_returns.
        if ($tab === DetailSale::SOURCE_INTERNAL) {
            $returnNumbers = \DB::table('sales_returns')->pluck('return_no')->all();
            $detailSales = $detailSales->filter(function ($r) use ($returnNumbers) {
                $doc = (string) $r->doc_number;
                if (in_array($doc, $returnNumbers, true)) {
                    return false;
                }
                if (str_starts_with($doc, 'SR/') && strlen($doc) > 4) {
                    $lastChar = substr($doc, -1);
                    if (ctype_alpha($lastChar)) {
                        $base = substr($doc, 0, -1);
                        if (in_array($base, $returnNumbers, true)) {
                            return false;
                        }
                    }
                }
                return true;
            })->values();
        }

        // Kolom Faktur Pajak (tab external): cocokkan doc_number dengan reference faktur_pajak.
        // Reference double (2 faktur) dipetakan ke nomor pajak faktur pertama.
        // Status manual FP ("Gung Gung" = FP memang tidak dibuat) disimpan di detail_sales.fp_status.
        $fpMap = collect();
        if ($tab === DetailSale::SOURCE_EXTERNAL) {
            $fpMap = FakturPajak::query()
                ->whereNotNull('reference')
                ->where('reference', '!=', '')
                ->orderBy('id')
                ->get(['reference', 'tax_invoice_number'])
                ->unique('reference')
                ->mapWithKeys(fn ($r) => [$r->reference => $r->tax_invoice_number]);
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

        // Payload ringkas untuk render client-side (DataTables deferRender).
        $payload = $detailSales->map(fn ($r) => [
            'id' => $r->id,
            'customer_code' => $r->customer_code,
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
        if ($tab === DetailSale::SOURCE_INTERNAL) {
            $optQuery->whereNotIn('doc_number', function ($sub) {
                $sub->select('return_no')->from('sales_returns');
            });
        }
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
        if ($tab === DetailSale::SOURCE_INTERNAL) {
            $totalQuery->whereNotIn('doc_number', function ($sub) {
                $sub->select('return_no')->from('sales_returns');
            });
        }
        $totalAll = $totalQuery->count();

        $pageTitle = $isInvoices ? 'Invoice Belum Lunas' : 'Detail Sales';
        $pageDescription = $isInvoices
            ? 'Daftar invoice dengan nilai owing (belum lunas)'
            : 'Detail transaksi sales per invoice';

        return view('detail-sales.index', compact(
            'detailSales', 'tab', 'payload',
            'availableBulan', 'availableTahun', 'filterBulan', 'filterTahun', 'totalAll',
            'isInvoices', 'tabRoute', 'pageTitle', 'pageDescription'
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
     * Compare doc_number against the other tab.
     *
     * Nilai internal : tax_base > 0 (ada PPN) → invoice_amount, selain itu → calculate_field.
     * Nilai external : amount.
     *
     * match_status:
     *   'match'   — |nilaiInternal - nilaiExternal| < 0.01
     *   'unmatch' — selisih >= 0.01
     *   'no_pair' — doc_number tidak ada di tab lain
     *
     * Berlaku untuk kedua tab (status identik untuk sepasang doc).
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

        // Nilai internal per doc_number (hanya dibutuhkan saat tab = external).
        // calculate_field export internal sudah net return_amount → jangan dipulihkan.
        $internalRows = $tab === DetailSale::SOURCE_INTERNAL
            ? collect()
            : DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
                ->whereIn('doc_number', $docNumbers)
                ->get(['doc_number', 'tax_base', 'invoice_amount', 'calculate_field', 'return_amount']);
        $internalVals = $internalRows->mapWithKeys(fn ($r) => [
            $r->doc_number => (float) $r->tax_base > 0
                ? (float) $r->invoice_amount
                : (float) $r->calculate_field,
        ]);
        $internalReturns = $internalRows->mapWithKeys(fn ($r) => [
            $r->doc_number => (float) $r->return_amount,
        ]);

        // Total retur per invoice (amount retur sudah negatif). Koreksi hanya untuk
        // invoice yang punya dokumen SR di sales_returns — calculate_field export
        // internal sudah net return_amount kolom export, jadi hindari potong ganda.
        $returnDeductions = SalesReturn::query()
            ->whereIn('invoice_no', $docNumbers)
            ->groupBy('invoice_no')
            ->selectRaw('invoice_no, SUM(amount) AS total')
            ->pluck('total', 'invoice_no');

        foreach ($detailSales as $row) {
            $partner = $others[$row->doc_number] ?? null;

            if ($partner === null) {
                $row->match_status = 'no_pair';
                continue;
            }

            $doc = (string) $row->doc_number;

            if ($tab === DetailSale::SOURCE_INTERNAL) {
                $base = (float) $row->tax_base > 0
                    ? (float) $row->invoice_amount
                    : (float) $row->calculate_field;
                $koreksi = array_key_exists($doc, $returnDeductions->all())
                    ? (float) $returnDeductions[$doc] + (float) $row->return_amount
                    : 0.0;
                $nilaiInternal = $base + $koreksi;
                $nilaiExternal = (float) $partner->amount;
            } else {
                $internalRowBase = $internalVals[$doc] ?? null;
                if ($internalRowBase === null) {
                    $row->match_status = 'no_pair';
                    continue;
                }
                $koreksi = array_key_exists($doc, $returnDeductions->all())
                    ? (float) $returnDeductions[$doc] + (float) ($internalReturns[$doc] ?? 0)
                    : 0.0;
                $nilaiInternal = (float) $internalRowBase + $koreksi;
                $nilaiExternal = (float) $row->amount;
            }

            $row->match_status = abs($nilaiInternal - $nilaiExternal) < 0.01
                ? 'match'
                : 'unmatch';
        }
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
            }

            return redirect()
                ->route('detail-sales.index', ['tab' => $tab])
                ->with('success', $message);
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

        if ($result['blockers'] !== []) {
            return response()->json([
                'ok' => false,
                'message' => 'Export dibatalkan: ' . count($result['blockers']) . ' data belum lengkap.',
                'errors' => $result['blockers'],
            ], 422);
        }

        if ($result['count'] === 0) {
            return response()->json(['ok' => false, 'message' => 'Invoice tidak ditemukan di tab ' . $tab . '.'], 422);
        }

        $single = count($docNumbers) === 1 ? $docNumbers[0] : null;
        $suffix = ($single !== null ? ' ' . $single : '') . ' ' . now()->format('Ymd-His');

        return response()->json([
            'ok' => true,
            'count' => $result['count'],
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

        $header = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
            ->where('doc_number', $doc)
            ->first();

        if (! $header) {
            return $this->externalFailure(['Dokumen ' . $doc . ' tidak ditemukan di tab internal.']);
        }

        $lines = DetailSaleItem::where('source_type', DetailSaleItem::SOURCE_INTERNAL)
            ->where('doc_number', $doc)
            ->orderBy('id')
            ->get();

        if ($lines->isEmpty()) {
            return $this->externalFailure(['Item dokumen ' . $doc . ' tidak ditemukan.']);
        }

        $reasons = [];

        if ($header->transaction_date === null) {
            $reasons[] = 'Tanggal transaksi ' . $doc . ' kosong.';
        }

        $client = new AccurateApiClient();
        if (! $client->configured()) {
            $reasons[] = 'API Token dan Signature Secret Accurate belum diisi di Settings.';
        }

        $existing = ExternalDocument::where('doc_number', $doc)->where('type', $type)->first();
        if ($existing) {
            return response()->json([
                'ok' => true,
                'already' => true,
                'number' => $existing->accurate_number,
                'message' => strtoupper($type) . ' ' . $existing->accurate_number . ' sudah ada di Accurate.',
            ]);
        }

        [$custMap, $itemCodeMap, $itemNameMap] = $this->externalMaps();

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
            $remaining = (float) $ret->quantity;
            $found = false;
            $retExternal = trim((string) ($itemCodeMap[(string) $ret->item_no] ?? ''));

            foreach ($prepared as &$row) {
                $sameItem = (string) $row['line']->item_no === (string) $ret->item_no;

                if (! $sameItem && $retExternal !== '') {
                    $rowExternal = trim((string) ($itemCodeMap[(string) $row['line']->item_no] ?? ''));
                    $sameItem = $rowExternal !== '' && $rowExternal === $retExternal;
                }

                if (! $sameItem) {
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
            return $this->externalFailure($reasons);
        }

        foreach ($prepared as &$row) {
            $row['item_no_external'] = (string) $itemCodeMap[(string) $row['line']->item_no];
            $row['item_name_external'] = $this->externalItemName($row['line'], $itemNameMap);
        }
        unset($row);

        $soNumber = $soDoc?->accurate_number ?? $doc;
        $doNumber = $doDoc?->accurate_number ?? $doc;

        $payload = $this->buildExternalPayload($type, $header, $prepared, $customerNo, $soNumber, $doNumber);

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

    public function deleteExternalDoc(Request $request)
    {
        $data = $request->validate([
            'doc_number' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(['so', 'do', 'inv'])],
        ]);

        $type = $data['type'];
        $doc = $data['doc_number'];

        $row = ExternalDocument::where('doc_number', $doc)->where('type', $type)->first();

        if ($type === ExternalDocument::TYPE_SO
            && ExternalDocument::where('doc_number', $doc)->whereIn('type', [ExternalDocument::TYPE_DO, ExternalDocument::TYPE_INV])->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'Hapus DO dan INV ' . $doc . ' terlebih dahulu.',
            ], 422);
        }

        if ($type === ExternalDocument::TYPE_DO
            && ExternalDocument::where('doc_number', $doc)->where('type', ExternalDocument::TYPE_INV)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'Hapus INV ' . $doc . ' terlebih dahulu.',
            ], 422);
        }

        $client = new AccurateApiClient();
        if (! $client->configured()) {
            return response()->json([
                'ok' => false,
                'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.',
            ], 422);
        }

        $accurateId = $row?->accurate_id;
        if ($accurateId === null) {
            $found = $client->findByNumber(ExternalDocument::MODULES[$type], $doc);
            $accurateId = isset($found['id']) ? (int) $found['id'] : null;
        }

        if ($accurateId === null) {
            $row?->delete();

            if ($row && $type === ExternalDocument::TYPE_INV) {
                DetailSaleItem::where('source_type', DetailSaleItem::SOURCE_EXTERNAL)->where('doc_number', $doc)->delete();
                DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)->where('doc_number', $doc)->delete();
            }

            return response()->json([
                'ok' => true,
                'message' => strtoupper($type) . ' ' . $doc . ' sudah tidak ada di Accurate.',
            ]);
        }

        $result = $client->delete(ExternalDocument::MODULES[$type], $accurateId);
        if (! $result['ok'] && ! $this->isNotFound($result['message'])) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?? ('Accurate gagal menghapus ' . strtoupper($type) . '.'),
            ], 422);
        }

        $row?->delete();

        if ($row && $type === ExternalDocument::TYPE_INV) {
            DetailSaleItem::where('source_type', DetailSaleItem::SOURCE_EXTERNAL)->where('doc_number', $doc)->delete();
            DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)->where('doc_number', $doc)->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => strtoupper($type) . ' ' . $doc . ' dihapus dari Accurate.',
        ]);
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

    private function buildExternalPayload(string $type, DetailSale $header, array $prepared, string $customerNo, string $soNumber, string $doNumber): array
    {
        $termId = Customer::where('source_type', Customer::SOURCE_INTERNAL)
            ->where('customer_code', $header->customer_code)
            ->value('payment_term_id');
        $termDays = (int) (PaymentTerm::whereKey($termId)->value('days') ?? 0);

        $warehouse = trim((string) Setting::get('accurate_warehouse_name', 'Utama')) ?: 'Utama';
        $date = $header->transaction_date->format('d/m/Y');
        $doc = (string) $header->doc_number;

        $items = [];
        foreach ($prepared as $row) {
            $item = [
                'itemNo' => (string) $row['item_no_external'],
                'detailName' => (string) $row['item_name_external'],
                'quantity' => (float) $row['qty'],
                'itemUnitName' => (string) $row['line']->unit,
                'unitPrice' => (float) $row['line']->unit_price,
                'itemDiscPercent' => '0',
                'useTax1' => true,
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
            'taxable' => true,
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
        }

        return $payload;
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

    private function externalMaps(): array
    {
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

    private function mirrorExternalInvoice(DetailSale $header, array $payload, AccurateApiClient $client): void
    {
        $doc = (string) $header->doc_number;

        $dpp = 0.0;
        $items = [];
        foreach ($payload['detailItem'] as $item) {
            $qty = (float) $item['quantity'];
            $price = (float) $item['unitPrice'];
            $amount = $qty * $price;
            $dpp += $amount;

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
        $tax = round($dpp * $rate / 100, 2);
        $total = round($dpp + $tax, 2);
        $customerName = $this->accurateCustomerName($client, (string) $payload['customerNo']) ?? $header->customer_name;

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
                'discount' => 0,
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

    private function accurateCustomerName(AccurateApiClient $client, string $customerNo): ?string
    {
        $result = $client->get('/accurate/api/customer/detail.do', ['customerNo' => $customerNo]);
        if (! $result['ok']) {
            return null;
        }

        $name = $result['body']['d']['name'] ?? null;

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
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
