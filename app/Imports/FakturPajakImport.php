<?php

namespace App\Imports;

use App\Models\FakturPajak;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Import Faktur Pajak dari CSV export e-Faktur (delimiter koma, UTF-8).
 * Header baris 1; upsert pada (tax_invoice_number).
 * Kolom `status` TIDAK disentuh oleh import (diisi manual).
 */
class FakturPajakImport implements ToCollection
{
    private const HEADER_MAP = [
        'buyer tin' => 'buyer_tin',
        'display name' => 'display_name',
        'tax invoice code' => 'tax_invoice_code',
        'tax invoice number' => 'tax_invoice_number',
        'tax invoice date' => 'tax_invoice_date',
        'tax invoice period' => 'tax_invoice_period',
        'tax invoice year' => 'tax_invoice_year',
        'tax invoice status' => 'tax_invoice_status',
        'e sign status' => 'e_sign_status',
        'selling price' => 'selling_price',
        'other tax base' => 'other_tax_base',
        'vat' => 'vat',
        'signer' => 'signer',
        'reference' => 'reference',
        'reported by seller' => 'reported_by_seller',
        'reported by vat collector' => 'reported_by_vat_collector',
    ];

    private const COMPARE_FIELDS = [
        'buyer_tin', 'display_name', 'tax_invoice_code', 'tax_invoice_date',
        'tax_invoice_period', 'tax_invoice_year', 'tax_invoice_status',
        'e_sign_status', 'selling_price', 'other_tax_base', 'vat',
        'signer', 'reference', 'reported_by_seller', 'reported_by_vat_collector',
    ];

    private int $created = 0;
    private int $updated = 0;
    private int $unchanged = 0;
    private int $skipped = 0;

    public function collection(Collection $rows): void
    {
        $rows = $rows->map(fn ($row) => $this->rowToArray($row))->values();

        [$headerIndex, $colMap] = $this->detectHeader($rows);
        if ($headerIndex === null) {
            $this->skipped += $rows->count();
            return;
        }

        for ($i = $headerIndex + 1; $i < $rows->count(); $i++) {
            $row = $rows[$i];
            if (!is_array($row)) {
                $this->skipped++;
                continue;
            }

            $number = $this->clean($this->cell($row, $colMap, 'tax_invoice_number'));
            if ($number === '') {
                $this->skipped++;
                continue;
            }

            $payload = [
                'buyer_tin' => $this->limit($this->clean($this->cell($row, $colMap, 'buyer_tin')), 20),
                'display_name' => $this->limit($this->clean($this->cell($row, $colMap, 'display_name')), 255),
                'tax_invoice_code' => $this->limit($this->clean($this->cell($row, $colMap, 'tax_invoice_code')), 255),
                'tax_invoice_date' => $this->parseDate($this->cell($row, $colMap, 'tax_invoice_date')),
                'tax_invoice_period' => $this->limit($this->clean($this->cell($row, $colMap, 'tax_invoice_period')), 20),
                'tax_invoice_year' => $this->parseYear($this->cell($row, $colMap, 'tax_invoice_year')),
                'tax_invoice_status' => $this->limit($this->clean($this->cell($row, $colMap, 'tax_invoice_status')), 50),
                'e_sign_status' => $this->limit($this->clean($this->cell($row, $colMap, 'e_sign_status')), 50),
                'selling_price' => $this->parseAmount($this->cell($row, $colMap, 'selling_price')),
                'other_tax_base' => $this->parseAmount($this->cell($row, $colMap, 'other_tax_base')),
                'vat' => $this->parseAmount($this->cell($row, $colMap, 'vat')),
                'signer' => $this->limit($this->clean($this->cell($row, $colMap, 'signer')), 100),
                'reference' => $this->limit($this->clean($this->cell($row, $colMap, 'reference')), 50),
                'reported_by_seller' => $this->parseBool($this->cell($row, $colMap, 'reported_by_seller')) ?? false,
                'reported_by_vat_collector' => $this->parseBool($this->cell($row, $colMap, 'reported_by_vat_collector')),
            ];

            $this->upsert($number, $payload);
        }
    }

