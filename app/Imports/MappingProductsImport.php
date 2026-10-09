<?php

namespace App\Imports;

use App\Models\MappingProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Import Mapping Product (Sample/item_mapping.xlsx).
 *
 * Header baris 1: ID | Item Code Internal | Item Code external | Code Coretax | Nama Barang Internal | Nama Barang external
 * Data mulai baris 2. Upsert berdasarkan (kode_item_internal + kode_item_external).
 *
 * Index kolom (0-based): 0=ID, 1=Item Code Internal, 2=Item Code external, 3=Code Coretax, 4=Nama Barang Internal, 5=Nama Barang external
 */
class MappingProductsImport implements ToCollection
{
    private const COL_ID = 0;
    private const COL_KODE_INTERNAL = 1;
    private const COL_KODE_EXTERNAL = 2;
    private const COL_CORETAX = 3;
    private const COL_NAMA_INTERNAL = 4;
    private const COL_NAMA_EXTERNAL = 5;

    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;

    public function collection(Collection $rows): void
    {
        $rows = $rows->map(fn ($row) => $this->rowToArray($row))->values();

        $headerIndex = $this->detectHeader($rows);
        if ($headerIndex === null) {
            $this->skipped += $rows->count();
            return;
        }

        for ($i = $headerIndex + 1; $i < $rows->count(); $i++) {
            $row = $rows[$i] ?? null;
            if (!is_array($row)) {
                continue;
            }

            $kodeInternal = $this->clean($row[self::COL_KODE_INTERNAL] ?? null);
            $kodeExternal = $this->clean($row[self::COL_KODE_EXTERNAL] ?? null);
            $coretax = $this->clean($row[self::COL_CORETAX] ?? null);
            $namaInternal = $this->clean($row[self::COL_NAMA_INTERNAL] ?? null);
            $namaExternal = $this->clean($row[self::COL_NAMA_EXTERNAL] ?? null);

            if ($kodeInternal === '' || $kodeExternal === '') {
                $this->skipped++;
                continue;
            }

            $sourceId = $row[self::COL_ID] ?? null;
            $sourceId = is_numeric($sourceId) ? (int) $sourceId : null;

            $payload = [
                'source_id' => $sourceId,
                'kode_item_internal' => $this->limit($kodeInternal, 100),
                'kode_item_external' => $this->limit($kodeExternal, 100),
                'code_coretax' => $coretax !== '' ? $this->limit($coretax, 100) : null,
                'nama_item_internal' => $this->limit($namaInternal !== '' ? $namaInternal : $kodeInternal, 255),
                'nama_item_external' => $this->limit($namaExternal !== '' ? $namaExternal : $kodeExternal, 255),
            ];

            $this->upsert($payload);
        }
    }

    private function detectHeader(Collection $rows): ?int
    {
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = [];
            foreach ($row as $cell) {
                if ($cell === null) {
                    continue;
                }
                $normalized[] = strtolower(trim((string) $cell));
            }

            if (in_array('item code internal', $normalized, true)
                && in_array('item code external', $normalized, true)) {
                return $index;
            }
        }

        return null;
    }

    private function upsert(array $payload): void
    {
        $existing = MappingProduct::where('kode_item_internal', $payload['kode_item_internal'])
            ->where('kode_item_external', $payload['kode_item_external'])
            ->first();

        if ($existing) {
            $existing->fill($payload);
            $existing->save();
            $this->updated++;
        } else {
            MappingProduct::create($payload);
            $this->created++;
        }
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
