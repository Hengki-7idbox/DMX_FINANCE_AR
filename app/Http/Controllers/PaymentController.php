<?php

namespace App\Http\Controllers;

use App\Models\DetailSale;
use App\Models\PaymentLine;
use App\Models\PaymentUpload;
use App\Services\PaymentExternalConverter;
use App\Services\PaymentXmlGenerator;
use App\Services\PaymentXmlParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $dari = $request->query('dari');
        $sampai = $request->query('sampai');

        $base = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL);
        if ($dari === null && $sampai === null) {
            $maxDate = (clone $base)->max('transaction_date');
            if ($maxDate !== null) {
                $y = (int) substr((string) $maxDate, 0, 4);
                $m = (int) substr((string) $maxDate, 5, 2);
                $dari = sprintf('%04d-%02d-01', $y, $m);
                $sampai = sprintf('%04d-%02d-%02d', $y, $m, (int) date('t', mktime(0, 0, 0, $m, 1, $y)));
            } else {
                $dari = now()->startOfMonth()->toDateString();
                $sampai = now()->endOfMonth()->toDateString();
            }
        }

        $invoices = $base
            ->when($dari, fn ($q) => $q->where('transaction_date', '>=', $dari))
            ->when($sampai, fn ($q) => $q->where('transaction_date', '<=', $sampai))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get([
                'id', 'customer_code', 'customer_name', 'doc_number',
                'transaction_date', 'invoice_amount', 'amount', 'tax_base',
                'calculate_field', 'owing', 'payment_amount', 'return_amount',
            ]);

        $docNumbers = $invoices->pluck('doc_number');

        $externalSales = DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)
            ->whereIn('doc_number', $docNumbers)
            ->get(['doc_number', 'invoice_amount', 'payment_amount', 'tax_base'])
            ->keyBy('doc_number');

        $externalOnlyCount = DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)
            ->whereBetween('transaction_date', [$dari, $sampai])
            ->whereNotIn('doc_number', $docNumbers)
            ->count();

        $linesBySource = PaymentLine::whereIn('ar_invoice_id', $docNumbers)
            ->where('void', false)
            ->get([
                'ar_invoice_id', 'source_type', 'payment_amount',
                'sequence_no', 'payment_date', 'bank_account', 'cheque_amount',
                'bill_to_id', 'transaction_id', 'description', 'created_at',
            ])
            ->groupBy('source_type');

        $payload = $invoices->map(function ($r) use ($linesBySource, $externalSales) {
            $internalLines = $linesBySource->get(PaymentLine::SOURCE_INTERNAL, collect())
                ->where('ar_invoice_id', $r->doc_number);
            $externalLines = $linesBySource->get(PaymentLine::SOURCE_EXTERNAL, collect())
                ->where('ar_invoice_id', $r->doc_number);
            $webLines = $linesBySource->get(PaymentLine::SOURCE_WEB, collect())
                ->where('ar_invoice_id', $r->doc_number);

            $invoiceAmount = (float) $r->invoice_amount;
            $paymentAmount = (float) $r->payment_amount;
            $extSale = $externalSales->get($r->doc_number);
            $extPaymentAmount = $extSale !== null ? (float) $extSale->payment_amount : null;
            $extInvoiceAmount = $extSale !== null ? (float) $extSale->invoice_amount : null;

            if ($extSale === null) {
                $matchStatus = 'hanya_internal';
                $extDiff = null;
            } else {
                // Match: (nilai invoice - retur) internal == nilai invoice external, atau external termasuk PPN 11% — toleransi Rp 1 (pembulatan)
                $netInternal = $invoiceAmount - (float) $r->return_amount;
                $isMatch = abs($extInvoiceAmount - $netInternal) <= 1.0
                    || abs($extInvoiceAmount / 1.11 - $netInternal) <= 1.0;
                $matchStatus = $isMatch ? 'match' : 'beda_nilai';
                $extDiff = $isMatch ? null : $extInvoiceAmount - $netInternal;
            }

            return [
                'id' => $r->id,
                'doc_number' => $r->doc_number,
                'transaction_date' => $r->transaction_date?->format('Y-m-d'),
                'customer_code' => $r->customer_code,
                'customer_name' => $r->customer_name,
                'invoice_amount' => $invoiceAmount,
                'payment_amount' => $paymentAmount,
                'payment_amount_external' => $extPaymentAmount,
                'ext_invoice_amount' => $extInvoiceAmount,
                'ext_tax_base' => $extSale !== null ? (float) $extSale->tax_base : null,
                'return_amount' => (float) $r->return_amount,
                'match_status' => $matchStatus,
                'ext_diff' => $extDiff,
                'owing' => (float) $r->owing,
                'status_internal' => $this->paymentStatus($internalLines, $paymentAmount)['status'],
                'status_external' => $extSale !== null
                    ? $this->paymentStatus($externalLines, $extPaymentAmount)['status']
                    : 'belum',
                'paid_internal' => (float) $internalLines->sum('payment_amount'),
                'paid_external' => (float) $externalLines->sum('payment_amount'),
                'paid_web' => (float) $webLines->sum('payment_amount'),
                'web_count' => $webLines->count(),
                'detail_count' => $internalLines->count() + $externalLines->count() + $webLines->count(),
            ];
        })->values();

        $uploads = [
            PaymentUpload::SOURCE_INTERNAL => PaymentUpload::where('source_type', PaymentUpload::SOURCE_INTERNAL)->latest('id')->first(),
            PaymentUpload::SOURCE_EXTERNAL => PaymentUpload::where('source_type', PaymentUpload::SOURCE_EXTERNAL)->latest('id')->first(),
        ];

        $compare = [
            'match' => $payload->where('match_status', 'match')->count(),
            'beda_nilai' => $payload->where('match_status', 'beda_nilai')->count(),
            'hanya_internal' => $payload->where('match_status', 'hanya_internal')->count(),
            'hanya_external' => $externalOnlyCount,
        ];

        return view('payments.index', [
            'payload' => $payload,
            'dari' => $dari,
            'sampai' => $sampai,
            'uploads' => $uploads,
            'compare' => $compare,
        ]);
    }

    public function uploadXml(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xml', 'max:10240'],
            'source_type' => ['required', Rule::in([PaymentUpload::SOURCE_INTERNAL, PaymentUpload::SOURCE_EXTERNAL])],
        ]);

        $xmlContent = file_get_contents($data['file']->getRealPath());
        if ($xmlContent === false) {
            return back()->with('error', 'File tidak bisa dibaca.');
        }

        try {
            $parsed = (new PaymentXmlParser())->parse($xmlContent);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $userId = $request->user()?->id;

        DB::transaction(function () use ($data, $parsed, $userId) {
            PaymentLine::where('source_type', $data['source_type'])
                ->whereNotNull('upload_id')
                ->delete();

            $upload = PaymentUpload::create([
                'source_type' => $data['source_type'],
                'file_name' => $data['file']->getClientOriginalName(),
                'exim_id' => $parsed['exim_id'],
                'user_id' => $userId,
                'receipt_count' => $parsed['receipt_count'],
                'line_count' => $parsed['line_count'],
            ]);

            $rows = [];
            foreach ($parsed['receipts'] as $receipt) {
                foreach ($receipt['lines'] as $line) {
                    $rows[] = [
                        'upload_id' => $upload->id,
                        'source_type' => $data['source_type'],
                        'ar_invoice_id' => $line['ar_invoice_id'],
                        'payment_amount' => $line['payment_amount'],
                        'pph23_amount' => $line['pph23_amount'],
                        'disctaken_amount' => $line['disctaken_amount'],
                        'sequence_no' => $receipt['sequence_no'],
                        'payment_date' => $receipt['payment_date'],
                        'bank_account' => $receipt['bank_account'],
                        'cheque_no' => $receipt['cheque_no'],
                        'cheque_date' => $receipt['cheque_date'],
                        'cheque_amount' => $receipt['cheque_amount'],
                        'bill_to_id' => $receipt['bill_to_id'],
                        'transaction_id' => $receipt['transaction_id'],
                        'description' => $receipt['description'],
                        'void' => $receipt['void'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                PaymentLine::insert($chunk);
            }
        });

        return back()->with('success', "Upload XML {$data['source_type']} selesai: {$parsed['receipt_count']} receipt, {$parsed['line_count']} baris invoice.");
    }

    public function detail(string $invoice)
    {
        $sale = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
            ->where('doc_number', $invoice)
            ->first([
                'doc_number', 'customer_name', 'customer_code', 'transaction_date',
                'invoice_amount', 'owing', 'payment_amount',
            ]);

        if ($sale === null) {
            return response()->json(['ok' => false, 'message' => 'Invoice tidak ditemukan di tab internal.'], 404);
        }

        $lines = PaymentLine::where('ar_invoice_id', $invoice)
            ->where('void', false)
            ->orderBy('id')
            ->get([
                'source_type', 'payment_amount', 'sequence_no', 'payment_date',
                'bank_account', 'cheque_amount', 'bill_to_id', 'transaction_id',
                'description', 'created_at',
            ]);

        $invoiceAmount = (float) $sale->invoice_amount;
        $paymentAmount = (float) $sale->payment_amount;

        $extSale = DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)
            ->where('doc_number', $invoice)
            ->first(['payment_amount']);
        $extPaymentAmount = $extSale !== null ? (float) $extSale->payment_amount : null;

        return response()->json([
            'ok' => true,
            'invoice' => [
                'doc_number' => $sale->doc_number,
                'customer_name' => $sale->customer_name,
                'customer_code' => $sale->customer_code,
                'transaction_date' => $sale->transaction_date?->format('Y-m-d'),
                'invoice_amount' => $invoiceAmount,
                'payment_amount' => $paymentAmount,
                'payment_amount_external' => $extPaymentAmount,
                'owing' => (float) $sale->owing,
                'status_internal' => $this->paymentStatus($lines->where('source_type', PaymentLine::SOURCE_INTERNAL), $paymentAmount)['status'],
                'status_external' => $extSale !== null
                    ? $this->paymentStatus($lines->where('source_type', PaymentLine::SOURCE_EXTERNAL), $extPaymentAmount)['status']
                    : 'belum',
            ],
            'lines' => $lines->map(fn ($l) => [
                'source_type' => $l->source_type,
                'payment_amount' => (float) $l->payment_amount,
                'sequence_no' => $l->sequence_no,
                'payment_date' => $l->payment_date?->format('Y-m-d'),
                'bank_account' => $l->bank_account,
                'cheque_amount' => $l->cheque_amount !== null ? (float) $l->cheque_amount : null,
                'bill_to_id' => $l->bill_to_id,
                'transaction_id' => $l->transaction_id,
                'description' => $l->description,
                'created_at' => $l->created_at?->format('Y-m-d H:i'),
            ])->values(),
        ]);
    }

    public function pay(Request $request, string $invoice)
    {
        $sale = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
            ->where('doc_number', $invoice)
            ->first();

        if ($sale === null) {
            return response()->json(['ok' => false, 'message' => 'Invoice tidak ditemukan di tab internal.'], 404);
        }

        $data = $request->validate([
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'sequence_no' => ['required', 'string', 'max:50'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $billTo = trim($sale->customer_name . ' ' . $sale->customer_code);

        $line = PaymentLine::create([
            'upload_id' => null,
            'source_type' => PaymentLine::SOURCE_WEB,
            'ar_invoice_id' => $sale->doc_number,
            'payment_amount' => $data['payment_amount'],
            'sequence_no' => $data['sequence_no'],
            'payment_date' => $data['payment_date'],
            'bank_account' => $data['bank_account'] ?? null,
            'cheque_amount' => $data['payment_amount'],
            'bill_to_id' => $billTo !== '' ? $billTo : null,
            'description' => $data['description'] ?? null,
        ]);

        $xml = (new PaymentXmlGenerator())->build([
            [
                'sequence_no' => $line->sequence_no,
                'payment_date' => $line->payment_date?->toDateString(),
                'bank_account' => $line->bank_account,
                'cheque_amount' => (float) $line->cheque_amount,
                'bill_to_id' => $line->bill_to_id,
                'description' => $line->description,
                'void' => false,
                'lines' => [
                    [
                        'ar_invoice_id' => $line->ar_invoice_id,
                        'payment_amount' => (float) $line->payment_amount,
                        'pph23_amount' => 0,
                        'disctaken_amount' => 0,
                    ],
                ],
            ],
        ]);

        $filename = 'pelunasan-web-' . $sale->doc_number . '-' . now()->format('Ymd-His') . '.xml';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Payment-Saved' => $line->id,
        ]);
    }

    public function exportXml(Request $request)
    {
        $data = $request->validate([
            'source_type' => ['required', Rule::in([PaymentLine::SOURCE_INTERNAL, PaymentLine::SOURCE_EXTERNAL, PaymentLine::SOURCE_WEB])],
            'invoice_nos' => ['required', 'array', 'min:1'],
            'invoice_nos.*' => ['string', 'max:50'],
        ]);

        $lines = PaymentLine::where('source_type', $data['source_type'])
            ->whereIn('ar_invoice_id', $data['invoice_nos'])
            ->where('void', false)
            ->orderBy('id')
            ->get();

        if ($lines->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak ada data pelunasan ' . $data['source_type'] . ' untuk invoice terpilih.',
            ], 422);
        }

        $receipts = $lines
            ->groupBy(fn ($l) => $l->sequence_no ?? ('line-' . $l->id))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'sequence_no' => $first->sequence_no,
                    'payment_date' => $first->payment_date?->toDateString(),
                    'bank_account' => $first->bank_account,
                    'cheque_no' => $first->cheque_no,
                    'cheque_date' => $first->cheque_date?->toDateString() ?? $first->payment_date?->toDateString(),
                    'cheque_amount' => $first->cheque_amount !== null ? (float) $first->cheque_amount : (float) $group->sum('payment_amount'),
                    'bill_to_id' => $first->bill_to_id,
                    'transaction_id' => $first->transaction_id,
                    'description' => $first->description,
                    'void' => false,
                    'lines' => $group->map(fn ($l) => [
                        'ar_invoice_id' => $l->ar_invoice_id,
                        'payment_amount' => (float) $l->payment_amount,
                        'pph23_amount' => (float) $l->pph23_amount,
                        'disctaken_amount' => (float) $l->disctaken_amount,
                    ])->all(),
                ];
            })
            ->values()
            ->all();

        $xml = (new PaymentXmlGenerator())->build($receipts);
        $filename = 'pelunasan-' . $data['source_type'] . '-' . now()->format('Ymd-His') . '.xml';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Konversi pelunasan internal terpilih -> XML external
     * (mapping customer + PPN otomatis, aturan converter_pelunasan_v2).
     */
    public function exportExternal(Request $request)
    {
        $data = $request->validate([
            'invoice_nos' => ['required', 'array', 'min:1'],
            'invoice_nos.*' => ['string', 'max:50'],
        ]);

        try {
            $result = (new PaymentExternalConverter())->convert($data['invoice_nos']);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $filename = 'EXT_' . now()->format('Ymd-His') . '.xml';

        $headers = [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Receipt-Count' => (string) $result['receipt_count'],
            'X-Ppn-Count' => (string) $result['ppn_count'],
        ];

        if ($result['skipped'] !== []) {
            $codes = array_values(array_unique(array_map(
                fn ($s) => (string) $s['bill_to_id'],
                $result['skipped']
            )));
            $headers['X-Skipped-Receipts'] = (string) count($result['skipped']);
            $headers['X-Skipped-Codes'] = rawurlencode(json_encode($codes, JSON_UNESCAPED_UNICODE));
        }
        if ($result['warnings'] !== []) {
            $headers['X-Convert-Warnings'] = rawurlencode(json_encode($result['warnings'], JSON_UNESCAPED_UNICODE));
        }

        return response($result['xml'], 200, $headers);
    }

    /**
     * Cocok: total pelunasan non-void == expected (payment_amount Accurate, ±0.01);
     * belum: tanpa baris; selain itu tidak_cocok.
     *
     * @param  \Illuminate\Support\Collection  $lines
     * @return array{status: string, total: float}
     */
    private function paymentStatus($lines, float $expected): array
    {
        $total = (float) $lines->sum('payment_amount');

        if ($lines->isEmpty()) {
            return ['status' => 'belum', 'total' => 0.0];
        }

        return [
            'status' => abs($total - $expected) < 0.01 ? 'cocok' : 'tidak_cocok',
            'total' => $total,
        ];
    }
}
