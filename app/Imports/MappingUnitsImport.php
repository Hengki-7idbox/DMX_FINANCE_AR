<?php

namespace App\Imports;

use App\Models\MappingUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Import Mapping Unit.
 *
 * Header (opsional) baris pertama: Unit Internal | Unit Coretax.
 * Bila header tidak terdeteksi, kolom dibaca posisional (0=Unit Internal, 1=Unit Coretax).
 * Upsert berdasarkan unit_internal.
 */
class MappingUnitsImport implements ToCollection
{
    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;

    public function collection(Collection $rows): void
    {
        $rows = $rows->map(fn ($row) => $this->rowToArray($row))->values();

        $start = 0;
        $headerIndex = $this->detectHeader($rows);
        if ($headerIndex !== null) {
            $start = $headerIndex + 1;
        }

        for ($i = $start; $i < $rows->count(); $i++) {
            $row = $rows[$i] ?? null;
            if (!is_array($row)) {
                continue;
            }

            $internal = $this->clean($row[0] ?? null);
            $coretax = $this->clean($row[1] ?? null);

            if ($internal === '' || $coretax === '') {
                $this->skipped++;
                continue;
            }

            $this->upsert($this->limit($internal, 50), $this->limit($coretax, 50));
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

            if (in_array('unit internal', $normalized, true)
                || in_array('unit coretax', $normalized, true)
                || in_array("nama satuan ukur", $normalized, true)) {
                return $index;
            }
        }

        return null;
    }

    private function upsert(string $internal, string $coretax): void
    {
        $existing = MappingUnit::where('unit_internal', $internal)->first();

        if ($existing) {
            $existing->unit_coretax = $coretax;
            $existing->save();
            $this->updated++;
        } else {
            MappingUnit::create(['unit_internal' => $internal, 'unit_coretax' => $coretax]);
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
