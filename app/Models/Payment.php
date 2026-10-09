<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_number',
        'customer_id',
        'payment_date',
        'amount',
        'payment_method',
        'bank_account_id',
        'reference_number',
        'notes',
        'source_type',
        'source_payment_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function sourcePayment()
    {
        return $this->belongsTo(Payment::class, 'source_payment_id');
    }

    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
