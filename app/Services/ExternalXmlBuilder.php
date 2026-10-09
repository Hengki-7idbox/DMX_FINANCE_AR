<?php

namespace App\Services;

use App\Models\DetailSale;
use App\Models\DetailSaleItem;
use App\Models\MappingCustomer;
use App\Models\MappingProduct;
use App\Models\Setting;

/**
 * Membangun XML "External" (SALESORDER / DELIVERYORDER / SALESINVOICE) dari data
 * Detail Sales tab internal, memakai Mapping Customer, Mapping Product, dan Settings.
 *
 * Struktur & urutan tag mengikuti Sample/xml/External/*.xml.
 */
class ExternalXmlBuilder
{
    private string $branchExternal;
    private string $eximId;
    private float $taxRate;
    private string $tax1Id;
    private string $tax2Id;
    private float $tax2Rate;
    private string $warehouseId;
    private string $arAccount;
    private string $dpAccountId;
    private string $termsId;

    /** @var array<string,string> */
    private array $custMap = [];
    /** @var array<string,string> */
    private array $itemCodeMap = [];
    /** @var array<string,string> */
    private array $itemNameMap = [];

    public function __construct()
    {
        $this->branchExternal = (string) Setting::get('branch_code_external', '1127812608');
        $this->eximId = (string) Setting::get('exim_id', '2');
        $this->taxRate = (float) Setting::get('tax_rate', '11');
        $this->tax1Id = (string) Setting::get('tax1_id', 'P');
        $this->tax2Id = (string) Setting::get('tax2_id', 'J');
        $this->tax2Rate = (float) Setting::get('tax2_rate', '12');
        $this->warehouseId = (string) Setting::get('warehouse_id', 'CENTRE');
        $this->arAccount = (string) Setting::get('ar_account', '110311');
        $this->dpAccountId = (string) Setting::get('dp_account_id', '210201');
        $this->termsId = (string) Setting::get('terms_id', '');

        $this->loadMappings();
    }

    /**
     * @param  array<int,string>  $docNumbers
     * @return array{order:string, delivery:string, invoice:string, count:int}
     */
    public function build(array $docNumbers): array
    {
        $docNumbers = array_values(array_unique(array_filter(array_map('strval', $docNumbers))));
        if ($docNumbers === []) {
            return ['order' => '', 'delivery' => '', 'invoice' => '', 'count' => 0];
        }

        $headers = DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
            ->whereIn('doc_number', $docNumbers)
            ->get()
            ->keyBy('doc_number');

        $items = DetailSaleItem::where('source_type', DetailSaleItem::SOURCE_INTERNAL)
            ->whereIn('doc_number', $docNumbers)
            ->orderBy('id')
            ->get()
            ->groupBy('doc_number');

        $orderParts = '';
        $deliveryParts = '';
        $invoiceParts = '';
        $count = 0;

        foreach ($docNumbers as $doc) {
            $header = $headers->get($doc);
            if ($header === null) {
                continue;
            }

            $lines = $items->get($doc) ?? collect();
            [$order, $delivery, $invoice] = $this->buildOne($header, $lines);
            $orderParts .= $order;
            $deliveryParts .= $delivery;
            $invoiceParts .= $invoice;
            $count++;
        }

        return [
            'order' => $this->wrap($orderParts),
            'delivery' => $this->wrap($deliveryParts),
            'invoice' => $this->wrap($invoiceParts),
            'count' => $count,
        ];
    }

