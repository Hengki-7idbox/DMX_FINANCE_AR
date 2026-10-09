<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FakturPajak extends Model
{
    protected $fillable = [
        'status',
        'buyer_tin',
        'display_name',
        'tax_invoice_code',
        'tax_invoice_number',
        'tax_invoice_date',
        'tax_invoice_period',
        'tax_invoice_year',
        'tax_invoice_status',
        'e_sign_status',
        'selling_price',
        'other_tax_base',
        'vat',
        'signer',
        'reference',
        'reported_by_seller',
        'reported_by_vat_collector',
    ];

    protected $casts = [
        'tax_invoice_date' => 'date',
        'tax_invoice_year' => 'integer',
        'selling_price' => 'decimal:2',
        'other_tax_base' => 'decimal:2',
        'vat' => 'decimal:2',
        'reported_by_seller' => 'boolean',
        'reported_by_vat_collector' => 'boolean',
    ];
}
