<?php

namespace App\Services;

use App\Models\CoretaxItemCode;
use App\Models\MappingProduct;

class CoretaxItemCodeSync
{
    /**
     * @return array{created:int,updated:int,skipped:int,products:int}
     */
    public function importWorkbook(string $path): array
    {
        $rows = SpreadsheetSheetReader::rows(
            $path,
            ['Efaktur Code'],
            ['Kode Barang Jasa', 'Nama Barang/Jasa']
        );

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $code = (string) preg_replace('/\D/', '', trim((string) ($row['KODE BARANG JASA'] ?? '')));
            $name = trim((string) ($row['NAMA BARANG/JASA'] ?? ''));

            if (strlen($code) !== 6 || $name === '') {
                $skipped++;
                continue;
            }

            $normalized = CoretaxItemCode::normalizeName($name);
            if ($normalized === '') {
                $skipped++;
                continue;
            }

            $existing = CoretaxItemCode::query()->where('name_norm', $normalized)->first();

            if ($existing !== null) {
                if ($existing->code !== $code || $existing->name !== $name) {
                    $existing->fill(['code' => $code, 'name' => $name])->save();
                    $updated++;
                }
                continue;
            }

            CoretaxItemCode::create([
                'code' => $code,
                'name' => $name,
                'name_norm' => $normalized,
            ]);
            $created++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'products' => $this->backfillMappingProducts(),
        ];
    }

    public function backfillMappingProducts(): int
    {
        $codes = CoretaxItemCode::query()->pluck('code', 'name_norm')->all();
        if ($codes === []) {
            return 0;
        }

        $changed = 0;

        foreach (MappingProduct::query()->get(['id', 'code_coretax', 'nama_item_external', 'nama_item_internal']) as $product) {
            if (trim((string) $product->code_coretax) !== '') {
                continue;
            }

            $code = $codes[CoretaxItemCode::normalizeName($product->nama_item_external)] ?? null;

            if ($code === null) {
                $code = $codes[CoretaxItemCode::normalizeName($product->nama_item_internal)] ?? null;
            }
            if ($code !== null) {
                $product->code_coretax = $code;
                $product->save();
                $changed++;
            }
        }

        return $changed;
    }
}
