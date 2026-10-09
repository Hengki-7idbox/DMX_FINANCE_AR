<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MappingCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'kode_internal',
        'kode_external',
        'nama_internal',
        'nama_external',
    ];
}
