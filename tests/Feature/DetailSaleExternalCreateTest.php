<?php

namespace Tests\Feature;

use App\Http\Controllers\DetailSaleController;
use App\Models\DetailSale;
use App\Models\DetailSaleItem;
use App\Models\Setting;
use App\Services\AccurateApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetailSaleExternalCreateTest extends TestCase
{
    use RefreshDatabase;

    private function enableAccurate(): void
    {
        Setting::updateOrCreate(['key' => 'accurate_api_token'], ['value' => 'dummy-token']);
        Setting::updateOrCreate(['key' => 'accurate_signature_secret'], ['value' => 'dummy-secret']);
    }

    private function makeExternalDoc(array $header = [], array $item = []): DetailSale
    {
        $doc = $header['doc_number'] ?? 'DMXS26109999';

        $detail = DetailSale::create(array_merge([
            'source_type' => DetailSale::SOURCE_EXTERNAL,
            'doc_number' => $doc,
            'customer_code' => 'NPWP-112',
            'customer_name' => 'PT. SUPRA BOGA LESTARI Tbk',
            'transaction_date' => '2026-10-22',
            'description' => $doc,
            'invoice_amount' => 2220,
            'amount' => 2000,
            'tax_base' => 220,
            'discount' => 0,
            'return_amount' => 0,
            'owing' => 2220,
            'payment_amount' => 0,
            'calculate_field' => 2220,
        ], $header));

        DetailSaleItem::create(array_merge([
            'source_type' => DetailSaleItem::SOURCE_EXTERNAL,
            'doc_number' => $doc,
            'customer_code' => $detail->customer_code,
            'customer_name' => $detail->customer_name,
            'transaction_date' => $detail->transaction_date,
            'item_no' => 'J004-6',
            'item_description' => 'Buna Shimeji Mushroom',
            'unit_price' => 1000,
            'qty' => 2,
            'unit' => 'Pcs',
            'amount' => 2000,
            'discount' => 0,
        ], $item));

        return $detail;
    }

    private function prepare(DetailSale $header, string $type = 'so'): array
    {
        $controller = new DetailSaleController();
        $method = new \ReflectionMethod($controller, 'prepareExternalDocFromExternal');
        $method->setAccessible(true);

        return $method->invoke($controller, (string) $header->doc_number, $type, new AccurateApiClient());
    }

    public function test_baris_tidak_konsisten_ditolak(): void
    {
        $this->enableAccurate();

        // Kasus nyata DMXS26101733: qty 0,38 x 8.750 = 3.325, nilai baris 131.250.
        $header = $this->makeExternalDoc(
            ['doc_number' => 'DMXS26101733'],
            ['qty' => 0.38, 'unit_price' => 8750, 'amount' => 131250]
        );

        $result = $this->prepare($header, 'so');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString(
            'tidak sama dengan nilai baris',
            implode(' ', $result['reasons'])
        );
    }

    public function test_payload_so_dibangun_dari_data_external(): void
    {
        $this->enableAccurate();

        $header = $this->makeExternalDoc();
        $result = $this->prepare($header, 'so');

        $this->assertTrue($result['ok'], implode(' ', $result['reasons'] ?? []));

        $item = $result['payload']['detailItem'][0];
        $this->assertSame('J004-6', $item['itemNo']);
        $this->assertSame(2.0, (float) $item['quantity']);
        $this->assertSame(1000.0, (float) $item['unitPrice']);
        $this->assertSame('Pcs', $item['itemUnitName']);
        $this->assertArrayNotHasKey('cashDiscount', $result['payload']);
    }

    public function test_payload_invoice_membawa_cash_discount(): void    {
        $header = $this->makeExternalDoc(['discount' => 100]);

        $controller = new DetailSaleController();
        $method = new \ReflectionMethod($controller, 'buildExternalPayload');
        $method->setAccessible(true);

        $prepared = [[
            'line' => DetailSaleItem::where('doc_number', $header->doc_number)->first(),
            'qty' => 2.0,
            'qty_send' => 2.0,
            'unit_send' => 'Pcs',
            'price_send' => 1000.0,
            'item_no_external' => 'J004-6',
            'item_name_external' => 'Buna Shimeji Mushroom',
        ]];

        $payload = $method->invoke($controller, 'inv', $header, $prepared, 'NPWP-112', 'DMXS26109999', 'DMXS26109999');

        $this->assertSame(100.0, (float) $payload['cashDiscount']);
    }

    public function test_halaman_detail_sales_menandai_fp_draft(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Query halaman memakai REGEXP (khusus MySQL).');
        }

        $this->actingAs(\App\Models\User::factory()->create());

        $header = $this->makeExternalDoc();

        \App\Models\FakturPajak::create([
            'tax_invoice_number' => null,
            'tax_invoice_date' => '2026-10-22',
            'tax_invoice_period' => 'Oktober',
            'tax_invoice_year' => 2026,
            'tax_invoice_status' => 'CREATED',
            'selling_price' => 2000,
            'other_tax_base' => 1800,
            'vat' => 200,
            'reference' => $header->doc_number,
        ]);

        $this->get(route('detail-sales.index', ['tab' => 'external']))
            ->assertOk()
            ->assertSee('"fp_draft":true', false);
    }

    public function test_adopsi_invoice_bernomor_lain(): void
    {
        $this->enableAccurate();

        \Illuminate\Support\Facades\Cache::put('accurate.host', 'https://accurate.test', 3600);

        \App\Models\ExternalDocument::create([
            'doc_number' => 'SDMXS26100281',
            'type' => \App\Models\ExternalDocument::TYPE_DO,
            'accurate_id' => 12324,
            'accurate_number' => 'SDMXS26100281',
            'accurate_status' => 'SENT',
            'http_status' => 200,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*accurate.test/accurate/api/delivery-order/detail.do*' => \Illuminate\Support\Facades\Http::response([
                's' => true,
                'd' => [[
                    'id' => 12324,
                    'number' => 'SDMXS26100281',
                    'status' => 'INVOICED',
                    'shipDate' => '17/10/2026',
                    'customer' => ['id' => 706, 'name' => 'SIFA AZIZ'],
                    'detailItem' => [
                        ['id' => 29809, 'quantity' => 1, 'invoiceQuantity' => 1],
                        ['id' => 29810, 'quantity' => 1, 'invoiceQuantity' => 1],
                    ],
                ]],
            ]),
            '*accurate.test/accurate/api/sales-invoice/list.do*' => \Illuminate\Support\Facades\Http::response([
                's' => true,
                'd' => [[
                    'id' => 31563,
                    'number' => 'SI.2026.11.00008',
                    'transDate' => '16/11/2026',
                    'status' => 'OUTSTANDING',
                ]],
            ]),
            '*accurate.test/accurate/api/sales-invoice/detail.do*' => \Illuminate\Support\Facades\Http::response([
                's' => true,
                'd' => [[
                    'id' => 31563,
                    'number' => 'SI.2026.11.00008',
                    'status' => 'OUTSTANDING',
                    'detailItem' => [
                        ['deliveryOrderDetailId' => 29809],
                        ['deliveryOrderDetailId' => 29810],
                    ],
                ]],
            ]),
        ]);

        $controller = new DetailSaleController();
        $method = new \ReflectionMethod($controller, 'adoptExistingInvoice');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'SDMXS26100281', new AccurateApiClient());

        $this->assertNotNull($result);
        $this->assertSame('SI.2026.11.00008', $result['number']);

        $this->assertDatabaseHas('external_documents', [
            'doc_number' => 'SDMXS26100281',
            'type' => 'inv',
            'accurate_number' => 'SI.2026.11.00008',
        ]);
    }
}
