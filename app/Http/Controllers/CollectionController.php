<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function dashboard()
    {
        return view('collection.dashboard');
    }

    public function queue()
    {
        return view('collection.queue');
    }

    public function schedule()
    {
        return view('collection.schedule');
    }

    public function promise()
    {
        return view('collection.promise');
    }
}
