<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MappingVendor extends Model
{
    protected $fillable = [
        'kode_vendor_internal',
        'kode_vendor_external',
        'nama_vendor_internal',
        'nama_vendor_external',
    ];
}
