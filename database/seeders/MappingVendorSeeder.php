<?php

namespace Database\Seeders;

use App\Models\MappingVendor;
use Illuminate\Database\Seeder;

/**
 * Pemetaan kode vendor internal (XML Exim) ke kode vendor Accurate.
 *
 * Sumber: 63 dokumen receive item yang sudah ada di Accurate (Oktober 2026).
 * Baris yang sudah punya kode external tidak ditimpa, jadi aman dijalankan ulang.
 */
class MappingVendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            ['V.CHINA-002', 'V.IMPORT-001', 'SHENZHEN MAOXIONG'],
            ['V.CHINA-071', 'V.IMPORT-133', 'GANSU MAOXIONG LINGXIAN AGRICULTURAL TECHNOLOGY CO.,LTD'],
            ['V.CHINA-111', 'V.IMPORT-021', 'HUNAN DUOYING AGRICULTURAL SCIENCE AND TECHNOLOGY CO.,LTD'],
            ['V.CHINA-084', 'V.IMPORT-146', 'YUNNAN LEHUI FARM PRODUCE CO.,LTD'],
            ['V.LOKAL-119', 'V.LOKAL-138', 'YAMIN'],
            ['V.CHINA-092', 'V.IMPORT-147', 'SHENZHEN PINHEHUI AGRICULTURAL DEVELOPMENT CO.,LTD'],
            ['V.IMPORTIR-001', 'V.LOKAL-007', 'PT. SAKTI ABADI KILAT'],
            ['V.LOKAL-120', 'V.LOKAL-139', 'PAK TOTOK'],
            ['V.LOKAL-083', 'V.LOKAL-100', 'PT. GRAHA INMA SINAR TRIKARSA'],
            ['V.SEWA-038', 'V.LOKAL-025', 'PT. BANDAR BUAH'],
            ['V.LOKAL-133', 'V.LOKAL-154', 'PT PERTANIAN TEKNOLOGI GENGTUO ZHENPIN'],
            ['V.CHINA-079', 'V.IMPORT-139', 'SICHUAN NINGXIN AGRICULTURAL CO.,LTD'],
            ['V.CHINA-080', 'V.IMPORT-180', null],
            ['V.CHINA-088', 'V.IMPORT-172', 'WENZHOU BASA TRADING CO.,LTD'],
            ['V.LOKAL-142', 'V.LOKAL-165', null],
            ['V.LOKAL-117', 'V.LOKAL-136', 'PT INDO PANGAN SUKSES BERSAMA'],
            ['V.CHINA-056', 'V.IMPORT-116', 'HUBEI ZHIWEI INTERNATIONAL TRADE CO.,LTD'],
            ['V.CHINA-101', 'V.IMPORT-157', 'GUOLI (HAINAN) IMPORT AND EXPORT CO.,LTD'],
            ['V.CHINA-076', 'V.IMPORT-137', 'HAINAN FOSUN TRADING CO.,LTD'],
            ['V.LOKAL-140', 'V.LOKAL-161', 'CV. MULYAFAMILY'],
            ['V.CHINA-063', 'V.IMPORT-121', 'SHAANXI QIFENG IKIWI IMPORT & EXPORT CO.,LTD'],
            ['V.CHINA-078', 'V.IMPORT-141', 'NINGXIA LEYI MODERN AGRICULTURE CO.,LTD'],
        ];

        foreach ($vendors as [$internal, $external, $name]) {
            $vendor = MappingVendor::firstOrNew(['kode_vendor_internal' => $internal]);

            if (! $vendor->exists || ! $vendor->kode_vendor_external) {
                $vendor->kode_vendor_external = $external;
                $vendor->nama_vendor_external = $name;
                $vendor->save();
            }
        }
    }
}
