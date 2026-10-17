<?php

namespace App\Http\Controllers;

use App\Imports\DetailSaleItemsImport;
use App\Imports\DetailSalesImport;
use App\Models\DetailSale;
use App\Models\DetailSaleItem;
use App\Models\FakturPajak;
use App\Models\ImportSession;
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
        if ($tab === DetailSale::SOURCE_EXTERNAL) {
            $fpMap = FakturPajak::query()
                ->whereNotNull('reference')
                ->where('reference', '!=', '')
                ->orderBy('id')
                ->get(['reference', 'tax_invoice_number'])
                ->unique('reference')
                ->mapWithKeys(fn ($r) => [$r->reference => $r->tax_invoice_number]);
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
        $totalAll = DetailSale::where('source_type', $tab)->count();

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
        $internalVals = $tab === DetailSale::SOURCE_INTERNAL
            ? collect()
            : DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
                ->whereIn('doc_number', $docNumbers)
                ->get(['doc_number', 'tax_base', 'invoice_amount', 'calculate_field'])
                ->mapWithKeys(fn ($r) => [
                    $r->doc_number => (float) $r->tax_base > 0
                        ? (float) $r->invoice_amount
                        : (float) $r->calculate_field,
                ]);

        foreach ($detailSales as $row) {
            $partner = $others[$row->doc_number] ?? null;

            if ($partner === null) {
                $row->match_status = 'no_pair';
                continue;
            }

            if ($tab === DetailSale::SOURCE_INTERNAL) {
                $nilaiInternal = (float) $row->tax_base > 0
                    ? (float) $row->invoice_amount
                    : (float) $row->calculate_field;
                $nilaiExternal = (float) $partner->amount;
            } else {
                $nilaiInternal = $internalVals[$row->doc_number] ?? null;
                $nilaiExternal = (float) $row->amount;
            }

            if ($nilaiInternal === null) {
                $row->match_status = 'no_pair';
                continue;
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

    private function resolveTab(Request $request): string
    {
        $tab = $request->query('tab', $request->input('tab', DetailSale::SOURCE_EXTERNAL));

        return in_array($tab, [DetailSale::SOURCE_INTERNAL, DetailSale::SOURCE_EXTERNAL], true)
            ? $tab
            : DetailSale::SOURCE_EXTERNAL;
    }
}
