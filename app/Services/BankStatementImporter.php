<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankImportBatch;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BankStatementImporter
{
    public function __construct(private BaserowClient $baserow)
    {
    }

    /**
     * Siapkan data preview: cocokkan dengan Baserow dan dengan data yang sudah ada.
     *
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    public function prepare(array $parsed, ?string $fileName = null, ?string $fileHash = null): array
    {
        $meta = $parsed['meta'];
        $bankAccount = $this->resolveBankAccount($meta);

        $warnings = $parsed['warnings'] ?? [];

        $baserowByDate = [];
        if ($this->baserow->isConfigured()) {
            $dates = array_values(array_unique(array_column($parsed['rows'], 'transaction_date')));
            foreach ($dates as $date) {
                try {
                    $baserowByDate[$date] = $this->baserow->rowsByDate($date);
                } catch (\Throwable $e) {
                    $baserowByDate[$date] = [];
                    $warnings[] = "Baserow {$date}: " . $e->getMessage();
                }
            }
        } else {
            $warnings[] = 'Baserow belum dikonfigurasi. Nomor dokumen tidak bisa diambil otomatis.';
        }

        $dates = array_column($parsed['rows'], 'transaction_date');
        $start = $meta['period_start'] ?? ($dates === [] ? null : min($dates));
        $end = $meta['period_end'] ?? ($dates === [] ? null : max($dates));

        $existing = BankTransaction::query()
            ->where('bank_account_id', $bankAccount->id)
            ->when($start && $end, fn ($q) => $q->whereBetween('transaction_date', [$start, $end]))
            ->get();

        $existingByKey = [];
        foreach ($existing as $row) {
            $key = BcaStatementParser::matchKey(
                $row->transaction_date->format('Y-m-d'),
                (string) $row->position,
                (string) $row->description
            );
            $existingByKey[$key][] = $row;
        }

        $usedExistingIds = [];
        $rows = [];
        $diff = ['new' => 0, 'changed' => 0, 'unchanged' => 0, 'missing' => 0];

        foreach ($parsed['rows'] as $row) {
            $baserowMatch = $this->matchBaserow($row, $baserowByDate[$row['transaction_date']] ?? []);
            $baserowRow = $baserowMatch['row'];

            $key = $row['match_key'];
            [$existingRow, $changes] = $this->pickExisting($existingByKey[$key] ?? [], $usedExistingIds, $row);
            $state = $existingRow === null ? 'new' : ($changes === [] ? 'unchanged' : 'changed');
            $diff[$state]++;

            $rows[] = [
                'transaction_date' => $row['transaction_date'],
                'description' => $row['description'],
                'branch_code' => $row['branch_code'],
                'amount' => $row['amount'],
                'balance' => $row['balance'],
                'amount_text' => $row['amount_text'],
                'position' => $row['position'],
                'period_year' => $row['period_year'],
                'period_month' => $row['period_month'],
                'row_key' => $row['row_key'],
                'baserow_id' => $baserowRow['baserow_id'] ?? null,
                'sequence_no' => $baserowRow['sequence_no'] ?? null,
                'document_number' => $baserowRow['document_number'] ?? null,
                'document_internal' => $baserowRow['document_internal'] ?? null,
                'document_external' => $baserowRow['document_external'] ?? null,
                'is_paid' => $baserowRow['is_paid'] ?? false,
                'has_issue' => $baserowRow['has_issue'] ?? false,
                'note' => $baserowRow['note'] ?? null,
                'status' => $baserowMatch['status'],
                'state' => $state,
                'existing_id' => $existingRow?->id,
                'changes' => $changes,
            ];
        }

        foreach ($existing as $row) {
            if (! isset($usedExistingIds[$row->id])) {
                $diff['missing']++;
            }
        }

        $total = count($rows);
        $changeRatio = $total > 0 ? ($diff['changed'] + $diff['missing']) / $total : 0;
        $recommendation = ($diff['missing'] > 0 || $changeRatio > 0.3) ? 'replace' : 'incremental';

        $reason = $recommendation === 'replace'
            ? 'Ada baris yang hilang dari file atau perubahan melebihi 30%, sehingga menimpa periode lebih aman dan konsisten.'
            : 'Perubahan sedikit, jadi tambah/ubah baris yang berubah lebih cepat dan catatan manual tetap aman.';

        $baserowCount = 0;
        foreach ($baserowByDate as $list) {
            $baserowCount += count($list);
        }

        return [
            'file_name' => $fileName,
            'file_hash' => $fileHash,
            'bank_account_id' => $bankAccount->id,
            'bank_account_label' => $bankAccount->account_number . ' — ' . $bankAccount->account_name,
            'meta' => $meta,
            'summary' => $parsed['summary'],
            'errors' => $parsed['errors'] ?? [],
            'warnings' => $warnings,
            'rows' => $rows,
            'diff' => $diff,
            'recommendation' => $recommendation,
            'recommendation_reason' => $reason,
            'baserow_configured' => $this->baserow->isConfigured(),
            'baserow_row_count' => $baserowCount,
        ];
    }

    /**
     * Simpan hasil preview ke database.
     *
     * @param  array<string, mixed>  $prepared
     * @return array{batch: BankImportBatch, inserted: int, updated: int, deleted: int}
     */
    public function commit(array $prepared, string $strategy, ?int $userId = null): array
    {
        $accountId = (int) $prepared['bank_account_id'];
        $start = $prepared['meta']['period_start'] ?? null;
        $end = $prepared['meta']['period_end'] ?? null;

        return DB::transaction(function () use ($prepared, $strategy, $userId, $accountId, $start, $end) {
            $batch = BankImportBatch::create([
                'bank_account_id' => $accountId,
                'file_name' => $prepared['file_name'] ?? null,
                'file_hash' => $prepared['file_hash'] ?? null,
                'period_start' => $start,
                'period_end' => $end,
                'row_count' => count($prepared['rows']),
                'new_count' => $prepared['diff']['new'] ?? 0,
                'changed_count' => $prepared['diff']['changed'] ?? 0,
                'unchanged_count' => $prepared['diff']['unchanged'] ?? 0,
                'missing_count' => $prepared['diff']['missing'] ?? 0,
                'strategy' => $strategy,
                'user_id' => $userId,
            ]);

            $deleted = 0;
            if ($strategy === 'replace' && $start && $end) {
                $deleted = BankTransaction::query()
                    ->where('bank_account_id', $accountId)
                    ->whereBetween('transaction_date', [$start, $end])
                    ->delete();
            }

            $inserted = 0;
            $updated = 0;

            foreach ($prepared['rows'] as $row) {
                if ($strategy === 'incremental' && ($row['state'] ?? null) === 'unchanged' && ! empty($row['existing_id'])) {
                    // Tidak ada perubahan: biarkan baris apa adanya, termasuk batch import sebelumnya.
                    continue;
                }

                $data = [
                    'bank_account_id' => $accountId,
                    'import_batch_id' => $batch->id,
                    'baserow_id' => $row['baserow_id'],
                    'sequence_no' => $row['sequence_no'],
                    'document_number' => $row['document_number'],
                    'transaction_date' => $row['transaction_date'],
                    'is_paid' => (bool) $row['is_paid'],
                    'description' => $row['description'],
                    'branch_code' => $row['branch_code'],
                    'amount' => $row['amount'],
                    'has_issue' => (bool) $row['has_issue'],
                    'balance' => $row['balance'],
                    'period_month' => $row['period_month'],
                    'period_year' => $row['period_year'],
                    'amount_text' => $row['amount_text'],
                    'position' => $row['position'],
                    'document_internal' => $row['document_internal'],
                    'document_external' => $row['document_external'],
                    'note' => $row['note'],
                    'status' => $row['status'],
                    'row_key' => $row['row_key'],
                    'source' => 'BCA_CSV',
                ];

                if ($strategy === 'incremental' && ! empty($row['existing_id'])) {
                    $existing = BankTransaction::find($row['existing_id']);
                    if ($existing) {
                        // Jangan hapus catatan / nomor dokumen yang sudah diisi manual.
                        $data['note'] = $existing->note ?: $data['note'];
                        $data['document_number'] = $data['document_number'] ?: $existing->document_number;
                        $data['document_internal'] = $data['document_internal'] ?: $existing->document_internal;
                        $data['document_external'] = $data['document_external'] ?: $existing->document_external;

                        $existing->update($data);
                        $updated++;
                        continue;
                    }
                }

                BankTransaction::updateOrCreate(
                    [
                        'bank_account_id' => $accountId,
                        'row_key' => $row['row_key'],
                    ],
                    $data
                );
                $inserted++;
            }

            return [
                'batch' => $batch,
                'inserted' => $inserted,
                'updated' => $updated,
                'deleted' => $deleted,
            ];
        });
    }

    private function resolveBankAccount(array $meta): BankAccount
    {
        if (empty($meta['account_number'])) {
            throw new RuntimeException('Nomor rekening tidak ditemukan pada header file.');
        }

        return BankAccount::firstOrCreate(
            ['account_number' => $meta['account_number']],
            [
                'bank_name' => 'BCA',
                'account_name' => $meta['account_name'] ?: 'BCA',
                'currency' => $meta['currency'] ?: 'IDR',
                'status' => 'ACTIVE',
            ]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidates
     * @return array{status: string, row: array<string, mixed>|null}
     */
    private function matchBaserow(array $row, array $candidates): array
    {
        $normalized = BcaStatementParser::normalizeDescription($row['description']);
        $balanceSen = (float) $row['balance'] * 100;

        $exact = [];
        $loose = [];

        foreach ($candidates as $candidate) {
            if (BcaStatementParser::normalizeDescription($candidate['description'] ?? '') !== $normalized) {
                continue;
            }
            if (abs((float) ($candidate['amount'] ?? 0) - (float) $row['amount']) > 0.005) {
                continue;
            }
            if (($candidate['position'] ?? '') !== $row['position']) {
                continue;
            }

            $candidateBalance = (float) ($candidate['balance_sen'] ?? 0);
            if (abs($candidateBalance - $balanceSen) < 0.5
                || abs($candidateBalance - (float) $row['balance']) < 0.5) {
                $exact[] = $candidate;
            } else {
                $loose[] = $candidate;
            }
        }

        if (count($exact) === 1) {
            return ['status' => 'MATCHED', 'row' => $exact[0]];
        }
        if (count($exact) > 1) {
            return ['status' => 'AMBIGUOUS', 'row' => $exact[0]];
        }
        if (count($loose) === 1) {
            return ['status' => 'MATCHED', 'row' => $loose[0]];
        }
        if (count($loose) > 1) {
            return ['status' => 'AMBIGUOUS', 'row' => $loose[0]];
        }

        return ['status' => 'NOT_FOUND', 'row' => null];
    }

    /**
     * @param  array<int, BankTransaction>  $existingRows
     * @param  array<int, bool>  $usedIds
     * @return array{0: BankTransaction|null, 1: array<string, array{old: mixed, new: mixed}>}
     */
    private function pickExisting(array $existingRows, array &$usedIds, array $row): array
    {
        $exact = null;
        $fallback = null;

        foreach ($existingRows as $existing) {
            if (isset($usedIds[$existing->id])) {
                continue;
            }
            if ($fallback === null) {
                $fallback = $existing;
            }
            if (abs((float) $existing->amount - (float) $row['amount']) < 0.005) {
                $exact = $existing;
                break;
            }
        }

        $candidate = $exact ?? $fallback;
        if ($candidate === null) {
            return [null, []];
        }

        $usedIds[$candidate->id] = true;

        $changes = [];
        foreach ([
            'amount' => (float) $row['amount'],
            'balance' => (float) $row['balance'],
            'description' => (string) $row['description'],
            'branch_code' => (string) ($row['branch_code'] ?? ''),
            'amount_text' => (string) $row['amount_text'],
            'position' => (string) $row['position'],
        ] as $field => $newValue) {
            $oldValue = $field === 'amount' || $field === 'balance'
                ? (float) $candidate->{$field}
                : (string) ($candidate->{$field} ?? '');

            $isDifferent = is_float($newValue)
                ? abs($oldValue - $newValue) > 0.005
                : $oldValue !== $newValue;

            if ($isDifferent) {
                $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        return [$candidate, $changes];
    }
}
