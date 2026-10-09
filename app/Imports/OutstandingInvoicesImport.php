<?php

namespace App\Imports;

use App\Models\OutstandingInvoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Import Outstanding Invoices (export Accurate "Outstanding Invoices", .xls).
 *
 * Baris header memuat "Customer No." (biasanya baris ke-6); kolom data sparse
 * dengan pola +2 dari kolom "Customer No.":
 *   c+0 Customer No.   c+2 Customer Name   c+4 Customer Suspended  c+6 Terms Net Days
 *   c+8 Invoice No.    c+10 Invoice Date   c+12 Due Date           c+14 Invoice Amount
 *   c+16 Prime Owing   c+18 Tax Owing      c+20 Age fr Due        c+22 Customer Credit Limit
 *
 * Angka format Eropa ("399996,00"); tanggal "06 Oct 2026"; Age "3536 Day" → integer hari.
 * Upsert pada invoice_no (re-upload = update baris nomor invoice sama, bukan append).
 */
class OutstandingInvoicesImport implements ToCollection, WithChunkReading
{
    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private bool $headerFound = false;
    private ?int $colBase = null;

    /** @var array<int,string> invoice_no yang ADA di file import */
    private array $presentNos = [];

    public function chunkSize(): int
    {
        return 2000;
    }

    public function collection(Collection $rows): void
    {
        $rows = $rows->map(fn ($row) => $this->rowToArray($row))->values();

        $start = 0;
        if (! $this->headerFound) {
            $header = $this->detectHeader($rows);
            if ($header === null) {
                $this->skipped += $rows->count();
                return;
            }
            $this->headerFound = true;
            $this->colBase = $header['col'];
            $start = $header['row'] + 1;
        }

        for ($i = $start; $i < $rows->count(); $i++) {
            $row = $rows[$i] ?? null;
            if (! is_array($row)) {
                $this->skipped++;
                continue;
            }

            $invoiceNo = $this->clean($row[$this->col(8)] ?? null);
            $customerNo = $this->clean($row[$this->col(0)] ?? null);

            if ($invoiceNo === '') {
                $this->skipped++;
                continue;
            }

            if ($this->isFooter($row)) {
                $this->skipped++;
                continue;
            }

            try {
                $data = [
                    'customer_no' => $this->limit($customerNo, 50),
                    'customer_name' => $this->limit($this->clean($row[$this->col(2)] ?? null), 255),
                    'customer_suspended' => $this->limit($this->clean($row[$this->col(4)] ?? null), 50),
                    'terms_net_days' => $this->parseInt($row[$this->col(6)] ?? null),
                    'invoice_date' => $this->parseDate($row[$this->col(10)] ?? null),
                    'due_date' => $this->parseDate($row[$this->col(12)] ?? null),
                    'invoice_amount' => $this->parseAmount($row[$this->col(14)] ?? null),
                    'prime_owing' => $this->parseAmount($row[$this->col(16)] ?? null),
                    'tax_owing' => $this->parseAmount($row[$this->col(18)] ?? null),
                    'age_from_due' => $this->parseAge($row[$this->col(20)] ?? null),
                    'credit_limit' => $this->parseAmount($row[$this->col(22)] ?? null),
                ];

                $model = OutstandingInvoice::updateOrCreate(
                    ['invoice_no' => $this->limit($invoiceNo, 50)],
                    $data
                );

                $model->wasRecentlyCreated ? $this->created++ : $this->updated++;
                $this->presentNos[] = $model->invoice_no;
            } catch (\Illuminate\Database\QueryException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $this->skipped++;
                Log::warning('OutstandingInvoicesImport: baris dilewati', [
                    'invoice_no' => $invoiceNo,
                    'row' => $i,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function col(int $offset): int
    {
        return ($this->colBase ?? 0) + $offset;
    }

    /**
     * @return array{row:int,col:int}|null
     */
    private function detectHeader(Collection $rows): ?array
    {
        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            foreach ($row as $col => $cell) {
                $normalized = strtolower(trim((string) $cell));
                if ($normalized === 'customer no.' || $normalized === 'customer no') {
                    return ['row' => $index, 'col' => $col];
                }
            }
        }

        return null;
    }

    private function isFooter(array $row): bool
    {
        foreach ($row as $cell) {
            $v = strtolower(trim((string) $cell));
            if ($v === '') {
                continue;
            }
            if (str_contains($v, 'accurate accounting')
                || str_starts_with($v, 'printed on')
                || str_starts_with($v, 'page ')) {
                return true;
            }
        }

        return false;
    }

    private function rowToArray(mixed $row): array
    {
        if ($row instanceof Collection) {
            $row = $row->all();
        } elseif (is_object($row) && method_exists($row, 'toArray')) {
            $row = $row->toArray();
        } elseif (! is_array($row)) {
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

        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }

    private function limit(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    private function parseInt(mixed $value): ?int
    {
        $raw = $this->clean($value);
        if ($raw === '') {
            return null;
        }

        return preg_match('/-?\d+/', $raw, $m) ? (int) $m[0] : null;
    }

    private function parseAge(mixed $value): ?int
    {
        return $this->parseInt($value);
    }

    private function parseDate(mixed $value): ?string
    {
        $raw = $this->clean($value);
        if ($raw === '') {
            return null;
        }

        foreach (['d M Y', 'd M y'] as $format) {
            $parsed = \DateTime::createFromFormat($format, $raw);
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        $ts = strtotime($raw);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    private function parseAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return 0.0;
        }

        $s = str_replace([' ', "\xc2\xa0"], '', trim($value));

        if (str_contains($s, ',') && str_contains($s, '.')) {
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    /**
     * @return array<int,string>
     */
    public function getPresentInvoiceNos(): array
    {
        return array_values(array_unique($this->presentNos));
    }
}
