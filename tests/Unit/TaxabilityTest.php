<?php

namespace Tests\Unit;

use App\Support\Taxability;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaxabilityTest extends TestCase
{
    #[DataProvider('kodeExternalProvider')]
    public function test_deteksi_customer_non_ppn(?string $kode, bool $expected): void
    {
        $this->assertSame($expected, Taxability::isNonPpnCustomer($kode));
    }

    public static function kodeExternalProvider(): array
    {
        return [
            // Kode external ber-TNPWP -> non-PPN.
            'TNPWP-030' => ['TNPWP-030', true],
            'huruf kecil' => ['tnpwp-1', true],
            'campur' => ['x-TnPwP', true],
            'spasi di tepi' => ['  TNPWP-006  ', true],
            // Kode lain tetap seperti sebelumnya (ber-PPN).
            'NPWP' => ['NPWP-133', false],
            'KTP' => ['KTP-193', false],
            'CASH internal' => ['CASH 002 - C', false],
            'kosong' => ['', false],
            'null' => [null, false],
        ];
    }
}
