<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class SpreadsheetSheetReader
{
    /**
     * Baca satu sheet berdasarkan nama (fallback sheet pertama), deteksi baris header
     * dari daftar header yang diwajibkan, lalu kembalikan baris sebagai map header => nilai.
     *
     * @param  array<int,string>  $sheetNames
     * @param  array<int,string>  $requiredHeaders
     * @return array<int,array<string,mixed>>
     */
    public static function rows(string $path, array $sheetNames, array $requiredHeaders): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = null;

        foreach ($sheetNames as $name) {
            $candidate = $spreadsheet->getSheetByName($name);
            if ($candidate !== null) {
                $sheet = $candidate;
                break;
            }
        }

        if ($sheet === null) {
            $sheet = $spreadsheet->getSheet(0);
        }

        $data = $sheet->toArray(null, true, true, false);
        $required = array_map(fn ($h) => strtoupper(trim((string) $h)), $requiredHeaders);

        $headerRow = null;
        $headers = [];
        foreach ($data as $index => $row) {
            $candidate = [];
            foreach ($row as $col => $value) {
                $header = strtoupper(trim((string) $value));
                if ($header !== '') {
                    $candidate[$col] = $header;
                }
            }

            $found = 0;
            foreach ($required as $needle) {
                if (in_array($needle, $candidate, true)) {
                    $found++;
                }
            }

            if ($found === count($required)) {
                $headerRow = $index;
                $headers = $candidate;
                break;
            }
        }

        if ($headerRow === null) {
            throw new RuntimeException('Header tidak ditemukan: ' . implode(', ', $requiredHeaders) . '.');
        }

        $rows = [];
        foreach ($data as $index => $row) {
            if ($index <= $headerRow) {
                continue;
            }

            $mapped = [];
            $empty = true;
            foreach ($headers as $col => $header) {
                $value = $row[$col] ?? null;
                if ($value !== null && trim((string) $value) !== '') {
                    $empty = false;
                }
                $mapped[$header] = $value;
            }

            if (! $empty) {
                $rows[] = $mapped;
            }
        }

        return $rows;
    }
}
