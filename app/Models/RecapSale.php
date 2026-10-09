<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecapSale extends Model
{
    use HasFactory;

    public const SOURCE_INTERNAL = 'internal';
    public const SOURCE_EXTERNAL = 'external';

    protected $fillable = [
        'source_type',
        'period',
        'customer_code',
        'customer_name',
        'total_sales',
        'varian',
    ];

    protected $casts = [
        'total_sales' => 'decimal:2',
    ];
}
