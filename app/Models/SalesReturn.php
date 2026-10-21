<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $fillable = [
        'customer_code',
        'customer_name',
        'invoice_no',
        'return_no',
        'return_date',
        'item_no',
        'item_description',
        'unit_price',
        'quantity',
        'unit',
        'amount',
    ];

    protected $casts = [
        'return_date' => 'date',
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'amount' => 'decimal:2',
    ];
}