    private function upsert(string $number, array $payload): void
    {
        $existing = FakturPajak::where('tax_invoice_number', $number)->first();

        if ($existing === null) {
            FakturPajak::create($payload + ['tax_invoice_number' => $number]);
            $this->created++;
            return;
        }

        $dirty = false;
        foreach (self::COMPARE_FIELDS as $field) {
            if ($this->valuesDiffer($existing->getAttribute($field), $payload[$field], $field)) {
                $dirty = true;
                break;
            }
        }

        if ($dirty) {
            $existing->fill($payload);
            $existing->save();
            $this->updated++;
        } else {
            $this->unchanged++;
        }
    }

    private function detectHeader(Collection $rows): array
    {
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $colMap = [];
            foreach ($row as $col => $cell) {
                $normalized = strtolower(trim((string) $cell));
                if (isset(self::HEADER_MAP[$normalized])) {
                    $colMap[self::HEADER_MAP[$normalized]] = $col;
                }
            }

            if (isset($colMap['tax_invoice_number'])) {
                return [$index, $colMap];
            }
        }

        return [null, []];
    }

    private function cell(array $row, array $colMap, string $key): mixed
    {
        $col = $colMap[$key] ?? null;

        return $col === null ? null : ($row[$col] ?? null);
    }

    private function valuesDiffer(mixed $old, mixed $new, string $field): bool
    {
        if ($field === 'tax_invoice_date') {
            $oldDate = $old instanceof \DateTimeInterface ? $old->format('Y-m-d') : $old;

            return ($oldDate ?? null) !== ($new ?? null);
        }

        if (in_array($field, ['selling_price', 'other_tax_base', 'vat'], true)) {
            return (float) ($old ?? 0) !== (float) ($new ?? 0);
        }

        if ($field === 'tax_invoice_year') {
            return (int) ($old ?? 0) !== (int) ($new ?? 0);
        }

        if (in_array($field, ['reported_by_seller', 'reported_by_vat_collector'], true)) {
            $o = $old === null ? null : (bool) $old;
            $n = $new === null ? null : (bool) $new;

            return $o !== $n;
        }

        return trim((string) ($old ?? '')) !== trim((string) ($new ?? ''));
    }

    private function rowToArray(mixed $row): array
    {
        if ($row instanceof Collection) {
            $row = $row->all();
        } elseif (is_object($row) && method_exists($row, 'toArray')) {
            $row = $row->toArray();
        } elseif (!is_array($row)) {
            return [];
        }

        return array_values(array_map(function ($cell) {
            if ($cell === null) {
                return null;
            }

            if (is_object($cell)) {
                if (method_exists($cell, 'getPlainText')) {
                    return $cell->getPlainText();
                }

                if (method_exists($cell, '__toString')) {
                    return (string) $cell;
                }

                return null;
            }

            return $cell;
        }, $row));
    }

    private function clean(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_numeric($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return '';
        }

        return trim($value);
    }

    private function limit(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    private function parseDate(mixed $value): ?string
    {
        $raw = $this->clean($value);
        if ($raw === '') {
            return null;
        }

        $parsed = \DateTime::createFromFormat('d/m/Y', $raw);
        if ($parsed !== false) {
            return $parsed->format('Y-m-d');
        }

        foreach (['d/m/y', 'Y-m-d', 'd M Y'] as $format) {
            $parsed = \DateTime::createFromFormat($format, $raw);
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        $timestamp = strtotime($raw);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private function parseYear(mixed $value): ?int
    {
        $raw = $this->clean($value);

        return $raw === '' ? null : (int) $raw;
    }

    private function parseAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', "\xa0"], '', trim((string) $value));

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function parseBool(mixed $value): ?bool
    {
        $raw = $this->clean($value);
        if ($raw === '') {
            return null;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getUnchangedCount(): int
    {
        return $this->unchanged;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }
}
