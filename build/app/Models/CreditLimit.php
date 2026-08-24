<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditLimit extends Model
{
    protected $fillable = [
        'customer_id', 'credit_limit', 'effective_date',
        'expiry_date', 'approved_by', 'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'effective_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
            });
    }
}
