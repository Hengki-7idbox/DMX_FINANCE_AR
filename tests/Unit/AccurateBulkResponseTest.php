<?php

namespace Tests\Unit;

use App\Support\AccurateBulkResponse;
use PHPUnit\Framework\TestCase;

class AccurateBulkResponseTest extends TestCase
{
    public function test_membaca_daftar_hasil_langsung(): void
    {
        $body = [
            ['s' => true, 'd' => ['id' => 10, 'number' => 'DMXS1']],
            ['s' => false, 'd' => ['Nomor dokumen sudah dipakai.']],
        ];

        $rows = AccurateBulkResponse::rows($body);

        $this->assertIsArray($rows);
        $this->assertCount(2, $rows);
        $this->assertTrue($rows[0]['s']);
        $this->assertFalse($rows[1]['s']);
    }

    public function test_membaca_hasil_yang_dibungkus_kunci_d(): void
    {
        $body = ['d' => [
            ['s' => true, 'd' => ['id' => 1]],
            ['s' => true, 'd' => ['id' => 2]],
        ]];

        $rows = AccurateBulkResponse::rows($body);

        $this->assertIsArray($rows);
        $this->assertCount(2, $rows);
    }

    public function test_membaca_satu_dokumen_tanpa_pembungkus(): void
    {
        $body = ['s' => true, 'd' => ['id' => 7, 'number' => 'DMXS7']];

        $rows = AccurateBulkResponse::rows($body);

        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);
        $this->assertSame('DMXS7', $rows[0]['d']['number']);
    }

    public function test_bentuk_tidak_dikenal_mengembalikan_null(): void
    {
        $this->assertNull(AccurateBulkResponse::rows(['raw' => '<html>error</html>']));
        $this->assertNull(AccurateBulkResponse::rows([]));
    }

    public function test_hasil_dipetakan_sesuai_urutan_payload(): void
    {
        $rows = [
            ['s' => true, 'd' => ['id' => 1, 'number' => 'DMXS1']],
            ['s' => false, 'd' => ['Satuan PCS tidak ada.']],
        ];

        $results = AccurateBulkResponse::results($rows, 3, 200);

        $this->assertCount(3, $results);
        $this->assertTrue($results[0]['ok']);
        $this->assertSame('DMXS1', $results[0]['doc']['number']);
        $this->assertNull($results[0]['message']);

        $this->assertFalse($results[1]['ok']);
        $this->assertSame('Satuan PCS tidak ada.', $results[1]['message']);
        $this->assertSame([], $results[1]['doc']);

        // Baris ketiga tidak ada di respons -> dianggap gagal, bukan sukses.
        $this->assertFalse($results[2]['ok']);
        $this->assertStringContainsString('tidak ada di respons', (string) $results[2]['message']);
    }

    public function test_dokumen_tersimpan_dibaca_dari_kunci_r(): void
    {
        // Bentuk resmi bulk-save: pesan di `d`, dokumen tersimpan di `r`.
        $rows = [[
            's' => true,
            'd' => 'Pesanan Penjualan "DMXS1" berhasil disimpan',
            'r' => ['id' => 32408, 'number' => 'DMXS1'],
        ]];

        $results = AccurateBulkResponse::results($rows, 1, 200);

        $this->assertTrue($results[0]['ok']);
        $this->assertSame(32408, $results[0]['doc']['id']);
        $this->assertSame('DMXS1', $results[0]['doc']['number']);
    }

    public function test_pesan_error_diratakan_dari_list_bertingkat(): void
    {
        $rows = [['s' => false, 'd' => [['Pajak tidak ditemukan'], ['Periksa master item']]]];

        $results = AccurateBulkResponse::results($rows, 1, 200);

        $this->assertFalse($results[0]['ok']);
        $this->assertStringContainsString('Pajak tidak ditemukan', (string) $results[0]['message']);
        $this->assertStringContainsString('Periksa master item', (string) $results[0]['message']);
    }

    public function test_dokumen_sukses_tetap_dianggap_sukses_walau_ada_baris_kurang(): void
    {
        $rows = [['s' => true, 'd' => ['id' => 5, 'number' => 'DMXS5']]];

        $results = AccurateBulkResponse::results($rows, 2, 200);

        $this->assertTrue($results[0]['ok']);
        $this->assertFalse($results[1]['ok']);
    }
}
