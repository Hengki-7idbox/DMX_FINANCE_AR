<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * China Report (export Excel) — matriks per kategori termin × bulan invoice.
 *
 * Blok 1: Company Name | CR. Term | <9 bulan...> | <catch-all> | Balance | Last Pay Date
 *         dengan judul section + baris Subtotal per section + GRAND TOTAL.
 * Blok 2: Recap kategori × bulan (nilai + persentase).
 */
class ChinaReportExport implements FromArray, WithHeadings, WithStyles
{
    /** @var array<int, array<int, mixed>> */
    private array $rows = [];

    /** @var array<int, int> nomor baris (1-based) yang perlu bold + fill */
    private array $styledRows = [];

    private bool $recapBlock = false;

    private int $lastCol = 14;

    /** @var array<int, string> */
    private array $headings = [];

    public function __construct(array $data)
    {
        $months = $data['months'];
        $catch = $data['catch'];
        $this->lastCol = 4 + count($months);

        $this->headings = ['Company Name', 'CR. Term'];
        foreach ($months as $m) {
            $this->headings[] = $m['label'];
        }
        $this->headings[] = $catch['label'];
        $this->headings[] = 'Balance';
        $this->headings[] = 'Last Pay Date';

        foreach ($data['sections'] as $section) {
            $this->push(array_merge([$section['title']], array_fill(0, $this->lastCol - 1, '')), true);

            foreach ($section['rows'] as $r) {
                $cells = [$r['name'], $r['term']];
                foreach ($months as $m) {
                    $cells[] = $r['months'][$m['key']];
                }
                $cells[] = $r['catch'];
                $cells[] = $r['balance'];
                $cells[] = '-';
                $this->push($cells);
            }

            $cells = ['Subtotal', ''];
            foreach ($months as $m) {
                $cells[] = $section['subtotal']['months'][$m['key']];
            }
            $cells[] = $section['subtotal']['catch'];
            $cells[] = $section['subtotal']['balance'];
            $cells[] = '';
            $this->push($cells, true);

            $this->push(array_fill(0, $this->lastCol, ''));
        }

        if (! empty($data['sections'])) {
            $cells = ['GRAND TOTAL', ''];
            foreach ($months as $m) {
                $cells[] = $data['grand']['months'][$m['key']];
            }
            $cells[] = $data['grand']['catch'];
            $cells[] = $data['grand']['balance'];
            $cells[] = '';
            $this->push($cells, true);

            $this->push(array_fill(0, $this->lastCol, ''));
        }

        if (! empty($data['recap'])) {
            // Recap memakai tata letak kolom sendiri: nilai + % berdampingan.
            $recapCols = 2 + (count($months) + 1) * 2 + 1; // label + spacer + (bulan+catch)*2 + total
            $this->push(array_merge(['Recap'], array_fill(0, $recapCols - 1, '')), true);

            $header = ['Kategori', ''];
            foreach ($months as $m) {
                $header[] = $m['label'];
                $header[] = '%';
            }
            $header[] = $catch['label'];
            $header[] = '%';
            $header[] = 'Total';
            $this->push($header, true);

            foreach ($data['recap'] as $r) {
                $cells = [$r['label'], ''];
                foreach ($months as $m) {
                    $cells[] = $r['months'][$m['key']];
                    $cells[] = $r['months'][$m['key']] > 0 ? $r['pct'][$m['key']] / 100 : null;
                }
                $cells[] = $r['catch'];
                $cells[] = $r['catch'] > 0 && $data['grand']['catch'] > 0 ? $r['catch'] / $data['grand']['catch'] : null;
                $cells[] = $r['total'];
                $this->push($cells);
            }

            $cells = ['Total', ''];
            foreach ($months as $m) {
                $cells[] = $data['grand']['months'][$m['key']];
                $cells[] = null;
            }
            $cells[] = $data['grand']['catch'];
            $cells[] = null;
            $cells[] = $data['grand']['balance'];
            $this->push($cells, true);

            $this->recapBlock = true;
        }
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
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->rows) + 1;
        $lastCol = Coordinate::stringFromColumnIndex(max($this->lastCol, 23));

        $border = ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']]];
        $fill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']];

        // Baris heading utama.
        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($this->lastCol) . '1')->applyFromArray([
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

        // Format angka: kolom bulan s/d Balance (C..M) pada tabel utama.
        $numStart = Coordinate::stringFromColumnIndex(3);
        $numEnd = Coordinate::stringFromColumnIndex($this->lastCol - 1);
        $sheet->getStyle($numStart . '2:' . $numEnd . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        // Recap: kolom nilai (ganjil) angka, kolom persentase format %.
        if ($this->recapBlock) {
            $recapStart = null;
            foreach ($this->rows as $i => $cells) {
                if (($cells[0] ?? null) === 'Kategori') {
                    $recapStart = $i + 2; // +1 heading, +1 offset 1-based
                    break;
                }
            }
            if ($recapStart !== null) {
                $pctCols = [];
                for ($col = 4; $col <= 22; $col += 2) {
                    $pctCols[] = Coordinate::stringFromColumnIndex($col);
                }
                foreach ($pctCols as $col) {
                    $sheet->getStyle($col . $recapStart . ':' . $col . $lastRow)
                        ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                }
                $valCols = [];
                for ($col = 3; $col <= 23; $col += 2) {
                    $valCols[] = Coordinate::stringFromColumnIndex($col);
                }
                foreach ($valCols as $col) {
                    $sheet->getStyle($col . $recapStart . ':' . $col . $lastRow)
                        ->getNumberFormat()->setFormatCode('#,##0');
                }
            }
        }

        foreach (range(1, max($this->lastCol, 23)) as $i) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $sheet->freezePane('C2');

        return [];
    }
}
