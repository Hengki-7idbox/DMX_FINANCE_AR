<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiveItemDraft extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_EXISTS = 'exists';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_FAILED = 'failed';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'receive_item_import_id',
        'receive_number',
        'transaction_id',
        'sequence_no',
        'vendor_internal',
        'vendor_external',
        'vendor_name',
        'trans_date',
        'ship_date',
        'tax_date',
        'rate',
        'currency_code',
        'taxable',
        'inclusive_tax',
        'payment_term_name',
        'description',
        'warehouse_name',
        'total_amount',
        'status',
        'accurate_id',
        'accurate_number',
        'accurate_status',
        'message',
        'payload',
        'response',
        'submitted_at',
    ];

    protected $casts = [
        'trans_date' => 'date',
        'ship_date' => 'date',
        'tax_date' => 'date',
        'rate' => 'float',
        'taxable' => 'boolean',
        'inclusive_tax' => 'boolean',
        'total_amount' => 'float',
        'accurate_id' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(ReceiveItemImport::class, 'receive_item_import_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ReceiveItemDraftLine::class)->orderBy('line_no');
    }

    public function isReadOnly(): bool
    {
        return in_array($this->status, [self::STATUS_EXISTS, self::STATUS_SUBMITTED], true);
    }

    public function hasBlockedLines(): bool
    {
        if ($this->relationLoaded('lines')) {
            return $this->lines->contains(fn (ReceiveItemDraftLine $line) => $line->status === ReceiveItemDraftLine::STATUS_BLOCKED);
        }

        return $this->lines()->where('status', ReceiveItemDraftLine::STATUS_BLOCKED)->exists();
    }

    public function canSubmit(): bool
    {
        if ($this->isReadOnly()) {
            return false;
        }

        if (trim((string) $this->vendor_external) === '') {
            return false;
        }

        if ($this->relationLoaded('lines')) {
            if ($this->lines->isEmpty()) {
                return false;
            }
        } elseif (! $this->lines()->exists()) {
            return false;
        }

        return ! $this->hasBlockedLines();
    }
}
