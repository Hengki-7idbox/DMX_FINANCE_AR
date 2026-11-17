<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiveItemImport extends Model
{
    protected $fillable = [
        'original_filename',
        'exim_id',
        'branch_code',
        'total_documents',
        'total_lines',
        'new_documents',
        'existing_documents',
        'duplicate_check_status',
        'duplicate_check_message',
        'checked_at',
        'user_id',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function drafts(): HasMany
    {
        return $this->hasMany(ReceiveItemDraft::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
