<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export Excel dokumen Coretax yang dilewati:
 *  - Sheet "Dokumen Dilewati" : rincian per invoice + alasannya.
 *  - Sheet "Customer eksternal": daftar unik customer (PERSONNO + kolom
 *    TAX1EXEMPTIONNO kosong) yang bisa langsung diisi NIK lalu diimpor lewat
 *    menu Customers → Import NPWP.
 */
class CoretaxSkippedExport implements WithMultipleSheets
{
    /**
     * @param  array<int,array<string,mixed>>  $documents
     * @param  array<int,array<string,mixed>>  $customers
     */
    public function __construct(
        private readonly array $documents,
        private readonly array $customers
    ) {
    }

    public function sheets(): array
    {
        return [
            new CoretaxSkippedDocsExport($this->documents),
            new CoretaxSkippedCustomersExport($this->customers),
        ];
    }
}
