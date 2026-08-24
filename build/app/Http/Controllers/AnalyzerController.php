<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomerExport;

class AnalyzerController extends Controller
{
    public function index()
    {
        $customers = Customer::active()
            ->withCount(['invoices', 'invoices as outstanding_count' => fn($q) => $q->outstanding()])
            ->withSum('invoices as total_invoiced', 'amount')
            ->get()
            ->map(fn($c) => [
                'customer' => $c,
                'outstanding' => $c->invoices->where('status', '!=', 'SETTLED')->sum('amount'),
                'avg_days_to_pay' => $c->invoices->where('status', 'SETTLED')->avg(
                    fn($i) => $i->updated_at->diffInDays($i->invoice_date)
                ) ?? 0,
            ]);

        return view('analyzer.index', compact('customers'));
    }

    public function analyze(Customer $customer)
    {
        $customer->load(['invoices' => fn($q) => $q->with('payments')]);

        $stats = [
            'total_invoiced' => $customer->invoices->sum('amount'),
            'total_paid' => $customer->invoices->where('status', 'SETTLED')->sum('amount'),
            'outstanding' => $customer->getTotalOutstanding(),
            'utilization' => $customer->getUtilizationPct(),
            'avg_days_to_pay' => $customer->invoices->where('status', 'SETTLED')->avg(
                fn($i) => $i->updated_at->diffInDays($i->invoice_date)
            ) ?? 0,
            'payment_frequency' => $customer->invoices->where('status', 'SETTLED')->count(),
            'risk_score' => $this->calculateRiskScore($customer),
        ];

        return view('analyzer.customer', compact('customer', 'stats'));
    }

    public function cohort()
    {
        $cohorts = Customer::active()
            ->selectRaw('
                region,
                COUNT(*) as customer_count,
                SUM(credit_limit) as total_limit,
                AVG(credit_limit) as avg_limit
            ')
            ->groupBy('region')
            ->get();

        return view('analyzer.cohort', compact('cohorts'));
    }

    public function export(Customer $customer)
    {
        return Excel::download(new CustomerExport($customer->id), "customer-{$customer->code}.xlsx");
    }

    private function calculateRiskScore(Customer $customer): int
    {
        $score = 50; // base

        if ($customer->getUtilizationPct() > 90) $score += 20;
        elseif ($customer->getUtilizationPct() > 70) $score += 10;

        if ($customer->risk_profile === 'blocked') $score += 30;
        elseif ($customer->risk_profile === 'risk') $score += 20;
        elseif ($customer->risk_profile === 'caution') $score += 10;

        $overdueCount = $customer->invoices()->overdue()->count();
        $score += min($overdueCount * 5, 20);

        return min($score, 100);
    }
}
