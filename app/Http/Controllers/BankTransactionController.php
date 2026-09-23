<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BankTransactionController extends Controller
{
    public function index()
    {
        return view('bank-transactions.index');
    }

    public function show($id)
    {
        return view('bank-transactions.show');
    }
}
