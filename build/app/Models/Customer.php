<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'address', 'phone', 'email',
        'sales_rep_id', 'region', 'credit_limit', 'payment_terms',
        'risk_profile', 'contact_preference', 'status',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'payment_terms' => 'integer',
    ];

    // Relationships
    public function salesRep()
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function creditLimits()
    {
        return $this->hasMany(CreditLimit::class);
    }

    public function latestCreditLimit()
    {
        return $this->hasOne(CreditLimit::class)->latestOfMany('effective_date');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    // Helpers
    public function getTotalOutstanding(): float
    {
        return (float) $this->invoices()
            ->whereIn('status', ['PENDING', 'SENT', 'PARTIAL'])
            ->sum('amount');
    }

    public function getUtilizationPct(): float
    {
        $limit = (float) $this->credit_limit;
        if ($limit <= 0) return 0;
        return ($this->getTotalOutstanding() / $limit) * 100;
    }
}
