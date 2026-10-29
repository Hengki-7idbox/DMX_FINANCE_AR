<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel tab DB (Outstanding) & Anomali Piutang.
 * Kolom mengikuti tabel di halaman /invoices/outstanding dan /invoices/anomali.
 */
class OutstandingExport implements FromArray, WithHeadings, WithStyles
{
    /** @param array<int,array<string,mixed>> $rows */
    public function __construct(private readonly array $rows)
    {
    }

    public function headings(): array
    {
        return [
            'Customer No', 'Customer Name', 'Suspended', 'Terms',
            'Invoice No', 'Invoice Date', 'Due Date', 'Invoice Amount',
            'Prime Owing', 'Tax Owing', 'Age From Due', 'Credit Limit',
            'Exception Type', 'Missing Count', 'Tukar Faktur',
        ];
    }

    public function array(): array
    {
        $labels = [
            'anomali' => 'Anomali',
            'biaya_bank' => 'Biaya Bank',
            'sudah_bayar' => 'Sudah Bayar Belum Proses',
        ];

        $tukarLabels = [
            'sudah' => 'Sudah',
            'belum' => 'Belum',
            'tidak_ada' => 'Tidak Ada',
        ];

        $out = [];

        foreach ($this->rows as $r) {
            $out[] = [
                $r['customer_no'],
                $r['customer_name'],
                $r['customer_suspended'],
                $r['terms_net_days'],
                $r['invoice_no'],
                $r['invoice_date'],
                $r['due_date'],
                (float) $r['invoice_amount'],
                (float) $r['prime_owing'],
                (float) $r['tax_owing'],
                (int) $r['age_from_due'],
                (float) $r['credit_limit'],
                $labels[$r['exception_type']] ?? ($r['exception_type'] ?? '-'),
                (int) $r['missing_count'],
                $tukarLabels[$r['tukar_faktur']] ?? '-',
            ];
        }

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $dataEnd = count($this->rows) + 1; // header baris 1, data mulai baris 2

        $border = ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']]];
        $fill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']];

        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => $fill,
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => $border,
        ]);

        if ($dataEnd >= 2) {
            $sheet->getStyle('A2:O' . $dataEnd)->applyFromArray(['borders' => $border]);
            $sheet->getStyle('H2:L' . $dataEnd)->getNumberFormat()->setFormatCode('#,##0');
        }

        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        return [];
    }
}
