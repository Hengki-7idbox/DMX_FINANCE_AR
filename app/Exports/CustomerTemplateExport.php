<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'customer_code',
            'customer_name',
            'group_code',
            'group_name',
            'customer_type',
            'tax_number',
            'address',
            'city',
            'phone',
            'email',
            'payment_term',
            'credit_limit',
            'status',
            'notes',
        ];
    }

    public function array(): array
    {
        return [
            [
                'CUST-001',
                'PT Contoh Indonesia',
                'GRP-001',
                'Distributor',
                'CORPORATE',
                '01.234.567.8-901.000',
                'Jl. Contoh No. 1',
                'Jakarta',
                '081234567890',
                'contoh@email.com',
                'NET30',
                100000000,
                'ACTIVE',
                'Template import customer',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
