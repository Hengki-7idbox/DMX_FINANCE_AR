<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_code',
        'group_name',
        'description',
        'status',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
