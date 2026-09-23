<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'term_code',
        'term_name',
        'days',
        'description',
        'status',
    ];

    protected $casts = [
        'days' => 'integer',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
