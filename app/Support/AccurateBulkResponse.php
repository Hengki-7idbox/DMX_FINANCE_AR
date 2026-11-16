<?php

namespace App\Support;

/**
 * Pemetaan respons endpoint bulk-save Accurate.
 *
 * Bentuk resmi: array per dokumen `[{ "s": true, "d": {...} }, { "s": false, "d": ["pesan"] }]`.
 * Beberapa akun membungkusnya di `d`/`r`/`data`, jadi pembacaan dibuat toleran.
 */
class AccurateBulkResponse
{
    /**
     * Ambil daftar baris hasil per dokumen dari body respons.
     *
     * @return array<int,array>|null null bila bentuk respons tidak dikenali
     */
    public static function rows(array $body): ?array
    {
        if (self::isRowList($body)) {
            return array_values($body);
        }

        foreach (['d', 'r', 'data'] as $key) {
            $candidate = $body[$key] ?? null;

            if (is_array($candidate) && self::isRowList($candidate)) {
                return array_values($candidate);
            }
        }

        // Satu dokumen: respons bisa berupa objek tunggal `{s:true,d:{...}}`.
        if (array_key_exists('s', $body)) {
            return [$body];
        }

        return null;
    }

    /**
     * Petakan baris hasil ke tiap payload sesuai urutan kirim.
     *
     * @param  array<int,array>  $rows
     * @return array<int,array{ok:bool,status:int,doc:array,body:array,message:?string}>
     */
    public static function results(array $rows, int $count, int $status): array
    {
        $rows = array_values($rows);
        $results = [];

        for ($i = 0; $i < $count; $i++) {
            $row = $rows[$i] ?? null;

            if (! is_array($row)) {
                $results[$i] = [
                    'ok' => false,
                    'status' => $status,
                    'doc' => [],
                    'body' => [],
                    'message' => 'Dokumen tidak ada di respons bulk Accurate.',
                ];

                continue;
            }

            $ok = ($row['s'] ?? false) === true;
            $d = $row['d'] ?? [];
            $r = $row['r'] ?? [];

            // Dokumen tersimpan ada di `d` (sebagian akun) atau `r` (bentuk resmi
            // bulk-save: `{s:true, d:"pesan", r:{...dokumen...}}`).
            $document = [];
            if (is_array($d) && ! array_is_list($d)) {
                $document = $d;
            } elseif (is_array($r) && ! array_is_list($r)) {
                $document = $r;
            }

            $results[$i] = [
                'ok' => $ok,
                'status' => $status,
                'doc' => $document,
                'body' => $row,
                'message' => $ok ? null : self::message($d),
            ];
        }

        return $results;
    }

    /**
     * Ratakan pesan error dari `d` (string, list, atau objek).
     */
    public static function message(mixed $detail): string
    {
        if (is_string($detail)) {
            $detail = trim($detail);

            return $detail !== '' ? $detail : 'Accurate menolak pembuatan dokumen.';
        }

        if (is_array($detail)) {
            $parts = [];
            array_walk_recursive($detail, function ($value) use (&$parts) {
                if (is_scalar($value) && trim((string) $value) !== '') {
                    $parts[] = trim((string) $value);
                }
            });

            if ($parts !== []) {
                return implode(' ', $parts);
            }
        }

        return 'Accurate menolak pembuatan dokumen.';
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function isRowList(array $value): bool
    {
        if ($value === [] || ! array_is_list($value)) {
            return false;
        }

        return is_array($value[0] ?? null) && array_key_exists('s', $value[0]);
    }
}
