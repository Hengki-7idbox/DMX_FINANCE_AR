<?php

namespace Tests\Feature;

use App\Http\Controllers\TaxXmlExternalController;
use App\Models\DetailSale;
use App\Models\FakturPajak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxXmlExternalFpFilterTest extends TestCase
{
    use RefreshDatabase;

    private function makeDoc(string $doc, ?string $fpStatus = null): DetailSale
    {
        return DetailSale::create([
            'source_type' => DetailSale::SOURCE_EXTERNAL,
            'doc_number' => $doc,
            'customer_code' => 'NPWP-112',
            'customer_name' => 'PT. SUPRA BOGA LESTARI Tbk',
            'transaction_date' => '2026-10-15',
            'description' => $doc,
            'invoice_amount' => 1110,
            'amount' => 1000,
            'tax_base' => 110,
            'discount' => 0,
            'return_amount' => 0,
            'owing' => 1110,
            'payment_amount' => 0,
            'calculate_field' => 1110,
            'fp_status' => $fpStatus,
        ]);
    }

    private function makeFp(string $reference, ?string $number): void
    {
        FakturPajak::create([
            'tax_invoice_number' => $number,
            'tax_invoice_date' => '2026-10-15',
            'tax_invoice_period' => 'Oktober',
            'tax_invoice_year' => 2026,
            'tax_invoice_status' => $number ? 'APPROVED' : 'CREATED',
            'selling_price' => 1000,
            'other_tax_base' => 900,
            'vat' => 100,
            'reference' => $reference,
        ]);
    }

    private function countFor(string $status): int
    {
        $controller = new TaxXmlExternalController();
        $method = new \ReflectionMethod($controller, 'applyFakturPajak');
        $method->setAccessible(true);

        $query = DetailSale::query()->where('source_type', DetailSale::SOURCE_EXTERNAL);
        $method->invoke($controller, $query, $status);

        return $query->count();
    }

    public function test_draft_tidak_masuk_kategori_belum(): void
    {
        $this->makeDoc('DOC-ADA');
        $this->makeFp('DOC-ADA', '08002600449030430');

        $this->makeDoc('DOC-DRAFT');
        $this->makeFp('DOC-DRAFT', null);

        $this->makeDoc('DOC-BELUM');
        $this->makeDoc('DOC-GUNG', 'gung_gung');

        $this->assertSame(1, $this->countFor('ada'));
        $this->assertSame(1, $this->countFor('draft'));
        $this->assertSame(1, $this->countFor('belum'));
        $this->assertSame(1, $this->countFor('gung_gung'));
    }

    public function test_download_status_selain_belum_ditolak(): void
    {
        $this->actingAs(User::factory()->create());

        $this->makeDoc('DOC-DRAFT');
        $this->makeFp('DOC-DRAFT', null);

        foreach (['ada', 'draft', 'gung_gung'] as $fp) {
            $this->post(route('tax.download-xml-external.download'), [
                'mode' => 'bulan',
                'bulan' => 10,
                'tahun' => 2026,
                'fp' => $fp,
            ])
                ->assertStatus(422)
                ->assertJsonPath('ok', false)
                ->assertSee('berisiko membuat Faktur Pajak dobel', false);
        }
    }
}
