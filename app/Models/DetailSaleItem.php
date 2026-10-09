<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailSaleItem extends Model
{
    use HasFactory;

    public const SOURCE_INTERNAL = 'internal';
    public const SOURCE_EXTERNAL = 'external';

    protected $fillable = [
        'source_type',
        'doc_number',
        'customer_code',
        'customer_name',
        'transaction_date',
        'item_no',
        'item_description',
        'unit_price',
        'qty',
        'unit',
        'amount',
        'discount',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'unit_price' => 'decimal:2',
        'qty' => 'decimal:2',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
    ];
}
