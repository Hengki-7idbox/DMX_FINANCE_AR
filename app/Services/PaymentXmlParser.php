<?php

namespace App\Services;

/**
 * Parse XML pelunasan Accurate (NMEXML / CUSTOMERRECEIPT).
 * Contoh struktur: Sample/des-2025.xml — 1 file berisi banyak receipt,
 * tiap receipt berisi 1+ InvoiceLine (ARINVOICEID + PAYMENTAMOUNT).
 */
class PaymentXmlParser
{
    /**
     * @return array{receipts: array<int, array<string, mixed>>, receipt_count: int, line_count: int, exim_id: string|null}
     */
    public function parse(string $xmlContent): array
    {
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if ($xml === false) {
            throw new \RuntimeException('File XML tidak valid atau bukan format XML.');
        }

        $receiptNodes = $xml->TRANSACTIONS->CUSTOMERRECEIPT ?? [];
        if (! isset($xml->TRANSACTIONS)) {
            throw new \RuntimeException('Struktur XML tidak ditemukan (tag TRANSACTIONS/CUSTOMERRECEIPT tidak ada).');
        }

        $receipts = [];
        $lineCount = 0;

        foreach ($receiptNodes as $node) {
            $lines = [];
            foreach ($node->InvoiceLine as $line) {
                $invoiceId = trim((string) $line->ARINVOICEID);
                if ($invoiceId === '') {
                    continue;
                }
                $lines[] = [
                    'ar_invoice_id' => $invoiceId,
                    'payment_amount' => (float) $line->PAYMENTAMOUNT,
                    'pph23_amount' => (float) $line->PPH23AMOUNT,
                    'disctaken_amount' => (float) $line->DISCTAKENAMOUNT,
                ];
                $lineCount++;
            }

            if ($lines === []) {
                continue;
            }

            $receipts[] = [
                'transaction_id' => trim((string) $node->TRANSACTIONID) ?: null,
                'sequence_no' => trim((string) $node->SEQUENCENO) ?: null,
                'payment_date' => $this->dateOrNull((string) $node->PAYMENTDATE),
                'bank_account' => trim((string) $node->BANKACCOUNT) ?: null,
                'cheque_no' => trim((string) $node->CHEQUENO) ?: null,
                'cheque_date' => $this->dateOrNull((string) $node->CHEQUEDATE),
                'cheque_amount' => $node->CHEQUEAMOUNT !== '' && $node->CHEQUEAMOUNT !== null
                    ? (float) $node->CHEQUEAMOUNT
                    : null,
                'bill_to_id' => trim((string) $node->BILLTOID) ?: null,
                'description' => trim((string) $node->DESCRIPTION) ?: null,
                'void' => (string) $node->VOID === '1',
                'lines' => $lines,
            ];
        }

        if ($receipts === []) {
            throw new \RuntimeException('Tidak ada CUSTOMERRECEIPT dengan InvoiceLine yang valid di file XML.');
        }

        return [
            'receipts' => $receipts,
            'receipt_count' => count($receipts),
            'line_count' => $lineCount,
            'exim_id' => trim((string) ($xml['EximID'] ?? '')) ?: null,
        ];
    }

    private function dateOrNull(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        return date('Y-m-d', strtotime($raw));
    }
}
