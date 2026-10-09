<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportSession extends Model
{
    use HasFactory;

    public const TYPE_SALES = 'sales';
    public const TYPE_ITEMS = 'items';
    public const TYPE_RECAP = 'recap';

    protected $fillable = [
        'source_type',
        'import_type',
        'file_name',
        'user_id',
        'new_count',
        'changed_count',
        'unchanged_count',
        'skipped_count',
    ];

    protected $casts = [
        'new_count' => 'integer',
        'changed_count' => 'integer',
        'unchanged_count' => 'integer',
        'skipped_count' => 'integer',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(ImportSessionDetail::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
