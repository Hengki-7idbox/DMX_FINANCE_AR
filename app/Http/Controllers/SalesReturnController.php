<?php

namespace App\Http\Controllers;

use App\Models\SalesReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SalesReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesReturn::query();

        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', '%' . $request->input('customer_name') . '%');
        }
        if ($request->filled('bulan')) {
            $query->whereRaw('MONTH(return_date) = ?', [(int) $request->input('bulan')]);
        } else {
            $query->whereRaw('MONTH(return_date) = ?', [(int) now()->month]);
        }
        if ($request->filled('tahun')) {
            $query->whereRaw('YEAR(return_date) = ?', [(int) $request->input('tahun')]);
        } else {
            $query->whereRaw('YEAR(return_date) = ?', [(int) now()->year]);
        }

        $returns = $query
            ->orderByDesc('return_date')
            ->orderBy('return_no')
            ->orderBy('id')
            ->get();

        return view('retur-sales.index', compact('returns'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        try {
            $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet();
        } catch (\Throwable $e) {
            return back()->with('error', 'File tidak bisa dibaca: ' . $e->getMessage());
        }

        if (trim((string) $sheet->getCell('B6')->getValue()) !== 'Customer No.'
            || trim((string) $sheet->getCell('H6')->getValue()) !== 'Invoice No.') {
            return back()->with('error', 'Format file tidak dikenali. Gunakan export Accurate "Sales Return by Item".');
        }

        $rows = [];
        $skipped = 0;

        for ($r = 7; $r <= $sheet->getHighestRow(); $r++) {
            $returnNo = trim((string) $sheet->getCell('H' . $r)->getValue());

            if ($returnNo === '' || $returnNo === 'Invoice No.') {
                $skipped++;
                continue;
            }

            $rows[] = [
                'customer_code' => $this->nullableString($sheet->getCell('B' . $r)->getValue()),
                'customer_name' => $this->nullableString($sheet->getCell('D' . $r)->getValue()),
                'invoice_no' => $this->nullableString($sheet->getCell('F' . $r)->getValue()),
                'return_no' => $returnNo,
                'return_date' => $this->parseDate($sheet->getCell('J' . $r)->getValue()),
                'item_no' => $this->nullableString($sheet->getCell('L' . $r)->getValue()),
                'item_description' => $this->nullableString($sheet->getCell('N' . $r)->getValue()),
                'unit_price' => $this->parseNumber($sheet->getCell('P' . $r)->getValue()),
                'quantity' => $this->parseNumber($sheet->getCell('R' . $r)->getValue()),
                'unit' => $this->nullableString($sheet->getCell('T' . $r)->getValue()),
                'amount' => $this->parseNumber($sheet->getCell('V' . $r)->getValue()),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows === []) {
            return back()->with('error', 'Tidak ada baris retur yang terbaca dari file.');
        }

        $returnNos = array_values(array_unique(array_column($rows, 'return_no')));

        DB::transaction(function () use ($rows, $returnNos) {
            SalesReturn::whereIn('return_no', $returnNos)->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                SalesReturn::insert($chunk);
            }
        });

        return back()->with(
            'success',
            count($rows) . ' baris retur (' . count($returnNos) . ' nomor retur) berhasil diimport.'
        );
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseNumber(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $value = str_replace(["\u{00A0}", ' '], '', $value);
        $negative = str_starts_with($value, '-')
            || (str_starts_with($value, '(') && str_ends_with($value, ')'));

        $value = trim($value, '()');
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        $number = (float) $value;

        return $negative ? -abs($number) : $number;
    }
}
