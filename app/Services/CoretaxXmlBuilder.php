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

    /** @var array<string,array{name:string,address:string,tax:string,nitku:string}> */
    private array $custInternal = [];
    /** @var array<string,array{name:string,address:string,tax:string,nitku:string}> */
    private array $custExternal = [];
    /** @var array<string,string> */
    private array $custMap = [];
    /** @var array<string,array{code:string,name:string}> */
    private array $productByInternal = [];
    /** @var array<string,array{code:string,name:string}> */
    private array $productByExternal = [];
    /** @var array<string,string> */
    private array $unitMap = [];
    /** @var array<string,string> kunci lowercase => kode unit Coretax */
    private array $unitMapLower = [];
    /** @var array<string,string> name_norm => kode 6 digit */
    private array $itemCodes = [];
    /** @var array<int,string> kata kunci (lowercase) */
    private array $keywords = [];
    /** @var array<int,string> dokumen yang dilewati beserta alasannya */
    private array $skipped = [];
    /** @var array<int,array<string,mixed>> detail dokumen yang dilewati (untuk export Excel) */
    private array $skippedDetails = [];
    /** @var array<string,array<string,bool>> unit belum dipetakan per dokumen */
    private array $unmappedUnits = [];

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
     * @return array{xml:string, count:int, skipped:array<int,string>, unmapped:array<int,string>, unmapped_default:string}
     */
    public function build(array $docNumbers, string $sourceType): array
    {
        $this->skipped = [];
        $this->skippedDetails = [];
        $this->unmappedUnits = [];

        $docNumbers = array_values(array_unique(array_filter(array_map('strval', $docNumbers))));
        if ($docNumbers === []) {
            return ['xml' => '', 'count' => 0, 'skipped' => [], 'unmapped' => [], 'unmapped_default' => $this->unitDefault];
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
        $unmappedAll = [];
        foreach ($docNumbers as $doc) {
            $header = $headers->get($doc);
            if ($header === null) {
                continue;
            }

            $invoice = $this->buildInvoice(
                $header,
                $items->get($doc) ?? collect(),
                $externalDates->get($doc),
                $sourceType,
                $returns->get($doc) ?? collect(),
                $taxByReference,
                $financeLines->get($doc) ?? collect(),
                $doc
            );

            // Dokumen bermasalah dilewati (bukan membatalkan seluruh export).
            if ($invoice === null) {
                continue;
            }

            $invoices .= $invoice;
            $count++;

            foreach (array_keys($this->unmappedUnits[$doc] ?? []) as $unmappedUnit) {
                $unmappedAll[$unmappedUnit] = true;
            }
        }

        $unmapped = array_keys($unmappedAll);
        sort($unmapped);

        return [
            'xml' => $this->wrap($invoices),
            'count' => $count,
            'skipped' => array_values(array_unique($this->skipped)),
            'unmapped' => $unmapped,
            'unmapped_default' => $this->unitDefault,
        ];
    }

    /**
     * Detail terstruktur dokumen yang dilewati pada build() terakhir.
     * Dipakai export Excel supaya tim bisa memperbaiki data (NIK/NPWP, kode barang).
     *
     * @return array<int,array{doc:string,date:string,customer_code:string,customer_name:string,tax_number:string,nitku:string,reasons:array<int,string>}>
     */
    public function skippedDetails(): array
    {
        return $this->skippedDetails;
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
    ): ?string {
        $date = $externalDate
            ? Carbon::parse($externalDate)->format('Y-m-d')
            : ($header->transaction_date?->format('Y-m-d') ?? '');

        $code = (string) $header->customer_code;
        $mappedCode = $this->custMap[$code] ?? null;

        $extCode = $sourceType === DetailSale::SOURCE_INTERNAL
            ? ($mappedCode ?? $code)
            : $code;

        $internal = $this->custInternal[$code] ?? null;
        $external = $this->custExternal[$extCode] ?? null;

        // Tab external: mapping customer jadi acuan utama bila kode langsung
        // tidak ada atau NPWP-nya kosong. Dengan begitu NPWP/NITKU/nama/alamat
        // buyer diambil dari kode external hasil mapping (mis. ADE V-022-T → NPWP-112).
        if ($sourceType === DetailSale::SOURCE_EXTERNAL
            && $mappedCode !== null
            && trim((string) ($external['tax'] ?? '')) === '') {
            $mapped = $this->custExternal[$mappedCode] ?? null;
            if ($mapped !== null) {
                $external = $external === null
                    ? $mapped
                    : array_merge($external, array_filter(
                        $mapped,
                        static fn ($v) => trim((string) $v) !== ''
                    ));
                $extCode = $mappedCode;
            }
        }

        $isKtp = stripos($extCode, 'KTP') !== false;

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

        // NITKU: pakai isian customer bila ada (External dulu, lalu Internal);
        // kalau kosong tetap pakai rumus lama NPWP/NIK + '000000'.
        $nitkuExternal = trim((string) ($external['nitku'] ?? ''));
        $nitkuInternal = trim((string) ($internal['nitku'] ?? ''));
        $nitku = $nitkuExternal !== '' ? $nitkuExternal : $nitkuInternal;

        // Qty sisa setelah retur. Baris yang seluruh qty-nya sudah diretur
        // tidak diexport (menghindari baris qty 0 / DPP negatif).
        $returnQty = [];
        foreach ($returns as $ret) {
            $key = (string) $ret->item_no;
            $returnQty[$key] = ($returnQty[$key] ?? 0) + (float) $ret->quantity;
        }

        $isExternal = $sourceType === DetailSale::SOURCE_EXTERNAL;

        $activeLines = [];
        $totalGross = 0.0;
        foreach ($lines as $line) {
            $netQty = (float) $line->qty + ($returnQty[(string) $line->item_no] ?? 0.0);
            if ($netQty <= 0.005) {
                continue;
            }

            if ($isExternal) {
                // Tab external: nilai baris diambil dari invoice external (amount).
                // Sebagian data menyimpan harga per Kg dengan qty Crt, sehingga
                // qty x harga tidak sama dengan nilai baris. Harga diturunkan dari
                // nilai baris agar Price x Qty = TaxBase (validasi Coretax).
                $value = (float) $line->amount;
                $price = $netQty != 0.0 ? round($value / $netQty, 2) : 0.0;

                if ($value <= 0 || $price <= 0) {
                    $price = (float) $line->unit_price;
                    $value = $price * $netQty;
                }
            } else {
                $price = (float) $line->unit_price;
                $value = $price * $netQty;
            }

            $activeLines[] = ['line' => $line, 'qty' => $netQty, 'gross' => $value, 'price' => $price];
            $totalGross += $value;
        }

        // Masalah dokumen dikumpulkan dulu; bila ada, dokumen dilewati (tidak
        // membatalkan seluruh export) dan alasannya ditampilkan ke pengguna.
        $problems = [];

        // NIK/NPWP wajib 16 digit untuk semua tipe. Khusus customer KTP,
        // Coretax juga memvalidasi struktur NIK (kode wilayah & tanggal lahir);
        // 16 digit yang sebenarnya nomor NPWP (mis. berawalan 02/07) tetap ditolak.
        if ($taxNumber === '') {
            $problems[] = "Customer {$code} ({$buyerName}) belum punya "
                . ($isKtp ? 'NIK' : 'NPWP') . ' 16 digit.';
        } elseif ($taxNumber === '0000000000000000') {
            $problems[] = "Customer {$code} ({$buyerName}) masih memakai placeholder "
                . '0000000000000000; isi ' . ($isKtp ? 'NIK' : 'NPWP') . ' yang sebenarnya.';
        } elseif (! preg_match('/^\d{16}$/', $taxNumber)) {
            $problems[] = "Customer {$code} ({$buyerName}) "
                . ($isKtp ? 'NIK' : 'NPWP') . " '{$taxNumber}' harus 16 digit angka.";
        } elseif ($isKtp && ($nikProblem = $this->nikProblem($taxNumber)) !== null) {
            $problems[] = "Customer {$code} ({$buyerName}) NIK '{$taxNumber}' tidak valid: {$nikProblem}.";
        }

        if ($nitku !== '' && ! preg_match('/^\d{22}$/', $nitku)) {
            $problems[] = "NITKU customer {$code} ({$buyerName}) tidak valid: '{$nitku}' (harus 22 digit).";
        }

        if ($lines->isEmpty()) {
            $problems[] = 'Dokumen tidak punya baris item.';
        } elseif ($activeLines === []) {
            $problems[] = 'Dokumen tidak punya sisa qty setelah retur.';
        } else {
            foreach ($activeLines as $row) {
                $line = $row['line'];
                $resolved = $this->resolveItemCode($line, $sourceType);
                if (! preg_match('/^\d{6}$/', $resolved['code'])) {
                    $problems[] = "Item {$line->item_no} ({$resolved['name']}) belum punya Kode Barang/Jasa Coretax 6 digit.";
                }
            }
        }

        if ($problems !== []) {
            // Satu entri = satu dokumen, agar jumlah di notifikasi akurat.
            $this->skipped[] = $doc . ' — ' . implode(' ', $problems);

            // Data terstruktur untuk export Excel "dokumen dilewati".
            $this->skippedDetails[] = [
                'doc' => $doc,
                'date' => $date,
                'customer_code' => $code,
                'customer_name' => $buyerName !== '' ? $buyerName : (string) $header->customer_name,
                'tax_number' => $taxNumber,
                'nitku' => $nitku,
                'reasons' => $problems,
            ];

            return null;
        }

        $buyerTin = $isKtp ? '0000000000000000' : $taxNumber;
        $buyerDocNumber = $isKtp ? $taxNumber : '0000000000000000';
        $buyerIdtku = $nitku !== ''
            ? $nitku
            : (($isKtp ? $buyerDocNumber : $buyerTin) . '000000');

        $trxCode = $this->resolveTrxCode($lines);
        $addInfo = $trxCode === '08' ? 'TD.00510' : '';
        $facilityStamp = $trxCode === '08' ? 'TD.01110' : '';

        if ($isExternal) {
            // Tab external: pakai angka invoice external itu sendiri.
            // Diskon = total nilai baris - DPP invoice (header.amount). Untuk
            // invoice lama yang harganya sudah dipotong diskon, selisihnya 0
            // sehingga diskon tidak dipotong dua kali.
            $headerDpp = max(0.0, (float) $header->amount);
            $discountTotal = $headerDpp > 0 ? max(0.0, $totalGross - $headerDpp) : 0.0;
            if ($discountTotal < 1.0) {
                $discountTotal = 0.0;
            }

            // Retur tanpa rincian item: DPP final = amount - return_amount.
            $targetDpp = max(0.0, $headerDpp - (float) $header->return_amount);
            $base = $totalGross - $discountTotal;
            $factor = ($base > 0 && $targetDpp > 0 && abs($targetDpp - $base) > 0.01)
                ? $targetDpp / $base
                : 1.0;

            // Diskon selalu dialokasikan proporsional; jangan pakai diskon per
            // baris karena nilainya diskon invoice yang tersimpan berulang.
            $useLineDiscount = false;
        } else {
            // Diskon invoice (satu nilai untuk seluruh dokumen) dialokasikan
            // proporsional terhadap nilai kotor tiap baris sisa.
            $discountTotal = $this->invoiceDiscountTotal($financeLines);
            $useLineDiscount = $discountTotal <= 0;

            // Retur tanpa rincian item (sales_returns kosong) tetap harus menurunkan DPP:
            // skalakan qty + diskon agar total = invoice_amount - return_amount.
            $factor = $this->returnFactor($header, $totalGross, $discountTotal);
        }

        $goodServices = '';
        foreach ($activeLines as $row) {
            $lineDiscount = $useLineDiscount
                ? (float) $row['line']->discount
                : ($totalGross > 0 ? $discountTotal * ($row['gross'] / $totalGross) : 0.0);

            $goodServices .= $this->buildItem(
                $row['line'],
                $sourceType,
                $row['qty'],
                $lineDiscount,
                $doc,
                $factor,
                $row['price']
            );
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
     * Validasi struktur NIK 16 digit: kode provinsi dan tanggal lahir.
     *
     * Coretax menolak NIK yang kode wilayahnya tidak dikenal atau tanggal/
     * bulannya tidak wajar — biasanya karena nomor NPWP diisi sebagai NIK
     * (mis. 0266707900615000 / 0759068877028000).
     */
    private function nikProblem(string $nik): ?string
    {
        $province = (int) substr($nik, 0, 2);
        if ($province < 11) {
            return "kode provinsi '{$province}' tidak dikenal";
        }

        $day = (int) substr($nik, 6, 2);
        $month = (int) substr($nik, 8, 2);

        // NIK perempuan: tanggal lahir ditambah 40.
        if ($day > 40) {
            $day -= 40;
        }

        if ($day < 1 || $day > 31) {
            return "tanggal lahir '{$day}' tidak wajar";
        }

        if ($month < 1 || $month > 12) {
            return "bulan lahir '{$month}' tidak wajar";
        }

        return null;
    }

    /**
     * Total diskon invoice (satu nilai yang tersimpan berulang di setiap baris
     * internal). 0 bila tidak ada / nilainya berbeda per baris (diskon item).
     */
    private function invoiceDiscountTotal($financeLines): float
    {
        $discounts = [];
        foreach ($financeLines as $line) {
            $discount = round((float) $line->discount, 2);
            if ($discount > 0) {
                $discounts[$discount] = true;
            }
        }

        return count($discounts) === 1 ? (float) array_key_first($discounts) : 0.0;
    }

    /**
     * Faktor koreksi bila retur tidak punya rincian item (sales_returns kosong)
     * sehingga DPP tetap = invoice_amount - return_amount.
     */
    private function returnFactor(DetailSale $header, float $totalGross, float $discountTotal): float
    {
        $invoiceAmount = (float) $header->invoice_amount;
        $returnAmount = (float) $header->return_amount;

        if ($invoiceAmount <= 0 || $returnAmount <= 0) {
            return 1.0;
        }

        $base = $totalGross - $discountTotal;
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

    /**
     * Kode & nama barang Coretax untuk satu baris item.
     *
     * @return array{code:string,name:string}
     */
    private function resolveItemCode(DetailSaleItem $line, string $sourceType): array
    {
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

        return ['code' => $code, 'name' => $name];
    }

    private function buildItem(
        DetailSaleItem $line,
        string $sourceType,
        float $qty,
        float $discount,
        string $doc,
        float $factor = 1.0,
        ?float $price = null
    ): string {
        $itemNo = (string) $line->item_no;
        $resolved = $this->resolveItemCode($line, $sourceType);
        $name = $resolved['name'];
        $code = $resolved['code'];

        $unit = trim((string) $line->unit);
        $unitOut = $this->unitMap[$unit]
            ?? $this->unitMapLower[mb_strtolower($unit)]
            ?? null;

        if ($unitOut === null) {
            $unitOut = $this->unitDefault;
            $this->unmappedUnits[$doc][$unit === '' ? '(kosong)' : $unit] = true;
        }

        $price = $price ?? (float) $line->unit_price;

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
            ->get(['customer_code', 'customer_name', 'address', 'tax_number', 'nitku']) as $c) {
            $k = (string) $c->customer_code;
            if ($k === '' || array_key_exists($k, $this->custInternal)) {
                continue;
            }
            $this->custInternal[$k] = [
                'name' => (string) $c->customer_name,
                'address' => (string) $c->address,
                'tax' => (string) $c->tax_number,
                'nitku' => (string) $c->nitku,
            ];
        }

        foreach (Customer::where('source_type', Customer::SOURCE_EXTERNAL)
            ->get(['customer_code', 'customer_name', 'address', 'tax_number', 'nitku']) as $c) {
            $k = (string) $c->customer_code;
            if ($k === '' || array_key_exists($k, $this->custExternal)) {
                continue;
            }
            $this->custExternal[$k] = [
                'name' => (string) $c->customer_name,
                'address' => (string) $c->address,
                'tax' => (string) $c->tax_number,
                'nitku' => (string) $c->nitku,
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

                // Cadangan case-insensitive: mapping "Crt" juga berlaku untuk "CRT".
                $lower = mb_strtolower(trim($k));
                if ($lower !== '' && ! array_key_exists($lower, $this->unitMapLower)) {
                    $this->unitMapLower[$lower] = (string) $r->unit_coretax;
                }
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
