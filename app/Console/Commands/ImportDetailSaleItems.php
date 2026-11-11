<?php

namespace App\Console\Commands;

use App\Imports\DetailSaleItemsImport;
use App\Models\ImportSession;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import Detail Product Sales (per item) dari CLI, dengan pengaman format file.
 *
 * Dipakai untuk memperbaiki/mengisi ulang item dokumen tanpa lewat UI, mis.:
 *   php artisan imports:items "Sample/Detail Ext.xls" --source=external --only-existing --dry-run
 *   php artisan imports:items "Sample/Detail Ext.xls" --source=external --only-existing
 */
class ImportDetailSaleItems extends Command
{
    protected $signature = 'imports:items
        {file : Path file Excel (relatif ke project root atau absolut)}
        {--source=external : Tab tujuan (internal|external)}
        {--only-existing : Lewati dokumen yang belum ada di detail_sales}
        {--dry-run : Hitung baris tanpa menghapus/menulis item}';

    protected $description = 'Import Detail Product Sales (per item) dengan validasi format dan opsi hanya dokumen yang sudah ada';

    public function handle(): int
    {
        $source = (string) $this->option('source');
        if (! in_array($source, ['internal', 'external'], true)) {
            $this->error('Opsi --source harus internal atau external.');

            return self::FAILURE;
        }

        $path = (string) $this->argument('file');
        if (! is_file($path)) {
            $path = base_path($path);
        }

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $format = $this->detectFormat($path);
        $this->line("File   : {$path}");
        $this->line('Judul  : ' . ($format['title'] ?: '-'));
        $this->line('Mode   : ' . ($this->option('dry-run') ? 'DRY-RUN' : 'APPLY')
            . ($this->option('only-existing') ? ' | hanya dokumen yang sudah ada' : ''));

        if ($format['title'] !== '' && ! $format['isItemLayout']) {
            $this->error('File ini bukan export "Detail Sales Per Item".');
            $this->line('Judul terdeteksi: ' . $format['title']);
            $this->comment('File "Sales By Customer Detail" harus diimpor lewat Import Detail Sales (per invoice), bukan Detail Product Sales.');

            return self::FAILURE;
        }

        if (! $format['headerOk']) {
            $this->warn('Header "Item No." (kolom J) / "Item Description" (kolom L) tidak terdeteksi — periksa kembali file.');
        }

        $dryRun = (bool) $this->option('dry-run');

        $import = new DetailSaleItemsImport(
            $source,
            (bool) $this->option('only-existing'),
            $dryRun,
        );

        $session = null;
        if (! $dryRun) {
            $session = ImportSession::create([
                'source_type' => $source,
                'import_type' => ImportSession::TYPE_ITEMS,
                'file_name' => basename($path),
                'user_id' => null,
            ]);
        }

        Excel::import($import, $path);

        if ($session !== null) {
            $session->update([
                'new_count' => $import->getCreatedCount(),
                'changed_count' => $import->getUpdatedCount(),
                'unchanged_count' => 0,
                'skipped_count' => $import->getSkippedCount(),
            ]);
        }

        $docs = $import->getTouchedDocs();

        $this->newLine();
        $this->info(($dryRun ? 'Dry-run' : 'Selesai') . ": {$import->getCreatedCount()} baris item"
            . ($dryRun ? ' akan ditulis' : ' ditulis')
            . ', ' . count($docs) . ' dokumen, ' . $import->getSkippedCount() . ' baris dilewati.');

        if ($session !== null) {
            $this->line("Sesi import #{$session->id} tercatat (file " . basename($path) . ').');
        }

        if ($dryRun) {
            $this->comment('Jalankan tanpa --dry-run untuk menerapkan.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{title:string,isItemLayout:bool,headerOk:bool}
     */
    private function detectFormat(string $path): array
    {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($path)->getSheet(0);

            $cells = [];
            foreach (range('A', 'H') as $col) {
                foreach ([1, 2, 3] as $row) {
                    $value = $sheet->getCell($col . $row)->getValue();
                    if (is_string($value) && trim($value) !== '') {
                        $cells[] = trim($value);
                    }
                }
            }

            $titleText = implode(' | ', $cells);
            $isItemLayout = stripos($titleText, 'Detail Sales Per Item') !== false;
            $isInvoiceLayout = stripos($titleText, 'Sales By Customer Detail') !== false;

            // Header data: Item No. di J6, Item Description di L5/L6 (variasi export Accurate).
            $headerOk = str_contains(strtolower((string) $sheet->getCell('J6')->getValue()), 'item no')
                && (str_contains(strtolower((string) $sheet->getCell('L5')->getValue()), 'item description')
                    || str_contains(strtolower((string) $sheet->getCell('L6')->getValue()), 'item description'));

            return [
                'title' => $isInvoiceLayout ? 'Sales By Customer Detail (per invoice)' : ($isItemLayout ? 'Detail Sales Per Item' : $titleText),
                'isItemLayout' => $isItemLayout && ! $isInvoiceLayout,
                'headerOk' => $headerOk,
            ];
        } catch (\Throwable $e) {
            return ['title' => '', 'isItemLayout' => true, 'headerOk' => false];
        }
    }
}
