<?php

namespace Tests\Unit;

use App\Support\DateParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateParserTest extends TestCase
{
    #[DataProvider('tanggalProvider')]
    public function test_parse_tanggal_accurately(string $raw, ?string $expected): void
    {
        $this->assertSame($expected, DateParser::parseString($raw));
    }

    public static function tanggalProvider(): array
    {
        return [
            // Konvensi Indonesia/Accurate: d/m/Y, bukan m/d/Y.
            'd/m/Y oktober' => ['12/10/2026', '2026-10-12'],
            'd/m/Y november' => ['06/11/2026', '2026-11-06'],
            'd/m/Y hari > 12' => ['27/10/2026', '2026-10-27'],
            'strip' => ['12-10-2026', '2026-10-12'],
            // Bulan Indonesia.
            'bulan Indonesia' => ['02 Des 2025', '2025-12-02'],
            'apostrophe text-format' => ["'02 Des 2025", '2025-12-02'],
            // Format ISO.
            'ISO' => ['2026-10-12', '2026-10-12'],
            // Serial date Excel.
            'excel serial' => ['46300', '2026-10-05'],
            // Tanggal tidak valid -> null (bukan tanggal ngawur).
            '31 Februari' => ['31/02/2026', null],
            'kosong' => ['', null],
            'bukan tanggal' => ['bukan tanggal', null],
        ];
    }
}
