<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalDocument extends Model
{
    use SoftDeletes;

    public const TYPE_SO = 'so';
    public const TYPE_DO = 'do';
    public const TYPE_INV = 'inv';

    public const MODULES = [
        self::TYPE_SO => 'sales-order',
        self::TYPE_DO => 'delivery-order',
        self::TYPE_INV => 'sales-invoice',
    ];

    protected $fillable = [
        'doc_number',
        'type',
        'payload',
        'response',
        'accurate_id',
        'accurate_number',
        'accurate_status',
        'http_status',
    ];

    protected $casts = [
        'accurate_id' => 'integer',
    ];
}
