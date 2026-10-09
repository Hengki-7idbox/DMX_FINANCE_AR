<?php

namespace App\Http\Controllers;

use App\Models\DetailSale;
use App\Models\FakturPajak;
use App\Models\ImportSession;
use App\Models\ImportSessionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OlahDataController extends Controller
{
    private const AMOUNT_FIELDS = [
        'invoice_amount', 'amount', 'tax_base', 'discount',
        'return_amount', 'owing', 'payment_amount', 'calculate_field',
        'total_sales',
    ];

    private const FIELD_LABELS = [
        'customer_code' => 'Customer No.',
        'customer_name' => 'Customer Name',
        'transaction_date' => 'Tanggal',
        'description' => 'Deskripsi',
        'invoice_amount' => 'Invoice Amount',
        'amount' => 'Amount',
        'tax_base' => 'Tax Base',
        'discount' => 'Discount',
        'return_amount' => 'Return',
        'owing' => 'Owing',
        'payment_amount' => 'Payment',
        'calculate_field' => 'Calculate Field',
        'total_sales' => 'Total Sales',
    ];

    public function perubahanDataInternal(Request $request)
    {
        $tab = $request->query('tab', 'internal');
        if (! in_array($tab, ['internal', 'external'], true)) {
            $tab = 'internal';
        }

        $sessions = ImportSession::where('source_type', $tab)
            ->orderByDesc('created_at')
            ->get();

        $selected = null;
        if ($sessions->isNotEmpty()) {
            $requestedId = $request->query('session');
            $selected = $requestedId !== null
                ? $sessions->firstWhere('id', (int) $requestedId)
                : null;
            $selected = $selected ?? $sessions->first();
        }

        $details = $selected !== null
            ? ImportSessionDetail::where('session_id', $selected->id)
                ->orderByRaw("FIELD(change_type, 'new', 'changed', 'unchanged')")
                ->orderBy('doc_number')
                ->get()
            : collect();

        $payload = $this->detailPayload($details);

        return view('olah-data.perubahan-internal', compact('sessions', 'selected', 'details', 'payload', 'tab'));
    }

    /**
     * Payload ringkas utk render DataTables (client-side).
     */
    private function detailPayload(Collection $details): array
    {
        return $details->map(function ($d) {
            $diff = [];
            foreach ($d->old_values ?? [] as $field => $old) {
                $new = $d->new_values[$field] ?? null;
                $label = self::FIELD_LABELS[$field] ?? $field;
                $diff[] = $label . ': ' . $this->formatValue($old, $field)
                    . ' → ' . $this->formatValue($new, $field);
            }

            return [
                'doc' => $d->doc_number,
                'type' => $d->change_type,
                'date' => $d->transaction_date?->format('d/m/Y'),
                'customer' => $d->customer_name,
                'invoice_amount' => (float) ($d->new_values['invoice_amount'] ?? $d->new_values['total_sales'] ?? 0),
                'diff' => $diff,
            ];
        })->values()->all();
    }

    private function formatValue(mixed $value, string $field): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (in_array($field, self::AMOUNT_FIELDS, true)) {
            return 'Rp ' . number_format((float) $value, 0, ',', '.');
        }

        if ($field === 'transaction_date') {
            $ts = strtotime((string) $value);

            return $ts !== false ? date('d/m/Y', $ts) : '-';
        }

        return (string) $value;
    }

    /**
     * Hapus semua riwayat upload (import_sessions + details cascade).
     * Akses dilindungi password.
     */
    public function clearPerubahanInternal(Request $request)
    {
        if ($request->input('password') !== 'dmx8899') {
            return back()->with('error', 'Password salah. Riwayat tidak dihapus.');
        }

        $deleted = ImportSession::query()->delete();

        return back()->with('success', "Berhasil menghapus {$deleted} riwayat upload.");
    }

    /**
     * Hapus satu riwayat sesi (detail ikut terhapus via cascade).
     */
    public function destroySession(ImportSession $session)
    {
        $tab = $session->source_type;
        $session->delete();

        return redirect()
            ->route('olah-data.perubahan-data-internal', ['tab' => $tab])
            ->with('success', 'Riwayat sesi berhasil dihapus.');
    }

    /**
     * Rekonsiliasi harian internal: dokumen per tanggal + penanda
     * sudah/belum terima + catatan.
     */
    public function rekonsiliasiHarianInternal(Request $request)
    {
        $date = $request->query('date');
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
            // Default: kemarin (tanggal hari ini - 1 hari).
            $date = now()->subDay()->format('Y-m-d');
        }

        $rows = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
            ->whereDate('transaction_date', $date)
            ->orderBy('doc_number')
            ->get([
                'id', 'doc_number', 'transaction_date', 'customer_name',
                'invoice_amount', 'is_received', 'received_note',
            ]);

        $payload = $rows->map(fn ($r) => [
            'id' => $r->id,
            'doc' => $r->doc_number,
            'date' => $r->transaction_date?->format('d/m/Y'),
            'customer' => $r->customer_name,
            'invoice_amount' => (float) $r->invoice_amount,
            'receive_status' => (int) $r->is_received,
            'is_exchanged' => (int) $r->is_exchanged,
            'note' => (string) ($r->received_note ?? ''),
        ])->values();

        $countByStatus = fn (int $status) => $payload->where('receive_status', $status)->count();
        $pendingCount = $countByStatus(0);
        $exchangedCount = $payload->where('is_exchanged', 1)->count();

        return view('olah-data.rekonsiliasi-harian-internal', [
            'date' => $date,
            'payload' => $payload,
            'total' => $payload->count(),
            'receivedCount' => $countByStatus(1),
            'pendingCount' => $pendingCount,
            'onlineCount' => $countByStatus(2),
            'cashCount' => $countByStatus(3),
            'exchangedCount' => $exchangedCount,
            'notExchangedCount' => $payload->count() - $exchangedCount,
        ]);
    }

    /**
     * Update status terima (0=Belum, 1=Sudah, 2=Online, 3=Cash) / catatan dokumen (JSON).
     */
    public function updateReceipt(Request $request)
    {
        $data = $request->validate([
            'doc_number' => 'required|string|max:50',
            'receive_status' => 'nullable|integer|in:0,1,2,3',
            'is_exchanged' => 'nullable|integer|in:0,1',
            'received_note' => 'nullable|string|max:65000',
        ]);

        $row = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
            ->where('doc_number', $data['doc_number'])
            ->first();

        if ($row === null) {
            return response()->json(['ok' => false, 'message' => 'Dokumen tidak ditemukan.'], 404);
        }

        if (array_key_exists('receive_status', $data)) {
            $row->is_received = (int) $data['receive_status'];
            $row->received_at = $row->is_received !== 0 ? now() : null;
        }

        if (array_key_exists('is_exchanged', $data)) {
            $row->is_exchanged = (int) $data['is_exchanged'];
        }

        if (array_key_exists('received_note', $data)) {
            $row->received_note = $data['received_note'];
        }

        $row->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Bulk update status terima untuk invoice terpilih (by id, JSON).
     */
    public function bulkUpdateReceipt(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:detail_sales,id',
            'receive_status' => 'required|integer|in:0,1,2,3',
        ]);

        $count = DB::transaction(function () use ($data) {
            return DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
                ->whereIn('id', $data['ids'])
                ->update([
                    'is_received' => $data['receive_status'],
                    'received_at' => $data['receive_status'] !== 0 ? now() : null,
                ]);
        });

        return response()->json(['ok' => true, 'count' => $count]);
    }

    /**
     * Bulk update status tukar faktur (0/1) untuk invoice terpilih (by id, JSON).
     */
    public function bulkUpdateExchange(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:detail_sales,id',
            'is_exchanged' => 'required|integer|in:0,1',
        ]);

        $count = DB::transaction(function () use ($data) {
            return DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
                ->whereIn('id', $data['ids'])
                ->update(['is_exchanged' => $data['is_exchanged']]);
        });

        return response()->json(['ok' => true, 'count' => $count]);
    }

    /**
     * Data external yang tidak match (unmatch + tidak ada pasangan internal).
     * Logika status identik dengan DetailSaleController::annotateMatch.
     */
    public function dataExternalTidakSama()
    {
        $externals = DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get([
                'id', 'fp_status', 'customer_code', 'customer_name', 'doc_number',
                'transaction_date', 'description',
                'invoice_amount', 'amount', 'tax_base', 'discount',
                'return_amount', 'owing', 'payment_amount', 'calculate_field',
            ]);

        $internals = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
            ->get(['doc_number', 'tax_base', 'invoice_amount', 'calculate_field'])
            ->mapWithKeys(fn ($r) => [
                $r->doc_number => (float) $r->tax_base > 0
                    ? (float) $r->invoice_amount
                    : (float) $r->calculate_field,
            ]);

        $fpMap = FakturPajak::query()
            ->whereNotNull('reference')
            ->where('reference', '!=', '')
            ->orderBy('id')
            ->get(['reference', 'tax_invoice_number'])
            ->unique('reference')
            ->mapWithKeys(fn ($r) => [$r->reference => $r->tax_invoice_number]);

        $payload = $externals
            ->map(function ($r) use ($internals, $fpMap) {
                $nilaiInternal = $internals[$r->doc_number] ?? null;

                if ($nilaiInternal === null) {
                    $status = 'no_pair';
                } else {
                    $status = abs($nilaiInternal - (float) $r->amount) < 0.01
                        ? 'match'
                        : 'unmatch';
                }

                return [
                    'id' => $r->id,
                    'customer_code' => $r->customer_code,
                    'customer_name' => $r->customer_name,
                    'doc_number' => $r->doc_number,
                    'transaction_date' => $r->transaction_date?->format('Y-m-d'),
                    'description' => $r->description,
                    'status' => $status,
                    'fp_number' => $fpMap[$r->doc_number] ?? null,
                    'fp_status' => $r->fp_status,
                    'invoice_amount' => (float) $r->invoice_amount,
                    'amount' => (float) $r->amount,
                    'tax_base' => (float) $r->tax_base,
                    'discount' => (float) $r->discount,
                    'return_amount' => (float) $r->return_amount,
                    'owing' => (float) $r->owing,
                    'payment_amount' => (float) $r->payment_amount,
                    'calculate_field' => (float) $r->calculate_field,
                ];
            })
            ->filter(fn ($r) => $r['status'] !== 'match')
            ->values();

        return view('olah-data.external-tidak-sama', [
            'payload' => $payload,
            'total' => $payload->count(),
            'unmatchCount' => $payload->where('status', 'unmatch')->count(),
            'noPairCount' => $payload->where('status', 'no_pair')->count(),
        ]);
    }
}
