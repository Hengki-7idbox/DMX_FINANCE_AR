<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\CollectionAction;
use App\Models\InvoiceExclusion;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAr = Invoice::outstanding()->sum('amount');
        $outstanding = Invoice::outstanding()->sum('amount');
        $overdue = Invoice::overdue()->sum('amount');
        $settled = Invoice::where('status', 'SETTLED')->sum('amount');
        $collectionRate = $totalAr > 0 ? round(($settled / ($totalAr + $settled)) * 100, 1) : 0;

        // Aging buckets
        $agingData = [
            'current' => Invoice::outstanding()->byAgingBucket('current')->sum('amount'),
            '31-60' => Invoice::outstanding()->byAgingBucket('31-60')->sum('amount'),
            '61-90' => Invoice::outstanding()->byAgingBucket('61-90')->sum('amount'),
            'over90' => Invoice::outstanding()->byAgingBucket('over90')->sum('amount'),
        ];

        // Recent activity
        $recentActions = CollectionAction::with('invoice.customer')
            ->latest('action_date')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'totalAr', 'outstanding', 'overdue', 'collectionRate',
            'agingData', 'recentActions'
        ));
    }
}
