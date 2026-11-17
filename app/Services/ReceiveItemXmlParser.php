<?php

namespace App\Services;

use RuntimeException;

/**
 * Membaca file "Import Receive item.xml" (format NMEXML / Exim) menjadi
 * struktur dokumen penerimaan barang yang siap dipetakan ke Accurate.
 */
class ReceiveItemXmlParser
{
    /**
     * @return array{
     *     exim_id: ?string,
     *     branch_code: ?string,
     *     documents: array<int,array<string,mixed>>
     * }
     */
    public function parseFile(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('File XML tidak ditemukan.');
        }

        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException('File XML kosong atau tidak bisa dibaca.');
        }

        return $this->parseString($raw);
    }

    /**
     * @return array{
     *     exim_id: ?string,
     *     branch_code: ?string,
     *     documents: array<int,array<string,mixed>>
     * }
     */
    public function parseString(string $raw): array
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            $first = $errors[0]->message ?? 'format tidak dikenali';

            throw new RuntimeException('XML tidak valid: ' . trim($first));
        }

        if (! isset($xml->TRANSACTIONS)) {
            throw new RuntimeException('Struktur XML tidak sesuai: elemen TRANSACTIONS tidak ditemukan.');
        }

        $documents = [];

        foreach ($xml->TRANSACTIONS->RECIEVEITEM as $node) {
            $documents[] = $this->parseDocument($node);
        }

        if ($documents === []) {
            throw new RuntimeException('Tidak ada dokumen RECIEVEITEM di dalam XML.');
        }

        return [
            'exim_id' => $this->string($xml['EximID'] ?? null),
            'branch_code' => $this->string($xml['BranchCode'] ?? null),
            'documents' => $documents,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function parseDocument(\SimpleXMLElement $node): array
    {
        $rate = $this->decimal((string) ($node->RATE ?? '1'), 1.0);
        $currency = abs($rate - 1.0) > 0.000001 ? 'USD' : 'IDR';

        $lines = [];
        $lineNo = 0;

        foreach ($node->ITEMLINE as $lineNode) {
            $lineNo++;

            $quantityXml = $this->decimal((string) ($lineNode->QUANTITY ?? '0'));
            $priceXml = $this->decimal((string) (($lineNode->BRUTOUNITPRICE ?? '') !== ''
                ? $lineNode->BRUTOUNITPRICE
                : ($lineNode->UNITPRICE ?? '0')));
            $ratio = $this->decimal((string) ($lineNode->UNITRATIO ?? ''), 1.0);

            $lines[] = [
                'line_no' => $lineNo,
                'key_id' => $this->string($lineNode->KeyID ?? null),
                'item_internal' => $this->string($lineNode->ITEMNO ?? null),
                'description' => $this->cleanText($lineNode->ITEMOVDESC ?? null),
                'quantity_xml' => $quantityXml,
                'unit_xml' => $this->string($lineNode->ITEMUNIT ?? null),
                'unit_ratio' => $ratio,
                'price_xml' => $priceXml,
                'disc_percent' => $this->decimalOrNull((string) ($lineNode->ITEMDISCPC ?? '')),
                'warehouse' => $this->string($lineNode->WAREHOUSEID ?? null),
            ];
        }

        return [
            'receive_number' => $this->string($node->INVOICENO ?? null),
            'transaction_id' => $this->string($node->TRANSACTIONID ?? null),
            'sequence_no' => $this->string($node->SEQUENCENO ?? null),
            'vendor_internal' => $this->string($node->VENDORID ?? null),
            'trans_date' => $this->date($node->INVOICEDATE ?? null),
            'ship_date' => $this->date($node->SHIPDATE ?? null),
            'tax_date' => $this->date($node->TAXDATE ?? null),
            'rate' => $rate,
            'currency_code' => $currency,
            'taxable' => $this->boolean($node->INVOICEISTAXABLE ?? null),
            'inclusive_tax' => $this->boolean($node->INCLUSIVETAX ?? null),
            'payment_term_name' => $this->string($node->TERMSID ?? null),
            'description' => $this->cleanText($node->DESCRIPTION ?? null),
            'warehouse' => $this->string($node->WAREHOUSEID ?? null),
            'total_amount' => $this->decimal((string) ($node->INVOICEAMOUNT ?? '0')),
            'lines' => $lines,
        ];
    }

    private function string(mixed $value): ?string
    {
        if ($value instanceof \SimpleXMLElement) {
            $value = (string) $value;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function cleanText(mixed $value): ?string
    {
        $value = $this->string($value);
        if ($value === null) {
            return null;
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        // Teks China di file Exim tersimpan sebagai mojibake GBK yang terbaca Latin-1.
        if (preg_match('/[\x{00C0}-\x{00FF}]{2,}/u', $value) !== 1) {
            return $value;
        }

        $bytes = @mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
        if (! is_string($bytes) || $bytes === '') {
            return $value;
        }

        $fixed = @mb_convert_encoding($bytes, 'UTF-8', 'GB18030');
        if (! is_string($fixed) || $fixed === '' || ! mb_check_encoding($fixed, 'UTF-8')) {
            return $value;
        }

        if (str_contains($fixed, "\u{FFFD}")) {
            return $value;
        }

        return trim($fixed);
    }

    private function decimal(string $value, float $default = 0.0): float
    {
        $value = trim(str_replace([' ', ','], ['', '.'], $value));
        if ($value === '' || ! is_numeric($value)) {
            return $default;
        }

        return (float) $value;
    }

    private function decimalOrNull(string $value): ?float
    {
        $value = trim(str_replace([' ', ','], ['', '.'], $value));

        return ($value === '' || ! is_numeric($value)) ? null : (float) $value;
    }

    private function date(mixed $value): ?string
    {
        $value = $this->string($value);
        if ($value === null) {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private function boolean(mixed $value): bool
    {
        $value = $this->string($value);

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y'], true);
    }
}
