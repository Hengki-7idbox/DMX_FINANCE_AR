<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankImportBatch;
use App\Models\BankTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Menarik baris rekening koran dari Baserow (table 632) ke database lokal.
 *
 * Sifat sync:
 * - hanya menambah baris baru dan mengisi field yang masih kosong;
 * - tidak pernah menimpa isian manual maupun data hasil import CSV;
 * - saldo Baserow direkonstruksi best-effort karena satuannya tidak konsisten.
 */
class BaserowSyncService
{
    /**
     * Kandidat skala `Saldo` Baserow terhadap rupiah.
     *
     * @var array<int, float>
     */
    private const SCALES = [
        0.000001, 0.001, 1.0, 10.0, 100.0, 1000.0, 10000.0, 100000.0,
        1000000.0, 10000000.0, 100000000.0, 1000000000.0, 100000000000.0,
    ];

    public function __construct(private BaserowClient $baserow)
    {
    }

    public function isConfigured(): bool
    {
        return $this->baserow->isConfigured();
    }

    /**
     * Informasi untuk modal sync: rentang bulan Baserow dan daftar rekening.
     *
     * @return array<string, mixed>
     */
    public function info(): array
    {
        $bounds = $this->baserow->dateBounds();

        $months = [];
        if ($bounds['min'] && $bounds['max']) {
            $cursor = Carbon::createFromFormat('!Y-m-d', $bounds['min'])->startOfMonth();
            $end = Carbon::createFromFormat('!Y-m-d', $bounds['max'])->startOfMonth();

            while ($cursor->lte($end) && count($months) < 120) {
                $months[] = $cursor->format('Y-m');
                $cursor->addMonthNoOverflow();
            }
        }

        $accounts = BankAccount::query()
            ->withCount('bankTransactions')
            ->orderByDesc('bank_transactions_count')
            ->orderBy('bank_name')
            ->get()
            ->map(fn (BankAccount $account) => [
                'id' => $account->id,
                'label' => trim($account->bank_name . ' — ' . $account->account_number),
                'name' => $account->account_name,
                'transaction_count' => (int) $account->bank_transactions_count,
            ])
            ->values();

        return [
            'configured' => $this->isConfigured(),
            'min_date' => $bounds['min'],
            'max_date' => $bounds['max'],
            'months' => $months,
            'current_month' => now()->format('Y-m'),
            'default_account_id' => $accounts->first()['id'] ?? null,
            'accounts' => $accounts,
        ];
    }

