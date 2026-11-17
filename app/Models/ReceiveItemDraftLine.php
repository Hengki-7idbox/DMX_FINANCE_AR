<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiveItemDraftLine extends Model
{
    public const STATUS_OK = 'ok';

    public const STATUS_WARNING = 'warning';

    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'receive_item_draft_id',
        'line_no',
        'key_id',
        'item_internal',
        'item_external',
        'item_name',
        'description',
        'quantity_xml',
        'unit_xml',
        'unit_ratio',
        'quantity',
        'unit_name',
        'price_xml',
        'unit_price',
        'disc_percent',
        'warehouse_name',
        'status',
        'message',
    ];

    protected $casts = [
        'line_no' => 'integer',
        'quantity_xml' => 'float',
        'unit_ratio' => 'float',
        'quantity' => 'float',
        'price_xml' => 'float',
        'unit_price' => 'float',
        'disc_percent' => 'float',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(ReceiveItemDraft::class, 'receive_item_draft_id');
    }

    public function lineTotal(): float
    {
        $gross = $this->quantity * $this->unit_price;
        $disc = (float) ($this->disc_percent ?? 0);

        return round($gross - ($gross * $disc / 100), 4);
    }
}
