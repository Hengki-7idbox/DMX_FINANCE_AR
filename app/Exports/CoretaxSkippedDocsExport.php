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
 * Sheet rincian dokumen yang dilewati saat export XML Coretax.
 */
class CoretaxSkippedDocsExport implements FromArray, WithHeadings, WithColumnFormatting, WithStyles, WithTitle
{
    /** @param array<int,array<string,mixed>> $rows */
    public function __construct(private readonly array $rows)
    {
    }

    public function title(): string
    {
        return 'Dokumen Dilewati';
    }

    public function headings(): array
    {
        return [
            'No. Invoice',
            'Tanggal',
            'Customer No.',
            'Customer Name',
            'NIK/NPWP Saat Ini',
            'NITKU',
            'Alasan Dilewati',
        ];
    }

    public function array(): array
    {
        $out = [];

        foreach ($this->rows as $row) {
            $out[] = [
                (string) ($row['doc'] ?? ''),
                (string) ($row['date'] ?? ''),
                (string) ($row['customer_code'] ?? ''),
                (string) ($row['customer_name'] ?? ''),
                (string) ($row['tax_number'] ?? ''),
                (string) ($row['nitku'] ?? ''),
                implode(' ', $row['reasons'] ?? []),
            ];
        }

        return $out;
    }

    /**
     * Paksa kolom NIK/NPWP & NITKU sebagai text agar angka 16/22 digit tidak
     * kehilangan nol di depan atau berubah jadi notasi ilmiah.
     */
    public function columnFormats(): array
    {
        return [
            'E' => '@',
            'F' => '@',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->rows) + 1;
        $border = ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']]];
        $fill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']];

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => $fill,
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => $border,
        ]);

        if ($lastRow >= 2) {
            $sheet->getStyle('A2:G' . $lastRow)->applyFromArray(['borders' => $border]);
            $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setWrapText(true);
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        return [];
    }
}
