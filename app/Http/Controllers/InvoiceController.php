<?php

namespace App\Http\Controllers;

use App\Exports\ChinaGlobalExport;
use App\Exports\ChinaReportExport;
use App\Exports\StandardReportExport;
use App\Imports\OutstandingInvoicesImport;
use App\Models\Customer;
use App\Models\DetailSale;
use App\Models\OutstandingInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class InvoiceController extends Controller
{
    /** Jumlah kolom bulan pada China Report (sisa data masuk kolom catch-all). */
    private const CHINA_MONTH_COLUMNS = 9;

    private const CHINA_MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];


    /**
     * /invoices = halaman detail-sales difilter `owing != 0` (Invoice Belum Lunas).
     */
    public function index(Request $request)
    {
        return (new DetailSaleController())->index($request);
    }

    public function create()
    {
        return view('invoices.create');
    }

    public function show($id)
    {
        return view('invoices.show');
    }

    /** Outstanding Invoices (baris normal, tanpa exception). */
    public function outstanding(Request $request)
    {
        return $this->listPage($request, 'outstanding');
    }

    /** Anomali Piutang (baris ber-exception: anomali / biaya_bank). */
    public function anomali(Request $request)
    {
        return $this->listPage($request, 'anomali');
    }

    /** Tab Standard Report — Aging per customer. */
    public function standardReport(Request $request)
    {
        $year = $this->reportYear($request);
        $data = $this->standardReportData($year);

        return view('invoices.standard-report', [
            'year' => $year,
            'rows' => $data['rows'],
            'grand' => $data['grand'],
            'years' => $this->reportYears(),
        ]);
    }

    /** Export Excel Standard Report (mengikuti tahun + filter aktif). */
    public function standardReportExport(Request $request)
    {
        $year = $this->reportYear($request);
        $data = $this->standardReportData($year, $this->reportFilters($request));

        return Excel::download(
            new StandardReportExport($data['rows'], $data['grand'], $year ?? 0),
            'aging-standard-' . ($year ?? 'all') . '.xlsx'
        );
    }

    /** Print preview Standard Report (tanpa layout/sidebar), mengikuti filter aktif. */
    public function standardReportPrint(Request $request)
    {
        $year = $this->reportYear($request);
        $data = $this->standardReportData($year, $this->reportFilters($request));

        return view('invoices.standard-report-print', [
            'year' => $year,
            'rows' => $data['rows'],
            'grand' => $data['grand'],
        ]);
    }

    private function reportYear(Request $request): ?int
    {
        if ($request->has('tahun')) {
            $v = trim((string) $request->query('tahun'));

            return $v === '' ? null : (int) $v;
        }

        return (int) now()->year;
    }

    /**
     * Filter aktif dari query (dipakai Export & Print).
     *
     * @return array{q:string,code:array<int,string>,name:array<int,string>,susp:array<int,string>}
     */
    private function reportFilters(Request $request): array
    {
        $clean = fn ($arr) => array_values(array_filter((array) $arr, fn ($v) => $v !== '' && $v !== null));

        return [
            'q' => trim((string) $request->query('q', '')),
            'code' => $clean($request->query('code', [])),
            'name' => $clean($request->query('name', [])),
            'susp' => $clean($request->query('susp', [])),
        ];
    }

    /**
     * @return array<int,int>
     */
    private function reportYears(): array
    {
        return OutstandingInvoice::query()
            ->selectRaw('DISTINCT YEAR(invoice_date) AS y')
            ->pluck('y')
            ->filter(fn ($v) => $v !== null && (int) $v > 0)
            ->map(fn ($v) => (int) $v)
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * Agregasi Aging per customer untuk tahun tertentu (sumber: outstanding_invoices).
     *
     * @return array{rows: array<int,array<string,mixed>>, grand: array<string,float>}
     */
    private function standardReportData(?int $year, array $filters = []): array
    {
        $query = OutstandingInvoice::whereNull('exception_type')
            ->where('missing_count', '<', 1);
        if ($year !== null) {
            $query->whereYear('invoice_date', $year);
        }

        $rows = $query->get(['customer_no', 'customer_name', 'customer_suspended', 'terms_net_days', 'age_from_due', 'prime_owing']);

        $creditArr = Customer::where('source_type', Customer::SOURCE_INTERNAL)
            ->pluck('credit_limit', 'customer_code')
            ->all();

        $grouped = [];
        foreach ($rows as $r) {
            $code = (string) $r->customer_no;
            if (! isset($grouped[$code])) {
                $grouped[$code] = [
                    'code' => $code,
                    'name' => (string) $r->customer_name,
                    'suspended' => (string) $r->customer_suspended,
                    'credit' => array_key_exists($code, $creditArr) ? (float) $creditArr[$code] : null,
                    'not_yet_due' => 0.0, 'cod' => 0.0, 'd1_30' => 0.0, 'd31_60' => 0.0,
                    'd61_90' => 0.0, 'd91_120' => 0.0, 'd120' => 0.0, 'total' => 0.0,
                ];
            }
            $bucket = $this->agingBucket($r->terms_net_days, $r->age_from_due);
            $amt = (float) $r->prime_owing;
            $grouped[$code][$bucket] += $amt;
            $grouped[$code]['total'] += $amt;
        }

        ksort($grouped, SORT_STRING);
        $list = array_values($grouped);

        // Terapkan filter (untuk Export & Print): facet code/name/suspended + pencarian teks.
        $fCode = $filters['code'] ?? [];
        $fName = $filters['name'] ?? [];
        $fSusp = $filters['susp'] ?? [];
        $q = trim((string) ($filters['q'] ?? ''));

        if ($fCode !== []) {
            $list = array_values(array_filter($list, fn ($r) => in_array($r['code'], $fCode, true)));
        }
        if ($fName !== []) {
            $list = array_values(array_filter($list, fn ($r) => in_array($r['name'], $fName, true)));
        }
        if ($fSusp !== []) {
            $list = array_values(array_filter($list, fn ($r) => in_array($r['suspended'], $fSusp, true)));
        }
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $list = array_values(array_filter($list, fn ($r) => mb_strpos(mb_strtolower((string) $r['code']), $needle) !== false
                || mb_strpos(mb_strtolower((string) $r['name']), $needle) !== false
                || mb_strpos(mb_strtolower((string) $r['suspended']), $needle) !== false));
        }

        $grand = [
            'credit' => 0.0, 'not_yet_due' => 0.0, 'cod' => 0.0, 'd1_30' => 0.0,
            'd31_60' => 0.0, 'd61_90' => 0.0, 'd91_120' => 0.0, 'd120' => 0.0, 'total' => 0.0,
        ];
        foreach ($list as $r) {
            $grand['not_yet_due'] += $r['not_yet_due'];
            $grand['cod'] += $r['cod'];
            $grand['d1_30'] += $r['d1_30'];
            $grand['d31_60'] += $r['d31_60'];
            $grand['d61_90'] += $r['d61_90'];
            $grand['d91_120'] += $r['d91_120'];
            $grand['d120'] += $r['d120'];
            $grand['total'] += $r['total'];
            if ($r['credit'] !== null) {
                $grand['credit'] += $r['credit'];
            }
        }

        return ['rows' => $list, 'grand' => $grand];
    }

    private function agingBucket(mixed $terms, mixed $age): string
    {
        if ((int) $terms === 0) {
            return 'cod';
        }
        if ($age === null || (int) $age <= 0) {
            return 'not_yet_due';
        }
        $a = (int) $age;
        if ($a <= 30) {
            return 'd1_30';
        }
        if ($a <= 60) {
            return 'd31_60';
        }
        if ($a <= 90) {
            return 'd61_90';
        }
        if ($a <= 120) {
            return 'd91_120';
        }

        return 'd120';
    }

    /** Tab China Report — matriks per kategori termin × bulan invoice. */
    public function chinaReport(Request $request)
    {
        $year = $this->chinaYear($request);
        $data = $this->chinaReportData($year);

        return view('invoices.china-report', [
            'year' => $year,
            'years' => $this->reportYears(),
            'months' => $data['months'],
            'catch' => $data['catch'],
            'sections' => $data['sections'],
            'grand' => $data['grand'],
            'recap' => $data['recap'],
            'updatedAt' => now(),
        ]);
    }

    public function chinaReportExport(Request $request)
    {
        $year = $this->chinaYear($request);
        $data = $this->chinaReportData($year);

        return Excel::download(
            new ChinaReportExport($data),
            'china-report-' . ($year ?? 'all') . '.xlsx'
        );
    }

    public function chinaReportPrint(Request $request)
    {
        $year = $this->chinaYear($request);
        $data = $this->chinaReportData($year);

        return view('invoices.china-report-print', [
            'year' => $year,
            'months' => $data['months'],
            'catch' => $data['catch'],
            'sections' => $data['sections'],
            'grand' => $data['grand'],
            'recap' => $data['recap'],
            'updatedAt' => now(),
        ]);
    }

    private function chinaYear(Request $request): ?int
    {
        if ($request->has('tahun')) {
            $v = trim((string) $request->query('tahun'));

            return $v === '' ? null : (int) $v;
        }

        return null; // default: semua tahun
    }

    /** Tab Global — outstanding invoice ≤ 2024 (format sheet "AR Report Global 2024++"). */
    public function chinaReportGlobal()
    {
        $data = $this->chinaGlobalData();

        return view('invoices.china-report-global', [
            'rows' => $data['rows'],
            'grand' => $data['grand'],
            'updatedAt' => now(),
        ]);
    }

    /** Export Excel tab Global (Company Code, Company Name, CR. Term, 2024++, Balance, Last Pay Date). */
    public function chinaReportGlobalExport()
    {
        return Excel::download(
            new ChinaGlobalExport($this->chinaGlobalData()),
            'china-report-global.xlsx'
        );
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, grand: array<string, float>}
     */
    private function chinaGlobalData(): array
    {
        $invoices = OutstandingInvoice::whereNull('exception_type')
            ->where('missing_count', '<', 1)
            ->whereDate('invoice_date', '<', '2025-01-01')
            ->get(['customer_no', 'customer_name', 'terms_net_days', 'prime_owing']);

        $grouped = [];
        foreach ($invoices as $r) {
            $code = (string) $r->customer_no;
            $amt = (float) $r->prime_owing;
            $term = $this->chinaTermLabel($r->terms_net_days);

            if (! isset($grouped[$code])) {
                $grouped[$code] = ['code' => $code, 'name' => (string) $r->customer_name, 'terms' => [], 'total' => 0.0];
            }
            $grouped[$code]['terms'][$term] = ($grouped[$code]['terms'][$term] ?? 0.0) + $amt;
            $grouped[$code]['total'] += $amt;
        }

        $list = [];
        $grandTotal = 0.0;
        foreach ($grouped as $g) {
            arsort($g['terms']);
            $list[] = [
                'code' => $g['code'],
                'name' => $g['name'],
                'term' => (string) array_key_first($g['terms']),
                'amount' => $g['total'],
                'balance' => $g['total'],
            ];
            $grandTotal += $g['total'];
        }

        usort($list, fn ($a, $b) => strcmp($a['code'], $b['code']));

        return [
            'rows' => $list,
            'grand' => ['amount' => $grandTotal, 'balance' => $grandTotal],
        ];
    }

    private function chinaTermLabel(mixed $terms): string
    {
        if ($terms === null || $terms === '') {
            return 'N/A';
        }
        $t = (int) $terms;
        if ($t === 0) {
            return 'C.O.D.';
        }

        return 'NET ' . str_pad((string) $t, 2, '0', STR_PAD_LEFT) . ' DAYS';
    }

    private function chinaCategory(mixed $terms, mixed $age): string
    {
        if ($age !== null && (int) $age > 120) {
            return 'BAD DEBTS';
        }

        return $this->chinaTermLabel($terms);
    }

    /**
     * China Report — matriks outstanding per kategori termin × bulan invoice.
     *
     * Kolom bulan = 9 bulan yang diakhiri bulan terbaru `invoice_date` pada data,
     * ditambah 1 kolom catch-all "Bulan Tahun+" (sisanya) — format sample Excel.
     *
     * @return array{
     *     months: array<int, array{key: string, label: string}>,
     *     catch: array{key: string, label: string},
     *     sections: array<int, array{title: string, key: string, rows: array<int, array<string, mixed>>, subtotal: array{months: array<string, float>, catch: float, balance: float}}>,
     *     grand: array{months: array<string, float>, catch: float, balance: float},
     *     recap: array<int, array{label: string, months: array<string, float>, catch: float, total: float, pct: array<string, float>}>
     * }
     */
    private function chinaReportData(?int $year): array
    {
        $query = OutstandingInvoice::whereNull('exception_type')
            ->where('missing_count', '<', 1);
        if ($year !== null) {
            $query->whereYear('invoice_date', $year);
        }

        $rows = $query->get(['customer_no', 'customer_name', 'terms_net_days', 'age_from_due', 'invoice_date', 'prime_owing']);

        ['months' => $months, 'catch' => $catch] = $this->chinaMonthBuckets($rows->pluck('invoice_date'));
        $monthKeys = array_column($months, 'key');

        $grouped = [];
        foreach ($rows as $r) {
            $cat = $this->chinaCategory($r->terms_net_days, $r->age_from_due);
            $code = (string) $r->customer_no;
            $amt = (float) $r->prime_owing;
            $ym = $r->invoice_date?->format('Y-m');

            if (! isset($grouped[$cat][$code])) {
                $grouped[$cat][$code] = [
                    'code' => $code,
                    'name' => (string) $r->customer_name,
                    'months' => array_fill_keys($monthKeys, 0.0),
                    'catch' => 0.0,
                    'balance' => 0.0,
                ];
            }

            if ($ym !== null && isset($grouped[$cat][$code]['months'][$ym])) {
                $grouped[$cat][$code]['months'][$ym] += $amt;
            } else {
                $grouped[$cat][$code]['catch'] += $amt;
            }
            $grouped[$cat][$code]['balance'] += $amt;
        }

        $sections = [];
        foreach ($this->chinaSectionOrder($grouped) as $cat) {
            $byCode = $grouped[$cat];
            ksort($byCode, SORT_STRING);

            $list = [];
            $subtotal = ['months' => array_fill_keys($monthKeys, 0.0), 'catch' => 0.0, 'balance' => 0.0];
            foreach ($byCode as $row) {
                $row['term'] = $cat;
                $list[] = $row;

                foreach ($monthKeys as $k) {
                    $subtotal['months'][$k] += $row['months'][$k];
                }
                $subtotal['catch'] += $row['catch'];
                $subtotal['balance'] += $row['balance'];
            }

            $sections[] = [
                'title' => $this->chinaSectionTitle($cat),
                'key' => $cat,
                'rows' => $list,
                'subtotal' => $subtotal,
            ];
        }

        $grand = ['months' => array_fill_keys($monthKeys, 0.0), 'catch' => 0.0, 'balance' => 0.0];
        $recap = [];
        foreach ($sections as $s) {
            foreach ($monthKeys as $k) {
                $grand['months'][$k] += $s['subtotal']['months'][$k];
            }
            $grand['catch'] += $s['subtotal']['catch'];
            $grand['balance'] += $s['subtotal']['balance'];
        }

        foreach ($sections as $s) {
            $pct = [];
            foreach ($monthKeys as $k) {
                $total = $grand['months'][$k];
                $pct[$k] = $total > 0 ? ($s['subtotal']['months'][$k] / $total) * 100 : 0.0;
            }
            $recap[] = [
                'label' => $s['title'],
                'months' => $s['subtotal']['months'],
                'catch' => $s['subtotal']['catch'],
                'total' => $s['subtotal']['balance'],
                'pct' => $pct,
            ];
        }

        return [
            'months' => $months,
            'catch' => $catch,
            'sections' => $sections,
            'grand' => $grand,
            'recap' => $recap,
        ];
    }

    /** 9 bulan terakhir (diakhiri bulan terbaru pada data) + catch-all bulan sebelumnya ke bawah. */
    private function chinaMonthBuckets($dates): array
    {
        $max = null;
        foreach ($dates as $d) {
            if ($d === null) {
                continue;
            }
            $key = $d->format('Y-m');
            if ($max === null || $key > $max) {
                $max = $key;
            }
        }
        $max ??= now()->format('Y-m');

        [$y, $m] = array_map('intval', explode('-', $max));

        $months = [];
        for ($i = 0; $i < self::CHINA_MONTH_COLUMNS; $i++) {
            $months[] = ['key' => sprintf('%04d-%02d', $y, $m), 'label' => $this->chinaMonthLabel($y, $m)];
            $m--;
            if ($m === 0) {
                $m = 12;
                $y--;
            }
        }

        $catch = [
            'key' => sprintf('%04d-%02d', $y, $m),
            'label' => self::CHINA_MONTH_NAMES[$m - 1] . ' ' . $y . '+',
        ];

        return ['months' => $months, 'catch' => $catch];
    }

    private function chinaMonthLabel(int $y, int $m): string
    {
        return sprintf('%s-%02d', self::CHINA_MONTH_NAMES[$m - 1], $y % 100);
    }

    /** Urutan section: kategori baku lebih dulu (Bad debt di paling atas), sisanya menyusul sort. */
    private function chinaSectionOrder(array $grouped): array
    {
        $present = array_keys($grouped);
        $order = ['BAD DEBTS', 'C.O.D.', 'N/A', 'NET 07 DAYS', 'NET 14 DAYS', 'NET 21 DAYS', 'NET 30 DAYS', 'NET 60 DAYS', 'NET 120 DAYS'];

        $known = array_values(array_filter($order, fn ($c) => in_array($c, $present, true)));
        $others = array_values(array_diff($present, $known));
        sort($others);

        return array_merge($known, $others);
    }

    private function chinaSectionTitle(string $cat): string
    {
        if ($cat === 'BAD DEBTS') {
            return 'Bad debt';
        }
        if ($cat === 'C.O.D.') {
            return 'CASH';
        }
        if (preg_match('/^NET (\d+) DAYS$/', $cat, $m)) {
            return (ltrim($m[1], '0') ?: '0') . ' days';
        }

        return $cat;
    }

    private function listPage(Request $request, string $mode)
    {
        $isAnomali = $mode === 'anomali';

        $base = OutstandingInvoice::query();
        $isAnomali ? $base->whereNotNull('exception_type') : $base->whereNull('exception_type');

        $filterBulan = $this->intList($request->query('bulan'));
        $filterTahun = $this->intList($request->query('tahun'));
        $showAll = $request->query('all') === '1';
        $hasParam = $showAll || $request->query('bulan') !== null || $request->query('tahun') !== null;

        // Default: bulan ini + tahun ini; jika kosong → bulan terakhir yang ada data.
        if (! $hasParam) {
            $nowY = (int) now()->year;
            $nowM = (int) now()->month;
            $existsNow = (clone $base)->whereYear('invoice_date', $nowY)->whereMonth('invoice_date', $nowM)->exists();

            if ($existsNow) {
                $filterTahun = [$nowY];
                $filterBulan = [$nowM];
            } else {
                $maxDate = (clone $base)->max('invoice_date');
                if ($maxDate !== null) {
                    $filterTahun = [(int) substr((string) $maxDate, 0, 4)];
                    $filterBulan = [(int) substr((string) $maxDate, 5, 2)];
                } else {
                    $filterTahun = [$nowY];
                    $filterBulan = [$nowM];
                }
            }
        }

        $query = clone $base;
        if ($filterTahun !== []) {
            $query->whereRaw('YEAR(invoice_date) IN (' . implode(',', $filterTahun) . ')');
        }
        if ($filterBulan !== []) {
            $query->whereRaw('MONTH(invoice_date) IN (' . implode(',', $filterBulan) . ')');
        }

        $rows = $query->orderByDesc('invoice_date')->orderByDesc('id')->get();

        $payload = $this->buildPayload($rows);

        $availableBulan = OutstandingInvoice::query()
            ->selectRaw('DISTINCT MONTH(invoice_date) AS m')
            ->pluck('m')->filter(fn ($v) => $v !== null && (int) $v > 0)->map(fn ($v) => (int) $v)->sort()->values();
        $availableTahun = OutstandingInvoice::query()
            ->selectRaw('DISTINCT YEAR(invoice_date) AS y')
            ->pluck('y')->filter(fn ($v) => $v !== null && (int) $v > 0)->map(fn ($v) => (int) $v)->sort()->values();

        $pageTitle = $isAnomali ? 'Anomali Piutang' : 'Outstanding Invoices';
        $pageDescription = $isAnomali
            ? 'Invoice yang ditandai anomali / biaya bank / sudah bayar belum proses'
            : 'Daftar invoice outstanding (belum lunas) — hasil import Accurate';

        return view('invoices.outstanding', compact(
            'payload', 'filterBulan', 'filterTahun', 'availableBulan', 'availableTahun', 'mode', 'pageTitle', 'pageDescription'
        ));
    }

    private function buildPayload($rows): \Illuminate\Support\Collection
    {
        $invoiceNos = $rows->pluck('invoice_no')->all();

        // Peta status tukar faktur dari detail-sales INTERNAL (cocok doc_number).
        $exMap = $invoiceNos === []
            ? collect()
            : DetailSale::where('source_type', DetailSale::SOURCE_INTERNAL)
                ->whereIn('doc_number', $invoiceNos)
                ->pluck('is_exchanged', 'doc_number');

        return $rows->map(function ($r) use ($exMap) {
            $tk = 'tidak_ada';
            if ($exMap->has($r->invoice_no)) {
                $tk = ((int) $exMap[$r->invoice_no]) === 1 ? 'sudah' : 'belum';
            }

            return [
                'id' => $r->id,
                'customer_no' => $r->customer_no,
                'customer_name' => $r->customer_name,
                'customer_suspended' => $r->customer_suspended,
                'terms_net_days' => $r->terms_net_days,
                'invoice_no' => $r->invoice_no,
                'invoice_date' => $r->invoice_date?->format('Y-m-d'),
                'due_date' => $r->due_date?->format('Y-m-d'),
                'invoice_amount' => (float) $r->invoice_amount,
                'prime_owing' => (float) $r->prime_owing,
                'tax_owing' => (float) $r->tax_owing,
                'age_from_due' => $r->age_from_due,
                'credit_limit' => (float) $r->credit_limit,
                'exception_type' => $r->exception_type,
                'missing_count' => (int) $r->missing_count,
                'tukar_faktur' => $tk,
            ];
        })->values();
    }

    public function importOutstanding(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480',
        ]);

        ignore_user_abort(true);
        set_time_limit(0);

        try {
            $import = new OutstandingInvoicesImport();
            Excel::import($import, $request->file('file'));

            $created = $import->getCreatedCount();
            $updated = $import->getUpdatedCount();
            $skipped = $import->getSkippedCount();

            if ($created + $updated === 0) {
                return redirect()
                    ->route('invoices.outstanding')
                    ->with('error', 'Tidak ada data terimport. Pastikan file "Outstanding Invoices" dari Accurate.');
            }

            $message = "Import Outstanding selesai: {$created} ditambahkan, {$updated} diupdate";
            if ($skipped > 0) {
                $message .= ", {$skipped} baris dilewati";
            }
            $message .= '.';

            // Deteksi invoice yang hilang dari file: +1 semua; reset 0 untuk yang ada;
            // hapus yang sudah 3x berturut-turut tidak muncul.
            $present = $import->getPresentInvoiceNos();
            DB::transaction(function () use ($present) {
                OutstandingInvoice::query()->update(['missing_count' => DB::raw('missing_count + 1')]);
                foreach (array_chunk($present, 1000) as $chunk) {
                    OutstandingInvoice::whereIn('invoice_no', $chunk)->update(['missing_count' => 0]);
                }

                // "Sudah bayar belum proses" yang tidak ada di file → lunas tervalidasi di
                // Accurate: clear exception (pindah dari Anomali ke Outstanding, badge "Sudah Lunas").
                OutstandingInvoice::where('exception_type', 'sudah_bayar')
                    ->whereNotIn('invoice_no', $present)
                    ->update(['exception_type' => null, 'exception_at' => null]);

                OutstandingInvoice::where('missing_count', '>=', 3)->delete();
            });

            return redirect()
                ->route('invoices.outstanding')
                ->with('success', $message);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstErrors = [];
            foreach ($errors as $field => $messages) {
                $firstErrors[] = "{$field}: " . $messages[0];
            }

            return redirect()
                ->route('invoices.outstanding')
                ->with('error', 'Validasi gagal - ' . implode('; ', array_slice($firstErrors, 0, 5)));
        } catch (\Exception $e) {
            return redirect()
                ->route('invoices.outstanding')
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    /**
     * Set/clear exception (anomali | biaya_bank | sudah_bayar | clear) untuk baris terpilih.
     *
     * `sudah_bayar` = sudah bayar tapi belum divalidasi di Accurate; baris pindah ke
     * halaman Anomali dan otomatis di-clear saat upload berikutnya invoice tidak ada di file.
     */
    public function setException(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'type' => ['required', 'string', 'in:anomali,biaya_bank,sudah_bayar,clear'],
        ]);

        if ($data['type'] === 'clear') {
            $updated = OutstandingInvoice::whereIn('id', $data['ids'])
                ->update(['exception_type' => null, 'exception_at' => null]);
        } else {
            $updated = OutstandingInvoice::whereIn('id', $data['ids'])
                ->update(['exception_type' => $data['type'], 'exception_at' => now()]);
        }

        return response()->json(['ok' => true, 'updated' => $updated]);
    }

    private function intList(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $out = [];
        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if (ctype_digit($part)) {
                $out[] = (int) $part;
            }
        }

        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }
}
