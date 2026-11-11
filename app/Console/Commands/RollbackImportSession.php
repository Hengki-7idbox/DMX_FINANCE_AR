<?php

namespace App\Console\Commands;

use App\Models\DetailSale;
use App\Models\ImportSession;
use App\Models\ImportSessionDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rollback satu sesi import Detail Sales berdasarkan audit import_session_details.
 *
 * Aturan:
 * - Dokumen yang punya entri `new` pada sesi ini = dokumen BARU hasil sesi,
 *   sehingga dihapus (nilai old_values-nya bukan state sebelum sesi).
 * - Dokumen yang hanya punya entri `changed` = dokumen lama yang tertimpa,
 *   sehingga dikembalikan dari `old_values` entri changed paling awal.
 *
 * Sebelum mengubah data, tabel detail_sales & detail_sale_items yang terdampak
 * disalin ke tabel backup `*_bak_s{session}_{timestamp}`.
 *
 * Pemakaian:
 *   php artisan imports:rollback 92 --dry-run
 *   php artisan imports:rollback 92
 */
class RollbackImportSession extends Command
{
    protected $signature = 'imports:rollback
        {session : ID import_sessions}
        {--dry-run : Hanya tampilkan rencana, tidak mengubah data}';

    protected $description = 'Rollback satu sesi import Detail Sales memakai audit import_session_details';

    /** Kolom detail_sales yang boleh direstore dari old_values. */
    private const RESTORABLE = [
        'customer_code', 'customer_name', 'transaction_date', 'description',
        'invoice_amount', 'amount', 'tax_base', 'discount',
        'return_amount', 'owing', 'payment_amount', 'calculate_field',
    ];

