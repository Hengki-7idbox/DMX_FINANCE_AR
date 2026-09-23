<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    public const SOURCE_INTERNAL = 'internal';
    public const SOURCE_EXTERNAL = 'external';

    protected $fillable = [
        'source_type',
        'customer_code',
        'customer_name',
        'customer_group_id',
        'customer_type',
        'tax_number',
        'address',
        'city',
        'phone',
        'email',
        'payment_term_id',
        'credit_limit',
        'status',
        'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
    ];

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getOutstandingAttribute()
    {
        return $this->invoices()->sum('outstanding_amount');
    }
}
