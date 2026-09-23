<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReconciliationController extends Controller
{
    public function index()
    {
        return view('reconciliations.index');
    }

    public function show($id)
    {
        return view('reconciliations.show');
    }

    public function center()
    {
        return view('reconciliations.center');
    }
}
