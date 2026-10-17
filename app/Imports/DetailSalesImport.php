<?php

namespace App\Imports;

use App\Models\DetailSale;
use App\Models\ImportSessionDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Import Detail Sales (per invoice) dari export Accurate Online
 * (Sample/Detail Sales.xls — "Sales By Customer Detail").
 *
 * Header sparse baris 7 (0-based): 1=B Customer No., 3=D Name, 5=F Invoice No.,
 * 7=H Date, 9=J Description, 11=L Invoice Amount, 13=N Amount, 15=P Tax Base,
 * 17=R Discount, 19=T Return, 21=V Owing, 23=X Payment, 25=Z Calculate Field.
 *
 * Data mulai baris setelah header; skip header berulang (page break),
 * baris footer Accurate, dan baris total (tanpa customer/invoice).
 * Amount format Eropa "7215000,00"; tanggal "09 Sep 2026".
 * Upsert pada (source_type, doc_number).
 *
 * WithChunkReading: file dibaca per 500 baris (ChunkReadFilter) sehingga
 * memori hanya menampung 1 chunk — file export besar (banyak bulan) tidak
 * meledakkan memory_limit saat PhpSpreadsheet load workbook.
 */
class DetailSalesImport implements ToCollection, WithChunkReading
{
    private const COL_CODE = 1;
    private const COL_NAME = 3;
    private const COL_INVOICE = 5;
    private const COL_DATE = 7;
    private const COL_DESC = 9;
    private const COL_INVOICE_AMOUNT = 11;
    private const COL_AMOUNT = 13;
    private const COL_TAX_BASE = 15;
    private const COL_DISCOUNT = 17;
    private const COL_RETURN = 19;
    private const COL_OWING = 21;
    private const COL_PAYMENT = 23;
    private const COL_CALC = 25;

    private const AMOUNT_FIELDS = [
        'invoice_amount', 'amount', 'tax_base', 'discount',
        'return_amount', 'owing', 'payment_amount', 'calculate_field',
    ];

    private int $created = 0;
    private int $changed = 0;
    private int $unchanged = 0;
    private int $skipped = 0;
    private int $rowsRead = 0;
    private bool $headerDetected = false;
    private bool $headerFound = false;

    public function chunkSize(): int
    {
        // 2000 = keseimbangan: file export besar (mis. 32rb baris / 7MB) cukup
        // ~17x parse ulang (cepat), memori per chunk tetap ±20-40MB (aman).
        return 2000;
    }

    public function __construct(
        private readonly string $sourceType = DetailSale::SOURCE_EXTERNAL,
        private readonly ?int $sessionId = null,
    ) {
        if (!in_array($this->sourceType, [DetailSale::SOURCE_INTERNAL, DetailSale::SOURCE_EXTERNAL], true)) {
            $this->sourceType = DetailSale::SOURCE_EXTERNAL;
        }
    }

