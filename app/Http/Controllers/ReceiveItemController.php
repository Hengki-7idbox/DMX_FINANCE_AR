<?php

namespace App\Http\Controllers;

use App\Models\MappingVendor;
use App\Models\ReceiveItemDraft;
use App\Models\ReceiveItemDraftLine;
use App\Models\ReceiveItemImport;
use App\Services\ReceiveItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class ReceiveItemController extends Controller
{
    public function __construct(private readonly ReceiveItemService $service)
    {
    }

    public function index(Request $request)
    {
        $imports = ReceiveItemImport::query()
            ->latest('id')
            ->limit(30)
            ->get();

        $currentImport = null;

        if ($request->filled('import')) {
            $currentImport = ReceiveItemImport::find($request->integer('import'));
        }

        if (! $currentImport) {
            $currentImport = $imports->first();
        }

        $status = (string) $request->get('status', 'all');
        $search = trim((string) $request->get('q', ''));

        $drafts = collect();
        $stats = [
            'total' => 0,
            'new' => 0,
            'exists' => 0,
            'submitted' => 0,
            'failed' => 0,
            'invalid' => 0,
            'blocked' => 0,
        ];

        if ($currentImport) {
            $baseQuery = ReceiveItemDraft::query()
                ->where('receive_item_import_id', $currentImport->id);

            $stats['total'] = (clone $baseQuery)->count();
            $stats['new'] = (clone $baseQuery)->where('status', ReceiveItemDraft::STATUS_NEW)->count();
            $stats['exists'] = (clone $baseQuery)->where('status', ReceiveItemDraft::STATUS_EXISTS)->count();
            $stats['submitted'] = (clone $baseQuery)->where('status', ReceiveItemDraft::STATUS_SUBMITTED)->count();
            $stats['failed'] = (clone $baseQuery)->where('status', ReceiveItemDraft::STATUS_FAILED)->count();
            $stats['invalid'] = (clone $baseQuery)->where('status', ReceiveItemDraft::STATUS_INVALID)->count();
            $stats['blocked'] = (clone $baseQuery)
                ->whereHas('lines', fn ($query) => $query->where('status', ReceiveItemDraftLine::STATUS_BLOCKED))
                ->count();

            $drafts = $baseQuery
                ->when($status !== 'all' && $status !== '', fn ($query) => $query->where('status', $status))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('receive_number', 'like', '%' . $search . '%')
                            ->orWhere('vendor_internal', 'like', '%' . $search . '%')
                            ->orWhere('vendor_external', 'like', '%' . $search . '%');
                    });
                })
                ->withCount([
                    'lines',
                    'lines as blocked_lines_count' => fn ($query) => $query->where('status', ReceiveItemDraftLine::STATUS_BLOCKED),
                ])
                ->orderByRaw('COALESCE(trans_date, created_at) asc')
                ->orderBy('id')
                ->paginate(50)
                ->withQueryString();
        }

        $vendors = MappingVendor::query()->orderBy('kode_vendor_internal')->get();

        return view('receive-items.index', compact(
            'imports',
            'currentImport',
            'drafts',
            'stats',
            'vendors',
            'status',
            'search'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['xml', 'txt'], true)) {
            return back()->with('error', 'File harus berformat XML (hasil export Exim).');
        }

        try {
            $import = $this->service->import(
                $file->getRealPath(),
                $file->getClientOriginalName(),
                auth()->id()
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal memproses XML: ' . $e->getMessage());
        }

        Cache::forget('accurate.receive-items.existing');

        $message = sprintf(
            'Import selesai: %d dokumen (%d baru, %d sudah ada di Accurate).',
            $import->total_documents,
            $import->new_documents,
            $import->existing_documents
        );

        if ($import->duplicate_check_status === 'failed') {
            $message .= ' Pengecekan duplikat gagal: ' . $import->duplicate_check_message;
        }

        return redirect()
            ->route('receive-items.index', ['import' => $import->id])
            ->with($import->duplicate_check_status === 'failed' ? 'error' : 'success', $message);
    }

    public function check(Request $request, ReceiveItemImport $import): JsonResponse
    {
        $result = $this->service->recheckDuplicates($import);

        return response()->json($result);
    }

    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:12'],
            'ids.*' => ['integer'],
        ]);

        try {
            $result = $this->service->submitDrafts($data['ids']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Gagal mengirim ke Accurate: ' . $e->getMessage(),
                'results' => [],
                'skipped' => [],
            ], 500);
        }

        return response()->json($result);
    }

    public function lines(ReceiveItemDraft $draft): JsonResponse
    {
        $draft->load('lines');

        return response()->json([
            'draft' => $this->draftPayload($draft),
            'lines' => $draft->lines->map(fn (ReceiveItemDraftLine $line) => $this->linePayload($line))->values(),
            'can_submit' => $draft->canSubmit(),
        ]);
    }

    public function updateDraft(Request $request, ReceiveItemDraft $draft): JsonResponse
    {
        if ($draft->isReadOnly()) {
            return response()->json(['ok' => false, 'message' => 'Dokumen sudah ada/terkirim dan tidak bisa diubah.'], 422);
        }

        $data = $request->validate([
            'vendor_internal' => ['nullable', 'string', 'max:100'],
            'vendor_external' => ['nullable', 'string', 'max:100'],
            'trans_date' => ['nullable', 'date'],
            'ship_date' => ['nullable', 'date'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'payment_term_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $vendorChanged = $request->has('vendor_internal')
            && $this->normalize($data['vendor_internal'] ?? '') !== $this->normalize($draft->vendor_internal);

        if ($vendorChanged) {
            $draft->vendor_internal = $data['vendor_internal'] ?: null;
            $this->service->applyVendorMapping($draft);
        }

        if ($request->has('vendor_external')) {
            $draft->vendor_external = $data['vendor_external'] ?: null;
            if ($draft->status === ReceiveItemDraft::STATUS_INVALID && $draft->vendor_external) {
                $draft->status = ReceiveItemDraft::STATUS_NEW;
                $draft->message = null;
            }
        }

        if ($request->has('trans_date')) {
            $draft->trans_date = $data['trans_date'] ?: null;
        }

        if ($request->has('ship_date')) {
            $draft->ship_date = $data['ship_date'] ?: null;
        }

        if ($request->has('rate')) {
            $draft->rate = (float) ($data['rate'] ?? 1);
        }

        if ($request->has('currency_code')) {
            $draft->currency_code = $data['currency_code'] ?: $this->currencyFromRate($draft->rate);
        }

        if ($request->has('payment_term_name')) {
            $draft->payment_term_name = $data['payment_term_name'] ?: null;
        }

        if ($request->has('description')) {
            $draft->description = $data['description'] ?: null;
        }

        if (! $request->has('currency_code')) {
            $draft->currency_code = $this->currencyFromRate($draft->rate);
        }

        if (! $draft->vendor_external && $draft->status !== ReceiveItemDraft::STATUS_FAILED) {
            $draft->status = ReceiveItemDraft::STATUS_INVALID;
            $draft->message = 'Vendor belum dipetakan ke vendor Accurate.';
        } elseif ($draft->status === ReceiveItemDraft::STATUS_INVALID && $draft->vendor_external) {
            $draft->status = ReceiveItemDraft::STATUS_NEW;
            $draft->message = null;
        }

        $draft->save();
        $draft->load('lines');

        return response()->json([
            'ok' => true,
            'draft' => $this->draftPayload($draft),
            'can_submit' => $draft->canSubmit(),
        ]);
    }

    public function updateLine(Request $request, ReceiveItemDraftLine $line): JsonResponse
    {
        $line->load('draft');

        if ($line->draft->isReadOnly()) {
            return response()->json(['ok' => false, 'message' => 'Dokumen sudah ada/terkirim dan tidak bisa diubah.'], 422);
        }

        $data = $request->validate([
            'item_external' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric'],
            'unit_name' => ['nullable', 'string', 'max:30'],
            'unit_ratio' => ['nullable', 'numeric', 'min:0'],
            'disc_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        // Total sebelum qty diubah, dipakai untuk menjaga total tagihan tetap sama.
        $targetBeforeEdit = $request->has('quantity') && ! $request->has('unit_price')
            ? $this->sumLineTotals($line->draft)
            : null;

        if ($request->has('quantity')
            && (float) ($data['quantity'] ?? 0) <= 0
            && $line->draft->lines()->count() <= 1) {
            return response()->json([
                'ok' => false,
                'message' => 'Minimal 1 baris item dengan qty lebih dari 0 agar total tagihan tetap terjaga.',
            ], 422);
        }

        $item = null;
        $itemError = null;
        $requireItem = false;

        $itemChanged = $request->has('item_external')
            && $this->normalize($data['item_external'] ?? '') !== $this->normalize($line->item_external);

        if ($itemChanged) {
            $line->item_external = $this->nullIfEmpty($data['item_external'] ?? null);
            $line->item_external = $line->item_external ? $this->normalize($line->item_external) : null;

            if ($line->item_external) {
                $result = $this->service->fetchItem($line->item_external);
                $item = $result['item'];
                $itemError = $result['message'];
                $requireItem = true;
            }
        }

        $ratioChanged = $request->has('unit_ratio');
        if ($ratioChanged) {
            $line->unit_ratio = (float) ($data['unit_ratio'] ?? 0);
        }

        if ($request->has('quantity')) {
            $line->quantity = (float) ($data['quantity'] ?? 0);
            $line->quantity_xml = $line->unit_ratio > 0
                ? round($line->quantity / $line->unit_ratio, 6)
                : $line->quantity;
        }

        if ($request->has('unit_price')) {
            $line->unit_price = (float) ($data['unit_price'] ?? 0);
            $line->price_xml = round($line->unit_price * max((float) $line->unit_ratio, 0), 6);
        }

        if ($request->has('unit_name')) {
            $line->unit_name = $this->nullIfEmpty($data['unit_name'] ?? null);
        }

        if ($request->has('disc_percent')) {
            $line->disc_percent = $data['disc_percent'] !== null && $data['disc_percent'] !== ''
                ? (float) $data['disc_percent']
                : null;
        }

        if ($request->has('description')) {
            $line->description = $this->nullIfEmpty($data['description'] ?? null);
        }

        if ($ratioChanged && ! $request->has('quantity') && ! $request->has('unit_price')) {
            $this->service->applyConversion($line, $item, $itemError);
        } else {
            if ($itemChanged && $item && ! $request->has('unit_name')) {
                $line->unit_name = $item['unit1'] ?? $line->unit_name;
            }

            $this->service->refreshLineStatus($line, $item, $itemError, $requireItem);
        }

        $line->save();

        $rebalanced = false;
        if ($targetBeforeEdit !== null) {
            $rebalanced = $this->service->rebalanceTotal($line->draft, $targetBeforeEdit)['ok'];
        }

        $draft = $line->draft->fresh();
        $draft->load('lines');

        return response()->json([
            'ok' => true,
            'line' => $this->linePayload($line->fresh()),
            'lines' => $draft->lines->map(fn (ReceiveItemDraftLine $item) => $this->linePayload($item))->values(),
            'draft_can_submit' => $draft->canSubmit(),
            'draft_total' => $this->sumLineTotals($draft),
            'rebalanced' => $rebalanced,
        ]);
    }

    public function destroyLine(ReceiveItemDraftLine $line): JsonResponse
    {
        $line->load('draft');
        $draft = $line->draft;

        if ($draft->isReadOnly()) {
            return response()->json(['ok' => false, 'message' => 'Dokumen sudah ada/terkirim dan tidak bisa diubah.'], 422);
        }

        if ($draft->lines()->count() <= 1) {
            return response()->json([
                'ok' => false,
                'message' => 'Minimal 1 baris item harus tersisa agar total tagihan tetap terjaga.',
            ], 422);
        }

        $target = $this->sumLineTotals($draft);
        $line->delete();

        $rebalance = $this->service->rebalanceTotal($draft, $target);

        $draft = $draft->fresh();
        $draft->load('lines');

        return response()->json([
            'ok' => true,
            'lines' => $draft->lines->map(fn (ReceiveItemDraftLine $item) => $this->linePayload($item))->values(),
            'draft_can_submit' => $draft->canSubmit(),
            'draft_total' => $this->sumLineTotals($draft),
            'message' => $rebalance['ok'] ? null : $rebalance['message'],
        ]);
    }

    private function sumLineTotals(ReceiveItemDraft $draft): float
    {
        return round((float) $draft->lines()->get()->sum(fn (ReceiveItemDraftLine $line) => $line->lineTotal()), 4);
    }

    public function destroyImport(ReceiveItemImport $import)
    {
        $import->delete();

        return redirect()
            ->route('receive-items.index')
            ->with('success', 'Data import berhasil dihapus.');
    }

    public function vendorStore(Request $request): JsonResponse
    {
        $data = $this->validateVendor($request);

        $vendor = MappingVendor::updateOrCreate(
            ['kode_vendor_internal' => $data['kode_vendor_internal']],
            [
                'kode_vendor_external' => $data['kode_vendor_external'] ?: null,
                'nama_vendor_internal' => $data['nama_vendor_internal'] ?: null,
                'nama_vendor_external' => $data['nama_vendor_external'] ?: null,
            ]
        );

        $this->applyVendorToDrafts($vendor);

        return response()->json([
            'ok' => true,
            'vendor' => $this->vendorPayload($vendor),
        ]);
    }

    public function vendorUpdate(Request $request, MappingVendor $vendor): JsonResponse
    {
        $data = $this->validateVendor($request, $vendor->id);

        $vendor->update([
            'kode_vendor_internal' => $data['kode_vendor_internal'],
            'kode_vendor_external' => $data['kode_vendor_external'] ?: null,
            'nama_vendor_internal' => $data['nama_vendor_internal'] ?: null,
            'nama_vendor_external' => $data['nama_vendor_external'] ?: null,
        ]);

        $this->applyVendorToDrafts($vendor);

        return response()->json([
            'ok' => true,
            'vendor' => $this->vendorPayload($vendor->fresh()),
        ]);
    }

    public function vendorDestroy(MappingVendor $vendor): JsonResponse
    {
        $vendor->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string,mixed>
     */
    private function validateVendor(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'kode_vendor_internal' => [
                'required',
                'string',
                'max:100',
                'unique:mapping_vendors,kode_vendor_internal' . ($ignoreId ? ',' . $ignoreId : ''),
            ],
            'kode_vendor_external' => ['nullable', 'string', 'max:100'],
            'nama_vendor_internal' => ['nullable', 'string', 'max:255'],
            'nama_vendor_external' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function applyVendorToDrafts(MappingVendor $vendor): void
    {
        ReceiveItemDraft::query()
            ->where('vendor_internal', $vendor->kode_vendor_internal)
            ->whereNotIn('status', [ReceiveItemDraft::STATUS_EXISTS, ReceiveItemDraft::STATUS_SUBMITTED])
            ->get()
            ->each(fn (ReceiveItemDraft $draft) => $this->service->applyVendorMapping($draft));
    }

    /**
     * @return array<string,mixed>
     */
    private function draftPayload(ReceiveItemDraft $draft): array
    {
        return [
            'id' => $draft->id,
            'receive_number' => $draft->receive_number,
            'transaction_id' => $draft->transaction_id,
            'vendor_internal' => $draft->vendor_internal,
            'vendor_external' => $draft->vendor_external,
            'vendor_name' => $draft->vendor_name,
            'trans_date' => $draft->trans_date?->format('Y-m-d'),
            'ship_date' => $draft->ship_date?->format('Y-m-d'),
            'rate' => $draft->rate,
            'currency_code' => $draft->currency_code,
            'taxable' => $draft->taxable,
            'inclusive_tax' => $draft->inclusive_tax,
            'payment_term_name' => $draft->payment_term_name,
            'description' => $draft->description,
            'warehouse_name' => $draft->warehouse_name,
            'total_amount' => $draft->total_amount,
            'status' => $draft->status,
            'status_label' => $this->statusLabel($draft->status),
            'accurate_id' => $draft->accurate_id,
            'accurate_number' => $draft->accurate_number,
            'accurate_status' => $draft->accurate_status,
            'message' => $draft->message,
            'is_read_only' => $draft->isReadOnly(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function linePayload(ReceiveItemDraftLine $line): array
    {
        return [
            'id' => $line->id,
            'line_no' => $line->line_no,
            'item_internal' => $line->item_internal,
            'item_external' => $line->item_external,
            'item_name' => $line->item_name,
            'description' => $line->description,
            'quantity_xml' => $line->quantity_xml,
            'unit_xml' => $line->unit_xml,
            'unit_ratio' => $line->unit_ratio,
            'quantity' => $line->quantity,
            'unit_name' => $line->unit_name,
            'price_xml' => $line->price_xml,
            'unit_price' => $line->unit_price,
            'disc_percent' => $line->disc_percent,
            'warehouse_name' => $line->warehouse_name,
            'line_total' => $line->lineTotal(),
            'status' => $line->status,
            'message' => $line->message,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function vendorPayload(MappingVendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'kode_vendor_internal' => $vendor->kode_vendor_internal,
            'kode_vendor_external' => $vendor->kode_vendor_external,
            'nama_vendor_internal' => $vendor->nama_vendor_internal,
            'nama_vendor_external' => $vendor->nama_vendor_external,
        ];
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            ReceiveItemDraft::STATUS_NEW => 'Baru',
            ReceiveItemDraft::STATUS_EXISTS => 'Sudah Ada',
            ReceiveItemDraft::STATUS_SUBMITTED => 'Terkirim',
            ReceiveItemDraft::STATUS_FAILED => 'Gagal',
            ReceiveItemDraft::STATUS_INVALID => 'Perlu Perbaikan',
            ReceiveItemDraft::STATUS_SKIPPED => 'Dilewati',
            default => (string) $status,
        };
    }

    private function currencyFromRate(float $rate): string
    {
        return abs($rate - 1.0) > 0.000001 ? 'USD' : 'IDR';
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