    private function wrap(string $transactions): string
    {
        return '<?xml version="1.0"?>' . "\n"
            . '<NMEXML EximID="' . $this->esc($this->eximId) . '" BranchCode="' . $this->esc($this->branchExternal)
            . '" ACCOUNTANTCOPYID=""><TRANSACTIONS OnError="CONTINUE">' . $transactions . '</TRANSACTIONS></NMEXML>';
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function buildOne(DetailSale $header, $lines): array
    {
        $doc = (string) $header->doc_number;
        $date = $header->transaction_date?->format('Y-m-d') ?? '';
        $custId = $this->custMap[$header->customer_code] ?? (string) $header->customer_code;
        $desc = trim((string) $header->description);
        $discount = (float) $header->discount;

        $gross = 0.0;
        foreach ($lines as $line) {
            $gross += (float) $line->qty * (float) $line->unit_price;
        }
        $net = $gross - $discount;
        $soTax = $gross * $this->taxRate / 100;
        $invTax = $net * $this->taxRate / 100;
        $invoiceAmount = $net + $invTax;

        $orderLines = '';
        $deliveryLines = '';
        $invoiceLines = '';
        $seq = 1;
        foreach ($lines as $line) {
            $itemNo = $this->itemCodeMap[$line->item_no] ?? (string) $line->item_no;
            $itemDesc = $this->itemNameMap[$line->item_description] ?? trim((string) $line->item_description);
            $qty = $this->num($line->qty);
            $price = $this->num($line->unit_price);
            $unit = $this->esc((string) $line->unit);
            $itemNoE = $this->esc($itemNo);
            $itemDescE = $this->esc($itemDesc);

            $orderLines .= '<ITEMLINE operation="Add">'
                . '<KeyID>' . $seq . '</KeyID>'
                . '<ITEMNO>' . $itemNoE . '</ITEMNO>'
                . '<QUANTITY>' . $qty . '</QUANTITY>'
                . '<ITEMUNIT>' . $unit . '</ITEMUNIT>'
                . '<UNITRATIO>1</UNITRATIO>'
                . $this->reservedTags()
                . '<ITEMOVDESC>' . $itemDescE . '</ITEMOVDESC>'
                . '<UNITPRICE>' . $price . '</UNITPRICE>'
                . '<DISCPC/>'
                . '<TAXCODES>' . $this->esc($this->tax1Id) . '</TAXCODES>'
                . '<GROUPSEQ/>'
                . '<QTYSHIPPED>' . $qty . '</QTYSHIPPED>'
                . '</ITEMLINE>';

            $deliveryLines .= '<ITEMLINE operation="Add">'
                . '<KeyID>' . $seq . '</KeyID>'
                . '<ITEMNO>' . $itemNoE . '</ITEMNO>'
                . '<QUANTITY>' . $qty . '</QUANTITY>'
                . '<ITEMUNIT>' . $unit . '</ITEMUNIT>'
                . '<UNITRATIO>1</UNITRATIO>'
                . $this->reservedTags()
                . '<ITEMOVDESC>' . $itemDescE . '</ITEMOVDESC>'
                . '<UNITPRICE>' . $price . '</UNITPRICE>'
                . '<ITEMDISCPC/>'
                . '<TAXCODES>' . $this->esc($this->tax1Id) . '</TAXCODES>'
                . '<GROUPSEQ/>'
                . '<SOSEQ>' . $seq . '</SOSEQ>'
                . '<BRUTOUNITPRICE>' . $price . '</BRUTOUNITPRICE>'
                . '<WAREHOUSEID>' . $this->esc($this->warehouseId) . '</WAREHOUSEID>'
                . '<QTYCONTROL>0</QTYCONTROL>'
                . '<DOSEQ/>'
                . '<SOID>' . $this->esc($doc) . '</SOID>'
                . '<DOID/>'
                . '</ITEMLINE>';

            $invoiceLines .= '<ITEMLINE operation="Add">'
                . '<KeyID>' . $seq . '</KeyID>'
                . '<ITEMNO>' . $itemNoE . '</ITEMNO>'
                . '<QUANTITY>' . $qty . '</QUANTITY>'
                . '<ITEMUNIT>' . $unit . '</ITEMUNIT>'
                . '<UNITRATIO>1</UNITRATIO>'
                . $this->reservedTags()
                . '<ITEMOVDESC>' . $itemDescE . '</ITEMOVDESC>'
                . '<UNITPRICE>' . $price . '</UNITPRICE>'
                . '<ITEMDISCPC/>'
                . '<TAXCODES>' . $this->esc($this->tax1Id) . '</TAXCODES>'
                . '<GROUPSEQ/>'
                . '<SOSEQ>' . $seq . '</SOSEQ>'
                . '<BRUTOUNITPRICE>' . $price . '</BRUTOUNITPRICE>'
                . '<WAREHOUSEID>' . $this->esc($this->warehouseId) . '</WAREHOUSEID>'
                . '<QTYCONTROL>0</QTYCONTROL>'
                . '<DOSEQ>' . $seq . '</DOSEQ>'
                . '<SOID>' . $this->esc($doc) . '</SOID>'
                . '<DOID>' . $this->esc($doc) . '</DOID>'
                . '</ITEMLINE>';

            $seq++;
        }

        $order = '<SALESORDER operation="Add" REQUESTID="1">'
            . '<TRANSACTIONID>0</TRANSACTIONID>'
            . $orderLines
            . '<SONO>' . $this->esc($doc) . '</SONO>'
            . '<SODATE>' . $this->esc($date) . '</SODATE>'
            . '<TAX1ID>' . $this->esc($this->tax1Id) . '</TAX1ID>'
            . '<TAX2ID>' . $this->esc($this->tax2Id) . '</TAX2ID>'
            . '<TAX1CODE>' . $this->esc($this->tax1Id) . '</TAX1CODE>'
            . '<TAX2CODE>' . $this->esc($this->tax2Id) . '</TAX2CODE>'
            . '<TAX1RATE>' . $this->num($this->taxRate) . '</TAX1RATE>'
            . '<TAX2RATE>' . $this->num($this->tax2Rate) . '</TAX2RATE>'
            . '<TAX1AMOUNT>' . $this->num($soTax) . '</TAX1AMOUNT>'
            . '<TAX2AMOUNT>0</TAX2AMOUNT>'
            . '<RATE>1</RATE>'
            . '<TAXINCLUSIVE>0</TAXINCLUSIVE>'
            . '<CUSTOMERISTAXABLE>1</CUSTOMERISTAXABLE>'
            . '<CASHDISCOUNT>0</CASHDISCOUNT>'
            . '<CASHDISCPC/>'
            . '<FREIGHT>0</FREIGHT>'
            . '<TERMSID>' . $this->esc($this->termsId) . '</TERMSID>'
            . '<FOB/>'
            . '<ESTSHIPDATE>' . $this->esc($date) . '</ESTSHIPDATE>'
            . '<DESCRIPTION>' . $this->esc($desc) . '</DESCRIPTION>'
            . '<SHIPTO1/><SHIPTO2/><SHIPTO3/><SHIPTO4/><SHIPTO5/>'
            . '<DP>0</DP>'
            . '<DPACCOUNTID>' . $this->esc($this->dpAccountId) . '</DPACCOUNTID>'
            . '<DPUSED/>'
            . '<CUSTOMERID>' . $this->esc($custId) . '</CUSTOMERID>'
            . '<PONO/>'
            . '<CURRENCYNAME>IDR</CURRENCYNAME>'
            . '</SALESORDER>';

        $delivery = '<DELIVERYORDER operation="Add" REQUESTID="1">'
            . '<TRANSACTIONID>0</TRANSACTIONID>'
            . $deliveryLines
            . '<INVOICENO>' . $this->esc($doc) . '</INVOICENO>'
            . '<INVOICEDATE>' . $this->esc($date) . '</INVOICEDATE>'
            . '<INVOICEAMOUNT>0</INVOICEAMOUNT>'
            . '<PURCHASEORDERNO/>'
            . '<WAREHOUSEID>' . $this->esc($this->warehouseId) . '</WAREHOUSEID>'
            . '<DESCRIPTION>' . $this->esc($desc) . '</DESCRIPTION>'
            . '<SHIPDATE>' . $this->esc($date) . '</SHIPDATE>'
            . '<DELIVERYORDER></DELIVERYORDER>'
            . '<CUSTOMERID>' . $this->esc($custId) . '</CUSTOMERID>'
            . '<SHIPTO1/><SHIPTO2/><SHIPTO3/><SHIPTO4/><SHIPTO5/>'
            . '<CURRENCYNAME>IDR</CURRENCYNAME>'
            . '<AUTOMATICINSERTGROUPING/>'
            . '</DELIVERYORDER>';

        $invoice = '<SALESINVOICE operation="Add" REQUESTID="1">'
            . '<TRANSACTIONID>0</TRANSACTIONID>'
            . $invoiceLines
            . '<INVOICENO>' . $this->esc($doc) . '</INVOICENO>'
            . '<INVOICEDATE>' . $this->esc($date) . '</INVOICEDATE>'
            . '<TAX1ID>' . $this->esc($this->tax1Id) . '</TAX1ID>'
            . '<TAX2ID>' . $this->esc($this->tax2Id) . '</TAX2ID>'
            . '<TAX1CODE>' . $this->esc($this->tax1Id) . '</TAX1CODE>'
            . '<TAX2CODE>' . $this->esc($this->tax2Id) . '</TAX2CODE>'
            . '<TAX1RATE>' . $this->num($this->taxRate) . '</TAX1RATE>'
            . '<TAX2RATE>' . $this->num($this->tax2Rate) . '</TAX2RATE>'
            . '<RATE>1</RATE>'
            . '<INCLUSIVETAX>0</INCLUSIVETAX>'
            . '<CUSTOMERISTAXABLE>1</CUSTOMERISTAXABLE>'
            . '<CASHDISCOUNT>' . $this->num($discount) . '</CASHDISCOUNT>'
            . '<CASHDISCPC/>'
            . '<INVOICEAMOUNT>' . $this->num($invoiceAmount) . '</INVOICEAMOUNT>'
            . '<FREIGHT>0</FREIGHT>'
            . '<TERMSID>' . $this->esc($this->termsId) . '</TERMSID>'
            . '<FOB/>'
            . '<PURCHASEORDERNO/>'
            . '<WAREHOUSEID>' . $this->esc($this->warehouseId) . '</WAREHOUSEID>'
            . '<DESCRIPTION>' . $this->esc($desc) . '</DESCRIPTION>'
            . '<SHIPDATE>' . $this->esc($date) . '</SHIPDATE>'
            . '<DELIVERYORDER></DELIVERYORDER>'
            . '<FISCALRATE>1</FISCALRATE>'
            . '<TAXDATE>' . $this->esc($date) . '</TAXDATE>'
            . '<CUSTOMERID>' . $this->esc($custId) . '</CUSTOMERID>'
            . '<PRINTED>0</PRINTED>'
            . '<SHIPTO1/><SHIPTO2/><SHIPTO3/><SHIPTO4/><SHIPTO5/>'
            . '<ARACCOUNT>' . $this->esc($this->arAccount) . '</ARACCOUNT>'
            . '<TAXFORMNUMBER/>'
            . '<TAXFORMCODE/>'
            . '<CURRENCYNAME>IDR</CURRENCYNAME>'
            . '<AUTOMATICINSERTGROUPING/>'
            . '</SALESINVOICE>';

        return [$order, $delivery, $invoice];
    }

    private function reservedTags(): string
    {
        $out = '';
        for ($i = 1; $i <= 10; $i++) {
            $out .= '<ITEMRESERVED' . $i . '/>';
        }

        return $out;
    }

    private function loadMappings(): void
    {
        foreach (MappingCustomer::query()->get(['kode_internal', 'kode_external']) as $r) {
            $k = (string) $r->kode_internal;
            if ($k !== '' && ! array_key_exists($k, $this->custMap)) {
                $this->custMap[$k] = (string) $r->kode_external;
            }
        }

        foreach (MappingProduct::query()->get(['kode_item_internal', 'kode_item_external', 'nama_item_internal', 'nama_item_external']) as $r) {
            $code = (string) $r->kode_item_internal;
            if ($code !== '' && ! array_key_exists($code, $this->itemCodeMap)) {
                $this->itemCodeMap[$code] = (string) $r->kode_item_external;
            }

            $name = (string) $r->nama_item_internal;
            if ($name !== '' && ! array_key_exists($name, $this->itemNameMap)) {
                $this->itemNameMap[$name] = (string) $r->nama_item_external;
            }
        }
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
