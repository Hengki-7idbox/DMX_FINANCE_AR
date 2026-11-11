<?php

namespace App\Support;

/**
 * Parser tanggal untuk file export Accurate (locale Indonesia).
 *
 * Aturan utama: tanggal angka berslash SELALU dibaca d/m/Y, bukan m/d/Y.
 * strtotime() tidak dipakai untuk pola angka karena PHP membacanya sebagai
 * m/d/Y ("12/10/2026" menjadi 10 Desember, padahal maksudnya 12 Oktober).
 */
class DateParser
{
    private const MONTHS_ID_TO_EN = [
        'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
        'Mei' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul',
        'Agu' => 'Aug', 'Agt' => 'Aug', 'Sep' => 'Sep',
        'Okt' => 'Oct', 'Nov' => 'Nov', 'Des' => 'Dec',
    ];

    public static function parseString(string $raw): ?string
    {
        // Apostrophe = prefix text-format di file .xls.
        $raw = ltrim(trim($raw), "'");
        if ($raw === '') {
            return null;
        }

        // Cell tanggal asli Excel (serial number, mis. 46300).
        if (is_numeric($raw)) {
            $serial = (float) $raw;
            if ($serial > 1 && $serial < 100000) {
                try {
                    return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($serial)->format('Y-m-d');
                } catch (\Throwable) {
                    // bukan serial Excel -> lanjut ke format berikutnya
                }
            }
        }

        // "02 Des 2025" -> "02 Dec 2025".
        $normalized = preg_replace_callback(
            '/\b(Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Agt|Sep|Okt|Nov|Des)\b/iu',
            fn ($m) => self::MONTHS_ID_TO_EN[ucfirst(strtolower($m[1]))] ?? $m[1],
            $raw
        ) ?? $raw;

        // Tanggal angka: utamakan d/m/Y.
        if (preg_match('#^\s*(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{2,4})(?:[ T].*)?$#', $normalized, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            $year = strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3];

            // Komponen pertama > 12 -> pasti tanggal; kedua > 12 -> pasti bulan.
            $firstIsDay = $a > 12 || $b <= 12;
            $day = $firstIsDay ? $a : $b;
            $month = $firstIsDay ? $b : $a;

            return checkdate($month, $day, $year)
                ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                : null;
        }

        foreach (['d M Y', 'd M y', 'Y-m-d', 'Y-m-d H:i:s', 'Y/m/d'] as $format) {
            $parsed = \DateTime::createFromFormat('!' . $format, $normalized);
            $errors = \DateTime::getLastErrors();
            if ($parsed !== false
                && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $parsed->format('Y-m-d');
            }
        }

        // Fallback terakhir: format non-ambigu (mis. "2026-10-12T00:00:00").
        $timestamp = strtotime($normalized);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}
