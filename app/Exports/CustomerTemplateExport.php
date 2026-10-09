<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template import customer mengikuti format export Accurate Online
 * (lihat Sample/ExportFile.xls):
 *
 * Row 1: Nama perusahaan
 * Row 2: Export data - Customer List
 * Row 3: As of <tanggal>
 * Row 4: kosong
 * Row 5: "Customer Type Name" di kolom F
 * Row 6: header kolom (sparse — kolom ganjil sering kosong)
 * Row 7+: data
 *
 * Index kolom (0-based / array) harus sama dengan import Accurate:
 * 1=Customer No., 3=Customer Name, 5=Customer Type Name,
 * 7=Phone, 9=Contact Name, 11=Balance Prime, 13=Total Data,
 * 15=Address 1, 17=Address 2, 19=City, 21=E-mail, 23=Credit Limit
 */
class CustomerTemplateExport implements FromArray, WithStyles
{
    private const LAST_COLUMN_INDEX = 23; // X

    public function array(): array
    {
        $rows = [];

        $rows[0] = $this->row(0, [
            0 => config('app.name', 'Finance AR'),
        ]);

        $rows[1] = $this->row(1, [
            0 => 'Export data - Customer List',
        ]);

        $rows[2] = $this->row(2, [
            0 => 'As of ' . now()->format('d M Y'),
        ]);

        $rows[3] = $this->row(3, []);

        $rows[4] = $this->row(4, [
            5 => 'Customer Type Name',
        ]);

        $rows[5] = $this->row(5, [
            1 => 'Customer No.',
            3 => 'Customer Name',
            7 => 'Phone',
            9 => 'Contact Name',
            11 => 'Balance Prime',
            13 => 'Total Data',
            15 => 'Address 1',
            17 => 'Address 2',
            19 => 'City',
            21 => 'E-mail',
            23 => 'Credit Limit',
        ]);

        $rows[6] = $this->row(6, [
            1 => '2016 - 001',
            3 => 'PT Contoh Indonesia',
            5 => 'FRUIT',
            7 => '081234567890',
            9 => 'Budi : 081234567890',
            11 => '0,00',
            13 => '1,00',
            15 => 'Jl. Contoh No. 1',
            17 => 'Kompleks Contoh Blok A',
            19 => 'Jakarta',
            21 => 'contoh@email.com',
            23 => '0,00',
        ]);

        $rows[7] = $this->row(7, [
            1 => '2016 - 002',
            3 => 'CV Contoh Sejahtera',
            5 => 'SUPERMARKET',
            7 => '021-5551234',
            9 => 'Sari : 08111111111',
            11 => '0,00',
            13 => '1,00',
            15 => 'Jl. Merdeka No. 2',
            19 => 'Bekasi',
            21 => 'sari@contoh.co.id',
            23 => '0,00',
        ]);

        return $rows;
    }

    /**
     * @param array<int, mixed> $cells 0-based index => value
     */
    private function row(int $rowIndex, array $cells): array
    {
        $row = array_fill(0, self::LAST_COLUMN_INDEX + 1, null);

        foreach ($cells as $index => $value) {
            $row[$index] = $value;
        }

        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            3 => ['font' => ['italic' => true]],
            6 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E2F3'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
