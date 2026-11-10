<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien Baserow untuk tabel Rekening Koran (default table 632).
 */
class BaserowClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.baserow.url'))
            && filled(config('services.baserow.token'))
            && filled(config('services.baserow.table_id'));
    }

    /**
     * Ambil seluruh baris Baserow untuk satu tanggal.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rowsByDate(string $date): array
    {
        $rows = $this->fetchRows([
            'filter__' . $this->dateField() . '__date_equal' => $date,
        ], 'Tanggal');

        return array_values(array_filter(
            $rows,
            fn (array $row) => $row['transaction_date'] === $date
        ));
    }

    /**
     * Ambil seluruh baris Baserow pada satu bulan (format YYYY-MM).
     *
     * Filter Baserow date_after / date_before bersifat eksklusif, jadi rentang
     * dilebarkan satu hari lalu disaring ulang di PHP agar tidak ada baris
     * di awal/akhir bulan yang terlewat.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rowsByMonth(string $month): array
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw new RuntimeException('Format bulan harus YYYY-MM.');
        }

        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = $this->fetchRows([
            'filter__' . $this->dateField() . '__date_after' => $start->copy()->subDay()->format('Y-m-d'),
            'filter__' . $this->dateField() . '__date_before' => $end->copy()->addDay()->format('Y-m-d'),
        ], 'Tanggal', 'ID');

        return array_values(array_filter(
            $rows,
            fn (array $row) => is_string($row['transaction_date'])
                && str_starts_with($row['transaction_date'], $month . '-')
        ));
    }

    /**
     * Tanggal paling awal dan paling akhir yang tersedia di Baserow.
     *
     * @return array{min: string|null, max: string|null}
     */
    public function dateBounds(): array
    {
        $field = $this->dateField();
        $filter = 'filter__' . $field . '__not_empty';

        $ascending = $this->fetchRows([$filter => 'true'], $field, null, true);
        $descending = $this->fetchRows([$filter => 'true'], '-' . $field, null, true);

        return [
            'min' => $ascending[0]['transaction_date'] ?? null,
            'max' => $descending[0]['transaction_date'] ?? null,
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(array $filters, string $orderBy, ?string $secondaryOrderBy = null, bool $firstPageOnly = false): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Konfigurasi Baserow belum lengkap (BASEROW_URL / BASEROW_TOKEN / BASEROW_TABLE_ID).');
        }

        $url = rtrim((string) config('services.baserow.url'), '/')
            . '/api/database/rows/table/' . (string) config('services.baserow.table_id') . '/';

        $query = [
            'user_field_names' => 'true',
            'size' => $firstPageOnly ? 1 : 200,
            'order_by' => $secondaryOrderBy ? $orderBy . ',' . $secondaryOrderBy : $orderBy,
        ] + $filters;

        $rows = [];
        $page = 1;

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . (string) config('services.baserow.token'),
            ])
                ->acceptJson()
                ->timeout(60)
                ->get($url, ['page' => $page] + $query);

            if (! $response->successful()) {
                throw new RuntimeException(
                    'Baserow HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 300)
                );
            }

            $payload = $response->json();
            foreach (($payload['results'] ?? []) as $row) {
                if (is_array($row)) {
                    $rows[] = $this->mapRow($row);
                }
            }

            $hasNext = ! $firstPageOnly && ! empty($payload['next']);
            $page++;
        } while ($hasNext && $page <= 100);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        return [
            'baserow_id' => isset($row['id']) ? (int) $row['id'] : null,
            'sequence_no' => isset($row['ID']) ? (int) $row['ID'] : null,
            'document_number' => $this->stringOrNull($row['No Dokumen'] ?? null),
            'transaction_date' => $this->stringOrNull($row['Tanggal'] ?? null),
            'is_paid' => (bool) ($row['Pelunasan'] ?? false),
            'description' => (string) ($row['Keterangan'] ?? ''),
            'branch_code' => $this->stringOrNull($row['Cabang'] ?? null),
            'amount' => (float) ($this->numberOrNull($row['Nilai Rp.'] ?? null) ?? 0),
            'has_issue' => (bool) ($row['Ada Masalah'] ?? false),
            // Skala `Saldo` tidak konsisten (rupiah, sen, atau kelipatan lain per
            // baris/tanggal), jadi disimpan mentah dan direkonstruksi saat sync.
            'balance_raw' => $this->numberOrNull($row['Saldo'] ?? null),
            // `Saldo.` adalah saldo dalam satuan juta/miliar yang ikut tidak konsisten.
            'balance_hint' => $this->numberOrNull($row['Saldo.'] ?? null),
            'period_month' => isset($row['Periode']) ? (int) $row['Periode'] : null,
            'period_year' => isset($row['Tahun']) ? (int) $row['Tahun'] : null,
            'amount_text' => $this->stringOrNull($row['Jumlah'] ?? null),
            'position' => $this->stringOrNull($row['Pos'] ?? null),
            'document_internal' => $this->stringOrNull($row['No Dok Internal'] ?? null),
            'document_external' => $this->stringOrNull($row['No Dok External'] ?? null),
            'note' => $this->stringOrNull($row['Catatan'] ?? null),
        ];
    }

    private function dateField(): string
    {
        return (string) config('services.baserow.date_filter_field', 'Tanggal');
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function numberOrNull(mixed $value): ?float
    {
        if ($value === null || is_bool($value)) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = str_replace(["\xC2\xA0", ' '], '', trim((string) $value));

        if ($text === '') {
            return null;
        }

        // Format Indonesia: 1.234.567,89
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $text)) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } else {
            // Format umum: 1,234,567.89
            $text = str_replace(',', '', $text);
        }

        return is_numeric($text) ? (float) $text : null;
    }
}
