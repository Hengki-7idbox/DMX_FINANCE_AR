<?php

namespace Tests\Feature;

use App\Imports\FakturPajakImport;
use App\Models\FakturPajak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakturPajakDraftTest extends TestCase
{
    use RefreshDatabase;

    private function header(): array
    {
        return [
            'Buyer Tin', 'Display Name', 'Tax Invoice Code', 'Tax Invoice Number',
            'Tax Invoice Date', 'Tax Invoice Period', 'Tax Invoice Year',
            'Tax Invoice Status', 'E Sign Status', 'Selling Price', 'Other Tax Base',
            'Vat', 'Signer', 'Reference', 'Reported By Seller', 'Reported By Vat Collector',
        ];
    }

    private function row(array $overrides = []): array
    {
        $v = array_merge([
            'buyer_tin' => '0000000000000000',
            'display_name' => 'HERIYANTO',
            'code' => '08 - penyerahan dengan fasilitas dibebaskan PPN',
            'number' => '',
            'date' => '05/10/2026',
            'period' => 'Oktober',
            'year' => '2026',
            'status' => 'CREATED',
            'esign' => '',
            'sell' => '83250000',
            'other' => '76312500',
            'vat' => '9157500',
            'signer' => '',
            'ref' => 'DMXS26100368',
            'seller' => 'FALSE',
            'collector' => '',
        ], $overrides);

        return [
            $v['buyer_tin'], $v['display_name'], $v['code'], $v['number'], $v['date'],
            $v['period'], $v['year'], $v['status'], $v['esign'], $v['sell'],
            $v['other'], $v['vat'], $v['signer'], $v['ref'], $v['seller'], $v['collector'],
        ];
    }

    private function import(array $rows): FakturPajakImport
    {
        $import = new FakturPajakImport();
        $import->collection(collect(array_merge([$this->header()], $rows)));

        return $import;
    }

    public function test_draft_tanpa_nomor_tetap_tersimpan(): void
    {
        $import = $this->import([
            $this->row([
                'number' => '08002600449030430',
                'status' => 'APPROVED',
                'ref' => 'DMXS26101747',
            ]),
            $this->row(),
        ]);

        $this->assertSame(2, FakturPajak::count());
        $this->assertSame(1, $import->getDraftCount());

        $draft = FakturPajak::where('reference', 'DMXS26100368')->first();
        $this->assertNotNull($draft);
        $this->assertNull($draft->tax_invoice_number);
        $this->assertSame('CREATED', $draft->tax_invoice_status);
    }

    public function test_import_ulang_tidak_menggandakan_draft(): void
    {
        $this->import([$this->row()]);
        $this->import([$this->row()]);

        $this->assertSame(1, FakturPajak::count());
    }

    public function test_draft_terisi_nomor_saat_import_ulang(): void
    {
        $this->import([$this->row()]);
        $draft = FakturPajak::first();
        $this->assertNull($draft->tax_invoice_number);

        $this->import([$this->row([
            'number' => '08002600999999999',
            'status' => 'APPROVED',
        ])]);

        $this->assertSame(1, FakturPajak::count());

        $draft->refresh();
        $this->assertSame('08002600999999999', $draft->tax_invoice_number);
        $this->assertSame('APPROVED', $draft->tax_invoice_status);
    }

    public function test_clear_per_bulan_ikut_menghapus_draft(): void
    {
        $this->actingAs(User::factory()->create());

        $this->import([
            $this->row(['number' => '08002600449030430', 'status' => 'APPROVED', 'ref' => 'DMXS26101747']),
            $this->row(),
        ]);

        $this->delete(route('faktur-pajak.clear'), [
            'mode' => 'bulan',
            'bulan' => 10,
            'tahun' => 2026,
        ])->assertSessionHas('success');

        $this->assertSame(0, FakturPajak::count());
    }

    public function test_halaman_menampilkan_badge_belum_terbit(): void
    {
        $this->actingAs(User::factory()->create());

        $this->import([$this->row()]);

        $this->get(route('faktur-pajak.index'))
            ->assertOk()
            ->assertSee('Belum terbit');
    }
}
