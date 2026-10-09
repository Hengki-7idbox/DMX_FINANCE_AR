<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MappingProduct extends Model
{
    protected $fillable = [
        'source_id',
        'kode_item_internal',
        'kode_item_external',
        'code_coretax',
        'nama_item_internal',
        'nama_item_external',
    ];
}
