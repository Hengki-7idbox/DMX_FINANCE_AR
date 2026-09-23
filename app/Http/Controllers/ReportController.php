<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function aging()
    {
        return view('reports.aging');
    }

    public function outstanding()
    {
        return view('reports.outstanding');
    }

    public function payment()
    {
        return view('reports.payment');
    }

    public function reconciliation()
    {
        return view('reports.reconciliation');
    }

    public function collection()
    {
        return view('reports.collection');
    }
}
