<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutstandingInvoice extends Model
{
    protected $fillable = [
        'customer_no',
        'customer_name',
        'customer_suspended',
        'terms_net_days',
        'invoice_no',
        'exception_type',
        'exception_at',
        'missing_count',
        'invoice_date',
        'due_date',
        'invoice_amount',
        'prime_owing',
        'tax_owing',
        'age_from_due',
        'credit_limit',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'exception_at' => 'datetime',
        'missing_count' => 'integer',
        'terms_net_days' => 'integer',
        'age_from_due' => 'integer',
        'invoice_amount' => 'decimal:2',
        'prime_owing' => 'decimal:2',
        'tax_owing' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];
}
