<?php

namespace Database\Seeders;

use App\Models\MappingProduct;
use Illuminate\Database\Seeder;

/**
 * Dua item internal yang dipakai 68 dokumen penerimaan baru tetapi belum
 * ada di mapping_products.
 */
class MappingProductReceiveItemSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'kode_item_internal' => 'LR009.2',
                'kode_item_external' => 'W017.1',
                'nama_item_internal' => 'Ubi Ungu - 1kg',
                'nama_item_external' => 'Ubi Ungu - 1kg',
            ],
            [
                'kode_item_internal' => 'LR009.4.1',
                'kode_item_external' => 'W017.1.1',
                'nama_item_internal' => 'Ubi Cilembu - 1kg',
                'nama_item_external' => 'Ubi Cilembu - 1kg',
            ],
        ];

        foreach ($products as $product) {
            MappingProduct::firstOrCreate(
                ['kode_item_internal' => $product['kode_item_internal']],
                $product
            );
        }
    }
}
