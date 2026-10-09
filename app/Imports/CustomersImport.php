<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\PaymentTerm;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CustomersImport implements ToCollection
{
    private const TYPE_MAP = [
        'TOKO - TOKO' => 'RETAIL',
        'SUPERMARKET' => 'RETAIL',
        'ONLINE' => 'RETAIL',
        'FRUIT' => 'DISTRIBUTOR',
        'VEGETABLE' => 'DISTRIBUTOR',
        'MUSHROOM' => 'DISTRIBUTOR',
        'CARROT' => 'DISTRIBUTOR',
        'DRIED MUSHROOM' => 'DISTRIBUTOR',
        'GINGKO' => 'DISTRIBUTOR',
        'GINGER' => 'DISTRIBUTOR',
        'FROZEN' => 'DISTRIBUTOR',
    ];

    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;

    private ?CustomerGroup $defaultGroup = null;
    private ?PaymentTerm $defaultTerm = null;

    public function __construct(private readonly string $sourceType = Customer::SOURCE_EXTERNAL)
    {
        if (!in_array($this->sourceType, [Customer::SOURCE_INTERNAL, Customer::SOURCE_EXTERNAL], true)) {
            $this->sourceType = Customer::SOURCE_EXTERNAL;
        }
    }

    public function collection(Collection $rows): void
    {
        $rows = $rows->map(fn ($row) => $this->rowToArray($row))->values();
        $detected = $this->detectFormat($rows);

        if ($detected === null) {
            $this->skipped += $rows->count();
            return;
        }

        [$format, $headerIndex] = $detected;

        if ($format === 'accurate') {
            $this->importAccurate($rows, $headerIndex);
        } else {
            $this->importTemplate($rows, $headerIndex);
        }
    }

    private function rowToArray(mixed $row): array
    {
        if ($row instanceof Collection) {
            $row = $row->all();
        } elseif (is_object($row) && method_exists($row, 'toArray')) {
            $row = $row->toArray();
        } elseif (!is_array($row)) {
            return [];
        }

        return array_values(array_map(function ($cell) {
            if ($cell === null) {
                return null;
            }

            if (is_object($cell)) {
                if (method_exists($cell, 'getPlainText')) {
                    return $cell->getPlainText();
                }

                if (method_exists($cell, '__toString')) {
                    return (string) $cell;
                }

                return null;
            }

            return $cell;
        }, $row));
    }

    private function detectFormat(Collection $rows): ?array
    {
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($row as $cell) {
                if ($cell === null) {
                    continue;
                }

                $normalized = strtolower(trim((string) $cell));

                if ($normalized === 'customer_code') {
                    return ['template', $index];
                }

                if (in_array($normalized, ['customer no.', 'customer no'], true)) {
                    return ['accurate', $index];
                }
            }
        }

        return null;
    }

    private function importAccurate(Collection $rows, int $headerIndex): void
    {
        for ($i = $headerIndex + 1; $i < $rows->count(); $i++) {
            $row = $rows[$i] ?? null;
            if (!is_array($row)) {
                continue;
            }

            $code = $this->clean($row[1] ?? null);
            $name = $this->clean($row[3] ?? null);

            if ($code === '' || $name === '') {
                $this->skipped++;
                continue;
            }

            $rawType = $this->clean($row[5] ?? null);
            $phone = $this->clean($row[7] ?? null);
            $contact = $this->clean($row[9] ?? null);
            $address1 = $this->clean($row[15] ?? null);
            $address2 = $this->clean($row[17] ?? null);
            $city = $this->clean($row[19] ?? null);
            $email = $this->clean($row[21] ?? null);
            $creditLimit = $this->parseAmount($row[23] ?? null);

            if ($phone === '' && $contact !== '') {
                $phone = $this->extractPhone($contact);
            }

            $address = trim($address1 . ' ' . $address2);
            $status = 'ACTIVE';
            $type = 'CORPORATE';

            if ($rawType !== '') {
                $upper = mb_strtoupper($rawType);
                if ($upper === 'NON AKTIF' || $upper === 'NON-AKTIF') {
                    $status = 'INACTIVE';
                } elseif (isset(self::TYPE_MAP[$upper])) {
                    $type = self::TYPE_MAP[$upper];
                } elseif (in_array($upper, ['DISTRIBUTOR', 'CORPORATE', 'RETAIL', 'GOVERNMENT', 'RESELLER', 'INTERNAL'], true)) {
                    $type = $upper;
                }
            }

            $notes = $contact !== '' ? $contact : null;

            $payload = [
                'customer_name' => $this->limit($name, 255),
                'customer_group_id' => $this->defaultGroup()->id,
                'customer_type' => $type,
                'tax_number' => null,
                'address' => $address !== '' ? $this->limit($address, 500) : null,
                'city' => $city !== '' ? $this->limit($city, 100) : null,
                'phone' => $phone !== '' ? $this->limit($phone, 255) : null,
                'email' => $email !== '' ? $this->limit($email, 255) : null,
                'payment_term_id' => $this->defaultTerm()->id,
                'credit_limit' => $creditLimit,
                'status' => $status,
                'notes' => $notes !== null ? $this->limit($notes, 500) : null,
                'source_type' => $this->sourceType,
            ];

            $this->upsert($code, $payload);
        }
    }

    private function importTemplate(Collection $rows, int $headerIndex): void
    {
        $headings = [];
        $headerRow = $rows[$headerIndex] ?? [];

        if (is_array($headerRow)) {
            foreach ($headerRow as $col => $heading) {
                $headings[$col] = strtolower(trim((string) $heading));
            }
        }

        for ($i = $headerIndex + 1; $i < $rows->count(); $i++) {
            $row = $rows[$i] ?? null;
            if (!is_array($row)) {
                continue;
            }

            $assoc = [];
            foreach ($headings as $col => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = $row[$col] ?? null;
            }

            $code = $this->clean($assoc['customer_code'] ?? null);
            $name = $this->clean($assoc['customer_name'] ?? null);

            if ($code === '' || $name === '') {
                $this->skipped++;
                continue;
            }

            $group = null;
            $groupCode = $this->clean($assoc['group_code'] ?? null);
            $groupName = $this->clean($assoc['group_name'] ?? null);

            if ($groupCode !== '') {
                $group = CustomerGroup::where('group_code', $groupCode)->first();
            }
            if (!$group && $groupName !== '') {
                $group = CustomerGroup::where('group_name', $groupName)->first();
            }
            if (!$group) {
                $group = $this->defaultGroup();
            }

            $term = null;
            $termCode = $this->clean($assoc['payment_term'] ?? null);
            if ($termCode !== '') {
                $term = PaymentTerm::where('term_code', $termCode)->first()
                    ?? PaymentTerm::where('term_name', $termCode)->first();
            }
            if (!$term) {
                $term = $this->defaultTerm();
            }

            $type = mb_strtoupper($this->clean($assoc['customer_type'] ?? null));
            if (!in_array($type, ['DISTRIBUTOR', 'CORPORATE', 'RETAIL', 'GOVERNMENT', 'RESELLER', 'INTERNAL'], true)) {
                $type = 'CORPORATE';
            }

            $status = mb_strtoupper($this->clean($assoc['status'] ?? null));
            if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
                $status = 'ACTIVE';
            }

            $payload = [
                'customer_name' => $this->limit($name, 255),
                'customer_group_id' => $group->id,
                'customer_type' => $type,
                'tax_number' => $this->nullableLimit($assoc['tax_number'] ?? null, 30),
                'address' => $this->nullableLimit($assoc['address'] ?? null, 500),
                'city' => $this->nullableLimit($assoc['city'] ?? null, 100),
                'phone' => $this->nullableLimit($assoc['phone'] ?? null, 255),
                'email' => $this->nullableLimit($assoc['email'] ?? null, 255),
                'payment_term_id' => $term->id,
                'credit_limit' => $this->parseAmount($assoc['credit_limit'] ?? null),
                'status' => $status,
                'notes' => $this->nullableLimit($assoc['notes'] ?? null, 500),
                'source_type' => $this->sourceType,
            ];

            $this->upsert($code, $payload);
        }
    }

    private function upsert(string $code, array $payload): void
    {
        $code = $this->limit($code, 20);

        $customer = Customer::withTrashed()
            ->where('customer_code', $code)
            ->where('source_type', $this->sourceType)
            ->first();

        if ($customer) {
            if ($customer->trashed()) {
                $customer->restore();
            }
            $customer->fill($payload);
            if ($customer->isDirty()) {
                $customer->save();
                $this->updated++;
            } else {
                $this->updated++;
            }
        } else {
            Customer::create($payload + ['customer_code' => $code]);
            $this->created++;
        }
    }

    private function defaultGroup(): CustomerGroup
    {
        if (!$this->defaultGroup) {
            $this->defaultGroup = CustomerGroup::firstOrCreate(
                ['group_code' => 'DEFAULT'],
                [
                    'group_name' => 'Uncategorized',
                    'description' => 'Default group for import',
                    'status' => 'ACTIVE',
                ]
            );
        }

        return $this->defaultGroup;
    }

    private function defaultTerm(): PaymentTerm
    {
        if (!$this->defaultTerm) {
            $this->defaultTerm = PaymentTerm::firstOrCreate(
                ['term_code' => 'COD'],
                [
                    'term_name' => 'Cash On Delivery',
                    'days' => 0,
                    'description' => 'Default payment term for import',
                    'status' => 'ACTIVE',
                ]
            );
        }

        return $this->defaultTerm;
    }

    private function clean(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_numeric($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return '';
        }

        return trim($value);
    }

    private function limit(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    private function nullableLimit(mixed $value, int $max): ?string
    {
        $value = $this->clean($value);
        if ($value === '') {
            return null;
        }

        return $this->limit($value, $max);
    }

    private function extractPhone(string $contact): string
    {
        if (preg_match('/(?:\+?62|0)8\d{7,12}/', $contact, $mobile)) {
            return $mobile[0];
        }

        if (preg_match('/0\d{2,3}[-\s]?\d{3,4}[-\s]?\d{3,4}/', $contact, $landline)) {
            return $landline[0];
        }

        return '';
    }

    private function parseAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        $normalized = str_replace([' ', "\xa0"], '', trim($value));

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            if (preg_match('/,\d{1,2}$/', $normalized)) {
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }
}
