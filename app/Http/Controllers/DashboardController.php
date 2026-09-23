<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // TODO: Get real data from database
        $data = [
            'total_outstanding' => 125500000,
            'overdue' => 45200000,
            'paid_this_month' => 85300000,
            'unreconciled' => 12800000,
        ];

        return view('dashboard', $data);
    }
}
