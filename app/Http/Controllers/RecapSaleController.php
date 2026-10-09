<?php

namespace App\Http\Controllers;

use App\Imports\RecapSalesImport;
use App\Models\ImportSession;
use App\Models\MappingCustomer;
use App\Models\RecapSale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class RecapSaleController extends Controller
{
    public function index(Request $request)
    {
        $tab = $this->resolveTab($request);

        $recapSales = RecapSale::where('source_type', $tab)
            ->orderByDesc('period')
            ->orderByDesc('id')
            ->get();

        $this->annotateBalance($recapSales, $tab);

        return view('recap-sales.index', compact('recapSales', 'tab'));
    }

    /**
     * Bidirectional balance check via mapping_customers.
     *
     * External tab: external.code → mapping(kode_external)→(kode_internal) → internal recap total.
     * Internal tab: internal.code → mapping(kode_internal)→(kode_external) → external recap total.
     *
     * Badge:
     *   'balance'        — totals match
     *   'not_balance'    — totals differ
     *   'partner_tidak_ada' — code mapped, but partner row not found in partner tab (no data to compare)
     *   'mapping_tidak_ada' — customer code not in mapping AND same-code not found in partner tab.
     *
     * Fallback: if mapping partner not found in partner tab, compare same customer_code in partner tab.
     */
    private function annotateBalance($recapSales, string $tab): void
    {
        $isExternal = ($tab === RecapSale::SOURCE_EXTERNAL);

        // Build both mapping directions.
        $mapExtToInt = [];
        $mapIntToExt = [];
        foreach (MappingCustomer::orderBy('id')->get() as $map) {
            if (!array_key_exists($map->kode_external, $mapExtToInt)) {
                $mapExtToInt[$map->kode_external] = $map->kode_internal;
            }
            if (!array_key_exists($map->kode_internal, $mapIntToExt)) {
                $mapIntToExt[$map->kode_internal] = $map->kode_external;
            }
        }

        // Codes to look up in partner tab.
        $partnerCodes = [];
        foreach ($recapSales as $row) {
            $partnerCodes[] = $row->customer_code;
        }
        $partnerCodes = array_values(array_unique(array_filter($partnerCodes)));
        $mappedCodes = array_values(array_unique(array_merge(array_values($mapExtToInt), array_values($mapIntToExt))));
        $candidateCodes = array_unique(array_merge($partnerCodes, $mappedCodes));
        $candidateCodes = array_values(array_filter($candidateCodes));

        $partnerByKey = [];
        if ($candidateCodes !== []) {
            $partnerSource = $isExternal ? RecapSale::SOURCE_INTERNAL : RecapSale::SOURCE_EXTERNAL;
            $partnerByKey = RecapSale::where('source_type', $partnerSource)
                ->whereIn('customer_code', $candidateCodes)
                ->get()
                ->groupBy(fn ($r) => $r->customer_code)
                ->all();
        }

        foreach ($recapSales as $row) {
            $totalThis = (float) $row->total_sales;
            $inMapping = false;
            $partnerCode = null;

            if ($isExternal) {
                $partnerCode = $mapExtToInt[$row->customer_code] ?? null;
                if ($partnerCode !== null) $inMapping = true;
            } else {
                $partnerCode = $mapIntToExt[$row->customer_code] ?? null;
                if ($partnerCode !== null) $inMapping = true;
            }

            $nilaiPartner = null;
            $compared = false;

            if ($inMapping && $partnerCode !== null) {
                $matched = $partnerByKey[$partnerCode] ?? null;
                if ($matched !== null) {
                    $nilaiPartner = (float) $matched->sum('total_sales');
                    $compared = true;
                }
            }

            // Fallback same code in partner tab if mapping partner not found or period mismatch.
            if (!$compared && ($row->customer_code ?? '') !== '') {
                $matched = $partnerByKey[$row->customer_code] ?? null;
                if ($matched !== null) {
                    $nilaiPartner = (float) $matched->sum('total_sales');
                    $compared = true;
                }
            }

            $row->nilai_internal = $nilaiPartner;
            $row->selisih = $compared ? $totalThis - $nilaiPartner : null;

            if (! $inMapping && ! $compared) {
                $row->balance_status = 'mapping_tidak_ada';
            } elseif (! $compared) {
                // mapping exists but partner data not found → nothing to compare yet
                $row->balance_status = 'partner_tidak_ada';
                $row->nilai_internal = null;
            } elseif (abs($totalThis - $nilaiPartner) < 0.01) {
                $row->balance_status = 'balance';
            } else {
                $row->balance_status = 'not_balance';
            }
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'source_type' => ['required', Rule::in(['internal', 'external'])],
        ]);

        $tab = $request->input('source_type');

        $session = ImportSession::create([
            'source_type' => $tab,
            'import_type' => ImportSession::TYPE_RECAP,
            'file_name' => $request->file('file')->getClientOriginalName(),
            'user_id' => $request->user()?->id,
        ]);

        try {
            $import = new RecapSalesImport($tab, $session->id);
            Excel::import($import, $request->file('file'));

            $created = $import->getCreatedCount();
            $updated = $import->getChangedCount();
            $unchanged = $import->getUnchangedCount();
            $skipped = $import->getSkippedCount();

            $session->update([
                'new_count' => $created,
                'changed_count' => $updated,
                'unchanged_count' => $unchanged,
                'skipped_count' => $skipped,
            ]);

            if ($created + $updated + $unchanged === 0) {
                $session->delete();

                return redirect()
                    ->route('recap-sales.index', ['tab' => $tab])
                    ->with('error', 'Tidak ada data terimport. Pastikan file export Accurate "Sales By Customer".');
            }

            $message = "Import selesai: {$created} ditambahkan, {$updated} diupdate";
            if ($unchanged > 0) {
                $message .= ", {$unchanged} tidak berubah";
            }
            if ($skipped > 0) {
                $message .= ", {$skipped} baris dilewati";
            }
            $message .= '.';

            return redirect()
                ->route('recap-sales.index', ['tab' => $tab])
                ->with('success', $message);
        } catch (ValidationException $e) {
            $session->delete();
            $errors = $e->errors();
            $firstErrors = [];
            foreach ($errors as $field => $messages) {
                $firstErrors[] = "{$field}: " . $messages[0];
            }

            return redirect()
                ->route('recap-sales.index', ['tab' => $tab])
                ->with('error', 'Validasi gagal - ' . implode('; ', array_slice($firstErrors, 0, 5)));
        } catch (\Exception $e) {
            $session->delete();

            return redirect()
                ->route('recap-sales.index', ['tab' => $tab])
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    public function clear(Request $request)
    {
        $tab = $this->resolveTab($request);

        $deleted = RecapSale::where('source_type', $tab)->delete();

        if ($deleted === 0) {
            return redirect()
                ->route('recap-sales.index', ['tab' => $tab])
                ->with('error', 'Tidak ada data untuk dihapus di tab ' . $tab . '.');
        }

        return redirect()
            ->route('recap-sales.index', ['tab' => $tab])
            ->with('success', "Berhasil menghapus {$deleted} data Recap Sales tab {$tab}.");
    }

    private function resolveTab(Request $request): string
    {
        $tab = $request->query('tab', $request->input('tab', RecapSale::SOURCE_EXTERNAL));

        return in_array($tab, [RecapSale::SOURCE_INTERNAL, RecapSale::SOURCE_EXTERNAL], true)
            ? $tab
            : RecapSale::SOURCE_EXTERNAL;
    }
}
