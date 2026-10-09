<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailSale extends Model
{
    use HasFactory;

    public const SOURCE_INTERNAL = 'internal';
    public const SOURCE_EXTERNAL = 'external';

    protected $fillable = [
        'source_type',
        'fp_status',
        'customer_code',
        'customer_name',
        'doc_number',
        'transaction_date',
        'description',
        'invoice_amount',
        'amount',
        'tax_base',
        'discount',
        'return_amount',
        'owing',
        'payment_amount',
        'calculate_field',
        'is_received',
        'received_note',
        'received_at',
        'is_exchanged',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'invoice_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax_base' => 'decimal:2',
        'discount' => 'decimal:2',
        'return_amount' => 'decimal:2',
        'owing' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'calculate_field' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DetailSaleItem::class, 'doc_number', 'doc_number')
            ->where('detail_sale_items.source_type', $this->source_type);
    }
}