    public function handle(): int
    {
        $sessionId = (int) $this->argument('session');
        $dryRun = (bool) $this->option('dry-run');

        $session = ImportSession::find($sessionId);
        if ($session === null) {
            $this->error("Sesi import #{$sessionId} tidak ditemukan.");

            return self::FAILURE;
        }

        if ($session->import_type !== ImportSession::TYPE_SALES) {
            $this->error("Sesi #{$sessionId} bukan import sales (import_type={$session->import_type}).");

            return self::FAILURE;
        }

        $this->info("Sesi #{$session->id} | {$session->source_type} | {$session->file_name} | {$session->created_at}");
        $this->line("Mode: " . ($dryRun ? 'DRY-RUN (tidak ada perubahan)' : 'APPLY'));

        $details = ImportSessionDetail::where('session_id', $session->id)->get();

        if ($details->isEmpty()) {
            $this->warn('Tidak ada baris audit untuk sesi ini.');

            return self::SUCCESS;
        }

        $newDocs = $details->where('change_type', 'new')->pluck('doc_number')->unique()->values();

        // State sebelum sesi = nilai `old` PALING AWAL per field per dokumen.
        // Tidak cukup memakai entri changed pertama: entri pertama hanya memuat
        // field yang berubah pada baris itu, sedangkan field lain bisa berubah di
        // baris berikutnya (dulu ini meninggalkan sisa data impor yang salah).
        $restoreValues = [];
        foreach ($details->where('change_type', 'changed')->sortBy('id') as $detail) {
            $doc = $detail->doc_number;
            foreach ($this->decodeValues($detail->old_values) as $field => $value) {
                if (! in_array($field, self::RESTORABLE, true)) {
                    continue;
                }

                if (! array_key_exists($field, $restoreValues[$doc] ?? [])) {
                    $restoreValues[$doc][$field] = $value;
                }
            }
        }

        $restoreDocs = collect(array_keys($restoreValues))->diff($newDocs)->values();
        foreach ($newDocs as $doc) {
            unset($restoreValues[$doc]);
        }

        $deleteDocs = $newDocs;

        $this->line("Dokumen baru (dihapus)     : {$deleteDocs->count()}");
        $this->line("Dokumen lama (direstore)   : {$restoreDocs->count()}");
        $this->line("Total dokumen terdampak    : " . ($deleteDocs->count() + $restoreDocs->count()));

        $existingDelete = DetailSale::where('source_type', $session->source_type)
            ->whereIn('doc_number', $deleteDocs)
            ->count();
        $existingRestore = DetailSale::where('source_type', $session->source_type)
            ->whereIn('doc_number', $restoreDocs)
            ->count();

        $this->line("Baris detail_sales ditemukan: hapus={$existingDelete}, restore={$existingRestore}");

        $decAfter = DetailSale::where('source_type', $session->source_type)
            ->whereIn('doc_number', $deleteDocs)
            ->whereYear('transaction_date', 2026)
            ->whereMonth('transaction_date', 12)
            ->pluck('doc_number')
            ->unique();

        if ($decAfter->isNotEmpty()) {
            $this->line('Contoh dokumen Desember yang akan terhapus: ' . $decAfter->take(5)->implode(', '));
        }

        if ($restoreDocs->isNotEmpty()) {
            $sampleDoc = $restoreDocs->first();
            $old = $restoreValues[$sampleDoc] ?? [];
            $this->line("Contoh restore {$sampleDoc}: tanggal " . ($old['transaction_date'] ?? '-')
                . ', customer ' . ($old['customer_name'] ?? '-')
                . ', invoice ' . ($old['invoice_amount'] ?? '-'));
        }

        if ($dryRun) {
            $this->newLine();
            $this->comment('Dry-run selesai. Jalankan tanpa --dry-run untuk menerapkan.');

            return self::SUCCESS;
        }

        $stamp = now()->format('YmdHis');
        $bakDetail = "detail_sales_bak_s{$session->id}_{$stamp}";
        $bakItems = "detail_sale_items_bak_s{$session->id}_{$stamp}";
        $allDocs = $deleteDocs->merge($restoreDocs)->values();

        DB::statement(
            $this->backupSql($bakDetail, 'detail_sales', $allDocs),
            $allDocs->isEmpty() ? [] : array_merge([$session->source_type], $allDocs->all())
        );

        DB::statement(
            $this->backupSql($bakItems, 'detail_sale_items', $deleteDocs),
            $deleteDocs->isEmpty() ? [] : array_merge([$session->source_type], $deleteDocs->all())
        );

        $this->line("Backup dibuat: {$bakDetail}, {$bakItems}");

        $restored = 0;
        $deleted = 0;
        $deletedItems = 0;

        DB::transaction(function () use (
            $session, $restoreValues, $restoreDocs, $deleteDocs,
            &$restored, &$deleted, &$deletedItems
        ) {
            foreach ($restoreDocs as $doc) {
                $update = $restoreValues[$doc] ?? [];
                if ($update === []) {
                    continue;
                }

                $restored += DetailSale::where('source_type', $session->source_type)
                    ->where('doc_number', $doc)
                    ->update($update);
            }

            $deletedItems = DB::table('detail_sale_items')
                ->where('source_type', $session->source_type)
                ->whereIn('doc_number', $deleteDocs)
                ->delete();

            $deleted = DetailSale::where('source_type', $session->source_type)
                ->whereIn('doc_number', $deleteDocs)
                ->delete();
        });

        $this->newLine();
        $this->info("Selesai: {$restored} baris direstore, {$deleted} dokumen dihapus, {$deletedItems} item dihapus.");

        Log::warning('imports.rollback diterapkan', [
            'session_id' => $session->id,
            'source_type' => $session->source_type,
            'restored' => $restored,
            'deleted' => $deleted,
            'deleted_items' => $deletedItems,
            'backup_detail' => $bakDetail,
            'backup_items' => $bakItems,
        ]);

        return self::SUCCESS;
    }

    /**
     * old_values/new_values sudah di-cast array oleh model; tetap dukung string JSON mentah.
     */
    private function decodeValues(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function backupSql(string $table, string $sourceTable, $docs): string
    {
        if ($docs->isEmpty()) {
            return "CREATE TABLE {$table} AS SELECT * FROM {$sourceTable} WHERE 1 = 0";
        }

        return "CREATE TABLE {$table} AS SELECT * FROM {$sourceTable} WHERE source_type = ? AND doc_number IN ("
            . $this->placeholders($docs->count()) . ')';
    }

    private function placeholders(int $count): string
    {
        return implode(',', array_fill(0, max($count, 1), '?'));
    }
}
