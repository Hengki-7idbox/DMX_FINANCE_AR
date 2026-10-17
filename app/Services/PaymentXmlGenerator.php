<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Bangun file XML pelunasan Accurate (NMEXML / CUSTOMERRECEIPT) — format sama
 * dengan file upload (Sample/des-2025.xml), siap diimport ke Accurate 5.
 */
class PaymentXmlGenerator
{
    /**
     * @param  array<int, array<string, mixed>>  $receipts
     *        Tiap receipt: sequence_no, payment_date, bank_account, cheque_no,
     *        cheque_date, cheque_amount, bill_to_id, description, void,
     *        transaction_id (opsional), lines: [{ar_invoice_id, payment_amount,
     *        pph23_amount, disctaken_amount, discount?, disc_account?}]
     * @param  string|null  $eximId  Override EximID root (default: Setting exim_id)
     */
    public function build(array $receipts, ?string $eximId = null): string
    {
        $eximId = htmlspecialchars($eximId ?? (string) Setting::get('exim_id', ''), ENT_XML1);
        $branchCode = htmlspecialchars((string) Setting::get('branch_code_external', ''), ENT_XML1);

        $body = '';
        $requestId = 0;

        foreach ($receipts as $receipt) {
            $requestId++;
            $body .= $this->receiptXml($receipt, $requestId);
        }

        return '<?xml version="1.0"?>' . "\n"
            . '<NMEXML EximID="' . $eximId . '" BranchCode="' . $branchCode . '" ACCOUNTANTCOPYID="">'
            . '<TRANSACTIONS OnError="CONTINUE">'
            . $body
            . '</TRANSACTIONS></NMEXML>';
    }

    private function receiptXml(array $receipt, int $requestId): string
    {
        $transactionId = $this->e($receipt['transaction_id'] ?? null);
        $sequenceNo = $this->e($receipt['sequence_no'] ?? null);
        $paymentDate = $this->e($receipt['payment_date'] ?? null);
        $bankAccount = $this->e($receipt['bank_account'] ?? null);
        $chequeNo = $this->e($receipt['cheque_no'] ?? null);
        $chequeDate = $this->e($receipt['cheque_date'] ?? $receipt['payment_date'] ?? null);
        $billTo = $this->e($receipt['bill_to_id'] ?? null);
        $description = $this->e($receipt['description'] ?? null);
        $void = ! empty($receipt['void']) ? '1' : '0';

        $total = 0.0;
        $lines = '';
        foreach ($receipt['lines'] ?? [] as $i => $line) {
            $amount = (float) ($line['payment_amount'] ?? 0);
            $total += $amount;
            $discount = (float) ($line['discount'] ?? 0);
            $invoiceLine = '<InvoiceLine operation="Add">'
                . '<KeyID>' . ($i + 1) . '</KeyID>'
                . '<PAYMENTAMOUNT>' . number_format($amount, 2, '.', '') . '</PAYMENTAMOUNT>'
                . '<PPH23AMOUNT>' . number_format((float) ($line['pph23_amount'] ?? 0), 2, '.', '') . '</PPH23AMOUNT>'
                . '<PPH23RATE>0</PPH23RATE>'
                . '<PPH23FISCALRATE>1</PPH23FISCALRATE>'
                . '<PPH23NUMBER/>'
                . '<DISCTAKENAMOUNT>' . number_format((float) ($line['disctaken_amount'] ?? 0), 2, '.', '') . '</DISCTAKENAMOUNT>';
            if ($discount > 0) {
                $invoiceLine .= '<DiscountLine operation="Ret">'
                    . '<KeyID>' . ($i + 1) . '</KeyID>'
                    . '<DISCOUNT>' . number_format($discount, 2, '.', '') . '</DISCOUNT>'
                    . '<DISCACCOUNT>' . $this->e($line['disc_account'] ?? null) . '</DISCACCOUNT>'
                    . '</DiscountLine>';
            }
            $invoiceLine .= '<ARINVOICEID>' . $this->e($line['ar_invoice_id']) . '</ARINVOICEID>'
                . '</InvoiceLine>';
            $lines .= $invoiceLine;
        }

        $chequeAmount = $receipt['cheque_amount'] ?? $total;

        return '<CUSTOMERRECEIPT operation="Add" REQUESTID="' . $requestId . '">'
            . '<TRANSACTIONID>' . $transactionId . '</TRANSACTIONID>'
            . '<IMPORTEDTRANSACTIONID/>'
            . $lines
            . '<SEQUENCENO>' . $sequenceNo . '</SEQUENCENO>'
            . '<PAYMENTDATE>' . $paymentDate . '</PAYMENTDATE>'
            . '<CHEQUENO>' . $chequeNo . '</CHEQUENO>'
            . '<BANKACCOUNT>' . $bankAccount . '</BANKACCOUNT>'
            . '<CHEQUEDATE>' . $chequeDate . '</CHEQUEDATE>'
            . '<CHEQUEAMOUNT>' . number_format((float) $chequeAmount, 2, '.', '') . '</CHEQUEAMOUNT>'
            . '<RATE>1</RATE>'
            . '<DESCRIPTION>' . $description . '</DESCRIPTION>'
            . '<FISCALPMT>0</FISCALPMT>'
            . '<VOID>' . $void . '</VOID>'
            . '<BILLTOID>' . $billTo . '</BILLTOID>'
            . '<OVERPAYUSED/>'
            . '<APPLYFROMCREDIT>0</APPLYFROMCREDIT>'
            . '<CURRENCYNAME>IDR</CURRENCYNAME>'
            . '<RETURNCREDIT>0</RETURNCREDIT>'
            . '</CUSTOMERRECEIPT>';
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_XML1);
    }
}
