<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportSessionDetail extends Model
{
    use HasFactory;

    public const TYPE_NEW = 'new';
    public const TYPE_CHANGED = 'changed';
    public const TYPE_UNCHANGED = 'unchanged';

    protected $fillable = [
        'session_id',
        'doc_number',
        'change_type',
        'transaction_date',
        'customer_name',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ImportSession::class, 'session_id');
    }
}