    /**
     * Sync satu bulan Baserow ke database lokal.
     *
     * @return array<string, mixed>
     */
    public function syncMonth(int $accountId, string $month, bool $fillEmpty = true, ?int $userId = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Baserow belum dikonfigurasi (BASEROW_URL / BASEROW_TOKEN / BASEROW_TABLE_ID).');
        }

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw new RuntimeException('Format bulan harus YYYY-MM.');
        }

        $account = BankAccount::find($accountId);

        if (! $account) {
            throw new RuntimeException('Rekening bank tidak ditemukan.');
        }

        $fetched = $this->baserow->rowsByMonth($month);

        $summary = [
            'month' => $month,
            'account_id' => $account->id,
            'account_label' => trim($account->bank_name . ' — ' . $account->account_number),
            'fetched' => count($fetched),
            'invalid' => 0,
            'duplicate' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'balance_dates' => 0,
            'balance_estimated_dates' => 0,
            'batch_id' => null,
        ];

        $rows = [];
        $seen = [];

        foreach ($fetched as $raw) {
            $row = $this->normalizeRow($raw);

            if ($row === null) {
                $summary['invalid']++;
                continue;
            }

            // Hanya duplikat benar-benar identik yang dibuang (termasuk saldo mentah),
            // supaya dua transaksi sah dengan nominal sama tetap masuk.
            $signature = implode('|', [
                $row['transaction_date'],
                $row['position'],
                BcaStatementParser::normalizeDescription($row['description']),
                number_format($row['amount'], 2, '.', ''),
                $row['balance_raw'] === null ? 'null' : number_format($row['balance_raw'], 2, '.', ''),
            ]);

            if (isset($seen[$signature])) {
                $summary['duplicate']++;
                continue;
            }

            $seen[$signature] = true;
            $rows[] = $row;
        }

        if ($rows === []) {
            return $summary;
        }

        $this->applyBalances($rows, $summary);

        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth()->format('Y-m-d');
        $end = Carbon::createFromFormat('!Y-m', $month)->endOfMonth()->format('Y-m-d');

        return DB::transaction(function () use ($rows, $account, $month, $start, $end, $fillEmpty, $userId, $summary) {
            $existing = BankTransaction::query()
                ->where('bank_account_id', $account->id)
                ->whereBetween('transaction_date', [$start, $end])
                ->get();

            $byBaserowId = [];
            $byMatchKey = [];

            foreach ($existing as $model) {
                if ($model->baserow_id) {
                    $byBaserowId[(int) $model->baserow_id] = $model;
                }

                $byMatchKey[$this->localMatchKey($model)][] = $model;
            }

            $usedIds = [];
            $inserts = [];
            $updates = [];

            foreach ($rows as $row) {
                $match = null;

                if ($row['baserow_id'] !== null && isset($byBaserowId[$row['baserow_id']])) {
                    $candidate = $byBaserowId[$row['baserow_id']];

                    if (! isset($usedIds[$candidate->id])) {
                        $match = $candidate;
                    }
                }

                if ($match === null) {
                    $key = $this->rowMatchKey($row);
                    foreach ($byMatchKey[$key] ?? [] as $candidate) {
                        if (! isset($usedIds[$candidate->id])) {
                            $match = $candidate;
                            break;
                        }
                    }
                }

                if ($match === null) {
                    $inserts[] = $row;
                    continue;
                }

                $usedIds[$match->id] = true;

                if (! $fillEmpty) {
                    $summary['unchanged']++;
                    continue;
                }

                $changes = $this->emptyFieldUpdates($match, $row);

                if ($changes === []) {
                    $summary['unchanged']++;
                    continue;
                }

                $updates[$match->id] = $changes;
            }

            if ($inserts === [] && $updates === []) {
                return $summary;
            }

            $batch = BankImportBatch::create([
                'bank_account_id' => $account->id,
                'file_name' => null,
                'file_hash' => null,
                'period_start' => $start,
                'period_end' => $end,
                'row_count' => count($rows),
                'new_count' => count($inserts),
                'changed_count' => count($updates),
                'unchanged_count' => $summary['unchanged'],
                'missing_count' => 0,
                'strategy' => 'baserow_sync',
                'user_id' => $userId,
            ]);

            foreach ($updates as $id => $changes) {
                BankTransaction::whereKey($id)->update($changes + ['updated_at' => now()]);
            }

            foreach ($inserts as $row) {
                BankTransaction::create([
                    'bank_account_id' => $account->id,
                    'import_batch_id' => $batch->id,
                    'baserow_id' => $row['baserow_id'],
                    'sequence_no' => $row['sequence_no'],
                    'document_number' => $row['document_number'],
                    'transaction_date' => $row['transaction_date'],
                    'is_paid' => $row['is_paid'],
                    'description' => $row['description'],
                    'branch_code' => $row['branch_code'],
                    'amount' => $row['amount'],
                    'has_issue' => $row['has_issue'],
                    'balance' => $row['balance'],
                    'period_month' => $row['period_month'],
                    'period_year' => $row['period_year'],
                    'amount_text' => $row['amount_text'],
                    'position' => $row['position'],
                    'document_internal' => $row['document_internal'],
                    'document_external' => $row['document_external'],
                    'note' => $row['note'],
                    'status' => 'MATCHED',
                    'row_key' => 'BSR-' . ($row['baserow_id'] ?? sha1($this->rowMatchKey($row))),
                    'source' => 'BASEROW',
                ]);
            }

            $summary['inserted'] = count($inserts);
            $summary['updated'] = count($updates);
            $summary['batch_id'] = $batch->id;

            return $summary;
        });
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    private function normalizeRow(array $raw): ?array
    {
        $date = $raw['transaction_date'] ?? null;
        $position = strtoupper((string) ($raw['position'] ?? ''));
        $description = trim(preg_replace('/\s+/', ' ', (string) ($raw['description'] ?? '')) ?? '');
        $amount = (float) ($raw['amount'] ?? 0);

        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        if (! in_array($position, ['CR', 'DB'], true)) {
            return null;
        }

        if ($description === '' || $amount <= 0) {
            return null;
        }

        return [
            'baserow_id' => $raw['baserow_id'] ?? null,
            'sequence_no' => $raw['sequence_no'] ?? null,
            'document_number' => $raw['document_number'] ?? null,
            'transaction_date' => $date,
            'is_paid' => (bool) ($raw['is_paid'] ?? false),
            'description' => $description,
            'branch_code' => $raw['branch_code'] ?? null,
            'amount' => round($amount, 2),
            'signed_amount' => $position === 'DB' ? -round($amount, 2) : round($amount, 2),
            'has_issue' => (bool) ($raw['has_issue'] ?? false),
            'balance_raw' => $raw['balance_raw'] ?? null,
            'balance_hint' => $raw['balance_hint'] ?? null,
            'balance' => null,
            'period_month' => $raw['period_month'] ?? (int) substr($date, 5, 2),
            'period_year' => $raw['period_year'] ?? (int) substr($date, 0, 4),
            'amount_text' => $raw['amount_text'] ?? null,
            'position' => $position,
            'document_internal' => $raw['document_internal'] ?? null,
            'document_external' => $raw['document_external'] ?? null,
            'note' => $this->cleanNote($raw['note'] ?? null),
        ];
    }

    /**
     * Rekonstruksi saldo per tanggal.
     *
     * `Saldo` Baserow punya satuan yang berbeda-beda per baris/tanggal. Untuk
     * setiap tanggal dicari skala yang membuat selisih saldo cocok dengan
     * nominal transaksi. Jika tidak ada skala yang cocok, dipakai skala
     * dominan pada bulan tersebut dan nilainya ditandai perkiraan.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $summary
     */
    private function applyBalances(array &$rows, array &$summary): void
    {
        $groups = [];

        foreach ($rows as $index => $row) {
            $groups[$row['transaction_date']][] = $index;
        }

        $votes = [];
        $dateScales = [];

        foreach ($groups as $date => $indices) {
            [$scale, $confident] = $this->detectScale($rows, $indices);

            $dateScales[$date] = [$scale, $confident];
            $summary['balance_dates']++;

            if ($confident) {
                $votes[$scale] = ($votes[$scale] ?? 0) + count($indices);
            } else {
                $summary['balance_estimated_dates']++;
            }
        }

        arsort($votes);
        $dominant = $votes === [] ? 1.0 : (float) array_key_first($votes);

        foreach ($groups as $date => $indices) {
            [$scale, $confident] = $dateScales[$date];

            if (! $confident || $scale <= 0) {
                $scale = $dominant;
            }

            foreach ($indices as $index) {
                $raw = $rows[$index]['balance_raw'];

                if ($raw === null) {
                    $rows[$index]['balance'] = 0.0;
                    continue;
                }

                $rows[$index]['balance'] = round($raw / $scale, 2);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $indices
     * @return array{0: float, 1: bool}
     */
    private function detectScale(array $rows, array $indices): array
    {
        $count = count($indices);

        foreach ($indices as $index) {
            if ($rows[$index]['balance_raw'] === null) {
                return [1.0, false];
            }
        }

        if ($count === 1) {
            return [$this->guessSingleScale($rows[$indices[0]]), false];
        }

        $bestScale = 1.0;
        $bestMatches = -1;

        foreach (self::SCALES as $scale) {
            $anchor = $rows[$indices[0]]['balance_raw'] / $scale;
            $cumulative = 0.0;
            $matches = 1;

            for ($i = 1; $i < $count; $i++) {
                $cumulative += $rows[$indices[$i]]['signed_amount'];
                $expected = $anchor + $cumulative;
                $actual = $rows[$indices[$i]]['balance_raw'] / $scale;

                if (abs($expected - $actual) <= 1.0) {
                    $matches++;
                }
            }

            if ($matches > $bestMatches) {
                $bestMatches = $matches;
                $bestScale = (float) $scale;
            }
        }

        $ratio = $bestMatches / $count;

        // Rantai dianggap terpercaya bila seluruh baris cocok, atau hampir
        // seluruhnya cocok pada tanggal dengan banyak baris.
        $confident = $bestMatches === $count || ($count >= 5 && $ratio >= 0.95);

        return [$bestScale, $confident];
    }

    /**
     * Tebak skala untuk tanggal yang hanya punya satu baris.
     *
     * @param  array<string, mixed>  $row
     */
    private function guessSingleScale(array $row): float
    {
        $raw = (float) ($row['balance_raw'] ?? 0);
        $hint = $row['balance_hint'] ?? null;

        if ($raw <= 0 || $hint === null || (float) $hint <= 0) {
            return 1.0;
        }

        $ratio = $raw / (float) $hint;
        $best = self::SCALES[0];
        $bestDistance = PHP_FLOAT_MAX;

        foreach (self::SCALES as $scale) {
            $distance = abs(log10($ratio) - log10($scale));

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $scale;
            }
        }

        return (float) $best;
    }

    /**
     * Field yang boleh diisi otomatis, hanya jika masih kosong.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function emptyFieldUpdates(BankTransaction $model, array $row): array
    {
        $changes = [];

        $candidates = [
            'baserow_id' => $row['baserow_id'],
            'sequence_no' => $row['sequence_no'],
            'document_number' => $row['document_number'],
            'document_internal' => $row['document_internal'],
            'document_external' => $row['document_external'],
            'note' => $row['note'],
        ];

        foreach ($candidates as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $current = $model->{$field};

            if ($current === null || $current === '' || ($field === 'baserow_id' && (int) $current === 0)) {
                $changes[$field] = $value;
            }
        }

        return $changes;
    }

    private function localMatchKey(BankTransaction $model): string
    {
        return sha1(implode('|', [
            $model->transaction_date?->format('Y-m-d'),
            strtoupper((string) $model->position),
            BcaStatementParser::normalizeDescription($model->description),
            number_format((float) $model->amount, 2, '.', ''),
        ]));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowMatchKey(array $row): string
    {
        return sha1(implode('|', [
            $row['transaction_date'],
            $row['position'],
            BcaStatementParser::normalizeDescription($row['description']),
            number_format((float) $row['amount'], 2, '.', ''),
        ]));
    }

    private function cleanNote(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }

        $note = trim($note);

        // Artefak Baserow: kolom Catatan berisi "TRUE" pada banyak baris.
        if ($note === '' || strcasecmp($note, 'TRUE') === 0) {
            return null;
        }

        return $note;
    }
}
