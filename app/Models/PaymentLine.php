<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLine extends Model
{
    use HasFactory;

    public const SOURCE_INTERNAL = 'internal';
    public const SOURCE_EXTERNAL = 'external';
    public const SOURCE_WEB = 'web';

    protected $fillable = [
        'upload_id',
        'source_type',
        'ar_invoice_id',
        'payment_amount',
        'pph23_amount',
        'disctaken_amount',
        'sequence_no',
        'payment_date',
        'bank_account',
        'cheque_no',
        'cheque_date',
        'cheque_amount',
        'bill_to_id',
        'transaction_id',
        'description',
        'void',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'pph23_amount' => 'decimal:2',
        'disctaken_amount' => 'decimal:2',
        'payment_date' => 'date',
        'cheque_date' => 'date',
        'cheque_amount' => 'decimal:2',
        'void' => 'boolean',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(PaymentUpload::class, 'upload_id');
    }
}
