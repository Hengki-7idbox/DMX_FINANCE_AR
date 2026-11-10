<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Services\BcaStatementParser;
use App\Services\BankStatementImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BankTransactionController extends Controller
{
    public function __construct(private BankStatementImporter $importer)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'bank_account_id',
            'period_year',
            'period_month',
            'position',
            'status',
            'q',
        ]);

        $base = BankTransaction::query()->filter($filters);

        $transactions = (clone $base)
            ->with('bankAccount')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'total' => (clone $base)->count(),
            'matched' => (clone $base)->where('status', 'MATCHED')->count(),
            'not_found' => (clone $base)->whereIn('status', ['NOT_FOUND', 'AMBIGUOUS'])->count(),
            'credit' => (float) (clone $base)->where('position', 'CR')->sum('amount'),
            'debit' => (float) (clone $base)->where('position', 'DB')->sum('amount'),
        ];

        $bankAccounts = BankAccount::orderBy('bank_name')->get();
        $years = BankTransaction::query()
            ->whereNotNull('period_year')
            ->distinct()
            ->orderByDesc('period_year')
            ->pluck('period_year');

        return view('bank-transactions.index', compact('transactions', 'stats', 'bankAccounts', 'years', 'filters'));
    }

    public function importForm()
    {
        return view('bank-transactions.import', [
            'prepared' => $this->loadPrepared(),
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:20480',
        ]);

        // Pencocokan Baserow bisa memakan waktu untuk satu bulan penuh.
        ignore_user_abort(true);
        set_time_limit(0);

        $file = $request->file('file');
        $parser = new BcaStatementParser();

        try {
            $parsed = $parser->parse($file->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        if (! empty($parsed['errors'])) {
            return back()->with('error', 'Validasi file gagal: ' . implode(' ', $parsed['errors']));
        }

        try {
            $prepared = $this->importer->prepare(
                $parsed,
                $file->getClientOriginalName(),
                hash_file('sha256', $file->getRealPath())
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyiapkan import: ' . $e->getMessage());
        }

        // Simpan hasil preview di file sementara, bukan di session: satu bulan bisa
        // berisi ribuan baris dan payload session akan membengkak di setiap request.
        $this->forgetPrepared();
        $this->prunePrepared();

        $token = (string) Str::uuid();
        Storage::disk('local')->put(
            $this->preparedPath($token),
            json_encode($prepared, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        session(['bank_import_prepared' => $token]);

        return view('bank-transactions.import', compact('prepared'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'strategy' => 'required|in:replace,incremental',
        ]);

        $prepared = $this->loadPrepared();

        if (! is_array($prepared) || empty($prepared['rows'])) {
            return redirect()->route('bank-transactions.import')
                ->with('error', 'Sesi preview sudah tidak ada. Silakan upload ulang.');
        }

        try {
            $result = $this->importer->commit($prepared, $validated['strategy'], $request->user()?->id);
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        $this->forgetPrepared();

        $message = "Import selesai: {$result['inserted']} ditambahkan";
        if ($result['updated'] > 0) {
            $message .= ", {$result['updated']} diperbarui";
        }
        if ($result['deleted'] > 0) {
            $message .= ", {$result['deleted']} dihapus (replace periode)";
        }
        $message .= '.';

        return redirect()->route('bank-transactions.index')->with('success', $message);
    }

    private function loadPrepared(): ?array
    {
        $token = session('bank_import_prepared');

        if (! is_string($token) || $token === '') {
            return null;
        }

        $path = $this->preparedPath($token);

        if (! Storage::disk('local')->exists($path)) {
            session()->forget('bank_import_prepared');

            return null;
        }

        $decoded = json_decode((string) Storage::disk('local')->get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function forgetPrepared(): void
    {
        $token = session('bank_import_prepared');

        if (is_string($token) && $token !== '') {
            Storage::disk('local')->delete($this->preparedPath($token));
        }

        session()->forget('bank_import_prepared');
    }

    private function prunePrepared(): void
    {
        foreach (Storage::disk('local')->files('bank-imports') as $file) {
            if (Storage::disk('local')->lastModified($file) < now()->subDay()->getTimestamp()) {
                Storage::disk('local')->delete($file);
            }
        }
    }

    private function preparedPath(string $token): string
    {
        return 'bank-imports/' . $token . '.json';
    }

    public function show(BankTransaction $bankTransaction)
    {
        $bankTransaction->load(['bankAccount', 'importBatch']);

        return view('bank-transactions.show', compact('bankTransaction'));
    }

    public function updateFields(Request $request, BankTransaction $bankTransaction)
    {
        $validated = $request->validate([
            'document_number' => 'nullable|string|max:60',
            'document_internal' => 'nullable|string|max:60',
            'document_external' => 'nullable|string|max:60',
            'note' => 'nullable|string|max:5000',
        ]);

        $bankTransaction->update($validated);

        return back()->with('success', 'Catatan / nomor dokumen berhasil diperbarui.');
    }
}
