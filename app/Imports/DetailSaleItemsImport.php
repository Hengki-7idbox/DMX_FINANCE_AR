<?php

namespace App\Imports;

use App\Models\DetailSaleItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Import Detail Product Sales (per item) dari export Accurate Online
 * (Sample/Detail Sales Item Produk.xls — "Detail Sales Per Item").
 *
 * Header baris 6 (0-based): 0=A Customer No., 3=D Name, 5=F Invoice No.,
 * 7=H Date, 9=J Item No., 11=L Description, 13=N Unit Price, 15=P Qty,
 * 17=R Unit, 19=T Amount, 21=V Discount.
 *
 * Amount format Eropa "38078223,00"; tanggal "01/09/26".
 * Re-import MENGGANTI item dokumen yang sama (hapus item lama doc itu, lalu insert
 * baris dari file) — tidak menumpuk. Tetap mendukung banyak baris identik dalam 1 invoice.
 * Data di-tag source_type tab aktif; link ke detail_sales via doc_number.
 *
 * WithChunkReading: file dibaca per 500 baris agar memori stabil saat
 * file item (baris jauh lebih banyak) di-import.
 */
class DetailSaleItemsImport implements ToCollection, WithChunkReading
{
    private const COL_CODE = 0;
    private const COL_NAME = 3;
    private const COL_INVOICE = 5;
    private const COL_DATE = 7;
    private const COL_ITEM_NO = 9;
    private const COL_DESC = 11;
    private const COL_PRICE = 13;
    private const COL_QTY = 15;
    private const COL_UNIT = 17;
    private const COL_AMOUNT = 19;
    private const COL_DISCOUNT = 21;

    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private bool $headerFound = false;

    /** @var array<string,bool> dokumen yang sudah dibersihkan (dihapus item lamanya) di import ini */
    private array $clearedDocs = [];

    public function chunkSize(): int
    {
        // 2000 baris per chunk — memori stabil walau file item sangat panjang.
        return 2000;
    }

    public function __construct(private readonly string $sourceType = DetailSaleItem::SOURCE_EXTERNAL)
    {
        if (!in_array($this->sourceType, [DetailSaleItem::SOURCE_INTERNAL, DetailSaleItem::SOURCE_EXTERNAL], true)) {
            $this->sourceType = DetailSaleItem::SOURCE_EXTERNAL;
        }
    }

    public function collection(Collection $rows): void
    {
        $rows = $rows->map(fn ($row) => $this->rowToArray($row))->values();

        // Chunk-aware: deteksi header hanya sampai ketemu sekali.
        $headerIndex = $this->headerFound ? null : $this->detectHeader($rows);

        if ($headerIndex !== null) {
            $this->headerFound = true;
        } elseif (!$this->headerFound) {
            $this->skipped += $rows->count();
            return;
        }

        $start = $headerIndex !== null ? $headerIndex + 1 : 0;

        for ($i = $start; $i < $rows->count(); $i++) {
            $row = $rows[$i] ?? null;
            if (!is_array($row)) {
                $this->skipped++;
                continue;
            }

            $code = $this->clean($row[self::COL_CODE] ?? null);
            $invoice = $this->clean($row[self::COL_INVOICE] ?? null);

            $isHeaderRepeat = in_array(strtolower($code), ['customer no.', 'customer no'], true)
                || strtolower($invoice) === 'invoice no.';

            if ($isHeaderRepeat) {
                continue;
            }

            $isFooter = str_contains(strtolower($code), 'accurate accounting')
                || str_starts_with(strtolower($code), 'printed on')
                || str_starts_with(strtolower($code), 'page ');

            if ($isFooter || ($code === '' && $invoice === '')) {
                $this->skipped++;
                continue;
            }

            if ($invoice === '') {
                $this->skipped++;
                continue;
            }

            $docKey = $this->limit($invoice, 50);

            // Re-import = GANTI (bukan tumpuk): hapus item lama dokumen ini satu kali.
            // Chunk-safe: ditandai agar dokumen lintas-chunk tidak dihapus berulang.
            if (! isset($this->clearedDocs[$docKey])) {
                $this->clearedDocs[$docKey] = true;
                DetailSaleItem::where('source_type', $this->sourceType)
                    ->where('doc_number', $docKey)
                    ->delete();
            }

            DetailSaleItem::create([
                'source_type' => $this->sourceType,
                'doc_number' => $docKey,
                'customer_code' => $this->limit($code, 50),
                'customer_name' => $this->limit($this->clean($row[self::COL_NAME] ?? null), 255),
                'transaction_date' => $this->parseDate($row[self::COL_DATE] ?? null),
                'item_no' => $this->limit($this->clean($row[self::COL_ITEM_NO] ?? null), 100),
                'item_description' => $this->limit($this->clean($row[self::COL_DESC] ?? null), 500),
                'unit_price' => $this->parseAmount($row[self::COL_PRICE] ?? null),
                'qty' => $this->parseAmount($row[self::COL_QTY] ?? null),
                'unit' => $this->limit($this->clean($row[self::COL_UNIT] ?? null), 50),
                'amount' => $this->parseAmount($row[self::COL_AMOUNT] ?? null),
                'discount' => $this->parseAmount($row[self::COL_DISCOUNT] ?? null),
            ]);

            $this->created++;
        }
    }

    private function detectHeader(Collection $rows): ?int
    {
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($row as $cell) {
                if ($cell === null) {
                    continue;
                }

                $normalized = strtolower(trim((string) $cell));
                if (in_array($normalized, ['customer no.', 'customer no'], true)) {
                    return $index;
                }
            }
        }

        return null;
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

        foreach (['d/m/y', 'd/m/Y', 'd M Y', 'd M y'] as $format) {
            $parsed = \DateTime::createFromFormat($format, $raw);
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
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
            if (preg_match('/,\d{1,2}$/', $normalized)) {
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

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }
}
