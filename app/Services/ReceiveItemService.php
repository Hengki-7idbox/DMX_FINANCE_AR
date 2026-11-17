<?php

namespace App\Services;

use App\Models\MappingProduct;
use App\Models\MappingVendor;
use App\Models\ReceiveItemDraft;
use App\Models\ReceiveItemDraftLine;
use App\Models\ReceiveItemImport;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ReceiveItemService
{
    /** Batas aman Accurate: 8 request/detik & 8 paralel. */
    private const CONCURRENCY = 6;

    /** Jumlah dokumen per request bulk-save. */
    private const DOCS_PER_REQUEST = 4;

    private const ITEM_CHUNK = 40;

    public function __construct(
        private readonly AccurateApiClient $client,
        private readonly ReceiveItemXmlParser $parser,
    ) {
    }

    /**
     * Baca XML, simpan draft, petakan vendor/item, dan cek duplikat ke Accurate.
     */
    public function import(string $path, ?string $originalFilename, ?int $userId = null): ReceiveItemImport
    {
        $parsed = $this->parser->parseFile($path);
        $documents = $parsed['documents'];

        $import = ReceiveItemImport::create([
            'original_filename' => $originalFilename,
            'exim_id' => $parsed['exim_id'],
            'branch_code' => $parsed['branch_code'],
            'total_documents' => count($documents),
            'total_lines' => array_sum(array_map(fn (array $doc) => count($doc['lines']), $documents)),
            'user_id' => $userId,
        ]);

        $productMap = $this->productMap($documents);
        $itemResult = $this->fetchItems($this->externalCodes($productMap));
        $items = $itemResult['items'];
        $itemError = $itemResult['ok'] ? null : $itemResult['message'];

        $existingResult = $this->fetchExistingNumbers();
        $existing = $existingResult['numbers'];

        $vendorMap = MappingVendor::query()
            ->get()
            ->keyBy(fn (MappingVendor $vendor) => $this->normalize($vendor->kode_vendor_internal));

        $warehouseName = (string) Setting::get('accurate_warehouse_name', 'Utama');

        $newCount = 0;
        $existingCount = 0;

        DB::transaction(function () use (
            $documents,
            $import,
            $productMap,
            $items,
            $itemError,
            $existing,
            $vendorMap,
            $warehouseName,
            &$newCount,
            &$existingCount
        ) {
            foreach ($documents as $doc) {
                $number = trim((string) ($doc['receive_number'] ?? ''));
                if ($number === '') {
                    continue;
                }

                $key = $this->normalize($number);
                $accurate = $existing[$key] ?? null;
                $isExisting = $accurate !== null;

                $vendorInternal = $doc['vendor_internal'];
                $vendor = $vendorMap[$this->normalize($vendorInternal)] ?? null;
                $vendorExternal = $this->nullIfEmpty($vendor?->kode_vendor_external);

                if ($isExisting) {
                    $status = ReceiveItemDraft::STATUS_EXISTS;
                    $message = 'Dokumen sudah ada di Accurate (' . ($accurate['number'] ?? '-') . ').';
                    $existingCount++;
                } elseif ($vendorExternal === null) {
                    $status = ReceiveItemDraft::STATUS_INVALID;
                    $message = 'Vendor ' . ($vendorInternal ?: '-') . ' belum dipetakan ke vendor Accurate.';
                } else {
                    $status = ReceiveItemDraft::STATUS_NEW;
                    $message = null;
                    $newCount++;
                }

                $draft = $import->drafts()->create([
                    'receive_number' => $number,
                    'transaction_id' => $doc['transaction_id'],
                    'sequence_no' => $doc['sequence_no'],
                    'vendor_internal' => $vendorInternal,
                    'vendor_external' => $vendorExternal,
                    'vendor_name' => $this->nullIfEmpty($vendor?->nama_vendor_external),
                    'trans_date' => $doc['trans_date'],
                    'ship_date' => $doc['ship_date'],
                    'tax_date' => $doc['tax_date'],
                    'rate' => $doc['rate'],
                    'currency_code' => $doc['currency_code'],
                    'taxable' => $doc['taxable'],
                    'inclusive_tax' => $doc['inclusive_tax'],
                    'payment_term_name' => $doc['payment_term_name'],
                    'description' => $doc['description'],
                    'warehouse_name' => $warehouseName,
                    'total_amount' => $doc['total_amount'],
                    'status' => $status,
                    'accurate_id' => $accurate['id'] ?? null,
                    'accurate_number' => $accurate['number'] ?? null,
                    'message' => $message,
                ]);

                foreach ($doc['lines'] as $line) {
                    $internal = $this->normalize($line['item_internal']);
                    $product = $productMap[$internal] ?? null;
                    $external = $this->nullIfEmpty($product?->kode_item_external);
                    $external = $external !== null ? $this->normalize($external) : null;
                    $item = $external !== null ? ($items[$external] ?? null) : null;

                    $model = new ReceiveItemDraftLine([
                        'receive_item_draft_id' => $draft->id,
                        'line_no' => $line['line_no'],
                        'key_id' => $line['key_id'],
                        'item_internal' => $line['item_internal'],
                        'item_external' => $external,
                        'item_name' => $item['name'] ?? null,
                        'description' => $line['description'],
                        'quantity_xml' => $line['quantity_xml'],
                        'unit_xml' => $line['unit_xml'],
                        'unit_ratio' => $line['unit_ratio'],
                        'price_xml' => $line['price_xml'],
                        'disc_percent' => $line['disc_percent'],
                        'warehouse_name' => $warehouseName,
                    ]);

                    $this->applyConversion($model, $item, $itemError);
                    $model->save();
                }
            }
        });

        $import->update([
            'new_documents' => $newCount,
            'existing_documents' => $existingCount,
            'duplicate_check_status' => $existingResult['ok'] ? 'ok' : 'failed',
            'duplicate_check_message' => $existingResult['ok'] ? null : $existingResult['message'],
            'checked_at' => now(),
        ]);

        Cache::forget('accurate.receive-items.existing');

        return $import->fresh();
    }

    /**
     * Cek ulang nomor dokumen ke Accurate dan perbarui status draft.
     *
     * @return array{ok:bool,message:?string,existing:int,new:int}
     */
    public function recheckDuplicates(ReceiveItemImport $import): array
    {
        $result = $this->fetchExistingNumbers(true);

        if (! $result['ok']) {
            $import->update([
                'duplicate_check_status' => 'failed',
                'duplicate_check_message' => $result['message'],
                'checked_at' => now(),
            ]);

            return ['ok' => false, 'message' => $result['message'], 'existing' => 0, 'new' => 0];
        }

        $existing = $result['numbers'];
        $existingCount = 0;
        $newCount = 0;

        foreach ($import->drafts()->withCount('lines')->get() as $draft) {
            $key = $this->normalize($draft->receive_number);
            $accurate = $existing[$key] ?? null;

            if ($accurate !== null) {
                $draft->status = ReceiveItemDraft::STATUS_EXISTS;
                $draft->accurate_id = $accurate['id'] ?? $draft->accurate_id;
                $draft->accurate_number = $accurate['number'] ?? $draft->accurate_number;
                $draft->message = 'Dokumen sudah ada di Accurate (' . ($accurate['number'] ?? '-') . ').';
                $existingCount++;
            } elseif ($draft->status === ReceiveItemDraft::STATUS_EXISTS) {
                // Sebelumnya ada, sekarang tidak ditemukan (mis. dihapus di Accurate).
                $draft->status = $draft->vendor_external ? ReceiveItemDraft::STATUS_NEW : ReceiveItemDraft::STATUS_INVALID;
                $draft->accurate_id = null;
                $draft->accurate_number = null;
                $draft->message = $draft->vendor_external ? null : 'Vendor belum dipetakan ke vendor Accurate.';
                $newCount++;
            } elseif ($draft->status !== ReceiveItemDraft::STATUS_SUBMITTED) {
                $newCount++;
            }

            $draft->save();
        }

        $import->update([
            'new_documents' => $newCount,
            'existing_documents' => $existingCount,
            'duplicate_check_status' => 'ok',
            'duplicate_check_message' => null,
            'checked_at' => now(),
        ]);

        return ['ok' => true, 'message' => null, 'existing' => $existingCount, 'new' => $newCount];
    }

    /**
     * Konversi baris memakai UNITRATIO XML: qty x ratio, harga / ratio,
     * satuan mengikuti satuan dasar item Accurate.
     */
    public function applyConversion(ReceiveItemDraftLine $line, ?array $item = null, ?string $itemError = null): void
    {
        $ratio = (float) $line->unit_ratio;
        $quantityXml = (float) $line->quantity_xml;
        $priceXml = (float) $line->price_xml;

        if ($this->nullIfEmpty($line->item_external) === null) {
            $line->quantity = round($quantityXml, 6);
            $line->unit_price = round($priceXml, 6);
            $line->unit_name = $line->unit_xml;
            $line->status = ReceiveItemDraftLine::STATUS_BLOCKED;
            $line->message = 'Item internal ' . ($line->item_internal ?: '-') . ' belum dipetakan ke item Accurate.';

            return;
        }

        if ($ratio <= 0) {
            $line->quantity = round($quantityXml, 6);
            $line->unit_price = round($priceXml, 6);
            $line->unit_name = $line->unit_xml;
            $line->status = ReceiveItemDraftLine::STATUS_BLOCKED;
            $line->message = 'UNITRATIO kosong/0. Isi rasio satuan atau perbaiki baris ini.';

            return;
        }

        if ($item === null) {
            $line->quantity = round($quantityXml, 6);
            $line->unit_price = round($priceXml, 6);
            $line->unit_name = $line->unit_xml;
            $line->status = ReceiveItemDraftLine::STATUS_BLOCKED;
            $line->message = $itemError
                ? 'Gagal memverifikasi item ke Accurate: ' . $itemError
                : 'Item ' . $line->item_external . ' tidak ditemukan di Accurate.';

            return;
        }

        $line->quantity = round($quantityXml * $ratio, 6);
        $line->unit_price = round($priceXml / $ratio, 6);
        $line->unit_name = $this->nullIfEmpty($item['unit1'] ?? null) ?? $line->unit_xml;
        $line->item_name = $this->nullIfEmpty($item['name'] ?? null) ?? $line->item_name;
        $line->status = ReceiveItemDraftLine::STATUS_OK;
        $line->message = null;
    }

    /**
     * Perbarui status baris tanpa mengubah qty/harga (dipakai setelah edit manual).
     */
    public function refreshLineStatus(
        ReceiveItemDraftLine $line,
        ?array $item = null,
        ?string $itemError = null,
        bool $requireItem = false
    ): void {
        if ($this->nullIfEmpty($line->item_external) === null) {
            $line->status = ReceiveItemDraftLine::STATUS_BLOCKED;
            $line->message = 'Item internal ' . ($line->item_internal ?: '-') . ' belum dipetakan ke item Accurate.';

            return;
        }

        if ((float) $line->unit_ratio <= 0) {
            $line->status = ReceiveItemDraftLine::STATUS_BLOCKED;
            $line->message = 'UNITRATIO kosong/0. Isi rasio satuan atau perbaiki baris ini.';

            return;
        }

        if ($this->nullIfEmpty($line->unit_name) === null) {
            $line->status = ReceiveItemDraftLine::STATUS_BLOCKED;
            $line->message = 'Satuan item belum diisi.';

            return;
        }

        if ($item === null) {
            if ($requireItem) {
                $line->status = ReceiveItemDraftLine::STATUS_BLOCKED;
                $line->message = $itemError
                    ? 'Gagal memverifikasi item ke Accurate: ' . $itemError
                    : 'Item ' . $line->item_external . ' tidak ditemukan di Accurate.';

                return;
            }
        } else {
            $line->item_name = $this->nullIfEmpty($item['name'] ?? null) ?? $line->item_name;
        }

        $line->status = ReceiveItemDraftLine::STATUS_OK;
        $line->message = null;
    }

    /**
     * Sesuaikan harga satuan seluruh baris agar total draft kembali ke $target.
     * Selisih dibagikan proporsional terhadap nilai tiap baris, lalu sisa
     * pembulatan diserap oleh baris terakhir yang memenuhi syarat.
     *
     * @return array{ok:bool,message:?string,total:float}
     */
    public function rebalanceTotal(ReceiveItemDraft $draft, float $target): array
    {
        $lines = $draft->lines()->get();

        if ($lines->isEmpty()) {
            return ['ok' => false, 'message' => 'Tidak ada baris item.', 'total' => 0.0];
        }

        if ($target <= 0) {
            return ['ok' => false, 'message' => 'Total target tidak valid.', 'total' => 0.0];
        }

        $total = fn ($items) => round((float) $items->sum(fn (ReceiveItemDraftLine $line) => $line->lineTotal()), 4);

        $current = $total($lines);

        if ($current <= 0) {
            return [
                'ok' => false,
                'message' => 'Total baris masih 0, total tagihan tidak bisa dijaga.',
                'total' => 0.0,
            ];
        }

        $factor = $target / $current;

        foreach ($lines as $line) {
            $line->unit_price = round((float) $line->unit_price * $factor, 6);
        }

        // Baris yang aman untuk menyerap pembulatan: qty > 0 dan diskon < 100%.
        $absorbable = $lines->filter(function (ReceiveItemDraftLine $line) {
            return (float) $line->quantity > 0 && (float) ($line->disc_percent ?? 0) < 100;
        });

        $diff = round($target - $total($lines), 4);

        if (abs($diff) >= 0.0001 && $absorbable->isNotEmpty()) {
            /** @var ReceiveItemDraftLine $last */
            $last = $absorbable->last();
            $denominator = (float) $last->quantity * (1 - ((float) ($last->disc_percent ?? 0) / 100));

            if ($denominator > 0) {
                $last->unit_price = round((float) $last->unit_price + ($diff / $denominator), 6);
            }
        }

        foreach ($lines as $line) {
            $line->save();
        }

        return ['ok' => true, 'message' => null, 'total' => $total($lines)];
    }

    /**
     * Petakan vendor draft dari tabel mapping_vendors.
     */
    public function applyVendorMapping(ReceiveItemDraft $draft): void
    {
        if ($draft->isReadOnly()) {
            return;
        }

        $vendor = MappingVendor::query()
            ->where('kode_vendor_internal', (string) $draft->vendor_internal)
            ->first();

        $draft->vendor_external = $this->nullIfEmpty($vendor?->kode_vendor_external);
        $draft->vendor_name = $this->nullIfEmpty($vendor?->nama_vendor_external);

        if ($draft->status === ReceiveItemDraft::STATUS_INVALID && $draft->vendor_external !== null) {
            $draft->status = ReceiveItemDraft::STATUS_NEW;
            $draft->message = null;
        } elseif ($draft->status === ReceiveItemDraft::STATUS_NEW && $draft->vendor_external === null) {
            $draft->status = ReceiveItemDraft::STATUS_INVALID;
            $draft->message = 'Vendor ' . ($draft->vendor_internal ?: '-') . ' belum dipetakan ke vendor Accurate.';
        }

        $draft->save();
    }

    /**
     * Susun payload receive-item/save.do untuk satu draft.
     *
     * @return array<string,mixed>
     */
    public function buildPayload(ReceiveItemDraft $draft): array
    {
        $draft->loadMissing('lines');

        $items = [];
        foreach ($draft->lines as $line) {
            if ($line->status === ReceiveItemDraftLine::STATUS_BLOCKED) {
                continue;
            }

            $items[] = [
                'itemNo' => $line->item_external,
                'quantity' => (float) $line->quantity,
                'itemUnitName' => $line->unit_name,
                'unitPrice' => (float) $line->unit_price,
                'detailName' => $line->description ?: $line->item_name,
                'warehouseName' => $line->warehouse_name ?: (string) Setting::get('accurate_warehouse_name', 'Utama'),
                'itemDiscPercent' => (float) ($line->disc_percent ?? 0),
                'useTax1' => (bool) $draft->taxable,
            ];
        }

        $payload = [
            'receiveNumber' => $draft->receive_number,
            'transDate' => $draft->trans_date?->format('d/m/Y') ?? now()->format('d/m/Y'),
            'vendorNo' => $draft->vendor_external,
            'currencyCode' => $draft->currency_code ?: 'IDR',
            'rate' => (float) ($draft->rate ?: 1),
            'taxable' => (bool) $draft->taxable,
            'inclusiveTax' => (bool) $draft->inclusive_tax,
            'detailItem' => $items,
        ];

        if ($draft->payment_term_name) {
            $payload['paymentTermName'] = $draft->payment_term_name;
        }

        if ($draft->ship_date) {
            $payload['shipDate'] = $draft->ship_date->format('d/m/Y');
        }

        if ($draft->description) {
            $payload['description'] = $draft->description;
        }

        return $payload;
    }

    /**
     * Kirim draft ke Accurate lewat bulk-save (fallback save.do bila bulk tidak tersedia).
     *
     * @param  array<int,int>  $ids
     * @return array{ok:bool,message:?string,results:array<int,array>,skipped:array<int,array>}
     */
    public function submitDrafts(array $ids): array
    {
        $drafts = ReceiveItemDraft::with('lines')->whereIn('id', $ids)->get()->keyBy('id');

        $skipped = [];
        $candidates = [];

        foreach ($ids as $id) {
            $draft = $drafts->get((int) $id);
            if (! $draft) {
                continue;
            }

            if ($draft->isReadOnly()) {
                $skipped[] = [
                    'id' => $draft->id,
                    'receive_number' => $draft->receive_number,
                    'message' => 'Dokumen sudah ada/terkirim, tidak dikirim ulang.',
                ];
                continue;
            }

            if (! $draft->canSubmit()) {
                $draft->status = ReceiveItemDraft::STATUS_INVALID;
                $draft->message = $this->blockReason($draft);
                $draft->save();

                $skipped[] = [
                    'id' => $draft->id,
                    'receive_number' => $draft->receive_number,
                    'message' => $draft->message,
                ];
                continue;
            }

            $candidates[] = $draft;
        }

        if ($candidates === []) {
            return ['ok' => true, 'message' => null, 'results' => [], 'skipped' => $skipped];
        }

        // Guard duplikat: cek ulang tepat sebelum kirim.
        $existingResult = $this->fetchExistingNumbers(true);
        if (! $existingResult['ok']) {
            return [
                'ok' => false,
                'message' => 'Gagal mengecek duplikat ke Accurate: ' . $existingResult['message'],
                'results' => [],
                'skipped' => $skipped,
            ];
        }

        $existing = $existingResult['numbers'];
        $payloads = [];
        $draftByIndex = [];

        foreach ($candidates as $draft) {
            $key = $this->normalize($draft->receive_number);

            if (isset($existing[$key])) {
                $accurate = $existing[$key];
                $draft->status = ReceiveItemDraft::STATUS_EXISTS;
                $draft->accurate_id = $accurate['id'] ?? $draft->accurate_id;
                $draft->accurate_number = $accurate['number'] ?? $draft->accurate_number;
                $draft->message = 'Dokumen sudah ada di Accurate (' . ($accurate['number'] ?? '-') . ').';
                $draft->save();

                $skipped[] = [
                    'id' => $draft->id,
                    'receive_number' => $draft->receive_number,
                    'message' => $draft->message,
                ];
                continue;
            }

            $payload = $this->buildPayload($draft);
            $draft->payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $draft->save();

            $draftByIndex[count($payloads)] = $draft;
            $payloads[] = $payload;
        }

        if ($payloads === []) {
            return ['ok' => true, 'message' => null, 'results' => [], 'skipped' => $skipped];
        }

        $groups = array_chunk($payloads, self::DOCS_PER_REQUEST);
        $responses = $this->client->bulkSaveMany('receive-item', $groups, self::CONCURRENCY);

        $results = [];
        $offset = 0;

        foreach ($groups as $groupIndex => $groupPayloads) {
            $response = $responses[$groupIndex] ?? null;

            if ($response === null) {
                $offset += count($groupPayloads);
                continue;
            }

            if ($response['unsupported'] ?? false) {
                foreach ($groupPayloads as $localIndex => $payload) {
                    $index = $offset + $localIndex;
                    $draft = $draftByIndex[$index] ?? null;
                    if (! $draft) {
                        continue;
                    }

                    $single = $this->client->post('/accurate/api/receive-item/save.do', $payload);
                    $ok = (bool) ($single['ok'] ?? false);
                    $doc = is_array($single['body']['d'] ?? null) ? $single['body']['d'] : [];

                    $results[] = $this->finishDraft(
                        $draft,
                        $ok,
                        $doc,
                        $ok ? null : ($single['message'] ?? 'Accurate menolak dokumen.'),
                        $single['body'] ?? []
                    );
                }

                $offset += count($groupPayloads);
                continue;
            }

            $groupResults = $response['results'] ?? [];

            foreach ($groupPayloads as $localIndex => $payload) {
                $index = $offset + $localIndex;
                $draft = $draftByIndex[$index] ?? null;
                if (! $draft) {
                    continue;
                }

                $row = $groupResults[$localIndex] ?? null;

                if ($row === null) {
                    $results[] = $this->finishDraft(
                        $draft,
                        false,
                        [],
                        $response['message'] ?? 'Respons bulk Accurate tidak lengkap.',
                        $response
                    );
                    continue;
                }

                $results[] = $this->finishDraft(
                    $draft,
                    (bool) ($row['ok'] ?? false),
                    is_array($row['doc'] ?? null) ? $row['doc'] : [],
                    $row['message'] ?? null,
                    $row['body'] ?? []
                );
            }

            $offset += count($groupPayloads);
        }

        Cache::forget('accurate.receive-items.existing');

        return ['ok' => true, 'message' => null, 'results' => $results, 'skipped' => $skipped];
    }

    /**
     * @param  array<string,mixed>  $doc
     * @param  array<mixed>  $response
     * @return array<string,mixed>
     */
    private function finishDraft(ReceiveItemDraft $draft, bool $ok, array $doc, ?string $message, array $response): array
    {
        if ($ok) {
            $draft->status = ReceiveItemDraft::STATUS_SUBMITTED;
            $draft->accurate_id = isset($doc['id']) ? (int) $doc['id'] : $draft->accurate_id;
            $draft->accurate_number = $doc['number'] ?? $draft->accurate_number;
            $draft->accurate_status = $doc['statusName'] ?? $doc['status'] ?? null;
            $draft->message = null;
            $draft->submitted_at = now();
        } else {
            $draft->status = ReceiveItemDraft::STATUS_FAILED;
            $draft->message = $message ?: 'Accurate menolak dokumen.';
        }

        $draft->response = json_encode($response, JSON_UNESCAPED_UNICODE);
        $draft->save();

        return [
            'id' => $draft->id,
            'receive_number' => $draft->receive_number,
            'ok' => $ok,
            'status' => $draft->status,
            'accurate_number' => $draft->accurate_number,
            'message' => $draft->message,
        ];
    }

    private function blockReason(ReceiveItemDraft $draft): string
    {
        if ($this->nullIfEmpty($draft->vendor_external) === null) {
            return 'Vendor belum dipetakan ke vendor Accurate.';
        }

        if ($draft->lines->isEmpty()) {
            return 'Tidak ada baris item yang bisa dikirim.';
        }

        $blocked = $draft->lines->firstWhere('status', ReceiveItemDraftLine::STATUS_BLOCKED);

        return $blocked?->message ?: 'Ada baris item yang belum siap dikirim.';
    }

    /**
     * @param  array<int,array<string,mixed>>  $documents
     * @return array<string,MappingProduct>
     */
    private function productMap(array $documents): array
    {
        $codes = [];
        foreach ($documents as $doc) {
            foreach ($doc['lines'] as $line) {
                $code = trim((string) ($line['item_internal'] ?? ''));
                if ($code !== '') {
                    $codes[$code] = true;
                }
            }
        }

        if ($codes === []) {
            return [];
        }

        $map = [];
        foreach (MappingProduct::query()->whereIn('kode_item_internal', array_keys($codes))->get() as $product) {
            $map[$this->normalize($product->kode_item_internal)] = $product;
        }

        return $map;
    }

    /**
     * @param  array<string,MappingProduct>  $productMap
     * @return array<int,string>
     */
    private function externalCodes(array $productMap): array
    {
        $codes = [];
        foreach ($productMap as $product) {
            $code = $this->nullIfEmpty($product->kode_item_external);
            if ($code !== null) {
                $codes[$this->normalize($code)] = true;
            }
        }

        return array_keys($codes);
    }

    /**
     * Ambil data item Accurate berdasarkan kode eksternal (multi-nilai per request).
     *
     * @param  array<int,string>  $codes
     * @return array{ok:bool,items:array<string,array<string,mixed>>,message:?string}
     */
    public function fetchItems(array $codes, bool $force = false): array
    {
        $codes = array_values(array_unique(array_filter(array_map(
            fn ($code) => $this->normalize($code),
            $codes
        ))));

        if ($codes === []) {
            return ['ok' => true, 'items' => [], 'message' => null];
        }

        sort($codes);
        $cacheKey = 'accurate.receive-items.item-map.' . md5(implode('|', $codes));

        if (! $force) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return ['ok' => true, 'items' => $cached, 'message' => null];
            }
        }

        $items = [];

        foreach (array_chunk($codes, self::ITEM_CHUNK) as $chunk) {
            $query = [
                'sp.pageSize' => 100,
                'fields' => 'id,no,name,unit1,unit2',
                'filter.no.op' => 'EQUAL',
            ];

            foreach (array_values($chunk) as $index => $code) {
                $query['filter.no.val[' . $index . ']'] = $code;
            }

            $result = $this->getWithRetry('/accurate/api/item/list.do', $query);

            if (! ($result['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'items' => [],
                    'message' => $result['message'] ?? 'Gagal mengambil data item dari Accurate.',
                ];
            }

            foreach (($result['body']['d'] ?? []) as $row) {
                $no = $this->normalize($row['no'] ?? '');
                if ($no === '') {
                    continue;
                }

                $items[$no] = [
                    'id' => $row['id'] ?? null,
                    'no' => $row['no'] ?? null,
                    'name' => $row['name'] ?? null,
                    'unit1' => $row['unit1']['name'] ?? null,
                    'unit2' => $row['unit2']['name'] ?? null,
                ];
            }

            usleep(180000);
        }

        Cache::put($cacheKey, $items, 3600);

        return ['ok' => true, 'items' => $items, 'message' => null];
    }

    /**
     * Ambil satu item (dipakai saat user mengganti item pada baris draft).
     *
     * @return array{ok:bool,item:?array<string,mixed>,message:?string}
     */
    public function fetchItem(string $code): array
    {
        $result = $this->fetchItems([$code], true);
        $item = $result['items'][$this->normalize($code)] ?? null;

        return [
            'ok' => $result['ok'] && $item !== null,
            'item' => $item,
            'message' => $result['ok'] ? null : $result['message'],
        ];
    }

    /**
     * Seluruh nomor receiveNumber yang sudah ada di Accurate (map nomor => info).
     *
     * @return array{ok:bool,numbers:array<string,array<string,mixed>>,message:?string}
     */
    public function fetchExistingNumbers(bool $force = false): array
    {
        if (! $force) {
            $cached = Cache::get('accurate.receive-items.existing');
            if (is_array($cached)) {
                return ['ok' => true, 'numbers' => $cached, 'message' => null];
            }
        }

        $numbers = [];
        $page = 1;
        $pageCount = 1;

        do {
            $result = $this->getWithRetry('/accurate/api/receive-item/list.do', [
                'sp.pageSize' => 100,
                'sp.page' => $page,
                'fields' => 'id,number,receiveNumber,transDate',
            ]);

            if (! ($result['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'numbers' => [],
                    'message' => $result['message'] ?? 'Gagal mengambil daftar receive item dari Accurate.',
                ];
            }

            foreach (($result['body']['d'] ?? []) as $row) {
                $number = $this->normalize($row['receiveNumber'] ?? '');
                if ($number === '') {
                    continue;
                }

                $numbers[$number] = [
                    'id' => $row['id'] ?? null,
                    'number' => $row['number'] ?? null,
                    'transDate' => $row['transDate'] ?? null,
                ];
            }

            $pageCount = (int) ($result['body']['sp']['pageCount'] ?? 1);
            $page++;
            usleep(280000);
        } while ($page <= $pageCount);

        Cache::put('accurate.receive-items.existing', $numbers, 600);

        return ['ok' => true, 'numbers' => $numbers, 'message' => null];
    }

    /**
     * GET dengan retry saat kena batas 8 request/detik.
     *
     * @param  array<string,mixed>  $query
     * @return array{ok:bool,status:int,body:array,message:?string}
     */
    private function getWithRetry(string $path, array $query, int $tries = 6): array
    {
        $result = [];

        for ($attempt = 1; $attempt <= $tries; $attempt++) {
            $result = $this->client->get($path, $query);

            if ($result['ok'] ?? false) {
                return $result;
            }

            $status = (int) ($result['status'] ?? 0);
            $message = (string) ($result['message'] ?? '');
            $rateLimited = $status === 429 || stripos($message, 'melebihi toleransi') !== false;

            if (! $rateLimited) {
                return $result;
            }

            Log::warning('Accurate rate limit, retry ' . $attempt . '/' . $tries . ' untuk ' . $path);
            usleep(1500000 * $attempt);
        }

        return $result;
    }

    private function normalize(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
