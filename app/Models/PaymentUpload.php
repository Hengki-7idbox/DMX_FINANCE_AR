<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentUpload extends Model
{
    use HasFactory;

    public const SOURCE_INTERNAL = 'internal';
    public const SOURCE_EXTERNAL = 'external';

    protected $fillable = [
        'source_type',
        'file_name',
        'exim_id',
        'user_id',
        'receipt_count',
        'line_count',
    ];

    protected $casts = [
        'receipt_count' => 'integer',
        'line_count' => 'integer',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PaymentLine::class, 'upload_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
