<?php

namespace App\Services;

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
        if (! $this->isConfigured()) {
            throw new RuntimeException('Konfigurasi Baserow belum lengkap (BASEROW_URL / BASEROW_TOKEN / BASEROW_TABLE_ID).');
        }

        $tableId = (string) config('services.baserow.table_id');
        $dateField = (string) config('services.baserow.date_filter_field', 'Tanggal');
        $url = rtrim((string) config('services.baserow.url'), '/')
            . '/api/database/rows/table/' . $tableId . '/';

        $rows = [];
        $page = 1;

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . (string) config('services.baserow.token'),
            ])
                ->acceptJson()
                ->timeout(30)
                ->get($url, [
                    'user_field_names' => 'true',
                    'size' => 200,
                    'page' => $page,
                    'filter__' . $dateField . '__date_equal' => $date,
                ]);

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

            $hasNext = ! empty($payload['next']);
            $page++;
        } while ($hasNext && $page <= 25);

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
            'amount' => (float) ($row['Nilai Rp.'] ?? 0),
            'has_issue' => (bool) ($row['Ada Masalah'] ?? false),
            // Baserow menyimpan saldo dalam sen (×100 dari nilai rupiah).
            'balance_sen' => (float) ($row['Saldo'] ?? 0),
            'period_month' => isset($row['Periode']) ? (int) $row['Periode'] : null,
            'amount_text' => $this->stringOrNull($row['Jumlah'] ?? null),
            'position' => $this->stringOrNull($row['Pos'] ?? null),
            'document_internal' => $this->stringOrNull($row['No Dok Internal'] ?? null),
            'document_external' => $this->stringOrNull($row['No Dok External'] ?? null),
            'period_year' => isset($row['Tahun']) ? (int) $row['Tahun'] : null,
            'note' => $this->stringOrNull($row['Catatan'] ?? null),
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
