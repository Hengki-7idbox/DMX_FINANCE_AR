<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        return view('payments.index');
    }

    public function show($id)
    {
        return view('payments.show');
    }

    public function createInternal()
    {
        return view('payments.create-internal');
    }

    public function createExternal()
    {
        return view('payments.create-external');
    }
}
