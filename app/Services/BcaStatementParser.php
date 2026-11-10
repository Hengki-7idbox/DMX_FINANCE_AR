<?php

namespace App\Services;

use Carbon\Carbon;
use RuntimeException;

/**
 * Parser rekening koran BCA (format CSV hasil download internet banking).
 *
 * Format yang didukung:
 * - metadata 6 baris (No. rekening, Nama, Periode, Kode Mata Uang)
 * - delimiter titik koma (;)
 * - header: Tanggal Transaksi;Keterangan;Cabang;Jumlah;Saldo
 * - Jumlah berakhiran CR/DB, contoh: 680,000.00 CR
 * - footer: Saldo Awal, Mutasi Debet, Mutasi Kredit, Saldo Akhir
 */
class BcaStatementParser
{
    public function parse(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('File rekening koran tidak ditemukan atau tidak bisa dibaca.');
        }

        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);

        $meta = [
            'account_number' => null,
            'account_name' => null,
            'period_start' => null,
            'period_end' => null,
            'currency' => 'IDR',
        ];

        $rows = [];
        $footer = [];
        $errors = [];
        $warnings = [];
        $headerFound = false;

        foreach ($lines as $index => $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                continue;
            }

            if (! $headerFound) {
                if (preg_match('/^No\.\s*rekening\s*:\s*(.+)$/i', $line, $m)) {
                    $meta['account_number'] = trim(rtrim($m[1], "; \t"));
                    continue;
                }
                if (preg_match('/^Nama\s*:\s*(.+)$/i', $line, $m)) {
                    $meta['account_name'] = trim(rtrim($m[1], "; \t"));
                    continue;
                }
                if (preg_match('/^Periode\s*:\s*(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}\/\d{2}\/\d{4})/i', $line, $m)) {
                    $meta['period_start'] = Carbon::createFromFormat('!d/m/Y', $m[1])->format('Y-m-d');
                    $meta['period_end'] = Carbon::createFromFormat('!d/m/Y', $m[2])->format('Y-m-d');
                    continue;
                }
                if (preg_match('/^Kode Mata Uang\s*:\s*(.+)$/i', $line, $m)) {
                    $currency = strtoupper(trim(rtrim($m[1], "; \t")));
                    $meta['currency'] = $currency === 'RP' ? 'IDR' : $currency;
                    continue;
                }
                if (stripos($line, 'Tanggal Transaksi') !== false && stripos($line, 'Keterangan') !== false) {
                    $headerFound = true;
                    continue;
                }
                continue;
            }

            if (preg_match('/^(Saldo Awal|Mutasi Debet|Mutasi Kredit|Saldo Akhir)\s*:/i', $line)) {
                $footer[] = $this->parseFooterLine($line);
                continue;
            }

            $row = $this->parseDataLine($line, $index + 1, $warnings);
            if ($row !== null) {
                $rows[] = $row;
            }
        }

        if (! $headerFound) {
            $errors[] = 'Header "Tanggal Transaksi;Keterangan;Cabang;Jumlah;Saldo" tidak ditemukan.';
        }

        if ($rows === []) {
            $errors[] = 'Tidak ada baris transaksi yang berhasil dibaca.';
        }

        $computedDebit = 0.0;
        $computedCredit = 0.0;
        $debitCount = 0;
        $creditCount = 0;

        foreach ($rows as $row) {
            if ($row['position'] === 'DB') {
                $computedDebit += $row['amount'];
                $debitCount++;
            } else {
                $computedCredit += $row['amount'];
                $creditCount++;
            }
        }

        $footerByLabel = [];
        foreach ($footer as $item) {
            if ($item !== null) {
                $footerByLabel[$item['label']] = $item;
            }
        }

        $opening = $footerByLabel['Saldo Awal']['amount'] ?? null;
        $closing = $footerByLabel['Saldo Akhir']['amount'] ?? null;
        $footerDebit = $footerByLabel['Mutasi Debet']['amount'] ?? null;
        $footerCredit = $footerByLabel['Mutasi Kredit']['amount'] ?? null;
        $footerDebitCount = $footerByLabel['Mutasi Debet']['count'] ?? null;
        $footerCreditCount = $footerByLabel['Mutasi Kredit']['count'] ?? null;

        if ($opening !== null) {
            $running = $opening;
            $badLines = [];
            foreach ($rows as $row) {
                $running += $row['position'] === 'CR' ? $row['amount'] : -$row['amount'];
                if (abs($running - $row['balance']) > 0.01) {
                    $badLines[] = $row['line_no'];
                }
            }
            if ($badLines !== []) {
                $errors[] = 'Saldo berjalan tidak cocok pada baris: ' . implode(', ', array_slice($badLines, 0, 15))
                    . (count($badLines) > 15 ? ' ...' : '');
            }
        } else {
            $badLines = [];
        }

        // Rantai saldo berjalan adalah bukti terkuat bahwa seluruh baris konsisten.
        // Jika rantai valid tetapi footer tidak cocok, footer-nya yang salah ketik —
        // cukup jadi peringatan, bukan penghalang import.
        $chainValid = $opening !== null && $badLines === [];
        $footerMismatchBucket = $chainValid ? 'warnings' : 'errors';

        if ($opening !== null && $closing !== null) {
            $expectedClosing = $opening + $computedCredit - $computedDebit;
            if (abs($expectedClosing - $closing) > 0.01) {
                ${$footerMismatchBucket}[] = sprintf(
                    'Saldo akhir tidak cocok: saldo awal %s + kredit %s - debet %s = %s, tetapi footer menyebut %s.',
                    number_format($opening, 2, ',', '.'),
                    number_format($computedCredit, 2, ',', '.'),
                    number_format($computedDebit, 2, ',', '.'),
                    number_format($expectedClosing, 2, ',', '.'),
                    number_format($closing, 2, ',', '.')
                );
            }
        }

        if ($footerDebit !== null && abs($footerDebit - $computedDebit) > 0.01) {
            ${$footerMismatchBucket}[] = sprintf(
                'Total mutasi debet tidak cocok: file %s, terbaca %s.',
                number_format($footerDebit, 2, ',', '.'),
                number_format($computedDebit, 2, ',', '.')
            );
        }

        if ($footerCredit !== null && abs($footerCredit - $computedCredit) > 0.01) {
            ${$footerMismatchBucket}[] = sprintf(
                'Total mutasi kredit tidak cocok: file %s, terbaca %s.',
                number_format($footerCredit, 2, ',', '.'),
                number_format($computedCredit, 2, ',', '.')
            );
        }

        if ($footerDebitCount !== null && $footerDebitCount !== $debitCount) {
            ${$footerMismatchBucket}[] = "Jumlah baris debet tidak cocok: file {$footerDebitCount}, terbaca {$debitCount}.";
        }

        if ($footerCreditCount !== null && $footerCreditCount !== $creditCount) {
            ${$footerMismatchBucket}[] = "Jumlah baris kredit tidak cocok: file {$footerCreditCount}, terbaca {$creditCount}.";
        }

        $dates = array_column($rows, 'transaction_date');
        if ($meta['period_start'] === null && $dates !== []) {
            $meta['period_start'] = min($dates);
        }
        if ($meta['period_end'] === null && $dates !== []) {
            $meta['period_end'] = max($dates);
        }

        return [
            'meta' => $meta,
            'rows' => $rows,
            'summary' => [
                'row_count' => count($rows),
                'debit_count' => $debitCount,
                'credit_count' => $creditCount,
                'total_debit' => $computedDebit,
                'total_credit' => $computedCredit,
                'opening_balance' => $opening,
                'closing_balance' => $closing,
                'computed_closing_balance' => $opening === null ? null : $opening + $computedCredit - $computedDebit,
                'footer_debit_count' => $footerDebitCount,
                'footer_credit_count' => $footerCreditCount,
                'footer_total_debit' => $footerDebit,
                'footer_total_credit' => $footerCredit,
                'validated' => $errors === [],
            ],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function parseDataLine(string $line, int $lineNo, array &$warnings): ?array
    {
        $cols = str_getcsv($line, ';');
        $cols = array_map(fn ($value) => trim((string) $value), $cols);

        if (count($cols) < 5) {
            $warnings[] = "Baris {$lineNo}: kolom kurang dari 5, dilewati.";
            return null;
        }

        [$dateText, $description, $branch, $amountText, $balanceText] = array_slice($cols, 0, 5);

        if ($dateText === '' && $description === '') {
            return null;
        }

        $date = $this->parseDate($dateText);
        if ($date === null) {
            $warnings[] = "Baris {$lineNo}: tanggal '{$dateText}' tidak dikenali, dilewati.";
            return null;
        }

        if (! preg_match('/^([\d\.,]+)\s*(CR|DB)$/i', $amountText, $m)) {
            $warnings[] = "Baris {$lineNo}: nominal '{$amountText}' tidak sesuai format CR/DB, dilewati.";
            return null;
        }

        $amount = (float) str_replace([',', ' '], '', $m[1]);
        $position = strtoupper($m[2]);
        $balance = (float) str_replace([',', ' '], '', $balanceText);

        return [
            'line_no' => $lineNo,
            'transaction_date' => $date,
            'description' => $description,
            'branch_code' => $branch === '' ? null : $branch,
            'amount_text' => $amountText,
            'position' => $position,
            'amount' => $amount,
            'balance' => $balance,
            'period_year' => (int) substr($date, 0, 4),
            'period_month' => (int) substr($date, 5, 2),
            'row_key' => self::rowKey($date, $position, $description, $amount, $balance),
            'match_key' => self::matchKey($date, $position, $description),
        ];
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['!d F Y', '!d M Y', '!d/m/Y', '!Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseFooterLine(string $line): ?array
    {
        if (! preg_match('/^(Saldo Awal|Mutasi Debet|Mutasi Kredit|Saldo Akhir)\s*:\s*(.*)$/i', $line, $m)) {
            return null;
        }

        $label = $m[1];
        $rest = trim($m[2]);
        $count = null;

        if (stripos($label, 'Mutasi') === 0 && preg_match('/^(.*);(\d+);?$/', $rest, $mm)) {
            $rest = $mm[1];
            $count = (int) $mm[2];
        }

        $amount = (float) str_replace(';', '', $rest);

        return [
            'label' => $this->canonicalFooterLabel($label),
            'amount' => $amount,
            'count' => $count,
        ];
    }

    private function canonicalFooterLabel(string $label): string
    {
        return match (strtolower(trim($label))) {
            'saldo awal' => 'Saldo Awal',
            'mutasi debet' => 'Mutasi Debet',
            'mutasi kredit' => 'Mutasi Kredit',
            'saldo akhir' => 'Saldo Akhir',
            default => $label,
        };
    }

    public static function normalizeDescription(?string $value): string
    {
        $value = strtoupper(trim((string) $value));
        $value = str_replace("\xC2\xA0", ' ', $value);

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    public static function matchKey(string $date, string $position, string $description): string
    {
        return sha1($date . '|' . strtoupper($position) . '|' . self::normalizeDescription($description));
    }

    public static function rowKey(string $date, string $position, string $description, float $amount, float $balance): string
    {
        return sha1(
            self::matchKey($date, $position, $description)
            . '|' . number_format($amount, 2, '.', '')
            . '|' . number_format($balance, 2, '.', '')
        );
    }
}
