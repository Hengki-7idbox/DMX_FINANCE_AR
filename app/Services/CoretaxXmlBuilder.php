<?php

namespace App\Services;

use App\Models\CoretaxItemCode;
use App\Models\CoretaxKeyword;
use App\Models\Customer;
use App\Models\DetailSale;
use App\Models\DetailSaleItem;
use App\Models\FakturPajak;
use App\Models\MappingCustomer;
use App\Models\MappingProduct;
use App\Models\MappingUnit;
use App\Models\SalesReturn;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Membangun XML "Coretax" (TaxInvoiceBulk → ListOfTaxInvoice → TaxInvoice →
 * ListOfGoodService) dari data Detail Sales tab internal/external.
 *
 * Struktur mengikuti sample upload Coretax (SEPTEMBER 29 - Copy.xml) dan
 * formula Excel konverter "Hky - Faktur Pajak - Converter" sheet Faktur.
 */
class CoretaxXmlBuilder
{
    private string $sellerTin;
    private float $vatRate;
    private string $unitDefault;

    /** @var array<string,array{name:string,address:string,tax:string}> */
    private array $custInternal = [];
    /** @var array<string,array{name:string,address:string,tax:string}> */
    private array $custExternal = [];
    /** @var array<string,string> */
    private array $custMap = [];
    /** @var array<string,array{code:string,name:string}> */
    private array $productByInternal = [];
    /** @var array<string,array{code:string,name:string}> */
    private array $productByExternal = [];
    /** @var array<string,string> */
    private array $unitMap = [];
    /** @var array<string,string> name_norm => kode 6 digit */
    private array $itemCodes = [];
    /** @var array<int,string> kata kunci (lowercase) */
    private array $keywords = [];
    /** @var array<int,string> */
    private array $blockers = [];

    public function __construct()
    {
        $this->sellerTin = (string) Setting::get('seller_tin', '0029327129041000');
        $this->vatRate = (float) Setting::get('tax2_rate', '12');
        $this->unitDefault = (string) Setting::get('coretax_unit_default', 'UM.0022');

        if ($this->unitDefault === '') {
            $this->unitDefault = 'UM.0022';
        }

        $this->loadMappings();
    }

    /**
     * @param  array<int,string>  $docNumbers
     * @return array{xml:string, count:int, blockers:array<int,string>}
     */
    public function build(array $docNumbers, string $sourceType): array
    {
        $this->blockers = [];

        $docNumbers = array_values(array_unique(array_filter(array_map('strval', $docNumbers))));
        if ($docNumbers === []) {
            return ['xml' => '', 'count' => 0, 'blockers' => []];
        }

        $headers = DetailSale::where('source_type', $sourceType)
            ->whereIn('doc_number', $docNumbers)
            ->get()
            ->keyBy('doc_number');

        $items = DetailSaleItem::where('source_type', $sourceType)
            ->whereIn('doc_number', $docNumbers)
            ->orderBy('id')
            ->get()
            ->groupBy('doc_number');

        // Diskon invoice hanya tersimpan di baris internal; dipakai juga saat export tab external.
        $financeLines = DetailSaleItem::where('source_type', DetailSale::SOURCE_INTERNAL)
            ->whereIn('doc_number', $docNumbers)
            ->orderBy('id')
            ->get()
            ->groupBy('doc_number');

        $returns = SalesReturn::whereIn('invoice_no', $docNumbers)
            ->get()
            ->groupBy('invoice_no');

        $taxByReference = FakturPajak::whereIn('reference', $docNumbers)
            ->whereNotNull('buyer_tin')
            ->pluck('buyer_tin', 'reference')
            ->all();

        // Tanggal faktur: pakai tanggal dokumen external bila ada, fallback tanggal internal.
        $externalDates = DetailSale::where('source_type', DetailSale::SOURCE_EXTERNAL)
            ->whereIn('doc_number', $docNumbers)
            ->pluck('transaction_date', 'doc_number');

        $invoices = '';
        $count = 0;
        foreach ($docNumbers as $doc) {
            $header = $headers->get($doc);
            if ($header === null) {
                continue;
            }

            $invoices .= $this->buildInvoice(
                $header,
                $items->get($doc) ?? collect(),
                $externalDates->get($doc),
                $sourceType,
                $returns->get($doc) ?? collect(),
                $taxByReference,
                $financeLines->get($doc) ?? collect(),
                $doc
            );
            $count++;
        }

        return [
            'xml' => $this->wrap($invoices),
            'count' => $count,
            'blockers' => array_values(array_unique($this->blockers)),
        ];
    }

