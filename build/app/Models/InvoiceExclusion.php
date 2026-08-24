<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceExclusion extends Model
{
    protected $fillable = [
        'invoice_number', 'customer_name', 'original_amount',
        'reason_code', 'reason_detail', 'exclude_related_payment',
        'excluded_by', 'excluded_date', 'status',
        'revert_by', 'revert_date', 'revert_reason', 'notes',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'exclude_related_payment' => 'boolean',
        'excluded_date' => 'datetime',
        'revert_date' => 'datetime',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeReverted($query)
    {
        return $query->where('status', 'REVERTED');
    }
}
