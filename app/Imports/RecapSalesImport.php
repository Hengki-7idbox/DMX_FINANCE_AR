<?php

namespace App\Imports;

use App\Models\ImportSessionDetail;
use App\Models\RecapSale;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Import Recap Sales dari export Accurate Online.
 * (Sample/Recap Sales.xls — "Sales By Customer").
 *
 * Format ringkas (bukan detail invoice):
 * R3: "From MM/DD/YY to MM/DD/YY" → period
 * R4/R5: header sparse — Customer No. | Customer Name | label periode (total)
 * R6+: 1 baris per customer, amount sudah digabung
 * Footer: grand total tanpa kode, "ACCURATE", "Printed on", "Page X of Y" → skip
 *
 * Deteksi otomatis posisi kolom dari header "Customer No.".
 * Pola spacing antar kolom diasumsikan konsisten (code+2 untuk nama, code+4 untuk amount).
 */
class RecapSalesImport implements ToCollection
{
    private int $colCode = 1;
    private int $colName = 3;
    private int $colAmount = 5;

    private int $created = 0;
    private int $updated = 0;
    private int $unchanged = 0;
    private int $skipped = 0;

    public function __construct(
        private readonly string $sourceType = RecapSale::SOURCE_EXTERNAL,
        private readonly ?int $sessionId = null,
    ) {
        if (!in_array($this->sourceType, [RecapSale::SOURCE_INTERNAL, RecapSale::SOURCE_EXTERNAL], true)) {
            $this->sourceType = RecapSale::SOURCE_EXTERNAL;
        }
    }

    public function collection(Collection $rows): void
    {
        $rows = $rows->map(fn ($row) => $this->rowToArray($row))->values();

        $headerResult = $this->detectHeader($rows);
        if ($headerResult === null) {
            $this->skipped += $rows->count();
            return;
        }
        $headerIndex = $headerResult['row'];
        $this->colCode = $headerResult['col'];
        $this->colName = $this->colCode + 2;
        $this->colAmount = $this->colCode + 4;

        $period = $this->detectPeriod($rows);

        for ($i = $headerIndex + 1; $i < $rows->count(); $i++) {
            $row = $rows[$i] ?? null;
            if (!is_array($row)) {
                continue;
            }

            $code = $this->clean($row[$this->colCode] ?? null);
            $name = $this->clean($row[$this->colName] ?? null);

            if ($code === '' || $name === '') {
                $this->skipped++;
                continue;
            }

            if ($this->isFooterRow($code, $name)) {
                $this->skipped++;
                continue;
            }

            $amount = $this->parseAmount($row[$this->colAmount] ?? null);

            $this->upsert($period, [
                'customer_code' => $code,
                'customer_name' => $name,
                'total_sales' => $amount,
                'varian' => $this->clean($row[7] ?? null),
            ]);
        }
    }

    /**
     * Detect header row and return ['row' => headerRowIndex, 'col' => customerNoColumnIndex].
     */
    private function detectHeader(Collection $rows): ?array
    {
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($row as $colIdx => $cell) {
                if ($cell === null) {
                    continue;
                }

                $normalized = strtolower(trim((string) $cell));
                if (in_array($normalized, ['customer no.', 'customer no'], true)) {
                    return ['row' => $index, 'col' => $colIdx];
                }
            }
        }

        return null;
    }

    private function detectPeriod(Collection $rows): string
    {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($row as $cell) {
                if (!is_string($cell)) {
                    continue;
                }

                if (preg_match('/^From\s+(.+?)\s+to\s+(.+)$/i', trim($cell), $m)) {
                    $from = $this->normalizeDate($m[1]);
                    $to = $this->normalizeDate($m[2]);
                    if ($from !== '' && $to !== '') {
                        return $from . ' - ' . $to;
                    }
                }
            }
        }

        return now()->format('Y-m');
    }

    private function isFooterRow(string $code, string $name): bool
    {
        $haystack = strtolower($code . ' ' . $name);

        return str_contains($haystack, 'accurate')
            || str_contains($haystack, 'printed on')
            || str_contains($haystack, 'page ')
            || str_contains($haystack, 'accounting system');
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);

        // Accurate: 09/01/26 → prefer explicit M/d/y
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $value, $m)) {
            $month = (int) $m[1];
            $day = (int) $m[2];
            $year = (int) $m[3];
            if ($year < 100) {
                $year += 2000;
            }
            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }

    private function upsert(string $period, array $payload): void
    {
        $code = $this->limit($payload['customer_code'], 50);

        $existing = RecapSale::where('source_type', $this->sourceType)
            ->where('period', $period)
            ->where('customer_code', $code)
            ->first();

        $data = [
            'customer_name' => $this->limit($payload['customer_name'], 255),
            'total_sales' => round($payload['total_sales'], 2),
            'varian' => $payload['varian'] !== '' ? $this->limit($payload['varian'], 100) : null,
        ];

        $detailNew = [
            'customer_code' => $code,
            'customer_name' => $data['customer_name'],
            'total_sales' => $data['total_sales'],
        ];

        if ($existing) {
            $oldValues = [];
            if ((float) $existing->total_sales !== (float) $data['total_sales']) {
                $oldValues['total_sales'] = (float) $existing->total_sales;
            }
            if (trim((string) $existing->customer_name) !== trim((string) $data['customer_name'])) {
                $oldValues['customer_name'] = (string) $existing->customer_name;
            }
            if ((string) ($existing->varian ?? '') !== (string) ($data['varian'] ?? '')) {
                $oldValues['varian'] = (string) $existing->varian;
            }

            if ($oldValues !== []) {
                $existing->fill($data);
                $existing->save();
                $this->updated++;
                $this->logDetail($code, ImportSessionDetail::TYPE_CHANGED, $oldValues, $detailNew);
            } else {
                $this->unchanged++;
                $this->logDetail($code, ImportSessionDetail::TYPE_UNCHANGED, null, $detailNew);
            }

            return;
        }

        RecapSale::create($data + [
            'source_type' => $this->sourceType,
            'period' => $period,
            'customer_code' => $code,
        ]);
        $this->created++;
        $this->logDetail($code, ImportSessionDetail::TYPE_NEW, null, $detailNew);
    }

    private function logDetail(string $code, string $type, ?array $oldValues, array $newValues): void
    {
        if ($this->sessionId === null) {
            return;
        }

        ImportSessionDetail::create([
            'session_id' => $this->sessionId,
            'doc_number' => $this->limit($code, 50),
            'change_type' => $type,
            'transaction_date' => null,
            'customer_name' => $newValues['customer_name'] ?? null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
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

    private function parseAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        $normalized = str_replace([' ', "\xa0"], '', trim($value));

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            if (preg_match('/,-?\d{1,2}$/', $normalized) || preg_match('/^-,?\d/', $normalized)) {
                $normalized = str_replace(',', '.', $normalized);
            } elseif (preg_match('/,\d{1,2}$/', $normalized)) {
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        }

        if (preg_match('/^-(\d+),(\d+)$/', $normalized, $m)) {
            return -(float) ($m[1] . '.' . $m[2]);
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getChangedCount(): int
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
