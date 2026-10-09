<?php

namespace App\Imports;

use App\Models\MappingCustomer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Import Mapping Customer (Sample/Mapping Customer.xlsx).
 *
 * Header baris 1: ID | Kode Internal | Kode External | Nama Internal | Nama External
 * Data mulai baris 2. Upsert berdasarkan (kode_internal + kode_external).
 *
 * Index kolom (0-based): 0=ID, 1=Kode Internal, 2=Kode External, 3=Nama Internal, 4=Nama External
 */
class MappingCustomersImport implements ToCollection
{
    private const COL_ID = 0;
    private const COL_KODE_INTERNAL = 1;
    private const COL_KODE_EXTERNAL = 2;
    private const COL_NAMA_INTERNAL = 3;
    private const COL_NAMA_EXTERNAL = 4;

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
                'kode_internal' => $this->limit($kodeInternal, 100),
                'kode_external' => $this->limit($kodeExternal, 100),
                'nama_internal' => $this->limit($namaInternal !== '' ? $namaInternal : $kodeInternal, 255),
                'nama_external' => $this->limit($namaExternal !== '' ? $namaExternal : $kodeExternal, 255),
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

            if (in_array('kode internal', $normalized, true) && in_array('kode external', $normalized, true)) {
                return $index;
            }
        }

        return null;
    }

    private function upsert(array $payload): void
    {
        $existing = MappingCustomer::where('kode_internal', $payload['kode_internal'])
            ->where('kode_external', $payload['kode_external'])
            ->first();

        if ($existing) {
            $existing->fill($payload);
            $existing->save();
            $this->updated++;
        } else {
            MappingCustomer::create($payload);
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