    private function wrap(string $invoices): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>' . "\n"
            . '<TaxInvoiceBulk xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<TIN>' . $this->esc($this->sellerTin) . '</TIN>'
            . '<ListOfTaxInvoice>' . $invoices . '</ListOfTaxInvoice>'
            . '</TaxInvoiceBulk>';
    }

    private function buildInvoice(
        DetailSale $header,
        $lines,
        mixed $externalDate,
        string $sourceType,
        $returns,
        array $taxByReference,
        $financeLines,
        string $doc
    ): string {
        $date = $externalDate
            ? Carbon::parse($externalDate)->format('Y-m-d')
            : ($header->transaction_date?->format('Y-m-d') ?? '');

        $code = (string) $header->customer_code;
        $extCode = $sourceType === DetailSale::SOURCE_INTERNAL
            ? ($this->custMap[$code] ?? $code)
            : $code;

        $isKtp = stripos($extCode, 'KTP') !== false;

        $internal = $this->custInternal[$code] ?? null;
        $external = $this->custExternal[$extCode] ?? null;

        $taxExternal = trim((string) ($external['tax'] ?? ''));
        $taxInternal = trim((string) ($internal['tax'] ?? ''));
        $taxNumber = $taxExternal !== '' ? $taxExternal : $taxInternal;

        if ($taxNumber === '') {
            $taxNumber = trim((string) ($taxByReference[$doc] ?? ''));
        }

        $buyerName = trim((string) ($external['name'] ?? ''));
        if ($buyerName === '') {
            $buyerName = trim((string) ($internal['name'] ?? ''));
        }
        if ($buyerName === '') {
            $buyerName = (string) $header->customer_name;
        }

        $buyerAddress = trim((string) ($external['address'] ?? ''));
        if ($buyerAddress === '') {
            $buyerAddress = trim((string) ($internal['address'] ?? ''));
        }

        if (! $isKtp && ! preg_match('/^\d{16}$/', $taxNumber)) {
            $this->blockers[] = "Customer {$code} ({$buyerName}) pada {$doc} belum punya NPWP 16 digit.";
        }

        $buyerTin = $isKtp ? '0000000000000000' : $taxNumber;
        $buyerDocNumber = $isKtp ? $taxNumber : '0000000000000000';
        $buyerIdtku = ($isKtp ? $buyerDocNumber : $buyerTin) . '000000';

        $trxCode = $this->resolveTrxCode($lines);
        $addInfo = $trxCode === '08' ? 'TD.00510' : '';
        $facilityStamp = $trxCode === '08' ? 'TD.01110' : '';

        $returnQty = [];
        foreach ($returns as $ret) {
            $key = (string) $ret->item_no;
            $returnQty[$key] = ($returnQty[$key] ?? 0) + (float) $ret->quantity;
        }

        $discountAlloc = $this->invoiceDiscountAllocation($financeLines, $lines->count());

        if ($lines->isEmpty()) {
            $this->blockers[] = "Dokumen {$doc} tidak punya baris item.";
        }

        // Retur tanpa rincian item (sales_returns kosong) tetap harus menurunkan DPP:
        // skalakan qty + diskon proporsional agar total = invoice_amount - return_amount.
        $factor = $this->returnFactor($header, $lines, $returnQty, $discountAlloc);

        $goodServices = '';
        foreach ($lines as $line) {
            $goodServices .= $this->buildItem($line, $sourceType, $returnQty, $discountAlloc, $doc, $factor);
        }

        return '<TaxInvoice>'
            . $this->tag('TaxInvoiceDate', $date)
            . $this->tag('TaxInvoiceOpt', 'Normal')
            . $this->tag('TrxCode', $trxCode)
            . $this->tag('AddInfo', $addInfo)
            . $this->tag('CustomDoc', '-')
            . $this->tag('CustomDocMonthYear', '-')
            . $this->tag('RefDesc', $doc)
            . $this->tag('FacilityStamp', $facilityStamp)
            . $this->tag('SellerIDTKU', $this->sellerTin . '000000')
            . $this->tag('BuyerTin', $buyerTin)
            . $this->tag('BuyerDocument', $isKtp ? 'National ID' : 'TIN')
            . $this->tag('BuyerCountry', 'IDN')
            . $this->tag('BuyerDocumentNumber', $buyerDocNumber)
            . $this->tag('BuyerName', $buyerName)
            . $this->tag('BuyerAdress', $buyerAddress)
            . $this->tag('BuyerEmail', '')
            . $this->tag('BuyerIDTKU', $buyerIdtku)
            . '<ListOfGoodService>' . $goodServices . '</ListOfGoodService>'
            . '</TaxInvoice>';
    }

    /**
     * Diskon invoice tersimpan berulang di setiap baris internal; hitung sekali lalu
     * bagi rata ke semua baris (mengikuti file XML Coretax yang berhasil diupload).
     */
    private function invoiceDiscountAllocation($financeLines, int $lineCount): ?float
    {
        $discounts = [];
        foreach ($financeLines as $line) {
            $discount = (float) $line->discount;
            if ($discount > 0) {
                $discounts[round($discount, 2)] = true;
            }
        }

        if (count($discounts) !== 1 || $lineCount < 1) {
            return null;
        }

        return array_key_first($discounts) / $lineCount;
    }

    private function returnFactor(DetailSale $header, $lines, array $returnQty, ?float $discountAlloc): float
    {
        $invoiceAmount = (float) $header->invoice_amount;
        $returnAmount = (float) $header->return_amount;

        if ($invoiceAmount <= 0 || $returnAmount <= 0) {
            return 1.0;
        }

        $base = 0.0;
        foreach ($lines as $line) {
            $qty = (float) $line->qty + ($returnQty[(string) $line->item_no] ?? 0);
            if ($qty < 0) {
                $qty = 0;
            }
            $discount = $discountAlloc !== null ? $discountAlloc : (float) $line->discount;
            $base += (float) $line->unit_price * $qty - $discount;
        }

        $target = $invoiceAmount - $returnAmount;

        if ($base <= 0 || $target <= 0) {
            return 1.0;
        }

        $factor = $target / $base;

        if ($factor > 1.2 || $factor < 0.1 || abs($factor - 1.0) < 0.00005) {
            return 1.0;
        }

        return $factor;
    }

    private function buildItem(
        DetailSaleItem $line,
        string $sourceType,
        array $returnQty,
        ?float $discountAlloc,
        string $doc,
        float $factor = 1.0
    ): string {
        $itemNo = (string) $line->item_no;
        $map = $sourceType === DetailSale::SOURCE_INTERNAL
            ? ($this->productByInternal[$itemNo] ?? null)
            : ($this->productByExternal[$itemNo] ?? null);

        $name = trim((string) ($map['name'] ?? ''));
        if ($name === '' || str_starts_with($name, '=')) {
            $name = trim((string) $line->item_description);
        }

        $code = trim((string) ($map['code'] ?? ''));
        if ($code === '') {
            $code = $this->itemCodes[CoretaxItemCode::normalizeName($name)] ?? '';
        }
        if ($code === '') {
            $code = $this->itemCodes[CoretaxItemCode::normalizeName($line->item_description)] ?? '';
        }

        if (! preg_match('/^\d{6}$/', $code)) {
            $this->blockers[] = "Item {$itemNo} ({$name}) pada {$doc} belum punya Kode Barang/Jasa Coretax 6 digit.";
        }

        $unit = (string) $line->unit;
        $unitOut = $this->unitMap[$unit] ?? $this->unitDefault;

        $price = (float) $line->unit_price;
        $qty = (float) $line->qty + ($returnQty[$itemNo] ?? 0);
        if ($qty < 0) {
            $qty = 0;
        }

        $discount = $discountAlloc !== null ? $discountAlloc : (float) $line->discount;

        if ($factor !== 1.0) {
            $qty *= $factor;
            $discount *= $factor;
        }

        $taxBase = $price * $qty - $discount;
        $otherTaxBase = $this->otherTaxBase($taxBase);
        $vat = $otherTaxBase * $this->vatRate / 100;

        return '<GoodService>'
            . $this->tag('Opt', 'A')
            . $this->tag('Code', $code)
            . $this->tag('Name', $name)
            . $this->tag('Unit', $unitOut)
            . $this->tag('Price', $this->num($price))
            . $this->tag('Qty', $this->num($qty))
            . $this->tag('TotalDiscount', $this->num($discount))
            . $this->tag('TaxBase', $this->num($taxBase))
            . $this->tag('OtherTaxBase', $this->num($otherTaxBase))
            . $this->tag('VATRate', $this->num($this->vatRate))
            . $this->tag('VAT', $this->num($vat))
            . $this->tag('STLGRate', '0')
            . $this->tag('STLG', '0')
            . '</GoodService>';
    }

    /**
     * DPP Nilai Lain = DPP × (tarif - 1) / tarif (mis. 11/12 saat tarif PPN 12%).
     */
    private function otherTaxBase(float $taxBase): float
    {
        if ($this->vatRate <= 0) {
            return $taxBase;
        }

        return $taxBase * ($this->vatRate - 1) / $this->vatRate;
    }

    private function resolveTrxCode($lines): string
    {
        if ($this->keywords === []) {
            return '08';
        }

        foreach ($lines as $line) {
            $desc = mb_strtolower(trim((string) $line->item_description));
            if ($desc === '') {
                continue;
            }

            foreach ($this->keywords as $keyword) {
                if ($keyword !== '' && mb_strpos($desc, $keyword) !== false) {
                    return '04';
                }
            }
        }

        return '08';
    }

    private function loadMappings(): void
    {
        foreach (Customer::where('source_type', Customer::SOURCE_INTERNAL)
            ->get(['customer_code', 'customer_name', 'address', 'tax_number']) as $c) {
            $k = (string) $c->customer_code;
            if ($k === '' || array_key_exists($k, $this->custInternal)) {
                continue;
            }
            $this->custInternal[$k] = [
                'name' => (string) $c->customer_name,
                'address' => (string) $c->address,
                'tax' => (string) $c->tax_number,
            ];
        }

        foreach (Customer::where('source_type', Customer::SOURCE_EXTERNAL)
            ->get(['customer_code', 'customer_name', 'address', 'tax_number']) as $c) {
            $k = (string) $c->customer_code;
            if ($k === '' || array_key_exists($k, $this->custExternal)) {
                continue;
            }
            $this->custExternal[$k] = [
                'name' => (string) $c->customer_name,
                'address' => (string) $c->address,
                'tax' => (string) $c->tax_number,
            ];
        }

        foreach (MappingCustomer::query()->get(['kode_internal', 'kode_external']) as $r) {
            $k = (string) $r->kode_internal;
            if ($k !== '' && ! array_key_exists($k, $this->custMap)) {
                $this->custMap[$k] = (string) $r->kode_external;
            }
        }

        foreach (MappingProduct::query()->get(['kode_item_internal', 'kode_item_external', 'code_coretax', 'nama_item_external']) as $r) {
            $payload = [
                'code' => (string) $r->code_coretax,
                'name' => (string) $r->nama_item_external,
            ];

            $internal = (string) $r->kode_item_internal;
            if ($internal !== '' && ! array_key_exists($internal, $this->productByInternal)) {
                $this->productByInternal[$internal] = $payload;
            }

            $external = (string) $r->kode_item_external;
            if ($external !== '' && ! array_key_exists($external, $this->productByExternal)) {
                $this->productByExternal[$external] = $payload;
            }
        }

        foreach (MappingUnit::query()->get(['unit_internal', 'unit_coretax']) as $r) {
            $k = (string) $r->unit_internal;
            if ($k !== '' && ! array_key_exists($k, $this->unitMap)) {
                $this->unitMap[$k] = (string) $r->unit_coretax;
            }
        }

        $this->itemCodes = CoretaxItemCode::query()->pluck('code', 'name_norm')->all();

        foreach (CoretaxKeyword::query()->pluck('kata') as $kata) {
            $kata = mb_strtolower(trim((string) $kata));
            if ($kata !== '') {
                $this->keywords[] = $kata;
            }
        }
    }

    private function tag(string $name, mixed $value): string
    {
        $v = (string) $value;
        if ($v === '') {
            return '<' . $name . ' />';
        }

        return '<' . $name . '>' . $this->esc($v) . '</' . $name . '>';
    }

    private function esc(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function num(mixed $value): string
    {
        $v = (float) $value;
        if (floor($v) === $v) {
            return (string) (int) $v;
        }

        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }
}
