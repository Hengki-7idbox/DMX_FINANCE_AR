<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Global Report (export Excel) — outstanding invoice 2024 ke bawah.
 *
 * Kolom: Company Code | Company Name | CR. Term | 2024++ | Balance | Last Pay Date
 */
class ChinaGlobalExport implements FromArray, WithHeadings, WithStyles
{
    /** @var array<int, array<int, mixed>> */
    private array $rows = [];

    /** @var array<int, int> nomor baris (1-based) yang perlu bold + fill */
    private array $styledRows = [];

    private const LAST_COL = 6;

    public function __construct(array $data)
    {
        foreach ($data['rows'] as $r) {
            $this->push([
                $r['code'],
                $r['name'],
                $r['term'],
                $r['amount'],
                $r['balance'],
                '-',
            ]);
        }

        $this->push(['', 'GRAND TOTAL', '', $data['grand']['amount'], $data['grand']['balance'], ''], true);
    }

    private function push(array $cells, bool $style = false): void
    {
        $this->rows[] = $cells;
        if ($style) {
            $this->styledRows[] = count($this->rows) + 1; // +1: headings menempati baris 1
        }
    }

    public function headings(): array
    {
        return ['Company Code', 'Company Name', 'CR. Term', '2024++', 'Balance', 'Last Pay Date'];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->rows) + 1;
        $lastCol = Coordinate::stringFromColumnIndex(self::LAST_COL);

        $border = ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']]];
        $fill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']];

        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => $fill,
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => $border,
        ]);

        if ($lastRow >= 2) {
            $sheet->getStyle('A2:' . $lastCol . $lastRow)->applyFromArray(['borders' => $border]);
        }

        foreach ($this->styledRows as $row) {
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => $fill,
                'borders' => $border,
            ]);
        }

        // Format angka: kolom 2024++ dan Balance (D..E).
        $sheet->getStyle('D2:E' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        foreach (range(1, self::LAST_COL) as $i) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $sheet->freezePane('C2');

        return [];
    }
}