    public function collection(Collection $rows): void
    {
        $rows = $rows->map(fn ($row) => $this->rowToArray($row))->values();
        $this->rowsRead += $rows->count();

        // Dipanggil per chunk (WithChunkReading): deteksi header hanya sampai
        // ketemu sekali; chunk berikutnya dianggap lanjutan data langsung.
        $headerIndex = $this->headerFound ? null : $this->detectHeader($rows);

        if ($headerIndex !== null) {
            $this->headerFound = true;
            $this->headerDetected = true;
        } elseif (!$this->headerFound) {
            $this->skipped += $rows->count();
            return;
        }

        $start = $headerIndex !== null ? $headerIndex + 1 : 0;

        for ($i = $start; $i < $rows->count(); $i++) {
            $row = $rows[$i] ?? null;
            if (!is_array($row)) {
                $this->skipped++;
                continue;
            }

            $code = $this->clean($row[self::COL_CODE] ?? null);
            $name = $this->clean($row[self::COL_NAME] ?? null);
            $invoice = $this->clean($row[self::COL_INVOICE] ?? null);

            $isHeaderRepeat = in_array(strtolower($code), ['customer no.', 'customer no'], true)
                || strtolower($invoice) === 'invoice no.';

            if ($isHeaderRepeat) {
                continue;
            }

            $isFooter = str_contains($code, 'accurate accounting')
                || str_starts_with($code, 'printed on')
                || str_starts_with($code, 'page ');

            if ($isFooter || ($code === '' && $invoice === '')) {
                $this->skipped++;
                continue;
            }

            if ($invoice === '') {
                $this->skipped++;
                continue;
            }

            try {
                $payload = [
                    'customer_code' => $code,
                    'customer_name' => $name,
                    'doc_number' => $invoice,
                    'transaction_date' => $this->parseDate($row[self::COL_DATE] ?? null),
                    'description' => $this->limit($this->clean($row[self::COL_DESC] ?? null), 500),
                    'invoice_amount' => $this->parseAmount($row[self::COL_INVOICE_AMOUNT] ?? null),
                    'amount' => $this->parseAmount($row[self::COL_AMOUNT] ?? null),
                    'tax_base' => $this->parseAmount($row[self::COL_TAX_BASE] ?? null),
                    'discount' => $this->parseAmount($row[self::COL_DISCOUNT] ?? null),
                    'return_amount' => $this->parseAmount($row[self::COL_RETURN] ?? null),
                    'owing' => $this->parseAmount($row[self::COL_OWING] ?? null),
                    'payment_amount' => $this->parseAmount($row[self::COL_PAYMENT] ?? null),
                    'calculate_field' => $this->parseAmount($row[self::COL_CALC] ?? null),
                ];

                $this->upsert($payload);
            } catch (\Illuminate\Database\QueryException $e) {
                // Koneksi/DB bermasalah — gagalkan seluruh import agar jelas, jangan lanjut baris demi baris.
                throw $e;
            } catch (\Throwable $e) {
                // Satu baris rusak tidak boleh menggagalkan import seluruh file.
                $this->skipped++;
                Log::warning('DetailSalesImport: baris dilewati', [
                    'source_type' => $this->sourceType,
                    'session_id' => $this->sessionId,
                    'doc_number' => $invoice,
                    'row' => $i,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function detectHeader(Collection $rows): ?int
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
                if (in_array($normalized, ['customer no.', 'customer no'], true)) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function upsert(array $payload): void
    {
        $doc = $this->limit($payload['doc_number'], 50);

        $existing = DetailSale::where('source_type', $this->sourceType)
            ->where('doc_number', $doc)
            ->first();

        $data = [
            'customer_code' => $this->limit($payload['customer_code'], 50),
            'customer_name' => $this->limit($payload['customer_name'], 255),
            'transaction_date' => $payload['transaction_date'],
            'description' => $this->limit($payload['description'], 500),
            'invoice_amount' => $payload['invoice_amount'],
            'amount' => $payload['amount'],
            'tax_base' => $payload['tax_base'],
            'discount' => $payload['discount'],
            'return_amount' => $payload['return_amount'],
            'owing' => $payload['owing'],
            'payment_amount' => $payload['payment_amount'],
            'calculate_field' => $payload['calculate_field'],
        ];

        if ($existing === null) {
            DetailSale::create($data + [
                'source_type' => $this->sourceType,
                'doc_number' => $doc,
            ]);
            $this->created++;
            $this->logDetail($doc, ImportSessionDetail::TYPE_NEW, null, $data);

            return;
        }

        // Bandingkan field satu per satu — jika ada selisih → "changed".
        $oldValues = [];
        foreach (array_keys($data) as $field) {
            if ($this->valuesDiffer($existing->getAttribute($field), $data[$field], $field)) {
                $oldValues[$field] = $this->normalizeForJson($existing->getAttribute($field), $field);
            }
        }

        if ($oldValues !== []) {
            $existing->fill($data);
            $existing->save();
            $this->changed++;
            $this->logDetail($doc, ImportSessionDetail::TYPE_CHANGED, $oldValues, $data);
        } else {
            // Identik — data tidak disentuh (updated_at tidak ikut berubah).
            $this->unchanged++;
            $this->logDetail($doc, ImportSessionDetail::TYPE_UNCHANGED, null, $data);
        }
    }

    private function valuesDiffer(mixed $old, mixed $new, string $field): bool
    {
        if ($field === 'transaction_date') {
            $oldDate = $old instanceof \DateTimeInterface ? $old->format('Y-m-d') : $old;

            return ($oldDate ?? null) !== ($new ?? null);
        }

        if (in_array($field, self::AMOUNT_FIELDS, true)) {
            return (float) ($old ?? 0) !== (float) ($new ?? 0);
        }

        return trim((string) ($old ?? '')) !== trim((string) ($new ?? ''));
    }

    private function normalizeForJson(mixed $old, string $field): mixed
    {
        if ($old === null) {
            return null;
        }

        if ($field === 'transaction_date') {
            return $old instanceof \DateTimeInterface ? $old->format('Y-m-d') : (string) $old;
        }

        if (in_array($field, self::AMOUNT_FIELDS, true)) {
            return (float) $old;
        }

        return (string) $old;
    }

    private function logDetail(string $doc, string $type, ?array $oldValues, array $newValues): void
    {
        if ($this->sessionId === null) {
            return;
        }

        ImportSessionDetail::create([
            'session_id' => $this->sessionId,
            'doc_number' => $doc,
            'change_type' => $type,
            'transaction_date' => $newValues['transaction_date'] ?? null,
            'customer_name' => $newValues['customer_name'] ?? null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
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

    private function parseDate(mixed $value): ?string
    {
        $raw = $this->clean($value);
        if ($raw === '') {
            return null;
        }

        // Export Accurate locale Indonesia: "02 Des 2025" / "'02 Des 2025"
        // (apostrophe = text-format prefix di .xls). strtotime hanya mengerti
        // bulan Inggris — terjemahkan dulu, fallback ke strtotime untuk format lama.
        $raw = ltrim($raw, "'");
        $idToEn = [
            'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
            'Mei' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul',
            'Agu' => 'Aug', 'Agt' => 'Aug', 'Sep' => 'Sep',
            'Okt' => 'Oct', 'Nov' => 'Nov', 'Des' => 'Dec',
        ];
        $normalized = preg_replace_callback(
            '/\b(Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Agt|Sep|Okt|Nov|Des)\b/iu',
            fn ($m) => $idToEn[ucfirst(strtolower($m[1]))] ?? $m[1],
            $raw
        );

        $timestamp = strtotime($normalized !== null ? $normalized : $raw);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
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

        if (preg_match('/^-(\d+),(\d+)$/', $normalized, $m)) {
            return -(float) ($m[1] . '.' . $m[2]);
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getChangedCount(): int
    {
        return $this->changed;
    }

    public function getUnchangedCount(): int
    {
        return $this->unchanged;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function getRowsRead(): int
    {
        return $this->rowsRead;
    }

    public function isHeaderDetected(): bool
    {
        return $this->headerDetected;
    }
}
