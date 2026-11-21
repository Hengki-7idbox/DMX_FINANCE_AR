<?php

namespace Tests\Feature;

use App\Models\FakturPajak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakturPajakClearTest extends TestCase
{
    use RefreshDatabase;

    private function makeRow(array $overrides = []): FakturPajak
    {
        return FakturPajak::create(array_merge([
            'tax_invoice_number' => '01000000000000001',
            'tax_invoice_date' => '2026-10-05',
            'tax_invoice_period' => 'Oktober',
            'tax_invoice_year' => 2026,
            'selling_price' => 1000,
            'other_tax_base' => 900,
            'vat' => 100,
            'reference' => 'DMXS26100001',
        ], $overrides));
    }

    public function test_clear_per_bulan_only_deletes_that_period(): void
    {
        $this->actingAs(User::factory()->create());

        $oktober = $this->makeRow(['tax_invoice_number' => 'A1']);
        $november = $this->makeRow([
            'tax_invoice_number' => 'A2',
            'tax_invoice_date' => '2026-11-05',
            'tax_invoice_period' => 'November',
        ]);

        $this->delete(route('faktur-pajak.clear'), [
            'mode' => 'bulan',
            'bulan' => 10,
            'tahun' => 2026,
        ])
            ->assertRedirect(route('faktur-pajak.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('faktur_pajaks', ['id' => $oktober->id]);
        $this->assertDatabaseHas('faktur_pajaks', ['id' => $november->id]);
        $this->assertDatabaseCount('faktur_pajaks', 1);
    }

    public function test_clear_per_bulan_uses_period_not_invoice_date(): void
    {
        $this->actingAs(User::factory()->create());

        // Tanggal Februari tapi periode Januari — harus ikut terhapus saat pilih Januari.
        $row = $this->makeRow([
            'tax_invoice_number' => 'B1',
            'tax_invoice_date' => '2026-02-28',
            'tax_invoice_period' => 'Januari',
            'tax_invoice_year' => 2026,
        ]);

        $this->delete(route('faktur-pajak.clear'), [
            'mode' => 'bulan',
            'bulan' => 1,
            'tahun' => 2026,
        ])->assertSessionHas('success');

        $this->assertDatabaseMissing('faktur_pajaks', ['id' => $row->id]);
    }

    public function test_clear_per_tanggal_only_deletes_rows_inside_range(): void
    {
        $this->actingAs(User::factory()->create());

        $inside = $this->makeRow(['tax_invoice_number' => 'C1', 'tax_invoice_date' => '2026-10-05']);
        $outside = $this->makeRow(['tax_invoice_number' => 'C2', 'tax_invoice_date' => '2026-10-20']);

        $this->delete(route('faktur-pajak.clear'), [
            'mode' => 'tanggal',
            'dari' => '2026-10-01',
            'sampai' => '2026-10-10',
        ])->assertSessionHas('success');

        $this->assertDatabaseMissing('faktur_pajaks', ['id' => $inside->id]);
        $this->assertDatabaseHas('faktur_pajaks', ['id' => $outside->id]);
    }

    public function test_clear_periode_kosong_tidak_menghapus_apa_pun(): void
    {
        $this->actingAs(User::factory()->create());

        $row = $this->makeRow(['tax_invoice_number' => 'D1']);

        $this->delete(route('faktur-pajak.clear'), [
            'mode' => 'bulan',
            'bulan' => 12,
            'tahun' => 2026,
        ])
            ->assertRedirect(route('faktur-pajak.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('faktur_pajaks', ['id' => $row->id]);
        $this->assertDatabaseCount('faktur_pajaks', 1);
    }

    public function test_clear_tanggal_terbalik_ditolak(): void
    {
        $this->actingAs(User::factory()->create());

        $row = $this->makeRow(['tax_invoice_number' => 'E1']);

        $this->delete(route('faktur-pajak.clear'), [
            'mode' => 'tanggal',
            'dari' => '2026-10-10',
            'sampai' => '2026-10-01',
        ])->assertSessionHasErrors('sampai');

        $this->assertDatabaseHas('faktur_pajaks', ['id' => $row->id]);
    }

    public function test_clear_tanpa_periode_ditolak(): void
    {
        $this->actingAs(User::factory()->create());

        $this->makeRow(['tax_invoice_number' => 'F1']);

        $this->delete(route('faktur-pajak.clear'), [
            'mode' => 'bulan',
        ])->assertSessionHasErrors(['bulan', 'tahun']);

        $this->assertDatabaseCount('faktur_pajaks', 1);
    }

    public function test_mode_tidak_dikenal_ditolak(): void
    {
        $this->actingAs(User::factory()->create());

        $this->makeRow(['tax_invoice_number' => 'G1']);

        $this->delete(route('faktur-pajak.clear'), [
            'mode' => 'semua',
        ])->assertSessionHasErrors('mode');

        $this->assertDatabaseCount('faktur_pajaks', 1);
    }

    public function test_halaman_menampilkan_modal_pilih_periode(): void
    {
        $this->actingAs(User::factory()->create());

        $this->makeRow(['tax_invoice_number' => 'H1']);

        $this->get(route('faktur-pajak.index'))
            ->assertOk()
            ->assertSee('Hapus Data Faktur Pajak')
            ->assertSee('Per Bulan')
            ->assertSee('Per Tanggal')
            ->assertDontSee('Hapus Semua Data');
    }
}
