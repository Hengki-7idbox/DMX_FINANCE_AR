<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Daftar master satuan ukur (unit) dari Accurate Online.
 *
 * Dipakai halaman Mapping Unit agar seluruh unit yang ada di Accurate
 * tampil lengkap dan bisa dipetakan ke kode unit Coretax (UM.xxxx).
 */
class AccurateUnitService
{
    private const CACHE_KEY = 'accurate.units';

    private const CACHE_TTL = 600;

    private ?string $error = null;

    /**
     * @return array<int,array{id:?int,name:string}>
     */
    public function list(bool $refresh = false): array
    {
        $this->error = null;
        $cached = Cache::get(self::CACHE_KEY);
        $cached = is_array($cached) ? $cached : [];

        if (! $refresh && $cached !== []) {
            return $cached;
        }

        $client = new AccurateApiClient();
        if (! $client->configured()) {
            $this->error = 'API Token dan Signature Secret Accurate belum diisi di Settings.';

            return $cached;
        }

        $result = $client->get('/accurate/api/unit/list.do', ['sp.pageSize' => 500]);

        if (! ($result['ok'] ?? false)) {
            $this->error = (string) ($result['message'] ?? 'Gagal mengambil daftar unit dari Accurate.');

            return $cached;
        }

        $units = [];
        foreach (($result['body']['d'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $units[] = [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'name' => $name,
            ];
        }

        usort($units, function (array $a, array $b): int {
            return strcasecmp($a['name'], $b['name'])
                ?: (($a['id'] ?? 0) <=> ($b['id'] ?? 0));
        });

        Cache::put(self::CACHE_KEY, $units, self::CACHE_TTL);

        return $units;
    }

    public function error(): ?string
    {
        return $this->error;
    }
}
