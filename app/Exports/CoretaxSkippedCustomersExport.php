<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet daftar customer unik dari dokumen yang dilewati.
 *
 * Nama sheet & kolom sengaja memakai format yang dibaca
 * CustomerTaxNumberSync: sheet "Customer eksternal" dengan header
 * PERSONNO + TAX1EXEMPTIONNO, sehingga file bisa langsung dipakai di
 * menu Customers → Import NPWP setelah kolom TAX1EXEMPTIONNO diisi NIK.
 */
class CoretaxSkippedCustomersExport implements FromArray, WithHeadings, WithColumnFormatting, WithStyles, WithTitle
{
    /** @param array<int,array<string,mixed>> $rows */
    public function __construct(private readonly array $rows)
    {
    }

    public function title(): string
    {
        return 'Customer eksternal';
    }

    public function headings(): array
    {
        return [
            'PERSONNO',
            'NAMA',
            'NIK/NPWP SAAT INI',
            'TAX1EXEMPTIONNO',
            'JUMLAH DOKUMEN',
            'ALASAN',
        ];
    }

    public function array(): array
    {
        $out = [];

        foreach ($this->rows as $row) {
            $out[] = [
                (string) ($row['code'] ?? ''),
                (string) ($row['name'] ?? ''),
                (string) ($row['tax'] ?? ''),
                '', // diisi NIK/NPWP 16 digit oleh user
                (int) ($row['count'] ?? 0),
                implode(' ', $row['reasons'] ?? []),
            ];
        }

        return $out;
    }

    /**
     * Kolom kode & NIK/NPWP harus text agar nol di depan tidak hilang.
     */
    public function columnFormats(): array
    {
        return [
            'A' => '@',
            'C' => '@',
            'D' => '@',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->rows) + 1;
        $border = ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']]];
        $fill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']];
        $fillInput = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']];

        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => $fill,
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => $border,
        ]);

        if ($lastRow >= 2) {
            $sheet->getStyle('A2:F' . $lastRow)->applyFromArray(['borders' => $border]);
            // Kolom isian NIK diberi warna agar mudah terlihat.
            $sheet->getStyle('D2:D' . $lastRow)->applyFromArray(['fill' => $fillInput]);
            $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setWrapText(true);
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        return [];
    }
}
