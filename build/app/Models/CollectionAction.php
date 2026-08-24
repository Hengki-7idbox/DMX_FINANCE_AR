<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id', 'action_type', 'description', 'action_by',
        'action_date', 'status', 'notes',
    ];

    protected $casts = [
        'action_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
