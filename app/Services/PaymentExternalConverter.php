<?php

namespace App\Services;

use App\Models\MappingCustomer;
use App\Models\PaymentLine;
use App\Models\PaymentUpload;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Konversi pelunasan INTERNAL (hasil upload XML internal) -> XML EXTERNAL,
 * mengikuti aturan converter Python "converter_pelunasan_v2.py":
 *
 * - BANKACCOUNT diganti ke rekening external (110201)
 * - BILLTOID (kode customer internal) di-mapping ke kode external via tabel
 *   mapping_customers; receipt TANPA mapping dilewati (dilaporkan)
 * - PPN dideteksi otomatis dari kode external:
 *     "TNPWP" ada            -> non-PPN (DISCTAKENAMOUNT = 0)
 *     "KTP"/"NPWP" ada       -> PPN 11% (DISCTAKENAMOUNT = floor(bayar x 11%)
 *                               + <DiscountLine operation="Ret"> akun 210410)
 *     selainnya (CASH, dll)  -> non-PPN
 * - EximID root: mengikuti file upload sumber (ikuti converter lama,
 *   EximID file asli tidak diubah)
 * - PPN rate diambil dari Setting tax_rate (default 11)
 */
class PaymentExternalConverter
{
    private const BANK_ACCOUNT_EXTERNAL = '110201';
    private const DISC_ACCOUNT = '210410';

    /**
     * @param  array<int, string>  $invoiceNos
     * @return array{
     *     xml: string,
     *     receipt_count: int,
     *     ppn_count: int,
     *     non_ppn_count: int,
     *     skipped: array<int, array{sequence_no: string|null, bill_to_id: string|null, line_count: int}>,
     *     warnings: array<int, string>,
     *     exim_id: string|null
     * }
     */
    public function convert(array $invoiceNos): array
    {
        $lines = PaymentLine::whereIn('ar_invoice_id', $invoiceNos)
            ->where('source_type', PaymentLine::SOURCE_INTERNAL)
            ->where('void', false)
            ->orderBy('id')
            ->get();

        if ($lines->isEmpty()) {
            throw new \RuntimeException('Tidak ada data pelunasan internal untuk invoice terpilih.');
        }

        // Receipt diambil UTUH: semua line pada receipt yang line-nya menyentuh
        // invoice terpilih ikut terkonversi (CHEQUEAMOUNT = total receipt asli).
        $sequenceNos = $lines->pluck('sequence_no')->filter()->unique()->values();
        if ($sequenceNos->isNotEmpty()) {
            $lines = PaymentLine::whereIn('sequence_no', $sequenceNos)
                ->where('source_type', PaymentLine::SOURCE_INTERNAL)
                ->where('void', false)
                ->orderBy('id')
                ->get();
        }

        $custMap = $this->customerMap();
        $rate = ((float) Setting::get('tax_rate', 11)) / 100;

        $skipped = [];
        $receipts = [];
        $ppnCount = 0;

        $groups = $lines->groupBy(fn ($l) => $l->sequence_no ?? ('line-' . $l->id));
        foreach ($groups as $group) {
            $first = $group->first();
            $billTo = trim((string) $first->bill_to_id);
            $extCode = $custMap[$billTo] ?? null;

            if ($extCode === null) {
                $skipped[] = [
                    'sequence_no' => $first->sequence_no,
                    'bill_to_id' => $first->bill_to_id,
                    'line_count' => $group->count(),
                ];
                continue;
            }

            $usePpn = $this->isKenaPpn($extCode);
            if ($usePpn) {
                $ppnCount++;
            }

            $outLines = [];
            foreach ($group as $line) {
                $amount = (float) $line->payment_amount;
                $disc = $usePpn ? (int) floor($amount * $rate) : 0;

                $outLines[] = [
                    'ar_invoice_id' => $line->ar_invoice_id,
                    'payment_amount' => $amount,
                    'pph23_amount' => (float) $line->pph23_amount,
                    'disctaken_amount' => (float) $disc,
                    'discount' => $disc > 0 ? (float) $disc : 0.0,
                    'disc_account' => self::DISC_ACCOUNT,
                ];
            }

            $receipts[] = [
                'sequence_no' => $first->sequence_no,
                'payment_date' => $first->payment_date?->toDateString(),
                'bank_account' => self::BANK_ACCOUNT_EXTERNAL,
                'cheque_no' => $first->cheque_no,
                'cheque_date' => ($first->cheque_date ?? $first->payment_date)?->toDateString(),
                'cheque_amount' => $first->cheque_amount !== null
                    ? (float) $first->cheque_amount
                    : (float) $group->sum('payment_amount'),
                'bill_to_id' => $extCode,
                'description' => $first->description,
                'void' => false,
                'transaction_id' => $first->transaction_id,
                'lines' => $outLines,
            ];
        }

        if ($receipts === []) {
            throw new \RuntimeException(
                'Semua receipt dilewati: ' . count($skipped) . ' kode customer internal tidak ada di mapping (/mapping-customers).'
            );
        }

        [$eximId, $warnings] = $this->resolveEximId($lines);

        return [
            'xml' => (new PaymentXmlGenerator())->build($receipts, $eximId),
            'receipt_count' => count($receipts),
            'ppn_count' => $ppnCount,
            'non_ppn_count' => count($receipts) - $ppnCount,
            'skipped' => $skipped,
            'warnings' => $warnings,
            'exim_id' => $eximId,
        ];
    }

    /**
     * Peta kode customer internal -> external (baris pertama menang),
     * sama seperti ExternalXmlBuilder::loadMappings().
     *
     * @return array<string, string>
     */
    private function customerMap(): array
    {
        $map = [];
        foreach (MappingCustomer::query()->get() as $m) {
            $key = (string) $m->kode_internal;
            if ($key !== '' && ! isset($map[$key])) {
                $map[$key] = (string) $m->kode_external;
            }
        }

        return $map;
    }

    /** Deteksi PPN dari kode external — persis converter_pelunasan_v2.py. */
    private function isKenaPpn(string $code): bool
    {
        $code = strtoupper(trim($code));
        if (str_contains($code, 'TNPWP')) {
            return false;
        }
        if (str_contains($code, 'KTP') || str_contains($code, 'NPWP')) {
            return true;
        }

        return false;
    }

    /**
     * EximID file upload sumber (upload dengan line terbanyak);
     * upload lama tanpa exim_id -> null (fallback Setting saat build).
     *
     * @param  Collection<int, PaymentLine>  $lines
     * @return array{0: string|null, 1: array<int, string>}
     */
    private function resolveEximId(Collection $lines): array
    {
        $warnings = [];
        $uploadIds = $lines->pluck('upload_id')->filter()->unique()->values();

        if ($uploadIds->isEmpty()) {
            return [null, ['Line tidak terkait file upload — EximID mengikuti Setting.']];
        }

        $counts = $lines->groupBy('upload_id')->map->count()->sortDesc();
        $majorityId = $counts->keys()->first();
        $upload = PaymentUpload::find($majorityId);
        $eximId = $upload?->exim_id;

        if ($uploadIds->count() > 1) {
            $warnings[] = 'Invoice terpilih berasal dari ' . $uploadIds->count() . ' file upload berbeda — EximID diambil dari upload terbanyak.';
        }
        if ($eximId === null) {
            $warnings[] = 'File upload sumber belum menyimpan EximID (upload sebelum fitur ini) — upload ulang file XML internal, atau EximID mengikuti Setting.';
        }

        return [$eximId, $warnings];
    }
}
