<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MappingUnit extends Model
{
    /**
     * Pilihan kode unit Coretax beserta artinya.
     *
     * @var array<string,string>
     */
    public const CORETAX_UNITS = [
        'UM.0003' => 'Kilogram',
        'UM.0004' => 'Gram',
        'UM.0017' => 'Lusin',
        'UM.0021' => 'Piece',
        'UM.0022' => 'Boks',
        'UM.0033' => 'Lainnya',
    ];

    protected $fillable = ['unit_internal', 'unit_coretax'];
}
