<?php

namespace App\Services;

use App\Models\Customer;

class CustomerTaxNumberSync
{
    /**
     * @return array{updated:int,missing:int,skipped:int,missing_codes:array<int,string>}
     */
    public function importWorkbook(string $path, string $sourceType): array
    {
        $sheets = $sourceType === Customer::SOURCE_INTERNAL
            ? ['Customer internal']
            : ['Customer eksternal'];

        $rows = SpreadsheetSheetReader::rows(
            $path,
            $sheets,
            ['PERSONNO', 'TAX1EXEMPTIONNO']
        );

        $updated = 0;
        $missing = 0;
        $skipped = 0;
        $missingCodes = [];

        foreach ($rows as $row) {
            $code = trim((string) ($row['PERSONNO'] ?? ''));
            if ($code === '') {
                $skipped++;
                continue;
            }

            $tin = (string) preg_replace('/\D/', '', (string) ($row['TAX1EXEMPTIONNO'] ?? ''));

            if (strlen($tin) !== 16) {
                $skipped++;
                continue;
            }

            $customer = Customer::query()
                ->where('source_type', $sourceType)
                ->where('customer_code', $code)
                ->first();

            if ($customer === null) {
                $missing++;
                if (count($missingCodes) < 50) {
                    $missingCodes[] = $code;
                }
                continue;
            }

            if ($customer->tax_number !== $tin) {
                $customer->tax_number = $tin;
                $customer->save();
                $updated++;
            }
        }

        return [
            'updated' => $updated,
            'missing' => $missing,
            'skipped' => $skipped,
            'missing_codes' => $missingCodes,
        ];
    }
}
