<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Aging "Standard Report" (export Excel) — kolom mengikuti Sample/Aging Standard.xlsx:
 * Company Code | Company Name | Suspanded | Credit Limit |
 * Not Yet Due | COD | 1-30 Days | 31-60 Days | 61-90 Days | 91-120 Days | > 120 Days | Total
 */
class StandardReportExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(
        private readonly array $rows,
        private readonly array $grand,
        private readonly int $year,
    ) {
    }

    public function headings(): array
    {
        return [
            'Company Code', 'Company Name', 'Suspanded', 'Credit Limit',
            'Not Yet Due', 'COD', '1-30 Days', '31-60 Days', '61-90 Days',
            '91-120 Days', '> 120 Days', 'Total',
        ];
    }

    public function array(): array
    {
        $out = [];

        foreach ($this->rows as $r) {
            $out[] = [
                $r['code'],
                $r['name'],
                $r['suspended'],
                $r['credit'] === null ? '-' : (float) $r['credit'],
                $r['not_yet_due'],
                $r['cod'],
                $r['d1_30'],
                $r['d31_60'],
                $r['d61_90'],
                $r['d91_120'],
                $r['d120'],
                $r['total'],
            ];
        }

        $out[] = [
            'Grand Total', '', '', $this->grand['credit'],
            $this->grand['not_yet_due'], $this->grand['cod'], $this->grand['d1_30'],
            $this->grand['d31_60'], $this->grand['d61_90'], $this->grand['d91_120'],
            $this->grand['d120'], $this->grand['total'],
        ];

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $dataEnd = count($this->rows) + 1; // header = row 1, data mulai baris 2
        $grandRow = $dataEnd + 1;

        $border = ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']]];
        $fill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']];

        // Header: bold + background + border + rata tengah
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => $fill,
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => $border,
        ]);

        // Area data: border semua sisi + format angka + kolom Total bold
        if ($dataEnd >= 2) {
            $sheet->getStyle('A2:L' . $dataEnd)->applyFromArray(['borders' => $border]);
            $sheet->getStyle('D2:L' . $dataEnd)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('L2:L' . $dataEnd)->getFont()->setBold(true);
        }

        // Grand Total: bold + background + border + format angka
        $sheet->getStyle('A' . $grandRow . ':L' . $grandRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => $fill,
            'borders' => $border,
        ]);
        $sheet->getStyle('D' . $grandRow . ':L' . $grandRow)->getNumberFormat()->setFormatCode('#,##0');

        // Lebar kolom otomatis + freeze header
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        return [];
    }
}
